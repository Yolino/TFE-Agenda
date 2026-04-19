<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Planning;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Dompdf\Dompdf;
use Dompdf\Options;

class AdminController extends Controller
{
    public function __construct()
    {
        Carbon::setLocale('fr');
    }

    public function user()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    public function planning(Request $request)
    {
        $selectedWeek = $request->input('week', now()->format('W'));
        $selectedYear = $request->input('year', now()->year);

        $users = User::where('actif', true)->get();

        $daysInWeek = collect(range(1, 6))->map(function ($dayOffset) use ($selectedYear, $selectedWeek) {
            return Carbon::now()->setISODate($selectedYear, $selectedWeek, $dayOffset);
        });

        $daysInWeek = $daysInWeek->map(function ($day) use ($selectedYear, $selectedWeek) {
            if ($day->weekOfYear() === 1 && $selectedWeek > 51) {
                return $day->copy()->setYear($selectedYear + 1);
            } elseif ($day->weekOfYear() > 51 && $selectedWeek === 1) {
                return $day->copy()->setYear($selectedYear - 1);
            }
            return $day;
        });

        $planningEntries = $users->map(function ($user) use ($daysInWeek) {
            $entries = Planning::where('user_id', $user->id)
                ->whereIn('date', $daysInWeek->map->toDateString())
                ->with('demandeConge')
                ->get();

            if ($entries->isEmpty()) {
                $templatesByDay = $user->planningTemplates->keyBy('day_of_week');

                foreach ($daysInWeek as $day) {
                    $template = $templatesByDay->get((int) $day->isoWeekday());

                    if (!$template || $template->status === 'neant') {
                        continue;
                    }

                    Planning::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'date' => $day->toDateString(),
                        ],
                        [
                            'status_id' => $template->status_id,
                            'start_time_morning' => $template->start_time_morning,
                            'end_time_morning' => $template->end_time_morning,
                            'start_time_afternoon' => $template->start_time_afternoon,
                            'end_time_afternoon' => $template->end_time_afternoon,
                        ]
                    );
                }

                $entries = Planning::where('user_id', $user->id)
                    ->whereIn('date', $daysInWeek->map->toDateString())
                    ->with('demandeConge')
                    ->get();
            }

            return $entries;
        })->flatten();

        $planningEntries = $planningEntries->filter(function ($entry) {
            return $entry && $entry->user && $entry->user->actif;
        })->sort(function ($a, $b) {
            $typeOrder = ['B' => 1, 'S' => 2, 'C' => 3, 'I' => 4];

            $typeComparison = $typeOrder[$a->user->type] <=> $typeOrder[$b->user->type];

            if ($typeComparison === 0) {
                return strcmp($a->user->name, $b->user->name);
            }

            return $typeComparison;
        });

        return view('admin.planning', [
            'planningEntries' => $planningEntries,
            'daysInWeek' => $daysInWeek,
            'selectedWeek' => (int)$selectedWeek,
            'selectedYear' => (int)$selectedYear
        ]);
    }

    public function excelToPdf(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('excel_file');

        $spreadsheet = IOFactory::load($file->getPathname());
        $spreadsheet->setActiveSheetIndex(0);
        $calculationEngine = Calculation::getInstance($spreadsheet);
        $calculationEngine->getDebugLog()->setWriteDebugLog(false);

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');

        $pdfOptions = $pdf->getOptions();
        $pdfOptions->set('defaultPaperSize', 'A4');
        $pdfOptions->set('margin_top', '10mm');
        $pdfOptions->set('margin_right', '10mm');
        $pdfOptions->set('margin_bottom', '10mm');
        $pdfOptions->set('margin_left', '10mm');

        $allSheetsHtml = [];

        foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
            $highestColumn = $sheetIndex === 0 ? 'N' : 'N';
            $userCount = User::where('actif', true)->count();
            $highestRow = $sheetIndex === 0 ? $userCount + 1 : 11;

            $htmlContent = '<table border="1" style="border-collapse: collapse; width: 100%; font-size: 10px;">';

            foreach ($sheet->getRowIterator(1, $highestRow) as $rowIndex => $row) {
                $htmlContent .= '<tr>';
                foreach ($row->getCellIterator('A', $highestColumn) as $cell) {
                    $cellValue = $cell->getCalculatedValue();

                    if (Date::isDateTime($cell) && is_numeric($cell->getCalculatedValue())) {
                        $cellValue = Date::excelToDateTimeObject($cell->getCalculatedValue())->format('d/m/Y');
                    }

                    $cellStyle = $sheet->getStyle($cell->getCoordinate());
                    $bgColor = $cellStyle->getFill()->getStartColor()->getRGB();
                    $textColor = $cellStyle->getFont()->getColor()->getRGB();

                    $bgColorStyle = $bgColor ? 'background-color: #' . $bgColor . ';' : '';
                    $textColorStyle = $textColor ? 'color: #' . $textColor . ';' : '';

                    $mergeCells = $sheet->getMergeCells();
                    $coordinate = $cell->getCoordinate();
                    $colspan = 1;
                    $rowspan = 1;

                    foreach ($mergeCells as $mergedRange) {
                        [$startCell, $endCell] = explode(':', $mergedRange);
                        $start = Coordinate::indexesFromString($startCell);
                        $end = Coordinate::indexesFromString($endCell);

                        $current = Coordinate::indexesFromString($coordinate);

                        if ($current[0] === $start[0] && $current[1] === $start[1]) {
                            $colspan = $end[0] - $start[0] + 1;
                            $rowspan = $end[1] - $start[1] + 1;
                            break;
                        } elseif ($current[0] >= $start[0] && $current[0] <= $end[0] && $current[1] >= $start[1] && $current[1] <= $end[1]) {
                            continue 2;
                        }
                    }

                    $alignmentStyle = '';
                    if($rowIndex === 1) {
                        $alignmentStyle = 'text-align: center;';
                    } elseif ($coordinate[0] === 'A') {
                        $alignmentStyle = 'writing-mode: vertical-rl; text-orientation: mixed; text-align: center;';
                    } else {
                        $alignmentStyle = 'text-align: center; white-space: pre-wrap;';
                    }

                    $htmlContent .= '<td style="' . $bgColorStyle . $textColorStyle . $alignmentStyle . ' padding: 5px;" colspan="' . $colspan . '" rowspan="' . $rowspan . '">' . htmlspecialchars($cellValue) . '</td>';
                }
                $htmlContent .= '</tr>';
            }

            $htmlContent .= '</table>';

            if ($sheetIndex === 0) {
                $htmlContent = '<div style="margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; transform: scale(1); transform-origin: top left;">' . $htmlContent . '</div>';
            }

            $allSheetsHtml[] = $htmlContent;
        }

        $finalPdf = '';
        foreach ($allSheetsHtml as $sheetIndex => $sheetHtml) {
            $pageBreakStyle = $sheetIndex < count($allSheetsHtml) - 1 ? 'page-break-after: always;' : '';
            $finalPdf .= '<div style="' . $pageBreakStyle . ' width: 100%; height: 100%;">' . $sheetHtml . '</div>';
        }

        $pdf->loadHtml($finalPdf);
        $pdf->render();

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'converted.pdf'
        );
    }

    public function exportPlanning(Request $request, $week, $year)
    {
        $selectedWeek = $week ?? now()->format('W');
        $selectedYear = $year ?? now()->year;

        $daysInWeek = collect(range(1, 6))->map(function ($dayOffset) use ($selectedYear, $selectedWeek) {
            return now()->setISODate($selectedYear, $selectedWeek, $dayOffset);
        });

        $planningEntries = Planning::whereIn('date', $daysInWeek->map->toDateString())
            ->get()
            ->sort(function ($a, $b) {
                $typeOrder = ['B' => 1, 'S' => 2, 'C' => 3, 'I' => 4];

                $typeComparison = $typeOrder[$a->user->type] <=> $typeOrder[$b->user->type];

                if ($typeComparison === 0) {
                    return strcmp($a->user->name, $b->user->name);
                }

                return $typeComparison;
            });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('B1', 'Nom - Prénom');
        $sheet->setCellValue('C1', 'Societes');
        foreach ($daysInWeek as $index => $day) {
            $columnC = Coordinate::stringFromColumnIndex($index * 2 + 4);
            $columnD = Coordinate::stringFromColumnIndex($index * 2 + 5);
            $value = $day->translatedFormat('l d-m-Y');
            $sheet->setCellValue($columnC . '1', strtoupper($value));
            $sheet->mergeCells($columnC . '1:' . $columnD . '1');
            $sheet->getStyle($columnC . '1')->getAlignment()->setHorizontal('center');
        }

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($daysInWeek->count() * 2 + 3) . '1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
        ]);

        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFF2CC',
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($daysInWeek->count() * 2 + 3) . '1')->applyFromArray($headerStyle);

        $sheet->getRowDimension(1)->setRowHeight(30);

        $row = 2;
        foreach ($planningEntries->groupBy('user.type') as $type => $entriesByType) {
            $startRow = $row;

            foreach ($entriesByType->groupBy('user.id') as $userId => $entriesByUser) {
                $typeLabel = match ($type) {
                    'B' => 'Salaire',
                    'S' => 'Secrétariat',
                    'C' => 'Comptabilité',
                    'I' => 'Informatique',
                    default => 'Inconnu',
                };

                $sheet->setCellValue('A' . $row, $typeLabel);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);
                $sheet->getStyle('A' . $row)->getAlignment()->setTextRotation(90);
                $sheet->setCellValue('B' . $row, $entriesByUser->first()->user->name . ' ' . $entriesByUser->first()->user->firstname . "\n" . $entriesByUser->first()->user->phone . "\n" . $entriesByUser->first()->user->fixe);
                $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);

                $societes = explode(',', $entriesByUser->first()->user->remarque);
                $sheet->setCellValue('C' . $row, implode("\n", $societes));
                $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('C' . $row)->getFont()->setSize(5);

                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $phoneStr = (string)($entriesByUser->first()->user->phone ?? '');
                $richText->createText($entriesByUser->first()->user->name . ' ' . $entriesByUser->first()->user->firstname . "\n" . $phoneStr . "\n");

                $fixeStr = (string)($entriesByUser->first()->user->fixe ?? '');
                if (!empty($fixeStr)) {
                    $fixeText = $richText->createTextRun($fixeStr);
                    $fixeText->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
                    $fixeText->getFont()->setBold(true);
                }

                $sheet->setCellValue('B' . $row, $richText);
                $sheet->getStyle('B' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                foreach ($daysInWeek as $index => $day) {
                    $columnC = Coordinate::stringFromColumnIndex($index * 2 + 4);
                    $columnD = Coordinate::stringFromColumnIndex($index * 2 + 5);

                    $entriesForDay = $entriesByUser->where('date', $day->toDateString());

                    if ($entriesForDay->isNotEmpty()) {
                        $content = '';

                        foreach ($entriesForDay as $entry) {
                            $entryContent = '';
                            if ($entry->status) {
                                $status = $entry->status === 'tele_travail' ? 'DOMICILE' : strtoupper($entry->status);
                                $entryContent .= $status . "\n";

                                $sheet->getStyle($columnC . $row)->applyFromArray([
                                    'font' => [
                                        'italic' => true,
                                    ],
                                ]);

                                if ($status !== 'BUREAU') {
                                    $sheet->getStyle($columnC . $row)->applyFromArray([
                                        'fill' => [
                                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                            'startColor' => [
                                                'rgb' => 'D0CECE',
                                            ],
                                        ],
                                    ]);
                                    $sheet->getStyle($columnC . $row)->applyFromArray([
                                        'font' => [
                                            'bold' => true,
                                        ],
                                    ]);
                                    if ($status === 'DOMICILE') {
                                        $sheet->getStyle($columnC . $row)->applyFromArray([
                                            'font' => [
                                                'color' => ['rgb' => '008000'],
                                            ],
                                        ]);
                                    } else {
                                        $sheet->getStyle($columnC . $row)->applyFromArray([
                                            'font' => [
                                                'color' => ['rgb' => 'FF0000'],
                                            ],
                                        ]);
                                    }
                                }
                                if ($entry->start_time && $entry->end_time) {
                                    $startTime = substr($entry->start_time, 0, 2) . 'h' . substr($entry->start_time, 3, 2);
                                    $endTime = substr($entry->end_time, 0, 2) . 'h' . substr($entry->end_time, 3, 2);
                                    $entryContent .= $startTime . ' à ' . $endTime;
                                }
                                if ($entry->start_time_afternoon && $entry->end_time_afternoon) {
                                    $startTimeAfternoon = substr($entry->start_time_afternoon, 0, 2) . 'h' . substr($entry->start_time_afternoon, 3, 2);
                                    $endTimeAfternoon = substr($entry->end_time_afternoon, 0, 2) . 'h' . substr($entry->end_time_afternoon, 3, 2);
                                    $entryContent .= "\n" . $startTimeAfternoon . ' à ' . $endTimeAfternoon;
                                }
                            }

                            $content .= trim($entryContent) . "\n---\n";
                        }

                        $content = rtrim($content, "\n---\n");

                        $sheet->setCellValue($columnC . $row, $content);
                        $sheet->mergeCells($columnC . $row . ':' . $columnD . $row);
                        $sheet->getStyle($columnC . $row)->getAlignment()->setWrapText(true);
                    }
                }

                $backgroundColor = match ($type) {
                    'B' => 'A9D08E',
                    'S' => 'F4B084',
                    'C' => 'DDEBF7',
                    'I' => 'FCE4D6',
                    default => 'FFFFFF',
                };

                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => $backgroundColor,
                        ],
                    ],
                ]);

                $row++;
            }

            if ($row - 1 > $startRow) {
                $sheet->mergeCells("A{$startRow}:A" . ($row - 1));
                for ($currentRow = $startRow; $currentRow < $row; $currentRow++) {
                    $sheet->getRowDimension($currentRow)->setRowHeight(50);
                }
            }
        }

        foreach (range(1, $daysInWeek->count() * 2 + 3) as $columnIndex) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            if ($columnIndex >= 3) {
                $sheet->getColumnDimension($columnLetter)->setWidth(13);
            } else {
                $sheet->getColumnDimension($columnLetter)->setWidth(20);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(5);

        $lastColumn = Coordinate::stringFromColumnIndex($daysInWeek->count() * 2 + 3);
        $lastRow = $row - 1;
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray($styleArray);

        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'planning_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = storage_path('app/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}

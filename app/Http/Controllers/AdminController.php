<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Planning;
use App\Models\Agence;
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

    /**
     * Résout l'agence active : query string `agence_id` ou première agence du user connecté.
     */
    private function resolveCurrentAgenceId(Request $request): ?int
    {
        $fallback = $request->user()->agences->first()?->id;
        return (int) $request->input('agence_id', $fallback) ?: null;
    }

    /**
     * Renvoie les user_id liés à une agence via le pivot bti pivot_a_u.
     */
    private function userIdsInAgence(int $agenceId): \Illuminate\Support\Collection
    {
        return DB::connection('bti')->table('pivot_a_u')
            ->where('agence_id', $agenceId)
            ->pluck('user_id');
    }

    private function belgianHolidays(int $year): array
    {
        $a = $year % 19; $b = intdiv($year, 100); $c = $year % 100;
        $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3); $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;
        $easter = Carbon::createFromDate($year, $month, $day);

        return [
            "$year-01-01" => 'Nouvel An',
            $easter->copy()->addDay()->format('Y-m-d')    => 'Lundi de Pâques',
            "$year-05-01" => 'Fête du Travail',
            $easter->copy()->addDays(39)->format('Y-m-d') => 'Ascension',
            $easter->copy()->addDays(50)->format('Y-m-d') => 'Lundi de Pentecôte',
            "$year-07-21" => 'Fête nationale',
            "$year-08-15" => 'Assomption',
            "$year-11-01" => 'Toussaint',
            "$year-11-11" => 'Armistice',
            "$year-12-25" => 'Noël',
        ];
    }

    public function planning(Request $request)
    {
        // Par défaut on affiche la semaine suivante (l'admin prépare le planning à venir).
        $defaultDate = now()->addWeek();
        $selectedWeek = $request->input('week', $defaultDate->format('W'));
        $selectedYear = $request->input('year', $defaultDate->isoWeekYear);

        $selectedAgenceId = $this->resolveCurrentAgenceId($request);
        $currentAgence    = $selectedAgenceId ? Agence::with('societe')->find($selectedAgenceId) : null;

        $ids = $this->userIdsInAgence($currentAgence->id);
        
        $users = $currentAgence
            ? User::with(['planningTemplates', 'profile', 'departements'])
                ->where('actif', true)
                ->whereIn('id', $ids)
                ->get()
            : collect();

        $usersById = $users->keyBy('id');

        $daysInWeek = collect(range(1, 6))->map(function ($dayOffset) use ($selectedYear, $selectedWeek) {
            return Carbon::now()->setISODate($selectedYear, $selectedWeek, $dayOffset);
        });

        $weeksInYear     = (int) Carbon::create((int) $selectedYear, 12, 28)->format('W');
        $weeksInPrevYear = (int) Carbon::create((int) $selectedYear - 1, 12, 28)->format('W');

        $daysInWeek = $daysInWeek->map(function ($day) use ($selectedYear, $selectedWeek, $weeksInYear, $weeksInPrevYear) {
            if ($day->isoWeek() === 1 && $selectedWeek === $weeksInYear) {
                return $day->copy()->setYear($selectedYear + 1);
            } elseif ($day->isoWeek() === $weeksInPrevYear && $selectedWeek === 1) {
                return $day->copy()->setYear($selectedYear - 1);
            }
            return $day;
        });

        $years = $daysInWeek->map->year->unique();
        $holidays = [];
        foreach ($years as $y) {
            $holidays += $this->belgianHolidays($y);
        }

        $dateStrings = $daysInWeek->map->toDateString();

        $allEntriesByUser = Planning::whereIn('date', $dateStrings)
            ->whereIn('user_id', $users->pluck('id'))
            ->with('demandeConge')
            ->get()
            ->groupBy('user_id');

        $planningEntries = collect();

        foreach ($users as $user) {
            $isStudent = str_contains((string) ($user->acces_level ?? ''), 'ET');
            $userEntriesByDate = ($allEntriesByUser->get($user->id) ?? collect())->keyBy('date');
            $templatesByDay = $user->planningTemplates->keyBy('day_of_week');

            foreach ($daysInWeek as $day) {
                $dateStr = $day->toDateString();

                if (isset($holidays[$dateStr])) {
                    continue;
                }

                if ($userEntriesByDate->has($dateStr)) {
                    continue;
                }

                // Solution A : pas d'auto-création depuis template pour les étudiants
                // (l'admin les assigne manuellement via la modale "+" du département)
                if ($isStudent) {
                    continue;
                }

                $template = $templatesByDay->get((int) $day->isoWeekday());

                if (!$template || $template->status === 'neant') {
                    continue;
                }

                $new = Planning::create([
                    'user_id'              => $user->id,
                    'date'                 => $dateStr,
                    'status_id'            => $template->status_id,
                    'start_time_morning'   => $template->start_time_morning,
                    'end_time_morning'     => $template->end_time_morning,
                    'start_time_afternoon' => $template->start_time_afternoon,
                    'end_time_afternoon'   => $template->end_time_afternoon,
                ]);

                $userEntriesByDate->put($dateStr, $new->load('demandeConge'));
            }

            $planningEntries = $planningEntries->merge($userEntriesByDate->values());
        }

        // Solution A : un étudiant n'apparaît dans la grille que s'il a ≥1 entrée pour la semaine
        $studentIdsWithEntries = $planningEntries->pluck('user_id')->unique();
        $users = $users->filter(function ($u) use ($studentIdsWithEntries) {
            $isStudent = str_contains((string) ($u->acces_level ?? ''), 'ET');
            return !$isStudent || $studentIdsWithEntries->contains($u->id);
        })->values();

        $typeOrder = ['D' => 1, 'S' => 2, 'B' => 3, 'C' => 4, 'I' => 5, 'M' => 6, 'O' => 7, 'G' => 8, 'N' => 9, 'F' => 10, 'V' => 11];
        $planningEntries = $planningEntries->sort(function ($a, $b) use ($usersById, $typeOrder) {
            $userA = $usersById->get($a->user_id);
            $userB = $usersById->get($b->user_id);

            $letterA = $userA->departements->first()?->letter;
            $letterB = $userB->departements->first()?->letter;

            $typeComparison = ($typeOrder[$letterA] ?? 99) <=> ($typeOrder[$letterB] ?? 99);

            if ($typeComparison === 0) {
                return strcmp($userA->name, $userB->name);
            }

            return $typeComparison;
        });

        return view('admin.planning', [
            'planningEntries' => $planningEntries,
            'daysInWeek'      => $daysInWeek,
            'selectedWeek'    => (int) $selectedWeek,
            'selectedYear'    => (int) $selectedYear,
            'holidays'        => $holidays,
            'users'           => $users,
            'currentAgence'   => $currentAgence,
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

        $userCount = User::where('actif', true)->count();
        foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
            $highestColumn = $sheetIndex === 0 ? 'N' : 'N';
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

        $selectedAgenceId = $this->resolveCurrentAgenceId($request);
        $userIdsInAgence  = $selectedAgenceId
            ? $this->userIdsInAgence($selectedAgenceId)
            : collect();

        // Approche user-centric : on charge TOUS les utilisateurs actifs de l'agence,
        // même ceux sans entrée pour la semaine, pour que le tableau soit complet.
        $users = User::with(['profile', 'departements', 'agences.societe'])
            ->where('actif', true)
            ->whereIn('id', $userIdsInAgence)
            ->get();

        // Entrées indexées par user_id pour lookup rapide
        $planningEntriesByUser = Planning::whereIn('date', $daysInWeek->map->toDateString())
            ->whereIn('user_id', $userIdsInAgence)
            ->get()
            ->groupBy('user_id');

        $years = $daysInWeek->map->year->unique();
        $holidays = [];
        foreach ($years as $y) {
            $holidays += $this->belgianHolidays($y);
        }

        $typeOrder = ['D' => 1, 'S' => 2, 'B' => 3, 'C' => 4, 'I' => 5, 'M' => 6, 'O' => 7, 'G' => 8, 'N' => 9, 'F' => 10, 'V' => 11];

        $activeUsers = $users
            ->sortBy([
                fn($a, $b) => ($typeOrder[$a->departements->first()?->letter] ?? 99) <=> ($typeOrder[$b->departements->first()?->letter] ?? 99),
                fn($a, $b) => strcmp($a->name, $b->name),
            ])
            ->groupBy(fn($u) => $u->departements->first()?->letter ?? '?')
            ->sortBy(fn($group, $type) => $typeOrder[$type] ?? 99);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('B1', 'Nom - Prénom');
        $sheet->setCellValue('C1', 'Agence');
        foreach ($daysInWeek as $index => $day) {
            $columnC = Coordinate::stringFromColumnIndex($index * 2 + 4);
            $columnD = Coordinate::stringFromColumnIndex($index * 2 + 5);
            $dateStr = $day->toDateString();
            $isHoliday = isset($holidays[$dateStr]);

            $value = strtoupper($day->translatedFormat('l d-m-Y'));
            if ($isHoliday) {
                $value .= "\n" . $holidays[$dateStr];
            }
            $sheet->setCellValue($columnC . '1', $value);
            $sheet->mergeCells($columnC . '1:' . $columnD . '1');
            $sheet->getStyle($columnC . '1')->getAlignment()->setHorizontal('center');
            $sheet->getStyle($columnC . '1')->getAlignment()->setWrapText(true);

            if ($isHoliday) {
                $sheet->getStyle($columnC . '1:' . $columnD . '1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '99F6E4'],
                    ],
                    'font' => ['color' => ['rgb' => '134E4A']],
                ]);
            }
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
        foreach ($activeUsers as $type => $usersInGroup) {
            $startRow = $row;

            foreach ($usersInGroup->sortBy('name') as $user) {
                $userEntries = $planningEntriesByUser->get($user->id, collect());

                $typeLabel = $user->departements->first()?->nom ?? '—';

                $sheet->setCellValue('A' . $row, $typeLabel);
                $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
                $sheet->getStyle('A' . $row)->getAlignment()->setTextRotation(90);

                // Texte enrichi : nom + téléphone + fixe (fixe en rouge gras)
                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $phoneStr = (string)($user->phone ?? '');
                $richText->createText($user->name . ' ' . $user->firstname . "\n" . $phoneStr . "\n");

                $fixeStr = (string)($user->profile?->fixe ?? '');
                if (!empty($fixeStr)) {
                    $fixeRun = $richText->createTextRun($fixeStr);
                    $fixeRun->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
                    $fixeRun->getFont()->setBold(true);
                }

                $sheet->setCellValue('B' . $row, $richText);
                $sheet->getStyle('B' . $row)->applyFromArray(['font' => ['bold' => true]]);
                $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);

                $agencesNames = $user->agences->map(fn($a) => $a->display_name)->implode("\n");
                $sheet->setCellValue('C' . $row, $agencesNames);
                $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('C' . $row)->getFont()->setSize(5);

                foreach ($daysInWeek as $index => $day) {
                    $columnC = Coordinate::stringFromColumnIndex($index * 2 + 4);
                    $columnD = Coordinate::stringFromColumnIndex($index * 2 + 5);
                    $dateStr = $day->toDateString();
                    $isHoliday = isset($holidays[$dateStr]);

                    $entriesForDay = $userEntries->where('date', $dateStr);

                    if ($isHoliday) {
                        $sheet->setCellValue($columnC . $row, 'JOUR FÉRIÉ' . "\n" . $holidays[$dateStr]);
                        $sheet->mergeCells($columnC . $row . ':' . $columnD . $row);
                        $sheet->getStyle($columnC . $row)->applyFromArray([
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '99F6E4']],
                            'font' => ['bold' => true, 'color' => ['rgb' => '134E4A']],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                        ]);
                    } elseif ($entriesForDay->isNotEmpty()) {
                        $content = '';

                        foreach ($entriesForDay as $entry) {
                            $entryContent = '';
                            if ($entry->status) {
                                $status = match($entry->status) {
                                    'tele_travail' => 'DOMICILE',
                                    'custom' => strtoupper($entry->custom ?? 'PERSONNALISÉ'),
                                    default => strtoupper($entry->status),
                                };
                                $entryContent .= $status . "\n";

                                $sheet->getStyle($columnC . $row)->applyFromArray(['font' => ['italic' => true]]);

                                if ($status !== 'BUREAU') {
                                    $sheet->getStyle($columnC . $row)->applyFromArray([
                                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D0CECE']],
                                        'font' => ['bold' => true],
                                    ]);
                                    // tele_travail et custom → vert ; absences classiques → rouge
                                    $colorCode = in_array($entry->status, ['tele_travail', 'custom']) ? '008000' : 'FF0000';
                                    $sheet->getStyle($columnC . $row)->applyFromArray(['font' => ['color' => ['rgb' => $colorCode]]]);
                                }

                                // custom rejoint les statuts sans horaires dans la cellule
                                $hideTimes = in_array($entry->status, ['indisponible', 'recup', 'conge', 'maladie', 'custom'], true);
                                if (!$hideTimes && $entry->start_time && $entry->end_time) {
                                    $startTime = substr($entry->start_time, 0, 2) . 'h' . substr($entry->start_time, 3, 2);
                                    $endTime   = substr($entry->end_time, 0, 2) . 'h' . substr($entry->end_time, 3, 2);
                                    $entryContent .= $startTime . ' à ' . $endTime;
                                }
                                if (!$hideTimes && $entry->start_time_afternoon && $entry->end_time_afternoon) {
                                    $startAft = substr($entry->start_time_afternoon, 0, 2) . 'h' . substr($entry->start_time_afternoon, 3, 2);
                                    $endAft   = substr($entry->end_time_afternoon, 0, 2) . 'h' . substr($entry->end_time_afternoon, 3, 2);
                                    $entryContent .= "\n" . $startAft . ' à ' . $endAft;
                                }
                            }
                            $content .= trim($entryContent) . "\n---\n";
                        }

                        $content = rtrim($content, "\n---\n");
                        $sheet->setCellValue($columnC . $row, $content);
                        $sheet->mergeCells($columnC . $row . ':' . $columnD . $row);
                        $sheet->getStyle($columnC . $row)->getAlignment()->setWrapText(true);
                    } else {
                        // Cellule vide — on fusionne quand même les 2 colonnes du jour
                        $sheet->mergeCells($columnC . $row . ':' . $columnD . $row);
                    }
                }

                $backgroundColor = match ($type) {
                    'D' => 'D9D2E9',
                    'S' => 'F4B084',
                    'B' => 'A9D08E',
                    'C' => 'DDEBF7',
                    'I' => 'FCE4D6',
                    'M' => 'FFE699',
                    'O' => 'DEC5A4',
                    'G' => 'CFD9E4',
                    'N' => 'A8D4BC',
                    'F' => 'F4B3C2',
                    'V' => 'BDD7EE',
                    default => 'FFFFFF',
                };

                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $backgroundColor],
                    ],
                ]);

                $row++;
            }

            if ($row - 1 >= $startRow) {
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

    public function exportPlanningPdf(Request $request, $week, $year)
    {
        $selectedWeek = $week ?? now()->format('W');
        $selectedYear = $year ?? now()->year;

        $daysInWeek = collect(range(1, 6))->map(function ($dayOffset) use ($selectedYear, $selectedWeek) {
            return now()->setISODate($selectedYear, $selectedWeek, $dayOffset);
        });

        $selectedAgenceId = $this->resolveCurrentAgenceId($request);
        $currentAgence    = $selectedAgenceId ? Agence::with('societe')->find($selectedAgenceId) : null;
        $userIdsInAgence  = $selectedAgenceId
            ? $this->userIdsInAgence($selectedAgenceId)
            : collect();

        $planningEntries = Planning::whereIn('date', $daysInWeek->map->toDateString())
            ->whereIn('user_id', $userIdsInAgence)
            ->with(['user.profile', 'user.departements', 'user.agences.societe'])
            ->get()
            ->filter(fn($entry) => $entry->user !== null)
            ->sort(function ($a, $b) {
                $typeOrder = ['D' => 1, 'S' => 2, 'B' => 3, 'C' => 4, 'I' => 5, 'M' => 6, 'O' => 7, 'G' => 8, 'N' => 9, 'F' => 10, 'V' => 11];
                $letterA = $a->user->departements->first()?->letter;
                $letterB = $b->user->departements->first()?->letter;
                $typeComparison = ($typeOrder[$letterA] ?? 99) <=> ($typeOrder[$letterB] ?? 99);
                return $typeComparison === 0 ? strcmp($a->user->name, $b->user->name) : $typeComparison;
            });

        $years = $daysInWeek->map->year->unique();
        $holidays = [];
        foreach ($years as $y) {
            $holidays += $this->belgianHolidays($y);
        }

        $users = User::with(['profile', 'departements', 'agences.societe'])
            ->where('actif', true)
            ->whereIn('id', $userIdsInAgence)
            ->get();

        $typeOrder = ['D' => 1, 'S' => 2, 'B' => 3, 'C' => 4, 'I' => 5, 'M' => 6, 'O' => 7, 'G' => 8, 'N' => 9, 'F' => 10, 'V' => 11];
        $congeTypeLabels = [
            'recup' => 'Récup.',
            'conge' => 'Congé (VA)',
            'css' => 'CSS',
            'visite' => 'Visite méd.',
            'autre' => 'Autre',
        ];

        $activeUsers = $users->sortBy([
                fn($a, $b) => ($typeOrder[$a->departements->first()?->letter] ?? 99) <=> ($typeOrder[$b->departements->first()?->letter] ?? 99),
                fn($a, $b) => strcmp($a->name, $b->name),
            ])
            ->groupBy(fn($u) => $u->departements->first()?->letter ?? '?')
            ->sortBy(fn($group, $type) => $typeOrder[$type] ?? 99);

        $deptLabels = $users
            ->mapWithKeys(fn($u) => [$u->departements->first()?->letter ?? '?' => $u->departements->first()?->nom ?? '—']);

        $deptColors = [
            'D' => ['bg' => '#e9e4f8', 'text' => '#4a1870'],
            'S' => ['bg' => '#fce5cd', 'text' => '#783f04'],
            'B' => ['bg' => '#d9ead3', 'text' => '#274e13'],
            'C' => ['bg' => '#dae8fc', 'text' => '#1c4587'],
            'I' => ['bg' => '#fff2cc', 'text' => '#7f6000'],
            'M' => ['bg' => '#fff8cc', 'text' => '#7d5a00'],
            'O' => ['bg' => '#ede3ca', 'text' => '#5a3e1a'],
            'G' => ['bg' => '#e3ecf2', 'text' => '#2c3e50'],
            'N' => ['bg' => '#d0ece2', 'text' => '#1a4a3a'],
            'F' => ['bg' => '#fad8e6', 'text' => '#7a1b3a'],
            'V' => ['bg' => '#d5e8f9', 'text' => '#1a3060'],
        ];

        $html = view('admin.planning_pdf', compact(
            'selectedWeek', 'selectedYear', 'daysInWeek',
            'planningEntries', 'holidays', 'activeUsers',
            'deptLabels', 'congeTypeLabels', 'deptColors',
            'currentAgence'
        ))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultMediaType', 'print');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $text = "Page $pageNumber / $pageCount";
            $size = 7;
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text(
                $canvas->get_width() - $textWidth - 20,
                $canvas->get_height() - 15,
                $text, $font, $size, [0.3, 0.3, 0.3]
            );
        });

        $fileName = 'planning_semaine' . $selectedWeek . '_' . $selectedYear . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}

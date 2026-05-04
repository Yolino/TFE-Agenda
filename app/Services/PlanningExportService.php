<?php

namespace App\Services;

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanningExportService
{
    public function __construct(private AdminController $admin) {}

    public function generateExcel(int $week, int $year): string
    {
        $response = $this->admin->exportPlanning(new Request(), $week, $year);

        $sourcePath = $response->getFile()->getPathname();
        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.xlsx');
        copy($sourcePath, $filePath);

        return $filePath;
    }

    public function generatePdf(int $week, int $year): string
    {
        $response = $this->admin->exportPlanningPdf(new Request(), $week, $year);

        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.pdf');
        file_put_contents($filePath, $response->getContent());

        return $filePath;
    }
}

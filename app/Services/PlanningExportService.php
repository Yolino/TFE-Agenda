<?php

namespace App\Services;

use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanningExportService
{
    public function __construct(private AdminController $admin) {}

    public function generateExcel(int $week, int $year, ?int $agenceId = null): string
    {
        $response = $this->admin->exportPlanning($this->buildRequest($agenceId), $week, $year);

        $sourcePath = $response->getFile()->getPathname();
        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.xlsx');
        copy($sourcePath, $filePath);
        @unlink($sourcePath);

        return $filePath;
    }

    public function generatePdf(int $week, int $year, ?int $agenceId = null): string
    {
        $response = $this->admin->exportPlanningPdf($this->buildRequest($agenceId), $week, $year);

        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.pdf');
        file_put_contents($filePath, $response->getContent());

        return $filePath;
    }

    private function buildRequest(?int $agenceId): Request
    {
        $request = new Request();
        $request->setUserResolver(fn () => auth()->user());

        if ($agenceId !== null) {
            $request->merge(['agence_id' => $agenceId]);
        }

        return $request;
    }
}

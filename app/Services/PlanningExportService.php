<?php

namespace App\Services;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanningExportService
{
    public function __construct(private AdminController $admin) {}

    public function generateExcel(int $week, int $year, ?int $agenceId = null, ?User $actingUser = null): string
    {
        $response = $this->admin->exportPlanning($this->buildRequest($agenceId, $actingUser), $week, $year);

        $sourcePath = $response->getFile()->getPathname();
        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.xlsx');
        copy($sourcePath, $filePath);
        @unlink($sourcePath);

        return $filePath;
    }

    public function generatePdf(int $week, int $year, ?int $agenceId = null, ?User $actingUser = null): string
    {
        $response = $this->admin->exportPlanningPdf($this->buildRequest($agenceId, $actingUser), $week, $year);

        $filePath = storage_path('app/planning_' . $week . '_' . $year . '.pdf');
        file_put_contents($filePath, $response->getContent());

        return $filePath;
    }

    private function buildRequest(?int $agenceId, ?User $actingUser = null): Request
    {
        $request = new Request();
        $request->setUserResolver(fn () => $actingUser ?? auth()->user());

        if ($agenceId !== null) {
            $request->merge(['agence_id' => $agenceId]);
        }

        return $request;
    }
}

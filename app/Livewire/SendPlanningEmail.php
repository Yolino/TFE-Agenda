<?php

namespace App\Livewire;

use App\Mail\PlanningEmail;
use App\Models\Agence;
use App\Services\PlanningExportService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SendPlanningEmail extends Component
{
    public int $week;
    public int $year;
    public ?int $agenceId = null;

    #[Validate('required|email')]
    public string $recipient = '';

    public function mount(int $week, int $year, ?int $agenceId = null): void
    {
        $this->week = $week;
        $this->year = $year;
        $this->agenceId = $agenceId;
    }

    public function send(PlanningExportService $exporter): void
    {
        $this->validate();

        $pdfPath   = $exporter->generatePdf($this->week, $this->year, $this->agenceId);
        $excelPath = $exporter->generateExcel($this->week, $this->year, $this->agenceId);
        $sender    = auth()->user();
        $agence    = $this->agenceId ? Agence::find($this->agenceId) : null;

        Mail::to($this->recipient)->send(new PlanningEmail(
            week: $this->week,
            year: $this->year,
            pdfPath: $pdfPath,
            excelPath: $excelPath,
            senderName: trim($sender->name . ' ' . $sender->firstname),
            senderTitle: $sender->fonction ?? null,
            senderPhone: $sender->phone ?? null,
            agenceName: $agence?->display_name,
        ));

        @unlink($pdfPath);
        @unlink($excelPath);

        $this->reset('recipient');
        $this->dispatch('planning-emailed');
    }

    public function render()
    {
        return view('livewire.send-planning-email');
    }
}

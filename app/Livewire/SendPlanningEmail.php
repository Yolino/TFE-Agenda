<?php

namespace App\Livewire;

use App\Mail\PlanningEmail;
use App\Services\PlanningExportService;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SendPlanningEmail extends Component
{
    public int $week;
    public int $year;

    #[Validate('required|email')]
    public string $recipient = '';

    public function mount(int $week, int $year): void
    {
        $this->week = $week;
        $this->year = $year;
    }

    public function send(PlanningExportService $exporter): void
    {
        $this->validate();

        $pdfPath   = $exporter->generatePdf($this->week, $this->year);
        $excelPath = $exporter->generateExcel($this->week, $this->year);
        $sender    = auth()->user();

        Mail::to($this->recipient)->send(new PlanningEmail(
            week: $this->week,
            year: $this->year,
            pdfPath: $pdfPath,
            excelPath: $excelPath,
            senderName: trim($sender->name . ' ' . $sender->firstname),
            senderTitle: $sender->remarque ?? null,
            senderPhone: $sender->phone ?? null,
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

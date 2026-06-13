<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanningEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $dateFrom;
    public Carbon $dateTo;

    public function __construct(
        public int $week,
        public int $year,
        public string $pdfPath,
        public string $excelPath,
        public ?string $senderName = null,
        public ?string $senderTitle = null,
        public ?string $senderPhone = null,
        public ?string $agenceName = null,
    ) {
        Carbon::setLocale('fr');
        $this->dateFrom = Carbon::now()->setISODate($year, $week, 1); // lundi
        $this->dateTo   = Carbon::now()->setISODate($year, $week, 6); // samedi
    }

    public function envelope(): Envelope
    {
        $from = Carbon::now()->setISODate($this->year, $this->week, 1)->locale('fr')->isoFormat('D MMMM');
        $to   = Carbon::now()->setISODate($this->year, $this->week, 6)->locale('fr')->isoFormat('D MMMM YYYY');

        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                $this->senderName ?? config('mail.from.name'),
            ),
            subject: 'Planning ' . ($this->agenceName ?? 'Crocheux') . ' — du ' . $from . ' au ' . $to,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.planning',
        );
    }

    public function attachments(): array
    {
        $agence = $this->agenceName
            ? preg_replace('/[^A-Za-z0-9]+/', '_', $this->agenceName) . '_'
            : '';
        $label = sprintf('S%02d-%d', $this->week, $this->year);

        return [
            Attachment::fromPath($this->pdfPath)
                ->as('planning_' . $agence . $label . '.pdf')
                ->withMime('application/pdf'),
            Attachment::fromPath($this->excelPath)
                ->as('planning_' . $agence . $label . '.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}

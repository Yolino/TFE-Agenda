<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\JustificatifAbsence as JustificatifAbsenceModel;
use App\Models\Planning;
use App\Services\FileCompressionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class JustificatifAbsence extends Component
{
    use WithFileUploads;

    public $start_date;
    public $end_date;
    public $certificat_medical;

    protected function rules()
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'certificat_medical' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) === 'pdf' && $value->getSize() > 2 * 1024 * 1024) {
                        $fail('Le certificat PDF ne doit pas dépasser 2 Mo. Compressez-le ou convertissez-le en image.');
                    }
                },
            ],
        ];
    }

    protected function messages()
    {
        return [
            'start_date.required' => 'La date de début est requise.',
            'start_date.date' => 'La date de début n\'est pas valide.',
            'end_date.required' => 'La date de fin est requise.',
            'end_date.date' => 'La date de fin n\'est pas valide.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'certificat_medical.required' => 'Le certificat médical est requis.',
            'certificat_medical.file' => 'Le certificat médical doit être un fichier.',
            'certificat_medical.mimes' => 'Le certificat médical doit être une image (JPG, PNG) ou un PDF.',
            'certificat_medical.max' => 'Le certificat médical ne doit pas dépasser 5 Mo.',
        ];
    }

    public function submit(FileCompressionService $compressor)
    {
        $this->validate();

        $path = $compressor->storeAndCompress($this->certificat_medical, 'certificats', 'public');

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $nbJours = $startDate->diffInDays($endDate) + 1;

        JustificatifAbsenceModel::create([
            'user_id' => auth()->id(),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'nb_jours' => $nbJours,
            'certificat_medical' => $path,
        ]);

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            Planning::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'status_id' => Planning::STATUS_MAP['maladie'],
                    'start_time_morning' => null,
                    'end_time_morning' => null,
                    'start_time_afternoon' => null,
                    'end_time_afternoon' => null,
                ]
            );
        }

        session()->flash('success', 'Votre justificatif d\'absence a bien été enregistré et votre planning a été mis à jour.');

        $this->reset(['start_date', 'end_date', 'certificat_medical']);

        return redirect()->route('justificatif-absence.index');
    }

    public function render()
    {
        return view('livewire.justificatif-absence');
    }
}

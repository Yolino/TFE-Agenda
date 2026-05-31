<?php

namespace Tests\Feature;

use App\Livewire\JustificatifAbsence;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Le dépôt d'un justificatif d'absence est soumis à des règles strictes
 * (dates cohérentes, certificat obligatoire, formats et tailles maîtrisés).
 * Ces règles protègent à la fois les données et le stockage : on les teste au
 * niveau du composant Livewire, sans base de données (la validation échoue
 * avant toute écriture).
 */
class JustificatifAbsenceValidationTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: string, 3: string}>
     */
    public static function reglesDeDatesProvider(): array
    {
        return [
            'date de début manquante' => [null, '2024-01-10', 'start_date', 'required'],
            'date de fin manquante'   => ['2024-01-10', null, 'end_date', 'required'],
            'fin antérieure au début' => ['2024-01-10', '2024-01-05', 'end_date', 'after_or_equal'],
            'date de début invalide'  => ['pas-une-date', '2024-01-10', 'start_date', 'date'],
        ];
    }

    #[DataProvider('reglesDeDatesProvider')]
    public function test_les_regles_de_dates_sont_appliquees(
        ?string $debut,
        ?string $fin,
        string $champ,
        string $regle
    ): void {
        Livewire::test(JustificatifAbsence::class)
            ->set('start_date', $debut)
            ->set('end_date', $fin)
            ->call('submit')
            ->assertHasErrors([$champ => $regle]);
    }

    public function test_le_certificat_medical_est_obligatoire(): void
    {
        Livewire::test(JustificatifAbsence::class)
            ->set('start_date', '2024-01-10')
            ->set('end_date', '2024-01-12')
            ->call('submit')
            ->assertHasErrors(['certificat_medical' => 'required']);
    }

    public function test_un_format_de_fichier_non_supporte_est_refuse(): void
    {
        Livewire::test(JustificatifAbsence::class)
            ->set('start_date', '2024-01-10')
            ->set('end_date', '2024-01-12')
            ->set('certificat_medical', UploadedFile::fake()->create('document.txt', 100, 'text/plain'))
            ->call('submit')
            ->assertHasErrors(['certificat_medical' => 'mimes']);
    }

    public function test_une_image_de_plus_de_5_mo_est_refusee(): void
    {
        Livewire::test(JustificatifAbsence::class)
            ->set('start_date', '2024-01-10')
            ->set('end_date', '2024-01-12')
            ->set('certificat_medical', UploadedFile::fake()->create('scan.jpg', 6000, 'image/jpeg'))
            ->call('submit')
            ->assertHasErrors(['certificat_medical' => 'max']);
    }

    public function test_un_certificat_pdf_de_plus_de_2_mo_est_refuse(): void
    {
        // La règle métier dédiée plafonne spécifiquement les PDF à 2 Mo.
        Livewire::test(JustificatifAbsence::class)
            ->set('start_date', '2024-01-10')
            ->set('end_date', '2024-01-12')
            ->set('certificat_medical', UploadedFile::fake()->create('certificat.pdf', 3000, 'application/pdf'))
            ->call('submit')
            ->assertHasErrors(['certificat_medical']);
    }
}

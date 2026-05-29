<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Visualiseur des logs système.
 *
 * Onglet "Métier"    : logs sensibles stockés en base (filtrables, paginés).
 * Onglet "Technique" : dernières lignes du fichier de log technique du jour.
 *
 * Accès strictement réservé aux rôles Admin / Directeur (double barrière :
 * middleware sur la route + abort dans mount()).
 */
class SystemLogsViewer extends Component
{
    use WithPagination;

    /** Onglet courant : "metier" | "technique". */
    #[Url]
    public string $tab = 'metier';

    /* --- Filtres logs métiers (synchronisés à l'URL pour le partage/refresh) --- */
    #[Url]
    public string $filterUser = '';

    #[Url]
    public string $filterAction = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $search = '';

    /** Filtre de niveau pour l'onglet technique : ''|ERROR|WARNING|... */
    public string $filterLevel = '';

    /** Nombre de lignes lues en fin de fichier technique. */
    public int $techLines = 300;

    public function mount(): void
    {
        // Défense en profondeur : même si la route est mal configurée, on bloque ici.
        abort_unless(Auth::user()?->canAccessLogs(), 403);
    }

    /**
     * Tout changement de filtre/onglet ramène à la première page.
     * Hook générique => pas besoin de dupliquer resetPage() par propriété (DRY).
     */
    public function updated(string $name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filterUser', 'filterAction', 'dateFrom', 'dateTo', 'search', 'filterLevel']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.system-logs-viewer', [
            'logs'        => $this->tab === 'metier' ? $this->businessLogs() : null,
            'techLogs'    => $this->tab === 'technique' ? $this->technicalLogs() : [],
            'actions'     => $this->availableActions(),
            'users'       => $this->availableUsers(),
            'techFile'    => $this->currentTechnicalFile(),
        ]);
    }

    /* ====================================================================== */
    /*  LOGS MÉTIERS (base de données)                                        */
    /* ====================================================================== */

    /**
     * Construit la requête filtrée en chaînant les scopes du modèle.
     * La logique de filtrage vit dans ActivityLog (réutilisable ailleurs).
     */
    private function businessLogs()
    {
        return ActivityLog::query()
            ->forUser($this->filterUser)
            ->forAction($this->filterAction ?: null)
            ->betweenDates($this->dateFrom ?: null, $this->dateTo ?: null)
            ->search($this->search ?: null)
            ->latest()
            ->paginate(20);
    }

    /** Liste des types d'actions présents (alimente le <select> du filtre). */
    private function availableActions()
    {
        return ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    /** Liste des auteurs présents (id + nom instantané) pour le filtre utilisateur. */
    private function availableUsers()
    {
        return ActivityLog::query()
            ->select('user_id', 'user_name')
            ->whereNotNull('user_id')
            ->distinct()
            ->orderBy('user_name')
            ->get();
    }

    /* ====================================================================== */
    /*  LOGS TECHNIQUES (fichier du canal "technique")                        */
    /* ====================================================================== */

    /** Fichier technique le plus récent (technique-AAAA-MM-JJ.log). */
    private function currentTechnicalFile(): ?string
    {
        $dir = storage_path('logs/technique');

        if (! File::isDirectory($dir)) {
            return null;
        }

        $files = collect(File::glob($dir . '/technique-*.log'))
            ->sortDesc()
            ->values();

        return $files->first();
    }

    /**
     * Lit et parse les dernières lignes du fichier technique.
     * Format Laravel : [AAAA-MM-JJ HH:MM:SS] env.NIVEAU: message ...
     * Les lignes de stack-trace (sans en-tête) sont rattachées à l'entrée précédente.
     *
     * @return array<int, array{date:string, level:string, message:string}>
     */
    private function technicalLogs(): array
    {
        $file = $this->currentTechnicalFile();

        if (! $file || ! File::exists($file)) {
            return [];
        }

        // On ne charge que la fin du fichier pour rester performant.
        $lines = collect(preg_split('/\r\n|\r|\n/', File::get($file)))
            ->filter(fn ($l) => $l !== '')
            ->take(-$this->techLines);

        $entries = [];
        $pattern = '/^\[(?<date>[^\]]+)\]\s+\w+\.(?<level>[A-Z]+):\s*(?<message>.*)$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $m)) {
                $entries[] = [
                    'date'    => $m['date'],
                    'level'   => $m['level'],
                    'message' => trim($m['message']),
                ];
            } elseif (! empty($entries)) {
                // Continuation (trace) : on l'agrège au message courant (tronqué).
                $last = array_key_last($entries);
                if (mb_strlen($entries[$last]['message']) < 1000) {
                    $entries[$last]['message'] .= "\n" . $line;
                }
            }
        }

        // Filtre de niveau (réutilise une seule condition).
        if ($this->filterLevel !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => $e['level'] === $this->filterLevel
            ));
        }

        // Plus récent en premier.
        return array_reverse($entries);
    }

    /** Classe de badge DaisyUI selon le niveau technique. */
    public function levelBadge(string $level): string
    {
        return match ($level) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'badge-error',
            'WARNING'                                  => 'badge-warning',
            'NOTICE', 'INFO'                           => 'badge-info',
            default                                     => 'badge-ghost',
        };
    }
}

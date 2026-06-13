<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SystemLogsViewer extends Component
{
    use WithPagination;

    /**
     * Clés des propriétés de log qui contiennent un identifiant utilisateur.
     */
    public const USER_ID_KEYS = [
        'target_user_id',
        'by_user_id',
        'owner_user_id',
        'cancelled_by',
        'decided_by',
    ];

    #[Url]
    public string $tab = 'metier';

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

    public string $filterLevel = '';

    public int $techLines = 300;

    public function mount(): void
    {
        abort_unless(Auth::user()?->canAccessLogs(), 403);
    }

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
        $logs = $this->tab === 'metier' ? $this->businessLogs() : null;

        return view('livewire.system-logs-viewer', [
            'logs'        => $logs,
            'techLogs'    => $this->tab === 'technique' ? $this->technicalLogs() : [],
            'actions'     => $this->availableActions(),
            'users'       => $this->availableUsers(),
            'techFile'    => $this->currentTechnicalFile(),
            'userNames'   => $this->resolveUserNames($logs),
            'userIdKeys'  => self::USER_ID_KEYS,
        ]);
    }

    /**
     * Construit une table [id => "Prénom Nom"] pour tous les identifiants
     * utilisateur référencés dans les propriétés des logs affichés.
     *
     * @return array<int, string>
     */
    private function resolveUserNames($logs): array
    {
        if (! $logs) {
            return [];
        }

        $ids = collect($logs->items())
            ->flatMap(fn ($log) => $this->extractUserIds($log->properties ?? []))
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return User::whereIn('id', $ids)
            ->get(['id', 'firstname', 'name'])
            ->mapWithKeys(fn ($u) => [$u->id => trim(($u->firstname ?? '') . ' ' . ($u->name ?? ''))])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<int, int>
     */
    private function extractUserIds(array $properties): array
    {
        $ids = [];

        foreach (self::USER_ID_KEYS as $key) {
            if (isset($properties[$key]) && is_numeric($properties[$key])) {
                $ids[] = (int) $properties[$key];
            }
        }

        return $ids;
    }

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

    private function availableActions()
    {
        return ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    private function availableUsers()
    {
        return ActivityLog::query()
            ->select('user_id', 'user_name')
            ->whereNotNull('user_id')
            ->distinct()
            ->orderBy('user_name')
            ->get();
    }

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
     * @return array<int, array{date:string, level:string, message:string}>
     */
    private function technicalLogs(): array
    {
        $file = $this->currentTechnicalFile();

        if (! $file || ! File::exists($file)) {
            return [];
        }

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
                $last = array_key_last($entries);
                if (mb_strlen($entries[$last]['message']) < 1000) {
                    $entries[$last]['message'] .= "\n" . $line;
                }
            }
        }

        if ($this->filterLevel !== '') {
            $entries = array_values(array_filter(
                $entries,
                fn ($e) => $e['level'] === $this->filterLevel
            ));
        }

        return array_reverse($entries);
    }

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

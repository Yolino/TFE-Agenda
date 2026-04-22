@props([
    'balance',
    'compact' => false,
])

@php
    $percent = $balance['base'] > 0
        ? max(0, min(100, ($balance['remaining'] / $balance['base']) * 100))
        : 0;

    $color = $percent > 50 ? 'text-success' : ($percent > 25 ? 'text-warning' : 'text-error');
    $progressColor = $percent > 50 ? 'progress-success' : ($percent > 25 ? 'progress-warning' : 'progress-error');

    $format = fn ($n) => rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');
@endphp

<div {{ $attributes->merge(['class' => 'card bg-base-100 border border-base-300 shadow-sm']) }}>
    <div class="card-body p-4 {{ $compact ? 'gap-2' : 'gap-3' }}">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wide flex items-center gap-2">
                <i class="fa-duotone fa-umbrella-beach text-primary"></i>
                Solde VA {{ $balance['year'] }}
            </h3>
            <span class="badge badge-ghost badge-sm">base {{ $balance['base'] }} j</span>
        </div>

        <div class="text-center">
            <div class="text-3xl font-bold {{ $color }}">
                {{ $format($balance['remaining']) }}<span class="text-base font-normal text-base-content/60">&nbsp;/ {{ $balance['base'] }} j</span>
            </div>
        </div>

        <progress class="progress {{ $progressColor }} w-full" value="{{ $percent }}" max="100"></progress>

        @if(!$compact)
        <div class="flex justify-between text-xs text-base-content/70">
            <span><i class="fa-duotone fa-check-circle text-success mr-1"></i>{{ $format($balance['used']) }} j utilisé{{ $balance['used'] > 1 ? 's' : '' }}</span>
            @if(($balance['pending'] ?? 0) > 0)
                <span><i class="fa-duotone fa-hourglass-half text-info mr-1"></i>{{ $format($balance['pending']) }} j en attente</span>
            @endif
        </div>
        @endif
    </div>
</div>

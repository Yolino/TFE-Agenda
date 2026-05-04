@extends('app')
@section('title', 'Erreur 404')

@section('content')
@php
    $themes = ['futurama', 'spongebob', 'batman'];
    $theme = $themes[array_rand($themes)];

    $variants = [
        'futurama' => [
            'gif'       => 'images/fry-squint.gif',
            'alt'       => 'Fry suspicious',
            'gradient'  => 'from-indigo-50 via-purple-50 to-orange-50',
            'badge'     => 'badge-warning',
            'accent'    => 'border-l-4 border-secondary',
            'titleClass'=> 'text-secondary',
            'codeClass' => 'text-warning',
            'quote'     => '« Attends... cette page existait avant, ou je l\'imagine juste ? »',
            'btnClass'  => 'btn-secondary',
            'btnIcon'   => 'fa-rocket',
        ],
        'spongebob' => [
            'gif'       => 'images/suspicious03.gif',
            'alt'       => 'Bob suspicious',
            'gradient'  => 'from-cyan-50 via-sky-100 to-teal-100',
            'badge'     => 'badge-info',
            'accent'    => 'border-t-4 border-warning',
            'titleClass'=> 'text-warning',
            'codeClass' => 'text-info',
            'quote'     => '« Cette page... je l\'avais mise quelque part sous ma roche. »',
            'btnClass'  => 'btn-warning',
            'btnIcon'   => 'fa-water',
        ],
        'batman' => [
            'gif'       => 'images/batman-suspicious.gif',
            'alt'       => 'Batman suspicious',
            'gradient'  => 'from-base-300 via-base-200 to-base-100',
            'badge'     => 'badge-neutral',
            'accent'    => 'border-l-4 border-warning',
            'titleClass'=> 'text-base-content',
            'codeClass' => 'text-warning',
            'quote'     => '« Quelqu\'un a volé cette page. »',
            'btnClass'  => 'btn-warning',
            'btnIcon'   => 'fa-mask',
        ],
    ];

    $v = $variants[$theme];
@endphp

<section class="min-h-screen bg-gradient-to-br {{ $v['gradient'] }} flex flex-col items-center justify-center p-6 gap-6">
    <div class="flex items-center gap-3">
        <div class="badge {{ $v['badge'] }} badge-lg font-bold tracking-widest">404</div>
        <h1 class="text-3xl sm:text-4xl font-bold {{ $v['titleClass'] }}">Page non trouvée</h1>
    </div>

    <div class="card bg-base-100 shadow-xl overflow-hidden max-w-sm w-[90vw]">
        <figure>
            <img src="{{ asset($v['gif']) }}" alt="{{ $v['alt'] }}" class="w-full">
        </figure>
    </div>

    <div class="card bg-base-100 shadow-md w-full max-w-xl {{ $v['accent'] }}">
        <div class="card-body py-6 items-center text-center">
            <p class="text-base sm:text-lg font-semibold text-base-content/80 italic">
                {{ $v['quote'] }}
            </p>
            <p class="text-xs text-base-content/50 mt-2 uppercase tracking-widest">
                Erreur <span class="{{ $v['codeClass'] }}">404</span> — Ressource introuvable
            </p>
        </div>
    </div>

    <div class="flex gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour
        </a>
        <a href="{{ route('dashboard') }}" class="btn {{ $v['btnClass'] }} text-white">
            <i class="fa-solid {{ $v['btnIcon'] }} mr-1"></i> Retour à l'accueil
        </a>
    </div>
</section>
@endsection

@extends("app")
@section("title", "Logs système - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-2xl md:text-4xl font-medium">Logs système</h1>
        <p class="text-sm text-base-content/60 mt-1">
            Journalisation des actions sensibles et des anomalies techniques.
        </p>
    </div>
    <div class="card-eg">
        <livewire:system-logs-viewer />
    </div>
</div>
@endsection

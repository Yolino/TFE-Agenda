@extends("app")
@section("title", "Gestion des absences - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-2xl md:text-4xl font-medium">Gestion des absences</h1>
        <p class="text-sm text-base-content/60 mt-1">Suivi des collaborateurs absents et de leurs certificats médicaux.</p>
    </div>
    <div class="card-eg">
        <livewire:admin-absences />
    </div>
</div>
@endsection

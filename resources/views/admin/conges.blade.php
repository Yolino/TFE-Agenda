@extends("app")
@section("title", "Gestion des congés - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium">Gestion des congés</h1>
    </div>
    <div class="card-eg">
        <livewire:admin-conges />
    </div>
</div>
@endsection

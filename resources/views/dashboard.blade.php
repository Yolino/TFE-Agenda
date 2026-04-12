@extends("app")
@section("title", "Dashboard - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium">Tableau de bord</h1>
    </div>
</div>
@endsection
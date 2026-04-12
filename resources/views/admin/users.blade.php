@extends("app")
@section("title", "Gestion des utilisateurs - Admin")

@section("content")
@include("partials.nav")
<div class="p-4" x-data="{ openForm: false, user: {} }">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium mb-4">Gestion des utilisateurs</h1>

        @livewire('user-table')
    </div>
</div>
@endsection
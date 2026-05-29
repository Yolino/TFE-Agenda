@extends("app")
@section("title", "Connexion Teams - Agenda")

@section("content")
<section class="min-h-screen bg-gradient-to-br from-base-200 via-base-100 to-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card bg-base-100 shadow-2xl border border-base-300">
            <div class="card-body p-8 sm:p-10">
                <div class="flex flex-col items-center mb-6">
                    <img class="w-48 mb-4" src="{{ asset('images/logo_b.png') }}?v=20260422" alt="Agenda">
                    <h1 class="text-2xl font-bold text-base-content">Bienvenue</h1>
                    <p class="text-sm text-base-content/60 mt-1 text-center">
                        Connectez-vous à votre espace Agenda avec votre compte Microsoft.
                    </p>
                </div>

                <a href="{{ route('sso.login', 'microsoft') }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn-primary w-full text-base font-semibold">
                    <i class="fa-brands fa-microsoft"></i>
                    Connexion avec Microsoft
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-base-content/50 mt-6">
            &copy; {{ date('Y') }} Agenda — Tous droits réservés
        </p>
    </div>
</section>
@endsection

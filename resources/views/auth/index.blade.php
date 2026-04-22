@extends("app")
@section("title", "Connexion - Agenda")

@section("content")
<section class="min-h-screen bg-gradient-to-br from-base-200 via-base-100 to-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card bg-base-100 shadow-2xl border border-base-300">
            <div class="card-body p-8 sm:p-10">
                <div class="flex flex-col items-center mb-6">
                    <img class="w-48 mb-4" src="{{ asset('images/logo_b.png') }}?v=20260422" alt="Agenda">
                    <h1 class="text-2xl font-bold text-base-content">Bienvenue</h1>
                    <p class="text-sm text-base-content/60 mt-1">Connectez-vous à votre espace Agenda</p>
                </div>

                @error('errorsCredentials')
                <div role="alert" class="alert alert-error mb-4 text-sm">
                    <i class="fa-duotone fa-circle-exclamation"></i>
                    <span>{{ $message }}</span>
                </div>
                @enderror

                <form action="{{ route('auth.index') }}" method="post" class="space-y-4">
                    @csrf

                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text font-semibold uppercase text-xs tracking-wide">Email</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 focus-within:input-primary">
                            <i class="fa-duotone fa-envelope text-base-content/50"></i>
                            <input type="email" name="email" placeholder="nom@exemple.com"
                                   class="grow" required autofocus value="{{ old('email') }}">
                        </label>
                    </label>

                    <label class="form-control w-full" x-data="{ show: false }">
                        <div class="label">
                            <span class="label-text font-semibold uppercase text-xs tracking-wide">Mot de passe</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 focus-within:input-primary">
                            <i class="fa-duotone fa-lock text-base-content/50"></i>
                            <input :type="show ? 'text' : 'password'" name="password" placeholder="••••••••"
                                   class="grow" required>
                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="text-base-content/50 hover:text-base-content transition">
                                <i class="fa-duotone" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </label>
                    </label>

                    <div class="flex justify-end">
                        <a href="{{ route('password.request') }}" class="link link-hover text-xs text-base-content/70">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full text-base font-semibold">
                        <i class="fa-duotone fa-arrow-right-to-bracket"></i>
                        Se connecter
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-xs text-base-content/50 mt-6">
            &copy; {{ date('Y') }} Agenda — Tous droits réservés
        </p>
    </div>
</section>
@endsection

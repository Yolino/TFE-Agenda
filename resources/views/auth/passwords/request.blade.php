@extends('app')
@section('title', 'Mot de passe oublié')

@section('content')
<section class="min-h-screen bg-gradient-to-br from-base-200 via-base-100 to-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card bg-base-100 shadow-2xl border border-base-300">
            <div class="card-body p-8 sm:p-10">
                <div class="flex flex-col items-center mb-6">
                    <i class="fa-duotone fa-key text-primary text-4xl mb-3"></i>
                    <h1 class="text-2xl font-bold">Mot de passe oublié</h1>
                    <p class="text-sm text-base-content/60 mt-1 text-center">
                        Entrez votre email — nous vous enverrons un lien pour réinitialiser votre mot de passe.
                    </p>
                </div>

                @if(session('success'))
                    <div role="alert" class="alert alert-success mb-4 text-sm">
                        <i class="fa-duotone fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @error('email')
                    <div role="alert" class="alert alert-error mb-4 text-sm">
                        <i class="fa-duotone fa-circle-exclamation"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <form action="{{ route('password.email') }}" method="post" class="space-y-4">
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

                    <button type="submit" class="btn btn-primary w-full text-base font-semibold">
                        <i class="fa-duotone fa-paper-plane"></i> Envoyer le lien
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="{{ route('auth.index') }}" class="link link-hover text-xs text-base-content/70">
                        <i class="fa-duotone fa-arrow-left"></i> Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@extends('app')
@section('title', 'Définir mon mot de passe')

@section('content')
<section class="min-h-screen bg-gradient-to-br from-base-200 via-base-100 to-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card bg-base-100 shadow-2xl border border-base-300">
            <div class="card-body p-8 sm:p-10">
                <div class="flex flex-col items-center mb-6">
                    <i class="fa-duotone fa-shield-check text-primary text-4xl mb-3"></i>
                    <h1 class="text-2xl font-bold">Choisissez votre mot de passe</h1>
                    <p class="text-sm text-base-content/60 mt-1">Au moins 8 caractères.</p>
                </div>

                @error('email')
                    <div role="alert" class="alert alert-error mb-4 text-sm">
                        <i class="fa-duotone fa-circle-exclamation"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <form action="{{ route('password.update') }}" method="post" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text font-semibold uppercase text-xs tracking-wide">Email</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 focus-within:input-primary">
                            <i class="fa-duotone fa-envelope text-base-content/50"></i>
                            <input type="email" name="email" class="grow" required
                                   value="{{ old('email', $email) }}" readonly>
                        </label>
                    </label>

                    <label class="form-control w-full" x-data="{ show: false }">
                        <div class="label">
                            <span class="label-text font-semibold uppercase text-xs tracking-wide">Nouveau mot de passe</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 focus-within:input-primary">
                            <i class="fa-duotone fa-lock text-base-content/50"></i>
                            <input :type="show ? 'text' : 'password'" name="password" placeholder="••••••••"
                                   class="grow" required minlength="8">
                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="text-base-content/50 hover:text-base-content transition">
                                <i class="fa-duotone" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </label>
                        @error('password')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                    </label>

                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text font-semibold uppercase text-xs tracking-wide">Confirmation</span>
                        </div>
                        <label class="input input-bordered flex items-center gap-3 focus-within:input-primary">
                            <i class="fa-duotone fa-lock-keyhole text-base-content/50"></i>
                            <input type="password" name="password_confirmation" placeholder="••••••••"
                                   class="grow" required minlength="8">
                        </label>
                    </label>

                    <button type="submit" class="btn btn-primary w-full text-base font-semibold">
                        <i class="fa-duotone fa-circle-check"></i> Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

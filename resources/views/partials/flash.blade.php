@if(session('success'))
<div x-data="{ openSuccessAlert: true }" class="pb-4" x-show="openSuccessAlert" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ session('success') }}</span>
        <button class="btn btn-sm" @click="openSuccessAlert = false">Fermer</button>
    </div>
</div>
@endif
@if(session('error'))
<div x-data="{ openErrorAlert: true }" class="pb-4" x-show="openSuccessAlert" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ session('error') }}</span>
        <button class="btn btn-sm" @click="openErrorAlert = false">Fermer</button>
    </div>
</div>
@endif
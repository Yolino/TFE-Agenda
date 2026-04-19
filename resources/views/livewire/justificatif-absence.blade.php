<div>
    <form wire:submit.prevent="submit" enctype="multipart/form-data">
        <div class="flex flex-col gap-5">
            <!-- Date de début -->
            <div class="flex flex-col">
                <label for="start_date" class="uppercase text-sm font-bold mb-2">Date de début</label>
                <input type="date" id="start_date" class="input w-full" wire:model="start_date" required />
                @error('start_date') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
            </div>
            <!-- Date de fin -->
            <div class="flex flex-col">
                <label for="end_date" class="uppercase text-sm font-bold mb-2">Date de fin</label>
                <input type="date" id="end_date" class="input w-full" wire:model="end_date" required />
                @error('end_date') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
            </div>
            <!-- Certificat médical -->
            <div class="flex flex-col">
                <label for="certificat_medical" class="uppercase text-sm font-bold mb-2">Certificat médical</label>
                <input type="file" id="certificat_medical" class="file-input file-input-bordered w-full" wire:model="certificat_medical" accept=".jpg,.jpeg,.png,.pdf" required />
                <span class="text-xs text-gray-500 mt-1">Formats acceptés : JPG, PNG, PDF (max 5 Mo)</span>
                @error('certificat_medical') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
            </div>
            <!-- Indicateur de chargement -->
            <div wire:loading wire:target="certificat_medical" class="text-sm text-gray-500">
                <i class="fa-duotone fa-spinner-third fa-spin mr-1"></i> Chargement du fichier...
            </div>
            <!-- Bouton de soumission -->
            <div class="flex flex-col">
                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                    Soumettre le justificatif
                </button>
            </div>
        </div>
    </form>
</div>

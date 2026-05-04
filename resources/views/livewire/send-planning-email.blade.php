<div x-data="{ open: false }" @planning-emailed.window="open = false; Swal.fire({icon:'success', title:'Email envoyé !', timer:2000, showConfirmButton:false})">
    <button type="button" @click="open = true" class="btn btn-success text-white">
        <i class="fa-solid fa-paper-plane mr-1"></i> Envoyer par email
    </button>

    <dialog class="modal" :class="{ 'modal-open': open }">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Envoyer le planning crocheux</h3>

            <form wire:submit.prevent="send" class="space-y-4">
                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-semibold uppercase text-xs tracking-wide">Destinataire</span>
                    </div>
                    <input type="email" wire:model="recipient"
                           class="input input-bordered w-full focus:input-primary"
                           placeholder="nom@exemple.com" required>
                    @error('recipient')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                </label>

                <p class="text-xs text-base-content/60">
                    Pièces jointes : export PDF + export Excel de la semaine {{ $week }} - {{ $year }}.
                </p>

                <div class="modal-action">
                    <button type="button" class="btn" @click="open = false">Annuler</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="fa-solid fa-paper-plane mr-1"></i> Envoyer</span>
                        <span wire:loading><span class="loading loading-spinner loading-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop bg-black/40" @click="open = false"></div>
    </dialog>
</div>

@props([
    'agences' => collect(),
    'selected' => null,
    'placeholder' => 'Sélectionner une agence...',
    'nullable' => false,
    'nullLabel' => 'Toutes les agences',
    'inputClass' => 'input input-bordered w-full',
    'dropdownClass' => 'w-full',
])

@php
    $items = collect($agences)->map(fn ($a) => [
        'id'       => $a->id,
        'label'    => $a->display_name,
        'localite' => $a->localite ?? null,
    ])->values()->all();

    $selectedLabel = '';
    if ($selected !== null && $selected !== '') {
        $a = collect($agences)->firstWhere('id', (int) $selected);
        if ($a) {
            $selectedLabel = $a->display_name;
        }
    } elseif ($nullable) {
        $selectedLabel = $nullLabel;
    }
@endphp

<div class="relative" x-data="{
    items: @js($items),
    open: false,
    query: @js($selectedLabel),
    committedLabel: @js($selectedLabel),
    nullable: @js($nullable),
    nullLabel: @js($nullLabel),
    highlight: -1,
    get filtered() {
        if (!this.query || this.query === this.committedLabel) return this.items;
        const q = this.query.toLowerCase().trim();
        return this.items.filter(i =>
            i.label.toLowerCase().includes(q) ||
            (i.localite || '').toLowerCase().includes(q)
        );
    },
    select(id, label) {
        this.query = label;
        this.committedLabel = label;
        this.open = false;
        this.highlight = -1;
        this.$dispatch('selected', { value: id, label: label });
    },
    onFocus() {
        this.open = true;
        if (this.query === this.committedLabel) this.query = '';
    },
    onBlur() {
        setTimeout(() => {
            this.open = false;
            this.query = this.committedLabel;
        }, 150);
    },
    moveHighlight(d) {
        if (!this.open) this.open = true;
        const total = this.filtered.length;
        if (total === 0 && !this.nullable) return;
        const min = this.nullable ? -1 : 0;
        const max = total - 1;
        let h = this.highlight + d;
        if (h < min) h = max;
        if (h > max) h = min;
        this.highlight = h;
    },
    enterSelect() {
        if (this.highlight === -1 && this.nullable) {
            this.select(null, this.nullLabel);
            return;
        }
        const idx = this.highlight >= 0 ? this.highlight : 0;
        const item = this.filtered[idx];
        if (item) this.select(item.id, item.label);
    },
}">
    <input type="text" x-model="query"
           @focus="onFocus()"
           @blur="onBlur()"
           @keydown.escape.prevent="open = false; query = committedLabel"
           @keydown.down.prevent="moveHighlight(1)"
           @keydown.up.prevent="moveHighlight(-1)"
           @keydown.enter.prevent="enterSelect()"
           autocomplete="off"
           placeholder="{{ $placeholder }}"
           {{ $attributes->class($inputClass) }} />

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute left-0 mt-1 z-30 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-72 overflow-y-auto {{ $dropdownClass }}">
        @if($nullable)
            <button type="button"
                    @mousedown.prevent="select(null, nullLabel)"
                    @mouseenter="highlight = -1"
                    class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm italic"
                    :class="highlight === -1 ? 'bg-base-200' : ''"
                    x-text="nullLabel"></button>
        @endif
        <template x-if="filtered.length === 0">
            <p class="px-4 py-2 text-xs text-base-content/50 italic">Aucune agence trouvée</p>
        </template>
        <template x-for="(item, idx) in filtered" :key="item.id">
            <button type="button"
                    @mousedown.prevent="select(item.id, item.label)"
                    @mouseenter="highlight = idx"
                    class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm"
                    :class="highlight === idx ? 'bg-base-200' : ''">
                <span class="font-medium" x-text="item.label"></span>
                <template x-if="item.localite">
                    <span class="text-xs text-base-content/50 block" x-text="item.localite"></span>
                </template>
            </button>
        </template>
    </div>
</div>

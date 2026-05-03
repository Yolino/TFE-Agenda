@props(['conge'])

@if(in_array($conge->status, ['acceptee', 'refusee']) && $conge->decidedBy)
    @php
        $verb = $conge->status === 'acceptee' ? 'Validé' : 'Refusé';
        $cls = $conge->status === 'acceptee' ? 'text-success' : 'text-error';
    @endphp
    <span class="text-xs italic {{ $cls }}">
        <i class="fa-duotone fa-user-check mr-1"></i>
        {{ $verb }} par {{ $conge->decidedBy->firstname }} {{ $conge->decidedBy->name }}
        @if($conge->decided_at)
            le {{ \Carbon\Carbon::parse($conge->decided_at)->translatedFormat('d M Y à H:i') }}
        @endif
    </span>
@elseif($conge->status === 'annulee' && $conge->cancelled_at)
    @php
        $cancelledBy = $conge->cancelledBy ?? null;
    @endphp
    <span class="text-xs italic text-warning">
        <i class="fa-duotone fa-ban mr-1"></i>
        Annulée
        @if($cancelledBy)
            par {{ $cancelledBy->firstname }} {{ $cancelledBy->name }}
        @endif
        le {{ \Carbon\Carbon::parse($conge->cancelled_at)->translatedFormat('d M Y à H:i') }}
    </span>
@endif

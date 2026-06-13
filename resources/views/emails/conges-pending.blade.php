<x-emails.layout heading="Congés à traiter">

    <x-slot:subheader>
        <p style="margin:0; font-size:15px; font-weight:bold; color:#065f46;">
            {{ $count }} demande{{ $count > 1 ? 's' : '' }} de congé en attente de traitement
        </p>
    </x-slot:subheader>

    <p style="margin:0 0 16px;">Bonjour,</p>

    <p style="margin:0 0 24px; line-height:1.6;">
        @if($count > 1)
            <strong>{{ $count }}</strong> demandes de congé sont en attente de validation.
        @else
            Une demande de congé est en attente de validation.
        @endif
        Merci de les traiter dès que possible.
    </p>

    @foreach($demandesByAgence as $agence => $demandes)
        <p style="margin:0 0 8px; font-size:12px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; color:#059669;">
            {{ $agence }}
        </p>

        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:24px;">
            @foreach($demandes as $demande)
                <tr>
                    <td style="padding:10px 14px; background:#f0fdf4; border-left:4px solid #059669; border-radius:4px;">
                        <strong style="color:#111827;">{{ $demande->user->firstname }} {{ $demande->user->name }}</strong>
                        &nbsp;—&nbsp; {{ $demande->type_label }}
                        <br>
                        <span style="font-size:13px; color:#374151;">
                            Du {{ \Carbon\Carbon::parse($demande->start_date)->locale('fr')->isoFormat('D MMM YYYY') }}
                            au {{ \Carbon\Carbon::parse($demande->end_date)->locale('fr')->isoFormat('D MMM YYYY') }}
                            &nbsp;·&nbsp; {{ $demande->formatted_jours }}
                        </span>
                    </td>
                </tr>
                <tr><td style="height:8px;"></td></tr>
            @endforeach
        </table>
    @endforeach

    <table cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 24px;">
        <tr>
            <td style="background:#059669; border-radius:6px;">
                <a href="{{ route('admin.conges') }}"
                   style="display:inline-block; padding:12px 28px; color:#ffffff; font-weight:bold; font-size:14px; text-decoration:none;">
                    Traiter les demandes →
                </a>
            </td>
        </tr>
    </table>

</x-emails.layout>

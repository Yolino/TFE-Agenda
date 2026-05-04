<x-emails.layout heading="Planning Crocheux">

    {{-- Bandeau date --}}
    <x-slot:subheader>
        <p style="margin:0; font-size:15px; font-weight:bold; color:#065f46;">
            Semaine {{ $week }}
            &nbsp;·&nbsp;
            du {{ $dateFrom->locale('fr')->isoFormat('dddd D MMMM') }}
            au {{ $dateTo->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
        </p>
    </x-slot:subheader>

    {{-- Corps --}}
    <p style="margin:0 0 16px;">Bonjour,</p>

    <p style="margin:0 0 16px; line-height:1.6;">
        Veuillez trouver ci-joint le planning de la semaine&nbsp;<strong>{{ $week }}</strong>
        (du&nbsp;<strong>{{ $dateFrom->locale('fr')->isoFormat('D MMMM') }}</strong>
        au&nbsp;<strong>{{ $dateTo->locale('fr')->isoFormat('D MMMM YYYY') }}</strong>).
    </p>

    <p style="margin:0 0 20px; line-height:1.6;">Deux pièces jointes sont disponibles&nbsp;:</p>

    <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
            <td style="padding:8px 14px; background:#f0fdf4; border-left:4px solid #059669; border-radius:4px;">
                📄&nbsp; <strong>PDF</strong> — planning_S{{ sprintf('%02d', $week) }}-{{ $year }}.pdf
            </td>
        </tr>
        <tr><td style="height:8px;"></td></tr>
        <tr>
            <td style="padding:8px 14px; background:#f0fdf4; border-left:4px solid #059669; border-radius:4px;">
                📊&nbsp; <strong>Excel</strong> — planning_S{{ sprintf('%02d', $week) }}-{{ $year }}.xlsx
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; line-height:1.6;">Nous restons disponibles pour toute question.</p>
    <p style="margin:0 0 0; line-height:1.6;">Bien cordialement,</p>

    {{-- Signature --}}
    <x-slot:signature>
        <td style="padding-right:20px; vertical-align:top; width:52px;">
            <div style="width:48px; height:48px; border-radius:50%; background-color:#059669;
                        font-size:20px; font-weight:bold; color:#ffffff;
                        text-align:center; line-height:48px;">
                {{ strtoupper(substr($senderName ?? 'A', 0, 1)) }}
            </div>
        </td>
        <td style="vertical-align:top;">
            <p style="margin:0; font-weight:bold; font-size:14px; color:#111827;">{{ $senderName ?? config('app.name') }}</p>
            @if(!empty($senderTitle))
                <p style="margin:2px 0 0; font-size:12px; color:#6b7280;">{{ $senderTitle }}</p>
            @endif
            @if(!empty($senderPhone))
                <p style="margin:4px 0 0; font-size:12px; color:#6b7280;">📞 {{ $senderPhone }}</p>
            @endif
            <p style="margin:4px 0 0; font-size:12px; color:#059669;">{{ config('app.name') }}</p>
        </td>
    </x-slot:signature>

</x-emails.layout>

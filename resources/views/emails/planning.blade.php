<x-emails.layout heading="Planning Général">

    <x-slot:subheader>
        @if(!empty($agenceName))
            <p style="margin:0 0 4px; font-size:16px; font-weight:bold; color:#065f46;">{{ $agenceName }}</p>
        @endif
        <p style="margin:0; font-size:15px; font-weight:bold; color:#065f46;">
            Semaine {{ $week }}
            &nbsp;·&nbsp;
            du {{ $dateFrom->locale('fr')->isoFormat('dddd D MMMM') }}
            au {{ $dateTo->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
        </p>
    </x-slot:subheader>

    <p style="margin:0 0 16px;">Bonjour,</p>

    <p style="margin:0 0 16px; line-height:1.6;">
        Veuillez trouver ci-joint le planning de la semaine&nbsp;<strong>{{ $week }}</strong>
        (du&nbsp;<strong>{{ $dateFrom->locale('fr')->isoFormat('D MMMM') }}</strong>
        au&nbsp;<strong>{{ $dateTo->locale('fr')->isoFormat('D MMMM YYYY') }}</strong>).
    </p>

    <p style="margin:0 0 20px; line-height:1.6;">Deux pièces jointes sont disponibles&nbsp;:</p>

    @php
        $agencePrefix = !empty($agenceName) ? preg_replace('/[^A-Za-z0-9]+/', '_', $agenceName) . '_' : '';
        $label = $agencePrefix . 'S' . sprintf('%02d', $week) . '-' . $year;
    @endphp

    <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
        <tr>
            <td style="padding:8px 14px; background:#f0fdf4; border-left:4px solid #059669; border-radius:4px;">
                <strong>PDF</strong> — planning_{{ $label }}.pdf
            </td>
        </tr>
        <tr><td style="height:8px;"></td></tr>
        <tr>
            <td style="padding:8px 14px; background:#f0fdf4; border-left:4px solid #059669; border-radius:4px;">
                <strong>Excel</strong> — planning_{{ $label }}.xlsx
            </td>
        </tr>
    </table>

</x-emails.layout>

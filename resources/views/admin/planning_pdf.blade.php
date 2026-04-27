<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #111; }

        /* Marges gauche/droite/bas via wrapper */
        .wrapper { padding: 0 10mm 10mm 10mm; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: 1px solid #444;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }

        /* thead se répète sur chaque page — la ligne titre porte la marge haute */
        .thead-title {
            border: none;
            background: #ffffff;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            padding: 12mm 0 5px 0;
        }
        thead th {
            background-color: #fff2cc;
            font-weight: bold;
            font-size: 8.5px;
            height: 30px;
        }

        .col-name { width: 105px; text-align: left; }
        .col-soc  { width: 52px; }

        /* Ligne séparateur de département */
        .dept-row td {
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            padding: 2px 5px;
            height: 14px;
        }

        /* Couleurs département */
        .dept-B, .bg-B { background-color: #d9ead3; color: #274e13; }
        .dept-S, .bg-S { background-color: #fce5cd; color: #783f04; }
        .dept-C, .bg-C { background-color: #dae8fc; color: #1c4587; }
        .dept-I, .bg-I { background-color: #fff2cc; color: #7f6000; }

        /* Cellules jours */
        .holiday-header { background-color: #99f6e4; color: #134e4a; }
        .holiday-cell   { background-color: #99f6e4; color: #134e4a; font-weight: bold; }
        .absent-cell    { background-color: #d0cece; }

        /* Texte statut */
        .status-bureau   { font-weight: normal; }
        .status-domicile { color: #008000; font-weight: bold; }
        .status-absent   { color: #cc0000; font-weight: bold; }

        .times       { font-size: 7px; }
        .conge-label { font-size: 7px; font-style: italic; }
        .soc-text    { font-size: 6px; text-align: left; }
        .phone-text  { font-size: 7px; }
        .fixe-text   { font-size: 7px; color: #cc0000; font-weight: bold; }
        .empty-cell  { color: #bbb; }

        .user-row td { height: 38px; }
    </style>
</head>
<body>
<div class="wrapper">
    <table>
        <thead>
            <tr>
                <th class="thead-title" colspan="{{ 2 + $daysInWeek->count() }}">
                    Planning Crocheux &mdash; Semaine {{ $selectedWeek }} / {{ $selectedYear }}
                </th>
            </tr>
            <tr>
                <th class="col-name">Nom &ndash; Prénom</th>
                <th class="col-soc">Sociétés</th>
                @foreach($daysInWeek as $day)
                    @php $isHoliday = isset($holidays[$day->toDateString()]); @endphp
                    <th class="{{ $isHoliday ? 'holiday-header' : '' }}">
                        {{ mb_strtoupper($day->translatedFormat('l')) }}<br>
                        {{ $day->format('d/m/Y') }}
                        @if($isHoliday)
                            <br><span style="font-size:6.5px;font-style:italic;">{{ $holidays[$day->toDateString()] }}</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($activeUsers as $type => $usersInGroup)
                @php $label = $typeLabels[$type] ?? $type; @endphp

                <tr class="dept-row">
                    <td colspan="{{ 2 + $daysInWeek->count() }}" class="dept-{{ $type }}">{{ $label }}</td>
                </tr>

                @foreach($usersInGroup->sortBy('name') as $user)
                    @php $societes = array_filter(array_map('trim', explode(',', $user->remarque ?? ''))); @endphp
                    <tr class="user-row">

                        <td class="col-name bg-{{ $type }}" style="text-align:left;">
                            <span style="font-weight:bold;">{{ $user->name }} {{ $user->firstname }}</span>
                            @if($user->phone)
                                <br><span class="phone-text">{{ $user->phone }}</span>
                            @endif
                            @if($user->fixe)
                                <br><span class="fixe-text">{{ $user->fixe }}</span>
                            @endif
                        </td>

                        <td class="soc-text">
                            @foreach($societes as $s){{ $s }}<br>@endforeach
                        </td>

                        @foreach($daysInWeek as $day)
                            @php
                                $dateStr   = $day->toDateString();
                                $isHoliday = isset($holidays[$dateStr]);
                                $entry     = $planningEntries->where('user_id', $user->id)->where('date', $dateStr)->first();
                            @endphp

                            @if($isHoliday)
                                <td class="holiday-cell">
                                    JOUR FÉRIÉ<br>
                                    <span style="font-size:6.5px;font-style:italic;">{{ $holidays[$dateStr] }}</span>
                                </td>

                            @elseif($entry)
                                @php
                                    $status      = $entry->status;
                                    $isAbsent    = $status !== 'bureau';
                                    $statusLabel = $status === 'tele_travail' ? 'DOMICILE' : strtoupper($status);
                                    $textClass   = match($status) {
                                        'tele_travail' => 'status-domicile',
                                        'bureau'       => 'status-bureau',
                                        default        => 'status-absent',
                                    };
                                @endphp
                                <td class="{{ $isAbsent ? 'absent-cell' : '' }}">
                                    <span class="{{ $textClass }}">{{ $statusLabel }}</span>
                                    @if($entry->demandeConge && $entry->demandeConge->status === 'acceptee')
                                        <br><span class="conge-label">{{ $congeTypeLabels[$entry->demandeConge->type] ?? $entry->demandeConge->type }}</span>
                                    @endif
                                    @if($entry->start_time && $entry->end_time)
                                        <br><span class="times">{{ substr($entry->start_time,0,5) }} à {{ substr($entry->end_time,0,5) }}</span>
                                    @endif
                                    @if($entry->start_time_afternoon && $entry->end_time_afternoon)
                                        <br><span class="times">{{ substr($entry->start_time_afternoon,0,5) }} à {{ substr($entry->end_time_afternoon,0,5) }}</span>
                                    @endif
                                </td>

                            @else
                                <td class="empty-cell">—</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
</body>
</html>

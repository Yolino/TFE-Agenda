<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Congé</title>
    <script src="https://kit.fontawesome.com/8cfad572d3.js" crossorigin="anonymous"></script>
    <style type="text/css">
        html {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            z-index: 1;
            font-size: 20px;
            background-color: transparent;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
            width: 100%;
            height: 100%;
        }

        .identite {
            font-size: 2rem;
            margin-top: 14.5rem;
        }

        .date {
            margin-top: 3rem;
            font-size: 1.5rem;
            color: #348ca4;
            font-weight: bold;
        }

        .check {
            display: inline-block;
            margin-top: 2.3rem;
            margin-left: 5.6rem;
        }

        .nbJours {
            display: inline-block;
            margin-left: 2.4rem;
            color: #348ca4;
            font-weight: bold;
            width: 30px;
            text-align: center;
        }

        .wrapCheck {
            margin-top: 2.2rem;
            display: inline-block;
        }
    </style>
</head>

<body>
    <img class="background-image" src="images/conge.png" alt="Canevas">
    <div class="identite">
        <span style="margin-left: 15rem;">{{ auth()->user()->firstname }}</span>
        <span class="name">{{ auth()->user()->name }}</span>
    </div>
    <div class="date">
        <span style="margin-left: 9.3rem;">{{ $conge->dateDu['jour'] }}</span>
        <span style="margin-left: 4rem;">{{ $conge->dateDu['mois'] }}</span>
        <span style="margin-left: 3rem;">{{ $conge->dateDu['annee'] }}</span>

        <span style="margin-left: 7rem;">{{ $conge->dateAu['jour'] }}</span>
        <span style="margin-left: 4rem;">{{ $conge->dateAu['mois'] }}</span>
        <span style="margin-left: 3rem;">{{ $conge->dateAu['annee'] }}</span>
    </div>
    @php
    $types = ['recup', 'conge', 'css', 'visite', 'autre'];
    @endphp

    @foreach ($types as $type)
    <div class="wrapCheck" style="opacity: {{ $conge->type == $type ? 1 : 0 }}; {{ !$loop->first ? 'margin-left: 1.1rem;' : '' }}">
        <div class="check">
            <img class="" src="images/check_green.png" width="30px" alt="Check">
        </div>
        <br>
        <div class="nbJours">
            <span style="display:inline-block;margin-top: 2.9rem;">{{ $conge->nb_jours }}</span>
        </div>
    </div>
    @endforeach
</body>

</html>
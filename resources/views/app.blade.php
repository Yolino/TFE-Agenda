<!DOCTYPE html>
<html lang="fr" data-theme="emerald">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Agenda')">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v=20260422">

    <title>@yield("title")</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/8cfad572d3.js" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-agenda flex min-h-screen bg-gray-100" x-data="{ isSidebarOpen: true }" @toggle-sidebar.window="isSidebarOpen = !isSidebarOpen">
    @if(!Route::currentRouteNamed('auth.index'))
    @include('partials.sidebar')
    @endif

    <div class="w-screen">
        @yield('content')
    </div>

    @livewireScripts
    @stack("scripts")
</body>

</html>
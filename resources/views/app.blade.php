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
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.Swal = Swal.mixin({
                didOpen: function (popup) {
                    popup.querySelectorAll('.swal2-confirm, .swal2-cancel, .swal2-deny').forEach(function (btn) {
                        btn.style.setProperty('opacity', '1', 'important');
                        btn.style.setProperty('visibility', 'visible', 'important');
                        btn.style.setProperty('color', 'white', 'important');
                        if (!btn.style.background && !btn.style.backgroundColor) {
                            if (btn.classList.contains('swal2-confirm')) btn.style.backgroundColor = '#3085d6';
                            if (btn.classList.contains('swal2-cancel'))  btn.style.backgroundColor = '#6b7280';
                            if (btn.classList.contains('swal2-deny'))    btn.style.backgroundColor = '#dd6b55';
                        }
                    });
                }
            });
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-agenda flex min-h-screen bg-gray-100" x-data="{ isSidebarOpen: window.innerWidth > 768 }" @toggle-sidebar.window="isSidebarOpen = !isSidebarOpen">
    @auth
    <div x-show="isSidebarOpen" x-cloak
         @click="$dispatch('toggle-sidebar')"
         class="fixed inset-0 bg-black bg-opacity-40 z-[9] md:hidden"
         x-transition.opacity></div>
    @include('partials.sidebar')
    @endauth

    <div class="flex-1 min-w-0 overflow-x-hidden">
        @yield('content')
    </div>

    @livewireScripts
    @stack("scripts")
</body>

</html>
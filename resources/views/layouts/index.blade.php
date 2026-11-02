<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - @yield('title', 'Welcome')</title>
    <link rel="icon" href="{{ asset('img/logo-header.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('img/logo-header.svg') }}" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-[Plus_Jakarta_Sans]">
    
    <!-- Navigation Bar (Light Translucent Emerald) -->
    <nav class="bg-emerald-600/95 backdrop-blur-md border-b border-emerald-500/20 p-4 px-6 sm:px-10 flex justify-between items-center shadow-md text-white fixed top-0 left-0 w-full z-50">
        <div class="flex items-center gap-3">
            <div class="bg-white p-1 rounded-lg">
                <img src="{{ asset('img/logo-header.svg') }}" alt="Logo" class="h-6 w-auto">
            </div>
            <span class="font-bold text-base tracking-tight text-white">{{ config('app.name') }}</span>
        </div>
        <button id="menu-toggle" class="md:hidden text-white focus:outline-none p-1.5 hover:bg-emerald-700/50 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <ul id="menu" class="hidden md:flex items-center space-x-6 text-sm font-semibold text-white/95">
            <li><a href="/" class="hover:text-emerald-200 transition">Home</a></li>
            <li><a href="/schedule" class="hover:text-emerald-200 transition">Schedule</a></li>
            <li><a href="/admin/dashboard" class="hover:text-emerald-200 transition">Dashboard</a></li>
            <li><a href="#" class="hover:text-emerald-200 transition">Contact</a></li>
        </ul>
    </nav>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden md:hidden fixed top-[73px] left-0 w-full bg-emerald-600/95 backdrop-blur-lg border-b border-emerald-500/20 z-40 transition-all duration-300">
        <ul class="flex flex-col p-5 space-y-4 text-sm font-semibold text-white">
            <li><a href="/" class="hover:text-emerald-200 transition block py-2 border-b border-white/10">Home</a></li>
            <li><a href="/schedule" class="hover:text-emerald-200 transition block py-2 border-b border-white/10">Schedule</a></li>
            <li><a href="/admin/dashboard" class="hover:text-emerald-200 transition block py-2 border-b border-white/10">Dashboard</a></li>
            <li><a href="#" class="hover:text-emerald-200 transition block py-2">Contact</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="min-h-screen flex flex-col justify-between">
        <div class="flex-1">
            @yield('content')
        </div>
        
        <!-- Footer -->
        <footer class="bg-white/20 backdrop-blur-sm border-t border-slate-200/40 py-6 text-center text-xs text-slate-600">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </footer>
    </main>

    <script>
        document.getElementById('menu-toggle').addEventListener('click', function(e) {
            e.stopPropagation();
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuToggle = document.getElementById('menu-toggle');
            if (mobileMenu && !mobileMenu.contains(e.target) && e.target !== menuToggle) {
                mobileMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
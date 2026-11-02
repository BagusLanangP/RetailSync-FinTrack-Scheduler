<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - MSL Admin</title>
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
    
    <!-- Responsive layout container -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar Navigation -->
        @include('admin.layouts.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-y-auto min-w-0">
            
            <!-- Top Navbar -->
            <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 px-6 py-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <!-- Hamburger menu for mobile devices -->
                    <button id="mobile-sidebar-toggle" class="md:hidden text-slate-600 hover:text-slate-900 focus:outline-none p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">@yield('title')</h1>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Sistem Aktif</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 text-sm font-semibold rounded-xl transition duration-150 shadow-md shadow-rose-500/10 active:scale-[0.98]">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page-Specific Content Grid -->
            <main class="flex-1 p-6 max-w-7xl w-full mx-auto">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Responsive Drawer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('mobile-sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close');

            if(toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.remove('-translate-x-full');
                });
            }

            if(closeBtn && sidebar) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.add('-translate-x-full');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 768) {
                    if (sidebar && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                        sidebar.classList.add('-translate-x-full');
                    }
                }
            });
        });
    </script>
</body>
</html>

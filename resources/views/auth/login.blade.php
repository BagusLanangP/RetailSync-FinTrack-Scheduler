<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .bg-glow-1 {
            filter: blur(100px);
            background: radial-gradient(circle, rgba(16, 185, 129, 0.4) 0%, rgba(0, 0, 0, 0) 70%);
        }
        .bg-glow-2 {
            filter: blur(100px);
            background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, rgba(0, 0, 0, 0) 70%);
        }
    </style>
</head>
<body class="relative flex items-center justify-center min-h-screen bg-slate-950 overflow-hidden px-4">
    
    <!-- Background glowing shapes for premium aesthetics -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-glow-1 rounded-full pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-glow-2 rounded-full pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-glow-2 opacity-50 rounded-full pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 p-8 rounded-3xl shadow-2xl relative z-10">
        
        <!-- Header -->
        <div class="mb-6 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center shadow-inner mb-4 transition-all duration-300 hover:scale-105">
                <img src="/img/logo-header.svg" alt="Logo" class="h-10 w-auto">
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">
                Welcome Back
            </h2>
            <p class="text-sm text-slate-400 mt-1">Please sign in to access your dashboard</p>
        </div>

        <!-- Success/Error Alert -->
        @if(session('error'))
            <div class="mb-5 text-red-400 text-sm bg-red-950/40 border border-red-800/50 p-4 rounded-2xl flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 text-red-400 text-sm bg-red-950/40 border border-red-800/50 p-4 rounded-2xl flex flex-col gap-1">
                @foreach($errors->all() as $error)
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf
            
            <div class="space-y-2">
                <label for="name" class="block text-slate-300 text-sm font-medium">Username</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Admin" 
                    class="w-full px-4 py-3 bg-slate-950/50 border border-slate-800 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring focus:ring-emerald-500/20 rounded-2xl transition-all duration-300 outline-none" 
                    required autocomplete="username">
            </div>
            
            <div class="space-y-2">
                <label for="password" class="block text-slate-300 text-sm font-medium">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="••••••••" 
                        class="w-full px-4 py-3 bg-slate-950/50 border border-slate-800 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring focus:ring-emerald-500/20 rounded-2xl transition-all duration-300 outline-none pr-12" 
                        required autocomplete="current-password">
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-white focus:outline-none">
                        <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <button type="submit" 
                class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold py-3.5 px-4 rounded-2xl shadow-lg shadow-emerald-500/20 active:scale-[0.98] transform transition-all duration-150 outline-none">
                Sign In
            </button>
        </form>

        <!-- Credentials Info Box -->
        <div class="mt-6 border-t border-slate-800/80 pt-5 text-center">
            <div class="bg-indigo-950/30 border border-indigo-900/40 p-4 rounded-2xl text-left text-xs text-indigo-300 space-y-1">
                <div class="flex items-center gap-2 font-semibold text-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-emerald-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063 1.061l-1.061 1.06a1.875 1.875 0 00-2.652 2.652L9 16.5m.008-6.75h-.008v.008H9v-.008z" />
                    </svg>
                    <span>Informasi Login Default (Case Sensitive)</span>
                </div>
                <p class="text-slate-400">Database SQLite bersifat sensitif terhadap huruf besar/kecil (case-sensitive) untuk nama pengguna.</p>
                <div class="pt-2 grid grid-cols-2 gap-2 text-[11px]">
                    <div>
                        <span class="text-slate-500 block">Username (Admin):</span>
                        <strong class="text-emerald-400 font-mono">Admin</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Username (User):</span>
                        <strong class="text-emerald-400 font-mono">User</strong>
                    </div>
                    <div class="col-span-2">
                        <span class="text-slate-500 block">Password (Semua Akun):</span>
                        <strong class="text-emerald-400 font-mono">password123</strong>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.636 5.636m8.485 8.485L18.364 18.36" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }
    </script>
</body>
</html>

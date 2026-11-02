<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Homepage</title>
    <meta name="description" content="Jadwal, inventaris, dan keuangan toko dalam satu sistem yang tenang dan rapi.">
    <link rel="icon" href="{{ asset('img/logo-header.svg') }}" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Inter', 'Segoe UI', sans-serif; -webkit-font-smoothing: antialiased; }
        .display { letter-spacing: -0.035em; line-height: 1.04; }
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .9s cubic-bezier(.2,.7,.2,1), transform .9s cubic-bezier(.2,.7,.2,1); transition-delay: var(--d, 0ms); }
        .reveal.in { opacity: 1; transform: none; }
        .tile img { transition: transform 1.2s cubic-bezier(.2,.7,.2,1); }
        .tile:hover img { transform: scale(1.04); }
        .btn { transition: transform .2s ease, background-color .2s ease; }
        .btn:active { transform: scale(.97); }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            .tile img { transition: none; }
        }
    </style>
</head>
<body class="bg-[#fbfbfd] text-[#1d1d1f]">

    <!-- Nav -->
    <nav class="fixed top-0 inset-x-0 z-50 bg-[#fbfbfd]/80 backdrop-blur-xl">
        <div class="max-w-[1080px] mx-auto h-12 px-5 flex items-center justify-between text-[13px]">
            <a href="/" class="flex items-center gap-2 font-semibold">
                <img src="{{ asset('img/logo-header.svg') }}" alt="" class="h-5 w-auto">
                <span>{{ config('app.name') }}</span>
            </a>
            <div class="hidden sm:flex items-center gap-8 text-[#424245]">
                <a href="#fitur" class="hover:text-black transition">Fitur</a>
                <a href="/schedule" class="hover:text-black transition">Jadwal</a>
                <a href="/admin/dashboard" class="hover:text-black transition">Dashboard</a>
            </div>
            <a href="/admin/dashboard" class="btn bg-[#1d1d1f] text-white rounded-full px-3.5 py-1 font-medium hover:bg-black">Masuk</a>
        </div>
    </nav>

    <main>
        <!-- Hero -->
        <section class="pt-32 sm:pt-40 text-center px-5">
            <h1 class="reveal display text-[44px] sm:text-7xl lg:text-[88px] font-semibold max-w-4xl mx-auto">
                Satu sistem.<br>Seluruh toko.
            </h1>
            <p class="reveal mt-6 text-xl sm:text-2xl text-[#6e6e73] max-w-2xl mx-auto leading-snug" style="--d:100ms">
                Jadwal, inventaris, dan keuangan dalam satu sistem yang tenang dan mudah dipakai.
            </p>
            <div class="reveal mt-8 flex flex-col sm:flex-row gap-3 justify-center items-center" style="--d:200ms">
                <a href="/schedule" class="btn bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-7 py-3 text-[17px] font-medium">Lihat jadwal</a>
                <a href="/admin/dashboard" class="text-emerald-700 hover:underline text-[17px] font-medium py-3">Masuk dashboard &rsaquo;</a>
            </div>

            <div class="reveal mt-16 max-w-[1080px] mx-auto" style="--d:150ms">
                <div class="tile relative overflow-hidden rounded-[28px] sm:rounded-[40px] aspect-[4/3] sm:aspect-[16/9] bg-black">
                    <img src="{{ asset('img/toko.png') }}" alt="Tampilan toko MSL" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/55"></div>
                </div>
            </div>
        </section>

        <!-- Fitur -->
        <section id="fitur" class="max-w-[1080px] mx-auto px-5 pt-32 sm:pt-44">
            <h2 class="reveal display text-4xl sm:text-6xl font-semibold text-center max-w-3xl mx-auto">
                Semua yang toko butuhkan. Tidak lebih.
            </h2>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Jadwal -->
                <article class="reveal tile relative overflow-hidden rounded-[28px] bg-white min-h-[520px] flex flex-col">
                    <div class="p-8 sm:p-10 text-center">
                        <h3 class="display text-3xl sm:text-4xl font-semibold">Jadwal &amp; hari libur</h3>
                        <p class="mt-3 text-[17px] text-[#6e6e73] leading-snug">Atur shift karyawan dan lihat hari libur nasional langsung di kalender.</p>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1506784983877-45594efa4cbe?auto=format&fit=crop&w=1200&q=75" alt="Perencana bulanan dan secangkir kopi" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </article>

                <!-- Inventaris -->
                <article class="reveal tile relative overflow-hidden rounded-[28px] bg-[#1d1d1f] text-white min-h-[520px] flex flex-col" style="--d:100ms">
                    <div class="p-8 sm:p-10 text-center">
                        <h3 class="display text-3xl sm:text-4xl font-semibold">Inventaris</h3>
                        <p class="mt-3 text-[17px] text-neutral-400 leading-snug">Pantau stok dan supplier. Notifikasi email sebelum barang kedaluwarsa.</p>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=75" alt="Gudang dengan rak barang tertata" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </article>

                <!-- Keuangan (wide) -->
                <article class="reveal tile md:col-span-2 overflow-hidden rounded-[28px] bg-[#f5f5f7] grid md:grid-cols-2 items-stretch">
                    <div class="p-8 sm:p-14 flex flex-col justify-center">
                        <h3 class="display text-3xl sm:text-5xl font-semibold">Keuangan yang jelas.</h3>
                        <p class="mt-4 text-[19px] text-[#6e6e73] leading-snug max-w-md">
                            Anggaran 50/30/20, perbandingan target dan realisasi, serta tren bulanan. Unduh laporan performa dalam bentuk PDF kapan saja.
                        </p>
                        <a href="/admin/dashboard" class="mt-6 text-emerald-700 hover:underline text-[17px] font-medium">Buka dashboard &rsaquo;</a>
                    </div>
                    <div class="min-h-[300px] overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=75" alt="Laptop menampilkan grafik analitik keuangan" class="w-full h-full object-cover" loading="lazy">
                    </div>
                </article>
            </div>
        </section>

        <!-- CTA -->
        <section class="text-center px-5 py-32 sm:py-44">
            <h2 class="reveal display text-4xl sm:text-6xl font-semibold max-w-3xl mx-auto">Mulai dari jadwal hari ini.</h2>
            <div class="reveal mt-8 flex flex-col sm:flex-row gap-3 justify-center items-center" style="--d:100ms">
                <a href="/schedule" class="btn bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-7 py-3 text-[17px] font-medium">Lihat jadwal</a>
                <a href="/admin/dashboard" class="text-emerald-700 hover:underline text-[17px] font-medium py-3">Masuk dashboard &rsaquo;</a>
            </div>
        </section>
    </main>

    <footer class="bg-[#f5f5f7] text-xs text-[#6e6e73]">
        <div class="max-w-[1080px] mx-auto px-5 py-6 flex flex-col sm:flex-row justify-between gap-2">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <span>Foto: Unsplash</span>
        </div>
    </footer>

    <script>
        const els = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
            }, { threshold: 0.12 });
            els.forEach(el => io.observe(el));
        } else {
            els.forEach(el => el.classList.add('in'));
        }
    </script>
</body>
</html>

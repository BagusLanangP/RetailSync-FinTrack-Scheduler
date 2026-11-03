@extends('admin.layouts.app')

@section('title', isset($schedule) ? 'Edit Agenda' : 'Buat Agenda Baru')

@section('content')
    <!-- Header Page -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">
                {{ isset($schedule) ? 'Edit Agenda Schedule' : 'Buat Agenda Schedule Baru' }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                {{ isset($schedule) ? 'Perbarui data agenda operasional Anda.' : 'Tambahkan agenda operasional baru ke sistem ' . config('app.name') . '.' }}
            </p>
        </div>
        <div>
            <a href="{{ route('admin.schedule.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-sm font-semibold rounded-xl transition duration-150 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke List</span>
            </a>
        </div>
    </div>

    <!-- Global Alert Messaging -->
    @if(session('success'))
        <div id="form-success-alert" class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl mb-6 flex justify-between items-center text-sm shadow-sm transition duration-300">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="closeAlert('form-success-alert')" class="text-emerald-500 hover:text-emerald-700 font-bold px-2">✖</button>
        </div>
    @endif

    @if($errors->any())
        <div id="form-error-alert" class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl mb-6 flex justify-between items-center text-sm shadow-sm">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <span class="font-bold">Gagal menyimpan agenda:</span>
                    <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button onclick="closeAlert('form-error-alert')" class="text-rose-500 hover:text-rose-700 font-bold px-2 self-start">✖</button>
        </div>
    @endif

    <form action="{{ isset($schedule) ? route('schedule.update', $schedule->id) : route('schedule.store') }}" method="POST" class="space-y-6">
        @csrf
        @if(isset($schedule))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form Column (Left) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-5">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Informasi Detail Agenda</h3>

                    <!-- Nama Agenda -->
                    <div class="space-y-1.5">
                        <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Agenda / Kegiatan</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $schedule->name ?? '') }}" required 
                               placeholder="Contoh: Rapat Koordinasi Kuartalan" 
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('name') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">
                        @error('name')
                            <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Row 2: Jenis Schedule & Tanggal Pelaksanaan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="jenis_schedule_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori / Jenis Schedule</label>
                            <select name="jenis_schedule_id" id="jenis_schedule_id" required 
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('jenis_schedule_id') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($jenisSchedules as $jenis)
                                    <option value="{{ $jenis->id }}"
                                        {{ old('jenis_schedule_id', $schedule->jenis_schedule_id ?? '') == $jenis->id ? 'selected' : '' }}>
                                        {{ $jenis->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('jenis_schedule_id')
                                <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="date" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Pelaksanaan</label>
                            <input type="date" name="date" id="date" value="{{ old('date', $schedule->date ?? '') }}" required 
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('date') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">
                            @error('date')
                                <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 3: Budget & Tanggal Reminder -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="budget" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Biaya Estimasi (Rupiah)</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-slate-400 text-sm font-semibold">Rp</span>
                                </div>
                                <input type="number" step="0.01" name="budget" id="budget" value="{{ old('budget', $schedule->budget ?? 0) }}" 
                                       placeholder="0" 
                                       class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('budget') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">
                            

                            <div class="pt-3 space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <label for="budget_final" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Biaya Final (Rupiah)</label>
                                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">Isi nanti jika harga belum pasti</span>
                                </div>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-slate-400 text-sm font-semibold">Rp</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" name="budget_final" id="budget_final" value="{{ old('budget_final', $schedule->budget_final ?? '') }}"
                                           placeholder="Kosong = masih estimasi"
                                           class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('budget_final') border-rose-500 @enderror">
                                </div>
                                @error('budget_final')
                                    <p class="text-xs text-rose-500 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
</div>
                            <span id="budget-helper" class="text-xs text-emerald-600 font-semibold mt-1 block h-4"></span>
                            <div id="budget-alert-info" class="hidden text-xs font-semibold mt-1.5 p-3 bg-slate-50 border border-slate-200/60 rounded-xl flex flex-col gap-1.5">
                                <span id="budget-remaining-text" class="text-slate-500">Sisa anggaran kategori ini: Rp 0 / Rp 0</span>
                                <a id="budget-adjust-link" href="#" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Sesuaikan Rencana Budgeting</span>
                                </a>
                            </div>
                            @error('budget')
                                <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center">
                                <label for="reminder_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Reminder</label>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Harus ≥ Tanggal Mulai</span>
                            </div>
                            <input type="date" name="reminder_date" id="reminder_date" value="{{ old('reminder_date', $schedule->reminder_date ?? '') }}" required 
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('reminder_date') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">
                            
                            <!-- Custom Warning for Date Constraints -->
                            <div id="date-warning" class="hidden mt-1.5 p-3 bg-rose-50 border border-rose-100 text-rose-800 text-[11px] font-medium rounded-xl flex items-start gap-2">
                                <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span><strong>Error:</strong> Tanggal reminder tidak boleh lebih awal dari tanggal pelaksanaan agenda.</span>
                            </div>

                            @error('reminder_date')
                                <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <!-- Catatan / Keterangan -->
                    <div class="space-y-1.5">
                        <label for="note" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan / Keterangan</label>
                        <textarea name="note" id="note" rows="3" placeholder="Tambahkan deskripsi atau informasi detail mengenai agenda ini..." 
                                  class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 @error('note') border-rose-500 focus:border-rose-500 focus:ring-rose-500/10 @enderror">{{ old('note', $schedule->note ?? '') }}</textarea>
                        @error('note')
                            <p class="text-xs text-rose-500 mt-1 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Sidebar Column (Right) -->
            <div class="space-y-6">
                <!-- Card 1: Pengaturan Berulang -->
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Konfigurasi Tambahan</h3>
                    
                    <div class="flex items-center justify-between p-3.5 bg-slate-50/70 border border-slate-100 rounded-xl">
                        <div>
                            <span class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Jadikan Berulang?</span>
                            <span class="block text-[10px] text-slate-400 mt-0.5">Jadwal akan diulang berkala.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="berulang" value="0">
                            <input type="checkbox" name="berulang" id="berulang" value="1" 
                                   {{ old('berulang', $schedule->berulang ?? false) ? 'checked' : '' }} 
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Card 2: Hari Libur Nasional API -->
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-3">Integrasi Hari Libur</h3>

                    <div class="space-y-3">
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Sinkronkan dengan hari libur nasional untuk memudahkan pengisian nama dan penentuan tanggal agenda.
                        </p>

                        <button type="button" id="toggleHolidayBtn" onclick="toggleHolidayList()" 
                                class="w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs border border-slate-200/80 hover:border-slate-300 font-semibold rounded-xl transition duration-150 flex items-center justify-center gap-2 shadow-sm">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span id="holidayBtnText">Tampilkan Hari Libur</span>
                        </button>

                        <div id="holidayContainer" class="hidden border border-slate-100 bg-slate-50/50 rounded-2xl p-4 space-y-3 transition-all duration-300">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-200/60">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Daftar Hari Libur</span>
                                <button type="button" onclick="syncHolidays()" id="syncBtn" 
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold rounded-lg transition duration-150 flex items-center gap-1 shadow shadow-emerald-600/10">
                                    <svg id="syncIcon" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.283 8H18" />
                                    </svg>
                                    <span>Sinkronkan</span>
                                </button>
                            </div>

                            <!-- Search Input for Holidays -->
                            <div class="relative">
                                <input type="text" id="searchHolidayInput" placeholder="Cari hari libur..." 
                                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg outline-none focus:border-emerald-500 transition duration-150">
                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <ul id="holidayList" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                @forelse($dataApi as $date => $name)
                                    <li class="holiday-item p-2 bg-white hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-100 rounded-xl cursor-pointer text-xs font-semibold text-slate-700 hover:text-emerald-800 transition duration-150 flex justify-between items-center"
                                        data-name="{{ $name }}"
                                        data-date="{{ $date }}"
                                        onclick="selectHoliday('{{ $name }}', '{{ $date }}')">
                                        <span class="holiday-name truncate mr-2">{{ $name }}</span>
                                        <span class="text-[9px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded shrink-0">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                                    </li>
                                @empty
                                    <li id="noHolidays" class="p-4 text-center text-xs text-slate-400">Belum ada data. Silakan klik tombol Sinkronkan.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer Form Actions -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-5 border-t border-slate-100">
            <a href="{{ route('admin.schedule.index') }}" 
               class="w-full sm:w-auto px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-sm font-semibold rounded-xl text-center transition duration-150">
                Batal
            </a>
            <button type="submit" id="submitBtn" 
                    class="w-full sm:w-auto px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 transition duration-150 active:scale-[0.98]">
                {{ isset($schedule) ? 'Perbarui Agenda' : 'Simpan Agenda' }}
            </button>
        </div>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Alert closer
        function closeAlert(id) {
            $(`#${id}`).fadeOut(300, function() { $(this).remove(); });
        }

        // Custom Toast Notification System
        function showToast(message, type = 'success') {
            $('#custom-toast').remove();
            
            const bgClass = type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800';
            const iconColor = type === 'success' ? 'text-emerald-500' : 'text-rose-500';
            const icon = type === 'success' 
                ? `<svg class="w-5 h-5 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
                : `<svg class="w-5 h-5 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;

            const toast = $(`
                <div id="custom-toast" class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl border shadow-lg max-w-sm transition-all duration-300 translate-y-[-20px] opacity-0 ${bgClass}">
                    ${icon}
                    <span class="text-xs font-semibold">${message}</span>
                    <button onclick="$('#custom-toast').fadeOut(200)" class="text-slate-400 hover:text-slate-600 font-bold ml-2">✖</button>
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.removeClass('translate-y-[-20px] opacity-0');
            }, 10);
            
            setTimeout(() => {
                toast.addClass('translate-y-[-20px] opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Toggle Holiday List Widget
        function toggleHolidayList() {
            const container = $('#holidayContainer');
            const btnText = $('#holidayBtnText');
            if (container.hasClass('hidden')) {
                container.removeClass('hidden').hide().fadeIn(300);
                btnText.text('Sembunyikan Hari Libur');
            } else {
                container.fadeOut(200, function() {
                    $(this).addClass('hidden');
                });
                btnText.text('Tampilkan Hari Libur');
            }
        }

        // Holiday Search Filter
        $('#searchHolidayInput').on('input', function() {
            const query = $(this).val().toLowerCase();
            let hasVisible = false;
            $('.holiday-item').each(function() {
                const name = $(this).data('name').toLowerCase();
                const date = $(this).data('date').toLowerCase();
                if (name.includes(query) || date.includes(query)) {
                    $(this).removeClass('hidden');
                    hasVisible = true;
                } else {
                    $(this).addClass('hidden');
                }
            });
            
            if (!hasVisible) {
                if ($('#noSearchHolidays').length === 0) {
                    $('#holidayList').append('<li id="noSearchHolidays" class="p-4 text-center text-xs text-slate-400">Tidak ada hari libur yang cocok.</li>');
                }
            } else {
                $('#noSearchHolidays').remove();
            }
        });

        // Holiday API Sync
        function syncHolidays() {
            const syncIcon = $('#syncIcon');
            const syncBtn = $('#syncBtn');
            
            syncIcon.addClass('animate-spin');
            syncBtn.prop('disabled', true).addClass('opacity-75');

            $.ajax({
                url: '/fetcholidays',
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        const list = $('#holidayList');
                        list.empty();
                        
                        if (response.data.length === 0) {
                            list.html('<li class="p-4 text-center text-xs text-slate-400">Belum ada data. Silakan klik tombol Sinkronkan.</li>');
                        } else {
                            response.data.forEach(function(item) {
                                // Parse date
                                const parts = item.date.split('-');
                                const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
                                const formattedDate = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                                
                                const li = $(`
                                    <li class="holiday-item p-2 bg-white hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-100 rounded-xl cursor-pointer text-xs font-semibold text-slate-700 hover:text-emerald-800 transition duration-150 flex justify-between items-center" 
                                        data-name="${item.name}" 
                                        data-date="${item.date}">
                                        <span class="holiday-name truncate mr-2">${item.name}</span>
                                        <span class="text-[9px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded shrink-0">${formattedDate}</span>
                                    </li>
                                `);
                                li.on('click', function() {
                                    selectHoliday(item.name, item.date);
                                });
                                list.append(li);
                            });
                        }
                        
                        showToast('Hari libur berhasil disinkronisasi dari API!', 'success');
                    } else {
                        showToast('Gagal menyinkronkan data dari server.', 'error');
                    }
                },
                error: function() {
                    showToast('Terjadi kesalahan koneksi ke server saat menyinkronkan API.', 'error');
                },
                complete: function() {
                    syncIcon.removeClass('animate-spin');
                    syncBtn.prop('disabled', false).removeClass('opacity-75');
                }
            });
        }

        // Auto Select Holiday callback
        function selectHoliday(name, date) {
            $('#name').val(name);
            $('#date').val(date).trigger('change');
            
            // Automatically set reminder_date to the same date
            $('#reminder_date').val(date).trigger('change');

            // Select matching category (Libur / Holiday)
            let found = false;
            $('#jenis_schedule_id option').each(function() {
                const text = $(this).text().toLowerCase();
                if (text.includes('libur') || text.includes('holiday')) {
                    $('#jenis_schedule_id').val($(this).val());
                    found = true;
                    return false; // break loop
                }
            });
            
            if (!found) {
                $('#jenis_schedule_id option').each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes('operasional') || text.includes('meeting')) {
                        $('#jenis_schedule_id').val($(this).val());
                        found = true;
                        return false;
                    }
                });
            }
            
            // Flash input borders to notify user of selection
            const targets = $('#name, #date, #reminder_date, #jenis_schedule_id');
            targets.addClass('ring-4 ring-emerald-500/20 border-emerald-500');
            setTimeout(() => {
                targets.removeClass('ring-4 ring-emerald-500/20 border-emerald-500');
            }, 1000);

            showToast('Data hari libur berhasil diterapkan ke form!', 'success');
        }

        // Date Constraints (Reminder Date >= Execution Date)
        function checkDateConstraint() {
            const dateVal = $('#date').val();
            const reminderVal = $('#reminder_date').val();
            const submitBtn = $('#submitBtn');
            const dateWarning = $('#date-warning');
            const reminderInput = $('#reminder_date');
            
            if (dateVal && reminderVal) {
                if (new Date(reminderVal) < new Date(dateVal)) {
                    dateWarning.removeClass('hidden');
                    reminderInput.addClass('border-rose-500 ring-rose-500/10').removeClass('focus:border-emerald-500 focus:ring-emerald-500/10');
                    submitBtn.prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
                } else {
                    dateWarning.addClass('hidden');
                    reminderInput.removeClass('border-rose-500 ring-rose-500/10').addClass('focus:border-emerald-500 focus:ring-emerald-500/10');
                    submitBtn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                }
            } else {
                dateWarning.addClass('hidden');
                reminderInput.removeClass('border-rose-500 ring-rose-500/10');
                submitBtn.prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
            }
        }

        let lastDateVal = $('#date').val();
        $('#date').on('change input', function() {
            const dateVal = $(this).val();
            const reminderVal = $('#reminder_date').val();
            
            // Auto fill reminder date if empty or if it was matched to old date
            if (!reminderVal || reminderVal === lastDateVal) {
                $('#reminder_date').val(dateVal);
            }
            lastDateVal = dateVal;
            checkDateConstraint();
        });

        $('#reminder_date').on('change input', checkDateConstraint);

        // Budget IDR Formatter
        function formatRupiah(value) {
            if (!value) return '';
            const number = parseFloat(value);
            if (isNaN(number)) return '';
            return 'Format Rupiah: Rp ' + number.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        $('#budget').on('input', function() {
            const val = $(this).val();
            $('#budget-helper').text(formatRupiah(val));
        });

        let remainingBudgetValue = null;

        function checkRemainingBudget() {
            const dateVal = $('#date').val();
            const categoryId = $('#jenis_schedule_id').val();
            const alertInfo = $('#budget-alert-info');
            const remainingText = $('#budget-remaining-text');
            const adjustLink = $('#budget-adjust-link');
            const excludeId = "{{ isset($schedule) ? $schedule->id : '' }}";

            if (dateVal && categoryId) {
                const period = dateVal.substring(0, 7); // YYYY-MM
                
                $.ajax({
                    url: "{{ route('admin.finance.remainingBudget') }}",
                    type: 'GET',
                    data: {
                        periode: period,
                        category_id: categoryId,
                        exclude_id: excludeId
                    },
                    success: function(response) {
                        if (response.success) {
                            remainingBudgetValue = response.remaining;
                            
                            remainingText.text('Sisa anggaran kategori ini (' + period + '): Rp ' + response.remaining.toLocaleString('id-ID') + ' / Rp ' + response.limit.toLocaleString('id-ID'));
                            alertInfo.removeClass('hidden');

                            adjustLink.attr('href', '/admin/finance/budgeting?periode=' + period);
                            if (response.limit > 0) {
                                adjustLink.removeClass('hidden');
                            } else {
                                adjustLink.addClass('hidden');
                            }
                            
                            validateBudgetAmount();
                        }
                    },
                    error: function() {
                        alertInfo.addClass('hidden');
                        remainingBudgetValue = null;
                    }
                });
            } else {
                alertInfo.addClass('hidden');
                remainingBudgetValue = null;
            }
        }

        function validateBudgetAmount() {
            const budgetVal = parseFloat($('#budget').val()) || 0;
            const remainingText = $('#budget-remaining-text');
            const budgetInput = $('#budget');

            if (remainingBudgetValue !== null) {
                if (budgetVal > remainingBudgetValue) {
                    remainingText.removeClass('text-slate-500 text-emerald-600').addClass('text-rose-600 font-bold');
                    budgetInput.addClass('border-rose-400 focus:border-rose-500 focus:ring-rose-500/10').removeClass('border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/10');
                } else {
                    remainingText.removeClass('text-rose-600 font-bold').addClass('text-emerald-600');
                    budgetInput.removeClass('border-rose-400 focus:border-rose-500 focus:ring-rose-500/10').addClass('border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/10');
                }
            } else {
                remainingText.removeClass('text-rose-600 text-emerald-600 font-bold').addClass('text-slate-500');
                budgetInput.removeClass('border-rose-400 focus:border-rose-500 focus:ring-rose-500/10').addClass('border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/10');
            }
        }

        $('#date, #jenis_schedule_id').on('change input', checkRemainingBudget);
        $('#budget').on('input', validateBudgetAmount);

        // Initialize helper on load (for edit views)
        $(document).ready(function() {
            if ($('#budget').val()) {
                $('#budget-helper').text(formatRupiah($('#budget').val()));
            }
            checkDateConstraint();
            checkRemainingBudget();
        });
    </script>
@endsection
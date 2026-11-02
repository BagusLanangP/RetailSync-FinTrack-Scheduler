@extends('layouts.index')

@section('tittle', 'homepage')

@section('content')

<div class="content mt-10 p-4 sm:p-10 pt-24 max-w-7xl mx-auto w-full">
    
    <!-- Header Actions -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Jadwal & Agenda Publik</h2>
            <p class="text-xs text-slate-500 mt-1">Daftar agenda kegiatan operasional dan jadwal hari libur terintegrasi.</p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="/schedule-create" class="flex-1 sm:flex-none px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl text-center transition duration-150">
                Excel Export
            </a>
            <a href="/schedule-create" class="flex-1 sm:flex-none px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl text-center shadow-md shadow-emerald-500/10 transition duration-150 active:scale-[0.98]">
                + Buat Schedule
            </a>
        </div>
    </div>

    <!-- Budget Widget Cards -->
    <div class="mb-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Ikhtisar Anggaran Kategori Bulan Ini ({{ \Carbon\Carbon::now()->translatedFormat('F Y') }})</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($budgetWidgetData as $widget)
                <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-sm text-slate-800">{{ $widget['kategori'] }}</span>
                            @if($widget['limit'] > 0)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $widget['remaining'] >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    {{ number_format($widget['percentage'], 1) }}%
                                </span>
                            @else
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                    0%
                                </span>
                            @endif
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-3">
                            <div class="h-1.5 rounded-full {{ $widget['remaining'] >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ $widget['percentage'] }}%"></div>
                        </div>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-500">
                            <span>Limit/Plafond:</span>
                            <span class="font-semibold text-slate-700">Rp{{ number_format($widget['limit'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Telah Digunakan:</span>
                            <span class="font-semibold text-slate-700">Rp{{ number_format($widget['used'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1.5">
                            <span class="font-bold text-slate-600">Sisa Anggaran:</span>
                            <span class="font-bold {{ $widget['remaining'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                @if($widget['remaining'] >= 0)
                                    Rp{{ number_format($widget['remaining'], 0, ',', '.') }}
                                @else
                                    -Rp{{ number_format(abs($widget['remaining']), 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Filter & Filter Buttons Card -->
    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm mb-6 space-y-4">
        
        <!-- Filter Form -->
        <form method="GET" action="{{ route('public.schedule.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="w-full sm:w-64 space-y-1.5">
                <label for="jenis" class="block text-xs font-semibold text-slate-500 uppercase">Filter Jenis Schedule</label>
                <select name="jenis" id="jenis" class="w-full border border-slate-200/80 bg-slate-50/50 rounded-xl px-3 py-2 text-sm focus:border-emerald-500 focus:ring focus:ring-emerald-500/10 outline-none transition duration-150">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($jenisSchedule as $jenis)
                        <option value="{{ $jenis->id }}" {{ request('jenis') == $jenis->id ? 'selected' : '' }}>
                            {{ $jenis->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-xl transition duration-150">
                Filter
            </button>
            @if(request('jenis'))
                <a href="{{ route('public.schedule.index') }}" class="w-full sm:w-auto px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl text-center transition duration-150">
                    Reset
                </a>
            @endif
        </form>

        <!-- Divider -->
        <div class="border-t border-slate-100"></div>

        <!-- Filter Status Buttons -->
        <div class="flex flex-wrap gap-2">
            <button id="show-all" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition duration-150">
                Semua Agenda
            </button>
            <button id="show-upcoming" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl transition duration-150">
                Jadwal Mendatang
            </button>
            <button id="show-finished" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold rounded-xl transition duration-150">
                Jadwal Selesai
            </button>
        </div>

    </div>

    <!-- Alert Messaging -->
    @if (session('success'))
        <div id="alert-success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl mb-6 flex justify-between items-center text-sm">
            <span>{{ session('success') }}</span>
            <button onclick="closeAlert('alert-success')" class="text-emerald-500 hover:text-emerald-700 font-bold px-2">✖</button>
        </div>
    @endif
    
    @if (session('error'))
        <div id="alert-error" class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl mb-6 flex justify-between items-center text-sm">
            <span>{{ session('error') }}</span>
            <button onclick="closeAlert('alert-error')" class="text-rose-500 hover:text-rose-700 font-bold px-2">✖</button>
        </div>
    @endif

    <!-- View Mode Switcher -->
    <div class="mb-6 flex bg-slate-100 p-1.5 rounded-2xl shadow-inner max-w-[320px] border border-slate-200/50">
        <button onclick="switchView('table')" id="btn-view-table" class="flex-1 py-2 text-xs font-bold rounded-xl text-center transition duration-150 bg-white text-emerald-700 shadow-sm">
            Daftar Tabel
        </button>
        <button onclick="switchView('calendar')" id="btn-view-calendar" class="flex-1 py-2 text-xs font-bold rounded-xl text-center transition duration-150 text-slate-500 hover:text-slate-900">
            Kalender Agenda
        </button>
    </div>

    <!-- Table View Container -->
    <div id="table-view-container" class="transition-all duration-300">
        <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="schedule-table" class="w-full text-sm text-left text-slate-600">
                <thead class="text-xs uppercase bg-slate-50 text-slate-400 font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 w-[5%]">No</th>
                        <th class="px-6 py-4 w-[25%]">Nama Kegiatan</th>
                        <th class="px-6 py-4 w-[15%]">Kategori</th>
                        <th class="px-6 py-4 w-[18%]">Tanggal Pelaksanaan</th>
                        <th class="px-6 py-4 w-[12%]">Budget</th>
                        <th class="px-6 py-4 w-[25%]">Catatan</th>
                        <th class="px-6 py-4 w-[18%] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body" class="divide-y divide-slate-100">
                    @foreach ($data as $schedule)
                        <tr id="row-{{ $schedule->id }}" 
                            class="hover:bg-slate-50/50 transition-colors {{ $schedule->status ? 'bg-slate-50/70 text-slate-400 opacity-80' : '' }}" 
                            data-status="{{ $schedule->status ? 'true' : 'false' }}">
                            <td class="px-6 py-4 font-semibold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($schedule->status)
                                        <span class="text-emerald-500" title="Selesai">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="text-slate-300" title="Belum Selesai">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <circle cx="12" cy="12" r="9" />
                                            </svg>
                                        </span>
                                    @endif
                                    <span class="font-bold text-slate-900 {{ $schedule->status ? 'line-through text-slate-400' : '' }}">{{ $schedule->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg border {{ $schedule->status ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-indigo-50 text-indigo-600 border-indigo-100' }}">
                                    {{ $schedule->jenisSchedule->nama }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                <div>Mulai: {{ \Carbon\Carbon::parse($schedule->date)->format('d M Y') }}</div>
                                <div class="text-slate-400 mt-1">Remind: {{ \Carbon\Carbon::parse($schedule->reminder_date)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $schedule->budget ? 'Rp' . number_format($schedule->budget, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate italic">
                                {{ $schedule->note ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-1.5 items-center">
                                
                                <!-- Edit Button -->
                                <a href="{{ route('schedule.edit', $schedule->id) }}" 
                                   class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl transition duration-150"
                                   title="Edit Schedule">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4.5 h-4.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 20.062a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>

                                <!-- Delete Button -->
                                <form action="{{ route('schedule.destroy', $schedule->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus schedule ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition duration-150" title="Hapus Schedule">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4.5 h-4.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </form>

                                <!-- Email Notifier Button -->
                                <form action="{{ route('email.schedule', $schedule->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl transition duration-150" title="Kirim Notifikasi Email">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4.5 h-4.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                        </svg>
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- FullCalendar CSS & JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
    /* Customize FullCalendar to match Emerald Theme */
    .fc-theme-standard .fc-scrollgrid {
        border-color: #f1f5f9 !important;
    }
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: #f1f5f9 !important;
    }
    .fc .fc-toolbar-title {
        font-size: 1rem !important;
        font-weight: 700;
        color: #0f172a;
    }
    .fc .fc-button-primary {
        background-color: #ffffff !important;
        border-color: #e2e8f0 !important;
        color: #475569 !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        border-radius: 10px !important;
        padding: 5px 10px !important;
        text-transform: capitalize !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        transition: all 150ms ease !important;
    }
    .fc .fc-button-primary:hover {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border-color: #cbd5e1 !important;
    }
    .fc .fc-button-primary:disabled {
        background-color: #f1f5f9 !important;
        color: #94a3b8 !important;
        border-color: #e2e8f0 !important;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active, 
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #059669 !important;
        border-color: #059669 !important;
        color: #ffffff !important;
    }
    .fc .fc-daygrid-day.fc-day-today {
        background-color: #ecfdf5 !important;
    }
    .fc .fc-col-header-cell {
        background-color: #f8fafc;
        padding: 8px 0 !important;
        font-size: 0.7rem !important;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.05em;
    }
    .fc-event {
        cursor: pointer;
        padding: 2px 4px !important;
        border-radius: 6px !important;
        font-size: 0.7rem !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02) !important;
        transition: transform 100ms ease, box-shadow 100ms ease !important;
    }
    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    }
</style>

<!-- Scripting (jQuery + Vanilla JS Hooks) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function showDetailModal(button) {
        let data = {};
        if (button && (button.name || button.extendedProps)) {
            const props = button.extendedProps || {};
            data = {
                name: props.name,
                category: props.category,
                date: props.date,
                reminder: props.reminder,
                budget: props.budget,
                creator: props.creator,
                status: props.status,
                completed: props.completed,
                note: props.note
            };
        } else {
            const btn = $(button);
            data = {
                name: btn.data('name'),
                category: btn.data('category'),
                date: btn.data('date'),
                reminder: btn.data('reminder'),
                budget: btn.data('budget'),
                creator: btn.data('creator'),
                status: btn.data('status'),
                completed: btn.data('completed'),
                note: btn.data('note')
            };
        }

        $('#detail-name').text(data.name || '-');
        $('#detail-category').text(data.category || '-');
        $('#detail-date').text(data.date || '-');
        $('#detail-reminder').text(data.reminder || '-');
        $('#detail-budget').text(data.budget || '-');
        $('#detail-creator').text(data.creator || '-');
        
        const statusSpan = $('#detail-status');
        statusSpan.empty();
        if (data.status === 'Selesai' || data.status === 'Selesai (Libur)') {
            statusSpan.html('<span class="px-2 py-0.5 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded">Selesai</span>');
            $('.id-completed-container').show();
            $('#detail-completed').text(data.completed || '-');
        } else {
            statusSpan.html('<span class="px-2 py-0.5 text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100 rounded">Belum Selesai</span>');
            $('.id-completed-container').hide();
        }
        
        $('#detail-note').text(data.note || '-');

        $('#detail-modal').removeClass('hidden').addClass('flex');
        setTimeout(() => {
            $('#modal-card').removeClass('scale-95').addClass('scale-100');
        }, 10);
    }

    function closeDetailModal() {
        $('#modal-card').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => {
            $('#detail-modal').removeClass('flex').addClass('hidden');
        }, 150);
    }

    // Switch View table vs calendar
    let calendar = null;
    function switchView(mode) {
        if (mode === 'table') {
            $('#table-view-container').removeClass('hidden');
            $('#calendar-view-container').addClass('hidden');
            $('#btn-view-table').addClass('bg-white text-emerald-700 shadow-sm').removeClass('text-slate-500');
            $('#btn-view-calendar').addClass('text-slate-500').removeClass('bg-white text-emerald-700 shadow-sm');
        } else {
            $('#table-view-container').addClass('hidden');
            $('#calendar-view-container').removeClass('hidden');
            $('#btn-view-calendar').addClass('bg-white text-emerald-700 shadow-sm').removeClass('text-slate-500');
            $('#btn-view-table').addClass('text-slate-500').removeClass('bg-white text-emerald-700 shadow-sm');
            
            // Initialize FullCalendar on first switch
            if (!calendar) {
                initCalendar();
            } else {
                calendar.updateSize();
            }
        }
    }

    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        const events = @json($calendarEvents);
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            editable: false, // Public calendars are read-only
            events: events,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            buttonText: {
                today: 'Hari Ini',
                month: 'Bulan',
                week: 'Minggu'
            },
            eventClick: function(info) {
                showDetailModal(info.event);
            }
        });
        
        calendar.render();
    }


    const showUpcomingBtn = document.getElementById('show-upcoming');
    if (showUpcomingBtn) {
        showUpcomingBtn.addEventListener('click', function () {
            let rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                let status = row.getAttribute('data-status');
                row.style.display = (status === 'false') ? '' : 'none';
            });
        });
    }

    const showFinishedBtn = document.getElementById('show-finished');
    if (showFinishedBtn) {
        showFinishedBtn.addEventListener('click', function () {
            let rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                let status = row.getAttribute('data-status');
                row.style.display = (status === 'true') ? '' : 'none';
            });
        });
    }

    const showAllBtn = document.getElementById('show-all');
    if (showAllBtn) {
        showAllBtn.addEventListener('click', function () {
            let rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        });
    }

    function closeAlert(alertId) {
        document.getElementById(alertId)?.remove();
    }

    // Auto-close alert after 3 seconds
    setTimeout(() => {
        closeAlert('alert-success');
        closeAlert('alert-error');
    }, 3000);

    // Close modal when clicking outside the card
    $(document).ready(function() {
        $('#detail-modal').on('click', function(e) {
            if (e.target === this) {
                closeDetailModal();
            }
        });
    });
</script>

@endsection
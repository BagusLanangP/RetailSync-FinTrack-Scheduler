<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ApiLibur;
use Illuminate\Http\Request;
use App\Models\JenisSchedule;
use App\Models\MonthlyBudget;
use App\Models\DailyTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['jenisSchedule', 'creator']);
        
        if ($request->filled('jenis')) {
            $query->where('jenis_schedule_id', $request->jenis); // ✅ Benar
        }

        $data = $query->orderBy('status', 'asc')->orderBy('date', 'asc')->get();
        $jenisSchedule = JenisSchedule::all();
        $dataSchedule = Schedule::all();

        // Fetch national holidays to display on the calendar
        $holidays = ApiLibur::all();
        
        $calendarEvents = [];
        
        // Map schedules to FullCalendar events
        foreach ($data as $s) {
            $calendarEvents[] = [
                'id' => 'schedule-' . $s->id,
                'dbId' => $s->id,
                'title' => $s->name,
                'start' => $s->date,
                'end' => $s->date,
                'type' => 'schedule',
                'backgroundColor' => $s->status ? '#10b981' : '#4f46e5',
                'borderColor' => $s->status ? '#059669' : '#4338ca',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'name' => $s->name,
                    'category' => $s->jenisSchedule->nama ?? 'Umum',
                    'date' => \Carbon\Carbon::parse($s->date)->format('d M Y'),
                    'reminder' => \Carbon\Carbon::parse($s->reminder_date)->format('d M Y'),
                    'budget' => $s->is_estimate ? 'Rp' . number_format($s->budget, 0, ',', '.') . ' (estimasi)' : 'Rp' . number_format($s->budget_final, 0, ',', '.'),
                    'budgetEstimate' => 'Rp' . number_format($s->budget, 0, ',', '.'),
                    'isEstimate' => $s->is_estimate,
                    'creator' => $s->creator->name ?? 'Sistem',
                    'status' => $s->status ? 'Selesai' : 'Belum Selesai',
                    'completed' => $s->completed_at ? \Carbon\Carbon::parse($s->completed_at)->format('d M Y H:i') : '-',
                    'note' => $s->note ?? '-',
                    'isEditable' => true
                ]
            ];
        }
        
        // Map holidays to FullCalendar events
        foreach ($holidays as $h) {
            $calendarEvents[] = [
                'id' => 'holiday-' . $h->id,
                'title' => '🎉 ' . $h->name,
                'start' => $h->date,
                'end' => $h->date,
                'type' => 'holiday',
                'backgroundColor' => '#fef3c7',
                'borderColor' => '#f59e0b',
                'textColor' => '#b45309',
                'extendedProps' => [
                    'name' => $h->name,
                    'category' => 'Hari Libur Nasional',
                    'date' => \Carbon\Carbon::parse($h->date)->format('d M Y'),
                    'reminder' => '-',
                    'budget' => '-',
                    'creator' => 'Sistem (API)',
                    'status' => 'Selesai (Libur)',
                    'completed' => '-',
                    'note' => 'Hari libur nasional resmi disinkronkan dari API Pemerintah.',
                    'isEditable' => false
                ]
            ];
        }

        // Calculate current month's budget performance per category
        $currentMonth = Carbon::now()->format('Y-m');
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthlyBudget = MonthlyBudget::where('periode', $currentMonth)->first();
        $budgetWidgetData = [];

        foreach ($jenisSchedule as $jenis) {
            $allocationLimit = 0;
            if ($monthlyBudget) {
                $allocation = $monthlyBudget->allocations()->where('jenis_schedule_id', $jenis->id)->first();
                if ($allocation) {
                    $allocationLimit = floatval($allocation->nominal_limit);
                }
            }

            // Sum schedules budgets
            $usedScheduleBudget = floatval(Schedule::where('jenis_schedule_id', $jenis->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('budget')
                ->sum(\DB::raw('COALESCE(budget_final, budget)')));

            // Sum daily transactions (expenses)
            $usedDailyTx = floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->where('kategori', $jenis->nama)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->sum('nominal'));

            $totalUsed = $usedScheduleBudget + $usedDailyTx;
            $remaining = $allocationLimit - $totalUsed;

            $budgetWidgetData[] = [
                'kategori' => $jenis->nama,
                'limit' => $allocationLimit,
                'used' => $totalUsed,
                'remaining' => $remaining,
                'percentage' => $allocationLimit > 0 ? min(($totalUsed / $allocationLimit) * 100, 100) : 0
            ];
        }

        if ($request->is('admin/*')) {
            return view('admin.schedule.index', compact('data', 'dataSchedule', 'jenisSchedule', 'calendarEvents', 'budgetWidgetData'));
        }
        return view('schedule.index', compact('data', 'dataSchedule', 'jenisSchedule', 'calendarEvents', 'budgetWidgetData'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $jenisSchedules = JenisSchedule::all();
        $dataApi = ApiLibur::pluck('name', 'date');
        return view('admin.schedule.create', compact('dataApi', 'jenisSchedules'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'jenis_schedule_id' => 'required|exists:jenis_schedules,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'budget' => 'nullable|numeric|min:0',
            'budget_final' => 'nullable|numeric|min:0',
            'berulang' => 'required|in:0,1',
            'reminder_date' => 'required|date|after_or_equal:date',
        ]);

        // Simpan data ke database
        Schedule::create([
            'name' => $request->name,
            'jenis_schedule_id' => $request->jenis_schedule_id,
            'date' => $request->date,
            'note' => $request->note,
            'budget' => $request->budget ?? 0,
            'budget_final' => $request->budget_final,
            'berulang' => $request->berulang,
            'reminder_date' => $request->reminder_date,
            'created_by' => Auth::id(),
        ]);

        // Redirect dengan pesan sukses
        return redirect()->back()->with('success', 'Schedule berhasil disimpan!');
    }

    // public function toggleStatus($id)
    // {
    //     $schedule = Schedule::findOrFail($id);
    //     $schedule->status = !$schedule->status; // Toggle status
    //     $schedule->save();

    //     return redirect()->back()->with('success', 'Schedule diperbarui!');
    // }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $schedule = Schedule::findOrFail($id);
        $jenisSchedules = JenisSchedule::all();
        $dataApi = ApiLibur::pluck('name', 'date');

        return view('admin.schedule.create', compact('schedule', 'jenisSchedules', 'dataApi'));
    }


    /**
     * Update the specified resource in storage.
     */
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        $schedule->delete();

        return back()->with('success', 'Schedule berhasil dihapus.');
        }

    public function toggleStatus(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->status = !$schedule->status; // Toggle status
        $schedule->completed_at = $schedule->status ? now() : null; // Set completion date
        $schedule->save();

        return response()->json([
            'success' => true,
            'status' => $schedule->status,
            'id' => $schedule->id,
            'completed_at' => $schedule->completed_at ? $schedule->completed_at->format('d M Y H:i') : null
        ]);
    }

    public function update(Request $request, $id)
    {
        // Cari data berdasarkan ID
        $schedule = Schedule::findOrFail($id);

        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'jenis_schedule_id' => 'required|exists:jenis_schedules,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'budget' => 'nullable|numeric|min:0',
            'budget_final' => 'nullable|numeric|min:0',
            'berulang' => 'required|in:0,1',
            'reminder_date' => 'required|date|after_or_equal:date',
        ]);

        // Update data
        $schedule->update([
            'name' => $request->name,
            'jenis_schedule_id' => $request->jenis_schedule_id,
            'date' => $request->date,
            'note' => $request->note,
            'budget' => $request->budget ?? 0,
            'budget_final' => $request->budget_final,
            'berulang' => $request->berulang,
            'reminder_date' => $request->reminder_date,
        ]);

        // Redirect ke halaman index dengan pesan sukses
        return redirect()->route('admin.schedule.index')->with('success', 'Schedule berhasil diperbarui!');
    }

    /**
     * Update the date of a schedule (via drag and drop in Calendar view).
     */
    public function updateDate(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'date' => 'required|date',
        ]);

        $newDate = \Carbon\Carbon::parse($request->date);
        
        $schedule->date = $request->date;
        
        // Keep reminder_date valid (reminder_date must be after_or_equal to execution date)
        if (\Carbon\Carbon::parse($schedule->reminder_date)->lt($newDate)) {
            $schedule->reminder_date = $request->date;
        }

        $schedule->save();

        return response()->json([
            'success' => true,
            'message' => 'Tanggal agenda berhasil diperbarui!'
        ]);
    }

}

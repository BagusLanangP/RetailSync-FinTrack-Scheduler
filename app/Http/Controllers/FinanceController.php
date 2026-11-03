<?php

namespace App\Http\Controllers;

use App\Models\InventoryChecking;
use App\Models\Schedule;
use App\Models\JenisSchedule;
use App\Models\MonthlyBudget;
use App\Models\BudgetAllocation;
use App\Models\DailyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class FinanceController extends Controller
{
    /**
     * Dashboard Analisis & Ringkasan Keuangan
     */
    /**
     * Dashboard Analisis & Ringkasan Keuangan
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->query('periode', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($selectedMonth . '-01');
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();

        // ==========================================
        // 1. POPULASI DAFTAR PERIODE BULANAN
        // ==========================================
        $monthsList = collect();
        InventoryChecking::where('status', 'aktif')->whereNotNull('tanggal')->pluck('tanggal')->each(function($date) use ($monthsList) {
            $monthsList->push(Carbon::parse($date)->format('Y-m'));
        });
        DailyTransaction::pluck('tanggal')->each(function($date) use ($monthsList) {
            $monthsList->push(Carbon::parse($date)->format('Y-m'));
        });
        Schedule::whereNotNull('budget')->pluck('date')->each(function($date) use ($monthsList) {
            $monthsList->push(Carbon::parse($date)->format('Y-m'));
        });
        $monthsList->push(Carbon::now()->format('Y-m'));
        $availablePeriods = $monthsList->unique()->sort()->reverse()->values()->toArray();

        // ==========================================
        // 2. DATA TRANSAKSI HARIAN UNTUK PERIODE TERPILIH
        // ==========================================
        $itemsInMonth = InventoryChecking::where('status', 'aktif')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        $schedulesInMonth = Schedule::whereNotNull('budget')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->with(['jenisSchedule', 'creator'])
            ->get();

        $txInMonth = DailyTransaction::whereBetween('tanggal', [$startOfMonth, $endOfMonth])->get();

        $dailyTransactions = [];

        // Gabungkan pemasukan inventory bulanan terpilih
        foreach ($itemsInMonth as $item) {
            $profit = (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
            $dailyTransactions[] = [
                'tanggal_raw' => Carbon::parse($item->tanggal),
                'tanggal' => Carbon::parse($item->tanggal)->format('d M Y'),
                'nama' => $item->nama,
                'tipe' => 'Pemasukan',
                'kategori' => $item->jenisBarang->name ?? 'Umum',
                'nominal' => $profit,
                'sumber' => 'Inventory',
                'keterangan' => 'Profit: ' . $item->jumlah . ' unit x Rp' . number_format($item->harga_jual - $item->harga_pokok, 0, ',', '.')
            ];
        }

        // Gabungkan pengeluaran schedule bulanan terpilih
        foreach ($schedulesInMonth as $s) {
            $dailyTransactions[] = [
                'tanggal_raw' => Carbon::parse($s->date),
                'tanggal' => Carbon::parse($s->date)->format('d M Y'),
                'nama' => $s->name,
                'tipe' => 'Pengeluaran',
                'kategori' => $s->jenisSchedule->nama ?? 'Umum',
                'nominal' => $s->effective_budget,
                'sumber' => 'Schedule',
                'keterangan' => $s->note ?? 'Pengeluaran operasional / agenda'
            ];
        }

        // Gabungkan daily transactions bulanan terpilih
        foreach ($txInMonth as $tx) {
            $dailyTransactions[] = [
                'tanggal_raw' => Carbon::parse($tx->tanggal),
                'tanggal' => Carbon::parse($tx->tanggal)->format('d M Y'),
                'nama' => $tx->nama,
                'tipe' => $tx->tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran',
                'kategori' => $tx->kategori,
                'nominal' => floatval($tx->nominal),
                'sumber' => ucfirst($tx->sumber),
                'keterangan' => $tx->keterangan ?? '-'
            ];
        }

        // Urutkan transaksi bulanan berdasarkan tanggal terbaru
        usort($dailyTransactions, function ($a, $b) {
            return $b['tanggal_raw']->timestamp <=> $a['tanggal_raw']->timestamp;
        });


        // ==========================================
        // 3. HITUNG NILAI METRIK UNTUK PERIODE TERPILIH
        // ==========================================
        $totalGrossProfit = 0;
        foreach ($itemsInMonth as $item) {
            $totalGrossProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $totalGrossProfit += floatval(DailyTransaction::where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        $totalExpenses = floatval(Schedule::whereNotNull('budget')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $totalExpenses += floatval(DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        $netMargin = $totalGrossProfit - $totalExpenses;

        // Hitung Saldo Kas Kumulatif All-Time (dengan modal awal 1 Milyar)
        $allTimeProfit = 0;
        foreach (InventoryChecking::where('status', 'aktif')->get() as $item) {
            $allTimeProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $allTimeProfit += floatval(DailyTransaction::where('tipe', 'pemasukan')->sum('nominal'));

        $allTimeExpense = floatval(Schedule::whereNotNull('budget')->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $allTimeExpense += floatval(DailyTransaction::where('tipe', 'pengeluaran')->sum('nominal'));

        $totalCashPool = max(1000000000 + $allTimeProfit - $allTimeExpense, 0);

        // Kebutuhan (Needs) vs Rencana (Wants) untuk periode terpilih
        $needsExpense = 0;
        $wantsExpense = 0;

        foreach ($schedulesInMonth as $s) {
            $budget = $s->effective_budget;
            $catName = $s->jenisSchedule->nama ?? 'Umum';
            if (in_array($catName, ['Operasional', 'Maintenance'])) {
                $needsExpense += $budget;
            } else {
                $wantsExpense += $budget;
            }
        }

        foreach ($txInMonth as $tx) {
            if ($tx->tipe === 'pengeluaran') {
                $nominal = floatval($tx->nominal);
                $catName = $tx->kategori;
                if (in_array($catName, ['Operasional', 'Maintenance'])) {
                    $needsExpense += $nominal;
                } else {
                    $wantsExpense += $nominal;
                }
            }
        }

        // Persentase Berdasarkan Pemasukan Periode Terpilih
        $needsPct = $totalGrossProfit > 0 ? ($needsExpense / $totalGrossProfit) * 100 : 0;
        $wantsPct = $totalGrossProfit > 0 ? ($wantsExpense / $totalGrossProfit) * 100 : 0;
        $savingsPct = $totalGrossProfit > 0 && $netMargin > 0 ? ($netMargin / $totalGrossProfit) * 100 : 0;

        $ratio = $totalGrossProfit > 0 ? ($totalExpenses / $totalGrossProfit) * 100 : ($totalExpenses > 0 ? 100 : 0);

        // Status Kesehatan Finansial Periode Terpilih
        $healthStatus = '';
        $healthColor = '';
        $healthRating = ''; 
        $healthRecommendation = '';

        if ($netMargin >= 0) {
            if ($ratio < 50) {
                $healthStatus = 'Sangat Sehat (Surplus Tinggi)';
                $healthColor = 'emerald';
                $healthRating = 95;
                $healthRecommendation = 'Arus kas periode ' . $targetDate->translatedFormat('F Y') . ' Anda dalam kondisi sangat prima. Rasio pengeluaran sangat rendah (<50%), sehingga tabungan/surplus (Savings) sangat optimal. Sisa budget secara otomatis tersimpan kembali ke saldo kas utama.';
            } else if ($ratio <= 80) {
                $healthStatus = 'Sehat (Stabil)';
                $healthColor = 'green';
                $healthRating = 75;
                $healthRecommendation = 'Arus kas berjalan seimbang dan sehat di periode ini. Pengeluaran operasional Anda terkendali dengan baik, menyisakan margin keuntungan bersih yang memadai.';
            } else {
                $healthStatus = 'Kurang Sehat (Efisiensi Diperlukan)';
                $healthColor = 'amber';
                $healthRating = 45;
                $healthRecommendation = 'Kondisi keuangan periode ini kurang ideal. Rasio pengeluaran operasional menyerap sebagian besar profit (>80%), menyisakan tabungan yang tipis. Disarankan untuk membatasi belanja non-kritis.';
            }
        } else {
            $defectPercentage = $totalGrossProfit > 0 ? (abs($netMargin) / $totalGrossProfit) * 100 : 100;
            if ($defectPercentage < 20) {
                $healthStatus = 'Kurang Sehat (Defisit Ringan)';
                $healthColor = 'amber';
                $healthRating = 35;
                $healthRecommendation = 'Periode ini mengalami defisit ringan karena beban pengeluaran melampaui profit bulanan. Disarankan menunda restocking non-prioritas ke bulan berikutnya.';
            } else {
                $healthStatus = 'Kritis (Defisit Tinggi)';
                $healthColor = 'rose';
                $healthRating = 15;
                $healthRecommendation = 'Arus kas periode ini berada pada level KRITIS (Defisit parah). Pengeluaran beban operasional menyerap anggaran jauh di atas laba kotor. Lakukan penangguhan pengeluaran non-esensial dengan segera.';
            }
        }


        // ==========================================
        // 4. DATA CHART TREN BULANAN (6 Bulan terakhir s/d periode terpilih)
        // ==========================================
        $chartLabels = [];
        $chartProfits = [];
        $chartExpenses = [];
        $chartNetMargins = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $targetDate->copy()->subMonths($i);
            $mKey = $monthDate->format('Y-m');
            $mLabel = $monthDate->translatedFormat('M Y');
            $startM = $monthDate->copy()->startOfMonth()->toDateString();
            $endM = $monthDate->copy()->endOfMonth()->toDateString();

            // Pemasukan item checking
            $itemsM = InventoryChecking::where('status', 'aktif')
                ->whereBetween('tanggal', [$startM, $endM])
                ->get();
            $profitM = 0;
            foreach ($itemsM as $item) {
                $profitM += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
            }

            // Pemasukan daily transactions
            $profitM += floatval(DailyTransaction::where('tipe', 'pemasukan')
                ->whereBetween('tanggal', [$startM, $endM])
                ->sum('nominal'));

            // Pengeluaran schedule
            $expenseM = floatval(Schedule::whereNotNull('budget')
                ->whereBetween('date', [$startM, $endM])
                ->sum(\DB::raw('COALESCE(budget_final, budget)')));

            // Pengeluaran daily transactions
            $expenseM += floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->whereBetween('tanggal', [$startM, $endM])
                ->sum('nominal'));

            $netM = $profitM - $expenseM;

            $chartLabels[] = $mLabel;
            $chartProfits[] = $profitM;
            $chartExpenses[] = $expenseM;
            $chartNetMargins[] = $netM;
        }


        // ==========================================
        // 5. DISTRIBUSI KATEGORI UNTUK PERIODE TERPILIH
        // ==========================================
        $categoryBudgets = [];
        foreach ($schedulesInMonth as $s) {
            $catName = $s->jenisSchedule->nama ?? 'Umum';
            if (!isset($categoryBudgets[$catName])) {
                $categoryBudgets[$catName] = 0;
            }
            $categoryBudgets[$catName] += $s->effective_budget;
        }
        foreach ($txInMonth as $tx) {
            if ($tx->tipe === 'pengeluaran') {
                $catName = $tx->kategori;
                if (!isset($categoryBudgets[$catName])) {
                    $categoryBudgets[$catName] = 0;
                }
                $categoryBudgets[$catName] += floatval($tx->nominal);
            }
        }
        $catLabels = array_keys($categoryBudgets);
        $catValues = array_values($categoryBudgets);


        // ==========================================
        // 6. RINGKASAN BULANAN HISTORIS (Semua periode untuk tabel perbandingan)
        // ==========================================
        $monthlySummaries = [];

        // Pendapatan Inventory all-time
        foreach (InventoryChecking::where('status', 'aktif')->whereNotNull('tanggal')->get() as $item) {
            $profit = (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
            $monthKey = Carbon::parse($item->tanggal)->format('Y-m');
            $monthLabel = Carbon::parse($item->tanggal)->translatedFormat('F Y');

            if (!isset($monthlySummaries[$monthKey])) {
                $monthlySummaries[$monthKey] = [
                    'label' => $monthLabel,
                    'pemasukan' => 0,
                    'pengeluaran' => 0,
                    'detail_pengeluaran' => []
                ];
            }
            $monthlySummaries[$monthKey]['pemasukan'] += $profit;
        }

        // DailyTransaction all-time
        foreach (DailyTransaction::get() as $tx) {
            $monthKey = Carbon::parse($tx->tanggal)->format('Y-m');
            $monthLabel = Carbon::parse($tx->tanggal)->translatedFormat('F Y');

            if (!isset($monthlySummaries[$monthKey])) {
                $monthlySummaries[$monthKey] = [
                    'label' => $monthLabel,
                    'pemasukan' => 0,
                    'pengeluaran' => 0,
                    'detail_pengeluaran' => []
                ];
            }

            if ($tx->tipe === 'pemasukan') {
                $monthlySummaries[$monthKey]['pemasukan'] += floatval($tx->nominal);
            } else {
                $monthlySummaries[$monthKey]['pengeluaran'] += floatval($tx->nominal);
                $catName = $tx->kategori;
                if (!isset($monthlySummaries[$monthKey]['detail_pengeluaran'][$catName])) {
                    $monthlySummaries[$monthKey]['detail_pengeluaran'][$catName] = 0;
                }
                $monthlySummaries[$monthKey]['detail_pengeluaran'][$catName] += floatval($tx->nominal);
            }
        }

        // Schedule expenses all-time
        foreach (Schedule::whereNotNull('budget')->whereNotNull('date')->get() as $s) {
            $budget = $s->effective_budget;
            $monthKey = Carbon::parse($s->date)->format('Y-m');
            $monthLabel = Carbon::parse($s->date)->translatedFormat('F Y');
            $catName = $s->jenisSchedule->nama ?? 'Umum';

            if (!isset($monthlySummaries[$monthKey])) {
                $monthlySummaries[$monthKey] = [
                    'label' => $monthLabel,
                    'pemasukan' => 0,
                    'pengeluaran' => 0,
                    'detail_pengeluaran' => []
                ];
            }

            $monthlySummaries[$monthKey]['pengeluaran'] += $budget;
            if (!isset($monthlySummaries[$monthKey]['detail_pengeluaran'][$catName])) {
                $monthlySummaries[$monthKey]['detail_pengeluaran'][$catName] = 0;
            }
            $monthlySummaries[$monthKey]['detail_pengeluaran'][$catName] += $budget;
        }

        krsort($monthlySummaries);

        // ==========================================
        // 7. ANALISIS VARIANS ANGGARAN (Rencana vs Realisasi)
        // ==========================================
        $monthlyBudget = MonthlyBudget::where('periode', $selectedMonth)->with('allocations.jenisSchedule')->first();
        $budgetVariance = [];

        // Fetch all active categories
        $activeCategories = JenisSchedule::where('status_aktif', true)->get();

        foreach ($activeCategories as $category) {
            $catId = $category->id;
            $catName = $category->nama;

            // 1. Batas Anggaran (Budget Limit)
            $allocation = $monthlyBudget ? $monthlyBudget->allocations->where('jenis_schedule_id', $catId)->first() : null;
            $limit = $allocation ? floatval($allocation->nominal_limit) : 0.0;

            // 2. Belanja Aktual (Actual Spent)
            // Schedule budget
            $actualSchedule = floatval(Schedule::where('jenis_schedule_id', $catId)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('budget')
                ->sum(\DB::raw('COALESCE(budget_final, budget)')));

            // DailyTransaction budget
            $actualDaily = floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->where('kategori', $catName)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->sum('nominal'));

            $actualSpent = $actualSchedule + $actualDaily;

            // 3. Sisa Dana
            $remaining = $limit - $actualSpent;

            // 4. Persentase Deviasi (Varians)
            if ($limit > 0) {
                $deviationPct = (($actualSpent - $limit) / $limit) * 100;
            } else {
                $deviationPct = $actualSpent > 0 ? 100.0 : 0.0;
            }

            $budgetVariance[] = [
                'kategori' => $catName,
                'limit' => $limit,
                'actual' => $actualSpent,
                'remaining' => $remaining,
                'deviation' => $deviationPct
            ];
        }

        // Handle any other category names from daily transactions in this month not in activeCategories
        $processedNames = $activeCategories->pluck('nama')->toArray();
        $otherTxCategories = DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->whereNotIn('kategori', $processedNames)
            ->distinct()
            ->pluck('kategori')
            ->toArray();

        foreach ($otherTxCategories as $otherCat) {
            $actualSpent = floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->where('kategori', $otherCat)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->sum('nominal'));

            $budgetVariance[] = [
                'kategori' => $otherCat,
                'limit' => 0.0,
                'actual' => $actualSpent,
                'remaining' => -$actualSpent,
                'deviation' => 100.0
            ];
        }

        return view('admin.finance.index', compact(
            'budgetVariance',
            'selectedMonth',
            'availablePeriods',
            'totalGrossProfit',
            'totalExpenses',
            'netMargin',
            'totalCashPool',
            'ratio',
            'healthStatus',
            'healthColor',
            'healthRating',
            'healthRecommendation',
            'dailyTransactions',
            'monthlySummaries',
            'needsExpense',
            'wantsExpense',
            'needsPct',
            'wantsPct',
            'savingsPct',
            'chartLabels',
            'chartProfits',
            'chartExpenses',
            'chartNetMargins',
            'catLabels',
            'catValues'
        ));
    }

    /**
     * Ekspor Laporan Performa Bulanan ke PDF (Print View)
     */
    public function exportPdf(Request $request)
    {
        $selectedMonth = $request->query('periode', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($selectedMonth . '-01');
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();

        // 1. Core KPIs
        $totalGrossProfit = 0;
        $itemsInMonth = InventoryChecking::where('status', 'aktif')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($itemsInMonth as $item) {
            $totalGrossProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $totalGrossProfit += floatval(DailyTransaction::where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        $totalExpenses = floatval(Schedule::whereNotNull('budget')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $totalExpenses += floatval(DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        $netMargin = $totalGrossProfit - $totalExpenses;

        // All time cumulative cash pool
        $allTimeProfit = 0;
        foreach (InventoryChecking::where('status', 'aktif')->get() as $item) {
            $allTimeProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $allTimeProfit += floatval(DailyTransaction::where('tipe', 'pemasukan')->sum('nominal'));

        $allTimeExpense = floatval(Schedule::whereNotNull('budget')->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $allTimeExpense += floatval(DailyTransaction::where('tipe', 'pengeluaran')->sum('nominal'));

        $totalCashPool = max(1000000000 + $allTimeProfit - $allTimeExpense, 0);

        // 2. Health Check Ratings
        $ratio = $totalGrossProfit > 0 ? ($totalExpenses / $totalGrossProfit) * 100 : ($totalExpenses > 0 ? 100 : 0);
        $healthStatus = '';
        $healthRating = 0;
        $healthColor = '';
        if ($netMargin >= 0) {
            if ($ratio < 50) {
                $healthStatus = 'Sangat Sehat';
                $healthRating = 95;
                $healthColor = 'emerald';
            } else if ($ratio <= 80) {
                $healthStatus = 'Sehat';
                $healthRating = 75;
                $healthColor = 'green';
            } else {
                $healthStatus = 'Kurang Sehat';
                $healthRating = 45;
                $healthColor = 'amber';
            }
        } else {
            $defectPercentage = $totalGrossProfit > 0 ? (abs($netMargin) / $totalGrossProfit) * 100 : 100;
            if ($defectPercentage < 20) {
                $healthStatus = 'Kurang Sehat (Defisit)';
                $healthRating = 35;
                $healthColor = 'amber';
            } else {
                $healthStatus = 'Kritis (Defisit Tinggi)';
                $healthRating = 15;
                $healthColor = 'rose';
            }
        }

        // 3. Grouped details by category
        // A. Pembelian Barang / Restocking
        $restockItems = InventoryChecking::where('status', 'aktif')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->with('jenisBarang')
            ->get();

        // B. Operasional
        $opsCategoryId = JenisSchedule::where('nama', 'Operasional')->value('id');
        $opsSchedules = Schedule::where('jenis_schedule_id', $opsCategoryId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('budget')
            ->get();
        $opsTransactions = DailyTransaction::where('tipe', 'pengeluaran')
            ->where('kategori', 'Operasional')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        // C. Maintenance
        $maintCategoryId = JenisSchedule::where('nama', 'Maintenance')->value('id');
        $maintSchedules = Schedule::where('jenis_schedule_id', $maintCategoryId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('budget')
            ->get();
        $maintTransactions = DailyTransaction::where('tipe', 'pengeluaran')
            ->where('kategori', 'Maintenance')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        // D. Other expenses (custom categories or Libur etc.)
        $otherSchedules = Schedule::whereNotIn('jenis_schedule_id', [$opsCategoryId, $maintCategoryId])
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('budget')
            ->with('jenisSchedule')
            ->get();
        $otherTransactions = DailyTransaction::where('tipe', 'pengeluaran')
            ->whereNotIn('kategori', ['Operasional', 'Maintenance'])
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        // 4. Budget Variance (Rencana vs Realisasi)
        $monthlyBudget = MonthlyBudget::where('periode', $selectedMonth)->with('allocations.jenisSchedule')->first();
        $budgetVariance = [];
        $activeCategories = JenisSchedule::where('status_aktif', true)->get();

        foreach ($activeCategories as $category) {
            $catId = $category->id;
            $catName = $category->nama;

            $allocation = $monthlyBudget ? $monthlyBudget->allocations->where('jenis_schedule_id', $catId)->first() : null;
            $limit = $allocation ? floatval($allocation->nominal_limit) : 0.0;

            $actualSchedule = floatval(Schedule::where('jenis_schedule_id', $catId)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('budget')
                ->sum(\DB::raw('COALESCE(budget_final, budget)')));

            $actualDaily = floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->where('kategori', $catName)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->sum('nominal'));

            $actualSpent = $actualSchedule + $actualDaily;
            $remaining = $limit - $actualSpent;
            $deviationPct = $limit > 0 ? (($actualSpent - $limit) / $limit) * 100 : ($actualSpent > 0 ? 100.0 : 0.0);

            $budgetVariance[] = [
                'kategori' => $catName,
                'limit' => $limit,
                'actual' => $actualSpent,
                'remaining' => $remaining,
                'deviation' => $deviationPct
            ];
        }

        // Handle other custom categories from transactions in variance list
        $processedNames = $activeCategories->pluck('nama')->toArray();
        $otherTxCategories = DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->whereNotIn('kategori', $processedNames)
            ->distinct()
            ->pluck('kategori')
            ->toArray();

        foreach ($otherTxCategories as $otherCat) {
            $actualSpent = floatval(DailyTransaction::where('tipe', 'pengeluaran')
                ->where('kategori', $otherCat)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->sum('nominal'));

            $budgetVariance[] = [
                'kategori' => $otherCat,
                'limit' => 0.0,
                'actual' => $actualSpent,
                'remaining' => -$actualSpent,
                'deviation' => 100.0
            ];
        }

        return view('admin.finance.pdf_report', compact(
            'selectedMonth',
            'totalGrossProfit',
            'totalExpenses',
            'netMargin',
            'totalCashPool',
            'healthStatus',
            'healthColor',
            'healthRating',
            'restockItems',
            'opsSchedules',
            'opsTransactions',
            'maintSchedules',
            'maintTransactions',
            'otherSchedules',
            'otherTransactions',
            'budgetVariance'
        ));
    }

    /**
     * Rencana Budgeting Bulanan (View & Form)
     */
    public function budgeting(Request $request)
    {
        $selectedMonth = $request->query('periode', Carbon::now()->format('Y-m'));
        $targetDate = Carbon::parse($selectedMonth . '-01');

        // 1. HITUNG SALDO KAS AKTUAL (Laba akumulatif sebelum periode terpilih)
        // Profit Inventory sebelum target bulan
        $profitTotal = 0;
        $items = InventoryChecking::where('status', 'aktif')
            ->whereDate('tanggal', '<', $targetDate)
            ->get();
        foreach ($items as $item) {
            $profitTotal += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }

        // Pemasukan DailyTransaction sebelum target bulan
        $profitTotal += floatval(DailyTransaction::where('tipe', 'pemasukan')
            ->whereDate('tanggal', '<', $targetDate)
            ->sum('nominal'));

        // Pengeluaran Schedule sebelum target bulan
        $expenseTotal = floatval(Schedule::whereNotNull('budget')
            ->whereDate('date', '<', $targetDate)
            ->sum(\DB::raw('COALESCE(budget_final, budget)')));

        // Pengeluaran DailyTransaction sebelum target bulan
        $expenseTotal += floatval(DailyTransaction::where('tipe', 'pengeluaran')
            ->whereDate('tanggal', '<', $targetDate)
            ->sum('nominal'));

        // Saldo Kas Kumulatif awal periode
        $calculatedCash = max(1000000000 + $profitTotal - $expenseTotal, 0);

        // 2. Ambil Kategori Jenis Schedule
        $categories = JenisSchedule::where('status_aktif', true)->get();

        // 3. Ambil data budgeting yang sudah tersimpan
        $savedBudgets = MonthlyBudget::with('allocations.jenisSchedule')
            ->orderBy('periode', 'desc')
            ->get();

        // 4. Ambil data budgeting periode ini jika ada untuk edit/pre-fill
        $currentBudget = MonthlyBudget::with('allocations')->where('periode', $selectedMonth)->first();
        $currentAllocations = $currentBudget ? $currentBudget->allocations->keyBy('jenis_schedule_id') : collect();

        return view('admin.finance.budgeting', compact(
            'selectedMonth',
            'calculatedCash',
            'categories',
            'savedBudgets',
            'currentBudget',
            'currentAllocations'
        ));
    }

    /**
     * Simpan Rencana Budgeting Bulanan
     */
    public function storeBudget(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
            'total_kas' => 'required|numeric|min:0',
            'alokasi_anggaran' => 'required|numeric|min:0',
            'allocations' => 'required|array',
            'allocations.*.jenis_schedule_id' => 'required|exists:jenis_schedules,id',
            'allocations.*.nominal_limit' => 'required|numeric|min:0',
            'allocations.*.catatan' => 'nullable|string'
        ]);

        $sumAllocations = collect($request->allocations)->sum('nominal_limit');
        if ($sumAllocations > $request->alokasi_anggaran) {
            return back()->withInput()->with('error', 'Jumlah total alokasi per kategori (Rp ' . number_format($sumAllocations, 0, ',', '.') . ') melebihi total rencana anggaran bulanan (Rp ' . number_format($request->alokasi_anggaran, 0, ',', '.') . ')!');
        }

        DB::transaction(function () use ($request) {
            // Hapus budget lama periode yang sama jika ada (untuk update/overwrite)
            $existing = MonthlyBudget::where('periode', $request->periode)->first();
            if ($existing) {
                $existing->delete();
            }

            // Simpan parent budget
            $budget = MonthlyBudget::create([
                'periode' => $request->periode,
                'total_kas' => $request->total_kas,
                'alokasi_anggaran' => $request->alokasi_anggaran,
                'catatan' => $request->catatan
            ]);

            // Simpan detail alokasi per kategori
            foreach ($request->allocations as $alloc) {
                if ($alloc['nominal_limit'] > 0) {
                    $budget->allocations()->create([
                        'jenis_schedule_id' => $alloc['jenis_schedule_id'],
                        'nominal_limit' => $alloc['nominal_limit'],
                        'catatan' => $alloc['catatan'] ?? null
                    ]);
                }
            }
        });

        return redirect()->route('admin.finance.budgeting')->with('success', 'Rencana anggaran bulanan berhasil disimpan!');
    }

    /**
     * Transaksi Harian Page (Listing & CSV operations)
     */
    public function transactions(Request $request)
    {
        $selectedMonth = $request->query('periode', Carbon::now()->format('Y-m'));
        $targetDateStart = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $targetDateEnd = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        // Ambil data transaksi harian di periode terpilih
        $transactions = DailyTransaction::whereBetween('tanggal', [$targetDateStart, $targetDateEnd])
            ->orderBy('tanggal', 'desc')
            ->get();

        // Hitung total pemasukan dan pengeluaran harian
        $totalPemasukan = $transactions->where('tipe', 'pemasukan')->sum('nominal');
        $totalPengeluaran = $transactions->where('tipe', 'pengeluaran')->sum('nominal');
        $netFlow = $totalPemasukan - $totalPengeluaran;

        return view('admin.finance.transactions', compact(
            'transactions',
            'selectedMonth',
            'totalPemasukan',
            'totalPengeluaran',
            'netFlow'
        ));
    }

    /**
     * Simpan Transaksi Harian Manual
     */
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        DailyTransaction::create([
            'tanggal' => $request->tanggal,
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'kategori' => $request->kategori,
            'nominal' => $request->nominal,
            'sumber' => 'manual',
            'keterangan' => $request->keterangan
        ]);

        $periode = Carbon::parse($request->tanggal)->format('Y-m');
        return redirect()->route('admin.finance.transactions', ['periode' => $periode])
            ->with('success', 'Transaksi harian berhasil dicatat secara manual!');
    }

    /**
     * Ekspor Transaksi ke CSV
     */
    public function exportTransactions(Request $request)
    {
        $selectedMonth = $request->query('periode', Carbon::now()->format('Y-m'));
        $targetDateStart = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $targetDateEnd = Carbon::parse($selectedMonth . '-01')->endOfMonth();

        $transactions = DailyTransaction::whereBetween('tanggal', [$targetDateStart, $targetDateEnd])
            ->orderBy('tanggal', 'asc')
            ->get();

        $csvFileName = 'Transaksi_Harian_' . $selectedMonth . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Tanggal', 'Nama Transaksi', 'Tipe', 'Kategori', 'Nominal (Rp)', 'Sumber Input', 'Keterangan'];

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->tanggal->format('Y-m-d'),
                    $tx->nama,
                    ucfirst($tx->tipe),
                    $tx->kategori,
                    $tx->nominal,
                    $tx->sumber,
                    $tx->keterangan
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Impor Transaksi dari CSV
     */
    public function importTransactions(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath();

        $csvData = array_map('str_getcsv', file($filePath));

        // Skip header row
        $header = array_shift($csvData);
        $importedCount = 0;

        DB::transaction(function () use ($csvData, &$importedCount) {
            foreach ($csvData as $row) {
                // Pastikan kolom lengkap
                if (count($row) >= 5) {
                    $tanggal = trim($row[0]);
                    $nama = trim($row[1]);
                    $tipe = strtolower(trim($row[2]));
                    $kategori = trim($row[3]);
                    $nominal = floatval(trim($row[4]));
                    $keterangan = isset($row[5]) ? trim($row[5]) : null;

                    if ($tanggal && $nama && in_array($tipe, ['pemasukan', 'pengeluaran']) && $nominal > 0) {
                        DailyTransaction::create([
                            'tanggal' => $tanggal,
                            'nama' => $nama,
                            'tipe' => $tipe,
                            'kategori' => $kategori,
                            'nominal' => $nominal,
                            'sumber' => 'csv',
                            'keterangan' => $keterangan
                        ]);
                        $importedCount++;
                    }
                }
            }
        });

        return back()->with('success', $importedCount . ' baris transaksi berhasil diimpor dari file CSV!');
    }

    /**
     * Endpoint API Kasir POS
     */
    public function apiStoreTransaction(Request $request)
    {
        // Token API statis dari config / env
        if (! $this->isValidCashierToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Token otorisasi API kasir tidak valid.'
            ], 401);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string|max:255',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string'
        ]);

        $tx = DailyTransaction::create([
            'tanggal' => $request->tanggal,
            'nama' => $request->nama,
            'tipe' => $request->tipe,
            'kategori' => $request->kategori,
            'nominal' => $request->nominal,
            'sumber' => 'api',
            'keterangan' => $request->keterangan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kasir shift berhasil disinkronisasi!',
            'data' => $tx
        ], 201);
    }

    /**
     * Get remaining budget allocation for a given period and category
     */
    public function getRemainingBudget(Request $request)
    {
        $request->validate([
            'periode' => 'required|string', // Format: Y-m (e.g. 2026-06)
            'category_id' => 'required|exists:jenis_schedules,id',
            'exclude_id' => 'nullable|integer'
        ]);

        $periode = $request->query('periode');
        $categoryId = $request->query('category_id');
        $excludeId = $request->query('exclude_id');

        // Find MonthlyBudget
        $budget = MonthlyBudget::where('periode', $periode)->first();
        $allocationLimit = 0;
        if ($budget) {
            $allocation = $budget->allocations()->where('jenis_schedule_id', $categoryId)->first();
            if ($allocation) {
                $allocationLimit = floatval($allocation->nominal_limit);
            }
        }

        // Calculate sum of schedules budgets in this period/category
        $start = Carbon::parse($periode . '-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($periode . '-01')->endOfMonth()->toDateString();

        $query = Schedule::where('jenis_schedule_id', $categoryId)
            ->whereBetween('date', [$start, $end])
            ->whereNotNull('budget');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $usedBudget = floatval($query->sum(\DB::raw('COALESCE(budget_final, budget)')));
        
        // Match category name
        $categoryName = JenisSchedule::find($categoryId)->nama ?? 'Umum';
        
        $usedDailyTx = floatval(DailyTransaction::where('tipe', 'pengeluaran')
            ->where('kategori', $categoryName)
            ->whereBetween('tanggal', [$start, $end])
            ->sum('nominal'));

        $totalUsed = $usedBudget + $usedDailyTx;
        $remaining = max($allocationLimit - $totalUsed, 0);

        return response()->json([
            'success' => true,
            'limit' => $allocationLimit,
            'used' => $totalUsed,
            'remaining' => $remaining
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use App\Models\ApiLibur;
use App\Models\InventoryChecking;
use App\Models\Supplier;
use App\Models\JenisBarang;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $expiredSoon = InventoryChecking::whereDate('expired_date', '<=', now()->addDays(7))->get();
        $ScheduleinWeek = Schedule::whereDate('date', '<=', now()->addDays(7))->get();
        $dataSchedule = Schedule::all();
        $totalSchedule = Schedule::count(); 
        $users = User::all();
        $jenisBarangs = JenisBarang::count();
        
        // FIX BUG: status is a boolean in database (1 or 0), not a string 'completed'
        $completedSchedule = Schedule::where('status', true)->count(); 
        
        $userCount = User::count(); 
        $dataApi = ApiLibur::pluck('name', 'date');
        $Inventory = InventoryChecking::count();
        $supplier = Supplier::count(); 

        // --- FINANCIAL ANALYTICS CALCULATIONS ---
        $targetDate = Carbon::now();
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();

        // 1. Current Month's Gross Profit (Income)
        $totalGrossProfit = 0;
        $itemsInMonth = InventoryChecking::where('status', 'aktif')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($itemsInMonth as $item) {
            $totalGrossProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $totalGrossProfit += floatval(\App\Models\DailyTransaction::where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        // 2. Current Month's Expense
        $totalExpenses = floatval(Schedule::whereNotNull('budget')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $totalExpenses += floatval(\App\Models\DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal'));

        // 3. All-time Cash Pool (starting with 1,000,000,000)
        $allTimeProfit = 0;
        foreach (InventoryChecking::where('status', 'aktif')->get() as $item) {
            $allTimeProfit += (floatval($item->harga_jual) - floatval($item->harga_pokok)) * intval($item->jumlah);
        }
        $allTimeProfit += floatval(\App\Models\DailyTransaction::where('tipe', 'pemasukan')->sum('nominal'));

        $allTimeExpense = floatval(Schedule::whereNotNull('budget')->sum(\DB::raw('COALESCE(budget_final, budget)')));
        $allTimeExpense += floatval(\App\Models\DailyTransaction::where('tipe', 'pengeluaran')->sum('nominal'));

        $totalCashPool = max(1000000000 + $allTimeProfit - $allTimeExpense, 0);

        // 4. Last 6 Months Trend (Income, Expense, Net Margin)
        $chartLabels = [];
        $chartProfits = [];
        $chartExpenses = [];
        $chartNetMargins = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $targetDate->copy()->subMonths($i);
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
            $profitM += floatval(\App\Models\DailyTransaction::where('tipe', 'pemasukan')
                ->whereBetween('tanggal', [$startM, $endM])
                ->sum('nominal'));

            // Pengeluaran schedule
            $expenseM = floatval(Schedule::whereNotNull('budget')
                ->whereBetween('date', [$startM, $endM])
                ->sum(\DB::raw('COALESCE(budget_final, budget)')));

            // Pengeluaran daily transactions
            $expenseM += floatval(\App\Models\DailyTransaction::where('tipe', 'pengeluaran')
                ->whereBetween('tanggal', [$startM, $endM])
                ->sum('nominal'));

            $netM = $profitM - $expenseM;

            $chartLabels[] = $mLabel;
            $chartProfits[] = $profitM;
            $chartExpenses[] = $expenseM;
            $chartNetMargins[] = $netM;
        }

        // 5. Category budgets for the current month
        $categoryBudgets = [];
        $schedulesWithBudget = Schedule::whereNotNull('budget')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($schedulesWithBudget as $s) {
            $categoryName = $s->jenisSchedule->nama ?? 'Umum';
            if (!isset($categoryBudgets[$categoryName])) {
                $categoryBudgets[$categoryName] = 0;
            }
            $categoryBudgets[$categoryName] += $s->effective_budget;
        }
        $txInMonth = \App\Models\DailyTransaction::where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($txInMonth as $tx) {
            $categoryName = $tx->kategori;
            if (!isset($categoryBudgets[$categoryName])) {
                $categoryBudgets[$categoryName] = 0;
            }
            $categoryBudgets[$categoryName] += floatval($tx->nominal);
        }
        $catLabels = array_keys($categoryBudgets);
        $catValues = array_values($categoryBudgets);

        return view('admin.dashboard', compact(
            'totalSchedule', 
            'completedSchedule', 
            'userCount', 
            'dataApi', 
            'users', 
            'Inventory', 
            'supplier', 
            'expiredSoon', 
            'jenisBarangs', 
            'ScheduleinWeek',
            'totalGrossProfit',
            'totalExpenses',
            'totalCashPool',
            'chartLabels',
            'chartProfits',
            'chartExpenses',
            'chartNetMargins',
            'catLabels',
            'catValues'
        ));
    }

    public function createSupplier()
    {
        return view('admin.createSupplier');
    }


}

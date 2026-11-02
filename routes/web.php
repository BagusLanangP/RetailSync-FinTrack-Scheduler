<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiLiburController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InventoryCheckingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;


Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Finance / Budgeting
    Route::get('/finance', [FinanceController::class, 'index'])->name('admin.finance.index');
    Route::get('/finance/export-pdf', [FinanceController::class, 'exportPdf'])->name('admin.finance.exportPdf');
    
    // Budgeting Planner
    Route::get('/finance/budgeting', [FinanceController::class, 'budgeting'])->name('admin.finance.budgeting');
    Route::post('/finance/budgeting', [FinanceController::class, 'storeBudget'])->name('admin.finance.storeBudget');
    Route::get('/finance/remaining-budget', [FinanceController::class, 'getRemainingBudget'])->name('admin.finance.remainingBudget');
    
    // Daily Transactions Log
    Route::get('/finance/transactions', [FinanceController::class, 'transactions'])->name('admin.finance.transactions');
    Route::post('/finance/transactions/store', [FinanceController::class, 'storeTransaction'])->name('admin.finance.storeTransaction');
    Route::get('/finance/transactions/export', [FinanceController::class, 'exportTransactions'])->name('admin.finance.exportTransactions');
    Route::post('/finance/transactions/import', [FinanceController::class, 'importTransactions'])->name('admin.finance.importTransactions');

    //dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // User Management (restricted to Super Admin only)
    Route::middleware(['super_admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        // Compatibility routes for existing links
        Route::get('/create-user', [UserController::class, 'create'])->name('admin.create-user');
        Route::post('/store-user', [UserController::class, 'store'])->name('admin.store-user');
    });


    // Inventory
    Route::get('/inventory', [InventoryCheckingController::class, 'index'])->name('inventory_index');
    Route::get('/inventory-checkings/create', [InventoryCheckingController::class, 'create'])->name('inventory_checkings.create');
    Route::get('/inventory/edit/{id}', [InventoryCheckingController::class, 'edit'])->name('inventory.edit');
    Route::post('/store-inventory', [InventoryCheckingController::class, 'store'])->name('admin.store-inventory');
    Route::put('/inventory/update/{id}', [InventoryCheckingController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/delete/{id}', [InventoryCheckingController::class, 'destroy'])->name('inventory.destroy');

    // Supplier
    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier_index');
    Route::get('/create-supplier', [SupplierController::class, 'create'])->name('admin.create-supplier');
    Route::post('/store-supplier', [SupplierController::class, 'store'])->name('admin.store-supplier');
    Route::get('/supplier/edit/{id}', [SupplierController::class, 'edit'])->name('supplier.edit');
    Route::get('/supplier/show/{id}', [SupplierController::class, 'show'])->name('supplier.show');
    Route::put('/supplier/update/{id}', [SupplierController::class, 'update'])->name('supplier.update');
    Route::patch('/supplier/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('supplier.toggleStatus');
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'destroy'])->name('supplier.destroy');


    //Schedule
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('admin.schedule.index');
    Route::get('/schedule-create', [ScheduleController::class, 'create'])->name('schedule.create');
    Route::post('/schedule/store', [ScheduleController::class, 'store'])->name('schedule.store');
    Route::post('/schedule/{id}/toggle-status', [ScheduleController::class, 'toggleStatus'])->name('schedule.toggleStatus');
    Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    Route::post('/kirim/email/schedule/{id}', [EmailController::class, 'kirimScheduleEmail'])->name('email.schedule');
    Route::post('/kirim/email/inventory/{id}', [EmailController::class, 'kirimInventoryEmail'])->name('email.inventory');
    Route::get('/schedule/{id}/edit', [ScheduleController::class, 'edit'])->name('schedule.edit');
    Route::put('/schedule/{id}', [ScheduleController::class, 'update'])->name('schedule.update');
    Route::post('/schedule/{id}/update-date', [ScheduleController::class, 'updateDate'])->name('schedule.updateDate');

    // HRM & Payroll
    Route::prefix('hrm')->group(function () {
        if (!env('HRM_ENABLED', false)) {
            Route::any('/attendance', function() { return view('admin.hrm.development'); })->name('admin.attendance.index');
            Route::any('/employees', function() { return view('admin.hrm.development'); })->name('admin.employees.index');
            Route::any('/payroll', function() { return view('admin.hrm.development'); })->name('admin.payroll.index');
            Route::any('{any}', function() { return view('admin.hrm.development'); })->where('any', '.*');
        } else {
            // Attendance - accessible to staff & admin
            Route::get('/attendance', [AttendanceController::class, 'index'])->name('admin.attendance.index');
            Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('admin.attendance.checkIn');
            Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('admin.attendance.checkOut');

            // Employees & Payroll - super admin only
            Route::middleware(['super_admin'])->group(function () {
                // Employees CRUD
                Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
                Route::get('/employees/create', [EmployeeController::class, 'create'])->name('admin.employees.create');
                Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
                Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit');
                Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('admin.employees.update');
                Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

                // Payroll CRUD & Actions
                Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
                Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('admin.payroll.generate');
                Route::put('/payroll/{id}/update-details', [PayrollController::class, 'updateDetails'])->name('admin.payroll.updateDetails');
                Route::post('/payroll/{id}/pay', [PayrollController::class, 'pay'])->name('admin.payroll.pay');
                Route::delete('/payroll/{id}', [PayrollController::class, 'destroy'])->name('admin.payroll.destroy');
            });
        }
    });
});

Route::get('/', function () {
    $totalSuppliers = \App\Models\Supplier::count();
    $totalInventory = \App\Models\InventoryChecking::count();
    return view('index', compact('totalSuppliers', 'totalInventory'));
});
Route::middleware(['auth'])->group(function () {
    Route::get('/fetcholidays', [ApiLiburController::class, 'fetchHolidays']);
    Route::get('/fetch', [ApiLiburController::class, 'index']);
});

Route::get('/schedule', [ScheduleController::class, 'index'])->name('public.schedule.index');

Route::get('/schedule-create', [ScheduleController::class, 'create'])->name('public.schedule.create');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Cashier POS API integration
Route::post('/api/finance/daily-transactions', [FinanceController::class, 'apiStoreTransaction']);
Route::post('/api/inventory/sync-stocks', [InventoryCheckingController::class, 'apiSyncStocks']);

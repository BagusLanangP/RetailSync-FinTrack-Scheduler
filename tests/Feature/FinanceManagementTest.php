<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JenisSchedule;
use App\Models\MonthlyBudget;
use App\Models\DailyTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private JenisSchedule $operasionalCategory;
    private JenisSchedule $gajiCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // Create standard categories (JenisSchedule)
        $this->operasionalCategory = new JenisSchedule();
        $this->operasionalCategory->nama = 'Operasional';
        $this->operasionalCategory->status_aktif = true;
        $this->operasionalCategory->save();

        $this->gajiCategory = new JenisSchedule();
        $this->gajiCategory->nama = 'Gaji Karyawan';
        $this->gajiCategory->status_aktif = true;
        $this->gajiCategory->save();
    }

    public function test_guest_cannot_access_finance_pages()
    {
        $this->get(route('admin.finance.index'))->assertRedirect(route('login'));
        $this->get(route('admin.finance.budgeting'))->assertRedirect(route('login'));
        $this->get(route('admin.finance.transactions'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_finance_pages()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.finance.index');

        $response = $this->actingAs($this->admin)->get(route('admin.finance.budgeting'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.finance.budgeting');

        $response = $this->actingAs($this->admin)->get(route('admin.finance.transactions'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.finance.transactions');
    }

    public function test_admin_can_store_monthly_budget()
    {
        $period = Carbon::now()->format('Y-m');

        $response = $this->actingAs($this->admin)->post(route('admin.finance.storeBudget'), [
            'periode' => $period,
            'total_kas' => 10000000,
            'alokasi_anggaran' => 5000000,
            'allocations' => [
                [
                    'jenis_schedule_id' => $this->operasionalCategory->id,
                    'nominal_limit' => 3000000,
                    'catatan' => 'Untuk bensin dan listrik'
                ],
                [
                    'jenis_schedule_id' => $this->gajiCategory->id,
                    'nominal_limit' => 2000000,
                    'catatan' => 'Staff bonus'
                ]
            ]
        ]);

        $response->assertRedirect(route('admin.finance.budgeting'));
        $response->assertSessionHas('success', 'Rencana anggaran bulanan berhasil disimpan!');

        $this->assertDatabaseHas('monthly_budgets', [
            'periode' => $period,
            'total_kas' => 10000000,
            'alokasi_anggaran' => 5000000
        ]);

        $this->assertDatabaseHas('budget_allocations', [
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 3000000,
            'catatan' => 'Untuk bensin dan listrik'
        ]);
    }

    public function test_cannot_store_monthly_budget_exceeding_total_allocation()
    {
        $period = Carbon::now()->format('Y-m');

        $response = $this->actingAs($this->admin)->post(route('admin.finance.storeBudget'), [
            'periode' => $period,
            'total_kas' => 10000000,
            'alokasi_anggaran' => 4000000, // Budget ceiling is 4,000,000
            'allocations' => [
                [
                    'jenis_schedule_id' => $this->operasionalCategory->id,
                    'nominal_limit' => 3000000,
                    'catatan' => 'Test'
                ],
                [
                    'jenis_schedule_id' => $this->gajiCategory->id,
                    'nominal_limit' => 2000000, // Total allocated is 5,000,000 (exceeds 4,000,000)
                    'catatan' => 'Test'
                ]
            ]
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('monthly_budgets', [
            'periode' => $period
        ]);
    }

    public function test_admin_can_store_manual_transaction()
    {
        $today = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->post(route('admin.finance.storeTransaction'), [
            'tanggal' => $today,
            'nama' => 'Pembelian Kertas A4',
            'tipe' => 'pengeluaran',
            'kategori' => 'Operasional',
            'nominal' => 75000,
            'keterangan' => 'Belanja ATK bulanan'
        ]);

        $period = Carbon::now()->format('Y-m');
        $response->assertRedirect(route('admin.finance.transactions', ['periode' => $period]));
        $response->assertSessionHas('success', 'Transaksi harian berhasil dicatat secara manual!');

        $this->assertDatabaseHas('daily_transactions', [
            'tanggal' => $today . ' 00:00:00',
            'nama' => 'Pembelian Kertas A4',
            'tipe' => 'pengeluaran',
            'kategori' => 'Operasional',
            'nominal' => 75000,
            'sumber' => 'manual',
            'keterangan' => 'Belanja ATK bulanan'
        ]);
    }

    public function test_admin_can_export_transactions_csv()
    {
        // Add mock transaction
        DailyTransaction::create([
            'tanggal' => Carbon::now()->format('Y-m-d'),
            'nama' => 'Penjualan Hari Ini',
            'tipe' => 'pemasukan',
            'kategori' => 'Retail',
            'nominal' => 500000,
            'sumber' => 'manual'
        ]);

        $period = Carbon::now()->format('Y-m');
        $response = $this->actingAs($this->admin)->get(route('admin.finance.exportTransactions', ['periode' => $period]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Penjualan Hari Ini', $response->streamedContent());
    }

    public function test_admin_can_import_transactions_csv()
    {
        $csvContent = "Tanggal,Nama Transaksi,Tipe,Kategori,Nominal (Rp),Sumber Input,Keterangan\n" .
                      "2026-06-12,Penjualan POS Shift A,pemasukan,Retail,1500000,csv,Kasir A\n" .
                      "2026-06-12,Iuran Sampah,pengeluaran,Operasional,50000,csv,Kebersihan\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('admin.finance.importTransactions'), [
            'csv_file' => $file
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 baris transaksi berhasil diimpor dari file CSV!');

        $this->assertDatabaseHas('daily_transactions', [
            'nama' => 'Penjualan POS Shift A',
            'nominal' => 1500000,
            'sumber' => 'csv'
        ]);

        $this->assertDatabaseHas('daily_transactions', [
            'nama' => 'Iuran Sampah',
            'nominal' => 50000,
            'sumber' => 'csv'
        ]);
    }

    public function test_cashier_pos_api_requires_valid_token()
    {
        $response = $this->postJson('/api/finance/daily-transactions', [
            'tanggal' => '2026-06-12',
            'nama' => 'Shift Malam POS',
            'tipe' => 'pemasukan',
            'kategori' => 'Retail',
            'nominal' => 1200000
        ], [
            'X-API-TOKEN' => 'wrong_token'
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Token otorisasi API kasir tidak valid.'
        ]);
    }

    public function test_cashier_pos_api_rejects_all_requests_when_token_not_configured()
    {
        config(['services.cashier.token' => null]);

        $response = $this->postJson('/api/finance/daily-transactions', [
            'tanggal' => '2026-06-12',
            'nama' => 'Shift Malam POS',
            'tipe' => 'pemasukan',
            'kategori' => 'Retail',
            'nominal' => 1200000
        ], [
            'X-API-TOKEN' => ''
        ]);

        $response->assertStatus(401);
    }

    public function test_cashier_pos_api_stores_transaction_with_valid_token()
    {
        // Token diambil dari CASHIER_API_TOKEN (diset di phpunit.xml)
        $response = $this->postJson('/api/finance/daily-transactions', [
            'tanggal' => '2026-06-12',
            'nama' => 'Shift Malam POS',
            'tipe' => 'pemasukan',
            'kategori' => 'Retail',
            'nominal' => 1200000,
            'keterangan' => 'Keterangan POS'
        ], [
            'X-API-TOKEN' => 'kasir_secret_token'
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Transaksi kasir shift berhasil disinkronisasi!'
        ]);

        $this->assertDatabaseHas('daily_transactions', [
            'nama' => 'Shift Malam POS',
            'nominal' => 1200000,
            'sumber' => 'api'
        ]);
    }

    public function test_budgeting_view_prefills_existing_budget_data()
    {
        $period = Carbon::now()->format('Y-m');

        // Create an existing budget
        $budget = MonthlyBudget::create([
            'periode' => $period,
            'total_kas' => 5000000,
            'alokasi_anggaran' => 2000000,
            'catatan' => 'Original Budget'
        ]);

        $allocation = $budget->allocations()->create([
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 1500000,
            'catatan' => 'Listrik'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.budgeting', ['periode' => $period]));
        $response->assertStatus(200);
        $response->assertViewHas('currentBudget');
        $response->assertViewHas('currentAllocations');
        
        $this->assertEquals($budget->id, $response->viewData('currentBudget')->id);
        $this->assertTrue($response->viewData('currentAllocations')->has($this->operasionalCategory->id));
    }

    public function test_admin_can_edit_existing_monthly_budget()
    {
        $period = Carbon::now()->format('Y-m');

        // Create an existing budget
        $budget = MonthlyBudget::create([
            'periode' => $period,
            'total_kas' => 5000000,
            'alokasi_anggaran' => 2000000,
            'catatan' => 'Original Budget'
        ]);

        $budget->allocations()->create([
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 1500000,
            'catatan' => 'Listrik'
        ]);

        // Submit new allocations to edit
        $response = $this->actingAs($this->admin)->post(route('admin.finance.storeBudget'), [
            'periode' => $period,
            'total_kas' => 5000000,
            'alokasi_anggaran' => 3000000, // Budget updated to 3,000,000
            'allocations' => [
                [
                    'jenis_schedule_id' => $this->operasionalCategory->id,
                    'nominal_limit' => 2000000, // Allocation updated to 2,000,000
                    'catatan' => 'Listrik Baru'
                ],
                [
                    'jenis_schedule_id' => $this->gajiCategory->id,
                    'nominal_limit' => 1000000,
                    'catatan' => 'Bonus Staff'
                ]
            ]
        ]);

        $response->assertRedirect(route('admin.finance.budgeting'));
        $response->assertSessionHas('success', 'Rencana anggaran bulanan berhasil disimpan!');

        $this->assertDatabaseHas('monthly_budgets', [
            'periode' => $period,
            'alokasi_anggaran' => 3000000
        ]);

        $this->assertDatabaseHas('budget_allocations', [
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 2000000,
            'catatan' => 'Listrik Baru'
        ]);

        // Assert old budget and old allocations are removed (due to cascade & storeBudget logic)
        $this->assertEquals(1, MonthlyBudget::where('periode', $period)->count());
        $this->assertDatabaseMissing('budget_allocations', [
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 1500000,
            'catatan' => 'Listrik'
        ]);
    }

    public function test_schedule_index_view_contains_budget_widget_data()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.schedule.index'));
        $response->assertStatus(200);
        $response->assertViewHas('budgetWidgetData');
        
        $widgetData = $response->viewData('budgetWidgetData');
        $this->assertIsArray($widgetData);
        $this->assertCount(2, $widgetData); // operasionalCategory and gajiCategory
    }

    public function test_finance_index_filters_by_specific_period()
    {
        $period = '2026-05';
        $response = $this->actingAs($this->admin)->get(route('admin.finance.index', ['periode' => $period]));
        
        $response->assertStatus(200);
        $response->assertViewHas('selectedMonth', $period);
    }

    public function test_finance_index_calculates_correct_period_data()
    {
        $period = '2026-05';

        // Create a transaction in 2026-05
        DailyTransaction::create([
            'tanggal' => '2026-05-10 00:00:00',
            'nama' => 'Pemasukan Mei',
            'tipe' => 'pemasukan',
            'kategori' => 'Operasional',
            'nominal' => 2000000,
            'sumber' => 'manual'
        ]);

        // Create a transaction in 2026-06 (different period)
        DailyTransaction::create([
            'tanggal' => '2026-06-10 00:00:00',
            'nama' => 'Pemasukan Juni',
            'tipe' => 'pemasukan',
            'kategori' => 'Operasional',
            'nominal' => 5000000,
            'sumber' => 'manual'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finance.index', ['periode' => $period]));
        
        $response->assertStatus(200);
        $response->assertViewHas('totalGrossProfit', 2000000.0); // Only should sum 2026-05 transaction
    }

    public function test_finance_index_calculates_budget_variance()
    {
        $period = '2026-06';

        // 1. Create a monthly budget and allocation for $this->operasionalCategory
        $budget = MonthlyBudget::create([
            'periode' => $period,
            'total_kas' => 10000000,
            'alokasi_anggaran' => 5000000,
            'catatan' => 'Test Budget'
        ]);

        $budget->allocations()->create([
            'jenis_schedule_id' => $this->operasionalCategory->id,
            'nominal_limit' => 3000000,
            'catatan' => 'Batas Operasional'
        ]);

        // 2. Create actual daily transaction expenses under the category name "Operasional"
        DailyTransaction::create([
            'tanggal' => '2026-06-15 10:00:00',
            'nama' => 'Beli ATK',
            'tipe' => 'pengeluaran',
            'kategori' => 'Operasional',
            'nominal' => 1000000,
            'sumber' => 'manual'
        ]);

        // 3. Make request
        $response = $this->actingAs($this->admin)->get(route('admin.finance.index', ['periode' => $period]));

        $response->assertStatus(200);
        $response->assertViewHas('budgetVariance');

        $variance = $response->viewData('budgetVariance');
        $this->assertIsArray($variance);

        // Find the "Operasional" variance item
        $operasionalVariance = collect($variance)->where('kategori', 'Operasional')->first();
        $this->assertNotNull($operasionalVariance);
        $this->assertEquals(3000000.0, $operasionalVariance['limit']);
        $this->assertEquals(1000000.0, $operasionalVariance['actual']);
        $this->assertEquals(2000000.0, $operasionalVariance['remaining']);
        $this->assertEqualsWithDelta(-66.666666666667, $operasionalVariance['deviation'], 0.0001);
    }

    public function test_admin_can_access_finance_pdf_report()
    {
        $period = '2026-06';
        
        $response = $this->actingAs($this->admin)->get(route('admin.finance.exportPdf', ['periode' => $period]));
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.finance.pdf_report');
        $response->assertViewHas('selectedMonth', $period);
        $response->assertViewHas('budgetVariance');
        $response->assertViewHas('restockItems');
        $response->assertViewHas('opsSchedules');
        $response->assertViewHas('opsTransactions');
        $response->assertViewHas('maintSchedules');
        $response->assertViewHas('maintTransactions');
    }
}



<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Schedule;
use App\Models\JenisSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_schedules()
    {
        $response = $this->get(route('admin.schedule.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_can_access_public_schedules()
    {
        $response = $this->get(route('public.schedule.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_access_admin_schedules_list()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.schedule.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_create_schedule_with_creator_and_details()
    {
        $user = User::factory()->create();
        
        $jenis = new JenisSchedule();
        $jenis->nama = 'Kategori A';
        $jenis->save();

        $response = $this->actingAs($user)->post(route('schedule.store'), [
            'name' => 'Meeting Kerja',
            'jenis_schedule_id' => $jenis->id,
            'date' => '2026-06-10',
            'note' => 'Catatan meeting',
            'budget' => 150000,
            'berulang' => '0',
            'reminder_date' => '2026-06-10'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'name' => 'Meeting Kerja',
            'jenis_schedule_id' => $jenis->id,
            'budget' => 150000,
            'created_by' => $user->id,
        ]);
    }

    public function test_user_can_toggle_schedule_status_and_tracks_completion_date()
    {
        $user = User::factory()->create();
        
        $jenis = new JenisSchedule();
        $jenis->nama = 'Kategori A';
        $jenis->save();

        $schedule = Schedule::create([
            'name' => 'Jadwal 1',
            'jenis_schedule_id' => $jenis->id,
            'date' => '2026-06-10',
            'reminder_date' => '2026-06-10',
            'berulang' => false,
            'status' => false,
            'created_by' => $user->id
        ]);

        $response = $this->actingAs($user)->post("/admin/schedule/{$schedule->id}/toggle-status");

        $response->assertJson(['success' => true, 'status' => true]);
        
        $schedule->refresh();
        $this->assertTrue((bool)$schedule->status);
        $this->assertNotNull($schedule->completed_at);

        // Toggle back to false
        $response = $this->actingAs($user)->post("/admin/schedule/{$schedule->id}/toggle-status");
        $response->assertJson(['success' => true, 'status' => false]);
        
        $schedule->refresh();
        $this->assertFalse((bool)$schedule->status);
        $this->assertNull($schedule->completed_at);
    }

    public function test_guest_cannot_fetch_holidays_or_send_notification_routes()
    {
        $this->get('/fetcholidays')->assertRedirect(route('login'));
        $this->get('/fetch')->assertRedirect(route('login'));
        $this->get('/kirim-email')->assertNotFound();
    }

    public function test_fetch_holidays_endpoint_returns_json()
    {
        Http::fake([
            'api-hari-libur.vercel.app/*' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'date' => '2026-08-17',
                        'description' => 'Hari Kemerdekaan RI'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs(User::factory()->create())->get('/fetcholidays');

        $response->assertJson([
            'success' => true,
            'message' => 'Hari libur berhasil disinkronisasi!'
        ]);
        
        $this->assertDatabaseHas('api_liburs', [
            'date' => '2026-08-17',
            'name' => 'Hari Kemerdekaan RI'
        ]);
    }

    public function test_inventory_checking_expiry_autosyncs_to_schedules()
    {
        $user = User::factory()->create();
        
        $jenisBarang = new \App\Models\JenisBarang();
        $jenisBarang->name = 'Kategori Barang A';
        $jenisBarang->save();

        $supplier = \App\Models\Supplier::create([
            'nama' => 'Supplier A',
            'status_aktif' => true,
            'nomor_whatsapp' => '081234567890',
            'dari_tanggal' => '2026-06-01',
            'jenis_barang_id' => $jenisBarang->id,
        ]);

        // 1. Assert automatic creation on store
        $response = $this->actingAs($user)->post(route('admin.store-inventory'), [
            'nama' => 'Obat Sakit Kepala',
            'jenis_barang_id' => $jenisBarang->id,
            'supplier_id' => $supplier->id,
            'tanggal' => '2026-06-01',
            'expired_date' => '2026-07-10',
            'jumlah' => 100,
            'total_harga' => 100000,
            'harga_pokok' => 1000,
            'harga_jual' => 1500,
            'keterangan' => 'Stok obat baru'
        ]);

        $response->assertRedirect();
        
        $item = \App\Models\InventoryChecking::where('nama', 'Obat Sakit Kepala')->first();
        $this->assertNotNull($item);
        
        $this->assertDatabaseHas('schedules', [
            'inventory_checking_id' => $item->id,
            'name' => 'Kedaluwarsa: Obat Sakit Kepala (100 Unit)',
            'date' => '2026-06-10', // 2026-07-10 minus 30 days
            'reminder_date' => '2026-07-10',
        ]);

        // 2. Assert update syncs changes
        $response = $this->actingAs($user)->put(route('inventory.update', $item->id), [
            'nama' => 'Obat Sakit Kepala Premium',
            'jenis_barang_id' => $jenisBarang->id,
            'supplier_id' => $supplier->id,
            'tanggal' => '2026-06-01',
            'expired_date' => '2026-08-20',
            'jumlah' => 50,
            'total_harga' => 50000,
            'harga_pokok' => 1000,
            'harga_jual' => 1500,
            'keterangan' => 'Stok obat baru',
            'status' => 'aktif'
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('schedules', [
            'inventory_checking_id' => $item->id,
            'name' => 'Kedaluwarsa: Obat Sakit Kepala Premium (50 Unit)',
            'date' => '2026-07-21', // 2026-08-20 minus 30 days
            'reminder_date' => '2026-08-20',
        ]);

        // 3. Assert delete cascades to schedule
        $response = $this->actingAs($user)->delete(route('inventory.destroy', $item->id));
        $response->assertRedirect();
        
        $this->assertDatabaseMissing('schedules', [
            'inventory_checking_id' => $item->id,
        ]);
    }

    public function test_user_can_update_schedule_date_via_ajax()
    {
        $user = User::factory()->create();
        
        $jenis = new JenisSchedule();
        $jenis->nama = 'Kategori A';
        $jenis->save();

        $schedule = Schedule::create([
            'name' => 'Jadwal 1',
            'jenis_schedule_id' => $jenis->id,
            'date' => '2026-06-10',
            'reminder_date' => '2026-06-12',
            'berulang' => false,
            'status' => false,
            'created_by' => $user->id
        ]);

        $response = $this->actingAs($user)->post("/admin/schedule/{$schedule->id}/update-date", [
            'date' => '2026-06-15'
        ]);

        $response->assertJson(['success' => true]);
        
        $schedule->refresh();
        $this->assertEquals('2026-06-15', $schedule->date);
        
        // Reminder date should be updated if it becomes less than the execution date
        $this->assertEquals('2026-06-15', $schedule->reminder_date);
    }

    public function test_schedule_budget_defaults_to_zero_estimate_when_not_given()
    {
        $user = User::factory()->create();
        $jenis = $this->makeJenis('Belanja');

        $this->actingAs($user)->post(route('schedule.store'), [
            'name' => 'Belanja Barang A',
            'jenis_schedule_id' => $jenis->id,
            'date' => '2026-11-10',
            'berulang' => '0',
            'reminder_date' => '2026-11-10',
        ])->assertRedirect();

        $s = Schedule::where('name', 'Belanja Barang A')->first();
        $this->assertEquals(0, $s->budget);
        $this->assertNull($s->budget_final);
        $this->assertTrue($s->is_estimate);
        $this->assertEquals(0, $s->effective_budget);
    }

    public function test_final_budget_overrides_estimate_and_keeps_estimate()
    {
        $user = User::factory()->create();
        $jenis = $this->makeJenis('Belanja');
        $s = Schedule::create([
            'name' => 'Belanja B', 'jenis_schedule_id' => $jenis->id, 'date' => '2026-11-10',
            'reminder_date' => '2026-11-10', 'berulang' => false, 'budget' => 100000,
        ]);
        $this->assertTrue($s->is_estimate);
        $this->assertEquals(100000, $s->effective_budget);

        $this->actingAs($user)->put(route('schedule.update', $s->id), [
            'name' => 'Belanja B', 'jenis_schedule_id' => $jenis->id, 'date' => '2026-11-10',
            'reminder_date' => '2026-11-10', 'berulang' => '0', 'budget' => 100000, 'budget_final' => 120000,
        ])->assertRedirect();

        $s->refresh();
        $this->assertFalse($s->is_estimate);
        $this->assertEquals(120000, $s->effective_budget);
        $this->assertEquals(100000, $s->budget);
        $this->actingAs($user)->get(route('admin.schedule.index'))->assertOk()->assertSee('Rp120.000')->assertDontSee('inline-block mt-1 px-2 py-0.5', false);
    }

    public function test_estimate_badge_is_shown_in_schedule_list()
    {
        $user = User::factory()->create();
        $jenis = $this->makeJenis('Belanja');
        Schedule::create([
            'name' => 'Belanja C', 'jenis_schedule_id' => $jenis->id, 'date' => '2026-11-10',
            'reminder_date' => '2026-11-10', 'berulang' => false, 'budget' => 0,
        ]);
        $this->actingAs($user)->get(route('admin.schedule.index'))->assertOk()->assertSee('inline-block mt-1 px-2 py-0.5', false);
    }

    private function makeJenis(string $nama): JenisSchedule
    {
        $j = new JenisSchedule();
        $j->nama = $nama;
        $j->save();
        return $j;
    }
}


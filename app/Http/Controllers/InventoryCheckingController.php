<?php

// app/Http/Controllers/InventoryCheckingController.php

namespace App\Http\Controllers;
use App\Models\InventoryChecking;
use App\Models\JenisBarang;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Carbon\Carbon;


class InventoryCheckingController extends Controller
{
    // Tampilkan data peringatan inventaris (Dashboard Alerts)
    public function index(Request $request)
    {
        // 1. STOK KRITIS: Jumlah di bawah atau sama dengan batas minimal (min_stock)
        $lowStockItems = InventoryChecking::with(['supplier', 'jenisBarang'])
            ->whereColumn('jumlah', '<=', 'min_stock')
            ->get();

        // 2. MENDEKATI KEDALUWARSA: Tanggal kedaluwarsa dalam 30 hari ke depan, dan masih ada stok (jumlah > 0)
        $expiringSoonItems = InventoryChecking::with(['supplier', 'jenisBarang'])
            ->whereNotNull('expired_date')
            ->where('jumlah', '>', 0)
            ->where('status', '!=', 'ditarik')
            ->whereDate('expired_date', '<=', Carbon::now()->addMonth())
            ->orderBy('expired_date', 'asc')
            ->get();

        return view('admin.inventory.index', compact('lowStockItems', 'expiringSoonItems'));
    }

    // Tampilkan form create (Deprecated)
    public function create()
    {
        return redirect()->route('inventory_index')->with('warning', 'Penambahan barang dilakukan secara otomatis melalui sinkronisasi API Kasir POS.');
    }

    // Simpan data baru (Deprecated in UI - retained for API compatibility and tests)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'jenis_barang_id' => 'required|exists:jenis_barangs,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'tanggal' => 'required|date',
            'expired_date' => 'nullable|date',
            'jumlah' => 'required|integer',
            'total_harga' => 'required|numeric',
            'harga_pokok' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'keterangan' => 'nullable|string',
        ]);

        $validated['status'] = 'belum_diproses';

        $item = InventoryChecking::create($validated);
        
        // Sinkronisasi otomatis ke agenda schedule
        $this->syncExpirySchedule($item);

        return redirect()->route('inventory_index')->with('success', 'Data berhasil ditambahkan!');
    }

    // Edit form (Deprecated)
    public function edit($id)
    {
        return redirect()->route('inventory_index')->with('warning', 'Pembaruan data barang dilakukan secara otomatis melalui sinkronisasi API Kasir POS.');
    }

    // Update data (Deprecated in UI - retained for API compatibility and tests)
    public function update(Request $request, $id)
    {
        $item = InventoryChecking::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string',
            'jenis_barang_id' => 'required|exists:jenis_barangs,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'tanggal' => 'required|date',
            'expired_date' => 'nullable|date',
            'jumlah' => 'required|integer',
            'total_harga' => 'required|numeric',
            'harga_pokok' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:belum_diproses,aktif,ditarik',
        ]);

        $item->update($validated);
        
        // Sinkronisasi otomatis ke agenda schedule
        $this->syncExpirySchedule($item);

        return redirect()->route('inventory_index')->with('success', 'Data berhasil diubah!');
    }

    // Hapus data
    public function destroy($id)
    {
        $item = InventoryChecking::findOrFail($id);
        
        // Hapus agenda schedule yang terkait terlebih dahulu
        \App\Models\Schedule::where('inventory_checking_id', $item->id)->delete();
        
        $item->delete();

        return redirect()->route('inventory_index')->with('success', 'Data berhasil dihapus!');
    }

    // Endpoint Sinkronisasi Stok dari Kasir POS
    public function apiSyncStocks(Request $request)
    {
        if (! $this->isValidCashierToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Token otorisasi API kasir tidak valid.'
            ], 401);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.sku' => 'required|string',
            'items.*.nama' => 'required|string',
            'items.*.kategori' => 'required|string',
            'items.*.supplier_nama' => 'nullable|string',
            'items.*.jumlah' => 'required|integer|min:0',
            'items.*.min_stock' => 'nullable|integer|min:0',
            'items.*.harga_pokok' => 'required|numeric|min:0',
            'items.*.harga_jual' => 'required|numeric|min:0',
            'items.*.expired_date' => 'nullable|date',
            'items.*.keterangan' => 'nullable|string'
        ]);

        $syncedCount = 0;

        \DB::transaction(function () use ($request, &$syncedCount) {
            foreach ($request->items as $itemData) {
                // 1. Cari atau buat JenisBarang
                $jenisBarang = JenisBarang::firstOrCreate([
                    'name' => $itemData['kategori']
                ]);

                // 2. Cari atau buat Supplier
                $supplierName = $itemData['supplier_nama'] ?: 'Supplier POS Umum';
                $supplier = Supplier::firstOrCreate([
                    'nama' => $supplierName
                ], [
                    'nomor_whatsapp' => '081234567890',
                    'email' => 'supplier@example.com',
                    'dari_tanggal' => Carbon::now()->toDateString(),
                    'jenis_barang_id' => $jenisBarang->id,
                    'status_aktif' => true,
                    'alamat' => '-',
                    'pic' => 'POS Sync'
                ]);

                $minStock = isset($itemData['min_stock']) ? intval($itemData['min_stock']) : 10;
                $expiredDate = isset($itemData['expired_date']) ? $itemData['expired_date'] : null;
                $totalHarga = floatval($itemData['harga_pokok']) * intval($itemData['jumlah']);

                $status = 'aktif';
                if (intval($itemData['jumlah']) === 0) {
                    $status = 'belum_diproses';
                }

                // 3. Update atau create item berdasarkan SKU
                $inventory = InventoryChecking::updateOrCreate(
                    ['sku' => $itemData['sku']],
                    [
                        'nama' => $itemData['nama'],
                        'jenis_barang_id' => $jenisBarang->id,
                        'supplier_id' => $supplier->id,
                        'tanggal' => Carbon::now()->toDateString(),
                        'expired_date' => $expiredDate,
                        'jumlah' => intval($itemData['jumlah']),
                        'min_stock' => $minStock,
                        'harga_pokok' => floatval($itemData['harga_pokok']),
                        'total_harga' => $totalHarga,
                        'harga_jual' => floatval($itemData['harga_jual']),
                        'keterangan' => $itemData['keterangan'] ?? 'Sync from POS API',
                        'status' => $status
                    ]
                );

                // 4. Sinkronisasi pengingat kedaluwarsa ke agenda schedules
                $this->syncExpirySchedule($inventory);

                $syncedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => $syncedCount . ' data stok barang berhasil disinkronkan!',
        ], 200);
    }

    // Helper sinkronisasi hari kedaluwarsa ke agenda schedule
    private function syncExpirySchedule(InventoryChecking $item)
    {
        if ($item->expired_date && $item->jumlah > 0 && $item->status !== 'ditarik') {
            // Dapatkan atau buat kategori baru "Kedaluwarsa Barang"
            $jenisSchedule = \App\Models\JenisSchedule::where('nama', 'Kedaluwarsa Barang')->first();
            if (!$jenisSchedule) {
                $jenisSchedule = new \App\Models\JenisSchedule();
                $jenisSchedule->nama = 'Kedaluwarsa Barang';
                $jenisSchedule->status_aktif = true;
                $jenisSchedule->save();
            }

            $expiry = \Carbon\Carbon::parse($item->expired_date);
            
            // Tanggal pengingat (reminder_date) adalah tanggal expired aktual
            // Tanggal pelaksanaan agenda (date) adalah 30 hari sebelum expired
            $executionDate = $expiry->copy()->subDays(30);
            
            // Amankan agar tanggal pelaksanaan tidak mendahului tanggal masuk barang
            $entryDate = \Carbon\Carbon::parse($item->tanggal);
            if ($executionDate->lt($entryDate)) {
                $executionDate = $entryDate->copy();
            }

            \App\Models\Schedule::updateOrCreate(
                ['inventory_checking_id' => $item->id],
                [
                    'name' => 'Kedaluwarsa: ' . $item->nama . ' (' . $item->jumlah . ' Unit)',
                    'jenis_schedule_id' => $jenisSchedule->id,
                    'date' => $executionDate->toDateString(),
                    'reminder_date' => $expiry->toDateString(),
                    'note' => 'Barang ' . $item->nama . ' (Stok: ' . $item->jumlah . ' unit) akan kedaluwarsa pada tanggal ' . $expiry->format('d M Y') . '.',
                    'budget' => 0,
                    'berulang' => false,
                    'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                ]
            );
        } else {
            // Jika expired_date kosong, atau stock habis, atau ditarik, hapus schedule terkait
            \App\Models\Schedule::where('inventory_checking_id', $item->id)->delete();
        }
    }
}




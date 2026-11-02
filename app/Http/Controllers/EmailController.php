<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifikasiEmail;
use App\Models\Schedule;
use App\Models\InventoryChecking;

class EmailController extends Controller
{
    public function kirimScheduleEmail($id)
    {
        $schedule = Schedule::find($id);
        if (!$schedule) {
            return back()->with('error', 'Data tidak ditemukan.');
        }
        $email = config('mail.notify_to');
        if (! $email) {
            return back()->with('error', 'NOTIFY_EMAIL belum diatur di .env.');
        }

        $data = [
            "name" => $schedule->name,
            "catatan" => $schedule->note,
            "date" => $schedule->date,
            "reminder_date" => $schedule->reminder_date
        ];
    
        Mail::to($email)->send(new NotifikasiEmail($data));

        return redirect()->back()->with('success', "Email berhasil dikirim ke $email");
    }

    public function kirimInventoryEmail($id)
    {
        $inventory = InventoryChecking::with(['supplier', 'jenisBarang'])->find($id);
        if (!$inventory) {
            return back()->with('error', 'Data tidak ditemukan.');
        }
        $email = config('mail.notify_to');
        if (! $email) {
            return back()->with('error', 'NOTIFY_EMAIL belum diatur di .env.');
        }

        $data = [
            "name" => $inventory->nama,
            "catatan" => "Jumlah: " . $inventory->jumlah . ", Harga Pokok: " . $inventory->harga_pokok . ", Harga Jual: " . $inventory->harga_jual . ($inventory->keterangan ? ". Catatan: " . $inventory->keterangan : ""),
            "date" => $inventory->tanggal,
            "reminder_date" => $inventory->expired_date ?? '-'
        ];
    
        Mail::to($email)->send(new NotifikasiEmail($data));

        return redirect()->back()->with('success', "Email berhasil dikirim ke $email");
    }
}

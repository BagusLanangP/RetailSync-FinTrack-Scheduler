<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Biaya sebenarnya; NULL = masih estimasi (kolom `budget` = estimasi).
            $table->decimal('budget_final', 15, 2)->nullable()->after('budget');
        });

        // Data lama dianggap sudah final agar angka Finance tidak berubah.
        DB::table('schedules')->whereNotNull('budget')->update(['budget_final' => DB::raw('budget')]);
        // Yang kosong menjadi estimasi 0.
        DB::table('schedules')->whereNull('budget')->update(['budget' => 0]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('budget_final');
        });
    }
};

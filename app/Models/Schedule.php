<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public function apiLibur(){
        return $this->hasOne(apiLibur::class);
        
    }
    public function jenisSchedule()
    {
        return $this->belongsTo(jenisSchedule::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected $fillable = [
        'name',
        'jenis_schedule_id',
        'berulang',
        'note',
        'date',
        'budget',
        'budget_final',
        'reminder_date',
        'status',
        'created_by',
        'completed_at',
        'inventory_checking_id'
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'budget_final' => 'decimal:2',
    ];

    /** Masih estimasi selama biaya final belum diisi. */
    public function getIsEstimateAttribute(): bool
    {
        return $this->budget_final === null;
    }

    /** Biaya yang dihitung sebagai pengeluaran: final jika ada, kalau tidak estimasi. */
    public function getEffectiveBudgetAttribute(): float
    {
        return floatval($this->budget_final ?? $this->budget ?? 0);
    }

    // public function customDates(){
    //     return $this->hasMany(CustomDate::class, 'schedule_id', 'id');
    // }
    
}

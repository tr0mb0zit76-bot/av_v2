<?php
// app/Models/Driver.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $table = 'drivers';

    protected $fillable = [
        'first_name',
        'last_name',
        'patronymic',
        'phone',
        'email',
        'license_number',
        'license_expiry',
        'contractor_id',
        'is_active',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->patronymic}");
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractors::class, 'contractor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
}
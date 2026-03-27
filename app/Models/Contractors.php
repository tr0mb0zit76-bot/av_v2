<?php
// app/Models/Contractors.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractors extends Model
{
    protected $table = 'contractors';

    protected $fillable = [
        'type',
        'name',
        'full_name',
        'inn',
        'kpp',
        'ogrn',
        'phone',
        'email',
        'contact_person',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ordersAsCustomer(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function ordersAsCarrier(): HasMany
    {
        return $this->hasMany(Order::class, 'carrier_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'contractor_id');
    }
}
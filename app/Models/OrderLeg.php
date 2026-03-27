<?php
// app/Models/OrderLeg.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderLeg extends Model
{
    protected $table = 'order_legs';

    protected $fillable = [
        'order_id',
        'sequence',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(RoutePoint::class, 'order_leg_id');
    }

    public function cargos(): BelongsToMany
    {
        return $this->belongsToMany(Cargo::class, 'cargo_leg', 'order_leg_id', 'cargo_id')
                    ->withPivot('quantity', 'status', 'notes')
                    ->withTimestamps();
    }
}
<?php
// app/Models/RoutePoint.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutePoint extends Model
{
    protected $table = 'route_points';

    protected $fillable = [
        'order_leg_id',
        'type',
        'sequence',
        'city',
        'address',
        'latitude',
        'longitude',
        'planned_date',
        'planned_time_from',
        'planned_time_to',
        'actual_date',
        'actual_time',
        'contact_person',
        'contact_phone',
        'instructions',
        'metadata',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'metadata' => 'json',
    ];

    public function leg(): BelongsTo
    {
        return $this->belongsTo(OrderLeg::class, 'order_leg_id');
    }
}
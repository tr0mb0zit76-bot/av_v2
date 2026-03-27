<?php
// app/Models/Cargo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cargo extends Model
{
    protected $fillable = [
        'title',
        'description',
        'weight',
        'volume',
        'cargo_type',
        'cargo_type_id',
        'packing_type',
        'pack_type_id',
        'pallet_count',
        'belt_count',
        'length',
        'width',
        'height',
        'is_hazardous',
        'hazard_class',
        'needs_temperature',
        'temp_min',
        'temp_max',
        'needs_hydraulic',
        'needs_manipulator',
        'special_instructions',
        'photos',
        'documents',
        'ati_load_id',
        'ati_published_at',
        'ati_response',
        'source_text',
        'source_file',
        'parsed_by_ai',
        'parsed_at',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'weight' => 'decimal:2',
        'volume' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'temp_min' => 'decimal:2',
        'temp_max' => 'decimal:2',
        'is_hazardous' => 'boolean',
        'needs_temperature' => 'boolean',
        'needs_hydraulic' => 'boolean',
        'needs_manipulator' => 'boolean',
        'parsed_by_ai' => 'boolean',
        'ati_published_at' => 'datetime',
        'parsed_at' => 'datetime',
        'photos' => 'json',
        'documents' => 'json',
        'ati_response' => 'json',
    ];
    
    public function legs(): BelongsToMany
    {
        return $this->belongsToMany(OrderLeg::class, 'cargo_leg')
                    ->withPivot('quantity', 'status', 'notes')
                    ->withTimestamps();
    }
    
    public function unloadingPoints()
    {
        return $this->belongsToMany(RoutePoint::class, 'cargo_unloading_points')
                    ->withPivot('quantity', 'unloaded_at', 'notes')
                    ->withTimestamps();
    }
}
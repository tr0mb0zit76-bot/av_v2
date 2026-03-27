<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDocument extends Model
{
    protected $table = 'order_documents';

    protected $fillable = [
        'order_id',
        'type',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
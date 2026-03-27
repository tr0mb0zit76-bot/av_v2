<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Order\OrderStatusCalculator;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_number', 'company_code', 'manager_id', 'site_id',
        'order_date', 'loading_date', 'unloading_date',
        'customer_rate', 'customer_payment_form', 'customer_payment_term',
        'carrier_rate', 'carrier_payment_form', 'carrier_payment_term',
        'additional_expenses', 'insurance', 'bonus',
        'kpi_percent', 'delta', 'salary_accrued', 'salary_paid',
        'status', 'is_active',
        'customer_id', 'carrier_id', 'driver_id',
        'invoice_number', 'upd_number', 'waybill_number',
        // Новые поля
        'track_number_customer', 'track_sent_date_customer', 'track_received_date_customer',
        'track_number_carrier', 'track_sent_date_carrier', 'track_received_date_carrier',
        'order_customer_number', 'order_customer_date',
        'order_carrier_number', 'order_carrier_date',
        'upd_carrier_number', 'upd_carrier_date',
        'customer_contact_name', 'customer_contact_phone', 'customer_contact_email',
        'carrier_contact_name', 'carrier_contact_phone', 'carrier_contact_email',
        'metadata', 'payment_statuses', 'created_by', 'updated_by',
		'manual_status', 'status_updated_by', 'status_updated_at'
    ];

    protected $casts = [
        'order_date' => 'date',
        'loading_date' => 'date',
        'unloading_date' => 'date',
        'track_sent_date_customer' => 'date',
        'track_received_date_customer' => 'date',
        'track_sent_date_carrier' => 'date',
        'track_received_date_carrier' => 'date',
        'order_customer_date' => 'date',
        'order_carrier_date' => 'date',
        'upd_carrier_date' => 'date',
        'customer_rate' => 'decimal:2',
        'carrier_rate' => 'decimal:2',
        'additional_expenses' => 'decimal:2',
        'insurance' => 'decimal:2',
        'bonus' => 'decimal:2',
        'kpi_percent' => 'decimal:2',
        'delta' => 'decimal:2',
        'salary_accrued' => 'decimal:2',
        'salary_paid' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'json',
        'payment_statuses' => 'json',
		'status_updated_at' => 'datetime',
    ];

    // Связи
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contractors::class, 'customer_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Contractors::class, 'carrier_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(OrderLeg::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class);
    }
	
	public function statusUpdater(): BelongsTo
	{
		return $this->belongsTo(User::class, 'status_updated_by');
	}
	
	// Аксессоры для вычисляемых полей
	public function getStatusAttribute(): string
	{
		try {
			$calculator = app(OrderStatusCalculator::class);
			$statusInfo = $calculator->calculate($this);
			return $statusInfo['code'];  // возвращаем только код статуса
		} catch (\Exception $e) {
			return $this->status ?? 'new';
		}
	}
	
	public function getStatusInfoAttribute(): array
	{
		return app(OrderStatusCalculator::class)->calculate($this);
	}

    // Вспомогательные методы для получения точек маршрута
    public function getFirstLoadingPointAttribute()
    {
        $leg = $this->legs->first();
        if (!$leg) return null;
        
        return $leg->points->where('type', 'loading')->sortBy('sequence')->first();
    }

    public function getLastUnloadingPointAttribute()
    {
        $leg = $this->legs->first();
        if (!$leg) return null;
        
        return $leg->points->where('type', 'unloading')->sortByDesc('sequence')->first();
    }

    public function getMainCargoAttribute()
    {
        $leg = $this->legs->first();
        if (!$leg) return null;
        
        return $leg->cargos->first();
    }

    // Получение платежей по типу
    public function getPaymentsByParty($party, $type)
    {
        return $this->paymentSchedules()
            ->where('party', $party)
            ->where('type', $type)
            ->first();
    }
}
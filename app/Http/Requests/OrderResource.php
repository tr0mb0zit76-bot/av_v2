<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $leg = $this->legs->first();
        $loadingPoint = $leg?->points->where('type', 'loading')->sortBy('sequence')->first();
        $unloadingPoint = $leg?->points->where('type', 'unloading')->sortByDesc('sequence')->first();
        $cargo = $leg?->cargos->first();
        $status = app(\App\Services\Order\OrderStatusCalculator::class)->calculate($this);

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'company_code' => $this->company_code,
            'manager_name' => $this->manager?->name,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'loading_point' => $loadingPoint?->city ?? $loadingPoint?->address,
            'unloading_point' => $unloadingPoint?->city ?? $unloadingPoint?->address,
            'loading_date' => $loadingPoint?->planned_date?->format('Y-m-d'),
            'unloading_date' => $unloadingPoint?->planned_date?->format('Y-m-d'),
            'cargo_description' => $cargo?->title,
            'customer_rate' => $this->customer_rate,
            'customer_payment_form' => $this->customer_payment_form,
            'customer_payment_term' => $this->customer_payment_term,
            'carrier_rate' => $this->carrier_rate,
            'carrier_payment_form' => $this->carrier_payment_form,
            'carrier_payment_term' => $this->carrier_payment_term,
            'additional_expenses' => $this->additional_expenses,
            'insurance' => $this->insurance,
            'bonus' => $this->bonus,
            'kpi_percent' => $this->kpi_percent,
            'delta' => $this->delta,
            'customer_name' => $this->customer?->name,
            'carrier_name' => $this->carrier?->name,
            'driver_name' => $this->driver?->full_name,
            'driver_phone' => $this->driver?->phone,
            'salary_accrued' => $this->salary_accrued,
            'salary_paid' => $this->salary_paid,
            'track_number_customer' => $this->track_number_customer,
            'track_number_carrier' => $this->track_number_carrier,
            'invoice_number' => $this->invoice_number,
            'upd_number' => $this->upd_number,
            'waybill_number' => $this->waybill_number,
            'status' => $this->status,
            'status_icon' => $status['icon'],
            'status_text' => $status['text'],
            'created_at' => $this->created_at,
        ];
    }
}
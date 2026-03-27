<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'company_code' => 'required|string|in:ЛР,АП,КВ',
            'order_date' => 'required|date',
            'customer_id' => 'nullable|exists:contractors,id',
            'carrier_id' => 'nullable|exists:contractors,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'customer_rate' => 'nullable|numeric|min:0',
            'customer_payment_form' => 'nullable|string',
            'customer_payment_term' => 'nullable|string',
            'carrier_rate' => 'nullable|numeric|min:0',
            'carrier_payment_form' => 'nullable|string',
            'carrier_payment_term' => 'nullable|string',
            'additional_expenses' => 'nullable|numeric|min:0',
            'insurance' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'legs' => 'required|array|min:1',
            'legs.*.type' => 'required|string|in:transport,storage,transshipment',
            'legs.*.points' => 'required|array|min:1',
            'legs.*.points.*.type' => 'required|string|in:loading,unloading,transit',
            'legs.*.points.*.city' => 'nullable|string',
            'legs.*.points.*.address' => 'nullable|string',
            'legs.*.points.*.planned_date' => 'nullable|date',
            'legs.*.cargos' => 'nullable|array',
            'legs.*.cargos.*.title' => 'required|string',
            'legs.*.cargos.*.weight' => 'nullable|numeric|min:0',
        ];
    }
}
<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Cargo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected OrderNumberGenerator $numberGenerator
    ) {}

    public function create(array $data, int $managerId): Order
    {
        DB::beginTransaction();
        
        try {
            // 1. Создаем заказ
            $order = Order::create([
                'company_code' => $data['company_code'],
                'order_date' => $data['order_date'],
                'manager_id' => $managerId,
                'customer_id' => $data['customer_id'] ?? null,
                'carrier_id' => $data['carrier_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'customer_rate' => $data['customer_rate'] ?? null,
                'customer_payment_form' => $data['customer_payment_form'] ?? null,
                'customer_payment_term' => $data['customer_payment_term'] ?? null,
                'carrier_rate' => $data['carrier_rate'] ?? null,
                'carrier_payment_form' => $data['carrier_payment_form'] ?? null,
                'carrier_payment_term' => $data['carrier_payment_term'] ?? null,
                'additional_expenses' => $data['additional_expenses'] ?? 0,
                'insurance' => $data['insurance'] ?? 0,
                'bonus' => $data['bonus'] ?? 0,
                'site_id' => 1,
                'created_by' => $managerId,
                'status' => 'new',
            ]);
            
            // 2. Генерируем номер
            $order->order_number = $this->numberGenerator->generate($order);
            $order->save();
            
            // 3. Создаем legs и точки
            $this->createLegsWithPoints($order, $data['legs']);
            
            DB::commit();
            
            return $order->load(['legs.points', 'legs.cargos', 'customer', 'carrier', 'driver']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    protected function createLegsWithPoints(Order $order, array $legs): void
    {
        foreach ($legs as $legIndex => $legData) {
            $leg = $order->legs()->create([
                'sequence' => $legIndex + 1,
                'type' => $legData['type'],
                'description' => $legData['description'] ?? null,
            ]);
            
            // Точки маршрута
            foreach ($legData['points'] as $pointIndex => $point) {
                $leg->points()->create([
                    'type' => $point['type'],
                    'sequence' => $pointIndex + 1,
                    'city' => $point['city'] ?? null,
                    'address' => $point['address'] ?? null,
                    'planned_date' => $point['planned_date'] ?? null,
                    'planned_time_from' => $point['planned_time_from'] ?? null,
                    'planned_time_to' => $point['planned_time_to'] ?? null,
                ]);
            }
            
            // Грузы
            if (!empty($legData['cargos'])) {
                foreach ($legData['cargos'] as $cargoData) {
                    $cargo = Cargo::create([
                        'title' => $cargoData['title'],
                        'description' => $cargoData['description'] ?? null,
                        'weight' => $cargoData['weight'] ?? null,
                        'volume' => $cargoData['volume'] ?? null,
                        'pallet_count' => $cargoData['pallet_count'] ?? null,
                        'created_by' => $order->created_by,
                    ]);
                    
                    $leg->cargos()->attach($cargo->id, [
                        'quantity' => $cargoData['quantity'] ?? 1,
                        'status' => 'planned',
                    ]);
                }
            }
        }
    }
    
    public function updateCell(Order $order, string $field, $value): Order
    {
        $allowedFields = [
            'customer_rate', 'carrier_rate', 'customer_payment_form', 'carrier_payment_form',
            'customer_payment_term', 'carrier_payment_term', 'additional_expenses',
            'insurance', 'bonus', 'salary_paid', 'track_number_customer',
            'track_number_carrier', 'invoice_number', 'upd_number', 'waybill_number',
            'status'
        ];
        
        if (!in_array($field, $allowedFields)) {
            throw new \Exception('Поле не доступно для редактирования');
        }
        
        $order->$field = $value;
        $order->save();
        
        return $order;
    }
    
    public function delete(Order $order): void
    {
        DB::beginTransaction();
        try {
            $order->legs()->delete();
            $order->paymentSchedules()->delete();
            $order->documents()->delete();
            $order->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
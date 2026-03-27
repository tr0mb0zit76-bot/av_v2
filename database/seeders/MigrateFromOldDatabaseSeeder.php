// database/seeders/MigrateFromOldDatabaseSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Contractor;
use App\Models\Cargo;
use App\Models\OrderLeg;
use App\Models\RoutePoint;
use App\Models\PaymentSchedule;
use App\Services\KPI\KpiService;

class MigrateFromOldDatabaseSeeder extends Seeder
{
    protected KpiService $kpiService;
    
    public function __construct(KpiService $kpiService)
    {
        $this->kpiService = $kpiService;
    }
    
    public function run(): void
    {
        $this->command->info('Starting migration from old database structure...');
        
        // Подключаемся к старой базе
        $oldOrders = DB::connection('mysql_old')->table('orders')->get();
        
        $this->command->info("Found {$oldOrders->count()} orders to migrate");
        
        $progressBar = $this->command->getOutput()->createProgressBar($oldOrders->count());
        
        foreach ($oldOrders as $oldOrder) {
            try {
                DB::transaction(function () use ($oldOrder) {
                    // 1. Создаем или находим контрагентов
                    $customerId = $this->migrateContractor($oldOrder, 'customer');
                    $carrierId = $this->migrateContractor($oldOrder, 'carrier');
                    
                    // 2. Создаем груз (если есть данные)
                    $cargoId = $this->migrateCargo($oldOrder);
                    
                    // 3. Создаем заказ
                    $order = Order::create([
                        'order_number' => $oldOrder->order_number,
                        'company_code' => $oldOrder->company_code,
                        'manager_id' => $oldOrder->manager_id,
                        'site_id' => $oldOrder->site_id,
                        'order_date' => $oldOrder->order_date,
                        'loading_date' => $oldOrder->loading_date,
                        'unloading_date' => $oldOrder->unloading_date,
                        'customer_rate' => $oldOrder->customer_rate,
                        'customer_payment_form' => $oldOrder->customer_payment_form,
                        'customer_payment_term' => $oldOrder->customer_payment_term,
                        'carrier_rate' => $oldOrder->carrier_rate,
                        'carrier_payment_form' => $oldOrder->carrier_payment_form,
                        'carrier_payment_term' => $oldOrder->carrier_payment_term,
                        'additional_expenses' => $oldOrder->additional_expenses ?? 0,
                        'insurance' => $oldOrder->insurance ?? 0,
                        'bonus' => $oldOrder->bonus ?? 0,
                        'kpi_percent' => $oldOrder->kpi_percent,
                        'delta' => $oldOrder->delta,
                        'salary_accrued' => $oldOrder->salary_accrued,
                        'salary_paid' => $oldOrder->salary_paid ?? 0,
                        'status' => $oldOrder->status ?? 'new',
                        'is_active' => $oldOrder->is_active ?? true,
                        'customer_id' => $customerId,
                        'carrier_id' => $carrierId,
                        'driver_id' => $oldOrder->driver_id,
                        'ai_draft_id' => $oldOrder->ai_draft_id,
                        'ai_confidence' => $oldOrder->ai_confidence,
                        'ai_metadata' => $oldOrder->ai_metadata ? json_decode($oldOrder->ai_metadata, true) : null,
                        'ati_response' => $oldOrder->ati_response ? json_decode($oldOrder->ati_response, true) : null,
                        'ati_load_id' => $oldOrder->ati_load_id,
                        'ati_published_at' => $oldOrder->ati_published_at,
                        'invoice_number' => $oldOrder->invoice_number,
                        'upd_number' => $oldOrder->upd_number,
                        'waybill_number' => $oldOrder->waybill_number,
                        'created_by' => $oldOrder->created_by,
                        'updated_by' => $oldOrder->updated_by,
                        'metadata' => $oldOrder->metadata ? json_decode($oldOrder->metadata, true) : null,
                        'payment_statuses' => $oldOrder->payment_statuses ? json_decode($oldOrder->payment_statuses, true) : null,
                        'created_at' => $oldOrder->created_at,
                        'updated_at' => $oldOrder->updated_at,
                    ]);
                    
                    // 4. Создаем этап (leg) и точки маршрута
                    $leg = $order->legs()->create([
                        'sequence' => 1,
                        'type' => 'transport',
                        'description' => 'Основная перевозка',
                    ]);
                    
                    // Точка загрузки
                    if ($oldOrder->loading_point) {
                        $leg->points()->create([
                            'type' => 'loading',
                            'sequence' => 1,
                            'address' => $oldOrder->loading_point,
                            'planned_date' => $oldOrder->loading_date,
                        ]);
                    }
                    
                    // Точка выгрузки
                    if ($oldOrder->unloading_point) {
                        $leg->points()->create([
                            'type' => 'unloading',
                            'sequence' => 2,
                            'address' => $oldOrder->unloading_point,
                            'planned_date' => $oldOrder->unloading_date,
                        ]);
                    }
                    
                    // 5. Связываем груз с этапом
                    if ($cargoId) {
                        $leg->cargos()->attach($cargoId, [
                            'quantity' => 1,
                            'status' => 'planned'
                        ]);
                    }
                    
                    // 6. Создаем график платежей из старых полей
                    $this->migratePaymentSchedule($order, $oldOrder);
                    
                    // 7. Если KPI не были рассчитаны (или изменились пороги), пересчитываем
                    if (is_null($order->kpi_percent) || $this->shouldRecalculateKpi($oldOrder)) {
                        $this->kpiService->updateOrderKpi($order);
                    }
                });
                
                $progressBar->advance();
                
            } catch (\Exception $e) {
                Log::error('Failed to migrate order', [
                    'order_id' => $oldOrder->id,
                    'error' => $e->getMessage()
                ]);
                $this->command->error("Failed to migrate order {$oldOrder->id}: {$e->getMessage()}");
            }
        }
        
        $progressBar->finish();
        $this->command->info("\nMigration completed!");
    }
    
    /**
     * Миграция контрагента
     */
    protected function migrateContractor($oldOrder, string $type): ?int
    {
        $field = $type === 'customer' ? 'customer_name' : 'carrier_name';
        $idField = $type === 'customer' ? 'customer_id' : 'carrier_id';
        
        // Если уже есть ID в новой структуре
        if (!empty($oldOrder->$idField)) {
            return $oldOrder->$idField;
        }
        
        // Если есть название, создаем контрагента
        if (!empty($oldOrder->$field)) {
            $contractor = Contractor::firstOrCreate(
                ['name' => $oldOrder->$field],
                [
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            return $contractor->id;
        }
        
        return null;
    }
    
    /**
     * Миграция груза
     */
    protected function migrateCargo($oldOrder): ?int
    {
        // Если уже есть cargo_id
        if (!empty($oldOrder->cargo_id)) {
            return $oldOrder->cargo_id;
        }
        
        // Если есть описание груза, создаем
        if (!empty($oldOrder->cargo_description)) {
            $cargo = Cargo::create([
                'title' => $oldOrder->cargo_description,
                'description' => $oldOrder->cargo_description,
                'weight' => $oldOrder->cargo_weight ?? null,
                'volume' => $oldOrder->cargo_volume ?? null,
                'created_at' => $oldOrder->created_at,
                'updated_at' => $oldOrder->updated_at,
            ]);
            return $cargo->id;
        }
        
        return null;
    }
    
    /**
     * Миграция графика платежей
     */
    protected function migratePaymentSchedule(Order $order, $oldOrder): void
    {
        // Платежи от заказчика
        if (!empty($oldOrder->prepayment_customer) && $oldOrder->prepayment_customer > 0) {
            $order->paymentSchedules()->create([
                'party' => 'customer',
                'type' => 'prepayment',
                'amount' => $oldOrder->prepayment_customer,
                'planned_date' => $oldOrder->prepayment_date,
                'actual_date' => $oldOrder->prepayment_date,
                'status' => $oldOrder->prepayment_status === 'paid' ? 'paid' : 'pending',
                'created_at' => $oldOrder->created_at,
            ]);
        }
        
        if (!empty($oldOrder->final_customer) && $oldOrder->final_customer > 0) {
            $order->paymentSchedules()->create([
                'party' => 'customer',
                'type' => 'final',
                'amount' => $oldOrder->final_customer,
                'planned_date' => $oldOrder->final_customer_date,
                'actual_date' => $oldOrder->final_customer_date,
                'status' => $oldOrder->final_customer_status === 'paid' ? 'paid' : 'pending',
                'created_at' => $oldOrder->created_at,
            ]);
        }
        
        // Платежи перевозчику
        if (!empty($oldOrder->prepayment_carrier) && $oldOrder->prepayment_carrier > 0) {
            $order->paymentSchedules()->create([
                'party' => 'carrier',
                'type' => 'prepayment',
                'amount' => $oldOrder->prepayment_carrier,
                'planned_date' => $oldOrder->prepayment_carrier_date,
                'actual_date' => $oldOrder->prepayment_carrier_date,
                'status' => $oldOrder->prepayment_carrier_status === 'paid' ? 'paid' : 'pending',
                'created_at' => $oldOrder->created_at,
            ]);
        }
        
        if (!empty($oldOrder->final_carrier) && $oldOrder->final_carrier > 0) {
            $order->paymentSchedules()->create([
                'party' => 'carrier',
                'type' => 'final',
                'amount' => $oldOrder->final_carrier,
                'planned_date' => $oldOrder->final_carrier_date,
                'actual_date' => $oldOrder->final_carrier_date,
                'status' => $oldOrder->final_carrier_status === 'paid' ? 'paid' : 'pending',
                'created_at' => $oldOrder->created_at,
            ]);
        }
    }
    
    /**
     * Проверка, нужно ли пересчитать KPI
     */
    protected function shouldRecalculateKpi($oldOrder): bool
    {
        // Если KPI не был рассчитан
        if (is_null($oldOrder->kpi_percent)) {
            return true;
        }
        
        // Если изменились пороги (можно проверить по дате заказа)
        // Здесь можно добавить логику проверки изменений в kpi_thresholds
        
        return false;
    }
}
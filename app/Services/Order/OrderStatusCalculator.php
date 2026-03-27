<?php

namespace App\Services\Order;

use App\Models\Order;
use Carbon\Carbon;

class OrderStatusCalculator
{
    /**
     * Дата отсечки — заказы до этой даты считаются завершенными
     */
    protected const CUTOFF_DATE = '2026-02-15';
    
    /**
     * Статусы и их отображение
     */
    protected const STATUSES = [
        'new' => [
            'code' => 'new',
            'icon' => '🆕',
            'text' => 'Новая',
            'description' => 'Заявка создана, дата погрузки не назначена'
        ],
        'in_progress' => [
            'code' => 'in_progress',
            'icon' => '🚛',
            'text' => 'Выполняется',
            'description' => 'Груз в пути'
        ],
        'documents' => [
            'code' => 'documents',
            'icon' => '📄',
            'text' => 'Документы',
            'description' => 'Требуются документы'
        ],
        'payment' => [
            'code' => 'payment',
            'icon' => '💰',
            'text' => 'Оплата',
            'description' => 'Ожидание оплаты'
        ],
        'completed' => [
            'code' => 'completed',
            'icon' => '✅',
            'text' => 'Завершена',
            'description' => 'Заявка выполнена'
        ],
        'cancelled' => [
            'code' => 'cancelled',
            'icon' => '❌',
            'text' => 'Отменена',
            'description' => 'Заявка отменена'
        ],
    ];
    
    /**
     * Рассчитать статус заявки
     */
    public function calculate(Order $order): array
    {
        // Если статус установлен вручную (для админов/руководителей)
        if ($order->manual_status && $order->status_updated_by) {
            return $this->getStatusInfo($order->manual_status);
        }
        
        // Заказы до даты отсечки считаем завершенными
        if ($order->order_date && $order->order_date->lte(Carbon::parse(self::CUTOFF_DATE))) {
            return $this->getStatusInfo('completed');
        }
        
        // Проверяем наличие точек маршрута
        $firstLoading = $this->getFirstLoadingPoint($order);
        $lastUnloading = $this->getLastUnloadingPoint($order);
        
        // Проверяем наличие документов
        $hasDocuments = $this->hasRequiredDocuments($order);
        
        // Проверяем статус оплат
        $paymentsStatus = $this->getPaymentsStatus($order);
        
        // Логика определения статуса
        if ($order->status === 'cancelled') {
            return $this->getStatusInfo('cancelled');
        }
        
        if (!$firstLoading) {
            return $this->getStatusInfo('new');
        }
        
        if (!$lastUnloading) {
            return $this->getStatusInfo('in_progress');
        }
        
        if (!$hasDocuments) {
            return $this->getStatusInfo('documents');
        }
        
        if (!$paymentsStatus['customer_paid'] || !$paymentsStatus['carrier_paid']) {
            return $this->getStatusInfo('payment');
        }
        
        return $this->getStatusInfo('completed');
    }
    
    /**
     * Получить первую точку погрузки
     */
    protected function getFirstLoadingPoint(Order $order)
    {
        $leg = $order->legs->first();
        if (!$leg) return null;
        
        return $leg->points
            ->where('type', 'loading')
            ->sortBy('planned_date')
            ->first();
    }
    
    /**
     * Получить последнюю точку выгрузки
     */
    protected function getLastUnloadingPoint(Order $order)
    {
        $leg = $order->legs->first();
        if (!$leg) return null;
        
        return $leg->points
            ->where('type', 'unloading')
            ->sortByDesc('planned_date')
            ->first();
    }
    
    /**
     * Проверить наличие обязательных документов
     */
    protected function hasRequiredDocuments(Order $order): bool
    {
        // Проверяем наличие номеров документов в заказе
        $hasInvoice = !empty($order->invoice_number);
        $hasUpd = !empty($order->upd_number);
        $hasWaybill = !empty($order->waybill_number);
        
        // Проверяем загруженные файлы
        $hasFiles = $order->documents()
            ->whereIn('type', ['invoice', 'upd', 'waybill', 'signed_order'])
            ->exists();
        
        return ($hasInvoice && $hasUpd && $hasWaybill) || $hasFiles;
    }
    
    /**
     * Получить статус оплат
     */
    protected function getPaymentsStatus(Order $order): array
    {
        $payments = $order->paymentSchedules;
        
        $customerPaid = $payments
            ->where('party', 'customer')
            ->where('status', 'paid')
            ->isNotEmpty();
            
        $carrierPaid = $payments
            ->where('party', 'carrier')
            ->where('status', 'paid')
            ->isNotEmpty();
        
        return [
            'customer_paid' => $customerPaid,
            'carrier_paid' => $carrierPaid,
        ];
    }
    
    /**
     * Получить информацию о статусе
     */
    public function getStatusInfo(string $statusCode): array
    {
        return self::STATUSES[$statusCode] ?? [
            'code' => 'unknown',
            'icon' => '❓',
            'text' => 'Неизвестно',
            'description' => 'Статус не определен'
        ];
    }
    
    /**
     * Получить все возможные статусы (для выпадающих списков)
     */
    public function getAllStatuses(): array
    {
        return array_values(self::STATUSES);
    }
    
    /**
     * Проверить, можно ли изменить статус вручную
     */
    public function canEditManually(Order $order, $user): bool
    {
        // Только админ или руководитель могут менять статус вручную
        return $user->isAdmin() || $user->isSupervisor();
    }
}
<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderNumberGenerator
{
    /**
     * Генерация номера заказа
     *
     * @param Order $order
     * @param string|null $companyCode
     * @param int|null $managerId
     * @return string
     */
    public function generate(Order $order, ?string $companyCode = null, ?int $managerId = null): string
    {
        // Если у заказа уже есть номер (при ручном редактировании) - не трогаем
        if ($order->exists && $order->order_number) {
            Log::info('Order number already exists, skipping generation', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);
            return $order->order_number;
        }

        $companyCode = $companyCode ?? $order->company_code;
        $managerId = $managerId ?? $order->manager_id;

        // Если нет компании - генерируем временный номер
        if (!$companyCode) {
            return $this->generateTemporaryNumber($order);
        }

        $manager = User::find($managerId);
        if (!$manager) {
            Log::warning('Manager not found for order number generation', [
                'order_id' => $order->id,
                'manager_id' => $managerId
            ]);
            return $this->generateTemporaryNumber($order);
        }

        // Получаем инициалы менеджера (Иванов Иван -> ИИ)
        $initials = $this->getManagerInitials($manager);
        
        // Получаем следующий порядковый номер
        $sequence = $this->getNextSequence($managerId, $companyCode);
        
        // Формируем номер по шаблону в зависимости от компании
        $orderNumber = $this->formatNumber($companyCode, $initials, $sequence);
        
        Log::info('Generated order number', [
            'order_id' => $order->id,
            'company_code' => $companyCode,
            'manager_id' => $managerId,
            'order_number' => $orderNumber,
            'sequence' => $sequence
        ]);
        
        return $orderNumber;
    }

    /**
     * Проверка уникальности номера
     *
     * @param string $number
     * @param int|null $excludeOrderId
     * @return bool
     */
    public function isUnique(string $number, ?int $excludeOrderId = null): bool
    {
        $query = Order::where('order_number', $number);
        
        if ($excludeOrderId) {
            $query->where('id', '!=', $excludeOrderId);
        }
        
        return !$query->exists();
    }

    /**
     * Получение следующего порядкового номера для менеджера и компании
     *
     * @param int $managerId
     * @param string $companyCode
     * @return int
     */
    protected function getNextSequence(int $managerId, string $companyCode): int
    {
        // Получаем последний номер заказа менеджера для этой компании в текущем году
        $lastOrder = Order::where('manager_id', $managerId)
            ->where('company_code', $companyCode)
            ->whereYear('created_at', now()->year)
            ->whereNotNull('order_number')
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$lastOrder || !$lastOrder->order_number) {
            return 1;
        }
        
        // Извлекаем числовую часть из номера
        $sequence = $this->extractSequenceNumber($lastOrder->order_number, $companyCode);
        
        return $sequence + 1;
    }

    /**
     * Извлечение порядкового номера из строки
     *
     * @param string $orderNumber
     * @param string $companyCode
     * @return int
     */
    protected function extractSequenceNumber(string $orderNumber, string $companyCode): int
    {
        // Формат ЛР-ИИ-001 -> извлекаем 001
        if ($companyCode === 'ЛР') {
            if (preg_match('/ЛР-[A-ZА-Я]{2}-(\d{3})/', $orderNumber, $matches)) {
                return (int) $matches[1];
            }
        }
        
        // Формат ИИ-АП-001
        if ($companyCode === 'АП') {
            if (preg_match('/[A-ZА-Я]{2}-АП-(\d{3})/', $orderNumber, $matches)) {
                return (int) $matches[1];
            }
        }
        
        // Формат 001-КВ-ИИ
        if ($companyCode === 'КВ') {
            if (preg_match('/(\d{3})-КВ-[A-ZА-Я]{2}/', $orderNumber, $matches)) {
                return (int) $matches[1];
            }
        }
        
        // Если не удалось извлечь, пробуем найти любые 3 цифры в конце
        if (preg_match('/(\d{3})$/', $orderNumber, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }

    /**
     * Форматирование номера по шаблону компании
     *
     * @param string $companyCode
     * @param string $initials
     * @param int $sequence
     * @return string
     */
    protected function formatNumber(string $companyCode, string $initials, int $sequence): string
    {
        $seqPadded = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return match($companyCode) {
            'ЛР' => "ЛР-{$initials}-{$seqPadded}",
            'АП' => "{$initials}-АП-{$seqPadded}",
            'КВ' => "{$seqPadded}-КВ-{$initials}",
            default => $seqPadded,
        };
    }

    /**
     * Получение инициалов менеджера (Иванов Иван Петрович -> ИИ)
     *
     * @param User $manager
     * @return string
     */
    protected function getManagerInitials(User $manager): string
    {
        $nameParts = explode(' ', trim($manager->name));
        $initials = '';
        
        // Берем первую букву фамилии
        if (isset($nameParts[0])) {
            $initials .= mb_substr($nameParts[0], 0, 1);
        }
        
        // Берем первую букву имени
        if (isset($nameParts[1])) {
            $initials .= mb_substr($nameParts[1], 0, 1);
        }
        
        // Если нет имени, берем первую букву email
        if (strlen($initials) < 2 && $manager->email) {
            $initials = mb_substr($manager->email, 0, 2);
        }
        
        // Если все еще нет, берем ID
        if (strlen($initials) < 2) {
            $initials = 'M' . $manager->id;
        }
        
        return mb_strtoupper($initials);
    }

    /**
     * Генерация временного номера (когда нет компании или менеджера)
     *
     * @param Order $order
     * @return string
     */
    protected function generateTemporaryNumber(Order $order): string
    {
        $prefix = 'TEMP';
        $year = now()->format('y');
        $month = now()->format('m');
        
        $lastTemp = Order::where('order_number', 'LIKE', "TEMP-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastTemp && preg_match('/TEMP-' . $year . $month . '-(\d+)/', $lastTemp->order_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }
        
        return "TEMP-{$year}{$month}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Принудительная генерация нового номера (для ручного пересчета)
     *
     * @param Order $order
     * @return string
     */
    public function regenerate(Order $order): string
    {
        Log::info('Regenerating order number', [
            'order_id' => $order->id,
            'old_number' => $order->order_number
        ]);
        
        $newNumber = $this->generate($order);
        
        // Проверяем уникальность
        if (!$this->isUnique($newNumber, $order->id)) {
            Log::warning('Generated number is not unique, incrementing', [
                'order_id' => $order->id,
                'number' => $newNumber
            ]);
            
            // Если номер не уникален, добавляем суффикс
            $newNumber = $newNumber . '-1';
        }
        
        return $newNumber;
    }
}
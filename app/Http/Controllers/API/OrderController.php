<?php

namespace App\Http\Controllers\Api;

//use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['manager', 'customer', 'carrier', 'driver', 'legs.points', 'legs.cargos']);
        
        if ($request->date_from) $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('order_date', '<=', $request->date_to);
        if ($request->status) $query->where('status', $request->status);
        if ($request->company_code) $query->where('company_code', $request->company_code);
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }
        
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSupervisor()) {
            $query->where('manager_id', $user->id);
        }
        
        $orders = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 50);
        
        return OrderResource::collection($orders);
    }

    public function show($id)
    {
        $order = Order::with([
            'manager', 'customer', 'carrier', 'driver',
            'legs.points', 'legs.cargos', 'paymentSchedules', 'documents'
        ])->findOrFail($id);
        
        $this->authorize('view', $order);
        
        return response()->json($order);
    }

    public function store(OrderStoreRequest $request)
    {
        $order = $this->orderService->create($request->validated(), auth()->id());
        
        return response()->json([
            'success' => true,
            'order' => $order,
            'message' => 'Заказ создан'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);
        
        $order->update($request->validate([
            'company_code' => 'sometimes|string',
            'customer_rate' => 'nullable|numeric',
            'carrier_rate' => 'nullable|numeric',
            'status' => 'nullable|string',
            'salary_paid' => 'nullable|numeric',
            'invoice_number' => 'nullable|string',
        ]));
        
        return response()->json(['success' => true, 'order' => $order]);
    }

    public function updateCell(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);
        
        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable'
        ]);
        
        try {
            $order = $this->orderService->updateCell($order, $request->field, $request->value);
            
            return response()->json([
                'success' => true,
                'order' => $order,
                'message' => 'Поле обновлено'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('delete', $order);
        
        $this->orderService->delete($order);
        
        return response()->json(['success' => true, 'message' => 'Заказ удален']);
    }
    
    public function stats(Request $request)
    {
        $query = Order::query();
        $user = auth()->user();
        
        if (!$user->isAdmin() && !$user->isSupervisor()) {
            $query->where('manager_id', $user->id);
        }
        
        if ($request->date_from) $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('order_date', '<=', $request->date_to);
        
        return response()->json([
            'total_orders' => $query->count(),
            'active_orders' => $query->whereIn('status', ['new', 'in_transit'])->count(),
            'completed_orders' => $query->where('status', 'completed')->count(),
            'total_revenue' => $query->sum('customer_rate'),
            'avg_kpi' => round($query->whereNotNull('kpi_percent')->avg('kpi_percent') ?? 0, 1),
        ]);
    }
    
    public function regenerateNumber($id)
    {
        $order = Order::findOrFail($id);
        
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSupervisor() && $order->manager_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Нет прав'], 403);
        }
        
        $newNumber = app(\App\Services\Order\OrderNumberGenerator::class)->regenerate($order);
        $order->order_number = $newNumber;
        $order->save();
        
        return response()->json(['success' => true, 'order_number' => $newNumber]);
    }
}
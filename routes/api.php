<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\Contractor;
use App\Models\Cargo;
use App\Models\Driver;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\DriverController;
use App\Services\DaDataService;

// ==================== ПУБЛИЧНЫЙ МАРШРУТ ЛОГИНА ====================
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = Auth::user();
        
        return response()->json([
            'user' => $user->load('role')
        ]);
    }

    return response()->json(['message' => 'Неверные учетные данные'], 401);
});

// ==================== ЗАЩИЩЕННЫЕ МАРШРУТЫ ====================
Route::middleware('auth')->group(function () {
    
    // ==================== AUTH ====================
    Route::get('/user', fn(Request $r) => $r->user()->load('role'));
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Выход выполнен']);
    });
    
    // ==================== USERS ====================
    Route::get('/users', fn() => User::with('role')->get());
    Route::get('/roles', fn() => Role::all());
    
    // ==================== ORDERS (НОВЫЕ МАРШРУТЫ — ЧЕРЕЗ КОНТРОЛЛЕР) ====================
    Route::post('/orders/{id}/regenerate-number', [OrderController::class, 'regenerateNumber']);
    Route::patch('/orders/{id}/cell', [OrderController::class, 'updateCell']);
    Route::get('/stats', [OrderController::class, 'stats']);
    
    // Старые маршруты — оставляем как есть (пока)
    Route::get('/orders', function (Request $request) {
        $query = Order::with(['manager', 'customer', 'carrier']);
        
        if ($request->date_from) $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('order_date', '<=', $request->date_to);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('carrier_name', 'like', "%{$search}%");
            });
        }
        
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSupervisor()) {
            $query->where('manager_id', $user->id);
        }
        
        $orders = $query->orderBy('id', 'asc')->get();
        
        return response()->json($orders->map(function($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'company_code' => $order->company_code,
                'manager_name' => $order->manager?->name,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'loading_point' => $order->loading_point,
                'unloading_point' => $order->unloading_point,
                'cargo_description' => $order->cargo_description,
                'loading_date' => $order->loading_date?->format('Y-m-d'),
                'unloading_date' => $order->unloading_date?->format('Y-m-d'),
                'customer_name' => $order->customer_name,
                'carrier_name' => $order->carrier_name,
                'customer_rate' => $order->customer_rate,
                'carrier_rate' => $order->carrier_rate,
                'kpi_percent' => $order->kpi_percent,
                'status' => $order->status,
                'delta' => $order->delta,
                'salary_accrued' => $order->salary_accrued,
                'salary_paid' => $order->salary_paid,
                'invoice_number' => $order->invoice_number,
                'upd_number' => $order->upd_number,
                'waybill_number' => $order->waybill_number,
                'payment_statuses' => $order->payment_statuses,
            ];
        }));
    });
    
    Route::post('/orders', function (Request $request) {
        $order = Order::create(array_merge(
            $request->validate([
                'company_code' => 'required|string',
                'order_date' => 'required|date',
                'customer_name' => 'nullable|string',
                'carrier_name' => 'nullable|string',
                'customer_rate' => 'nullable|numeric',
                'carrier_rate' => 'nullable|numeric',
                'cargo_description' => 'nullable|string',
                'loading_point' => 'nullable|string',
                'unloading_point' => 'nullable|string',
            ]),
            ['manager_id' => auth()->id(), 'site_id' => 1, 'status' => 'new']
        ));
        return response()->json($order, 201);
    });
    
    Route::put('/orders/{id}', function (Request $request, $id) {
        $order = Order::findOrFail($id);
        $order->update($request->validate([
            'company_code' => 'sometimes|string',
            'customer_rate' => 'nullable|numeric',
            'carrier_rate' => 'nullable|numeric',
            'status' => 'nullable|string',
            'salary_paid' => 'nullable|numeric',
            'invoice_number' => 'nullable|string',
            'upd_number' => 'nullable|string',
            'waybill_number' => 'nullable|string',
            'payment_statuses' => 'nullable|array',
        ]));
        return response()->json($order);
    });
    
    Route::delete('/orders/{id}', function ($id) {
        Order::findOrFail($id)->delete();
        return response()->json(['message' => 'Удалено']);
    });
    
    // ==================== НОВЫЕ МАРШРУТЫ ДЛЯ ПОЛУЧЕНИЯ ОДНОГО ЗАКАЗА ====================
    Route::get('/orders/{id}', function ($id) {
		$order = App\Models\Order::with([
			'manager', 'customer', 'carrier', 'driver',
			'legs.points', 'legs.cargos', 'paymentSchedules', 'documents'
		])->findOrFail($id);
		
		$data = $order->toArray();
		$data['status'] = $order->status;
		
		return response()->json($data);
	});
    
    // ==================== CONTRACTORS ====================
    Route::get('/contractors', [App\Http\Controllers\Api\ContractorController::class, 'index']);
	Route::get('/contractors/search', [App\Http\Controllers\Api\ContractorController::class, 'search']);
	Route::post('/contractors', [App\Http\Controllers\Api\ContractorController::class, 'store']);
	Route::get('/contractors/{id}', [App\Http\Controllers\Api\ContractorController::class, 'show']);
	Route::put('/contractors/{id}', [App\Http\Controllers\Api\ContractorController::class, 'update']);
	Route::delete('/contractors/{id}', [App\Http\Controllers\Api\ContractorController::class, 'destroy']);
    
    // ==================== DRIVERS ====================
    Route::get('/drivers', [App\Http\Controllers\Api\DriverController::class, 'index']);
	Route::get('/drivers/search', [App\Http\Controllers\Api\DriverController::class, 'search']);
	Route::post('/drivers', [App\Http\Controllers\Api\DriverController::class, 'store']);
	Route::get('/drivers/{id}', [App\Http\Controllers\Api\DriverController::class, 'show']);
	Route::put('/drivers/{id}', [App\Http\Controllers\Api\DriverController::class, 'update']);
	Route::delete('/drivers/{id}', [App\Http\Controllers\Api\DriverController::class, 'destroy']);
    
    // ==================== CARGOS ====================
    Route::get('/cargos', fn() => Cargo::all());
    
    // ==================== STATS ====================
    Route::get('/stats', function () {
        $query = Order::query();
        if (!auth()->user()->isAdmin()) {
            $query->where('manager_id', auth()->id());
        }
        return [
            'total_orders' => $query->count(),
            'active_orders' => $query->where('status', 'in_transit')->count(),
            'avg_kpi' => round($query->whereNotNull('kpi_percent')->avg('kpi_percent') ?? 0, 1),
            'total_revenue' => $query->sum('customer_rate'),
        ];
    });
    
    // ==================== КОНФИГУРАЦИЯ КОЛОНОК ====================
    Route::get('/user/columns-config/{module}', function ($module) {
        $user = auth()->user();
        $role = $user->role;
        
        \Log::info('Columns config request', [
            'user_id' => $user->id,
            'module' => $module,
            'role' => $role?->name,
            'has_config' => !empty($role?->columns_config)
        ]);
        
        if (!$role || !$role->columns_config) {
            return response()->json(null);
        }
        
        $config = $role->columns_config[$module] ?? null;
        return response()->json($config);
    });
    
    // ==================== DaData API МАРШРУТЫ ====================
    /**
     * Поиск адресов и городов (подсказки)
     */
    Route::post('/suggest', function (Request $request) {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'nullable|in:address,city'
        ]);
        
        $params = [
            'query' => $request->query,
            'count' => 10,
            'language' => 'ru'
        ];
        
        if ($request->type === 'city') {
            $params['from_bound'] = ['value' => 'city'];
            $params['to_bound'] = ['value' => 'city'];
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . env('DADATA_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', $params);
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('DaData suggest error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    /**
     * Поиск организаций по ИНН/названию (для подстановки контрагентов)
     */
    Route::post('/suggest/party', function (Request $request) {
        $request->validate([
            'query' => 'required|string|min:2',
            'count' => 'nullable|integer|min:1|max:20'
        ]);
        
        $count = $request->input('count', 10);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . env('DADATA_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party', [
                'query' => $request->query,
                'count' => $count,
                'status' => ['ACTIVE']
            ]);
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('DaData party suggest error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    /**
     * Получение организации по ИНН (точный поиск)
     */
    Route::post('/find-by-inn', function (Request $request) {
        $request->validate([
            'inn' => 'required|string|min:10|max:12'
        ]);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . env('DADATA_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party', [
                'query' => $request->inn,
                'count' => 1
            ]);
            
            $data = $response->json();
            
            if (!empty($data['suggestions'][0])) {
                $suggestion = $data['suggestions'][0];
                $value = $suggestion['data'];
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'name' => $value['name']['short_with_opf'] ?? $value['name']['full_with_opf'] ?? null,
                        'full_name' => $value['name']['full_with_opf'] ?? null,
                        'inn' => $value['inn'] ?? null,
                        'kpp' => $value['kpp'] ?? null,
                        'ogrn' => $value['ogrn'] ?? null,
                        'legal_address' => $value['address']['value'] ?? null,
                        'phone' => $value['phones'][0]['value'] ?? null,
                        'email' => $value['emails'][0] ?? null,
                    ]
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Организация не найдена'], 404);
            
        } catch (\Exception $e) {
            \Log::error('DaData find by INN error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // ==================== ДОКУМЕНТЫ ЗАКАЗОВ ====================
    Route::post('/orders/{id}/documents', function (Request $request, $id) {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'type' => 'required|string|in:track_customer,track_carrier,invoice,upd,order_customer,order_carrier,upd_carrier,other',
            'file' => 'required|file|max:10240'
        ]);
        
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store("orders/{$id}/documents", 'public');
        
        $document = $order->documents()->create([
            'type' => $request->type,
            'original_name' => $originalName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'metadata' => [
                'uploaded_at' => now(),
                'uploaded_by_name' => auth()->user()->name
            ]
        ]);
        
        return response()->json($document, 201);
    });
    
    Route::get('/orders/{id}/documents', function ($id) {
        $order = Order::findOrFail($id);
        return response()->json($order->documents);
    });
    
    Route::delete('/orders/documents/{documentId}', function ($documentId) {
        $document = App\Models\OrderDocument::findOrFail($documentId);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return response()->json(['message' => 'Файл удален']);
    });
    
    Route::get('/orders/documents/{documentId}/download', function ($documentId) {
        $document = App\Models\OrderDocument::findOrFail($documentId);
        return Storage::disk('public')->download($document->file_path, $document->original_name);
    });
    
    // ==================== ADMIN МАРШРУТЫ ====================
    Route::prefix('admin')->group(function () {
        Route::get('/roles', function () {
            return Role::withCount('users')->get();
        });
        
        Route::get('/roles/{id}', function ($id) {
            return Role::findOrFail($id);
        });
        
        Route::put('/roles/{id}', function (Request $request, $id) {
            $role = Role::findOrFail($id);
            
            $validated = $request->validate([
                'display_name' => 'sometimes|string',
                'description' => 'nullable|string',
                'permissions' => 'nullable|array',
                'columns_config' => 'nullable|array',
            ]);
            
            $role->update($validated);
            return response()->json($role);
        });
        
        Route::post('/roles/{id}/columns-config', function (Request $request, $id) {
            $role = Role::findOrFail($id);
            
            $validated = $request->validate([
                'module' => 'required|string|in:orders,contractors,cargos',
                'config' => 'required|array',
                'config.visible' => 'array',
                'config.editable' => 'array',
            ]);
            
            $configArray = $role->columns_config ?? [];
            $configArray[$validated['module']] = $validated['config'];
            $role->columns_config = $configArray;
            $role->save();
            
            return response()->json([
                'success' => true,
                'config' => $role->columns_config[$validated['module']]
            ]);
        });
        
        Route::delete('/roles/{id}/columns-config/{module}', function ($id, $module) {
            $role = Role::findOrFail($id);
            $configArray = $role->columns_config ?? [];
            unset($configArray[$module]);
            $role->columns_config = $configArray;
            $role->save();
            
            return response()->json(['success' => true]);
        });
    });
    
    // ==================== AI Chat ====================
    Route::post('/ai/chat', function (Request $request) {
        $message = $request->input('message');
        $context = $request->input('context', []);
        
        $reply = "Я понимаю ваш запрос: \"{$message}\". ";
        
        switch ($context['page'] ?? null) {
            case 'dashboard':
                $reply .= "На дашборде вы можете видеть ключевые показатели: общее количество заказов, активные перевозки, KPI менеджеров и финансовые показатели.";
                break;
            case 'orders':
                $reply .= "В разделе заказов вы можете просматривать, создавать и редактировать заявки. Для поиска используйте фильтры вверху страницы.";
                break;
            case 'contractors':
                $reply .= "В разделе контрагентов хранится информация о заказчиках и перевозчиках. Вы можете искать по названию или ИНН.";
                break;
            case 'cargos':
                $reply .= "В разделе грузов вы можете управлять номенклатурой перевозимых грузов.";
                break;
            default:
                $reply .= "Чем я могу вам помочь?";
        }
        
        return response()->json([
            'success' => true,
            'reply' => $reply,
            'action' => null
        ]);
    });
});
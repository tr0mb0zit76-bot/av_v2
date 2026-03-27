<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\Contractors;
use App\Models\Cargo;

// ==================== API МАРШРУТЫ ====================

// Логин
Route::post('/api/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        $user = Auth::user()->load('role');
        return response()->json(['user' => $user]);
    }

    return response()->json(['message' => 'Неверные учетные данные'], 401);
});

// Защищенные API маршруты
Route::middleware('auth')->group(function () {
    
    // ==================== DaData API МАРШРУТЫ ====================
    Route::post('/api/suggest', function (Request $request) {
		$query = $request->input('query');
		$city = $request->input('city');
		$type = $request->input('type');
		
		if (!$query || strlen($query) < 2) {
			return response()->json(['suggestions' => []]);
		}
		
		$token = env('DADATA_TOKEN');
		if (!$token) {
			\Log::error('DADATA_TOKEN not set');
			return response()->json(['suggestions' => []]);
		}
		
		$params = [
			'query' => $query,
			'count' => 10
		];
		
		// Если тип = city, ищем только города
		if ($type === 'city') {
			$params['from_bound'] = ['value' => 'city'];
			$params['to_bound'] = ['value' => 'city'];
		}
		
		// Если передан город, фильтруем адреса по городу
		if ($city && strlen($city) > 2) {
			$params['locations'] = [
				['city' => $city]
			];
		}
		
		try {
			$response = Http::withHeaders([
				'Authorization' => 'Token ' . $token,
				'Content-Type' => 'application/json',
			])->timeout(10)->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', $params);
			
			if ($response->successful()) {
				return response()->json($response->json());
			}
			
			return response()->json(['suggestions' => []]);
			
		} catch (\Exception $e) {
			\Log::error('DaData error: ' . $e->getMessage());
			return response()->json(['suggestions' => []]);
		}
	});
    
    // AUTH
    Route::get('/api/user', function (Request $request) {
        return $request->user()->load('role');
    });
    
    Route::post('/api/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Выход выполнен']);
    });
    
    // ПОЛЬЗОВАТЕЛИ
    Route::get('/api/users', function () {
        return User::with('role')->get();
    });
    
    Route::post('/api/users', function (Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'is_active' => 'boolean'
        ]);
        $validated['password'] = bcrypt($validated['password']);
        $user = User::create($validated);
        return response()->json($user->load('role'), 201);
    });
    
    Route::put('/api/users/{id}', function (Request $request, $id) {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
            'is_active' => 'sometimes|boolean'
        ]);
        
        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        $user->update($validated);
        return response()->json($user->load('role'));
    });
    
    Route::delete('/api/users/{id}', function ($id) {
        if (auth()->id() == $id) {
            return response()->json(['error' => 'Нельзя удалить себя'], 403);
        }
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Пользователь удален']);
    });
    
    // РОЛИ
    Route::get('/api/roles', function () {
        return Role::all();
    });
    
    // ЗАКАЗЫ - ОСНОВНОЙ МАРШРУТ (СО ВСЕМИ ПОЛЯМИ)
    Route::get('/api/orders', function (Request $request) {
        $query = Order::with([
            'manager', 
            'customer', 
            'carrier', 
            'driver',
            'legs.points',
            'legs.cargos',
            'paymentSchedules'
        ]);
        
        // Фильтры
        if ($request->date_from) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('carrier', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSupervisor()) {
            $query->where('manager_id', $user->id);
        }
        
        $orders = $query->orderBy('id', 'asc')->get();
        
        return response()->json($orders->map(function($order) {
            // Получаем точки маршрута
            $leg = $order->legs->first();
            $loadingPoint = $leg?->points->where('type', 'loading')->sortBy('sequence')->first();
            $unloadingPoint = $leg?->points->where('type', 'unloading')->sortByDesc('sequence')->first();
            
            // Получаем первый груз
            $cargo = $leg?->cargos->first();
            
            // Получаем платежи
            $prepaymentCustomer = $order->paymentSchedules
                ->where('party', 'customer')
                ->where('type', 'prepayment')
                ->first();
            $finalCustomer = $order->paymentSchedules
                ->where('party', 'customer')
                ->where('type', 'final')
                ->first();
            $prepaymentCarrier = $order->paymentSchedules
                ->where('party', 'carrier')
                ->where('type', 'prepayment')
                ->first();
            $finalCarrier = $order->paymentSchedules
                ->where('party', 'carrier')
                ->where('type', 'final')
                ->first();
            
            // Формируем полное имя водителя
            $driverName = null;
            if ($order->driver) {
                $driverName = trim(($order->driver->last_name ?? '') . ' ' . 
                                 ($order->driver->first_name ?? '') . ' ' . 
                                 ($order->driver->patronymic ?? ''));
                $driverName = $driverName ?: $order->driver->name ?? null;
            }
            
            return [
                'id' => $order->id,
                // Основные
                'order_number' => $order->order_number,
                'status' => $order->status,
                'company_code' => $order->company_code,
                'manager_name' => $order->manager?->name,
                'order_date' => $order->order_date?->format('Y-m-d'),
                
                // Маршрут
                'loading_point' => $loadingPoint?->address ?? $loadingPoint?->city ?? $order->loading_point,
                'unloading_point' => $unloadingPoint?->address ?? $unloadingPoint?->city ?? $order->unloading_point,
                'cargo_description' => $cargo?->title ?? $order->cargo_description,
                'loading_date' => $loadingPoint?->planned_date?->format('Y-m-d') ?? $order->loading_date?->format('Y-m-d'),
                'unloading_date' => $unloadingPoint?->planned_date?->format('Y-m-d') ?? $order->unloading_date?->format('Y-m-d'),
                
                // Финансы - ставки и условия
                'customer_rate' => $order->customer_rate,
                'customer_payment_form' => $order->customer_payment_form,
                'customer_payment_term' => $order->customer_payment_term,
                'carrier_rate' => $order->carrier_rate,
                'carrier_payment_form' => $order->carrier_payment_form,
                'carrier_payment_term' => $order->carrier_payment_term,
                
                // Расходы и KPI
                'additional_expenses' => $order->additional_expenses,
                'insurance' => $order->insurance,
                'bonus' => $order->bonus,
                'kpi_percent' => $order->kpi_percent,
                'delta' => $order->delta,
                
                // Оплаты - заказчик
                'prepayment_customer_amount' => $prepaymentCustomer?->amount,
                'prepayment_customer_planned_date' => $prepaymentCustomer?->planned_date?->format('Y-m-d'),
                'prepayment_customer_actual_date' => $prepaymentCustomer?->actual_date?->format('Y-m-d'),
                'prepayment_customer_status' => $prepaymentCustomer?->status,
                'final_customer_amount' => $finalCustomer?->amount,
                'final_customer_planned_date' => $finalCustomer?->planned_date?->format('Y-m-d'),
                'final_customer_actual_date' => $finalCustomer?->actual_date?->format('Y-m-d'),
                'final_customer_status' => $finalCustomer?->status,
                
                // Оплаты - перевозчик
                'prepayment_carrier_amount' => $prepaymentCarrier?->amount,
                'prepayment_carrier_planned_date' => $prepaymentCarrier?->planned_date?->format('Y-m-d'),
                'prepayment_carrier_actual_date' => $prepaymentCarrier?->actual_date?->format('Y-m-d'),
                'prepayment_carrier_status' => $prepaymentCarrier?->status,
                'final_carrier_amount' => $finalCarrier?->amount,
                'final_carrier_planned_date' => $finalCarrier?->planned_date?->format('Y-m-d'),
                'final_carrier_actual_date' => $finalCarrier?->actual_date?->format('Y-m-d'),
                'final_carrier_status' => $finalCarrier?->status,
                
                // Контрагенты
                'customer_name' => $order->customer?->name ?? $order->customer_name,
                'customer_contact_name' => $order->customer_contact_name ?? $order->customer?->contact_person,
                'customer_contact_phone' => $order->customer_contact_phone ?? $order->customer?->contact_person_phone,
                'customer_contact_email' => $order->customer_contact_email ?? $order->customer?->contact_person_email,
                'carrier_name' => $order->carrier?->name ?? $order->carrier_name,
                'carrier_contact_name' => $order->carrier_contact_name ?? $order->carrier?->contact_person,
                'carrier_contact_phone' => $order->carrier_contact_phone ?? $order->carrier?->contact_person_phone,
                'carrier_contact_email' => $order->carrier_contact_email ?? $order->carrier?->contact_person_email,
                'driver_name' => $driverName ?: '—',
                'driver_phone' => $order->driver?->phone ?: '—',
                
                // Зарплата
                'salary_accrued' => $order->salary_accrued,
                'salary_paid' => $order->salary_paid,
                
                // Документы - трек-номера
                'track_number_customer' => $order->track_number_customer,
                'track_sent_date_customer' => $order->track_sent_date_customer?->format('Y-m-d'),
                'track_received_date_customer' => $order->track_received_date_customer?->format('Y-m-d'),
                'track_number_carrier' => $order->track_number_carrier,
                'track_sent_date_carrier' => $order->track_sent_date_carrier?->format('Y-m-d'),
                'track_received_date_carrier' => $order->track_received_date_carrier?->format('Y-m-d'),
                
                // Документы - счета и УПД
                'invoice_number' => $order->invoice_number,
                'upd_number' => $order->upd_number,
                'upd_carrier_number' => $order->upd_carrier_number,
                'upd_carrier_date' => $order->upd_carrier_date?->format('Y-m-d'),
                
                // Документы - заявки
                'order_customer_number' => $order->order_customer_number,
                'order_customer_date' => $order->order_customer_date?->format('Y-m-d'),
                'order_carrier_number' => $order->order_carrier_number,
                'order_carrier_date' => $order->order_carrier_date?->format('Y-m-d'),
                
                // Дополнительно
                'waybill_number' => $order->waybill_number,
                
                // Метаданные
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
        }));
    });
    
    Route::get('/api/orders/{id}', function ($id) {
        $order = Order::with([
            'manager', 
            'customer', 
            'carrier', 
            'driver',
            'legs.points',
            'legs.cargos',
            'paymentSchedules'
        ])->findOrFail($id);
        
        $data = $order->toArray();
        $data['status'] = $order->status;
        
        // Добавляем поля из legs для удобства модалки
        $leg = $order->legs->first();
        if ($leg) {
            $loadingPoint = $leg->points->where('type', 'loading')->first();
            $unloadingPoint = $leg->points->where('type', 'unloading')->first();
            
            $data['loading_point'] = $loadingPoint?->address ?? $loadingPoint?->city;
            $data['unloading_point'] = $unloadingPoint?->address ?? $unloadingPoint?->city;
            $data['loading_date'] = $loadingPoint?->planned_date?->format('Y-m-d');
            $data['unloading_date'] = $unloadingPoint?->planned_date?->format('Y-m-d');
        }
        
        // Добавляем имена контрагентов
        $data['customer_name'] = $order->customer?->name;
        $data['carrier_name'] = $order->carrier?->name;
        $data['driver_name'] = $order->driver?->full_name;
        $data['manager_name'] = $order->manager?->name;
        
        // Форматируем дату заказа
        $data['order_date'] = $order->order_date?->format('Y-m-d');
        
        return response()->json($data);
    });
    
    Route::post('/api/orders', function (Request $request) {
        $validated = $request->validate([
            'company_code' => 'required|string',
            'order_date' => 'required|date',
            'customer_name' => 'nullable|string',
            'carrier_name' => 'nullable|string',
            'customer_rate' => 'nullable|numeric',
            'carrier_rate' => 'nullable|numeric',
            'cargo_description' => 'nullable|string',
            'loading_point' => 'nullable|string',
            'unloading_point' => 'nullable|string',
            'status' => 'nullable|string'
        ]);
        
        $validated['manager_id'] = auth()->id();
        $validated['site_id'] = 1;
        
        $order = Order::create($validated);
        return response()->json($order, 201);
    });
    
    Route::delete('/api/orders/{id}', function ($id) {
        Order::findOrFail($id)->delete();
        return response()->json(['message' => 'Заказ удален']);
    });
    
    // КОНТРАГЕНТЫ
    Route::get('/api/contractors', function () {
        return Contractor::all();
    });
    
    Route::get('/api/contractors/search', function (Request $request) {
        $query = $request->input('query');
        $type = $request->input('type');
        
        return Contractor::where('name', 'like', "%{$query}%")
            ->when($type, function($q) use ($type) {
                return $q->where('type', $type);
            })
            ->limit(10)
            ->get();
    });
    
    // ГРУЗЫ
    Route::get('/api/cargos', function () {
        return Cargo::all();
    });
    
    // СТАТИСТИКА
    Route::get('/api/stats', function () {
        $query = Order::query();
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSupervisor()) {
            $query->where('manager_id', $user->id);
        }
        
        return [
            'total_orders' => $query->count(),
            'active_orders' => $query->where('status', 'in_transit')->count(),
            'avg_kpi' => round($query->whereNotNull('kpi_percent')->avg('kpi_percent') ?? 0, 1),
            'total_revenue' => $query->sum('customer_rate'),
        ];
    });
    
    // ==================== НОВЫЕ МАРШРУТЫ ====================
    
    // Конфигурация колонок
    Route::get('/api/user/columns-config/{module}', function ($module) {
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
    
    // ADMIN маршруты
    Route::prefix('api/admin')->group(function () {
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
    
    // Загрузка файла документа
    Route::post('/api/orders/{id}/documents', function (Request $request, $id) {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'type' => 'required|string|in:track_customer,track_carrier,invoice,upd,order_customer,order_carrier,upd_carrier',
            'file' => 'required|file|max:10240' // 10MB max
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
        
        return response()->json($document);
    });

    // Получение файлов документа
    Route::get('/api/orders/{id}/documents', function ($id) {
        $order = Order::findOrFail($id);
        return response()->json($order->documents);
    });

    // Удаление файла
    Route::delete('/api/orders/documents/{documentId}', function ($documentId) {
        $document = OrderDocument::findOrFail($documentId);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return response()->json(['message' => 'Файл удален']);
    });

    // Скачивание файла
    Route::get('/api/orders/documents/{documentId}/download', function ($documentId) {
        $document = OrderDocument::findOrFail($documentId);
        return Storage::disk('public')->download($document->file_path, $document->original_name);
    });
    
    // AI Chat
    Route::post('/api/ai/chat', function (Request $request) {
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

// ==================== SPA ====================
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '.*');
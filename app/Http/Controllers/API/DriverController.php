<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        return Driver::with('contractor')->get();
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $contractorId = $request->input('contractor_id');

        $drivers = Driver::query()
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            });

        if ($contractorId) {
            $drivers->where('contractor_id', $contractorId);
        }

        return $drivers->limit(10)->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'contractor_id' => 'nullable|exists:contractors,id',
            'is_active' => 'boolean',
        ]);

        $driver = Driver::create($validated);

        return response()->json($driver, 201);
    }

    public function show($id)
    {
        $driver = Driver::with('contractor')->findOrFail($id);
        return response()->json($driver);
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'patronymic' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'contractor_id' => 'nullable|exists:contractors,id',
            'is_active' => 'boolean',
        ]);

        $driver->update($validated);

        return response()->json($driver);
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        
        // Проверка, есть ли связанные заказы
        if ($driver->orders()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить водителя, у которого есть заказы'
            ], 422);
        }

        $driver->delete();
        
        return response()->json(['message' => 'Водитель удален']);
    }
}
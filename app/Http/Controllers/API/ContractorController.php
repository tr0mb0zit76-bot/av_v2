<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contractors;
use Illuminate\Http\Request;

class ContractorController extends Controller
{
    public function index()
    {
        return Contractors::all();
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $type = $request->input('type');

        return Contractors::where('name', 'like', "%{$query}%")
            ->when($type, fn($q) => $q->where('type', $type))
            ->limit(10)
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,carrier,both',
            'name' => 'required|string|max:255',
            'full_name' => 'nullable|string',
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'contact_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $contractor = Contractors::create($validated);

        return response()->json($contractor, 201);
    }

    public function show($id)
    {
        $contractor = Contractors::findOrFail($id);
        return response()->json($contractor);
    }

    public function update(Request $request, $id)
    {
        $contractor = Contractors::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|in:customer,carrier,both',
            'name' => 'sometimes|string|max:255',
            'full_name' => 'nullable|string',
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'contact_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $contractor->update($validated);

        return response()->json($contractor);
    }

    public function destroy($id)
    {
        $contractor = Contractors::findOrFail($id);
        
        // Проверка, есть ли связанные заказы
        if ($contractor->ordersAsCustomer()->exists() || $contractor->ordersAsCarrier()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить контрагента, у которого есть заказы'
            ], 422);
        }

        $contractor->delete();
        
        return response()->json(['message' => 'Контрагент удален']);
    }
}
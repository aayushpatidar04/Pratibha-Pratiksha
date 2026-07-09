<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inventory/Index', [
            'items' => Inventory::orderBy('item_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:100',
            'category' => 'required|in:room,student,common',
            'total_quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:20',
        ]);

        $validated['available'] = $validated['total_quantity'];
        $validated['unit'] = $validated['unit'] ?? 'pieces';

        Inventory::create($validated);

        return back()->with('success', 'Inventory item added.');
    }

    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'total_quantity' => 'sometimes|integer|min:0',
            'in_use' => 'sometimes|integer|min:0',
            'damaged' => 'sometimes|integer|min:0',
        ]);

        $inventory->fill($validated);
        $inventory->available = max(0, $inventory->total_quantity - $inventory->in_use - $inventory->damaged);
        $inventory->save();

        return back()->with('success', 'Inventory updated.');
    }

    public function destroy(Inventory $inventory): RedirectResponse
    {
        $inventory->delete();

        return back()->with('success', 'Item removed.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = warehouse::query();

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $warehouses = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($warehouse) {
            $warehouse->status_text = $warehouse->status ? 'Active' : 'Inactive';
            return $warehouse;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'warehousename', 'header' => 'WAREHOUSE NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Warehouses/WarehouseIndex', [
            'warehouses' => $warehouses,
            'columns' => $columns
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Warehouse information
            'warehousename' => 'required|string|max:255',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        warehouse::create($validated);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(warehouse $warehouse)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(warehouse $warehouse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, warehouse $warehouse)
    {
        $validated = $request->validate([
            // Warehouse information
            'warehousename' => 'required|string|max:255',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $warehouse = warehouse::findOrFail($id); // find warehouse by ID or fail
        $warehouse->delete(); // delete warehouse

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->wantsJson()) {
            $search = $request->input('search');

            $query = ProductUnit::where('status', 1);

            if (!empty($search)) {
                $query->where('unit_name', 'like', "{$search}%");
            }
            return response()->json([
                'productUnits' => $query->orderBy('unit_name')->limit(10)->get(['id', 'unit_name', 'unit_code'])
            ]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = ProductUnit::query();

        // Show only active product units
        $query->where('status', true);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $productUnits = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($productUnit) {
            $productUnit->status_text = $productUnit->status ? 'Active' : 'Inactive';
            return $productUnit;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'unit_name', 'header' => 'UNIT NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'unit_code', 'header' => 'UNIT CODE', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('ProductUnits/ProductUnitIndex', [
            'productUnits' => $productUnits,
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
            'unit_name' => 'required|string|max:255',
            'unit_code' => 'nullable|string|max:50',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        ProductUnit::create($validated);

        return redirect()->route('product-units.index')->with('success', 'Product Unit created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductUnit $productUnit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductUnit $productUnit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductUnit $productUnit)
    {
        $validated = $request->validate([
            'unit_name' => 'required|string|max:255',
            'unit_code' => 'nullable|string|max:50',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $productUnit->update($validated);

        return redirect()->route('product-units.index')->with('success', 'Product Unit updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete by setting status to inactive.
     */
    public function destroy(Request $request, $id)
    {
        $productUnit = ProductUnit::findOrFail($id);

        // Soft delete: set status to inactive (false)
        $productUnit->update([
            'status' => false,
            'updated_by' => $request->user()->id
        ]);

        return redirect()->route('product-units.index')->with('success', 'Product Unit deactivated successfully!');
    }
}

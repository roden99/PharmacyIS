<?php

namespace App\Http\Controllers;

use App\Models\WarehouseItem;
use App\Models\warehouse;
use App\Models\product;
use Illuminate\Http\Request;

class WarehouseItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = WarehouseItem::with(['warehouse', 'product.brand']);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'warehouse_name') {
                $query->whereHas('warehouse', function ($q) use ($search) {
                    $q->where('warehousename', 'like', "{$search}%");
                });
            } elseif ($column === 'product_name') {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('productname', 'like', "{$search}%");
                });
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $warehouseItems = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($item) {
            $item->warehouse_name = $item->warehouse?->warehousename ?? 'N/A';
            $item->product_name = $item->product?->productname ?? 'N/A';
            $item->brand_name = $item->product?->brand?->brandname ?? 'N/A';
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'warehouse_name', 'header' => 'WAREHOUSE', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'brand_name', 'header' => 'BRAND', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'product_name', 'header' => 'PRODUCT', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'quantity', 'header' => 'QUANTITY', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('WarehouseItems/WarehouseItemIndex', [
            'warehouseItems' => $warehouseItems,
            'columns' => $columns,
            'warehouses' => warehouse::where('status', true)->orderBy('warehousename')->get(['id', 'warehousename']),
            'products' => product::where('status', true)->orderBy('productname')->get(['id', 'productname'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        WarehouseItem::create($validated);

        return redirect()->route('warehouse-items.index')->with('success', 'Warehouse item created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $item = WarehouseItem::findOrFail($id);
        $item->update($validated);

        return redirect()->route('warehouse-items.index')->with('success', 'Warehouse item updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = WarehouseItem::findOrFail($id);
        $item->delete();

        return redirect()->route('warehouse-items.index')->with('success', 'Warehouse item deleted successfully!');
    }
}

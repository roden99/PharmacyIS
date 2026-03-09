<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = Delivery::with(['supplier', 'purchaseOrder']);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'supplier_name') {
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('company', 'like', "{$search}%");
                });
            } elseif ($column === 'status') {
                $query->where('status', 'like', "{$search}%");
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($item) {
            $item->supplier_name = $item->supplier?->company ?? 'N/A';
            $item->po_number = $item->purchase_order_id ? "#PO-{$item->purchase_order_id}" : 'Standalone';
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'supplier_name', 'header' => 'SUPPLIER', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'po_number', 'header' => 'PO NUMBER', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'delivery_date', 'header' => 'DELIVERY DATE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'status', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'notes', 'header' => 'NOTES', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Deliveries/DeliveryIndex', [
            'deliveries' => $deliveries,
            'columns' => $columns,
            'suppliers' => supplier::where('status', true)->orderBy('company')->get(['id', 'company']),
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
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'delivery_date' => 'required|date',
            'status' => 'required|in:pending,received,partial,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        Delivery::create($validated);

        return redirect()->route('deliveries.index')->with('success', 'Delivery created successfully!');
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
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'delivery_date' => 'required|date',
            'status' => 'required|in:pending,received,partial,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $delivery = Delivery::findOrFail($id);
        $delivery->update($validated);

        return redirect()->route('deliveries.index')->with('success', 'Delivery updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->delete();

        return redirect()->route('deliveries.index')->with('success', 'Delivery deleted successfully!');
    }
}

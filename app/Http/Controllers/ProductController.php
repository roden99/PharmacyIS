<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\brand;
use App\Models\ProductUnit;
use App\Models\strength;
use App\Models\drugform;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (request()->wantsJson()) {
            $search = $request->input('search');
            $includeId = $request->input('include_id');
            $query = product::with(['brand', 'unit', 'drugform', 'strength'])->where('status', true);

            if ($includeId) {
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->where('productname', 'like', "%{$search}%")
                            ->orWhereHas('brand', function ($b) use ($search) {
                                $b->where('brandname', 'like', "%{$search}%");
                            });
                    }
                })->orWhere('id', $includeId);
            } elseif (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('productname', 'like', "%{$search}%")
                        ->orWhereHas('brand', function ($b) use ($search) {
                            $b->where('brandname', 'like', "%{$search}%");
                        });
                });
            }

            return response()->json([
                'products' => $query->orderBy('productname')->limit(20)->get()->map(function ($product) {
                    $parts = [$product->productname];
                    if ($product->drugform) {
                        $parts[] = $product->drugform->drugformname;
                    }
                    if ($product->strength) {
                        $parts[] = $product->strength->strengthname;
                    }
                    if ($product->unit) {
                        $parts[] = $product->unit->unit_name;
                    }
                    $displayName = implode(' ', $parts);
                    if ($product->brand) {
                        $displayName .= ' (' . $product->brand->brandname . ')';
                    }
                    return [
                        'id' => $product->id,
                        'display_name' => $displayName,
                    ];
                })
            ]);
        }


        $search = $request->input('search');
        $column = $request->input('column');
        $type = $request->input('type');


        $query = product::with(['brand', 'unit', 'drugform', 'productType'])->where('status', true);

        if ($type === 'generic') {
            $query->where('isgeneric', true);
        } elseif ($type === 'branded') {
            $query->where('isgeneric', false);
        }

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('brandname', 'like', "{$search}%");
                });
            } elseif ($column === 'type_name') {
                $query->whereHas('productType', function ($q) use ($search) {
                    $q->where('type_name', 'like', "{$search}%");
                });
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($product) {
            $product->status_text = $product->status ? 'Active' : 'Inactive';
            $product->generic_text = $product->isgeneric ? 'Generic' : 'Branded';
            $product->brand_name = $product->brand?->brandname ?? 'N/A';
            $product->product_qty = $product->is_inventory ? ($product->product_qty ?? 0) : '-';
            $product->reorder_level = $product->is_inventory ? ($product->reorder_level ?? 0) : '-';
            $product->unit_name = $product->unit?->unit_name ?? 'N/A';
            $product->type_name = $product->productType?->type_name ?? 'N/A';

            // Build display name: productname drugform unit (brand)
            $parts = [$product->productname];
            if ($product->drugform) {
                $parts[] = $product->drugform->drugformname;
            }
            if ($product->unit) {
                $parts[] = $product->unit->unit_name;
            }
            $displayName = implode(' ', $parts);
            if ($product->brand) {
                $displayName .= ' (' . $product->brand->brandname . ')';
            }
            $product->display_name = $displayName;

            return $product;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'generic_text', 'header' => 'TYPE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'type_name', 'header' => 'CATEGORY', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'display_name', 'header' => 'PRODUCT NAME', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'product_qty',    'header' => 'QTY',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'reorder_level',  'header' => 'REORDER LVL',   'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'productname', 'header' => 'PRODUCT NAME', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'brand_name', 'header' => 'BRAND', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Products/ProductIndex', [
            'products' => $products,
            'columns' => $columns,
            'brands' => brand::where('status', true)->orderBy('brandname')->get(['id', 'brandname']),
            'productUnits' => ProductUnit::where('status', true)->orderBy('unit_name')->get(['id', 'unit_name']),
            'strengths' => strength::where('status', true)->orderBy('strengthname')->get(['id', 'strengthname']),
            'drugforms' => drugform::where('status', true)->orderBy('drugformname')->get(['id', 'drugformname'])
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
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'strength_id' => 'nullable|exists:strengths,id',
            'drugform_id' => 'nullable|exists:drugforms,id',
            'isgeneric' => 'boolean',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, product $product)
    {
        $validated = $request->validate([
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'strength_id' => 'nullable|exists:strengths,id',
            'drugform_id' => 'nullable|exists:drugforms,id',
            'isgeneric' => 'boolean',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Set the initial inventory quantity for a product.
     */
    public function initialInventory(Request $request, product $product)
    {
        $validated = $request->validate([
            'product_qty' => 'required|integer|min:0',
        ]);

        $product->update([
            'product_qty'  => $validated['product_qty'],
            'initial_qty'  => $validated['product_qty'],
            'is_inventory' => true,
            'initial_date' => now()->startOfDay(),
            'updated_by'   => $request->user()->id,
        ]);

        return redirect()->route('products.index')->with('success', 'Initial inventory set successfully!');
    }

    public function reorderLevel(Request $request, product $product)
    {
        $validated = $request->validate([
            'reorder_level' => 'required|integer|min:0',
        ]);

        $product->update([
            'reorder_level' => $validated['reorder_level'],
            'updated_by'    => $request->user()->id,
        ]);

        return redirect()->route('products.index')->with('success', 'Reorder level updated successfully!');
    }

    public function history(product $product)
    {
        // Parse initial_date to Carbon so all date comparisons are consistent
        $initialDate = $product->initial_date
            ? \Carbon\Carbon::parse($product->initial_date)->startOfDay()
            : null;

        // Delivery items (IN) — use delivery_date from the parent delivery
        $deliveries = \App\Models\DeliveryItem::with('delivery.supplier')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $date = $item->delivery?->delivery_date
                    ? \Carbon\Carbon::parse($item->delivery->delivery_date)->startOfDay()
                    : $item->created_at;
                return [
                    'date'           => $date,
                    'type'           => 'IN',
                    'reference'      => 'Delivery #' . $item->delivery_id,
                    'party'          => $item->delivery?->supplier?->company ?? 'N/A',
                    'invoice_no'     => $item->delivery?->invoice_no ?? '—',
                    'qty'            => $item->quantity_received,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                    // lt: strictly before initial_date; same-day deliveries after init are handled by inventory guard
                ];
            });

        // Sales order items (OUT) — use delivery_date from the parent sales order
        $sales = \App\Models\SalesOrderItem::with([
            'salesOrder.customerSalesAccount.customer',
            'salesOrder.customerSalesAccount.salesAccount',
        ])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $date = $item->salesOrder?->delivery_date
                    ? \Carbon\Carbon::parse($item->salesOrder->delivery_date)->startOfDay()
                    : $item->created_at;
                $csa = $item->salesOrder?->customerSalesAccount;
                $customer = $csa?->customer;
                $account  = $csa?->salesAccount;
                $customerName = $customer
                    ? ($customer->is_drugstore
                        ? strtoupper($customer->company)
                        : trim(strtoupper($customer->last_name) . ', ' . strtoupper($customer->first_name)))
                    : 'N/A';
                $party = ($account ? strtoupper($account->account_name) . ' - ' : '') . $customerName;
                return [
                    'date'           => $date,
                    'type'           => 'OUT',
                    'reference'      => 'SO #' . $item->sales_order_id,
                    'party'          => $party,
                    'invoice_no'     => $item->salesOrder?->invoice_no ?? '—',
                    'qty'            => $item->quantity,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                    // lt: strictly before initial_date
                ];
            });

        // Initial entry
        $entries = collect();
        if ($initialDate) {
            $entries->push([
                'date'           => $initialDate,
                'type'           => 'INITIAL',
                'reference'      => 'Initial Inventory',
                'party'          => '—',
                'invoice_no'     => '—',
                'qty'            => $product->initial_qty ?? 0,
                'before_initial' => false,
            ]);
        }

        $entries = $entries
            ->concat($deliveries)
            ->concat($sales)
            ->sortBy('date')
            ->values();

        // Compute running balance — entries before initial get 0
        $balance = 0;
        $initialReached = false;
        $result = $entries->map(function ($entry) use (&$balance, &$initialReached) {
            if ($entry['type'] === 'INITIAL') {
                $initialReached = true;
                $balance = $entry['qty'];
                return array_merge($entry, [
                    'balance' => $balance,
                    'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
                ]);
            }

            if (!$initialReached || $entry['before_initial']) {
                return array_merge($entry, [
                    'balance' => 0,
                    'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
                ]);
            }

            $balance += $entry['type'] === 'IN' ? $entry['qty'] : -$entry['qty'];
            return array_merge($entry, [
                'balance' => $balance,
                'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
            ]);
        });

        return response()->json([
            'product' => [
                'id'           => $product->id,
                'display_name' => $product->display_name ?? $product->productname,
                'initial_date' => $initialDate ? \Carbon\Carbon::parse($initialDate)->format('m-d-Y') : null,
                'product_qty'  => $product->product_qty,
                'reorder_level' => $product->reorder_level,
            ],
            'history' => $result,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = product::findOrFail($id); // find product by ID or fail
        $product->update([
            'status' => false,
            'updated_by' => request()->user()->id
        ]); // soft delete by setting status to false

        return redirect()->route('products.index')->with('success', 'Product deactivated successfully!');
    }
}

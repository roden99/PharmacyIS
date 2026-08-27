<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\brand;
use App\Models\ProductUnit;
use App\Models\ProductLot;
use App\Models\strength;
use App\Models\drugform;
use Carbon\Carbon;
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
                        'id'           => $product->id,
                        'display_name' => $displayName,
                        'initial_date' => $product->initial_date
                            ? \Carbon\Carbon::parse($product->initial_date)->format('Y-m-d')
                            : null,
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

        if (!empty($search) && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('brandname', 'like', "%{$search}%");
                });
            } elseif ($column === 'type_name') {
                $query->whereHas('productType', function ($q) use ($search) {
                    $q->where('type_name', 'like', "%{$search}%");
                });
            } else {
                $query->where($column, 'like', "%{$search}%");
            }
        }

        $products = $query->orderBy('productname')->paginate(15)->through(function ($product) {
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

    public function posItems(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = product::with(['brand', 'unit', 'drugform', 'productType'])
            ->where('status', true)
            ->where('is_inventory', false);

        if (!empty($search) && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', fn($q) => $q->where('brandname', 'like', "%{$search}%"));
            } elseif ($column === 'type_name') {
                $query->whereHas('productType', fn($q) => $q->where('type_name', 'like', "%{$search}%"));
            } else {
                $query->where($column, 'like', "%{$search}%");
            }
        }

        $products = $query->orderBy('productname')->paginate(15)->through(function ($p) {
            $parts = [$p->productname];
            if ($p->drugform) $parts[] = $p->drugform->drugformname;
            if ($p->unit)     $parts[] = strtolower($p->unit->pos_unit) . ' (pcs)';
            $displayName = implode(' ', $parts);
            if ($p->brand) $displayName .= ' (' . $p->brand->brandname . ')';

            $p->display_name  = $displayName;
            $p->brand_name    = $p->brand?->brandname ?? 'N/A';
            $p->type_name     = $p->productType?->type_name ?? 'N/A';
            $p->generic_text  = $p->isgeneric ? 'Generic' : 'Branded';
            return $p;
        });

        $columns = [
            ['accessorKey' => 'id',           'header' => 'ID',           'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'generic_text', 'header' => 'TYPE',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'type_name',    'header' => 'CATEGORY',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'display_name', 'header' => 'PRODUCT NAME', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'productname',  'header' => 'PRODUCT NAME', 'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'brand_name',   'header' => 'BRAND',        'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'created_at',   'header' => 'CREATED AT',   'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('PosItems/PosItemsIndex', [
            'products' => $products,
            'columns'  => $columns,
        ]);
    }

    // Creates a POS-only product; excluded from main inventory via is_inventory=0
    public function storePosItem(Request $request)
    {
        $validated = $request->validate([
            'productname'     => 'required|string|max:255',
            'brand_id'        => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'drugform_id'     => 'nullable|exists:drugforms,id',
            'isgeneric'       => 'boolean',
        ]);

        $product = product::create([
            ...$validated,
            'is_inventory' => false,
            'created_by'   => $request->user()->id,
        ]);

        $product->load(['brand', 'unit', 'drugform']);

        $parts = [$product->productname];
        if ($product->drugform) $parts[] = $product->drugform->drugformname;
        if ($product->unit)     $parts[] = strtolower($product->unit->pos_unit) . ' (pcs)';
        $displayName = implode(' ', $parts);
        if ($product->brand) $displayName .= ' (' . $product->brand->brandname . ')';

        return response()->json([
            'product' => array_merge($product->toArray(), ['display_name' => $displayName]),
        ], 201);
    }

    public function updatePosItem(Request $request, product $product)
    {
        $validated = $request->validate([
            'productname'     => 'required|string|max:255',
            'brand_id'        => 'nullable|exists:brands,id',
            'product_unit_id' => 'required|exists:product_units,id',
            'product_type_id' => 'required|exists:product_types,id',
            'drugform_id'     => 'nullable|exists:drugforms,id',
            'isgeneric'       => 'boolean',
        ]);

        $product->update(array_merge($validated, ['updated_by' => $request->user()->id]));

        return response()->json(['success' => true]);
    }

    public function destroyPosItem(Request $request, product $product)
    {
        $product->update(['status' => false, 'updated_by' => $request->user()->id]);

        return response()->json(['success' => true]);
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

    public function storeLot(Request $request, product $product)
    {
        $validated = $request->validate([
            'lot_number'      => 'required|string|max:100',
            'expiration_date' => 'required|date',
            'quantity'        => 'required|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::table('product_lots')->updateOrInsert(
            [
                'product_id' => $product->id,
                'lot_number' => $validated['lot_number'],
            ],
            [
                'expiration_date' => $validated['expiration_date'],
                'quantity'        => $validated['quantity'],
                'created_by'      => $request->user()->id,
                'updated_by'      => $request->user()->id,
                'updated_at'      => now(),
                'created_at'      => now(),
            ]
        );

        return redirect()->route('products.index')->with('success', 'Lot added successfully!');
    }

    public function getLots(product $product)
    {
        $lots = \Illuminate\Support\Facades\DB::table('product_lots')
            ->where('product_id', $product->id)
            ->orderBy('expiration_date')
            ->get()
            ->map(fn($lot) => [
                'id'              => $lot->id,
                'lot_number'      => $lot->lot_number,
                'expiration_date' => \Carbon\Carbon::parse($lot->expiration_date)->format('m-d-Y'),
                'expiration_raw'  => $lot->expiration_date,
                'quantity'        => (float) $lot->quantity,
                'is_expired'      => \Carbon\Carbon::parse($lot->expiration_date)->isPast(),
            ]);

        return response()->json(['lots' => $lots]);
    }

    public function updateLot(Request $request, product $product, int $lot)
    {
        $validated = $request->validate([
            'lot_number'      => 'required|string|max:100',
            'expiration_date' => 'required|date',
        ]);

        \Illuminate\Support\Facades\DB::table('product_lots')
            ->where('id', $lot)
            ->where('product_id', $product->id)
            ->update([
                'lot_number'      => $validated['lot_number'],
                'expiration_date' => $validated['expiration_date'],
                'updated_by'      => $request->user()->id,
                'updated_at'      => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function destroyLot(Request $request, product $product, int $lot)
    {
        \Illuminate\Support\Facades\DB::table('product_lots')
            ->where('id', $lot)
            ->where('product_id', $product->id)
            ->delete();

        return redirect()->route('products.index')->with('success', 'Lot removed.');
    }

    public function multiplier(product $product)
    {
        $product->load('unit');
        return response()->json([
            'multiplier' => $product->unit?->multiplier ?? 1,
        ]);
    }

    public function productLots(product $product, Request $request)
    {
        $query = ProductLot::where('product_id', $product->id);

        if (!$request->boolean('include_empty')) {
            $query->where('quantity', '>', 0);
        }

        $lots = $query->orderBy('expiration_date')
            ->get()
            ->map(fn($lot) => [
                'value'           => (string) $lot->id,
                'label'           => $lot->lot_number . ' — exp: ' . Carbon::parse($lot->expiration_date)->format('m/d/Y') . ' (qty: ' . $lot->quantity . ')',
                'lot_number'      => $lot->lot_number,
                'expiration_date' => $lot->expiration_date,
                'available_qty'   => (float) $lot->quantity,
                'quantity'        => (float) $lot->quantity,
            ]);

        return response()->json(['lots' => $lots]);
    }

    public function history(product $product)
    {
        $product->load(['unit', 'brand', 'drugform']);

        $parts = [$product->productname];
        if ($product->drugform) $parts[] = $product->drugform->drugformname;
        if ($product->unit)     $parts[] = $product->unit->unit_name;
        $displayName = implode(' ', $parts);
        if ($product->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

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

        // Carry items (OUT) — products assigned to a sales agent
        $carryReturnTotals = \DB::table('carry_item_returns')
            ->where('product_id', $product->id)
            ->whereIn('carry_item_id', function ($sub) {
                $sub->select('id')->from('carry_items')->where('status', 'active');
            })
            ->selectRaw('carry_item_id, lot_id, SUM(quantity) as total')
            ->groupBy('carry_item_id', 'lot_id')
            ->get()
            ->keyBy(fn($r) => $r->carry_item_id . '-' . $r->lot_id);

        $carryItems = \App\Models\CarryItemDetail::with(['carryItem.salesAgent'])
            ->where('product_id', $product->id)
            ->whereHas('carryItem', fn($q) => $q->where('status', 'active'))
            ->get()
            ->map(function ($detail) use ($initialDate, $carryReturnTotals) {
                $date = $detail->carryItem?->carry_date
                    ? \Carbon\Carbon::parse($detail->carryItem->carry_date)->startOfDay()
                    : $detail->created_at;
                $agent = $detail->carryItem?->salesAgent?->name ?? 'N/A';
                $returned = (float) ($carryReturnTotals->get($detail->carry_item_id . '-' . $detail->lot_id)?->total ?? 0);
                return [
                    'date'           => $date,
                    'type'           => 'OUT',
                    'reference'      => 'Carry #' . $detail->carry_item_id,
                    'party'          => $agent . '/ CARRY STOCKS',
                    'invoice_no'     => '—',
                    'qty'            => (float) $detail->quantity + $returned,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        // RGS items (IN) — stock returned from a customer
        $rgsItems = \App\Models\ReturnGoodStockItem::with([
            'returnGoodStock.customer',
            'returnGoodStock.salesOrder.customerSalesAccount.customer',
            'returnGoodStock.salesOrder.customerSalesAccount.salesAccount',
        ])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $rgs  = $item->returnGoodStock;
                $date = $rgs?->rgs_date
                    ? \Carbon\Carbon::parse($rgs->rgs_date)->startOfDay()
                    : $item->created_at;

                // Resolve customer from SO path first, then fall back to direct customer_id
                $csa      = $rgs?->salesOrder?->customerSalesAccount;
                $customer = $csa?->customer ?? $rgs?->customer;
                $account  = $csa?->salesAccount;

                $customerName = $customer
                    ? ($customer->is_drugstore
                        ? strtoupper($customer->company)
                        : trim(strtoupper($customer->last_name) . ', ' . strtoupper($customer->first_name)))
                    : 'N/A';
                $party = 'RGS - ' . ($account ? strtoupper($account->account_name) . ' - ' : '') . $customerName;
                return [
                    'date'           => $date,
                    'type'           => 'IN',
                    'reference'      => 'RGS #' . $rgs?->id,
                    'party'          => $party,
                    'invoice_no'     => $rgs?->salesOrder?->invoice_no ?? '—',
                    'qty'            => $item->quantity,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        // Return to supplier items (OUT) — stock sent back to supplier
        $rtsItems = \App\Models\ReturnToSupplierItem::with([
            'returnToSupplier.supplier',
        ])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) use ($initialDate) {
                $rts  = $item->returnToSupplier;
                $date = $rts?->return_date
                    ? \Carbon\Carbon::parse($rts->return_date)->startOfDay()
                    : $item->created_at;
                $supplierName = strtoupper($rts?->supplier?->company ?? 'N/A');
                return [
                    'date'           => $date,
                    'type'           => 'OUT',
                    'reference'      => 'RTS #' . $rts?->id,
                    'party'          => 'RTS - ' . $supplierName,
                    'invoice_no'     => '—',
                    'qty'            => $item->quantity,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        // Carry item returns (IN) — products returned from sales agent to inventory
        $carryReturns = \App\Models\CarryItemReturn::with(['carryItem.salesAgent'])
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($ret) use ($initialDate) {
                $date = \Carbon\Carbon::parse($ret->return_date)->startOfDay();
                $agent = $ret->carryItem?->salesAgent?->name ?? 'N/A';
                return [
                    'date'           => $date,
                    'type'           => 'IN',
                    'reference'      => 'Carry Return #' . $ret->carry_item_id,
                    'party'          => $agent,
                    'invoice_no'     => '—',
                    'qty'            => $ret->quantity,
                    'before_initial' => $initialDate ? $date->lt($initialDate) : false,
                ];
            });

        $entries = $entries
            ->concat($deliveries)
            ->concat($sales)
            ->concat($carryItems)
            ->concat($carryReturns)
            ->concat($rgsItems)
            ->concat($rtsItems)
            ->sortBy('date')
            ->values();

        // Compute running balance — entries before initial get 0
        $balance = 0;
        $initialReached = false;
        $result = $entries->map(function ($entry) use (&$balance, &$initialReached) {
            if ($entry['type'] === 'INITIAL') {
                $initialReached = true;
                $balance = (int) $entry['qty'];
                return array_merge($entry, [
                    'qty'     => (int) $entry['qty'],
                    'balance' => $balance,
                    'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
                ]);
            }

            if (!$initialReached || $entry['before_initial']) {
                return array_merge($entry, [
                    'qty'     => (int) $entry['qty'],
                    'balance' => 0,
                    'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
                ]);
            }

            $balance += $entry['type'] === 'IN' ? (int) $entry['qty'] : -(int) $entry['qty'];
            return array_merge($entry, [
                'qty'     => (int) $entry['qty'],
                'balance' => $balance,
                'date'    => \Carbon\Carbon::parse($entry['date'])->format('m-d-Y'),
            ]);
        });

        return response()->json([
            'product' => [
                'id'           => $product->id,
                'display_name' => $displayName,
                'initial_date' => $initialDate ? \Carbon\Carbon::parse($initialDate)->format('m-d-Y') : null,
                'product_qty'   => (int) ($product->product_qty ?? 0),
                'reorder_level' => (int) ($product->reorder_level ?? 0),
            ],
            'history' => $result,
        ]);
    }

    public function pricingHistory(product $product)
    {
        $product->load(['unit', 'brand', 'drugform']);

        $parts = [$product->productname];
        if ($product->drugform) $parts[] = $product->drugform->drugformname;
        if ($product->unit)     $parts[] = $product->unit->unit_name;
        $displayName = implode(' ', $parts);
        if ($product->brand) $displayName .= ' (' . $product->brand->brandname . ')';

        $rows = \App\Models\DeliveryItem::with('delivery.supplier')
            ->where('product_id', $product->id)
            ->get()
            ->map(function ($item) {
                $delivery = $item->delivery;
                return [
                    'date'          => $delivery?->delivery_date
                        ? \Carbon\Carbon::parse($delivery->delivery_date)->format('m-d-Y')
                        : '—',
                    'reference'     => $delivery?->invoice_no ?? '—',
                    'supplier'      => $delivery?->supplier?->company ?? '—',
                    'quantity'      => (float) $item->quantity_received,
                    'unit_price'    => number_format((float) $item->unit_price, 2),
                    'selling_price' => '—',
                ];
            })
            ->sortBy('date')
            ->values();

        return response()->json([
            'product' => ['display_name' => $displayName],
            'rows'    => $rows,
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

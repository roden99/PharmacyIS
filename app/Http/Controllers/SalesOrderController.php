<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = DB::table('sales_orders as so')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->select(
                'so.id',
                'so.invoice_no',
                'so.invoice_date',
                'so.delivery_date',
                // 'so.discount_percentage',
                'so.terms',
                'so.customer_sales_account_id',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'sa.account_name'
            );

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'customer_name') {
                $query->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $query->where('sa.account_name', 'like', "{$search}%");
            } else {
                $query->where("so.{$column}", 'like', "{$search}%");
            }
        }

        $orders = $query->orderByDesc('so.created_at')->paginate(15)->through(function ($item) {
            $customerName = $item->is_drugstore
                ? strtoupper($item->company)
                : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name));

            return [
                'id'                        => $item->id,
                'customer_sales_account_id' => $item->customer_sales_account_id,
                'customer_name'             => $customerName,
                'account_name'              => strtoupper($item->account_name),
                'invoice_no'                => $item->invoice_no ?? '',
                'invoice_date'              => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('m-d-Y') : null,
                'delivery_date'             => $item->delivery_date ? Carbon::parse($item->delivery_date)->format('m-d-Y') : null,
                // 'discount_percentage'       => $item->discount_percentage,
                'terms'                     => $item->terms ?? '',
            ];
        });

        $columns = [
            ['accessorKey' => 'id',                  'header' => 'ID',            'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'account_name',         'header' => 'ACCOUNT',       'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'customer_name',        'header' => 'CUSTOMER',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_no',           'header' => 'INVOICE NO.',   'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_date',         'header' => 'INVOICE DATE',  'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'delivery_date',        'header' => 'DELIVERY DATE', 'isVisible' => false, 'isParameter' => false],
            // ['accessorKey' => 'discount_percentage',  'header' => 'DISC %',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'terms',                'header' => 'TERMS',         'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('SalesOrders/SalesOrderIndex', [
            'orders'  => $orders,
            'columns' => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            'delivery_date'                 => 'nullable|date',
            // 'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|string|max:255',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount_percentage'   => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['created_by'] = $request->user()->id;

        $order = SalesOrder::create(collect($validated)->except('items')->toArray());

        foreach ($validated['items'] as $item) {
            $disc = $item['discount_percentage'] ?? 0;
            $totalPrice = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            SalesOrderItem::create([
                'sales_order_id'      => $order->id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['delivery_date'] ?? $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order created successfully!');
    }

    public function show(string $id)
    {
        $order = SalesOrder::with(['items.product.brand', 'items.product.unit', 'items.product.drugform'])->findOrFail($id);

        return response()->json([
            'order' => $order,
            'items' => $order->items->map(function ($item) {
                $product = $item->product;
                $parts = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'                  => $item->id,
                    'product_id'          => (string) $item->product_id,
                    'product_name'        => $displayName ?: ('Product #' . $item->product_id),
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                ];
            }),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            'delivery_date'                 => 'nullable|date',
            'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|string|max:255',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount_percentage'   => 'nullable|numeric|min:0|max:100',
        ]);

        $order = SalesOrder::with('items')->findOrFail($id);

        // Reverse old item quantities back to stock
        foreach ($order->items as $oldItem) {
            $product = product::find($oldItem->product_id);
            if ($product && $product->is_inventory) {
                $oldDocDate = $order->delivery_date ?? $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($oldDocDate && Carbon::parse($oldDocDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $oldItem->quantity);
                }
            }
        }

        $order->items()->delete();

        $order->update([
            'customer_sales_account_id' => $validated['customer_sales_account_id'],
            'invoice_no'                => $validated['invoice_no'],
            'invoice_date'              => $validated['invoice_date'],
            'delivery_date'             => $validated['delivery_date'],
            'discount_percentage'       => $validated['discount_percentage'] ?? 0,
            'terms'                     => $validated['terms'] ?? null,
            'updated_by'                => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $disc = $item['discount_percentage'] ?? 0;
            $totalPrice = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            SalesOrderItem::create([
                'sales_order_id'      => $order->id,
                'product_id'          => $item['product_id'],
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['delivery_date'] ?? $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order updated successfully!');
    }

    public function destroy(string $id)
    {
        $order = SalesOrder::with('items')->findOrFail($id);

        // Restore stock
        foreach ($order->items as $item) {
            $product = product::find($item->product_id);
            if ($product && $product->is_inventory) {
                $docDate = $order->delivery_date ?? $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $item->quantity);
                }
            }
        }

        $order->delete();

        return redirect()->route('sales-orders.index')->with('success', 'Sales order deleted successfully!');
    }
}

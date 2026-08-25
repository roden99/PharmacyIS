<?php

namespace App\Http\Controllers;

use App\Models\ReturnGoodStock;
use App\Models\ReturnGoodStockItem;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\ProductLot;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReturnGoodStockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = DB::table('return_good_stocks as rgs')
            ->leftJoin('sales_orders as so', 'so.id', '=', 'rgs.sales_order_id')
            ->leftJoin('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->leftJoin('customers as c_so', 'c_so.id', '=', 'csa.customer_id')
            ->leftJoin('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoin('customers as c_direct', 'c_direct.id', '=', 'rgs.customer_id')
            ->leftJoinSub(
                DB::table('return_good_stock_items')
                    ->select('return_good_stock_id', DB::raw('COUNT(*) as items_count'))
                    ->groupBy('return_good_stock_id'),
                'ri',
                'ri.return_good_stock_id',
                '=',
                'rgs.id'
            )
            ->select(
                'rgs.id',
                'rgs.rgs_date',
                'rgs.notes',
                'rgs.sales_order_id',
                'so.invoice_no',
                DB::raw('COALESCE(c_so.first_name,  c_direct.first_name)  as first_name'),
                DB::raw('COALESCE(c_so.last_name,   c_direct.last_name)   as last_name'),
                DB::raw('COALESCE(c_so.company,     c_direct.company)     as company'),
                DB::raw('COALESCE(c_so.is_drugstore,c_direct.is_drugstore) as is_drugstore'),
                'sa.account_name',
                DB::raw('COALESCE(ri.items_count, 0) as items_count')
            );

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'invoice_no') {
                $query->where('so.invoice_no', 'like', "{$search}%");
            } elseif ($column === 'customer_name') {
                $query->where(function ($q) use ($search) {
                    $q->where('c_so.last_name',    'like', "{$search}%")
                        ->orWhere('c_so.company',    'like', "{$search}%")
                        ->orWhere('c_direct.last_name', 'like', "{$search}%")
                        ->orWhere('c_direct.company',   'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $query->where('sa.account_name', 'like', "{$search}%");
            }
        }

        $records = $query->orderByDesc('rgs.rgs_date')->paginate(15);
        $records->through(function ($item) {
            $isDrugstore = (bool) $item->is_drugstore;
            $item->customer_name = $isDrugstore
                ? strtoupper($item->company ?? '')
                : trim(strtoupper($item->last_name ?? '') . ', ' . strtoupper($item->first_name ?? ''));
            $item->account_name = $item->account_name ? strtoupper($item->account_name) : '—';
            $item->rgs_date     = $item->rgs_date
                ? Carbon::parse($item->rgs_date)->format('m-d-Y')
                : null;
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id',            'header' => 'ID',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'rgs_date',      'header' => 'RGS DATE',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'invoice_no',    'header' => 'INVOICE NO.',  'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'customer_name', 'header' => 'CUSTOMER',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'account_name',  'header' => 'ACCOUNT',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'items_count',   'header' => 'ITEMS',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',         'header' => 'NOTES',        'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('ReturnGoodStock/ReturnGoodStockIndex', [
            'records' => $records,
            'columns' => $columns,
        ]);
    }

    public function show(string $id)
    {
        $rgs = ReturnGoodStock::with([
            'items.product.brand',
            'items.product.unit',
            'items.product.drugform',
            'items.lot',
            'customer',
            'salesOrder.customerSalesAccount.customer',
            'salesOrder.customerSalesAccount.salesAccount',
        ])->findOrFail($id);

        $so           = $rgs->salesOrder;
        $customerName = '—';
        $accountName  = '—';
        $invoiceNo    = null;

        if ($so) {
            $csa          = $so->customerSalesAccount;
            $c            = $csa->customer;
            $customerName = $c->is_drugstore
                ? strtoupper($c->company)
                : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name));
            $accountName  = strtoupper($csa->salesAccount->account_name);
            $invoiceNo    = $so->invoice_no;
        } elseif ($rgs->customer) {
            $c            = $rgs->customer;
            $customerName = $c->is_drugstore
                ? strtoupper($c->company)
                : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name));
        }

        return response()->json([
            'rgs' => [
                'id'              => $rgs->id,
                'rgs_date'        => $rgs->rgs_date,
                'notes'           => $rgs->notes,
                'sales_order_id'  => $so?->id,
                'invoice_no'      => $invoiceNo,
                'customer_name'   => $customerName,
                'account_name'    => $accountName,
            ],
            'items' => $rgs->items->map(function ($item) {
                $product = $item->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'          => $item->id,
                    'product_id'  => (string) $item->product_id,
                    'product_name' => $displayName ?: ('Product #' . $item->product_id),
                    'lot_id'      => $item->lot_id ? (string) $item->lot_id : null,
                    'lot_number'  => $item->lot?->lot_number,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                ];
            }),
        ]);
    }

    public function storeStandalone(Request $request)
    {
        $validated = $request->validate([
            'customer_id'               => 'required|exists:customers,id',
            'customer_sales_account_id' => 'nullable|exists:customer_sales_account,id',
            'rgs_date'                  => 'required|date',
            'notes'                     => 'nullable|string|max:500',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.lot_number'        => 'nullable|string|max:100',
            'items.*.expiration_date'   => 'nullable|date',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.unit_price'        => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $rgs = ReturnGoodStock::create([
                'customer_id'               => $validated['customer_id'],
                'customer_sales_account_id' => $validated['customer_sales_account_id'] ?? null,
                'sales_order_id'            => null,
                'rgs_date'                  => $validated['rgs_date'],
                'notes'                     => $validated['notes'] ?? null,
                'created_by'                => $request->user()->id,
                'updated_by'                => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $lotId = null;

                if (!empty($item['lot_number'])) {
                    $existing = DB::table('product_lots')
                        ->where('product_id', $item['product_id'])
                        ->where('lot_number', $item['lot_number'])
                        ->first();

                    if ($existing) {
                        $lotId = $existing->id;
                    } else {
                        $lotId = DB::table('product_lots')->insertGetId([
                            'product_id'      => $item['product_id'],
                            'lot_number'      => $item['lot_number'],
                            'expiration_date' => $item['expiration_date'] ?? null,
                            'quantity'        => 0,
                            'created_by'      => $request->user()->id,
                            'updated_by'      => $request->user()->id,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }

                ReturnGoodStockItem::create([
                    'return_good_stock_id' => $rgs->id,
                    'product_id'           => $item['product_id'],
                    'lot_id'               => $lotId,
                    'quantity'             => $item['quantity'],
                    'unit_price'           => $item['unit_price'] ?? 0,
                    'created_by'           => $request->user()->id,
                    'updated_by'           => $request->user()->id,
                ]);

                $this->applyInventory($item['product_id'], $lotId, $item['quantity'], $validated['rgs_date'], '+');
            }
        });

        return response()->json(['message' => 'Return Good Stock recorded successfully.'], 201);
    }

    public function store(Request $request, string $salesOrderId)
    {
        $validated = $request->validate([
            'rgs_date'           => 'required|date',
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.lot_id'     => 'nullable|exists:product_lots,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $order = SalesOrder::findOrFail($salesOrderId);

        // Unit prices are always sourced from the original sales order, not the client
        $soItemPrices = DB::table('sales_order_items')
            ->where('sales_order_id', $order->id)
            ->get()
            ->keyBy(fn($i) => $i->product_id . '|' . ($i->lot_id ?? ''));

        DB::transaction(function () use ($validated, $order, $request, $soItemPrices) {
            $rgs = ReturnGoodStock::create([
                'sales_order_id' => $order->id,
                'rgs_date'       => $validated['rgs_date'],
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => $request->user()->id,
                'updated_by'     => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $soKey     = $item['product_id'] . '|' . ($item['lot_id'] ?? '');
                $unitPrice = $soItemPrices->get($soKey)?->unit_price
                    ?? $soItemPrices->firstWhere('product_id', $item['product_id'])?->unit_price
                    ?? 0;

                ReturnGoodStockItem::create([
                    'return_good_stock_id' => $rgs->id,
                    'product_id'           => $item['product_id'],
                    'lot_id'               => $item['lot_id'] ?? null,
                    'quantity'             => $item['quantity'],
                    'unit_price'           => $unitPrice,
                    'created_by'           => $request->user()->id,
                    'updated_by'           => $request->user()->id,
                ]);

                $this->applyInventory($item['product_id'], $item['lot_id'] ?? null, $item['quantity'], $validated['rgs_date'], '+');
            }
        });

        return redirect()->route('sales-orders.index')->with('success', 'Return Good Stock recorded successfully!');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'rgs_date'           => 'required|date',
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.lot_id'     => 'nullable|exists:product_lots,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $rgs = ReturnGoodStock::with('items')->findOrFail($id);

        // Unit prices are always sourced from the original sales order, not the client
        $soItemPrices = DB::table('sales_order_items')
            ->where('sales_order_id', $rgs->sales_order_id)
            ->get()
            ->keyBy(fn($i) => $i->product_id . '|' . ($i->lot_id ?? ''));

        DB::transaction(function () use ($rgs, $validated, $request, $soItemPrices) {
            // Reverse old items from inventory
            foreach ($rgs->items as $old) {
                $this->applyInventory($old->product_id, $old->lot_id, $old->quantity, $rgs->rgs_date, '-');
            }

            $rgs->items()->delete();

            $rgs->update([
                'rgs_date'   => $validated['rgs_date'],
                'notes'      => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $soKey     = $item['product_id'] . '|' . ($item['lot_id'] ?? '');
                $unitPrice = $soItemPrices->get($soKey)?->unit_price
                    ?? $soItemPrices->firstWhere('product_id', $item['product_id'])?->unit_price
                    ?? 0;

                ReturnGoodStockItem::create([
                    'return_good_stock_id' => $rgs->id,
                    'product_id'           => $item['product_id'],
                    'lot_id'               => $item['lot_id'] ?? null,
                    'quantity'             => $item['quantity'],
                    'unit_price'           => $unitPrice,
                    'created_by'           => $request->user()->id,
                    'updated_by'           => $request->user()->id,
                ]);

                $this->applyInventory($item['product_id'], $item['lot_id'] ?? null, $item['quantity'], $validated['rgs_date'], '+');
            }
        });

        return redirect()->route('return-good-stocks.index')->with('success', 'Return Good Stock updated successfully!');
    }

    public function destroy(string $id)
    {
        $rgs = ReturnGoodStock::with('items')->findOrFail($id);

        DB::transaction(function () use ($rgs) {
            foreach ($rgs->items as $item) {
                $this->applyInventory($item->product_id, $item->lot_id, $item->quantity, $rgs->rgs_date, '-');
            }
            $rgs->items()->delete();
            $rgs->delete();
        });

        return response()->json(['message' => 'Return Good Stock deleted successfully.']);
    }

    // +/- inventory qty respecting initial_date
    private function applyInventory(int $productId, ?int $lotId, int $qty, string $date, string $direction): void
    {
        $product = product::find($productId);
        if (!$product || !$product->is_inventory) return;

        $afterInit = !$product->initial_date
            || Carbon::parse($date)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay());

        if (!$afterInit) return;

        if ($direction === '+') {
            $product->increment('product_qty', $qty);
            if ($lotId) DB::table('product_lots')->where('id', $lotId)->increment('quantity', $qty);
        } else {
            $product->decrement('product_qty', $qty);
            if ($lotId) DB::table('product_lots')->where('id', $lotId)->decrement('quantity', $qty);
        }
    }
}

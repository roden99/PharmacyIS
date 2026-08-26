<?php

namespace App\Http\Controllers;

use App\Models\ReturnToSupplier;
use App\Models\ReturnToSupplierItem;
use App\Models\ProductLot;
use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReturnToSupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = DB::table('return_to_suppliers as rts')
            ->join('suppliers as s', 's.id', '=', 'rts.supplier_id')
            ->leftJoinSub(
                DB::table('return_to_supplier_items')
                    ->select('return_to_supplier_id', DB::raw('COUNT(*) as items_count'))
                    ->groupBy('return_to_supplier_id'),
                'ri',
                'ri.return_to_supplier_id',
                '=',
                'rts.id'
            )
            ->select(
                'rts.id',
                'rts.return_date',
                'rts.notes',
                's.company as supplier_name',
                DB::raw('COALESCE(ri.items_count, 0) as items_count')
            );

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'supplier_name') {
                $query->where('s.company', 'like', "{$search}%");
            }
        }

        $records = $query->orderByDesc('rts.return_date')->paginate(15);
        $records->through(function ($item) {
            $item->supplier_name = strtoupper($item->supplier_name);
            $item->return_date   = $item->return_date
                ? Carbon::parse($item->return_date)->format('m-d-Y')
                : null;
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id',            'header' => 'ID',          'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'return_date',   'header' => 'RETURN DATE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'supplier_name', 'header' => 'SUPPLIER',    'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'items_count',   'header' => 'ITEMS',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',         'header' => 'NOTES',       'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('ReturnToSupplier/ReturnToSupplierIndex', [
            'records' => $records,
            'columns' => $columns,
        ]);
    }

    public function show(string $id)
    {
        $rts = ReturnToSupplier::with([
            'supplier',
            'items.product.brand',
            'items.product.unit',
            'items.product.drugform',
            'items.lot',
        ])->findOrFail($id);

        return response()->json([
            'rts' => [
                'id'            => $rts->id,
                'return_date'   => $rts->return_date,
                'notes'         => $rts->notes,
                'supplier_name' => strtoupper($rts->supplier?->company ?? ''),
            ],
            'items' => $rts->items->map(function ($item) {
                $product = $item->product;
                $parts   = [$product?->productname];
                if ($product?->drugform) $parts[] = $product->drugform->drugformname;
                if ($product?->unit)     $parts[] = $product->unit->unit_name;
                $displayName = implode(' ', array_filter($parts));
                if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

                return [
                    'id'              => $item->id,
                    'product_id'      => (string) $item->product_id,
                    'product_name'    => $displayName ?: ('Product #' . $item->product_id),
                    'lot_id'          => $item->lot_id ? (string) $item->lot_id : null,
                    'lot_number'      => $item->lot?->lot_number,
                    'expiration_date' => $item->lot?->expiration_date,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'return_date'        => 'required|date',
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.lot_id'     => 'required|exists:product_lots,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $rts = ReturnToSupplier::create([
                'supplier_id' => $validated['supplier_id'],
                'return_date' => $validated['return_date'],
                'notes'       => $validated['notes'] ?? null,
                'created_by'  => $request->user()->id,
                'updated_by'  => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                ReturnToSupplierItem::create([
                    'return_to_supplier_id' => $rts->id,
                    'product_id'            => $item['product_id'],
                    'lot_id'                => $item['lot_id'],
                    'quantity'              => $item['quantity'],
                    'unit_price'            => $item['unit_price'] ?? 0,
                    'created_by'            => $request->user()->id,
                    'updated_by'            => $request->user()->id,
                ]);

                $this->applyInventory($item['product_id'], $item['lot_id'], $item['quantity'], $validated['return_date'], '-');
            }
        });

        return response()->json(['message' => 'Return to supplier recorded successfully.'], 201);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'return_date'        => 'required|date',
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.lot_id'     => 'required|exists:product_lots,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $rts = ReturnToSupplier::with('items')->findOrFail($id);

        DB::transaction(function () use ($rts, $validated, $request) {
            // Reverse old inventory before replacing items
            foreach ($rts->items as $old) {
                $this->applyInventory($old->product_id, $old->lot_id, $old->quantity, $rts->return_date, '+');
            }
            $rts->items()->delete();

            $rts->update([
                'return_date' => $validated['return_date'],
                'notes'       => $validated['notes'] ?? null,
                'updated_by'  => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                ReturnToSupplierItem::create([
                    'return_to_supplier_id' => $rts->id,
                    'product_id'            => $item['product_id'],
                    'lot_id'                => $item['lot_id'],
                    'quantity'              => $item['quantity'],
                    'unit_price'            => $item['unit_price'] ?? 0,
                    'created_by'            => $request->user()->id,
                    'updated_by'            => $request->user()->id,
                ]);
                $this->applyInventory($item['product_id'], $item['lot_id'], $item['quantity'], $validated['return_date'], '-');
            }
        });

        return response()->json(['message' => 'Return to supplier updated successfully.']);
    }

    public function destroy(string $id)
    {
        $rts = ReturnToSupplier::with('items')->findOrFail($id);

        DB::transaction(function () use ($rts) {
            foreach ($rts->items as $item) {
                $this->applyInventory($item->product_id, $item->lot_id, $item->quantity, $rts->return_date, '+');
            }
            $rts->items()->delete();
            $rts->delete();
        });

        return response()->json(['message' => 'Return to supplier deleted and inventory reversed.']);
    }

    // Mirrors ReturnGoodStockController logic — direction '-' decrements, '+' reverses
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

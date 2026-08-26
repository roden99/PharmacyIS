<?php

namespace App\Http\Controllers;

use App\Models\CarryItem;
use App\Models\CarryItemDetail;
use App\Models\CarryItemReturn;
use App\Models\product;
use App\Models\ProductLot;
use Illuminate\Http\Request;

class CarryItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = CarryItem::with(['salesAgent'])->withCount('details');

        if (!empty($search) && !empty($column)) {
            if ($column === 'sales_agent_name') {
                $query->whereHas('salesAgent', fn($q) => $q->where('name', 'like', "%{$search}%"));
            }
        }

        $carryItems = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($item) {
            $item->sales_agent_name = $item->salesAgent?->name ?? 'N/A';
            return $item;
        });

        $columns = [
            ['accessorKey' => 'id',               'header' => 'ID',           'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'sales_agent_name',  'header' => 'SALES AGENT',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'carry_date',        'header' => 'CARRY DATE',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'reference_number',  'header' => 'REFERENCE NO.',    'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'notes',             'header' => 'NOTES',            'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'details_count',     'header' => 'ITEMS',            'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('CarryItems/CarryItemIndex', [
            'carryItems' => $carryItems,
            'columns'    => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_agent_id'         => 'required|exists:sales_agents,id',
            'carry_date'             => 'required|date',
            'reference_number'       => 'nullable|string|max:100',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.lot_id'         => 'required|exists:product_lots,id',
            'items.*.quantity'       => 'required|numeric|min:0.0001',
        ]);

        $carryItem = CarryItem::create([
            'sales_agent_id'   => $validated['sales_agent_id'],
            'carry_date'       => $validated['carry_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes'            => $validated['notes'] ?? null,
            'created_by'       => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            CarryItemDetail::create([
                'carry_item_id' => $carryItem->id,
                'product_id'    => $item['product_id'],
                'lot_id'        => $item['lot_id'] ?? null,
                'quantity'      => $item['quantity'],
                'created_by'    => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product) {
                $product->decrement('product_qty', $item['quantity']);
            }

            if (!empty($item['lot_id'])) {
                ProductLot::where('id', $item['lot_id'])->decrement('quantity', $item['quantity']);
            }
        }

        return redirect()->route('carry-items.index')->with('success', 'Carry items created successfully!');
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'sales_agent_id'         => 'required|exists:sales_agents,id',
            'carry_date'             => 'required|date',
            'reference_number'       => 'nullable|string|max:100',
            'notes'                  => 'nullable|string|max:500',
            'remove_detail_ids'          => 'nullable|array',
            'remove_detail_ids.*'        => 'exists:carry_item_details,id',
            'updated_items'              => 'nullable|array',
            'updated_items.*.detail_id'  => 'required_with:updated_items.*|exists:carry_item_details,id',
            'updated_items.*.quantity'   => 'required_with:updated_items.*|numeric|min:0.0001',
            'new_items'                  => 'nullable|array',
            'new_items.*.product_id' => 'required_with:new_items.*|exists:products,id',
            'new_items.*.lot_id'     => 'required_with:new_items.*|exists:product_lots,id',
            'new_items.*.quantity'   => 'required_with:new_items.*|numeric|min:0.0001',
        ]);

        $carryItem = CarryItem::findOrFail($id);

        \DB::transaction(function () use ($validated, $carryItem, $request) {
            $carryItem->update([
                'sales_agent_id'   => $validated['sales_agent_id'],
                'carry_date'       => $validated['carry_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'updated_by'       => $request->user()->id,
            ]);

            // Update quantity of existing items, adjusting inventory for the delta
            foreach ($validated['updated_items'] ?? [] as $upd) {
                $detail = CarryItemDetail::where('id', $upd['detail_id'])
                    ->where('carry_item_id', $carryItem->id)
                    ->lockForUpdate()
                    ->first();
                if (!$detail) continue;

                // How much has already been returned for this product+lot
                $totalReturned = (float) \DB::table('carry_item_returns')
                    ->where('carry_item_id', $carryItem->id)
                    ->where('product_id', $detail->product_id)
                    ->where('lot_id', $detail->lot_id)
                    ->sum('quantity');

                $oldOriginal = (float) $detail->quantity + $totalReturned;
                $newOriginal = (float) $upd['quantity'];
                $delta       = $newOriginal - $oldOriginal;

                if ($delta > 0) {
                    product::where('id', $detail->product_id)->decrement('product_qty', $delta);
                    if ($detail->lot_id) {
                        ProductLot::where('id', $detail->lot_id)->decrement('quantity', $delta);
                    }
                } elseif ($delta < 0) {
                    product::where('id', $detail->product_id)->increment('product_qty', abs($delta));
                    if ($detail->lot_id) {
                        ProductLot::where('id', $detail->lot_id)->increment('quantity', abs($delta));
                    }
                }

                // Store the remaining qty (original minus already returned)
                $detail->update(['quantity' => max(0, $newOriginal - $totalReturned)]);
            }

            // Remove selected details and restore their inventory
            foreach ($validated['remove_detail_ids'] ?? [] as $detailId) {
                $detail = CarryItemDetail::where('id', $detailId)
                    ->where('carry_item_id', $carryItem->id)
                    ->first();
                if (!$detail) continue;
                product::where('id', $detail->product_id)->increment('product_qty', $detail->quantity);
                if ($detail->lot_id) {
                    ProductLot::where('id', $detail->lot_id)->increment('quantity', $detail->quantity);
                }
                $detail->delete();
            }

            // Add new items and decrement their inventory
            foreach ($validated['new_items'] ?? [] as $item) {
                CarryItemDetail::create([
                    'carry_item_id' => $carryItem->id,
                    'product_id'    => $item['product_id'],
                    'lot_id'        => $item['lot_id'] ?? null,
                    'quantity'      => $item['quantity'],
                    'created_by'    => $request->user()->id,
                ]);
                product::where('id', $item['product_id'])->decrement('product_qty', $item['quantity']);
                if (!empty($item['lot_id'])) {
                    ProductLot::where('id', $item['lot_id'])->decrement('quantity', $item['quantity']);
                }
            }
        });

        return redirect()->route('carry-items.index')->with('success', 'Carry item updated successfully.');
    }

    public function show(string $id)
    {
        $carryItem = CarryItem::with(['details.product.brand', 'details.product.unit', 'details.product.drugform', 'details.lot'])->findOrFail($id);

        // Sum returned quantities per product+lot to restore original carry qty
        $returnedMap = \DB::table('carry_item_returns')
            ->where('carry_item_id', $id)
            ->selectRaw('product_id, lot_id, SUM(quantity) as total')
            ->groupBy('product_id', 'lot_id')
            ->get()
            ->keyBy(fn($r) => $r->product_id . '-' . $r->lot_id);

        $existingKeys = $carryItem->details->map(fn($d) => $d->product_id . '-' . $d->lot_id)->flip();

        $items = $carryItem->details->map(function ($detail) use ($returnedMap) {
            $product = $detail->product;
            $parts   = [$product?->productname];
            if ($product?->drugform) $parts[] = $product->drugform->drugformname;
            if ($product?->unit)     $parts[] = $product->unit->unit_name;
            $displayName = implode(' ', array_filter($parts));
            if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

            return [
                'id'           => $detail->id,
                'product_name' => $displayName ?: ('Product #' . $detail->product_id),
                'lot_number'   => $detail->lot?->lot_number ?? '—',
                'quantity'     => (float) $detail->quantity + (float) ($returnedMap->get($detail->product_id . '-' . $detail->lot_id)?->total ?? 0),
            ];
        });

        // Reconstruct fully-returned items whose detail record was deleted
        foreach ($returnedMap as $key => $ret) {
            if ($existingKeys->has($key)) continue;

            $product = product::with(['brand', 'unit', 'drugform'])->find($ret->product_id);
            $parts   = [$product?->productname];
            if ($product?->drugform) $parts[] = $product->drugform->drugformname;
            if ($product?->unit)     $parts[] = $product->unit->unit_name;
            $displayName = implode(' ', array_filter($parts));
            if ($product?->brand)    $displayName .= ' (' . $product->brand->brandname . ')';

            $lot = ProductLot::find($ret->lot_id);
            $items->push([
                'id'           => null,
                'product_name' => $displayName ?: ('Product #' . $ret->product_id),
                'lot_number'   => $lot?->lot_number ?? '—',
                'quantity'     => (float) $ret->total,
            ]);
        }

        $returns = CarryItemReturn::with(['product.brand', 'product.unit', 'product.drugform'])
            ->where('carry_item_id', $id)
            ->orderBy('return_date')
            ->get()
            ->map(function ($ret) {
                $p     = $ret->product;
                $parts = [$p?->productname];
                if ($p?->drugform) $parts[] = $p->drugform->drugformname;
                if ($p?->unit)     $parts[] = $p->unit->unit_name;
                $name  = implode(' ', array_filter($parts));
                if ($p?->brand)    $name .= ' (' . $p->brand->brandname . ')';
                return [
                    'product_name' => $name ?: ('Product #' . $ret->product_id),
                    'quantity'     => (float) $ret->quantity,
                    'return_date'  => $ret->return_date,
                ];
            });

        return response()->json(['items' => $items, 'returns' => $returns]);
    }

    public function destroy(Request $request, string $id)
    {
        $carryItem = CarryItem::with('details')->findOrFail($id);

        foreach ($carryItem->details as $detail) {
            $product = product::find($detail->product_id);
            if ($product) {
                $product->increment('product_qty', $detail->quantity);
            }
            if ($detail->lot_id) {
                ProductLot::where('id', $detail->lot_id)->increment('quantity', $detail->quantity);
            }
        }

        $carryItem->delete();
    }

    public function returnItems(Request $request, string $id)
    {
        $validated = $request->validate([
            'returns'              => 'required|array|min:1',
            'returns.*.detail_id' => 'required|exists:carry_item_details,id',
            'returns.*.quantity'  => 'required|numeric|min:0.0001',
        ]);

        $carryItem = CarryItem::findOrFail($id);
        $userId    = $request->user()?->id;

        \DB::transaction(function () use ($validated, $carryItem, $userId) {
            foreach ($validated['returns'] as $ret) {
                $detail = CarryItemDetail::where('id', $ret['detail_id'])
                    ->where('carry_item_id', $carryItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $returnQty = min((float) $ret['quantity'], (float) $detail->quantity);

                // Restore product stock
                product::where('id', $detail->product_id)
                    ->increment('product_qty', $returnQty);

                // Restore lot stock
                if ($detail->lot_id) {
                    ProductLot::where('id', $detail->lot_id)
                        ->increment('quantity', $returnQty);
                }

                // Delete detail if fully returned, otherwise decrement
                if ((float) $detail->quantity <= $returnQty) {
                    $detail->delete();
                } else {
                    $detail->decrement('quantity', $returnQty);
                }

                CarryItemReturn::create([
                    'carry_item_id' => $carryItem->id,
                    'product_id'    => $detail->product_id,
                    'lot_id'        => $detail->lot_id,
                    'quantity'      => $returnQty,
                    'return_date'   => now()->toDateString(),
                    'returned_by'   => $userId,
                ]);
            }
        });

        return redirect()->route('carry-items.index')->with('success', 'Items returned to inventory successfully.');
    }

    public function returnDetail(Request $request, string $detailId)
    {
        $detail = CarryItemDetail::findOrFail($detailId);

        \DB::transaction(function () use ($detail, $request) {
            product::where('id', $detail->product_id)
                ->increment('product_qty', $detail->quantity);

            if ($detail->lot_id) {
                ProductLot::where('id', $detail->lot_id)
                    ->increment('quantity', $detail->quantity);
            }

            CarryItemReturn::create([
                'carry_item_id' => $detail->carry_item_id,
                'product_id'    => $detail->product_id,
                'lot_id'        => $detail->lot_id,
                'quantity'      => $detail->quantity,
                'return_date'   => now()->toDateString(),
                'returned_by'   => $request->user()?->id,
            ]);

            $detail->delete();
        });

        return redirect()->route('carry-items.index')->with('success', 'Item returned to inventory.');
    }
}

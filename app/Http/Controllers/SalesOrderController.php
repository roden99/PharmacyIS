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
        $filter = $request->input('filter');
        $account = $request->input('account');
        $customer = $request->input('customer');

        // ── Sales Orders ──────────────────────────────────────────────────────
        $soQuery = DB::table('sales_orders as so')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->select(
                'so.id',
                'so.invoice_no',
                'so.invoice_date',
                'so.terms',
                'so.customer_sales_account_id',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'sa.account_name'
            );

        if (!empty($account)) {
            $soQuery->where('sa.account_name', $account);
        }

        if (!empty($customer)) {
            $soQuery->where('c.id', $customer);
        }

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'customer_name') {
                $soQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $soQuery->where('sa.account_name', 'like', "{$search}%");
            } elseif ($column === 'invoice_no') {
                $soQuery->where('so.invoice_no', 'like', "{$search}%");
            }
        }

        $soRawRows = $soQuery->orderByDesc('so.invoice_date')->get();

        // Batch-fetch payment totals per SO
        $soIds = $soRawRows->pluck('id');
        $paymentsBySoId = DB::table('customer_payment_items as cpi')
            ->join('customer_payments as cp', 'cp.id', '=', 'cpi.customer_payment_id')
            ->whereIn('cpi.sales_order_id', $soIds)
            ->select(
                'cpi.sales_order_id',
                DB::raw('SUM(cpi.sub_amount) as total_paid'),
                DB::raw('MAX(cp.id) as last_pmt_id')
            )
            ->groupBy('cpi.sales_order_id')
            ->get()
            ->keyBy('sales_order_id');

        // Batch-fetch all individual payment rows per SO
        $soPaymentListById = DB::table('customer_payment_items as cpi')
            ->join('customer_payments as cp', 'cp.id', '=', 'cpi.customer_payment_id')
            ->whereIn('cpi.sales_order_id', $soIds)
            ->select('cpi.sales_order_id', 'cpi.sub_amount', 'cp.payment_date', 'cp.payment_method', 'cp.reference_no')
            ->orderBy('cp.payment_date')
            ->get()
            ->groupBy('sales_order_id');

        $soRows = $soRawRows->map(function ($item) use ($paymentsBySoId, $soPaymentListById) {
            $customerName = $item->is_drugstore
                ? strtoupper($item->company)
                : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name));

            $total = DB::table('sales_order_items')
                ->where('sales_order_id', $item->id)
                ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));

            $pmt = $paymentsBySoId->get($item->id);
            $totalFloat   = (float) $total;
            $totalPaid    = (float) ($pmt->total_paid ?? 0);
            $status       = $totalPaid >= $totalFloat && $totalFloat > 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid');
            $paymentList  = ($soPaymentListById->get($item->id) ?? collect())->map(fn($p) => [
                'amount'    => number_format((float) $p->sub_amount, 2, '.', ','),
                'date'      => $p->payment_date ? Carbon::parse($p->payment_date)->format('m-d-Y') : null,
                'method'    => $p->payment_method ?? 'Cash',
                'reference' => $p->reference_no ?? null,
            ])->values();

            return [
                'id'                        => $item->id,
                'entry_type'                => 'SO',
                'customer_sales_account_id' => $item->customer_sales_account_id,
                'customer_name'             => $customerName,
                'account_name'              => strtoupper($item->account_name),
                'invoice_no'                => $item->invoice_no ?? '',
                'invoice_date'              => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('m-d-Y') : null,
                'due_date'                  => ($item->invoice_date && $item->terms)
                    ? Carbon::parse($item->invoice_date)->addDays((int) $item->terms)->format('m-d-Y')
                    : null,
                'terms'                     => $item->terms !== null ? (int) $item->terms : null,
                'total_amount'              => number_format($totalFloat, 2, '.', ','),
                'payment_id'                => $pmt ? $item->id : null,
                'pmt_id'                    => $pmt?->last_pmt_id,
                'payment_status'            => $status,
                'payment_list'              => $paymentList,
                'payment_details'           => $pmt ? [
                    'amount'    => number_format($totalPaid, 2, '.', ','),
                    'date'      => null,
                    'method'    => null,
                    'reference' => null,
                ] : null,
            ];
        });

        // ── Manual Invoices ────────────────────────────────────────────────────
        $invQuery = DB::table('customer_account_invoices as i')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoinSub(
                DB::table('customer_payment_items')
                    ->whereNotNull('customer_account_invoice_id')
                    ->select('customer_account_invoice_id', DB::raw('SUM(sub_amount) as paid_amount'))
                    ->groupBy('customer_account_invoice_id'),
                'pmts',
                'pmts.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->leftJoinSub(
                DB::table('customer_payment_items as cpi2')
                    ->join('customer_payments as cp2', 'cp2.id', '=', 'cpi2.customer_payment_id')
                    ->whereNotNull('cpi2.customer_account_invoice_id')
                    ->select(
                        'cpi2.customer_account_invoice_id',
                        DB::raw('MAX(cp2.id) as last_pmt_id'),
                        DB::raw('MAX(cp2.payment_date) as pmt_date'),
                        DB::raw('MAX(cp2.payment_method) as pmt_method'),
                        DB::raw("GROUP_CONCAT(cp2.reference_no ORDER BY cp2.payment_date SEPARATOR ', ') as pmt_reference")
                    )
                    ->groupBy('cpi2.customer_account_invoice_id'),
                'last_pmt',
                'last_pmt.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->select(
                'i.id',
                'i.reference_no',
                'i.invoice_date',
                'i.terms',
                'i.amount',
                'i.customer_sales_account_id',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'sa.account_name',
                'pmts.paid_amount',
                'last_pmt.last_pmt_id',
                'last_pmt.pmt_date',
                'last_pmt.pmt_method',
                'last_pmt.pmt_reference'
            );

        if (!empty($account)) {
            $invQuery->where('sa.account_name', $account);
        }

        if (!empty($customer)) {
            $invQuery->where('c.id', $customer);
        }

        if (!empty($search) && strlen($search) >= 3) {
            if ($column === 'customer_name') {
                $invQuery->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "{$search}%")
                        ->orWhere('c.company', 'like', "{$search}%");
                });
            } elseif ($column === 'account_name') {
                $invQuery->where('sa.account_name', 'like', "{$search}%");
            } elseif ($column === 'invoice_no') {
                $invQuery->where('i.reference_no', 'like', "{$search}%");
            }
        }

        $invRawRows = $invQuery->orderByDesc('i.invoice_date')->get();

        // Batch-fetch all individual payment rows per invoice
        $invIds = $invRawRows->pluck('id');
        $invPaymentListById = DB::table('customer_payment_items as cpi')
            ->join('customer_payments as cp', 'cp.id', '=', 'cpi.customer_payment_id')
            ->whereIn('cpi.customer_account_invoice_id', $invIds)
            ->select('cpi.customer_account_invoice_id', 'cpi.sub_amount', 'cp.payment_date', 'cp.payment_method', 'cp.reference_no')
            ->orderBy('cp.payment_date')
            ->get()
            ->groupBy('customer_account_invoice_id');

        $invRows = $invRawRows->map(function ($item) use ($invPaymentListById) {
            $customerName = $item->is_drugstore
                ? strtoupper($item->company)
                : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name));

            $paymentList = ($invPaymentListById->get($item->id) ?? collect())->map(fn($p) => [
                'amount'    => number_format((float) $p->sub_amount, 2, '.', ','),
                'date'      => $p->payment_date ? Carbon::parse($p->payment_date)->format('m-d-Y') : null,
                'method'    => $p->payment_method ?? 'Cash',
                'reference' => $p->reference_no ?? null,
            ])->values();

            return [
                'id'                        => $item->id,
                'entry_type'                => 'INV',
                'customer_sales_account_id' => $item->customer_sales_account_id,
                'customer_name'             => $customerName,
                'account_name'              => strtoupper($item->account_name),
                'invoice_no'                => $item->reference_no ?? '',
                'invoice_date'              => $item->invoice_date ? Carbon::parse($item->invoice_date)->format('m-d-Y') : null,
                'due_date'                  => ($item->invoice_date && $item->terms)
                    ? Carbon::parse($item->invoice_date)->addDays((int) $item->terms)->format('m-d-Y')
                    : null,
                'terms'                     => $item->terms !== null ? (int) $item->terms : null,
                'total_amount'              => number_format((float) $item->amount, 2, '.', ','),
                'payment_id'                => null,
                'pmt_id'                    => $item->last_pmt_id ?? null,
                'payment_status'            => ($item->paid_amount ?? 0) >= $item->amount ? 'Paid' : (($item->paid_amount ?? 0) > 0 ? 'Partial' : 'Unpaid'),
                'payment_list'              => $paymentList,
                'payment_details'           => ($item->paid_amount ?? 0) > 0 ? [
                    'amount'    => number_format((float) $item->paid_amount, 2, '.', ','),
                    'date'      => null,
                    'method'    => null,
                    'reference' => null,
                ] : null,
            ];
        });

        // ── Merge, sort ───────────────────────────────────────────────────────
        $combined = $soRows->concat($invRows)
            ->sortByDesc(fn($r) => $r['invoice_date']
                ? Carbon::createFromFormat('m-d-Y', $r['invoice_date'])->timestamp
                : 0)
            ->values();

        // ── Apply filter ──────────────────────────────────────────────────────
        if ($filter) {
            $today     = Carbon::today();
            $weekLater = Carbon::today()->addDays(7);
            $combined  = $combined->filter(function ($r) use ($filter, $today, $weekLater) {
                $isPaid    = $r['payment_status'] === 'Paid';
                $isPartial = $r['payment_status'] === 'Partial';
                if ($filter === 'paid')   return $isPaid;
                if ($filter === 'unpaid') return !$isPaid;
                if ($isPaid || !$r['due_date']) return false;
                $due = Carbon::createFromFormat('m-d-Y', $r['due_date']);
                if ($filter === 'overdue')   return $due->lt($today);
                if ($filter === 'upcoming')  return $due->gte($today) && $due->lte($weekLater);
                return true;
            })->values();
        }

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paged = $combined->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $orders = new \Illuminate\Pagination\LengthAwarePaginator(
            $paged,
            $combined->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $columns = [
            ['accessorKey' => 'id',             'header' => 'ID',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'entry_type',      'header' => 'TYPE',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'account_name',    'header' => 'ACCOUNT',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'customer_name',   'header' => 'CUSTOMER',     'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_no',      'header' => 'INVOICE NO.',  'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'invoice_date',    'header' => 'INVOICE DATE', 'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'due_date',        'header' => 'DUE DATE',     'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'terms',           'header' => 'TERMS',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'total_amount',    'header' => 'TOTAL',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'payment_status',  'header' => 'STATUS',       'isVisible' => true,  'isParameter' => false],
        ];

        $accounts = DB::table('sales_accounts')
            ->orderBy('account_name')
            ->pluck('account_name');

        $customers = DB::table('customers as c')
            ->join('customer_sales_account as csa', 'csa.customer_id', '=', 'c.id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->select('c.id', 'c.first_name', 'c.last_name', 'c.company', 'c.is_drugstore', 'sa.account_name')
            ->distinct()
            ->orderBy('c.last_name')
            ->orderBy('c.company')
            ->get()
            ->map(fn($c) => [
                'value'   => (string) $c->id,
                'label'   => $c->is_drugstore
                    ? strtoupper($c->company)
                    : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name)),
                'account' => strtoupper($c->account_name),
            ]);

        return inertia('SalesOrders/SalesOrderIndex', [
            'orders'    => $orders,
            'columns'   => $columns,
            'filter'    => $filter,
            'account'   => $account,
            'accounts'  => $accounts,
            'customer'  => $customer,
            'customers' => $customers,
        ]);
    }

    public function overdueReport(Request $request)
    {
        $account = $request->input('account');
        $today   = Carbon::today();

        // ── Sales Orders (unpaid or partially paid) ───────────────────────────
        $soQuery = DB::table('sales_orders as so')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'so.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoinSub(
                DB::table('customer_payment_items')
                    ->whereNotNull('sales_order_id')
                    ->select('sales_order_id', DB::raw('SUM(sub_amount) as total_paid'))
                    ->groupBy('sales_order_id'),
                'so_paid',
                'so_paid.sales_order_id',
                '=',
                'so.id'
            )
            ->select(
                'so.id',
                'so.invoice_no',
                'so.invoice_date',
                'so.terms',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'c.address',
                'sa.account_name',
                'so_paid.total_paid'
            );

        if ($account) $soQuery->where('sa.account_name', $account);

        $soRows = $soQuery->get()->map(function ($item) use ($today) {
            if (!$item->invoice_date || $item->terms === null) return null;
            $due = Carbon::parse($item->invoice_date)->addDays((int) $item->terms);
            if ($due->gte($today)) return null;

            $total    = DB::table('sales_order_items')
                ->where('sales_order_id', $item->id)
                ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));
            $totalPaid = (float) ($item->total_paid ?? 0);
            $amount    = $total - $totalPaid;
            if ($amount <= 0) return null; // fully paid

            return [
                'customer_name' => $item->is_drugstore
                    ? strtoupper($item->company)
                    : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name)),
                'address'       => $item->address ?? '',
                'invoice_date'  => Carbon::parse($item->invoice_date)->format('m/d/Y'),
                'invoice_no'    => $item->invoice_no ?? '',
                'amount'        => round($amount, 2),
                'days_overdue'  => $due->diffInDays($today),
            ];
        })->filter()->values();

        // ── Manual Invoices (unpaid / partial) ───────────────────────────────
        $invQuery = DB::table('customer_account_invoices as i')
            ->join('customer_sales_account as csa', 'csa.id', '=', 'i.customer_sales_account_id')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoinSub(
                DB::table('customer_payment_items')
                    ->whereNotNull('customer_account_invoice_id')
                    ->select('customer_account_invoice_id', DB::raw('SUM(sub_amount) as paid_amount'))
                    ->groupBy('customer_account_invoice_id'),
                'pmts',
                'pmts.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->whereRaw('IFNULL(pmts.paid_amount, 0) < i.amount')
            ->select(
                'i.id',
                'i.reference_no as invoice_no',
                'i.invoice_date',
                'i.terms',
                'i.amount',
                'c.company',
                'c.first_name',
                'c.last_name',
                'c.is_drugstore',
                'c.address',
                'sa.account_name',
                'pmts.paid_amount'
            );

        if ($account) $invQuery->where('sa.account_name', $account);

        $invRows = $invQuery->get()->map(function ($item) use ($today) {
            if (!$item->invoice_date || $item->terms === null) return null;
            $due = Carbon::parse($item->invoice_date)->addDays((int) $item->terms);
            if ($due->gte($today)) return null;

            return [
                'customer_name' => $item->is_drugstore
                    ? strtoupper($item->company)
                    : trim(strtoupper($item->last_name) . ', ' . strtoupper($item->first_name)),
                'address'       => $item->address ?? '',
                'invoice_date'  => Carbon::parse($item->invoice_date)->format('m/d/Y'),
                'invoice_no'    => $item->invoice_no ?? '',
                'amount'        => round((float) $item->amount - (float) ($item->paid_amount ?? 0), 2),
                'days_overdue'  => $due->diffInDays($today),
            ];
        })->filter()->values();

        $rows = $soRows->concat($invRows)
            ->sortBy('customer_name')
            ->values();

        $accounts = DB::table('sales_accounts')->orderBy('account_name')->pluck('account_name');

        return inertia('SalesOrders/OverdueAccountReport', [
            'rows'     => $rows,
            'account'  => $account,
            'accounts' => $accounts,
            'month'    => $today->format('F Y'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            // 'delivery_date'              => 'nullable|date',
            // 'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|integer|min:0',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.lot_id'                => 'required|exists:product_lots,id',
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
                'lot_id'              => $item['lot_id'] ?? null,
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                    if (!empty($item['lot_id'])) {
                        DB::table('product_lots')
                            ->where('id', $item['lot_id'])
                            ->decrement('quantity', $item['quantity']);
                    }
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order created successfully!');
    }

    public function show(string $id)
    {
        $order = SalesOrder::with(['items.product.brand', 'items.product.unit', 'items.product.drugform', 'items.lot'])->findOrFail($id);

        $rgsRecords = DB::table('return_good_stocks as rgs')
            ->where('rgs.sales_order_id', $id)
            ->leftJoin(DB::raw('(SELECT return_good_stock_id, COUNT(*) as items_count FROM return_good_stock_items GROUP BY return_good_stock_id) as ri'), 'ri.return_good_stock_id', '=', 'rgs.id')
            ->select('rgs.id', 'rgs.rgs_date', 'rgs.notes', DB::raw('COALESCE(ri.items_count, 0) as items_count'))
            ->orderByDesc('rgs.rgs_date')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'rgs_date'    => $r->rgs_date ? \Carbon\Carbon::parse($r->rgs_date)->format('m-d-Y') : null,
                'notes'       => $r->notes,
                'items_count' => $r->items_count,
            ]);

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
                    'lot_id'              => $item->lot_id ? (string) $item->lot_id : null,
                    'lot_number'          => $item->lot?->lot_number,
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'discount_percentage' => $item->discount_percentage,
                    'initial_date'        => $product?->initial_date
                        ? \Carbon\Carbon::parse($product->initial_date)->format('Y-m-d')
                        : null,
                ];
            }),
            'rgs' => $rgsRecords,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'customer_sales_account_id'     => 'required|exists:customer_sales_account,id',
            'invoice_no'                    => 'nullable|string|max:255',
            'invoice_date'                  => 'nullable|date',
            // 'delivery_date'              => 'nullable|date',
            // 'discount_percentage'           => 'nullable|numeric|min:0|max:100',
            'terms'                         => 'nullable|integer|min:0',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.lot_id'                => 'required|exists:product_lots,id',
            'items.*.quantity'              => 'required|integer|min:1',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount_percentage'   => 'nullable|numeric|min:0|max:100',
        ]);

        $order = SalesOrder::with('items')->findOrFail($id);

        // Reverse old item quantities back to stock
        foreach ($order->items as $oldItem) {
            $product = product::find($oldItem->product_id);
            if ($product && $product->is_inventory) {
                $oldDocDate = $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($oldDocDate && Carbon::parse($oldDocDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $oldItem->quantity);
                    if ($oldItem->lot_id) {
                        DB::table('product_lots')
                            ->where('id', $oldItem->lot_id)
                            ->increment('quantity', $oldItem->quantity);
                    }
                }
            }
        }

        $order->items()->delete();

        $order->update([
            'customer_sales_account_id' => $validated['customer_sales_account_id'],
            'invoice_no'                => $validated['invoice_no'],
            'invoice_date'              => $validated['invoice_date'],
            // 'delivery_date'          => ...,
            // 'discount_percentage'       => $validated['discount_percentage'] ?? 0,
            'terms'                     => $validated['terms'] ?? null,
            'updated_by'                => $request->user()->id,
        ]);

        foreach ($validated['items'] as $item) {
            $disc = $item['discount_percentage'] ?? 0;
            $totalPrice = round($item['quantity'] * $item['unit_price'] * (1 - $disc / 100), 2);

            SalesOrderItem::create([
                'sales_order_id'      => $order->id,
                'product_id'          => $item['product_id'],
                'lot_id'              => $item['lot_id'] ?? null,
                'quantity'            => $item['quantity'],
                'unit_price'          => $item['unit_price'],
                'discount_percentage' => $disc,
                'total_price'         => $totalPrice,
                'created_by'          => $request->user()->id,
            ]);

            $product = product::find($item['product_id']);
            if ($product && $product->is_inventory) {
                $docDate = $validated['invoice_date'] ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->decrement('product_qty', $item['quantity']);
                    if (!empty($item['lot_id'])) {
                        DB::table('product_lots')
                            ->where('id', $item['lot_id'])
                            ->decrement('quantity', $item['quantity']);
                    }
                }
            }
        }

        return redirect()->route('sales-orders.index')->with('success', 'Sales order updated successfully!');
    }

    public function destroy(string $id)
    {
        $order = SalesOrder::with('items')->findOrFail($id);

        if ($order->customer_sales_account_id && DB::table('customer_payment_items')->where('sales_order_id', $order->id)->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete a paid sales order.']);
        }

        // Restore stock for each item before deleting
        foreach ($order->items as $item) {
            $product = product::find($item->product_id);
            if ($product && $product->is_inventory) {
                $docDate = $order->invoice_date ?? null;
                $afterInit = !$product->initial_date
                    || ($docDate && Carbon::parse($docDate)->startOfDay()->gte(Carbon::parse($product->initial_date)->startOfDay()));
                if ($afterInit) {
                    $product->increment('product_qty', $item->quantity);
                    if ($item->lot_id) {
                        DB::table('product_lots')
                            ->where('id', $item->lot_id)
                            ->increment('quantity', $item->quantity);
                    }
                }
            }
        }

        // Delete items explicitly first, then the order
        $order->items()->delete();
        $order->delete();

        return redirect()->route('sales-orders.index')->with('success', 'Sales order deleted successfully!');
    }
}

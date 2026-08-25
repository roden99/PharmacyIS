<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesAccount;
use App\Models\CustomerSalesAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAccountController extends Controller
{
    /**
     * Display a listing of customers with their corresponding sales account.
     */
    public function index(Request $request)
    {
        // JSON branch: used by SalesOrderForm combobox
        if ($request->expectsJson()) {
            $search    = $request->input('search', '');
            $includeId = $request->input('include_id');

            $query = DB::table('customer_sales_account as csa')
                ->join('customers as c', 'c.id', '=', 'csa.customer_id')
                ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
                ->where('c.status', 'active')
                ->select('csa.id', 'c.company', 'c.first_name', 'c.last_name', 'c.is_drugstore', 'sa.account_name', 'csa.discount_percentage');

            if ($includeId) {
                $query->where(function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->where('c.last_name', 'like', "%{$search}%")
                            ->orWhere('c.company', 'like', "%{$search}%");
                    }
                })->orWhere('csa.id', $includeId);
            } elseif (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('c.last_name', 'like', "%{$search}%")
                        ->orWhere('c.company', 'like', "%{$search}%");
                });
            }

            $accounts = $query->orderBy('sa.account_name')->orderBy('c.last_name')->limit(20)->get()
                ->map(fn($row) => [
                    'value'               => (string) $row->id,
                    'label'               => strtoupper($row->account_name) . ' - ' . (
                        $row->is_drugstore
                        ? strtoupper($row->company)
                        : trim(strtoupper($row->last_name) . ', ' . strtoupper($row->first_name))
                    ),
                    'discount_percentage' => (float) $row->discount_percentage,
                ]);

            return response()->json(['accounts' => $accounts]);
        }

        $search    = $request->input('search');
        $column    = $request->input('column');
        $accountId = $request->input('account');
        $type      = $request->input('type');

        // Pre-aggregated join subqueries replace correlated subqueries for performance
        $soTotal = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->select('so.customer_sales_account_id', DB::raw('SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as total'))
            ->groupBy('so.customer_sales_account_id');

        $invTotal = DB::table('customer_account_invoices')
            ->select('customer_sales_account_id', DB::raw('SUM(amount) as total'))
            ->groupBy('customer_sales_account_id');

        $rgsViaSO = DB::table('return_good_stocks as rgs')
            ->join('sales_orders as rso', 'rso.id', '=', 'rgs.sales_order_id')
            ->join('return_good_stock_items as ri', 'ri.return_good_stock_id', '=', 'rgs.id')
            ->whereNotNull('rgs.sales_order_id')
            ->select('rso.customer_sales_account_id', DB::raw('SUM(ri.quantity * ri.unit_price) as total'))
            ->groupBy('rso.customer_sales_account_id');

        $rgsDirect = DB::table('return_good_stocks as rgs')
            ->join('return_good_stock_items as ri', 'ri.return_good_stock_id', '=', 'rgs.id')
            ->whereNotNull('rgs.customer_sales_account_id')
            ->whereNull('rgs.sales_order_id')
            ->select('rgs.customer_sales_account_id', DB::raw('SUM(ri.quantity * ri.unit_price) as total'))
            ->groupBy('rgs.customer_sales_account_id');

        $rgsTotal = DB::query()->fromSub($rgsViaSO->union($rgsDirect), '_rgs')
            ->select('customer_sales_account_id', DB::raw('SUM(total) as total'))
            ->groupBy('customer_sales_account_id');

        // Union all payment item sources to avoid double-counting
        $pmtDirect = DB::table('customer_payment_items')
            ->select('customer_sales_account_id as csaid', 'sub_amount')
            ->whereNotNull('customer_sales_account_id');
        $pmtViaSO = DB::table('customer_payment_items as cpi')
            ->join('sales_orders as pso', 'pso.id', '=', 'cpi.sales_order_id')
            ->select('pso.customer_sales_account_id as csaid', 'cpi.sub_amount')
            ->whereNull('cpi.customer_sales_account_id');
        $pmtViaInv = DB::table('customer_payment_items as cpi2')
            ->join('customer_account_invoices as pci', 'pci.id', '=', 'cpi2.customer_account_invoice_id')
            ->select('pci.customer_sales_account_id as csaid', 'cpi2.sub_amount')
            ->whereNull('cpi2.customer_sales_account_id')
            ->whereNull('cpi2.sales_order_id');
        $pmtUnion = $pmtDirect->union($pmtViaSO)->union($pmtViaInv);
        $pmtTotal = DB::query()->fromSub($pmtUnion, '_pmt')
            ->select('csaid', DB::raw('SUM(sub_amount) as total'))
            ->groupBy('csaid');

        $query = DB::table('customer_sales_account as csa')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->leftJoinSub($soTotal,  'so_t',  'so_t.customer_sales_account_id',  '=', 'csa.id')
            ->leftJoinSub($invTotal, 'inv_t', 'inv_t.customer_sales_account_id', '=', 'csa.id')
            ->leftJoinSub($pmtTotal, 'pmt_t', 'pmt_t.csaid',                     '=', 'csa.id')
            ->leftJoinSub($rgsTotal, 'rgs_t', 'rgs_t.customer_sales_account_id', '=', 'csa.id')
            ->where('c.status', 'active')
            ->select(
                'csa.id as csa_id',
                'c.id',
                'c.company',
                'c.last_name',
                'c.first_name',
                'c.is_drugstore',
                'c.phone',
                'c.address',
                'sa.account_name',
                DB::raw('IFNULL(csa.forward_balance,0) + IFNULL(so_t.total,0) + IFNULL(inv_t.total,0) - IFNULL(pmt_t.total,0) - IFNULL(rgs_t.total,0) AS balance')
            );

        if (!empty($accountId) && is_numeric($accountId)) {
            $query->where('sa.id', (int) $accountId);
        }

        if ($type === 'drugstore') {
            $query->where('c.is_drugstore', true);
        } elseif ($type === 'person') {
            $query->where('c.is_drugstore', false);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('c.company', 'like', "%{$search}%")
                    ->orWhere('c.last_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('sa.account_name')->orderBy('c.last_name')->paginate(20)->through(function ($row) {
            return [
                'id'           => $row->id,
                'csa_id'       => $row->csa_id,
                'display_name' => $row->is_drugstore
                    ? strtoupper($row->company)
                    : trim(strtoupper($row->last_name) . ', ' . strtoupper($row->first_name)),
                'company'      => strtoupper($row->company),
                'last_name'    => strtoupper($row->last_name),
                'phone'        => $row->phone,
                'address'      => strtoupper($row->address),
                'account_name' => strtoupper($row->account_name),
                'is_drugstore' => $row->is_drugstore ? 'YES' : 'NO',
                'balance'      => number_format((float) $row->balance, 2),
            ];
        });

        $columns = [
            ['accessorKey' => 'id',           'header' => 'ID',             'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'account_name',  'header' => 'ACCOUNT',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'is_drugstore',  'header' => 'LEGEND',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'display_name',  'header' => 'CUSTOMER NAME',  'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'company',       'header' => 'COMPANY',        'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'last_name',     'header' => 'LAST NAME',      'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'phone',         'header' => 'PHONE',          'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'address',       'header' => 'ADDRESS',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'balance',       'header' => 'BALANCE',        'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'status',        'header' => 'STATUS',         'isVisible' => false, 'isParameter' => false],
        ];

        $accounts = SalesAccount::where('status', 'active')
            ->orderBy('account_name')
            ->get(['id', 'account_name'])
            ->map(fn($a) => ['value' => (string) $a->id, 'label' => strtoupper($a->account_name)]);

        return inertia('CustomerAccount/CustsomerAccountIndex', [
            'customers' => $customers,
            'columns'   => $columns,
            'accounts'  => $accounts,
        ]);
    }

    /**
     * Return all sales accounts linked to a customer (for RGS account picker).
     */
    public function accountsByCustomer(int $customerId)
    {
        $accounts = DB::table('customer_sales_account as csa')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->where('csa.customer_id', $customerId)
            ->select('csa.id', 'sa.account_name')
            ->orderBy('sa.account_name')
            ->get()
            ->map(fn($row) => [
                'value' => (string) $row->id,
                'label' => strtoupper($row->account_name),
            ]);

        return response()->json(['accounts' => $accounts]);
    }

    /**
     * Attach a customer to a sales account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_ids'     => 'required|array|min:1',
            'customer_ids.*'   => 'required|integer|exists:customers,id',
            'sales_account_id' => 'required|exists:sales_accounts,id',
        ]);

        $results = [];
        foreach ($validated['customer_ids'] as $customerId) {
            $customer = Customer::findOrFail($customerId);
            if ($customer->salesAccounts()->where('sales_accounts.id', $validated['sales_account_id'])->exists()) {
                $results[] = [
                    'customer_id' => $customerId,
                    'name'        => $customer->display_name,
                    'success'     => false,
                    'message'     => $customer->display_name . ' is already assigned to this account.',
                ];
            } else {
                $customer->salesAccounts()->attach($validated['sales_account_id']);
                $results[] = [
                    'customer_id' => $customerId,
                    'name'        => $customer->display_name,
                    'success'     => true,
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'message' => "{$successCount} customer(s) assigned successfully.",
            'results' => $results,
        ]);
    }

    /**
     * Return unpaid sales orders AND manual invoices for a customer sales account.
     */
    public function unpaidOrders(int $id)
    {
        $orders = DB::table('sales_orders as so')
            ->where('so.customer_sales_account_id', $id)
            ->leftJoinSub(
                DB::table('customer_payment_items')
                    ->whereNotNull('sales_order_id')
                    ->select('sales_order_id', DB::raw('SUM(sub_amount) as paid_amount'))
                    ->groupBy('sales_order_id'),
                'so_pmts',
                'so_pmts.sales_order_id',
                '=',
                'so.id'
            )
            ->leftJoinSub(
                DB::table('customer_payment_items as cpi2')
                    ->join('customer_payments as cp2', 'cp2.id', '=', 'cpi2.customer_payment_id')
                    ->whereNotNull('cpi2.sales_order_id')
                    ->select('cpi2.sales_order_id', DB::raw("GROUP_CONCAT(IFNULL(cp2.reference_no,'') ORDER BY cp2.payment_date SEPARATOR ', ') as paid_refs"))
                    ->groupBy('cpi2.sales_order_id'),
                'so_refs',
                'so_refs.sales_order_id',
                '=',
                'so.id'
            )
            ->select('so.id', 'so.invoice_no', 'so.invoice_date', 'so.terms', 'so_pmts.paid_amount', 'so_refs.paid_refs')
            ->orderBy('so.invoice_date')
            ->get()
            ->map(function ($row) {
                $total = DB::table('sales_order_items')
                    ->where('sales_order_id', $row->id)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));
                $dueDate = ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null;
                return [
                    'id'           => $row->id,
                    'type'         => 'order',
                    'invoice_no'   => $row->invoice_no ?? '—',
                    'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                    'due_date'     => $dueDate,
                    'total'        => round((float) $total, 2),
                    'paid'         => round((float) ($row->paid_amount ?? 0), 2),
                    'paid_refs'    => $row->paid_refs ?? null,
                ];
            });

        $invoices = DB::table('customer_account_invoices as i')
            ->where('i.customer_sales_account_id', $id)
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
                DB::table('customer_payment_items as cpi3')
                    ->join('customer_payments as cp3', 'cp3.id', '=', 'cpi3.customer_payment_id')
                    ->whereNotNull('cpi3.customer_account_invoice_id')
                    ->select('cpi3.customer_account_invoice_id', DB::raw("GROUP_CONCAT(IFNULL(cp3.reference_no,'') ORDER BY cp3.payment_date SEPARATOR ', ') as paid_refs"))
                    ->groupBy('cpi3.customer_account_invoice_id'),
                'inv_refs',
                'inv_refs.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->select('i.id', 'i.reference_no', 'i.invoice_date', 'i.terms', 'i.amount', 'pmts.paid_amount', 'inv_refs.paid_refs')
            ->orderBy('i.invoice_date')
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'type'         => 'invoice',
                'invoice_no'   => $row->reference_no ?? '—',
                'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                'due_date'     => ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null,
                'total'        => round((float) $row->amount, 2),
                'paid'         => round((float) ($row->paid_amount ?? 0), 2),
                'paid_refs'    => $row->paid_refs ?? null,
            ]);

        // Untagged payments (credited directly to account, not linked to any invoice/SO)
        $untagged = DB::table('customer_payments as cp')
            ->join('customer_payment_items as cpi', 'cpi.customer_payment_id', '=', 'cp.id')
            ->where('cpi.customer_sales_account_id', $id)
            ->select('cp.id', 'cpi.sub_amount as amount', 'cp.payment_date as invoice_date', 'cp.reference_no')
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'type'         => 'untagged',
                'invoice_no'   => $row->reference_no ?? '—',
                'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                'due_date'     => null,
                'total'        => round((float) $row->amount, 2),
                'paid'         => 0,
                'paid_refs'    => null,
            ]);

        $all = $orders->concat($invoices)->concat($untagged)->sortBy('invoice_date')->values();

        return response()->json(['orders' => $all]);
    }

    public function ordersForPayment(int $id, int $paymentId)
    {
        $orders = DB::table('sales_orders as so')
            ->where('so.customer_sales_account_id', $id)
            ->where(function ($q) use ($paymentId) {
                $q->whereNotExists(fn($s) => $s->from('customer_payment_items')->whereColumn('customer_payment_items.sales_order_id', 'so.id'))
                    ->orWhereExists(fn($s) => $s->from('customer_payment_items')->whereColumn('customer_payment_items.sales_order_id', 'so.id')->where('customer_payment_items.customer_payment_id', $paymentId));
            })
            ->leftJoinSub(
                DB::table('customer_payment_items')
                    ->whereNotNull('sales_order_id')
                    ->select('sales_order_id', DB::raw('SUM(sub_amount) as paid_amount'))
                    ->groupBy('sales_order_id'),
                'so_pmts',
                'so_pmts.sales_order_id',
                '=',
                'so.id'
            )
            ->leftJoinSub(
                DB::table('customer_payment_items as cpi_r')
                    ->join('customer_payments as cp_r', 'cp_r.id', '=', 'cpi_r.customer_payment_id')
                    ->whereNotNull('cpi_r.sales_order_id')
                    ->where('cpi_r.customer_payment_id', '!=', $paymentId)
                    ->select('cpi_r.sales_order_id', DB::raw("GROUP_CONCAT(IFNULL(cp_r.reference_no,'') ORDER BY cp_r.payment_date SEPARATOR ', ') as paid_refs"))
                    ->groupBy('cpi_r.sales_order_id'),
                'so_refs',
                'so_refs.sales_order_id',
                '=',
                'so.id'
            )
            ->select('so.id', 'so.invoice_no', 'so.invoice_date', 'so.terms', 'so_pmts.paid_amount', 'so_refs.paid_refs')
            ->orderBy('so.invoice_date')
            ->get()
            ->map(function ($row) use ($paymentId) {
                $total    = DB::table('sales_order_items')
                    ->where('sales_order_id', $row->id)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage, 0) / 100)'));
                $allocate = DB::table('customer_payment_items')
                    ->where('sales_order_id', $row->id)
                    ->where('customer_payment_id', $paymentId)
                    ->value('sub_amount');
                $dueDate  = ($row->invoice_date && $row->terms)
                    ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                    : null;
                return [
                    'id'           => $row->id,
                    'type'         => 'order',
                    'invoice_no'   => $row->invoice_no ?? '—',
                    'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                    'due_date'     => $dueDate,
                    'total'        => round((float) $total, 2),
                    'paid'         => round((float) ($row->paid_amount ?? 0), 2),
                    'paid_refs'    => $row->paid_refs ?? null,
                    'allocate'     => $allocate !== null ? round((float) $allocate, 2) : null,
                    'selected'     => $allocate !== null,
                ];
            });

        $invoices = DB::table('customer_account_invoices as i')
            ->where('i.customer_sales_account_id', $id)
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
                DB::table('customer_payment_items as cpi_r2')
                    ->join('customer_payments as cp_r2', 'cp_r2.id', '=', 'cpi_r2.customer_payment_id')
                    ->whereNotNull('cpi_r2.customer_account_invoice_id')
                    ->where('cpi_r2.customer_payment_id', '!=', $paymentId)
                    ->select('cpi_r2.customer_account_invoice_id', DB::raw("GROUP_CONCAT(IFNULL(cp_r2.reference_no,'') ORDER BY cp_r2.payment_date SEPARATOR ', ') as paid_refs"))
                    ->groupBy('cpi_r2.customer_account_invoice_id'),
                'inv_refs',
                'inv_refs.customer_account_invoice_id',
                '=',
                'i.id'
            )
            ->select('i.id', 'i.reference_no', 'i.invoice_date', 'i.terms', 'i.amount', 'pmts.paid_amount', 'inv_refs.paid_refs')
            ->orderBy('i.invoice_date')
            ->get()
            ->map(function ($row) use ($paymentId) {
                $allocate = DB::table('customer_payment_items')
                    ->where('customer_account_invoice_id', $row->id)
                    ->where('customer_payment_id', $paymentId)
                    ->value('sub_amount');
                return [
                    'id'           => $row->id,
                    'type'         => 'invoice',
                    'invoice_no'   => $row->reference_no ?? '—',
                    'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                    'due_date'     => ($row->invoice_date && $row->terms)
                        ? \Carbon\Carbon::parse($row->invoice_date)->addDays((int) $row->terms)->format('m-d-Y')
                        : null,
                    'total'        => round((float) $row->amount, 2),
                    'paid'         => round((float) ($row->paid_amount ?? 0), 2),
                    'paid_refs'    => $row->paid_refs ?? null,
                    'allocate'     => $allocate !== null ? round((float) $allocate, 2) : null,
                    'selected'     => $allocate !== null,
                ];
            });

        // Untagged credits for this account (excluding the current payment)
        $untagged = DB::table('customer_payments as cp')
            ->join('customer_payment_items as cpi', 'cpi.customer_payment_id', '=', 'cp.id')
            ->where('cpi.customer_sales_account_id', $id)
            ->where('cp.id', '!=', $paymentId)
            ->select('cp.id', 'cpi.sub_amount as amount', 'cp.payment_date as invoice_date', 'cp.reference_no')
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'type'         => 'untagged',
                'invoice_no'   => $row->reference_no ?? '—',
                'invoice_date' => $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('m-d-Y') : null,
                'due_date'     => null,
                'total'        => round((float) $row->amount, 2),
                'paid'         => 0,
                'paid_refs'    => null,
                'allocate'     => null,
                'selected'     => false,
            ]);

        $all = $orders->concat($invoices)->concat($untagged)->sortBy('invoice_date')->values();

        return response()->json(['orders' => $all]);
    }

    /**
     * Record a payment for a customer sales account.
     */
    public function storePayment(Request $request, int $id)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'payment_date'    => 'required|date',
            'reference_no'    => 'nullable|string|max:255',
            'payment_method'  => 'nullable|string|max:100',
            'check_number'    => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'      => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'           => 'nullable|string',
            'sales_order_ids'       => 'nullable|array',
            'sales_order_ids.*'     => 'integer|exists:sales_orders,id',
            'invoice_ids'           => 'nullable|array',
            'invoice_ids.*'         => 'integer|exists:customer_account_invoices,id',
            'sales_order_amounts'   => 'nullable|array',
            'sales_order_amounts.*' => 'nullable|numeric|min:0.01',
            'invoice_amounts'       => 'nullable|array',
            'invoice_amounts.*'     => 'nullable|numeric|min:0.01',
        ]);

        $itemsTotal = array_sum($validated['sales_order_amounts'] ?? [])
            + array_sum($validated['invoice_amounts'] ?? []);
        if ($itemsTotal > 0 && abs((float) $validated['amount'] - $itemsTotal) > 0.01) {
            return back()->withErrors(['amount' => 'Payment amount must equal the sum of allocated amounts.']);
        }

        DB::transaction(function () use ($id, $validated, $request) {
            $paymentId = DB::table('customer_payments')->insertGetId([
                'amount'         => $validated['amount'],
                'payment_date'   => $validated['payment_date'],
                'reference_no'   => $validated['reference_no'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'check_number'   => $validated['check_number'] ?? null,
                'check_date'     => $validated['check_date'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => $request->user()->id,
                'updated_by'     => $request->user()->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($validated['sales_order_ids'] ?? [] as $soId) {
                $soAmount = $validated['sales_order_amounts'][$soId]
                    ?? DB::table('sales_order_items')->where('sales_order_id', $soId)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage,0)/100)'));
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id' => $paymentId,
                    'sales_order_id'      => $soId,
                    'sub_amount'          => round((float) $soAmount, 2),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $invoices = DB::table('customer_account_invoices')
                ->whereIn('id', $validated['invoice_ids'] ?? [])
                ->where('customer_sales_account_id', $id)
                ->select('id', 'amount')
                ->get();
            foreach ($invoices as $invoice) {
                $allocAmount = $validated['invoice_amounts'][$invoice->id] ?? $invoice->amount;
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id'        => $paymentId,
                    'customer_account_invoice_id' => $invoice->id,
                    'sub_amount'                 => round((float) $allocAmount, 2),
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }

            if (empty($validated['sales_order_ids']) && empty($validated['invoice_ids'])) {
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id'       => $paymentId,
                    'customer_sales_account_id' => $id,
                    'sub_amount'                => round((float) $validated['amount'], 2),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }
        });

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment recorded successfully!');
    }

    /**
     * Set the forward (opening) balance for a customer sales account.
     */
    public function setForwardBalance(Request $request, int $id)
    {
        $validated = $request->validate([
            'forward_balance'      => 'required|numeric|min:0',
            'forward_balance_date' => 'required|date',
        ]);

        DB::table('customer_sales_account')
            ->where('id', $id)
            ->update([
                'forward_balance'      => $validated['forward_balance'],
                'forward_balance_date' => $validated['forward_balance_date'],
                'updated_at'           => now(),
            ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Forward balance set successfully!');
    }

    /**
     * Return ledger entries for a customer sales account.
     */
    public function ledger(int $id)
    {
        $csa = DB::table('customer_sales_account as csa')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->where('csa.id', $id)
            ->select(
                'csa.id',
                'sa.account_name',
                'c.company',
                'c.last_name',
                'c.first_name',
                'c.is_drugstore',
                'csa.forward_balance',
                'csa.forward_balance_date'
            )
            ->first();

        if (!$csa) {
            return response()->json(['error' => 'Account not found.'], 404);
        }

        // ── INVOICES from sales orders ────────────────────────────────────
        $invoices = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->where('so.customer_sales_account_id', $id)
            ->select(
                'so.id as order_id',
                'so.invoice_no',
                'so.invoice_date as date',
                DB::raw('SUM(soi.quantity * soi.unit_price * (1 - IFNULL(soi.discount_percentage,0)/100)) as amount')
            )
            ->groupBy('so.id', 'so.invoice_no', 'so.invoice_date')
            ->get()
            ->map(fn($row) => [
                'type'       => 'INVOICE',
                'reference'  => 'SO #' . $row->order_id,
                'invoice_no' => $row->invoice_no ?? '—',
                'amount'     => (float) $row->amount,
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── MANUAL INVOICES ───────────────────────────────────────────────
        $manualInvoices = DB::table('customer_account_invoices')
            ->where('customer_sales_account_id', $id)
            ->select('id', 'reference_no', 'invoice_date as date', 'amount', 'terms', 'notes')
            ->get()
            ->map(fn($row) => [
                'type'       => 'INVOICE',
                'is_manual'  => true,
                'invoice_id' => $row->id,
                'reference'  => 'INV #' . $row->id,
                'invoice_no' => $row->reference_no ?? '—',
                'amount'     => (float) $row->amount,
                'raw_amount' => (float) $row->amount,
                'raw_date'   => $row->date,
                'terms'      => $row->terms !== null ? (int) $row->terms : null,
                'notes'      => $row->notes ?? '',
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── PAYMENTS ──────────────────────────────────────────────────────
        $invoiceIds = DB::table('customer_account_invoices')->where('customer_sales_account_id', $id)->pluck('id');
        $soIds      = DB::table('sales_orders')->where('customer_sales_account_id', $id)->pluck('id');

        $payments = DB::table('customer_payments as cp')
            ->join('customer_payment_items as cpi', 'cpi.customer_payment_id', '=', 'cp.id')
            ->leftJoin('sales_orders as so', 'so.id', '=', 'cpi.sales_order_id')
            ->leftJoin('customer_account_invoices as ci', 'ci.id', '=', 'cpi.customer_account_invoice_id')
            ->where(fn($q) => $q
                ->where('cpi.customer_sales_account_id', $id)
                ->orWhereIn('cpi.customer_account_invoice_id', $invoiceIds)
                ->orWhereIn('cpi.sales_order_id', $soIds))
            ->select(
                'cp.id',
                DB::raw('SUM(cpi.sub_amount) as amount'),
                'cp.payment_date as date',
                'cp.reference_no',
                'cp.payment_method',
                'cp.check_date',
                'cp.check_number',
                'cp.notes',
                DB::raw("GROUP_CONCAT(
                    CASE
                        WHEN cpi.sales_order_id IS NOT NULL THEN CONCAT('SO#', IFNULL(so.invoice_no, cpi.sales_order_id))
                        WHEN cpi.customer_account_invoice_id IS NOT NULL THEN CONCAT('INV#', IFNULL(ci.reference_no, cpi.customer_account_invoice_id))
                        ELSE NULL
                    END
                    ORDER BY cpi.id SEPARATOR ', ') as linked_refs")
            )
            ->groupBy(
                'cp.id',
                'cp.payment_date',
                'cp.reference_no',
                'cp.payment_method',
                'cp.check_date',
                'cp.check_number',
                'cp.notes'
            )
            ->get()
            ->map(fn($row) => [
                'type'           => 'PAYMENT',
                'is_payment'     => true,
                'payment_id'     => $row->id,
                'reference'      => 'PMT #' . $row->id,
                'invoice_no'     => collect(array_filter([
                    $row->reference_no ?: null,
                    $row->linked_refs ? $row->linked_refs : ($row->reference_no ? null : 'Untagged Payment'),
                ]))->unique()->implode(' | ') ?: 'Untagged Payment',
                'amount'         => (float) $row->amount,
                'raw_amount'     => (float) $row->amount,
                'raw_date'       => $row->date,
                'payment_method' => $row->payment_method ?? 'Cash',
                'check_date'     => $row->check_date ?? null,
                'check_number'   => $row->check_number ?? null,
                'notes'          => $row->notes ?? '',
                'date'           => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        // ── RGS CREDITS ───────────────────────────────────────────────────
        $rgsViaSO = DB::table('return_good_stocks as rgs')
            ->join('sales_orders as so', 'so.id', '=', 'rgs.sales_order_id')
            ->join('return_good_stock_items as ri', 'ri.return_good_stock_id', '=', 'rgs.id')
            ->where('so.customer_sales_account_id', $id)
            ->select(
                'rgs.id',
                'so.invoice_no',
                'rgs.rgs_date as date',
                'rgs.notes',
                DB::raw('SUM(ri.quantity * ri.unit_price) as amount')
            )
            ->groupBy('rgs.id', 'so.invoice_no', 'rgs.rgs_date', 'rgs.notes')
            ->get()
            ->map(fn($row) => [
                'type'       => 'RGS',
                'reference'  => 'RGS #' . $row->id,
                'invoice_no' => $row->invoice_no ?? '—',
                'amount'     => (float) $row->amount,
                'notes'      => $row->notes ?? '',
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        $rgsDirect = DB::table('return_good_stocks as rgs')
            ->join('return_good_stock_items as ri', 'ri.return_good_stock_id', '=', 'rgs.id')
            ->where('rgs.customer_sales_account_id', $id)
            ->whereNull('rgs.sales_order_id')
            ->select(
                'rgs.id',
                'rgs.rgs_date as date',
                'rgs.notes',
                DB::raw('SUM(ri.quantity * ri.unit_price) as amount')
            )
            ->groupBy('rgs.id', 'rgs.rgs_date', 'rgs.notes')
            ->get()
            ->map(fn($row) => [
                'type'       => 'RGS',
                'reference'  => 'RGS #' . $row->id,
                'invoice_no' => '—',
                'amount'     => (float) $row->amount,
                'notes'      => $row->notes ?? '',
                'date'       => $row->date ? \Carbon\Carbon::parse($row->date) : null,
            ]);

        $rgsEntries = $rgsViaSO->concat($rgsDirect);

        // ── Merge & sort by date ──────────────────────────────────────────
        $entries = $invoices->concat($manualInvoices)->concat($payments)->concat($rgsEntries)
            ->sortBy(fn($e) => $e['date'] ?? \Carbon\Carbon::minValue())
            ->values();

        // ── Running balance (debit = invoice, credit = payment) ───────────
        $balance = (float) ($csa->forward_balance ?? 0);

        // Prepend FORWARD entry if set
        $forwardEntry = null;
        if ($balance > 0 && $csa->forward_balance_date) {
            $forwardEntry = [
                'type'       => 'FORWARD',
                'reference'  => 'Forward Balance',
                'invoice_no' => '—',
                'amount'     => number_format($balance, 2),
                'balance'    => number_format($balance, 2),
                'date'       => \Carbon\Carbon::parse($csa->forward_balance_date)->format('m-d-Y'),
            ];
        }

        $ledger = $entries->map(function ($entry) use (&$balance) {
            if ($entry['type'] === 'INVOICE') {
                $balance += $entry['amount'];
            } else {
                $balance -= $entry['amount'];
            }
            return array_merge($entry, [
                'balance' => number_format($balance, 2),
                'amount'  => number_format($entry['amount'], 2),
                'date'    => $entry['date'] ? $entry['date']->format('m-d-Y') : '—',
            ]);
        });

        if ($forwardEntry) {
            $ledger = collect([$forwardEntry])->concat($ledger);
        }

        $customerName = $csa->is_drugstore
            ? strtoupper($csa->company)
            : trim(strtoupper($csa->last_name) . ', ' . strtoupper($csa->first_name));

        return response()->json([
            'account' => [
                'id'           => $csa->id,
                'account_name' => strtoupper($csa->account_name),
                'customer'     => $customerName,
                'balance'      => number_format($balance, 2),
                'forward_balance' => number_format((float) ($csa->forward_balance ?? 0), 2),
            ],
            'ledger' => $ledger,
        ]);
    }

    /**
     * Store a manual (previous) invoice for a customer sales account.
     */
    public function storeInvoice(Request $request, int $id)
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'terms'        => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        DB::table('customer_account_invoices')->insert([
            'customer_sales_account_id' => $id,
            'reference_no'              => $validated['reference_no'] ?? null,
            'invoice_date'              => $validated['invoice_date'],
            'amount'                    => $validated['amount'],
            'terms'                     => $validated['terms'] ?? null,
            'notes'                     => $validated['notes'] ?? null,
            'created_by'                => $request->user()->id,
            'updated_by'                => $request->user()->id,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice recorded successfully!');
    }

    /**
     * Update a manual invoice for a customer sales account.
     */
    public function updateInvoice(Request $request, int $csaId, int $invoiceId)
    {
        $validated = $request->validate([
            'reference_no' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'terms'        => 'nullable|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        DB::table('customer_account_invoices')
            ->where('id', $invoiceId)
            ->where('customer_sales_account_id', $csaId)
            ->update([
                'reference_no' => $validated['reference_no'] ?? null,
                'invoice_date' => $validated['invoice_date'],
                'amount'       => $validated['amount'],
                'terms'        => $validated['terms'] ?? null,
                'notes'        => $validated['notes'] ?? null,
                'updated_by'   => $request->user()->id,
                'updated_at'   => now(),
            ]);

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice updated successfully!');
    }

    /**
     * Update a payment for a customer sales account.
     */
    public function updatePayment(Request $request, int $csaId, int $paymentId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
            'sales_order_ids'       => 'nullable|array',
            'sales_order_ids.*'      => 'integer|exists:sales_orders,id',
            'invoice_ids'            => 'nullable|array',
            'invoice_ids.*'          => 'integer|exists:customer_account_invoices,id',
            'sales_order_amounts'    => 'nullable|array',
            'sales_order_amounts.*'  => 'nullable|numeric|min:0.01',
            'invoice_amounts'        => 'nullable|array',
            'invoice_amounts.*'      => 'nullable|numeric|min:0.01',
        ]);

        $itemsTotal = array_sum($validated['sales_order_amounts'] ?? [])
            + array_sum($validated['invoice_amounts'] ?? []);
        if ($itemsTotal > 0 && abs((float) $validated['amount'] - $itemsTotal) > 0.01) {
            return back()->withErrors(['amount' => 'Payment amount must equal the sum of allocated amounts.']);
        }

        DB::transaction(function () use ($csaId, $paymentId, $validated, $request) {
            DB::table('customer_payments')
                ->where('id', $paymentId)
                ->update([
                    'amount'         => $validated['amount'],
                    'payment_date'   => $validated['payment_date'],
                    'reference_no'   => $validated['reference_no'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'check_number'   => $validated['check_number'] ?? null,
                    'check_date'     => $validated['check_date'] ?? null,
                    'notes'          => $validated['notes'] ?? null,
                    'updated_by'     => $request->user()->id,
                    'updated_at'     => now(),
                ]);

            DB::table('customer_payment_items')
                ->where('customer_payment_id', $paymentId)
                ->delete();

            foreach ($validated['sales_order_ids'] ?? [] as $soId) {
                $soAmount = $validated['sales_order_amounts'][$soId]
                    ?? DB::table('sales_order_items')->where('sales_order_id', $soId)
                    ->sum(DB::raw('quantity * unit_price * (1 - IFNULL(discount_percentage,0)/100)'));
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id' => $paymentId,
                    'sales_order_id'      => $soId,
                    'sub_amount'          => round((float) $soAmount, 2),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            $invoices = DB::table('customer_account_invoices')
                ->whereIn('id', $validated['invoice_ids'] ?? [])
                ->where('customer_sales_account_id', $csaId)
                ->select('id', 'amount')
                ->get();
            foreach ($invoices as $invoice) {
                $allocAmount = $validated['invoice_amounts'][$invoice->id] ?? $invoice->amount;
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id'        => $paymentId,
                    'customer_account_invoice_id' => $invoice->id,
                    'sub_amount'                 => round((float) $allocAmount, 2),
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }

            if (empty($validated['sales_order_ids']) && empty($validated['invoice_ids'])) {
                DB::table('customer_payment_items')->insert([
                    'customer_payment_id'       => $paymentId,
                    'customer_sales_account_id' => $csaId,
                    'sub_amount'                => round((float) $validated['amount'], 2),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }
        });

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment updated successfully!');
    }

    /**
     * Record a payment for a specific customer account invoice.
     */
    public function storeInvoicePayment(Request $request, int $invoiceId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($invoiceId, $validated, $request) {
            $paymentId = DB::table('customer_payments')->insertGetId([
                'amount'         => $validated['amount'],
                'payment_date'   => $validated['payment_date'],
                'reference_no'   => $validated['reference_no'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'check_number'   => $validated['check_number'] ?? null,
                'check_date'     => $validated['check_date'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => $request->user()->id,
                'updated_by'     => $request->user()->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::table('customer_payment_items')->insert([
                'customer_payment_id'        => $paymentId,
                'customer_account_invoice_id' => $invoiceId,
                'sub_amount'                 => round((float) $validated['amount'], 2),
                'created_at'                 => now(),
                'updated_at'                 => now(),
            ]);
        });

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment recorded successfully!');
    }

    /**
     * Update a payment for a specific customer account invoice.
     */
    public function updateInvoicePayment(Request $request, int $invoiceId, int $paymentId)
    {
        $isCheque = $request->input('payment_method') === 'Cheque';

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'reference_no'   => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'check_number'   => ($isCheque ? 'required' : 'nullable') . '|string|max:100',
            'check_date'     => ($isCheque ? 'required' : 'nullable') . '|date',
            'notes'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($invoiceId, $paymentId, $validated, $request) {
            DB::table('customer_payments')
                ->where('id', $paymentId)
                ->update([
                    'amount'         => $validated['amount'],
                    'payment_date'   => $validated['payment_date'],
                    'reference_no'   => $validated['reference_no'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'check_number'   => $validated['check_number'] ?? null,
                    'check_date'     => $validated['check_date'] ?? null,
                    'notes'          => $validated['notes'] ?? null,
                    'updated_by'     => $request->user()->id,
                    'updated_at'     => now(),
                ]);
            DB::table('customer_payment_items')
                ->where('customer_payment_id', $paymentId)
                ->where('customer_account_invoice_id', $invoiceId)
                ->update(['sub_amount' => round((float) $validated['amount'], 2), 'updated_at' => now()]);
        });

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment updated successfully!');
    }

    /**
     * Delete a payment for a specific customer account invoice.
     */
    public function destroyInvoicePayment(int $invoiceId, int $paymentId)
    {
        DB::table('customer_payments')
            ->where('id', $paymentId)
            ->whereExists(fn($q) => $q->from('customer_payment_items')
                ->where('customer_payment_id', $paymentId)
                ->where('customer_account_invoice_id', $invoiceId))
            ->delete();

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice payment deleted successfully!');
    }

    /**
     * Delete a manual invoice.
     */
    public function destroyInvoice(int $csaId, int $invoiceId)
    {
        DB::table('customer_account_invoices')
            ->where('id', $invoiceId)
            ->where('customer_sales_account_id', $csaId)
            ->delete();

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    /**
     * Delete a payment.
     */
    public function destroyPayment(int $csaId, int $paymentId)
    {
        $invoiceIds = DB::table('customer_account_invoices')->where('customer_sales_account_id', $csaId)->pluck('id');
        $soIds      = DB::table('sales_orders')->where('customer_sales_account_id', $csaId)->pluck('id');

        $belongs = DB::table('customer_payment_items')
            ->where('customer_payment_id', $paymentId)
            ->where(fn($q) => $q
                ->where('customer_sales_account_id', $csaId)
                ->orWhereIn('customer_account_invoice_id', $invoiceIds)
                ->orWhereIn('sales_order_id', $soIds))
            ->exists();

        if ($belongs) {
            DB::table('customer_payments')->where('id', $paymentId)->delete();
        }

        return redirect()->route('customer-accounts.index')
            ->with('success', 'Payment deleted successfully!');
    }
}

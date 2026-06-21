<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesAccount;
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

        $query = DB::table('customer_sales_account as csa')
            ->join('customers as c', 'c.id', '=', 'csa.customer_id')
            ->join('sales_accounts as sa', 'sa.id', '=', 'csa.sales_account_id')
            ->where('c.status', 'active')
            ->select(
                'c.id',
                'c.company',
                'c.last_name',
                'c.first_name',
                'c.is_drugstore',
                'c.phone',
                'c.address',
                'sa.account_name'
            );

        if (!empty($accountId) && is_numeric($accountId)) {
            $query->where('sa.id', (int) $accountId);
        }

        if ($type === 'drugstore') {
            $query->where('c.is_drugstore', true);
        } elseif ($type === 'person') {
            $query->where('c.is_drugstore', false);
        }

        if (!empty($search) && strlen($search) >= 3) {
            $query->where(function ($q) use ($search) {
                $q->where('c.company', 'like', "{$search}%")
                    ->orWhere('c.last_name', 'like', "{$search}%");
            });
        }

        $customers = $query->orderBy('sa.account_name')->orderBy('c.last_name')->paginate(15)->through(function ($row) {
            return [
                'id'           => $row->id,
                'display_name' => $row->is_drugstore
                    ? strtoupper($row->company)
                    : trim(strtoupper($row->last_name) . ', ' . strtoupper($row->first_name)),
                'company'      => strtoupper($row->company),
                'last_name'    => strtoupper($row->last_name),
                'phone'        => $row->phone,
                'address'      => strtoupper($row->address),
                'account_name' => strtoupper($row->account_name),
                'is_drugstore' => $row->is_drugstore ? 'YES' : 'NO',
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
}

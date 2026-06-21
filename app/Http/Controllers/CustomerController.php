<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->wantsJson()) {
            $search = $request->input('search');
            $query = Customer::where('status', 'active');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('company', 'like', "{$search}%")
                        ->orWhere('last_name', 'like', "{$search}%");
                });
            }

            $customers = $query->orderBy('company')->limit(10)->get(['id', 'company', 'last_name', 'first_name', 'is_drugstore']);

            return response()->json([
                'customers' => $customers->map(fn($c) => [
                    'id'           => $c->id,
                    'display_name' => $c->is_drugstore
                        ? strtoupper($c->company)
                        : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name)),
                ])
            ]);
        }

        $search = $request->input('search');
        $column = $request->input('column');
        $type = $request->input('type');

        $query = Customer::query();

        // Show only active customers
        $query->where('status', 'active');

        if ($type === 'drugstore') {
            $query->where('is_drugstore', true);
        } elseif ($type === 'person') {
            $query->where('is_drugstore', false);
        }

        if (!empty($search) && strlen($search) >= 3) {
            $query->where(function ($q) use ($search) {
                $q->where('company', 'like', "{$search}%")
                    ->orWhere('last_name', 'like', "{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($customer) {
            return [
                'id'          => $customer->id,
                'is_drugstore' => $customer->is_drugstore ? 'YES' : 'NO',
                'display_name' => $customer->is_drugstore
                    ? strtoupper($customer->company)
                    : trim(
                        ($customer->company ? strtoupper($customer->company) . ' - ' : '') .
                            strtoupper($customer->last_name)
                    ),
                'company'     => strtoupper($customer->company),
                'last_name'   => strtoupper($customer->last_name),
                'first_name'  => strtoupper($customer->first_name),
                'middle_name' => strtoupper($customer->middle_name),
                'phone'       => $customer->phone,
                'email'       => strtoupper($customer->email),
                'address'     => strtoupper($customer->address),
                'status'      => strtoupper($customer->status),
            ];
        });

        $columns = [
            ['accessorKey' => 'id',         'header' => 'ID',            'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'is_drugstore',  'header' => 'LEGEND',           'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'display_name',   'header' => 'COMPANY / NAME',   'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'company',        'header' => 'COMPANY',          'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'last_name',      'header' => 'CUSTOMER NAME',        'isVisible' => false, 'isParameter' => true],
            ['accessorKey' => 'phone',       'header' => 'PHONE',         'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'email',       'header' => 'EMAIL',         'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'address',     'header' => 'ADDRESS',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'status',      'header' => 'STATUS',        'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Customers/CustomerIndex', [
            'customers' => $customers,
            'columns'   => $columns,
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
            'is_drugstore' => 'boolean',
            'company' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'active';

        $customer = Customer::create($validated);

        if (request()->expectsJson()) {
            return response()->json(['customer' => $customer]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'is_drugstore' => 'boolean',
            'company' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'sales_account_id' => 'nullable|exists:sales_accounts,id',
        ]);

        $salesAccountId = isset($validated['sales_account_id']) ? (int) $validated['sales_account_id'] : null;
        unset($validated['sales_account_id']);

        $customer->update($validated);
        $customer->salesAccounts()->sync($salesAccountId ? [$salesAccountId] : []);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete by setting status to inactive.
     */
    public function destroy(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $customer->salesAccounts()->detach();

        // Soft delete: set status to inactive
        $customer->update([
            'status' => 'inactive'
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer deactivated successfully!');
    }
}

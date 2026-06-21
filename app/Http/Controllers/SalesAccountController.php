<?php

namespace App\Http\Controllers;

use App\Models\SalesAccount;
use Illuminate\Http\Request;


class SalesAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (request()->wantsJson()) {
            $search = $request->input('search');
            $query = SalesAccount::where('status', 'active');

            if (!empty($search)) {
                $query->where('account_name', 'like', "{$search}%");
            }
            return response()->json([
                'salesAccounts' => $query->orderBy('account_name')->limit(5)->get(['id', 'account_name'])
            ]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = SalesAccount::query();

        // Show only active sales accounts
        $query->where('status', 'active');

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $salesAccounts = $query->withCount('customers')->orderBy('created_at', 'desc')->paginate(15)->through(function ($salesAccount) {
            return [
                'id'              => $salesAccount->id,
                'account_name'    => strtoupper($salesAccount->account_name),
                'status'          => $salesAccount->status,
                'customers_count' => $salesAccount->customers_count,
            ];
        });



        //  $salesAccounts = $query->with('customers')->orderBy('created_at', 'desc')->paginate(15)->through(function ($salesAccount) {
        //     return [
        //         'id'              => $salesAccount->id,
        //         'account_name'    => strtoupper($salesAccount->account_name),
        //         'status'          => $salesAccount->status,
        //         'customers_count' => $salesAccount->customers->count(),
        //         'customers'       => $salesAccount->customers->map(fn($c) => [
        //             'id'           => $c->id,
        //             'display_name' => $c->is_drugstore
        //                 ? strtoupper($c->company)
        //                 : trim(strtoupper($c->last_name) . ', ' . strtoupper($c->first_name)),
        //             'phone'        => $c->phone,
        //         ]),
        //     ];
        // });

        $columns = [
            ['accessorKey' => 'id',              'header' => 'ID',           'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'account_name',    'header' => 'ACCOUNT NAME', 'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'status',          'header' => 'STATUS',       'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at',      'header' => 'CREATED AT',   'isVisible' => false, 'isParameter' => false],
        ];


        return inertia('SalesAccounts/SalesAccountIndex', [
            'salesAccounts' => $salesAccounts,
            'columns' => $columns
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
        ]);

        $salesAccount = SalesAccount::create($validated);

        if (request()->expectsJson()) {
            return response()->json(['salesAccount' => $salesAccount]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesAccount $salesAccount)
    {
        //

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesAccount $salesAccount)
    {
        //


    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, SalesAccount $salesAccount)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
        ]);

        //Add updated_by field
        $validated['updated_by'] = $request->user()->id;
        $salesAccount->update($validated);
    }


    public function destroy(Request $request, SalesAccount $salesAccount)
    {
        $salesAccount->update([
            'status'     => 'inactive',
            'updated_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Sales account deactivated successfully!']);
        }
    }
}

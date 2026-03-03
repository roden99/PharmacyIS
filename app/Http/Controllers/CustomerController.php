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
        $search = $request->input('search');
        $column = $request->input('column');

        $query = Customer::query();

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($customer) {
            $customer->full_name = trim(
                strtoupper($customer->last_name) . ', ' .
                    strtoupper($customer->first_name) . ' ' .
                    strtoupper($customer->middle_name)
            );
            return $customer;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'full_name', 'header' => 'CUSTOMER NAME', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'email', 'header' => 'EMAIL', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'phone', 'header' => 'PHONE', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => false],
        ];

        return inertia('Customers/CustomerIndex', [
            'customers' => $customers,
            'columns' => $columns
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully!');
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
    }
}

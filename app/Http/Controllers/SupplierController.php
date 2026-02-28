<?php

namespace App\Http\Controllers;

use App\Models\supplier;
use Illuminate\Http\Request;


class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {



        $search = $request->input('search');
        $column = $request->input('column');

        $query = supplier::query();


        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $suppliers = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($supplier) {
            $supplier->full_name = trim(
                strtoupper($supplier->lastname) . ', ' .
                    strtoupper($supplier->firstname) . ' ' .
                    strtoupper($supplier->middlename)
            );
            return $supplier;
        });

        $columns = [

            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'company', 'header' => 'COMPANY', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'full_name', 'header' => 'REPRESENTATIVE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'contact_email', 'header' => 'EMAIL', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'contact_phone', 'header' => 'CONTACT #', 'isVisible' => true, 'isParameter' => true],


            // ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            // ['accessorKey' => 'pin', 'header' => 'PHILHEALTH NO.', 'isVisible' => true, 'isParameter' => f],
            // ['accessorKey' => 'full_name', 'header' => 'MEMBER NAME', 'isVisible' => true, 'isParameter' => false],
            // ['accessorKey' => 'last_name', 'header' => 'LAST NAME', 'isVisible' => false, 'isParameter' => true],
            // ['accessorKey' => 'first_name', 'header' => 'FIRST NAME', 'isVisible' => false, 'isParameter' => true],
            // ['accessorKey' => 'middle_name', 'header' => 'MIDDLE NAME', 'isVisible' => false, 'isParameter' => false],
            // ['accessorKey' => 'suffix', 'header' => 'SUFFIX', 'isVisible' => false, 'isParameter' => false],
            // ['accessorKey' => 'birth_date', 'header' => 'BIRTH DATE', 'isVisible' => true, 'isParameter' => false],
            // ['accessorKey' => 'sex', 'header' => 'SEX', 'isVisible' => true, 'isParameter' => false],
        ];


        return inertia('Suppliers/SupplierIndex', [
            'suppliers' => $suppliers,
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
            // Supplier information
            'company' => 'required|string|max:255',

            // Contact person information
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',

            // Contact details
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'required|email|max:255',

            // Address information
            'address' => 'nullable|string|max:500',

        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'active';

        supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, supplier $supplier)
    {
        $validated = $request->validate([
            // Supplier information
            'company' => 'required|string|max:255',

            // Contact person information
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',

            // Contact details
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'required|email|max:255',

            // Address information
            'address' => 'nullable|string|max:500',


        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)


    {
        $supplier = Supplier::findOrFail($id); // find supplier by ID or fail
        $supplier->delete(); // delete supplier

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully!');
    }
}

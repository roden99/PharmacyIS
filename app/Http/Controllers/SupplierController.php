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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(supplier $supplier)
    {
        //
    }
}

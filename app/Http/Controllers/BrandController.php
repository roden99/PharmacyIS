<?php

namespace App\Http\Controllers;

use App\Models\brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(Request $request)
    {


        if (request()->wantsJson()) {
            $search = $request->input('search');
            $includeId = $request->input('include_id');

            $query = brand::where('status', 1);

            if (!empty($search)) {
                $query->where('brandname', 'like', "{$search}%");
            }
            $results = $query->orderBy('brandname')->limit(5)->get(['id', 'brandname']);

            if ($includeId && !$results->contains('id', (int)$includeId)) {
                $extra = brand::where('id', (int)$includeId)->first(['id', 'brandname']);
                if ($extra) $results->prepend($extra);
            }

            return response()->json(['brands' => $results]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = brand::query();

        // Show only active brands
        $query->where('status', true);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $brands = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($brand) {
            $brand->status_text = $brand->status ? 'Active' : 'Inactive';
            return $brand;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'brandname', 'header' => 'BRAND NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];


        return inertia('Brands/BrandIndex', [
            'brands' => $brands,
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
            // Brand information
            'brandname' => 'required|string|max:255',

        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        $brand = brand::create($validated);

        if (request()->expectsJson()) {
            return response()->json(['brand' => $brand]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(brand $brand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(brand $brand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, brand $brand)
    {
        $validated = $request->validate([
            // Brand information
            'brandname' => 'required|string|max:255',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $brand->update($validated);

        // return response()->json(['success' => true, 'message' => 'Brand updated successfully!']);
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete by setting status to inactive.
     */
    public function destroy(Request $request, $id)
    {
        $brand = brand::findOrFail($id);

        // Soft delete: set status to inactive (false)
        $brand->update([
            'status' => false,
            'updated_by' => $request->user()->id
        ]);

        // return response()->json(['success' => true, 'message' => 'Brand deactivated successfully!']);
    }
}

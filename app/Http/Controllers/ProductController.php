<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\brand;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = product::with('brand');

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            if ($column === 'brand_name') {
                $query->whereHas('brand', function ($q) use ($search) {
                    $q->where('brandname', 'like', "{$search}%");
                });
            } else {
                $query->where($column, 'like', "{$search}%");
            }
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($product) {
            $product->status_text = $product->status ? 'Active' : 'Inactive';
            $product->generic_text = $product->isgeneric ? 'Generic' : 'Branded';
            $product->brand_name = $product->brand?->brandname ?? 'N/A';
            return $product;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'productname', 'header' => 'PRODUCT NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'brand_name', 'header' => 'BRAND', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'generic_text', 'header' => 'TYPE', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => true, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Products/ProductIndex', [
            'products' => $products,
            'columns' => $columns,
            'brands' => brand::where('status', true)->orderBy('brandname')->get(['id', 'brandname'])
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
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'isgeneric' => 'boolean',
        ]);

        // Add system-generated fields
        $validated['created_by'] = $request->user()->id;

        product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, product $product)
    {
        $validated = $request->validate([
            // Product information
            'productname' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'isgeneric' => 'boolean',
        ]);

        // Add updated_by field
        $validated['updated_by'] = $request->user()->id;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = product::findOrFail($id); // find product by ID or fail
        $product->delete(); // delete product

        return redirect()->route('products.index')->with('success', 'Product deleted successfully!');
    }
}

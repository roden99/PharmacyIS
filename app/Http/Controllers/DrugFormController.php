<?php

namespace App\Http\Controllers;

use App\Models\drugform;
use Illuminate\Http\Request;

class DrugFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->wantsJson()) {
            $search = $request->input('search');
            $includeId = $request->input('include_id');

            $query = drugform::where('status', 1);

            if (!empty($search)) {
                $query->where('drugformname', 'like', "{$search}%");
            }
            $results = $query->orderBy('drugformname')->limit(5)->get(['id', 'drugformname']);

            if ($includeId && !$results->contains('id', (int)$includeId)) {
                $extra = drugform::where('id', (int)$includeId)->first(['id', 'drugformname']);
                if ($extra) $results->prepend($extra);
            }

            return response()->json(['drugforms' => $results]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = drugform::query();

        // Show only active drug forms
        $query->where('status', true);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $drugforms = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($drugform) {
            $drugform->status_text = $drugform->status ? 'Active' : 'Inactive';
            return $drugform;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'drugformname', 'header' => 'DRUG FORM NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('DrugForms/DrugFormIndex', [
            'drugforms' => $drugforms,
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
            'drugformname' => 'required|string|max:255',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $drugform = drugform::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['drugform' => $drugform]);
        }

        return redirect()->back()->with('success', 'Drug form created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(drugform $drugform)
    {
        return inertia('DrugForms/DrugFormShow', [
            'drugform' => $drugform,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(drugform $drugform)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, drugform $drugform)
    {
        $validated = $request->validate([
            'drugformname' => 'required|string|max:255',
        ]);

        $validated['updated_by'] = auth()->id();

        $drugform->update($validated);

        return redirect()->back()->with('success', 'Drug form updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(drugform $drugform)
    {
        $drugform->update(['status' => false]);
        return redirect()->back()->with('success', 'Drug form deactivated successfully.');
    }
}

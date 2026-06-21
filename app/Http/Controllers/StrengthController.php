<?php

namespace App\Http\Controllers;

use App\Models\strength;
use Illuminate\Http\Request;

class StrengthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->wantsJson()) {
            $search = $request->input('search');
            $includeId = $request->input('include_id');

            $query = strength::where('status', 1);

            if (!empty($search)) {
                $query->where('strengthname', 'like', "{$search}%");
            }
            $results = $query->orderBy('strengthname')->limit(5)->get(['id', 'strengthname']);

            if ($includeId && !$results->contains('id', (int)$includeId)) {
                $extra = strength::where('id', (int)$includeId)->first(['id', 'strengthname']);
                if ($extra) $results->prepend($extra);
            }

            return response()->json(['strengths' => $results]);
        }

        $search = $request->input('search');
        $column = $request->input('column');

        $query = strength::query();

        // Show only active strengths
        $query->where('status', true);

        if (!empty($search) && strlen($search) >= 3 && !empty($column)) {
            $query->where($column, 'like', "{$search}%");
        }

        $strengths = $query->orderBy('created_at', 'desc')->paginate(15)->through(function ($strength) {
            $strength->status_text = $strength->status ? 'Active' : 'Inactive';
            return $strength;
        });

        $columns = [
            ['accessorKey' => 'id', 'header' => 'ID', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'strengthname', 'header' => 'STRENGTH NAME', 'isVisible' => true, 'isParameter' => true],
            ['accessorKey' => 'status_text', 'header' => 'STATUS', 'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'created_at', 'header' => 'CREATED AT', 'isVisible' => false, 'isParameter' => false],
        ];

        return inertia('Strengths/StrengthIndex', [
            'strengths' => $strengths,
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
            'strengthname' => 'required|string|max:255',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $strength = strength::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['strength' => $strength]);
        }

        return redirect()->back()->with('success', 'Strength created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(strength $strength)
    {
        return inertia('Strengths/StrengthShow', [
            'strength' => $strength,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(strength $strength)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, strength $strength)
    {
        $validated = $request->validate([
            'strengthname' => 'required|string|max:255',
        ]);

        $validated['updated_by'] = auth()->id();

        $strength->update($validated);

        return redirect()->back()->with('success', 'Strength updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(strength $strength)
    {
        $strength->update(['status' => false]);
        return redirect()->back()->with('success', 'Strength deactivated successfully.');
    }
}

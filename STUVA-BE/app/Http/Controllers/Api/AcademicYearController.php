<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    // Semua TA (untuk dropdown FE)
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => AcademicYear::orderBy('name', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($validated['is_active'] ?? false) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $year = AcademicYear::create($validated);

        return response()->json(['status' => 'success', 'data' => $year], 201);
    }

    public function update(Request $request, $id)
    {
        $year = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:20|unique:academic_years,name,' . $id,
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($validated['is_active'] ?? false) {
            AcademicYear::where('id', '!=', $id)->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $year->update($validated);

        return response()->json(['status' => 'success', 'data' => $year]);
    }

    public function activate($id)
    {
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $year = AcademicYear::findOrFail($id);
        $year->update(['is_active' => true]);

        return response()->json(['status' => 'success', 'data' => $year]);
    }

    public function destroy($id)
    {
        $year = AcademicYear::findOrFail($id);
        if ($year->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa menghapus tahun ajaran yang sedang aktif.'
            ], 422);
        }
        $year->delete();

        return response()->json(['status' => 'success', 'message' => 'Tahun ajaran dihapus']);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\User;
use Illuminate\Http\Request;

class WardBedController extends Controller
{
    public function index()
    {
        $wards = Ward::with(['beds.patient'])->get();
        return response()->json([
            'success' => true,
            'wards' => $wards
        ]);
    }

    public function admitPatient(Request $request)
    {
        $request->validate([
            'bed_id' => 'required|exists:beds,id',
            'patient_name' => 'required|string',
            'patient_id' => 'nullable|exists:users,id',
        ]);

        $bed = Bed::findOrFail($request->bed_id);
        $bed->is_occupied = true;
        $bed->patient_name = $request->patient_name;
        $bed->patient_id = $request->patient_id;
        $bed->admitted_at = now();
        $bed->save();

        return response()->json([
            'success' => true,
            'message' => "Patient {$request->patient_name} admitted to Bed #{$bed->bed_number} successfully.",
            'bed' => $bed->load('ward')
        ]);
    }

    public function dischargePatient($id)
    {
        $bed = Bed::findOrFail($id);
        $bed->is_occupied = false;
        $bed->patient_name = null;
        $bed->patient_id = null;
        $bed->admitted_at = null;
        $bed->save();

        return response()->json([
            'success' => true,
            'message' => "Bed #{$bed->bed_number} is now marked clean and available.",
            'bed' => $bed
        ]);
    }
}

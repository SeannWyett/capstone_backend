<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function addCampus(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $campus = new Campus();
        $campus->name = $request->input('name');
        $campus->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Campus added successfully', 'campus' => $campus], 201);
    }

    public function addDepartment(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')->where(function ($query) use ($request) {
                    return $query->where('campus_id', $request->input('campus_id'));
                }),
            ],
            'campus_id' => 'required|exists:campuses,id',
        ]);

        $department = new Department();
        $department->name = $request->input('name');
        $department->campus_id = $request->input('campus_id');
        $department->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Department added successfully', 'department' => $department], 201);
    }

    public function addProgram(Request $request)
    {
        $request->validate([
            'name' => ['required|string|max:255',
                Rule::unique('programs')->where(function ($query) use ($request) {
                    return $query->where('department_id', $request->input('department_id'));
                }),
            ],
            'department_id' => 'required|exists:departments,id',
        ]);

        $program = new Program();
        $program->name = $request->input('name');
        $program->department_id = $request->input('department_id');
        $program->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Program added successfully', 'program' => $program], 201);
    }

    public function index()
    {
        $campuses = cache()->remember('locations-tree', now()->addHours(6), function () {
            return Campus::with(['departments.programs'])->get();
        });

        return response()->json(['campuses' => $campuses]);
    }
}

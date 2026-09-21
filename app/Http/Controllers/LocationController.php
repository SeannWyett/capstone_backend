<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campus;
use App\Models\College;
use App\Models\Program;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function validateCampus(Request $request, ?int $campusId = null)
    {
        $request->validate([
                'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('campuses')->ignore($campusId),
            ],
        ]);
    }

    public function validateCollege(Request $request, ?int $collegeId = null)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('colleges')
                ->where(fn ($query) => $query->where('campus_id', $request->input('campus_id')))
                ->ignore($collegeId),
            ],
            'campus_id' => 'required|exists:campuses,id',
        ]);
    }

    public function validateProgram(Request $request, ?int $programId = null)
    {
        $request->validate([
            'name' => ['required',
            'string',
            'max:255',
            Rule::unique('programs')
            ->where(fn ($query) => $query->where('college_id', $request->input('college_id')))
            ->ignore($programId),
            ],
            'college_id' => 'required|exists:colleges,id',
        ]);
    }

    public function addCampus(Request $request)
    {
        $this->validateCampus($request);

        $campus = new Campus();
        $campus->name = $request->input('name');
        $campus->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Campus added successfully', 'campus' => $campus], 201);
    }

    public function updateCampus(Request $request, $id)
    {
        $campus = Campus::findOrFail($id);

        $this->validateCampus($request, $campus->id);

        $campus->name = $request->input('name');
        $campus->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Campus updated successfully', 'campus' => $campus]);
    }

    public function destroyCampus($id)
    {
        $campus = Campus::findOrFail($id);
        $campus->delete();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Campus deleted successfully']);
    }

    public function addCollege(Request $request)
    {
        $this->validateCollege($request);

        $college = new College();
        $college->name = $request->input('name');
        $college->campus_id = $request->input('campus_id');
        $college->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'College added successfully', 'college' => $college], 201);
    }

    public function updateCollege(Request $request, $id)
    {
        $college = College::findOrFail($id);

        $this->validateCollege($request, $college->id);

        $college->name = $request->input('name');
        $college->campus_id = $request->input('campus_id');
        $college->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'College updated successfully', 'college' => $college]);
    }

    public function destroyCollege($id)
    {
        $college = College::findOrFail($id);
        $college->delete();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'College deleted successfully']);
    }

    public function addProgram(Request $request)
    {
        $this->validateProgram($request);

        $program = new Program();
        $program->name = $request->input('name');
        $program->college_id = $request->input('college_id');
        $program->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Program added successfully', 'program' => $program], 201);
    }

    public function updateProgram(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $this->validateProgram($request, $program->id);

        $program->name = $request->input('name');
        $program->college_id = $request->input('college_id');
        $program->save();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Program updated successfully', 'program' => $program]);
    }

    public function destroyProgram($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Program deleted successfully']);
    }

    public function index()
    {
        $campuses = cache()->remember('locations-tree', now()->addHours(6), function () {
            return Campus::with(['colleges.programs'])->get();
        });

        return response()->json(['campuses' => $campuses]);
    }
}

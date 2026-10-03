<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campus;
use App\Models\College;
use App\Models\Program;
use App\Models\Category;
use App\Models\PaperUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\CampusPolicy;
use Illuminate\Support\Facades\Auth;


class LocationController extends Controller
{
    public function validateLocation(Request $request, ?int $campusId = null)
    {
        $request->validate([
                'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('campuses')->ignore($campusId),
            ],
            'colleges' => 'sometimes|array',
            'colleges.*.name' => 'required_with:colleges|string|max:255',
            'colleges.*.programs' => 'sometimes|array',
            'colleges.*.programs.*.name' => 'required_with:colleges.*.programs|string|max:255',
            'colleges.*.programs.*.categories' => 'sometimes|array',
            'colleges.*.programs.*.categories.*.name' => 'required_with:colleges.*.programs.*.categories|string|max:255',

            //policy fields
            'guest_can_view_metadata' => 'sometimes|boolean',
            'guest_can_view_file' => 'sometimes|boolean',
            'guest_can_download' => 'sometimes|boolean',
            'student_view_metadata_scope' => 'sometimes|in:none,same_campus,all_campuses',
            'student_view_file_scope' => 'sometimes|in:none,same_campus,all_campuses',
            'student_download_scope' => 'sometimes|in:none,same_campus,all_campuses',
        ]);

        if ($request->filled('colleges')) {
            $colleges = collect($request->input('colleges'));

            //no duuplicate college names within the same campus
            $collegeNames = $colleges->pluck('name');
            if ($collegeNames->count() !== $collegeNames->unique()->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'colleges' => ['Duplicate college names are not allowed within the same campus.'],
                ]);
            }

            //no duplicate program names within the same college
            foreach ($colleges as $ci => $collegeData) {
                $programs = collect($collegeData['programs'] ?? []);
                $programNames = $programs->pluck('name');

                if ($programNames->count() !== $programNames->unique()->count()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "colleges.{$ci}.programs" => ['Duplicate program names are not allowed within the same college.'],
                    ]);
                }

                foreach ($programs as $pi => $programData) {
                    $categoryNames = collect($programData['categories'] ?? [])->pluck('name');
                    if ($categoryNames->count() !== $categoryNames->unique()->count()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "colleges.{$ci}.programs.{$pi}.categories" => ['Duplicate category names are not allowed within the same program.'],
                        ]);
                    }
                }
            }
        }
    }

    public function addLocation(Request $request)
    {
        $this->validateLocation($request);

        try {
            $campus = DB::transaction(function () use ($request) {
                $campus = new Campus();
                $campus->name = $request->input('name');
                $campus->save();

                $campus->policy()->create([
                    'guest_can_view_metadata' => $request->input('guest_can_view_metadata', false),
                    'guest_can_view_file' => $request->input('guest_can_view_file', false),
                    'guest_can_download' => $request->input('guest_can_download', false),
                    'student_view_metadata_scope' => $request->input('student_view_metadata_scope', 'all_campuses'),
                    'student_view_file_scope' => $request->input('student_view_file_scope', 'same_campus'),
                    'student_download_scope' => $request->input('student_download_scope', 'same_campus'),
                ]);

                foreach ($request->input('colleges', []) as $collegeData) {
                    $college = $campus->colleges()->create(['name' => $collegeData['name']]);

                    foreach ($collegeData['programs'] ?? [] as $programData) {
                        $program = $college->programs()->create(['name' => $programData['name']]);

                        foreach ($programData['categories'] ?? [] as $categoryData) {
                            $program->categories()->create(['name' => $categoryData['name']]);
                        }
                    }
                }

                return $campus;
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add location: ',
                'error' => $e->getMessage(),
            ], 500);
        }

        $campus->load('colleges.programs.categories');

        cache()->forget('locations-tree');

        return response()->json(['message' => 'Campus added successfully', 'campus' => $campus], 201);
    }
        
    public function updateLocation(Request $request, $id)
    {
        $campus = Campus::findOrFail($id);
        $this->validateLocation($request, $campus->id);

        try {
            $campus = DB::transaction(function () use ($request, $campus){
                $campus->name = $request->input('name');
                $campus->save();

                if ($request->hasAny([
                    'guest_can_view_metadata', 'guest_can_view_file', 'guest_can_download',
                    'student_view_metadata_scope', 'student_view_file_scope', 'student_download_scope',
                ])) {
                    $campus->policy()->updateOrCreate([], $request->only([
                        'guest_can_view_metadata', 'guest_can_view_file', 'guest_can_download',
                        'student_view_metadata_scope', 'student_view_file_scope', 'student_download_scope',
                    ]));
                }

                $submittedColleges = collect($request->input('colleges', []));
                $submittedCollegeIds = $submittedColleges->pluck('id')->filter()->map(fn ($v) => (int) $v);
                $existingCollegeIds = $campus->colleges()->pluck('id');
                
                // --- Handle removed colleges ---
                $collegesToRemove = $existingCollegeIds->diff($submittedCollegeIds);
                foreach ($collegesToRemove as $collegeId) {
                    $college = College::findOrFail($collegeId);
                    $this->guardCollegeRemoval($college);
                    $college->delete();
                    }
                    
                    // --- Handle submitted colleges (create or update) ---
                    foreach ($submittedColleges as $collegeData) {
                        if (!empty($collegeData['id'])) {
                        $college = College::findOrFail($collegeData['id']);
                        $college->update(['name' => $collegeData['name']]);
                        } else {
                            $college = $campus->colleges()->create(['name' => $collegeData['name']]);
                        }
                    
                        $this->syncPrograms($college, $collegeData['programs'] ?? []);
                    }
                
                    return $campus;
                    
                });
            }catch (\Illuminate\Validation\ValidationException $e) {
                throw $e; //laravel's normal 422 response
            }catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 409);
            }
            
            $campus->load('colleges.programs.categories');
            cache()->forget('locations-tree');
            
            return response()->json(['message' => 'Campus updated successfully', 'campus' => $campus]);
        }

    public function UpdateCampusPolicy(Request $request, $campusId)
    {
            $campus = Campus::findOrFail($campusId);

            if ($request->user()?->role !== 'super_admin') {
                abort(403, 'Only a super admin can update campus policies.');
            }

            $validated = $request->validate([
                'guest_can_view_metadata' => 'sometimes|boolean',
                'guest_can_view_file' => 'sometimes|boolean',
                'guest_can_download' => 'sometimes|boolean',
                'student_view_metadata_scope' => 'sometimes|in:none,same_campus,all_campuses',
                'student_view_file_scope' => 'sometimes|in:none,same_campus,all_campuses',
                'student_download_scope' => 'sometimes|in:none,same_campus,all_campuses',
            ]);

            $campus->policy()->updateOrCreate([], $validated);

            cache()->forget('locations-tree');

            return response()->json([
                'message' => 'Policy updated successfully',
                'policy' => $campus->policy()->first(),
            ]);
        }
        
    public function destroyCampus($id)
        {
            $campus = Campus::findOrFail($id);
            try {
                $this->guardCampusRemoval($campus);
            }catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 409);
            }
            
            $campus->delete();
            
            cache()->forget('locations-tree');
    
            return response()->json(['message' => 'Campus deleted successfully']);
        }

    private function syncPrograms(College $college, array $submittedPrograms)
        {
            $submitted = collect($submittedPrograms);
            $submittedIds = $submitted->pluck('id')->filter()->map(fn ($v) => (int) $v);
            $existingIds = $college->programs()->pluck('id');
            
            $toRemove = $existingIds->diff($submittedIds);
            foreach ($toRemove as $programId) {
                $program = Program::findOrFail($programId);
            $this->guardProgramRemoval($program);
            $program->delete();
        }

        foreach ($submitted as $programData) {
            if (!empty($programData['id'])) {
                $program = Program::findOrFail($programData['id']);
                $program->update(['name' => $programData['name']]);
            } else {
                $program = $college->programs()->create(['name' => $programData['name']]);
            }

            $this->syncCategories($program, $programData['categories'] ?? []);
        }
    }

    private function syncCategories(Program $program, array $submittedCategories)
    {
        $submitted = collect($submittedCategories);
        $submittedIds = $submitted->pluck('id')->filter()->map(fn ($v) => (int) $v);
        $existingIds = $program->categories()->pluck('id');

        $toRemove = $existingIds->diff($submittedIds);
        foreach ($toRemove as $categoryId) {
            $category = Category::findOrFail($categoryId);
            $this->guardCategoryRemoval($category);
            $category->delete();
        }

        foreach ($submitted as $categoryData) {
            if (!empty($categoryData['id'])) {
                Category::findOrFail($categoryData['id'])->update(['name' => $categoryData['name']]);
            } else {
                $program->categories()->create(['name' => $categoryData['name']]);
            }
        }
    }

    private function guardCampusRemoval(Campus $campus)
    {
        if ($campus->colleges()->exists()) {
            throw new \Exception("Cannot delete campus '{$campus->name}' - colleges are still associated with it. Remove them first.");
        }
    }

    private function guardCollegeRemoval(College $college)
    {
        if ($college->programs()->exists()) {
            throw new \Exception("Cannot delete college '{$college->name}' - programs are still associated with it. Remove them first.");
        }
    }

    private function guardProgramRemoval(Program $program)
    {
        if ($program->categories()->exists()) {
            throw new \Exception("Cannot delete program '{$program->name}' - categories are still associated with it. Remove them first.");
        }
        if (PaperUploads::where('program_id', $program->id)->exists()) {
            throw new \Exception("Cannot delete program '{$program->name}' - papers are still filed under it.");
        }
    }

    private function guardCategoryRemoval(Category $category)
    {
        if (PaperUploads::where('category_id', $category->id)->exists()) {
            throw new \Exception("Cannot delete category '{$category->name}' - papers are still filed under it.");
        }
    }

    public function index()
    {
        $campuses = cache()->remember('locations-tree', now()->addHours(6), function () {
            return Campus::with(['colleges.programs.categories', 'policy'])->get()->toArray();
        });

        return response()->json(['campuses' => $campuses]);
    }

}

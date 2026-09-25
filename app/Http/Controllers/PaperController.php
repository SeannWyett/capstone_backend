<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperUploads;
use App\Models\PaperViews;
use App\Http\Requests\StorePaperRequest;
use App\Services\HandlesPapersUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PaperController extends Controller
{
    private function paperQuery(Request $request)
    {
        $query = PaperUploads::query();

        // search logic(by title, and categories)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%");
                        // ->orWhereHas('category', function ($query) use ($search) {
                        //     $query->where('name', 'like', "%{$search}%");
                        // });
            });
        }

        //filtering by campus, college, program, year, and paper_type
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }
        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('paper_type')) {
            $query->where('paper_type', $request->paper_type);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->paperQuery($request)
            ->with(['campus:id,name', 'college:id,name', 'program:id,name', 'category:id,name']); // Eager load relationships

        // Pagination
        $perPage = min($request->integer('per_page', 5), 10); // Default to 5 if not provided, Limit to a maximum of 10
        $papers = $query->paginate($perPage);

        return response()->json($papers);
    }

    public function analytics()
    {
        return response()->json(
            Cache::remember('paper-analytics', now()->addMinutes(15), function () {
                $papersBytype = PaperUploads::select('paper_type', DB::raw('count(*) as total'))
                    ->groupBy('paper_type')
                    ->pluck('total', 'paper_type');

                $papersByCampus = PaperUploads::select('campus_id', DB::raw('count(*) as total'))
                    ->groupBy('campus_id')
                    ->with('campus:id,name') // Eager load the campus relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->campus->name ??'Unknown' => $paper->total];
                    });

                $papersByCollege = PaperUploads::select('college_id', DB::raw('count(*) as total'))
                    ->groupBy('college_id')
                    ->with('college:id,name') // Eager load the college relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->college->name ??'Unknown' => $paper->total];
                    });
                
                $papersByProgram = PaperUploads::select('program_id', DB::raw('count(*) as total'))
                    ->groupBy('program_id')
                    ->with('program:id,name') // Eager load the program relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->program->name ??'Unknown' => $paper->total];
                    });

                $papersByYear = PaperUploads::select('year', DB::raw('count(*) as total'))
                    ->groupBy('year')
                    ->pluck('total', 'year');

                $papersByCategory = PaperUploads::select('category_id', DB::raw('count(*) as total'))
                    ->groupBy('category_id')
                    ->with('category:id,name') // Eager load the category relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->category->name ??'Unknown' => $paper->total];
                    });

                $mostViewedPapers = PaperUploads::orderBy('views_count', 'desc')
                    ->take(5)
                    ->get(['id', 'title', 'views_count']);
                
                return [
                    'total_papers' => PaperUploads::count(),
                    'papers_capstone' => $papersBytype['capstone'] ?? 0,
                    'papers_thesis' => $papersBytype['thesis'] ?? 0,
                    'papers_by_campus' => $papersByCampus,
                    'papers_by_college' => $papersByCollege,
                    'papers_by_program' => $papersByProgram,
                    'papers_by_year' => $papersByYear,
                    'papers_by_category' => $papersByCategory,
                    'most_viewed_papers' => $mostViewedPapers,
                ];
            })
        );
        
    }

    public function store(StorePaperRequest $request, HandlesPapersUploads $uploader)
    {

        $validated = $request->validated();
        $file = $request->file('file');

        $filedata = $uploader->storefile(
            $file,
            $validated['campus_id'],
            $validated['college_id'],
            $validated['program_id']
        );

        try {
            $paperUpload = PaperUploads::create(array_merge($validated, $filedata));
        } catch (\Exception $e) {
            // If there's an error during the database operation, delete the uploaded file
            Storage::disk(config('filesystems.default'))->delete($filedata['file_url']);
            return response()->json(['message' => 'Failed to upload paper', 'error' => $e->getMessage()], 500);
        }


        return response()->json(['message' => 'Paper uploaded successfully', 'data' => $paperUpload], 201);
    }

    public function show($id)
    {
        $paperUpload = PaperUploads::with(['campus:id,name', 'college:id,name', 'program:id,name', 'category:id,name'])
            ->findOrFail($id);
        
        return response()->json($paperUpload);
    }

    public function update(StorePaperRequest $request, HandlesPapersUploads $uploader, $id)
    {
        $paperUpload = PaperUploads::findOrFail($id);
            $validated = $request->validated();

        // If a new file is uploaded, handle the file storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $oldPath = $paperUpload->file_url;

            $filedata = $uploader->storefile(
                $file,
                $validated['campus_id'],
                $validated['department_id'],
                $validated['program_id']
            );

            try {
                $paperUpload->update(array_merge($validated, $filedata));
            } catch (\Exception $e) {
                Storage::disk(config('filesystems.default'))->delete($filedata['file_url']);
                return response()->json(['message' => 'Failed to update paper', 'error' => $e->getMessage()], 500);
            }

            if ($oldPath && Storage::disk(config('filesystems.default'))->exists($oldPath)) {
                Storage::disk(config('filesystems.default'))->delete($oldPath);
            }
        } else {
            $paperUpload->update(array_merge($validated));
        }

        return response()->json(['message' => 'Paper updated successfully', 'data' => $paperUpload]);
    }

    public function destroy($id)
    {
        $paperUpload = PaperUploads::findOrFail($id);

        if ($paperUpload->file_url && Storage::disk(config('filesystems.default'))->exists($paperUpload->file_url)) {
            Storage::disk(config('filesystems.default'))->delete($paperUpload->file_url);
        }

        $paperUpload->delete();

        return response()->json(['message' => 'Paper deleted successfully']);
    }


    public function incrementViews($id, Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $paper = PaperUploads::findOrFail($id);
        $sessionId = $request->input('session_id');

        $alreadyViewed = PaperViews::where('paper_upload_id', $id)
            ->where('session_id', $sessionId)
            ->exists();
        
        if (!$alreadyViewed) {
            PaperViews::create([
                'paper_upload_id' => $id,
                'user_id' => auth('sanctum')->id(), // Assuming you want to associate the view with the authenticated user
                'session_id' => $sessionId,
                'viewed_at' => now(),
            ]);

            $paper->increment('views_count');
        }

        return response()->json(['message' => 'View count incremented successfully', 'views_count' => $paper->views_count]);
    }
    
    public function viewFile(PaperUploads $PaperUpload)
    {
        $path = $PaperUpload->file_url;

        if (!Storage::disk(config('filesystems.default'))->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file(storage_path('app/public/' . $path));
    }
}

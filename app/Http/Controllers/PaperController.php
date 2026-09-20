<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperUploads;
use App\Models\PaperViews;
use App\Http\Requests\StorePaperRequest;
use App\Services\HandlesPapersUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

        //filtering by campus, department, course, year, and paper_type
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
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
        $query = $this->paperQuery($request);

        // Pagination
        $perPage = $request->integer('per_page', 5); // Default to 5 if not provided
        $perPage = min($perPage, 10); // Limit to a maximum of 10
        $papers = $query->paginate($perPage);

        return response()->json($papers);
    }

    // public function capstone(Request $request)
    // {
    //     return response()->json(
    //         $this->paperQuery($request)
    //             ->where('paper_type', 'capstone')
    //             ->paginate($request->integer('per_page', 5))
    //     );
    // }

    // public function thesis(Request $request)
    // {
    //     return response()->json(
    //         $this->paperQuery($request)
    //             ->where('paper_type', 'thesis')
    //             ->paginate($request->integer('per_page', 5))
    //     );
    // }

    public function analytics()
    {
        $totalPapers = PaperUploads::count();

        $papersCapstone = PaperUploads::where('paper_type', 'capstone')->count();
        $papersThesis = PaperUploads::where('paper_type', 'thesis')->count();

        // $papersByCategory = PaperUploads::select('category_id', DB::raw('count(*) as total'))
        //     ->groupBy('category_id')
        //     ->with('category:id,name') // Eager load the category relationship to get the name
        //     ->get();

        $papersByCampus = PaperUploads::select('campus_id', DB::raw('count(*) as total'))
            ->groupBy('campus_id')
            ->get();

        // $mostViewedPapers = PaperUploads::orderBy('views_count', 'desc')
        //     ->take(5)
        //     ->get();

        return response()->json([
            'total_papers' => $totalPapers,
            'papers_capstone' => $papersCapstone,
            'papers_thesis' => $papersThesis,
            'papers_by_campus' => $papersByCampus,
            // 'papers_by_category' => $papersByCategory,
            // 'most_viewed_papers' => $mostViewedPapers,
        ]);
    }

    public function store(StorePaperRequest $request, HandlesPapersUploads $uploader)
    {

        $validated = $request->validated();
        $file = $request->file('file');

        $filedata = $uploader->storefile(
            $file,
            $validated['campus_id'],
            $validated['department_id'],
            $validated['program_id']
        );

        try {
            $paperUpload = PaperUploads::create(array_merge($validated, $filedata));
        } catch (\Exception $e) {
            // If there's an error during the database operation, delete the uploaded file
            Storage::disk('public')->delete($filedata['file_url']);
            return response()->json(['message' => 'Failed to upload paper', 'error' => $e->getMessage()], 500);
        }


        return response()->json(['message' => 'Paper uploaded successfully', 'data' => $paperUpload], 201);
    }

    public function show($id)
    {
        $paperUpload = PaperUploads::findOrFail($id);
        $paperUpload->paper_type = ucfirst($paperUpload->paper_type); // Ensure paper_type is included in the response
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
                Storage::disk('public')->delete($filedata['file_url']);
                return response()->json(['message' => 'Failed to update paper', 'error' => $e->getMessage()], 500);
            }

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            $paperUpload->update(array_merge($validated));
        }

        return response()->json(['message' => 'Paper updated successfully', 'data' => $paperUpload]);
    }

    public function destroy($id)
    {
        $paperUpload = PaperUploads::findOrFail($id);

        if ($paperUpload->file_url && Storage::disk('public')->exists($paperUpload->file_url)) {
            Storage::disk('public')->delete($paperUpload->file_url);
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
}

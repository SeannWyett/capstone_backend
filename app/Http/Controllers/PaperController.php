<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperUploads;
use App\Http\Requests\StorePaperRequest;
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
                $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
            });
        }

        //filtering by campus, department, course, year, and paper_type
        if ($request->filled('campus')) {
            $query->where('campus', $request->campus);
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('course')) {
            $query->where('course', $request->course);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('paper_type')) {
            $query->where('paper_type', $request->paper_typex);
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

    public function capstone(Request $request)
    {
        return response()->json(
            $this->paperQuery($request)
                ->where('paper_type', 'capstone')
                ->paginate($request->integer('per_page', 5))
        );
    }

    public function thesis(Request $request)
    {
        return response()->json(
            $this->paperQuery($request)
                ->where('paper_type', 'thesis')
                ->paginate($request->integer('per_page', 5))
        );
    }

    public function analytics()
    {
        $totalPapers = PaperUploads::count();

        $papersCapstone = PaperUploads::where('paper_type', 'capstone')->count();
        $papersThesis = PaperUploads::where('paper_type', 'thesis')->count();

        // $papersByCategory = PaperUploads::select('category_id', DB::raw('count(*) as total'))
        //     ->groupBy('category_id')
        //     ->with('category:id,name') // Eager load the category relationship to get the name
        //     ->get();

        $papersByBulan = PaperUploads::where('campus', 'Bulan')->count();
        
        // $mostViewedPapers = PaperUploads::orderBy('views_count', 'desc')
        //     ->take(5)
        //     ->get();

        return response()->json([
            'total_papers' => $totalPapers,
            'papers_capstone' => $papersCapstone,
            'papers_thesis' => $papersThesis,
            'papers_by_bulan' => $papersByBulan,
            // 'papers_by_category' => $papersByCategory,
            // 'most_viewed_papers' => $mostViewedPapers,
        ]);
    }

    public function store(StorePaperRequest $request)
    {
        $file = $request->file('file');

        $campus = $request->input('campus');
        $department = $request->input('department');
        $course = $request->input('course');

        $folderPath = sprintf('papers/%s/%s/%s', $campus, $department, $course);
        $path = $file->store($folderPath, 'public'); // Store the file in the specified  directory of the public disk

        $validated = $request->validated();
        $validated['file_url'] = $path;
        $validated['original_filename'] = $file->getClientOriginalName();
        $validated['file_size'] = $file->getSize();

        // Create a new PaperUploads record
        $paperUpload = PaperUploads::create($validated);

        return response()->json(['message' => 'Paper uploaded successfully', 'data' => $paperUpload], 201);
    }

    public function show($id)
    {
        $paperUpload = PaperUploads::findOrFail($id);
        $paperUpload->paper_type = ucfirst($paperUpload->paper_type); // Ensure paper_type is included in the response
        return response()->json($paperUpload);
    }

    public function update(StorePaperRequest $request, $id)
    {
        $paperUpload = PaperUploads::findOrFail($id);
        $validated = $request->validated();

        // If a new file is uploaded, handle the file storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $campus = $request->input('campus');
            $department = $request->input('department');
            $course = $request->input('course');

            $folderPath = sprintf('papers/%s/%s/%s', $campus, $department, $course);
            $path = $file->store($folderPath, 'public'); // Store the file in the specified directory of the public disk

            // Update file-related fields
            $validated['file_url'] = $path;
            $validated['original_filename'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        // Update the PaperUploads record
        $paperUpload->update($validated);

        return response()->json(['message' => 'Paper updated successfully', 'data' => $paperUpload]);
    }

    public function destroy($id)
    {
        $paperUpload = PaperUploads::findOrFail($id);
        $paperUpload->delete();

        return response()->json(['message' => 'Paper deleted successfully']);
    }
}

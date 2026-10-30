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
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Arr;
use App\Http\Resources\PaperDisplayResource;


class PaperController extends Controller
{
    private function filterValues(Request $request, string $key): array
    {
        $value = $request->input($key);
        
        if (is_array($value)) {
            return $value;
        }

        return explode(',', $value);
    }

    private function paperQuery(Request $request)
    {
        $query = PaperUploads::query();

        // search logic(by title, and categories)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->whereLike('title',  "%{$search}%")
                        ->orWhereLike('researchers', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->whereLike('name', "%{$search}%");
                        });
            });
        }

        //filtering by campus, college, program, year, and paper_type
        if ($request->filled('campus_id')) {
            $query->whereIn(
                'campus_id', 
                $this->filterValues($request, 'campus_id')
            );
        }
        if ($request->filled('college_id')) {
            $query->whereIn(
                'college_id', 
                $this->filterValues($request, 'college_id')
            );
        }
        if ($request->filled('program_id')) {
            $query->whereIn(
                'program_id', 
                $this->filterValues($request, 'program_id')
            );
        }
        if ($request->filled('year')) {
            $query->whereIn(
                'year', 
                $this->filterValues($request, 'year')
            );
        }
        if ($request->filled('paper_type')) {
            $query->whereIn(
                'paper_type', 
                $this->filterValues($request, 'paper_type')
            );
        }

        if ($request->filled('category_id')) {
            $query->whereIn(
                'category_id',
                $this->filterValues($request, 'category_id')
            );
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

    public function analytics(Request $request)
    {
        // return response()->json(
            // Cache::remember('paper-analytics', now()->addMinutes(15), function () {
                $papersBytype = PaperUploads::select('paper_type', DB::raw('count(*) as total'))
                    ->groupBy('paper_type')
                    ->pluck('total', 'paper_type')
                    ->toArray();

                $papersByCampus = PaperUploads::select('campus_id', DB::raw('count(*) as total'))
                    ->groupBy('campus_id')
                    ->with('campus:id,name') // Eager load the campus relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->campus->name ??'Unknown' => $paper->total];
                    })
                    ->toArray();

                $papersByCollege = PaperUploads::select('college_id', DB::raw('count(*) as total'))
                    ->groupBy('college_id')
                    ->with('college:id,name') // Eager load the college relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->college->name ??'Unknown' => $paper->total];
                    })
                    ->toArray();
                
                $papersByProgram = PaperUploads::select('program_id', DB::raw('count(*) as total'))
                    ->groupBy('program_id')
                    ->with('program:id,name') // Eager load the program relationship
                    ->get()
                    ->mapWithKeys(function ($paper) {
                        return [$paper->program->name ??'Unknown' => $paper->total];
                    })
                    ->toArray();

                $papersByYear = PaperUploads::select('year', DB::raw('count(*) as total'))
                    ->groupBy('year')
                    ->pluck('total', 'year')
                    ->toArray();

                $papersByCategory = PaperUploads::select(
                        'category_id',
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('category_id')
                    ->with('category:id,name')
                    ->get()
                    ->groupBy(function ($paper) {
                        return $paper->category->name ?? 'Unknown';
                    })
                    ->map(function ($categories) {
                        return $categories->sum('total');
                    })
                    ->toArray();

                $mostViewedPapers = PaperUploads::with(['campus:id,name', 'college:id,name', 'program:id,name', 'category:id,name'])
                    ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
                    ->orderBy('views_count', 'desc')
                    ->take(5)
                    ->get(['id', 'title', 'views_count', 'campus_id', 'college_id', 'program_id', 'category_id', 'researchers', 'year', 'paper_type', 'views_count'])
                    ->map(function ($paper) {
                        return [
                            'id' => $paper->id,
                            'title' => $paper->title,
                            'views_count' => $paper->views_count,
                            'campus' => $paper->campus?->name,
                            'college' => $paper->college?->name,
                            'program' => $paper->program?->name,
                            'category' => $paper->category?->name,
                            'year' => $paper->year,
                            'researchers' => $paper->researchers,
                            'paper_type' =>$paper->paper_type,
                            'views' => $paper->views_count,
                        ];
                    })
                    ->toArray();

                $papersBytype_per_campus = PaperUploads::select('campus_id', 'paper_type', DB::raw('count(*) as total'))
                    ->groupBy('campus_id', 'paper_type')
                    ->with('campus:id,name')
                    ->get()
                    ->groupBy(fn ($row) => $row->campus->name ?? 'Unknown')
                    ->map(fn ($row) => $row->pluck('total', 'paper_type'))
                    ->toArray();
                
                return response()->json([
                    'total_papers' => PaperUploads::count(),
                    'papers_capstone' => $papersBytype['capstone'] ?? 0,
                    'papers_thesis' => $papersBytype['thesis'] ?? 0,
                    'papers_by_campus' => $papersByCampus,
                    'papers_by_college' => $papersByCollege,
                    'papers_by_program' => $papersByProgram,
                    'papers_by_year' => $papersByYear,
                    'papers_by_category' => $papersByCategory,
                    'most_viewed_papers' => $mostViewedPapers,
                    'papers_by_campus_and_type' => $papersBytype_per_campus,
                ]);
            // })
        // );
        
    }

    public function store(StorePaperRequest $request, HandlesPapersUploads $uploader)
    {
        Gate::authorize('create', PaperUploads::class); 

        $user = $request->user();
        $validated = $request->validated();

        if ($user->role === 'campus_admin') {
            abort_if(
                $user->campus_id === null,
                403,
                'Your account is not assigned to a campus.'
            );
        }

        $filedata = $uploader->storefile(
            $request->file('file'),
            $validated['campus_id'],
            $validated['college_id'],
            $validated['program_id']
        );

        try {
            $paperUpload = DB::transaction(fn () => PaperUploads::create([
                ...Arr::except($validated, ['file']),
                'file_url' => $filedata['file_url'],
                'original_filename' => $filedata['original_filename'],
                'file_size' => $filedata['file_size'],  
            ]));
        } catch (Throwable $e) {
            $this->cleanupFile($filedata['file_url'] ?? null);

            report($e); // goes to your logger / Sentry

            return response()->json(['message' => 'Failed to upload paper.'], 500);
        }

        // cache()->forget('paper-analytics');
        return response()->json(['message' => 'Paper uploaded successfully', 'data' => $paperUpload], 201);
    }

    private function cleanupFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk(config('filesystems.default'))->delete($path);
        } catch (Throwable $e) {
            Log::warning('Failed to clean up orphaned upload', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function show(Request $request, $id)
    {   
        $paperUpload = PaperUploads::with(['campus.policy','campus:id,name', 'college:id,name', 'program:id,name', 'category:id,name'])
            ->findOrFail($id);

        $user = null;
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);
            $user = $accessToken?->tokenable;
        }
        if (Gate::forUser($user)->denies('viewMetadata', $paperUpload)) {
            return response()->json(['message' => 'You are not allowed to view this paper metadata'], 403);
        }

        return response()->json(['data' => $paperUpload]);
    }

    public function update(StorePaperRequest $request, HandlesPapersUploads $uploader, $id)
    {
        $paperUpload = PaperUploads::findOrFail($id);

        Gate::authorize('update', $paperUpload);

        $user = $request->user();
        $attributes = Arr::except($request->validated(), ['file']);

        if ($user->role === 'campus_admin') {
            abort_if(
                $user->campus_id === null,
                403,
                'Your account is not assigned to a campus.'
            );
        }

        $oldPath = $paperUpload->file_url;
        $filedata = null;

        // Store the new file first (outside the try: if this fails, there is nothing to clean up)
        if ($request->hasFile('file')) {
            $filedata = $uploader->storefile(
                $request->file('file'),
                $attributes['campus_id'],
                $attributes['college_id'],
                $attributes['program_id']
            );

            $attributes = [
                ...$attributes,
                ...Arr::only($filedata, ['file_url', 'original_filename', 'file_size']),
            ];
        }

        try {
            $paperUpload->update($attributes);
        } catch (Throwable $e) {
            // DB update failed: remove the new file, keep the old one
            $this->cleanupFile($filedata['file_url'] ?? null);

            report($e);

            return response()->json(['message' => 'Failed to update paper.'], 500);
        }

        // DB now points at the new file, so the old one is safe to remove.
        // A failure here is only logged and doesn't fail the request.
        if ($filedata) {
            $this->cleanupFile($oldPath);
        }

        // cache()->forget('paper-analytics');

        return response()->json(['message' => 'Paper updated successfully', 'data' => $paperUpload]);
    }

    public function destroy($id)
    {   
        $paperUpload = PaperUploads::findOrFail($id);

        Gate::authorize('delete', $paperUpload);

        if ($paperUpload->file_url && Storage::disk(config('filesystems.default'))->exists($paperUpload->file_url)) {
            Storage::disk(config('filesystems.default'))->delete($paperUpload->file_url);
        }

        $paperUpload->delete();

        // cache()->forget('paper-analytics');

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

        // cache()->forget('paper-analytics');

        return response()->json(['message' => 'View count incremented successfully', 'views_count' => $paper->views_count]);
    }
    
    public function viewFile(Request $request, PaperUploads $paperUpload)
    {   
        $paperUpload->load('campus.policy'); // Eager load the campus and its policy

        if (Gate::forUser($request->user('sanctum'))->denies('viewFile', $paperUpload)) {
            return response()->json(['message' => 'You are not allowed to view this file'], 403);
        }

        $disk = config('filesystems.default');
        $path = $paperUpload->file_url;

        if (!Storage::disk($disk)->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response(Storage::disk($disk)->get($path), 200)
            ->header('Content-Type', Storage::disk($disk)->mimeType($path))
            ->header('Content-Disposition', 'inline; filename="' . $paperUpload->original_filename . '"');
    }

    public function campusAdmindashboard(Request $request)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['campus_admin', 'super_admin'])) {
            abort(403, 'You do not have permission to access this dashboard.');
        }

        $query = $this->paperQuery($request)
            ->with([
                'campus:id,name',
                'college:id,name',
                'program:id,name',
                'category:id,name'
            ]);

        if ($user->role === 'campus_admin') {
            $query->where('campus_id', $user->campus_id);
        }

        $perPage = min($request->integer('per_page', 5), 10);

        $papers = $query->paginate($perPage);

        return response()->json($papers);
    }
}
                










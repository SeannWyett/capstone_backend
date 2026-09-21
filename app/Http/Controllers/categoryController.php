<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Validation\Rule;

class categoryController extends Controller
{
    public function validateCategory(Request $request, int $categoryId = null)
    {
        $request->validate([
            'name' => ['required', 
                'string', 
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $query->where('program_id', $request->input('program_id')))
                    ->ignore($categoryId),
            ],
            'program_id' => 'required|exists:programs,id',
        ]);
    }

    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $this->validateCategory($request);

        $category = new Category();
        $category->name = $request->input('name');
        $category->program_id = $request->input('program_id');
        $category->save();

        return response()->json(['message' => 'Category added successfully', 'category' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $this->validateCategory($request, $category->id);

        $category->name = $request->input('name');
        $category->program_id = $request->input('program_id');
        $category->save();

        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}

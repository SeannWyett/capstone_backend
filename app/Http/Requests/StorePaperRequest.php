<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // $user = $this->user();

        // if ($user && $user->role === 'admin') { // Check if the user is authenticated and has the 'admin' role
        //     return true;
        // } else {
        //     return false;
        // }

        return true; // Change this to true if you want to allow all users to make this request
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'paper_type' => 'required|in:capstone,thesis', // Ensure paper_type is one of the allowed values
            'abstract' => 'nullable|string',
            // 'file_url' => 'required|string|max:255',
            'file_size' => 'nullable|integer',
            'year' => 'required|integer',
            'campus' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            // 'campus_id' => 'required|integer|exists:campuses,id',
            // 'department_id' => 'required|integer|exists:departments,id',
            // 'program_id' => 'required|integer|exists:programs,id',
            // 'category_id' => 'required|integer|exists:categories,id',
            'researchers' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:10240', // Max file size of 10MB
        ];
    }
}

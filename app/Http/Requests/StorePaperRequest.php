<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\PaperUploads;
use Illuminate\Validation\Rule;

class StorePaperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->isMethod('POST')
            ? ($this->user()?->can('create', PaperUploads::class) ?? false)
            : $this->user() !== null; // Change this to true if you want to allow all users to make this request
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $isCampusAdmin = $this->user()?->role === 'campus_admin';

        return [
            'title' => 'required|string|max:255',
            'paper_type' => 'required|in:capstone,thesis', // Ensure paper_type is one of the allowed values
            'abstract' => 'nullable|string',
            // 'file_url' => 'required|string|max:255',
            'year' => 'required|integer',
            // 'campus' => 'required|string|max:255',
            // 'department' => 'required|string|max:255',
            // 'course' => 'required|string|max:255',
            'campus_id' => [
                Rule::requiredIf(! $isCampusAdmin),
                'nullable',
                'integer',
                'exists:campuses,id',
                    ...($isCampusAdmin
                        ? [Rule::in([(int) $this->user()->campus_id])]
                        : []),
            ],

            // Adjust the column names to your schema
            'college_id' => [
                'required', 'integer',
                Rule::exists('colleges', 'id')->where(
                    'campus_id',
                    $this->input('campus_id')
                ),
            ],
            'program_id' => [
                'required', 'integer',
                Rule::exists('programs', 'id')->where(
                    'college_id', 
                    $this->input('college_id')
                ),
            ],
            'category_id' => 'nullable|integer|exists:categories,id',
            'researchers' => 'required|string|max:255',
            'file' => $this->isMethod('PUT') || $this->isMethod('PATCH')
                ? 'sometimes|file|mimes:pdf|max:10240'
                : 'required|file|mimes:pdf|max:10240', // Max file size of 10MB
        ];
    }
}

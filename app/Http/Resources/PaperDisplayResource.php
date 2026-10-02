<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class PaperDisplayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'campus' => $this->campus?->name,
            'college' => $this->college?->name,
            'program' => $this->program?->name,
            'category' => $this->category?->name,
            'paper_type' => ucfirst($this->paper_type),
            'year' => $this->year,
            'researchers' => $this->researchers,
            'views_count' => $this->views_count,
            'created_at' => $this->created_at,

            'can_view_metadata' => Gate::forUser($user)->allows('viewMetadata', $this->resource),
            'can_view_file' => Gate::forUser($user)->allows('viewFile', $this->resource),
            'can_download' => Gate::forUser($user)->allows('download', $this->resource),
        ];
    }
}

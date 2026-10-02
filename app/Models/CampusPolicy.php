<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Campus;

class CampusPolicy extends Model
{
    protected $fillable = [
        'campus_id',
        'guest_can_view_metadata', 'guest_can_view_file', 'guest_can_download',
        'student_view_metadata_scope', 'student_view_file_scope', 'student_download_scope',
    ];

    protected $casts = [
        'guest_can_view_metadata' => 'boolean',
        'guest_can_view_file' => 'boolean',
        'guest_can_download' => 'boolean',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}

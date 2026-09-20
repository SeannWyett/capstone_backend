<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperViews extends Model
{
    protected $fillable = [
        'paper_upload_id',
        'user_id',
        'session_id',
        'viewed_at',
    ];

    public $timestamps = false;

    public function paperUpload()
    {
        return $this->belongsTo(PaperUploads::class, 'paper_upload_id');
    }
}

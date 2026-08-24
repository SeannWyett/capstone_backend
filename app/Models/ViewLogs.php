<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewLogs extends Model
{
    protected $fillable = [
        'user_id',
        'paper_upload_id',
        'viewed_at',
    ];

    public function paperUpload()
    {
        return $this->belongsTo(PaperUploads::class);
    }
}

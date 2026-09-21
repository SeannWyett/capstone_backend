<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'program_id',
    ];

    public function paperUploads()
    {
        return $this->hasMany(PaperUploads::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}

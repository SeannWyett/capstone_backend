<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'campus_id',
    ];

    public function paperUploads()
    {
        return $this->hasMany(PaperUploads::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}

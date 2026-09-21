<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'name',
        'college_id',
    ];

    public function paperUploads()
    {
        return $this->hasMany(PaperUploads::class);
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'college_id', 'campus_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'name',
        'department_id',
    ];

    public function paperUploads()
    {
        return $this->hasMany(PaperUploads::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'department_id', 'campus_id');
    }
}

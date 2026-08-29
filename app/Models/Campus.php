<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    protected $fillable = [
        'name',
    ];

    public function paperUploads()
    {
        return $this->hasMany(PaperUploads::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}

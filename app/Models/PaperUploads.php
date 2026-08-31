<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaperUploads extends Model
{
    protected $fillable = [
        'title',
        'paper_type',
        'abstract',
        'file_url',
        'original_filename',
        'file_size',
        'year',
        'campus',
        'department',
        'course',
        // 'category_id',
        'views_count',
        'researchers',
        'viewable',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function viewLogs()
    {
        return $this->hasMany(ViewLogs::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}

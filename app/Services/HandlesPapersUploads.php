<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class HandlesPapersUploads
{
    public function storefile(UploadedFile $file, int $campus, int $college, int $course): array
    {
        // Define the folder path based on campus, department, and course
        $folderPath = sprintf('papers/%d/%d/%d', $campus, $college, $course);
        $path = $file->store($folderPath, config('filesystems.default'));

        // Store the file in the specified directory of the public disk
        return [
            'file_url' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}

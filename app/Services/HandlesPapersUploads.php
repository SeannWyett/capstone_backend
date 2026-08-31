<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class HandlesPapersUploads
{
    public function storefile(UploadedFile $file, string $campus, string $department, string $course): array
    {
        // Define the folder path based on campus, department, and course
        $folderPath = sprintf('papers/%s/%s/%s', $campus, $department, $course);
        $path = $file->store($folderPath, 'public');

        // Store the file in the specified directory of the public disk
        return [
            'file_url' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}

<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait MediaFiles
{
    /**
     * Store File
     * @param $file object from Illuminate\Http\Request
     * @param $destinationPath
     * @return string | file path
     */
    public function storeFile($file, $destinationPath = 'uploads')
    {   if (empty($file->getError())) {
            $filePath = $file->store($destinationPath);
            return $filePath;
        }
        return '';
    }
}

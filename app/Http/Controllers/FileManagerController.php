<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->get('folder', ''); // Get current folder from URL
        $basePath = storage_path('app/invoices'); // Change to your folder
        $currentPath = $basePath . ($folder ? '/' . $folder : '');

        $folders = [];
        $files = [];

        if (File::exists($currentPath)) {
            // Get subdirectories
            $directories = File::directories($currentPath);

            foreach ($directories as $dir) {
                $folderName = basename($dir);
                $fileCount = count(File::files($dir));

                $folders[] = [
                    'name' => $folderName,
                    'path' => $folder ? $folder . '/' . $folderName : $folderName,
                    'file_count' => $fileCount,
                    'modified' => date('Y-m-d H:i:s', File::lastModified($dir)),
                ];
            }

            // Get files in current directory
            $filesInDir = File::files($currentPath);

            foreach ($filesInDir as $file) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'path' => $folder,
                ];
            }
        }

        // Sort folders by name descending (newest dates first)
        usort($folders, function($a, $b) {
            return strcmp($b['name'], $a['name']);
        });

        // Sort files by modified date
        usort($files, function($a, $b) {
            return strcmp($b['modified'], $a['modified']);
        });

        return view('admin.files', compact('folders', 'files', 'folder'));
    }

    public function download(Request $request)
    {
        $folder = $request->get('folder', '');
        $filename = $request->get('file');

        $path = storage_path('app/invoices/' . ($folder ? $folder . '/' : '') . $filename);

        if (!File::exists($path)) {
            abort(404, 'File not found');
        }

        return response()->download($path);
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

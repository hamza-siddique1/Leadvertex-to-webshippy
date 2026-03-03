<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use setasign\Fpdi\Fpdi;
use ZipArchive;

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

        return view('invoices.index', compact('folders', 'files', 'folder'));
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

    public function downloadFolder(Request $request)
    {
        $folder = $request->get('folder', '');
        $basePath = storage_path('app/invoices');
        $folderPath = $basePath . ($folder ? '/' . $folder : '');

        if (!File::exists($folderPath) || !File::isDirectory($folderPath)) {
            abort(404, 'Folder not found');
        }

        // Get all files in the folder
        $files = File::allFiles($folderPath);

        if (count($files) === 0) {
            abort(400, 'Folder is empty');
        }

        // Create temporary ZIP file
        $zipFileName = ($folder ? str_replace('/', '_', $folder) : 'invoices') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Create temp directory if it doesn't exist
        if (!File::exists(storage_path('app/temp'))) {
            File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        // Create ZIP archive
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // Add all files from folder
            foreach ($files as $file) {
                $relativePath = str_replace($folderPath . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }

            $zip->close();

            // Download and delete temp file after sending
            return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        abort(500, 'Could not create ZIP file');
    }

    public function mergePDFsFolder(Request $request)
    {
        $folder = $request->get('folder', '');
        $basePath = storage_path('app/invoices');
        $folderPath = $basePath . ($folder ? '/' . $folder : '');

        // Validate folder exists
        if (!File::exists($folderPath) || !File::isDirectory($folderPath)) {
            abort(404, 'Folder not found');
        }

        // Get all PDF files
        $files = File::files($folderPath);
        $pdfFiles = [];

        foreach ($files as $file) {
            // Only include PDF files
            if (strtolower($file->getExtension()) === 'pdf') {
                $pdfFiles[] = $file->getPathname();
            }
        }

        // Check if any PDFs found
        if (count($pdfFiles) === 0) {
            abort(400, 'No PDF files found in this folder');
        }

        // Sort files by name
        sort($pdfFiles);

        try {
            // ========== START MERGING ==========

            // Step 1: Create new FPDI instance
            $pdf = new Fpdi();

            // Step 2: Loop through each PDF file
            foreach ($pdfFiles as $pdfFile) {

                // Skip if file doesn't exist
                if (!file_exists($pdfFile)) {
                    continue;
                }

                try {
                    // Step 3: Load the PDF and count pages
                    $pageCount = $pdf->setSourceFile($pdfFile);

                    // Step 4: Import each page
                    for ($i = 1; $i <= $pageCount; $i++) {
                        // Import page template
                        $template = $pdf->importPage($i);

                        // Add blank page to merged PDF
                        $pdf->addPage();

                        // Use the imported page template
                        $pdf->useTemplate($template);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other PDFs
                    \Log::warning('Could not process PDF: ' . $pdfFile);
                    continue;
                }
            }

            // Step 5: Generate PDF as string (not file)
            $pdfContent = $pdf->Output('S');

            // ========== END MERGING ==========

            // Step 6: Create filename
            $fileName = ($folder ? str_replace('/', '_', $folder) : 'invoices')
                        . '_merged' . '.pdf';

            // Step 7: Return as download
            return response()->streamDownload(
                function () use ($pdfContent) {
                    echo $pdfContent;
                },
                $fileName,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ]
            );

        } catch (\Exception $e) {
            \Log::error('PDF Merge Error: ' . $e->getMessage());
            abort(500, 'Could not merge PDF files');
        }
    }
}

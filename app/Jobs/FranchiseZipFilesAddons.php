<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\Admin\Setting;

class FranchiseZipFilesAddons implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     * 
     */
    protected $zipFileName;
    protected $zipFilePath;
    public function __construct($zipFileName, $zipFilePath)
    {
        $this->zipFileName = $zipFileName;
        $this->zipFilePath = $zipFilePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $uploadPath = storage_path('app/public/');
        $zip = new ZipArchive;
        if ($zip->open($this->zipFilePath) === TRUE) {

            // Extract ZIP to temp folder
            $tempExtractPath = $uploadPath . '/extracted';
            if (!file_exists($tempExtractPath)) {
                mkdir($tempExtractPath, 0777, true);
            }

            $zip->extractTo($tempExtractPath);
            $zip->close();


             // ----------------------------------
            // CHECK REQUIRED FOLDERS
            // ----------------------------------
            $requiredFolders = [
                'franchise',
                'Auth',
                'Table',
                'Controller',
                'Model',
                'Payment-Model',
                'Franchise-Management',
                'Routes',
                'Filter',
                'Transformer-Franchise',
                'Transformer-Payment',
                'model-user'
            ];

            // Get extracted top-level folders
            $extractedDirs = array_filter(glob($tempExtractPath . '/*'), 'is_dir');
            $foundFolders = array_map('basename', $extractedDirs);

            // Find missing folders
            $missing = array_diff($requiredFolders, $foundFolders);

            // If ANY required folder is missing → stop and do NOT update settings
            if (!empty($missing)) {
                File::deleteDirectory($tempExtractPath);
                File::delete($this->zipFilePath);
                return;
            }

            //  Loop through extracted files
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempExtractPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            $fileMoved = true;

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($tempExtractPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath); 
                    $targetPath = null;

                    // Determine destination based on top folder
                    if (str_starts_with($relativePath, 'franchise/')) {
                        $targetPath = base_path('resources/js/Pages/' . substr($relativePath, strlen('franchise/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Auth/')) {
                        $targetPath = base_path('resources/js/Pages/Auth/' . substr($relativePath, strlen('Auth/')));
                    }
                    elseif (str_starts_with($relativePath, 'Table/')) {
                        $targetPath = base_path('database/migrations/' . substr($relativePath, strlen('Table/')));
                    }
                    elseif (str_starts_with($relativePath, 'Controller/')) {
                        $targetPath = base_path('app/Http/Controllers/' . substr($relativePath, strlen('Controller/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Model/')) {
                        $targetPath = base_path('app/Models/Admin/' . substr($relativePath, strlen('Model/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Payment-Model/')) {
                        $targetPath = base_path('app/Models/Payment/' . substr($relativePath, strlen('Payment-Model/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Franchise-Management/')) {
                        $targetPath = base_path('resources/js/Pages/pages/' . substr($relativePath, strlen('Franchise-Management/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Routes/')) {
                        $targetPath = base_path('routes/web/' . substr($relativePath, strlen('Routes/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Filter/')) {
                        $targetPath = base_path('app/Base/Filters/Admin/' . substr($relativePath, strlen('Filter/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Transformer-Payment/')) {
                        $targetPath = base_path('app/Transformers/Payment/' . substr($relativePath, strlen('Transformer-Payment/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Transformer-Franchise/')) {
                        $targetPath = base_path('app/Transformers/' . substr($relativePath, strlen('Transformer-Franchise/')));
                    } 
                     
                    elseif (str_starts_with($relativePath, 'model-user/')) {
                        $targetPath = base_path('app/Models/' . substr($relativePath, strlen('model-user/')));
                    }

                if (!$targetPath) {
                    $fileMoved = false; //  Mark that at least one file was moved
                    continue;
                }
                
                    File::ensureDirectoryExists(dirname($targetPath));
                    File::move($file->getPathname(), $targetPath);
                }
            }

            //  Clean up temp files
            File::deleteDirectory($tempExtractPath);
            File::delete($this->zipFilePath);

            if ($fileMoved) {
                Setting::updateOrCreate(
                    ['name' => 'franchise-addons'],
                    ['value' => '1']
                );
            }
        }
    }
}

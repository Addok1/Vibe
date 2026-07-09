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

class AgentZipFilesAddons implements ShouldQueue
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
                'agent',
                'Auth',
                'table',
                'Components',
                'Layouts',
                'controller',
                'model',
                'model-payment',
                'web-create-request',
                'agent-management',
                'routes',
                'filter',
                'transformer',
                'transformer-agent'
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
                    if (str_starts_with($relativePath, 'agent/')) {
                        $targetPath = base_path('resources/js/Pages/' . substr($relativePath, strlen('agent/')));
                    } 
                    elseif (str_starts_with($relativePath, 'Auth/')) {
                        $targetPath = base_path('resources/js/Pages/Auth/' . substr($relativePath, strlen('Auth/')));
                    }
                    elseif (str_starts_with($relativePath, 'table/')) {
                        $targetPath = base_path('database/migrations/' . substr($relativePath, strlen('table/')));
                    }
                    elseif (str_starts_with($relativePath, 'Components/')) {
                        $targetPath = base_path('resources/js/Components/' . substr($relativePath, strlen('Components/')));
                    }
                    elseif (str_starts_with($relativePath, 'Layouts/')) {
                        $targetPath = base_path('resources/js/Layouts/' . substr($relativePath, strlen('Layouts/')));
                    }
                    elseif (str_starts_with($relativePath, 'controller/')) {
                        $targetPath = base_path('app/Http/Controllers/' . substr($relativePath, strlen('controller/')));
                    } 
                     elseif (str_starts_with($relativePath, 'model/')) {
                        $targetPath = base_path('app/Models/Admin/' . substr($relativePath, strlen('model/')));
                    } 
                     elseif (str_starts_with($relativePath, 'model-payment/')) {
                        $targetPath = base_path('app/Models/Payment/' . substr($relativePath, strlen('model-payment/')));
                    } 
                    elseif (str_starts_with($relativePath, 'web-create-request/')) {
                        $targetPath = base_path('app/Http/Controllers/Web/Admin/' . substr($relativePath, strlen('web-create-request/')));
                    } 
                    elseif (str_starts_with($relativePath, 'agent-management/')) {
                        $targetPath = base_path('resources/js/Pages/pages/' . substr($relativePath, strlen('agent-management/')));
                    } 
                    elseif (str_starts_with($relativePath, 'routes/')) {
                        $targetPath = base_path('routes/web/' . substr($relativePath, strlen('routes/')));
                    } 
                    elseif (str_starts_with($relativePath, 'filter/')) {
                        $targetPath = base_path('app/base/filters/Admin/' . substr($relativePath, strlen('filter/')));
                    } 
                    elseif (str_starts_with($relativePath, 'transformer/')) {
                        $targetPath = base_path('app/Transformers/Payment/' . substr($relativePath, strlen('transformer/')));
                    } 
                    elseif (str_starts_with($relativePath, 'transformer-agent/')) {
                        $targetPath = base_path('app/Transformers/' . substr($relativePath, strlen('transformer-agent/')));
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
                    ['name' => 'agent-addons'],
                    ['value' => '1']
                );
            }
        }
    }
}

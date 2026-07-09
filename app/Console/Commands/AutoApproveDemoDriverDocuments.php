<?php

namespace App\Console\Commands;

use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Models\Admin\Driver;
use App\Models\Admin\DriverDocument;
use App\Models\Admin\DriverNeededDocument;
use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Database;

class AutoApproveDemoDriverDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:approve-driver-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-approve newly uploaded driver documents (demo only)';

    public function __construct(protected Database $database)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (env('APP_FOR') !== 'demo') {
            $this->info('APP_FOR is not demo. Skipping.');
            return Command::SUCCESS;
        }

        $pendingStatuses = [
            DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL,
            DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL,
        ];

        $updatedDocuments = 0;
        $approvedDrivers = 0;

        Driver::query()
            ->where(function ($q) use ($pendingStatuses) {
                $q->where('approve', '!=', 1)
                    ->orWhereHas('driverDocument', function ($docQ) use ($pendingStatuses) {
                        $docQ->whereIn('document_status', $pendingStatuses);
                    });
            })
            ->with(['driverDocument'])
            ->chunkById(200, function ($drivers) use ($pendingStatuses, &$updatedDocuments, &$approvedDrivers) {
                foreach ($drivers as $driver) {
                    $pendingDocs = $driver->driverDocument->filter(function ($doc) use ($pendingStatuses) {
                        return in_array($doc->document_status, $pendingStatuses, true);
                    });

                    foreach ($pendingDocs as $doc) {
                        /** @var DriverDocument $doc */
                        $doc->update([
                            'document_status' => DriverDocumentStatus::UPLOADED_AND_APPROVED,
                            'comment' => null,
                        ]);
                        $updatedDocuments++;
                    }

                    if ($this->shouldApproveDriver($driver)) {
                        if ((int) $driver->approve !== 1) {
                            $driver->update([
                                'approve' => 1,
                                'reason' => null,
                            ]);

                            $this->database
                                ->getReference('drivers/driver_' . $driver->id)
                                ->update([
                                    'approve' => 1,
                                    'reason' => null,
                                    'updated_at' => Database::SERVER_TIMESTAMP,
                                ]);

                            $approvedDrivers++;
                        }
                    }
                }
            });

        $this->info("Approved documents: {$updatedDocuments}; Approved drivers: {$approvedDrivers}");
        return Command::SUCCESS;
    }

    private function shouldApproveDriver(Driver $driver): bool
    {
        $accountTypes = $driver->owner_id ? ['fleet_driver', 'both'] : ['individual', 'both'];

        $requiredCount = DriverNeededDocument::active()
            ->where('is_required', true)
            ->whereIn('account_type', $accountTypes)
            ->count();

        if ($requiredCount === 0) {
            return true;
        }

        $approvedCount = $driver->driverDocument()
            ->where('document_status', DriverDocumentStatus::UPLOADED_AND_APPROVED)
            ->whereHas('driverNeededDocuments', function ($q) use ($accountTypes) {
                $q->where('active', true)
                    ->where('is_required', true)
                    ->whereIn('account_type', $accountTypes);
            })
            ->count();

        return $approvedCount >= $requiredCount;
    }
}

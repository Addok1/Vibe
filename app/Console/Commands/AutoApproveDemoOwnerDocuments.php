<?php

namespace App\Console\Commands;

use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Models\Admin\Owner;
use App\Models\Admin\OwnerDocument;
use App\Models\Admin\OwnerNeededDocument;
use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Database;

class AutoApproveDemoOwnerDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:approve-owner-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-approve newly uploaded owner documents (demo only)';

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
        $approvedOwners = 0;

        Owner::query()
            ->where(function ($q) use ($pendingStatuses) {
                $q->where('approve', '!=', 1)
                    ->orWhereHas('ownerDocument', function ($docQ) use ($pendingStatuses) {
                        $docQ->whereIn('document_status', $pendingStatuses);
                    });
            })
            ->with(['ownerDocument'])
            ->orderBy('id')
            ->chunk(200, function ($owners) use ($pendingStatuses, &$updatedDocuments, &$approvedOwners) {
                foreach ($owners as $owner) {
                    $pendingDocs = $owner->ownerDocument->filter(function ($doc) use ($pendingStatuses) {
                        return in_array($doc->document_status, $pendingStatuses, true);
                    });

                    foreach ($pendingDocs as $doc) {
                        /** @var OwnerDocument $doc */
                        $doc->update([
                            'document_status' => DriverDocumentStatus::UPLOADED_AND_APPROVED,
                            'comment' => null,
                        ]);
                        $updatedDocuments++;
                    }

                    if ($this->shouldApproveOwner($owner)) {
                        if ((int) $owner->approve !== 1) {
                            $owner->update(['approve' => 1]);

                            $this->database
                                ->getReference('owners/owner_' . $owner->id)
                                ->update([
                                    'approve' => 1,
                                    'updated_at' => Database::SERVER_TIMESTAMP,
                                ]);

                            $approvedOwners++;
                        }
                    }
                }
            });

        $this->info("Approved documents: {$updatedDocuments}; Approved owners: {$approvedOwners}");
        return Command::SUCCESS;
    }

    private function shouldApproveOwner(Owner $owner): bool
    {
        $requiredCount = OwnerNeededDocument::active()
            ->where('is_required', true)
            ->count();

        if ($requiredCount === 0) {
            return true;
        }

        $approvedCount = $owner->ownerDocument()
            ->where('document_status', DriverDocumentStatus::UPLOADED_AND_APPROVED)
            ->whereHas('ownerNeededDocuments', function ($q) {
                $q->where('active', true)->where('is_required', true);
            })
            ->count();

        return $approvedCount >= $requiredCount;
    }
}

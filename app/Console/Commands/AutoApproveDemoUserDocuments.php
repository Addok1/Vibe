<?php

namespace App\Console\Commands;

use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Models\Admin\UserDocument;
use App\Models\Admin\UserNeededDocument;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Kreait\Firebase\Contract\Database;

class AutoApproveDemoUserDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:approve-user-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-approve newly uploaded user documents (demo only)';

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
        $approvedUsers = 0;

        $userQuery = User::query();

        if (Schema::hasColumn('users', 'approve')) {
            $userQuery->where(function ($q) use ($pendingStatuses) {
                $q->where('approve', '!=', 1)
                    ->orWhereHas('userDocumentDetail', function ($docQ) use ($pendingStatuses) {
                        $docQ->whereIn('document_status', $pendingStatuses);
                    });
            });
        } else {
            $userQuery->whereHas('userDocumentDetail', function ($q) use ($pendingStatuses) {
                $q->whereIn('document_status', $pendingStatuses);
            });
        }

        $userQuery
            ->with(['userDocumentDetail'])
            ->chunkById(300, function ($users) use ($pendingStatuses, &$updatedDocuments, &$approvedUsers) {
                foreach ($users as $user) {
                    $pendingDocs = $user->userDocumentDetail->filter(function ($doc) use ($pendingStatuses) {
                        return in_array($doc->document_status, $pendingStatuses, true);
                    });

                    foreach ($pendingDocs as $doc) {
                        /** @var UserDocument $doc */
                        $doc->update([
                            'document_status' => DriverDocumentStatus::UPLOADED_AND_APPROVED,
                            'comment' => null,
                        ]);
                        $updatedDocuments++;
                    }

                    if ($this->shouldApproveUser($user)) {
                        $approveValue = (int) ($user->getAttribute('approve') ?? 0);
                        if ($approveValue !== 1) {
                            $attrs = ['approve' => 1];
                            if (Schema::hasColumn('users', 'reason')) {
                                $attrs['reason'] = null;
                            }

                            $user->forceFill($attrs)->save();

                            $firebaseUpdate = [
                                'approve' => 1,
                                'updated_at' => Database::SERVER_TIMESTAMP,
                            ];
                            if (Schema::hasColumn('users', 'reason')) {
                                $firebaseUpdate['reason'] = null;
                            }

                            $this->database
                                ->getReference('users/user_' . $user->id)
                                ->update($firebaseUpdate);

                            $approvedUsers++;
                        }
                    }
                }
            });

        $this->info("Approved documents: {$updatedDocuments}; Approved users: {$approvedUsers}");
        return Command::SUCCESS;
    }

    private function shouldApproveUser(User $user): bool
    {
        $requiredCount = UserNeededDocument::active()
            ->where('is_required', true)
            ->count();

        if ($requiredCount === 0) {
            return true;
        }

        $approvedCount = $user->userDocumentDetail()
            ->where('document_status', DriverDocumentStatus::UPLOADED_AND_APPROVED)
            ->whereHas('userNeededDocuments', function ($q) {
                $q->where('active', true)->where('is_required', true);
            })
            ->count();

        return $approvedCount >= $requiredCount;
    }
}

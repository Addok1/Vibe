<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Config;

class FranchiseOwnersExport implements FromView, ShouldAutoSize
{
    use Exportable;
    /**
     *
     */
    public function __construct($franchiseowners) {
        $this->franchiseowners = $franchiseowners;
    }

    public function view(): View
    {
        return view('franchiseowners.franchiseowners', [
            'results' => $this->franchiseowners,
        ]);
    }
}

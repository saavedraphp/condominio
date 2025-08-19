<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrScanController extends Controller
{
    public function ShowScanner(): view
    {
        return view('securities.qr_scanner.scanner', [
            'routes' => [
                'base' => route('security.scan-qr'),
            ],
        ]);

    }
}

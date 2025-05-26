<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\ManagesHouseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    use ManagesHouseSession;
    public function index(): View
    {
        $this->clearHouseSession();
        return view('user.dashboard', ['userId' => Auth::guard('web_user')->id()]);
    }

    public function pageQrCode(): View
    {
        return view('user.qr_code');
    }

    public function generateQrCode(Request $request): JsonResponse
    {
        $user = Auth::guard('web_user')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        //$dataToEncode = route('user.qr-verification', ['id' => $user->id]); // Needs a named route 'user.profile'
        $publicStatusUrl = $user->public_status_url;
        $dataToEncode = $publicStatusUrl;
        $qrCodeImage = QrCode::format('png')
            ->size(550) // Size in pixels
            ->margin(1) // Margin around the QR code
            ->generate($dataToEncode);

        // --- Prepare Response ---
        // Encode the image data as Base64 to include in JSON
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage);

        return response()->json([
            'status'      => true,
            'qr_code_url' => $qrCodeBase64,
            'url'        => $dataToEncode,
            'user_name'   => $user->name
        ]);
    }
}

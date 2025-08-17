<?php

// app/Models/QrCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class QrCode extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        'code',
        'type',
        'file_path',
        'order',
        'is_active',
        'white_label_id',
    ];

    protected $appends = ['qr_content', 'qr_svg'];


    /**
     * Get the JSON content that will be encoded into the QR code.
     * This is an accessor, so you can access it like a property: $qrCode->qr_content
     */
    protected function qrContent(): Attribute
    {
        return Attribute::make(
            get: fn () => json_encode([
                'type' => $this->type,
                'payload' => [
                    // We map the 'code' from our DB to 'zone_id' in the payload
                    'zone_id' => $this->code,
                ]
            ]),
        );
    }

    /**
     * Generate the QR code image as an SVG string.
     * This is also an accessor: $qrCode->qr_svg
     */
    protected function qrSvg(): Attribute
    {
        return Attribute::make(
            get: fn () => QrCodeGenerator::size(250)->generate($this->qr_content),
        );
    }

}

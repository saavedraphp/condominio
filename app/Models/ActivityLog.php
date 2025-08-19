<?php

// app/Models/ActivityLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ActivityStatus; // Usamos el Enum renombrado

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Laravel es inteligente y asumirá que la tabla para el modelo 'ActivityLog'
     * es 'activity_logs'. No es estrictamente necesario definirlo, pero es una buena práctica
     * para mayor claridad.
     */
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'qr_code_id',
        'code',
        'status',
        'remarks',
        'file_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ActivityStatus::class,
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the QR code that was scanned for this activity.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }
}

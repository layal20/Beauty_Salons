<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonService extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'service_id',
    ];
    /**
     * Get the salon that owns the salon_service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id', 'id');
    }

    /**
     * Get the service that owns the salon_service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }
}

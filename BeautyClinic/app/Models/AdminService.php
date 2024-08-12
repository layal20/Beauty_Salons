<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminService extends Model
{
    use HasFactory;
    protected $fillable = [
        'service_id',
        'admin_id',
    ];

    /**
     * Get the admin that owns the AdminService
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    /**
     * Get the service that owns the AdminService
     *
     * @return \Illuminate\servicebase\Eloquservice_id\Relid\BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(service::class, 'service_id', 'id');
    }
}

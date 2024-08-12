<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'time',
    ];

    /**
     * Get the customer that owns the AppointmentDetails
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * Get the service that owns the AppointmentDetails
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_details', 'appointment_id', 'service_id', 'id');
    }

    /**
     * Get the salon that owns the AppointmentDetails
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function salon(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'appointment_details', 'appointment_id', 'salon_id', 'id');
    }
}

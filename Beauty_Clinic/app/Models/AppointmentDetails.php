<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'service_id',
        'appointment_id',
        'customer_id'
    ];

   

   /**
    * Get the service that owns the AppointmentDetails
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function service(): BelongsTo
   {
       return $this->belongsTo(Service::class, 'service_id', 'id');
   }

   /**
    * Get the salon that owns the AppointmentDetails
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function salon(): BelongsTo
   {
       return $this->belongsTo(Salon::class, 'salon_id', 'id');
   }
    
   /**
    * Get the appointment that owns the AppointmentDetails
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function appointment(): BelongsTo
   {
       return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
   }
}

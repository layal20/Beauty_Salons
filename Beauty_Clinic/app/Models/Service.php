<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'status',
        'price',
        'name',
        'date',
        'description',
        'time',
        'admin_id',
        'employee_id',
        'image'
    ];

    /**
     * Get all of the salons for the service
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'salon_services', 'service_id', 'salon_id' , 'id');
    }

    /**
     * Get the admin that owns the service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_services','service_id' ,'admin_id' , 'id');
    }

    /**
     * Get the employee associated with the service
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    // /**
    //  * Get all of the appointmentDetails for the Service
    //  *
    //  * @return \Illuminate\DataBase\Eloquent\Relations\HasMany
    //  */
    // public function appointments(): HasMany
    // {
    //     return $this->hasMany(AppointmentDetails::class, 'service_id', 'id');
    // }

    /**
     * The appointments that belong to the service.
     */
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_details', 'service_id', 'appointment_id' ,'id');
    }

    public function scopeActive(Builder $builder)
    {
        $builder->where('status', '=', 'active');
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'tenant_id',
        'show_in_schedule',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the employee profile associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'employee_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }
}

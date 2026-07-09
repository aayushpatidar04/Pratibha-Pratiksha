<?php
// app/Models/RegistrationApplication.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistrationApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_no',
        'status',
        'student_name',
        'father_name',
        'mother_name',
        'dob',
        'age',
        'blood_group',
        'student_photo',
        'student_mobile',
        'father_mobile',
        'mother_mobile',
        'email',
        'permanent_address',
        'current_address',
        'institution_name',
        'institution_address',
        'course_name',
        'course_duration',
        'room_type',
        'stay_duration_from',
        'stay_duration_to',
        'has_driving_license',
        'vehicle_type',
        'vehicle_number',
        'disease_history',
        'allergy_details',
        'special_achievements',
        'guardian1_name',
        'guardian1_mobile',
        'guardian1_occupation',
        'guardian1_address',
        'guardian2_name',
        'guardian2_mobile',
        'guardian2_occupation',
        'guardian2_address',
        'father_photo',
        'mother_photo',
        'family_photo1',
        'family_photo2',
        'guardian_photo',
        'payment_method',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'registration_fee',
        'paid_at',
        'payment_status',
        'approved_by',
        'approved_at',
        'admin_remarks',
        'resident_id',
        'allotted_building_id',
        'allotted_floor_id',
        'allotted_room_id',
        'allotted_bed_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'stay_duration_from' => 'date',
        'stay_duration_to' => 'date',
        'has_driving_license' => 'boolean',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'registration_fee' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_no)) {
                $application->application_no = 'APP-' . date('Y') . '-' . str_pad(
                    static::whereYear('created_at', date('Y'))->count() + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function allottedBuilding()
    {
        return $this->belongsTo(Building::class, 'allotted_building_id');
    }

    public function allottedFloor()
    {
        return $this->belongsTo(Floor::class, 'allotted_floor_id');
    }

    public function allottedRoom()
    {
        return $this->belongsTo(Room::class, 'allotted_room_id');
    }

    public function allottedBed()
    {
        return $this->belongsTo(Bed::class, 'allotted_bed_id');
    }
}
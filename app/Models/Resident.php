<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Resident extends Model
{
    use HasFactory;

    protected $table = 'residents';

    protected $fillable = [
        'resident_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'whatsapp_number',
        'date_of_birth',
        'gender',
        'blood_group',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'course',
        'year',
        'batch',
        'roll_number',
        'institute',
        'father_name',
        'father_phone',
        'father_email',
        'mother_name',
        'mother_phone',
        'status',
        'photo_url',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date'
        ];
    }

    public function stays() { return $this->hasMany(ResidentStay::class); }

    public function documents() { return $this->hasMany(Document::class); }

    public function vehicles() { return $this->hasMany(Vehicle::class); }

    public function invoices() { return $this->hasMany(FeeInvoice::class); }

    public function payments() { return $this->hasMany(Payment::class); }

    public function complaints() { return $this->hasMany(Complaint::class); }

    public function leaves() { return $this->hasMany(Leave::class); }

    public function getFullNameAttribute() { return trim($this->first_name.' '.$this->last_name); }
}

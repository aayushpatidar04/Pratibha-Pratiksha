<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Resident extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'aadhar_number',
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
        'portal_enabled',
        'photo_url',
        'created_by',
        'password',
        'last_login_at',
        'password_changed_at',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'portal_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function stays()
    {
        return $this->hasMany(ResidentStay::class);
    }

    public function currentStay()
    {
        return $this->hasOne(ResidentStay::class)->whereIn('status', ['upcoming', 'active'])->latestOfMany();
    }

    public function activeStay(): HasOne
    {
        return $this->hasOne(ResidentStay::class)
            ->where('status', 'active')
            ->latestOfMany();
    }


    public function registrationApplications()
    {
        return $this->hasMany(
            RegistrationApplication::class,
            'resident_id'
        );
    }

    public function latestRegistrationApplication()
    {
        return $this->hasOne(
            RegistrationApplication::class,
            'resident_id'
        )->latestOfMany();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function invoices()
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function leaves()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function amenityOverride()
    {
        return $this->hasOne(ResidentAmenityOverride::class, 'resident_id');
    }

    public function emergencyAlerts(): HasMany
    {
        return $this->hasMany(
            EmergencyAlert::class,
            'resident_id'
        );
    }

    public function roomChangeRequests(): HasMany
    {
        return $this->hasMany(
            RoomChangeRequest::class,
            'resident_id'
        );
    }

    // Get effective amenity config (override or default)
    public function getEffectiveAmenity(string $amenity, MonthlyBillingConfig $config = null): array
    {
        $override = $this->amenityOverride;

        $enabled = $override?->$amenity ?? $config?->$amenity ?? false;

        // Custom amounts
        $customField = match ($amenity) {
            'rent_enabled' => 'custom_rent',
            'mess_enabled' => 'custom_mess',
            default => null,
        };

        $customAmount = $customField ? ($override?->$customField ?? null) : null;

        return [
            'enabled' => $enabled,
            'custom_amount' => $customAmount,
        ];
    }

    public function targetedNotices(): BelongsToMany
    {
        return $this->belongsToMany(
            Notice::class,
            'notice_resident'
        )->withTimestamps();
    }

    public function noticeReads(): HasMany
    {
        return $this->hasMany(
            NoticeRead::class,
            'resident_id'
        );
    }

    public function inventoryAssignments(): HasMany
    {
        return $this->hasMany(
            ResidentInventoryAssignment::class,
            'resident_id'
        );
    }

    public function checkoutRequests(): HasMany
    {
        return $this->hasMany(
            CheckoutRequest::class,
            'resident_id'
        );
    }

    public function activeCheckoutRequest(): HasOne
    {
        return $this->hasOne(
            CheckoutRequest::class,
            'resident_id'
        )
            ->whereNotIn('status', [
                CheckoutRequest::STATUS_COMPLETED,
                CheckoutRequest::STATUS_CANCELLED,
                CheckoutRequest::STATUS_ADMIN_REJECTED,
                CheckoutRequest::STATUS_WARDEN_REJECTED,
                CheckoutRequest::STATUS_EXPIRED,
            ])
            ->latestOfMany();
    }
}
<?php

return [
    'name' => env(
        'HOSTEL_NAME',
        'Pratibha Pratiksha Hostel'
    ),

    'office' => [
        'phone' => env(
            'HOSTEL_OFFICE_PHONE',
            '+91 98765 43210'
        ),

        'whatsapp' => env(
            'HOSTEL_OFFICE_WHATSAPP',
            '919876543210'
        ),

        'email' => env(
            'HOSTEL_OFFICE_EMAIL',
            'office@example.com'
        ),

        'address' => env(
            'HOSTEL_ADDRESS',
            'Hostel office address'
        ),

        'timings' => env(
            'HOSTEL_OFFICE_TIMINGS',
            'Monday to Saturday, 9:00 AM to 6:00 PM'
        ),
    ],

    'reception' => [
        'phone' => env(
            'HOSTEL_RECEPTION_PHONE',
            '+91 98765 43211'
        ),
    ],

    'warden' => [
        'name' => env(
            'HOSTEL_WARDEN_NAME',
            'Hostel Warden'
        ),

        'phone' => env(
            'HOSTEL_WARDEN_PHONE',
            '+91 98765 43212'
        ),

        'whatsapp' => env(
            'HOSTEL_WARDEN_WHATSAPP',
            '919876543212'
        ),
    ],

    'emergency' => [
        'phone' => env(
            'HOSTEL_EMERGENCY_PHONE',
            '+91 98765 43213'
        ),
    ],
];
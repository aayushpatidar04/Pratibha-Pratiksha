<?php

return [
    'modules' => [

        // Infrastructure
        [
            'key' => 'buildings',
            'label' => 'Infrastructure: Buildings',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'floors',
            'label' => 'Infrastructure: Floors',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'rooms',
            'label' => 'Infrastructure: Rooms',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'inventory',
            'label' => 'Infrastructure: Inventory',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // Residents
        [
            'key' => 'residents',
            'label' => 'Residents: List',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'kyc',
            'label' => 'Residents: KYC',
            'actions' => ['view', 'create', 'edit'],
        ],
        [
            'key' => 'kyc_settings',
            'label' => 'Residents: KYC Settings (Admin)',
            'actions' => ['view', 'edit'],
        ],
        [
            'key' => 'academics',
            'label' => 'Residents: Academic Details',
            'actions' => ['view', 'edit'],
        ],
        [
            'key' => 'student_vehicles',
            'label' => 'Residents: Vehicles',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'room_change_requests',
            'label' => 'Residents: Room Change Requests',
            'actions' => ['view', 'create', 'edit'],
        ],

        // Operations
        [
            'key' => 'checkinout',
            'label' => 'Operations: Check-In & Room Allotment',
            'actions' => [
                'view',
                'allot_room',
                'confirm_checkin',
            ],
        ],
        [
            'key' => 'checkout_requests',
            'label' => 'Operations: Checkout Requests',
            'actions' => [
                'view',
                'create',
                'start_review',
                'assign_inspector',
                'manage_dues',
                'hold',
                'reject',
                'final_approve',
                'regenerate_exit_token',
                'override',
            ],
        ],
        [
            'key' => 'checkout_inspections',
            'label' => 'Operations: Checkout Inspections',
            'actions' => [
                'view',
                'start',
                'save',
                'approve',
                'hold',
                'reject',
            ],
        ],
        [
            'key' => 'checkout_gate',
            'label' => 'Operations: Checkout Gate Verification',
            'actions' => [
                'view',
                'verify_exit',
                'complete_checkout',
            ],
        ],

        // Communication
        [
            'key' => 'notices',
            'label' => 'Communication: Notices & Circulars',
            'actions' => [
                'view',
                'create',
                'edit',
                'delete',
                'publish',
            ],
        ],

        // Billing
        [
            'key' => 'billing',
            'label' => 'Billing',
            'actions' => [
                'view',
                'create',
                'edit',
                'delete',
                'refund_security_deposit',
            ],
        ],

        // Communication
        [
            'key' => 'whatsapp',
            'label' => 'WhatsApp Communication',
            'actions' => ['view', 'create'],
        ],

        // Student Support
        [
            'key' => 'complaints',
            'label' => 'Student Support: Complaints',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'leaves',
            'label' => 'Student Support: Leaves',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        [
            'key' => 'emergency',
            'label' => 'Student Support: Emergency Alerts',
            'actions' => ['view', 'create', 'edit'],
        ],

        // Gate
        [
            'key' => 'gate',
            'label' => 'Gate Management',
            'actions' => ['view', 'create', 'edit'],
        ],

        // Tracking
        [
            'key' => 'tracking',
            'label' => 'Student Tracking',
            'actions' => ['view', 'create'],
        ],

        // Disciplinary
        [
            'key' => 'disciplinary',
            'label' => 'Disciplinary Action',
            'actions' => ['view', 'create', 'edit'],
        ],

        // Mess
        [
            'key' => 'mess',
            'label' => 'Hostel Mess',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // Reports
        [
            'key' => 'reports',
            'label' => 'Reports',
            'actions' => ['view'],
        ],

        // Analytics
        [
            'key' => 'analytics',
            'label' => 'Analytics',
            'actions' => ['view'],
        ],

        // Admin
        [
            'key' => 'admin_users',
            'label' => 'Admin: User & Permission Management',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],

        // Registrations
        [
            'key' => 'registrations',
            'label' => 'Registration Applications',
            'actions' => ['view', 'edit'],
        ],
    ],
];
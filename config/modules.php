<?php

return [
    'modules' => [
        ['key' => 'buildings', 'label' => 'Infrastructure: Buildings', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'floors', 'label' => 'Infrastructure: Floors', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'rooms', 'label' => 'Infrastructure: Rooms', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'inventory', 'label' => 'Infrastructure: Inventory', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'residents', 'label' => 'Residents: List', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'kyc', 'label' => 'Residents: KYC', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'kyc_settings', 'label' => 'Residents: KYC Settings (Admin)', 'actions' => ['view', 'edit']],
        ['key' => 'academics', 'label' => 'Residents: Academic Details', 'actions' => ['view', 'edit']],
        ['key' => 'student_vehicles', 'label' => 'Residents: Vehicles', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'room_change_requests', 'label' => 'Residents: Room Change Requests', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'checkinout', 'label' => 'Check-In / Check-Out', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'billing', 'label' => 'Billing', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'whatsapp', 'label' => 'WhatsApp Communication', 'actions' => ['view', 'create']],
        ['key' => 'complaints', 'label' => 'Student Support: Complaints', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'leaves', 'label' => 'Student Support: Leaves', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'emergency', 'label' => 'Student Support: Emergency Alerts', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'gate', 'label' => 'Gate Management', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'tracking', 'label' => 'Student Tracking', 'actions' => ['view', 'create']],
        ['key' => 'disciplinary', 'label' => 'Disciplinary Action', 'actions' => ['view', 'create', 'edit']],
        ['key' => 'mess', 'label' => 'Hostel Mess', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'reports', 'label' => 'Reports', 'actions' => ['view']],
        ['key' => 'analytics', 'label' => 'Analytics', 'actions' => ['view']],
        ['key' => 'admin_users', 'label' => 'Admin: User & Permission Management', 'actions' => ['view', 'create', 'edit', 'delete']],
    ],
];
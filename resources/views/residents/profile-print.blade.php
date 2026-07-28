<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $resident->resident_code }} -
        Resident Profile
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #1f2937;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto;
            background: #ffffff;
            padding: 15mm;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.10);
        }

        .action-bar {
            width: 210mm;
            margin: 18px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .button {
            display: inline-block;
            padding: 9px 16px;
            border: 0;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .button-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .button-dark {
            background: #111827;
            color: #ffffff;
        }

        .header {
            display: table;
            width: 100%;
            padding-bottom: 18px;
            border-bottom: 3px solid #2563eb;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 70%;
        }

        .header-right {
            width: 30%;
            text-align: right;
        }

        .organisation {
            margin: 0;
            color: #1d4ed8;
            font-size: 22px;
            font-weight: 700;
        }

        .document-title {
            margin: 4px 0 0;
            color: #111827;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-meta {
            margin-top: 5px;
            color: #6b7280;
            font-size: 10px;
        }

        .profile-summary {
            display: table;
            width: 100%;
            margin-top: 18px;
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #eff6ff;
        }

        .profile-photo-cell,
        .profile-details-cell,
        .profile-status-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .profile-photo-cell {
            width: 95px;
        }

        .profile-details-cell {
            padding-left: 14px;
        }

        .profile-status-cell {
            width: 115px;
            text-align: right;
        }

        .profile-photo {
            width: 82px;
            height: 96px;
            border: 2px solid #ffffff;
            border-radius: 7px;
            object-fit: cover;
            box-shadow: 0 1px 5px rgba(15, 23, 42, 0.15);
        }

        .photo-placeholder {
            width: 82px;
            height: 96px;
            border-radius: 7px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            line-height: 96px;
        }

        .resident-name {
            margin: 0;
            color: #111827;
            font-size: 21px;
            font-weight: 700;
        }

        .resident-code {
            margin-top: 3px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .summary-line {
            margin-top: 5px;
            color: #4b5563;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #dcfce7;
            color: #166534;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-upcoming {
            background: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background: #f3f4f6;
            color: #4b5563;
        }

        .status-suspended,
        .status-left {
            background: #fee2e2;
            color: #991b1b;
        }

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 8px;
            padding: 7px 10px;
            border-left: 4px solid #2563eb;
            background: #f8fafc;
            color: #111827;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .details-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details-grid td {
            width: 50%;
            padding: 7px 9px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .details-grid.three-column td {
            width: 33.333%;
        }

        .field-label {
            display: block;
            margin-bottom: 2px;
            color: #6b7280;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .field-value {
            display: block;
            color: #111827;
            font-size: 11px;
            font-weight: 500;
            overflow-wrap: break-word;
        }

        .subsection {
            margin-top: 10px;
        }

        .subsection-title {
            margin-bottom: 6px;
            color: #374151;
            font-size: 11px;
            font-weight: 700;
        }

        .stay-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .stay-table th {
            padding: 7px 5px;
            background: #1e3a8a;
            color: #ffffff;
            text-align: left;
            font-weight: 600;
        }

        .stay-table td {
            padding: 7px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .stay-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .amount {
            white-space: nowrap;
            text-align: right;
        }

        .empty {
            color: #9ca3af;
        }

        .image-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .image-grid td {
            width: 25%;
            text-align: center;
            vertical-align: top;
        }

        .application-image {
            width: 115px;
            height: 100px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            object-fit: cover;
        }

        .image-title {
            margin-top: 4px;
            color: #4b5563;
            font-size: 9px;
        }

        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 9px;
        }

        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 42px;
        }

        .signature-cell {
            display: table-cell;
            width: 33.333%;
            text-align: center;
        }

        .signature-line {
            width: 75%;
            margin: 0 auto 5px;
            border-top: 1px solid #374151;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .action-bar {
                display: none;
            }

            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }

            @page {
                size: A4;
                margin: 8mm;
            }
        }
    </style>
</head>

<body>
    @php
        $show = static function ($value, $fallback = '—') {
            return filled($value) ? $value : $fallback;
        };

        $money = static function ($value) {
            return '₹' . number_format((float) ($value ?? 0), 2);
        };

        $formatDate = static function ($value) {
            if (!$value) {
                return '—';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d M Y');
            } catch (\Throwable $e) {
                return $value;
            }
        };

        $formatDateTime = static function ($value) {
            if (!$value) {
                return '—';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)
                    ->format('d M Y, h:i A');
            } catch (\Throwable $e) {
                return $value;
            }
        };

        /*
         * Resident is the source of truth for overlapping fields.
         * Application data below contains only application-specific fields,
         * preventing duplicate name/contact/academic/parent entries.
         */

        $photoPath = null;

        if ($resident->photo_url) {
            $cleanPhotoPath = str_replace(
                ['/storage/', 'storage/'],
                '',
                $resident->photo_url
            );

            $possiblePath = storage_path(
                'app/public/' . ltrim($cleanPhotoPath, '/')
            );

            if (file_exists($possiblePath)) {
                $photoPath = $possiblePath;
            }
        }

        $resolveApplicationImage = static function ($path) {
            if (!$path) {
                return null;
            }

            $cleanPath = str_replace(
                ['/storage/', 'storage/'],
                '',
                $path
            );

            $possiblePath = storage_path(
                'app/public/' . ltrim($cleanPath, '/')
            );

            return file_exists($possiblePath)
                ? $possiblePath
                : null;
        };
    @endphp

    @unless($isPdf)
        <div class="action-bar">
            <button
                type="button"
                class="button button-dark"
                onclick="window.print()"
            >
                Print
            </button>

        </div>
    @endunless

    <main class="page">
        <header class="header">
            <div class="header-left">
                <h1 class="organisation">
                    Pratibha Pratiksha
                </h1>

                <p class="document-title">
                    Resident Information Sheet
                </p>

                <p class="document-meta">
                    Complete personal, academic, admission and
                    accommodation record
                </p>
            </div>

            <div class="header-right">
                <strong>Generated On</strong><br>
                {{ now()->format('d M Y, h:i A') }}
            </div>
        </header>

        <section class="profile-summary">
            <div class="profile-photo-cell">
                @if($photoPath)
                    <img
                        src="{{ asset('/storage/' . $resident->photo_url) }}"
                        class="profile-photo"
                        alt="Resident photograph"
                    >
                @else
                    <div class="photo-placeholder">
                        {{ strtoupper(substr($resident->first_name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="profile-details-cell">
                <h2 class="resident-name">
                    {{ trim(
                        $resident->first_name . ' ' .
                        $resident->last_name
                    ) }}
                </h2>

                <div class="resident-code">
                    {{ $resident->resident_code }}
                </div>

                <div class="summary-line">
                    {{ $show($resident->course) }}

                    @if($resident->institute)
                        · {{ $resident->institute }}
                    @endif
                </div>

                <div class="summary-line">
                    {{ $show($resident->phone) }}

                    @if($resident->email)
                        · {{ $resident->email }}
                    @endif
                </div>

                @if($resident->currentStay)
                    <div class="summary-line">
                        {{ $show(
                            optional($resident->currentStay->building)->name
                        ) }}
                        · Room
                        {{ $show(
                            optional($resident->currentStay->room)
                                ->room_number
                        ) }}
                        · Bed
                        {{ $show(
                            optional($resident->currentStay->bed)
                                ->bed_number
                        ) }}
                    </div>
                @endif
            </div>

            <div class="profile-status-cell">
                <span
                    class="status
                        status-{{ $resident->status }}"
                >
                    {{ str_replace(
                        '_',
                        ' ',
                        $resident->status
                    ) }}
                </span>
            </div>
        </section>

        {{-- Resident fields are printed only once from residents table. --}}
        <section class="section">
            <h3 class="section-title">
                Personal Information
            </h3>

            <table class="details-grid three-column">
                <tr>
                    <td>
                        <span class="field-label">Resident Code</span>
                        <span class="field-value">
                            {{ $resident->resident_code }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Gender</span>
                        <span class="field-value">
                            {{ ucfirst($show($resident->gender)) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Date of Birth</span>
                        <span class="field-value">
                            {{ $formatDate($resident->date_of_birth) }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td>
                        <span class="field-label">Blood Group</span>
                        <span class="field-value">
                            {{ $show($resident->blood_group) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Resident Status</span>
                        <span class="field-value">
                            {{ ucfirst($resident->status) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Profile Created</span>
                        <span class="field-value">
                            {{ $formatDateTime($resident->created_at) }}
                        </span>
                    </td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h3 class="section-title">
                Contact and Address
            </h3>

            <table class="details-grid">
                <tr>
                    <td>
                        <span class="field-label">Phone</span>
                        <span class="field-value">
                            {{ $show($resident->phone) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">WhatsApp Number</span>
                        <span class="field-value">
                            {{ $show($resident->whatsapp_number) }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td>
                        <span class="field-label">Email</span>
                        <span class="field-value">
                            {{ $show($resident->email) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Pincode</span>
                        <span class="field-value">
                            {{ $show($resident->pincode) }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <span class="field-label">
                            Permanent Address
                        </span>

                        <span class="field-value">
                            {{ collect([
                                $resident->address,
                                $resident->city,
                                $resident->state,
                                $resident->country,
                                $resident->pincode,
                            ])->filter()->implode(', ') ?: '—' }}
                        </span>
                    </td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h3 class="section-title">
                Academic Information
            </h3>

            <table class="details-grid">
                <tr>
                    <td>
                        <span class="field-label">Institute</span>
                        <span class="field-value">
                            {{ $show($resident->institute) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Course</span>
                        <span class="field-value">
                            {{ $show($resident->course) }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td>
                        <span class="field-label">Academic Year</span>
                        <span class="field-value">
                            {{ $show($resident->year) }}
                        </span>
                    </td>

                    <td>
                        <span class="field-label">Batch</span>
                        <span class="field-value">
                            {{ $show($resident->batch) }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <span class="field-label">Roll Number</span>
                        <span class="field-value">
                            {{ $show($resident->roll_number) }}
                        </span>
                    </td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h3 class="section-title">
                Parent Information
            </h3>

            <div class="subsection">
                <div class="subsection-title">
                    Father Details
                </div>

                <table class="details-grid three-column">
                    <tr>
                        <td>
                            <span class="field-label">Name</span>
                            <span class="field-value">
                                {{ $show($resident->father_name) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">Phone</span>
                            <span class="field-value">
                                {{ $show($resident->father_phone) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">Email</span>
                            <span class="field-value">
                                {{ $show($resident->father_email) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="subsection">
                <div class="subsection-title">
                    Mother Details
                </div>

                <table class="details-grid">
                    <tr>
                        <td>
                            <span class="field-label">Name</span>
                            <span class="field-value">
                                {{ $show($resident->mother_name) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">Phone</span>
                            <span class="field-value">
                                {{ $show($resident->mother_phone) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        @if($application)
            {{-- Only application-specific fields are printed here. --}}
            <section class="section">
                <h3 class="section-title">
                    Registration and Admission Information
                </h3>

                <table class="details-grid three-column">
                    <tr>
                        <td>
                            <span class="field-label">
                                Application Number
                            </span>

                            <span class="field-value">
                                {{ $application->application_no }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Application Status
                            </span>

                            <span class="field-value">
                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $application->status
                                    )
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Application Date
                            </span>

                            <span class="field-value">
                                {{ $formatDate($application->created_at) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">
                                Age At Registration
                            </span>

                            <span class="field-value">
                                {{ $show($application->age) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Course Duration
                            </span>

                            <span class="field-value">
                                {{ $show($application->course_duration) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Requested Room Type
                            </span>

                            <span class="field-value">
                                {{ $show($application->room_type) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">
                                Requested Stay From
                            </span>

                            <span class="field-value">
                                {{ $formatDate(
                                    $application->stay_duration_from
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Requested Stay To
                            </span>

                            <span class="field-value">
                                {{ $formatDate(
                                    $application->stay_duration_to
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Current Address
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->current_address
                                ) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <span class="field-label">
                                Institution Address
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->institution_address
                                ) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="section">
                <h3 class="section-title">
                    Health and Additional Information
                </h3>

                <table class="details-grid">
                    <tr>
                        <td>
                            <span class="field-label">
                                Disease History
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->disease_history
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Allergy Details
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->allergy_details
                                ) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <span class="field-label">
                                Special Achievements
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->special_achievements
                                ) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="section">
                <h3 class="section-title">
                    Additional Guardians
                </h3>

                <table class="details-grid">
                    <tr>
                        <td>
                            <span class="field-label">Guardian 1</span>
                            <span class="field-value">
                                {{ $show($application->guardian1_name) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Guardian 1 Mobile
                            </span>

                            <span class="field-value">
                                {{ $show($application->guardian1_mobile) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">
                                Guardian 1 Occupation
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->guardian1_occupation
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Guardian 1 Address
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->guardian1_address
                                ) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">Guardian 2</span>
                            <span class="field-value">
                                {{ $show($application->guardian2_name) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Guardian 2 Mobile
                            </span>

                            <span class="field-value">
                                {{ $show($application->guardian2_mobile) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">
                                Guardian 2 Occupation
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->guardian2_occupation
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Guardian 2 Address
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->guardian2_address
                                ) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="section">
                <h3 class="section-title">
                    Vehicle Information
                </h3>

                <table class="details-grid three-column">
                    <tr>
                        <td>
                            <span class="field-label">
                                Driving Licence
                            </span>

                            <span class="field-value">
                                {{ $application->has_driving_license
                                    ? 'Yes'
                                    : 'No' }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Vehicle Type
                            </span>

                            <span class="field-value">
                                {{ $show($application->vehicle_type) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Vehicle Number
                            </span>

                            <span class="field-value">
                                {{ $show($application->vehicle_number) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="section">
                <h3 class="section-title">
                    Registration Payment and Approval
                </h3>

                <table class="details-grid three-column">
                    <tr>
                        <td>
                            <span class="field-label">
                                Registration Fee
                            </span>

                            <span class="field-value">
                                {{ $money(
                                    $application->registration_fee
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Payment Method
                            </span>

                            <span class="field-value">
                                {{ ucfirst(
                                    $show(
                                        $application->payment_method
                                    )
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">
                                Payment Status
                            </span>

                            <span class="field-value">
                                {{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $show(
                                            $application->payment_status
                                        )
                                    )
                                ) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="field-label">Paid At</span>
                            <span class="field-value">
                                {{ $formatDateTime(
                                    $application->paid_at
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">Approved By</span>
                            <span class="field-value">
                                {{ $show(
                                    optional(
                                        $application->approvedBy
                                    )->name
                                ) }}
                            </span>
                        </td>

                        <td>
                            <span class="field-label">Approved At</span>
                            <span class="field-value">
                                {{ $formatDateTime(
                                    $application->approved_at
                                ) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <span class="field-label">
                                Administrative Remarks
                            </span>

                            <span class="field-value">
                                {{ $show(
                                    $application->admin_remarks
                                ) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </section>

            @php
                $applicationImages = [
                    'Father Photograph' => $application->father_photo ? '/storage/' . $application->father_photo : null,
                    
                    'Mother Photograph' => $application->mother_photo ? '/storage/' . $application->mother_photo : null,

                    'Family Photograph 1' => $application->family_photo1 ? '/storage/' . $application->family_photo1 : null,

                    'Family Photograph 2' => $application->family_photo2 ? '/storage/' . $application->family_photo2 : null,

                    'Guardian Photograph' => $application->guardian_photo ? '/storage/' . $application->guardian_photo : null,

                ];

                $applicationImages = collect(
                    $applicationImages
                )->filter();
            @endphp

            @if($applicationImages->isNotEmpty())
                <section class="section">
                    <h3 class="section-title">
                        Family and Guardian Photographs
                    </h3>

                    <table class="image-grid">
                        @foreach(
                            $applicationImages->chunk(4)
                            as $imageRow
                        )
                            <tr>
                                @foreach($imageRow as $title => $path)
                                    <td>
                                        <img
                                            src="{{ asset($path) }}"
                                            class="application-image"
                                            alt="{{ $title }}"
                                        >

                                        <div class="image-title">
                                            {{ $title }}
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </section>
            @endif
        @endif

        <section class="section">
            <h3 class="section-title">
                Stay and Accommodation History
            </h3>

            @if($stays->isNotEmpty())
                <table class="stay-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Accommodation</th>
                            <th>Check-in</th>
                            <th>Expected Out</th>
                            <th>Actual Out</th>
                            <th>Billing</th>
                            <th>Rent / Rate</th>
                            <th>Deposit</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($stays as $index => $stay)
                            <tr>
                                <td>
                                    {{ $index + 1 }}
                                </td>

                                <td>
                                    {{ $show(
                                        optional($stay->building)->name
                                    ) }}<br>

                                    Floor:
                                    {{ $show(
                                        optional($stay->floor)->name
                                            ?: optional($stay->floor)
                                                ->floor_number
                                    ) }}<br>

                                    Room:
                                    {{ $show(
                                        optional($stay->room)
                                            ->room_number
                                    ) }}

                                    / Bed:
                                    {{ $show(
                                        optional($stay->bed)
                                            ->bed_number
                                    ) }}
                                </td>

                                <td>
                                    {{ $formatDate(
                                        $stay->check_in_date
                                    ) }}
                                </td>

                                <td>
                                    {{ $formatDate(
                                        $stay->expected_check_out_date
                                    ) }}
                                </td>

                                <td>
                                    {{ $formatDate(
                                        $stay->actual_check_out_date
                                    ) }}
                                </td>

                                <td>
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $stay->billing_basis
                                                ?: $stay->bill_type
                                        )
                                    ) }}

                                    @if($stay->check_in_status)
                                        <br>Checked in:
                                        {{ $formatDateTime(
                                            $stay->checked_in_at
                                        ) }}
                                    @endif
                                </td>

                                <td class="amount">
                                    @if(
                                        $stay->billing_basis === 'daily'
                                    )
                                        {{ $money($stay->daily_rate) }}
                                        / day
                                    @else
                                        {{ $money($stay->rent_amount) }}
                                        / month
                                    @endif
                                </td>

                                <td class="amount">
                                    {{ $money(
                                        $stay->deposit_amount
                                    ) }}
                                </td>

                                <td>
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $stay->status
                                        )
                                    ) }}

                                    @if(
                                        $stay->checkout_status !==
                                        'not_requested'
                                    )
                                        <br>
                                        Checkout:
                                        {{ ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $stay->checkout_status
                                            )
                                        ) }}
                                    @endif
                                </td>
                            </tr>

                            @if(
                                $stay->notes ||
                                $stay->checkout_notes
                            )
                                <tr>
                                    <td></td>

                                    <td colspan="8">
                                        @if($stay->notes)
                                            <strong>Stay Notes:</strong>
                                            {{ $stay->notes }}
                                        @endif

                                        @if(
                                            $stay->notes &&
                                            $stay->checkout_notes
                                        )
                                            <br>
                                        @endif

                                        @if($stay->checkout_notes)
                                            <strong>Checkout Notes:</strong>
                                            {{ $stay->checkout_notes }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty">
                    No stay history is available for this resident.
                </div>
            @endif
        </section>

        <div class="signature-grid">
            <div class="signature-cell">
                <div class="signature-line"></div>
                Resident Signature
            </div>

            <div class="signature-cell">
                <div class="signature-line"></div>
                Parent / Guardian
            </div>

            <div class="signature-cell">
                <div class="signature-line"></div>
                Authorised Signatory
            </div>
        </div>

        <footer class="footer">
            This document is generated from the hostel management
            system and contains the latest available resident,
            registration, and accommodation information.
        </footer>
    </main>
</body>
</html>
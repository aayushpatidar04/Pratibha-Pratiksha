<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <style>
        html,
        body {
            margin: 5px;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            line-height: 1.25;
            color: #111;
        }

        .receipt {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .logo {
            width: 120px;
            max-height: 55px;
            object-fit: contain;
            margin: 0 auto 4px;
            display: block;
        }

        .title {
            font-size: 13px;
            font-weight: bold;
            line-height: 1.15;
        }

        .subtitle {
            font-size: 7px;
            line-height: 1.3;
            margin-top: 2px;
        }

        .receipt-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            border-top: 0.7px solid #000;
            border-bottom: 0.7px solid #000;
            padding: 3px 0;
            margin: 5px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            table-layout: fixed;
            margin-bottom: 5px;
        }

        .info-table td {
            padding: 1.5px 1px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .info-label {
            width: 24%;
            font-weight: bold;
        }

        .info-value {
            width: 26%;
        }

        .items-table {
            margin-top: 4px;
        }

        .items-table th,
        .items-table td {
            border: 0.7px solid #000;
            padding: 2.5px 3px;
            vertical-align: top;
        }

        .items-table th {
            font-size: 8px;
            text-align: center;
            font-weight: bold;
            background: #f2f2f2;
        }

        .items-table td {
            font-size: 8px;
        }

        .right {
            text-align: right;
        }

        .center-text {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .summary {
            margin-top: 5px;
            font-size: 8px;
        }

        .summary p {
            margin: 2px 0;
        }

        .payment-box {
            margin-top: 5px;
            border: 0.7px solid #000;
            padding: 3px;
        }

        .payment-table td {
            padding: 1.5px 1px;
            vertical-align: top;
        }

        .footer {
            margin-top: 14px;
            width: 100%;
        }

        .footer td {
            vertical-align: bottom;
            font-size: 7.5px;
        }

        .footer-note {
            color: #555;
            line-height: 1.3;
        }

        .signature {
            text-align: right;
            font-weight: bold;
        }

        .muted {
            color: #555;
        }
    </style>
</head>

<body>
    @php
        $isRegistrationInvoice = $invoice->fee_type === 'registration_fee';

        $resident = $invoice->resident;
        $application = $invoice->application;

        $personName = $resident
            ? trim(($resident->first_name ?? '') . ' ' . ($resident->last_name ?? ''))
            : ($application?->student_name ?? '-');

        $fatherName = $resident
            ? ($resident->father_name ?? '-')
            : ($application?->father_name ?? '-');

        $mobile = $resident
            ? ($resident->phone ?? $resident->whatsapp_number ?? '-')
            : ($application?->student_mobile ?? '-');

        $referenceNumber = $resident
            ? ($resident->resident_code ?? '-')
            : ($application?->application_no ?? '-');

        $referenceLabel = $resident
            ? 'Admission No.'
            : 'Application No.';

        $receiptTitle = $isRegistrationInvoice
            ? 'Registration Fee Receipt'
            : 'Hostel Fee Receipt';

        $payment = $invoice->payments->sortByDesc('payment_date')->first();

        $paymentMode = $payment?->payment_mode
            ? ucwords(str_replace('_', ' ', $payment->payment_mode))
            : 'Pending';

        $transactionId = $payment?->transaction_id ?: '-';

        $receiptNumber = $payment?->receipt_number ?: '-';

        $invoiceDate = optional($invoice->created_at)->format('d-M-Y') ?? '-';

        $paymentDate = $payment?->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('d-M-Y')
            : '-';

        $numberFormatter = \NumberFormatter::create(
            'en_IN',
            \NumberFormatter::SPELLOUT
        );

        $amountInWords = ucfirst(
            $numberFormatter->format((float) $invoice->amount + (float) $invoice->late_fee_amount)
        );
    @endphp

    <div class="receipt">
        <div class="center">
            <img src="{{ public_path('assets/images/pratibha-pratiksha-logo-text.png') }}" class="logo"
                alt="Pratibha Pratiksha">
            <div class="title">Pratibha Pratiksha, Indore</div>
            <div class="subtitle">
                Shri Chandraprabh Digambar Jain Mandir 11-12, Udayanagar<br>
                Bicholi Mardana Road, Near Phoenix Hospital, Indore - 452016 M.P.
            </div>
        </div>

        <div class="receipt-title">
            {{ $receiptTitle }}
        </div>

        <table class="info-table">
            <tr>
                <td class="info-label">Invoice No.</td>
                <td class="info-value">: {{ $invoice->invoice_number }}</td>

                <td class="info-label">Date</td>
                <td class="info-value">: {{ $invoiceDate }}</td>
            </tr>

            <tr>
                <td class="info-label">Student Name</td>
                <td class="info-value">: {{ $personName }}</td>

                <td class="info-label">{{ $referenceLabel }}</td>
                <td class="info-value">: {{ $referenceNumber }}</td>
            </tr>

            <tr>
                <td class="info-label">Father Name</td>
                <td class="info-value">: {{ $fatherName }}</td>

                <td class="info-label">Mobile</td>
                <td class="info-value">: {{ $mobile }}</td>
            </tr>

            @if (!$isRegistrationInvoice && $resident)
                <tr>
                    <td class="info-label">Room / Stay</td>
                    <td class="info-value">
                        :
                        @if ($invoice->stay)
                            {{ $invoice->stay->room?->room_number ?? '-' }}
                            @if ($invoice->stay->bed)
                                / Bed {{ $invoice->stay->bed->bed_number ?? $invoice->stay->bed->id }}
                            @endif
                        @else
                            -
                        @endif
                    </td>

                    <td class="info-label">Due Date</td>
                    <td class="info-value">
                        : {{ optional($invoice->due_date)->format('d-M-Y') ?? '-' }}
                    </td>
                </tr>
            @endif
        </table>

        @if ($isRegistrationInvoice)
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 72%;">Particular</th>
                        <th style="width: 28%;">Amount (₹)</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td>
                                {{ $item->title }}

                                @if ($item->description)
                                    <br>
                                    <span class="muted">
                                        {{ $item->description }}
                                    </span>
                                @endif
                            </td>

                            <td class="right">
                                {{ number_format((float) $item->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>Registration Fee</td>

                            <td class="right">
                                {{ number_format((float) $invoice->amount, 2) }}
                            </td>
                        </tr>
                    @endforelse

                    <tr>
                        <td class="bold">Total</td>

                        <td class="right bold">
                            {{ number_format((float) $invoice->amount, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="bold">Paid Amount</td>

                        <td class="right bold">
                            {{ number_format((float) $invoice->paid_amount, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @else
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 52%;">Particular</th>
                        <th style="width: 20%;">Month</th>
                        <th style="width: 28%;">Amount (₹)</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td>
                                {{ $item->title }}

                                @if ($item->description)
                                    <br>
                                    <span class="muted">
                                        {{ $item->description }}
                                    </span>
                                @endif
                            </td>

                            <td class="center-text">
                                @if ($invoice->monthlyConfig?->billing_month)
                                    {{ \Carbon\Carbon::parse($invoice->monthlyConfig->billing_month)->format('M-Y') }}
                                @else
                                    {{ optional($invoice->due_date)->format('M-Y') ?? '-' }}
                                @endif
                            </td>

                            <td class="right">
                                {{ number_format((float) $item->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $invoice->fee_type)) }}</td>

                            <td class="center-text">
                                {{ optional($invoice->due_date)->format('M-Y') ?? '-' }}
                            </td>

                            <td class="right">
                                {{ number_format((float) $invoice->amount, 2) }}
                            </td>
                        </tr>
                    @endforelse

                    @php
                        $coreAmount = (float) $invoice->amount;
                        $lateFeeAmount = (float) $invoice->late_fee_amount;
                        $paidAmount = (float) $invoice->paid_amount;

                        $lateFeeWasPaid = (
                            $lateFeeAmount > 0
                            && $paidAmount >= ($coreAmount + $lateFeeAmount)
                        );

                        $showLateFee = (
                            !$invoice->late_fee_waived
                            && (
                                $invoice->is_overdue
                                || $lateFeeWasPaid
                            )
                        );
                    @endphp

                    @if ($showLateFee)
                        <tr>
                            <td colspan="2" class="bold">
                                Late Fee
                            </td>

                            <td class="right">
                                {{ number_format($lateFeeAmount, 2) }}
                            </td>
                        </tr>
                    @endif

                    @if($invoice->late_fee_waived && $invoice->late_fee_amount > 0)
                        <tr>
                            <td colspan="2" class="bold">
                                Late Fee (Waived)
                            </td>
                            <td class="right">
                                -{{ number_format($invoice->late_fee_amount, 2) }}
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td colspan="2" class="bold">
                            Total Payable
                        </td>
                        <td class="right bold">
                            {{ number_format($invoice->total_payable, 2) }}
                        </td>
                    </tr>

                    @if($invoice->paid_amount > 0)
                        <tr>
                            <td colspan="2" class="bold">
                                Paid
                            </td>
                            <td class="right">
                                {{ number_format($invoice->paid_amount, 2) }}
                            </td>
                        </tr>
                    @endif

                    @if ((float) $invoice->balance_due > 0)
                        <tr>
                            <td colspan="2" class="bold">Balance Due</td>

                            <td class="right bold">
                                {{ number_format((float) $invoice->balance_due, 2) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

        @endif

        <div class="payment-box">
            <table class="payment-table">
                <tr>
                    <td class="info-label">Payment Mode</td>
                    <td>: {{ $paymentMode }}</td>

                    <td class="info-label">Payment Date</td>
                    <td>: {{ $paymentDate }}</td>
                </tr>

                <tr>
                    <td class="info-label">Receipt No.</td>
                    <td>: {{ $receiptNumber }}</td>

                    <td class="info-label">Transaction ID</td>
                    <td>: {{ $transactionId }}</td>
                </tr>
            </table>
        </div>

        <div class="summary">
            <p>
                <strong>In Words:</strong>
                {{ $amountInWords }} Rupees Only
            </p>

            <p>
                <strong>Remark:</strong>
                {{ $invoice->description ?? '-' }}
            </p>

            @if ($invoice->late_fee_waived)
                <p>
                    <strong>Late Fee Waived:</strong>
                    Yes
                    @if ($invoice->waive_reason)
                        — {{ $invoice->waive_reason }}
                    @endif
                </p>
            @endif
        </div>

        <table class="footer">
            <tr>
                <td class="footer-note">
                    Generated on {{ now()->format('d-M-Y h:i A') }}<br>
                    This is a computer-generated receipt.
                </td>

                <td class="signature">
                    Authorized Signature
                </td>
            </tr>
        </table>

        @if($invoice->status !== 'paid' && !$invoice->is_overdue && $invoice->late_fee_amount > 0)
            <p style="font-size:8px;margin-top:5px;">
                <strong>Note:</strong>
                A late fee of ₹{{ number_format($invoice->late_fee_amount, 2) }}
                will be applicable if payment is made after
                {{ $invoice->due_date->format('d-m-Y') }}.
            </p>
        @endif
    </div>
</body>

</html>
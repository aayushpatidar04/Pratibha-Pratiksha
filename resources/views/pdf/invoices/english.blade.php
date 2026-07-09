<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #111;
        }

        .center {
            text-align: center;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 12px;
            line-height: 1.4;
        }

        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 8px 0 12px;
        }

        table {
            width: 100%;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .items-table {
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .footer {
            margin-top: 45px;
            text-align: right;
        }
    </style>
</head>

<body>
    <div>
        <div class="center">
            <div class="title">Pratibha Pratishthan, Indore</div>
            <div class="subtitle">
                Shri Chandraprabhu Digambar Jain Mandir 11-12, Udayanagar<br>
                Near Clinics Hospital, Indore - 452016 M.P.
            </div>
        </div>

        <div class="receipt-title">Hostel Fee Receipt</div>

        <table class="info-table">
            <tr>
                <td>Receipt No.</td>
                <td>: {{ $invoice->invoice_number }}</td>
                <td>Date</td>
                <td>: {{ optional($invoice->created_at)->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td>Name</td>
                <td>: {{ $invoice->resident?->first_name }} {{ $invoice->resident?->last_name }}</td>
                <td>Admission No.</td>
                <td>: {{ $invoice->resident?->resident_code }}</td>
            </tr>
            <tr>
                <td>Father Name</td>
                <td colspan="3">: {{ $invoice->resident?->father_name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Payment Details</td>
                <td colspan="3">
                    : {{ $invoice->payments->first()?->payment_mode ?? 'Pending' }}
                    @if($invoice->payments->first()?->transaction_id)
                        Ref.No. {{ $invoice->payments->first()->transaction_id }}
                    @endif
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Particular</th>
                    <th>Month</th>
                    <th>Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ optional($invoice->due_date)->format('M-Y') }}</td>
                        <td class="right">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" class="bold">Total</td>
                    <td class="right bold">{{ number_format($invoice->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <p>
            <strong>In Words :</strong>
            {{ ucfirst(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($invoice->amount)) }}
            Rupees Only
        </p>

        <p><strong>Remark :</strong> {{ $invoice->description ?? '-' }}</p>

        <div class="footer">
            Authorized Signature
        </div>
    </div>
</body>

</html>
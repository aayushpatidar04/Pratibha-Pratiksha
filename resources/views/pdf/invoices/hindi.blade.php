<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Aparajita';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/Aparajita-Regular.ttf') }}") format("truetype");
        }

        html,
        body,
        * {
            font-family: 'Aparajita', DejaVu Sans, serif !important;
        }

        body {
            font-size: 14px;
            line-height: 1.35;
            color: #111;
        }

        .title,
        .receipt-title,
        .bold,
        th,
        strong {
            font-family: 'Aparajita', DejaVu Sans, serif !important;
            font-weight: normal;
        }

        .receipt {
            width: 100%;
            padding: 12px;
        }

        .center {
            text-align: center;
        }

        .title {
            font-size: 18px;
            font-weight: normal;
        }

        .subtitle {
            font-size: 12px;
            line-height: 1.4;
        }

        .line {
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        .receipt-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 6px 0 12px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
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
    <div class="receipt">
        <div class="center">
            <div class="title">प्रतिभा प्रतिष्ठान, इंदौर</div>
            <div class="subtitle">
                श्री चंद्रप्रभ दिगंबर जैन मंदिर 11-12, उदयनगर बिचौली मर्दाना<br>
                रोड फीनिक्स अस्पताल के पीछे इंदौर-452016 म.प्र
            </div>
        </div>

        <div class="receipt-title">छात्रावास शुल्क रसीद</div>

        <table class="info-table">
            <tr>
                <td>रसीद संख्या</td>
                <td>: {{ $invoice->invoice_number }}</td>
                <td>दिनांक</td>
                <td>: {{ optional($invoice->created_at)->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td>नाम</td>
                <td>: {{ $invoice->resident?->first_name }} {{ $invoice->resident?->last_name }}</td>
                <td>प्रवेश क्रमांक</td>
                <td>: {{ $invoice->resident?->resident_code }}</td>
            </tr>
            <tr>
                <td>कक्षा</td>
                <td>: -</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>पिता का नाम</td>
                <td colspan="3">: {{ $invoice->resident?->father_name ?? '-' }}</td>
            </tr>
            <tr>
                <td>भुगतान विवरण</td>
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
                    <th>विषय</th>
                    <th>महीना</th>
                    <th>राशि (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            @if($item->item_type === 'rent')
                                छात्रावास शुल्क
                            @elseif($item->item_type === 'mess')
                                भोजन शुल्क
                            @elseif($item->item_type === 'caution_money')
                                सावधानी राशि
                            @else
                                {{ $item->title }}
                            @endif
                        </td>
                        <td>{{ optional($invoice->due_date)->format('M-Y') }}</td>
                        <td class="right">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" class="bold">योगफल</td>
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
            हस्ताक्षर (अधिकृत प्राप्तकर्ता)
        </div>
    </div>
</body>

</html>
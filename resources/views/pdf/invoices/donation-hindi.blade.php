<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $invoice->invoice_number }} - दानदाता रसीद</title>

    <style>
        @font-face {
            font-family: "Aparajita";
            src: url("{{ asset('assets/fonts/Aparajita-Regular.ttf') }}") format("truetype");
            font-style: normal;
            font-weight: 400;
            font-display: swap;
        }

        @font-face {
            font-family: "Aparajita";
            src: url("{{ asset('assets/fonts/Aparajita-Bold.ttf') }}") format("truetype");
            font-style: normal;
            font-weight: 700;
            font-display: swap;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #eef1f5;
            color: #111;
            font-family: "Aparajita", "Nirmala UI", "Mangal", serif;
            font-size: 13px;
            line-height: 1.25;
        }

        /* -------------------------
           Preview toolbar
        ------------------------- */

        .preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            padding: 12px;

            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #d1d5db;

            font-family: Arial, sans-serif;
        }

        .toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 125px;

            border: 0;
            border-radius: 7px;

            padding: 9px 16px;

            color: #fff;
            font-size: 13px;
            font-weight: 600;

            cursor: pointer;
        }

        .download-button {
            background: #2563eb;
        }

        .download-button:hover {
            background: #1d4ed8;
        }

        .download-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .close-button {
            background: #6b7280;
        }

        .close-button:hover {
            background: #4b5563;
        }

        .preview-message {
            color: #4b5563;
            font-size: 12px;
        }

        /* -------------------------
           Receipt
        ------------------------- */

        .receipt-wrapper {
            padding: 20px;
        }

        /*
         * Donation receipt is intentionally larger
         * than the normal invoice A6 receipt.
         *
         * 6 x 8 inch approximately.
         */
        .receipt-page {
            width: 100mm;
            height: 148mm;
            margin: 1.5mm auto;
            padding: 3mm 3.5mm;
            background: #fff;
            border: 1px solid #7d3b3b;
            border-radius: 2mm;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        /*
         * Inner border like the physical receipt
         */
        .receipt-page::before {
            content: "";
            position: absolute;
            top: 1.5mm;
            left: 1.5mm;
            right: 1.5mm;
            bottom: 1.5mm;
            border: 0.7px solid #777;
            border-radius: 1.5mm;
            pointer-events: none;
        }

        .receipt-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 100%;
        }

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            width: 30mm;
            max-width: 100%;
            max-height: 13mm;
            margin: 0 auto 1px;
            object-fit: contain;
        }

        .subtitle {
            font-size: 9px;
            font-weight: 700;
            line-height: 1.15;
            margin-top: 0.5mm;
        }

        .header-line {
            border-top: 0.7px solid #7d3b3b;
            margin: 2mm 0 1.5mm;
        }

        .receipt-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 2mm;
        }

        .donor-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            table-layout: fixed;
        }

        .donor-info td {
            padding: 1mm 0;
            vertical-align: bottom;
        }

        .donor-label {
            width: 25%;
            font-size: 10.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .donor-value {
            width: 75%;
            border-bottom: 0.7px solid #222;
            font-size: 10.5px;
            padding-left: 1.5mm !important;
            min-height: 5mm;
            overflow-wrap: anywhere;
        }

        .donor-top-row {
            display: flex;
            align-items: flex-end;
            width: 100%;
            margin-bottom: 1mm;
        }

        .receipt-number {
            width: 50%;
        }

        .receipt-date {
            width: 50%;
            text-align: right;
        }

        .receipt-number .label,
        .receipt-date .label {
            font-size: 10.5px;
            font-weight: 700;
        }

        .donation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5mm;
            table-layout: fixed;
        }

        .donation-table th,
        .donation-table td {
            border: 0.7px solid #222;
        }

        .donation-table th {
            height: 7mm;
            text-align: center;
            font-size: 10.5px;
            font-weight: 700;
            padding: 1mm;
        }

        .donation-table td {
            min-height: 7mm;
            height: 7mm;
            font-size: 10px;
            padding: 1.2mm 1.5mm;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .donation-table .description {
            width: 70%;
        }

        .donation-table .amount {
            width: 30%;
        }

        .rupee {
            font-weight: 700;
            margin-right: 1mm;
        }

        .amount-value {
            float: right;
            font-weight: 700;
        }

        .total-row td {
            height: 7mm;
            font-weight: 700;
        }

        .total-label {
            text-align: right;
            padding-right: 2mm !important;
        }

        .words-row td {
            min-height: 7mm;
            height: auto;
            padding: 1.2mm 1.5mm;
            line-height: 1.2;
        }

        .words-label {
            font-weight: 700;
            white-space: nowrap;
        }

        .payment-box {
            margin-top: 2mm;
            border: 0.7px solid #222;
        }

        .payment-title {
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            padding: 1mm;
            border-bottom: 0.7px solid #222;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .payment-table td {
            padding: 1.3mm 1.5mm;
            vertical-align: bottom;
            overflow-wrap: anywhere;
        }

        .payment-label {
            width: 22%;
            font-size: 9.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .payment-value {
            border-bottom: 0.7px solid #222;
            font-size: 9.5px;
            overflow-wrap: anywhere;
        }

        .remarks {
            margin-top: 2mm;
            display: flex;
            align-items: flex-end;
        }

        .remarks-label {
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .remarks-value {
            flex: 1;
            margin-left: 2mm;
            border-bottom: 0.7px solid #222;
            min-height: 5mm;
            font-size: 9.5px;
            overflow-wrap: anywhere;
        }

        .footer {
            width: 100%;
            margin-top: 7mm;
            border-collapse: collapse;
        }

        .footer td {
            border: 0;
            padding: 0;
            vertical-align: middle;
            font-size: 10px;
            font-weight: 700;
        }

        .signature {
            text-align: end;
        }
        .signature-line {
            display: block;
            width: 25mm;
            border-top: 0.7px solid #222;
            margin-left: auto;
            margin-bottom: 1mm;
        }

        /* -------------------------
           PDF mode
        ------------------------- */

        .pdf-export-mode {
            margin: 0 !important;
            box-shadow: none !important;
        }

        .donor-value,
        .payment-value,
        .remarks-value,
        .donation-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        @media (max-width: 600px) {
            .receipt-wrapper {
                overflow-x: auto;
                padding: 10px;
            }

            .preview-toolbar {
                flex-wrap: wrap;
            }

            .preview-message {
                width: 100%;
                text-align: center;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .preview-toolbar {
                display: none;
            }

            .receipt-wrapper {
                padding: 0;
            }

            .receipt-page {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    @php
        /*
         * ---------------------------------------------------------
         * DONOR DATA
         * ---------------------------------------------------------
         *
         * These fallbacks make the receipt work whether the donor
         * information is stored directly on invoice or through
         * resident/application.
         */

        $resident = $invoice->resident;
        $application = $invoice->application;

        $payment = $invoice->payments
            ->sortByDesc('payment_date')
            ->first();

        /*
         * Donor name
         */
        $donorName =
            data_get($invoice, 'donator_name')
            ?? '-';

        /*
         * Mobile
         */
        $mobile =
            data_get($invoice, 'donator_phone')
            ?? '-';

        /*
         * Address
         */
        $address =
            data_get($invoice, 'donator_address')
            ?? '-';

        /*
         * Receipt number
         *
         * Prefer payment receipt number if available,
         * otherwise invoice number.
         */
        $receiptNumber =
            $invoice->invoice_number
            ?? $payment?->receipt_number
            ?? '-';

        /*
         * Receipt date
         */
        $receiptDate =
            $payment?->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y')
            : (
                optional($invoice->created_at)->format('d-m-Y')
                ?? now()->format('d-m-Y')
            );

        /*
         * Donation amount
         */
        $donationAmount = (float) (
            $invoice->total_payable
            ?? $invoice->amount
            ?? 0
        );

        /*
         * Donation description
         */
        $donationDescription =
            $invoice->description
            ?? 'दान राशि';

        /*
         * Payment labels
         */
        $paymentModeLabels = [
            'cash' => 'नकद',
            'upi' => 'यूपीआई',
            'card' => 'कार्ड',
            'bank_transfer' => 'बैंक हस्तांतरण',
            'other' => 'अन्य',
        ];

        $paymentMode = $payment
            ? (
                $paymentModeLabels[$payment->payment_mode]
                ?? ucwords(str_replace('_', ' ', $payment->payment_mode))
            )
            : '-';

        $paymentDate = $payment?->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y')
            : '-';

        $transactionId = $payment?->transaction_id ?? '-';

        /*
         * Amount in words
         */
        $numberFormatter = \NumberFormatter::create(
            'hi_IN',
            \NumberFormatter::SPELLOUT
        );

        $amountInWords = ucfirst(
            $numberFormatter->format($donationAmount)
        );

        /*
         * Logo
         */
        $logoUrl = asset(
            'assets/images/pratibha-pratiksha-logo-text.png'
        );

        $fileName = ($invoice->invoice_number ?? 'donation-receipt')
            . '-donation-hindi.pdf';
    @endphp


    <!-- Toolbar -->

    <div class="preview-toolbar">

        <button type="button" id="downloadPdfButton" class="toolbar-button download-button"
            onclick="downloadDonationReceiptPdf()">
            PDF डाउनलोड करें
        </button>

        <button type="button" class="toolbar-button close-button" onclick="window.close()">
            बंद करें
        </button>

        <span id="downloadStatus" class="preview-message">
            यह दानदाता रसीद PDF में डाउनलोड होगी।
        </span>

    </div>


    <!-- Receipt -->

    <div class="receipt-wrapper">

        <div class="receipt-page" id="receiptPdf">

            <div class="receipt-content">

                <!-- Header -->

                <div class="center">

                    <img src="{{ $logoUrl }}" class="logo" alt="प्रतिभा प्रतिष्ठा" crossorigin="anonymous">

                    <div class="subtitle">
                        श्री चंद्रप्रभ दिगंबर जैन मंदिर 11-12, उदयनगर बिचौली मर्दाना रोड,<br>
                        फीनिक्स अस्पताल के पीछे इंदौर-452016 म.प्र.
                    </div>

                </div>

                <div class="header-line"></div>

                <div class="receipt-title">
                    दानदाता रसीद
                </div>


                <!-- Receipt no + date -->

                <div class="donor-top-row">

                    <div class="receipt-number">

                        <span class="label">
                            रसीद क्रमांक:
                        </span>

                        <span>
                            {{ $receiptNumber }}
                        </span>

                    </div>

                    <div class="receipt-date">

                        <span class="label">
                            दिनांक:
                        </span>

                        <span>
                            {{ $receiptDate }}
                        </span>

                    </div>

                </div>


                <!-- Donor -->

                <table class="donor-info">

                    <tr>
                        <td class="donor-label">
                            दानदाता का नाम:
                        </td>

                        <td class="donor-value">
                            {{ $donorName }}
                        </td>
                    </tr>

                    <tr>
                        <td class="donor-label">
                            मोबाइल नं:
                        </td>

                        <td class="donor-value">
                            {{ $mobile }}
                        </td>
                    </tr>

                    <tr>
                        <td class="donor-label">
                            पता:
                        </td>

                        <td class="donor-value">
                            {{ $address }}
                        </td>
                    </tr>

                </table>


                <!-- Donation -->

                <table class="donation-table">

                    <thead>

                        <tr>
                            <th class="description">
                                विवरण
                            </th>

                            <th class="amount">
                                राशि (₹)
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>
                                {{ $donationDescription }}
                            </td>

                            <td class="amount-cell">

                                <span class="rupee">
                                    ₹
                                </span>

                                <span class="amount-value">
                                    {{ number_format($donationAmount, 2) }}
                                </span>

                            </td>

                        </tr>


                        <tr class="total-row">

                            <td class="total-label">
                                कुल दान राशि:
                            </td>

                            <td class="amount-cell">

                                <span class="rupee">
                                    ₹
                                </span>

                                <span class="amount-value">
                                    {{ number_format($donationAmount, 2) }}
                                </span>

                            </td>

                        </tr>


                        <tr class="words-row">

                            <td colspan="2">

                                <span class="words-label">
                                    शब्दों में:
                                </span>

                                {{ $amountInWords }} रुपये मात्र /-

                            </td>

                        </tr>

                    </tbody>

                </table>


                <!-- Payment -->

                <div class="payment-box">

                    <div class="payment-title">
                        भुगतान विवरण
                    </div>

                    <table class="payment-table">

                        <tr>

                            <td class="payment-label">
                                माध्यम:
                            </td>

                            <td class="payment-value">
                                {{ $paymentMode }}
                            </td>

                            <td class="payment-label">
                                भुगतान दिनांक:
                            </td>

                            <td class="payment-value">
                                {{ $paymentDate }}
                            </td>

                        </tr>

                        <tr>

                            <td class="payment-label">
                                ट्रांजेक्शन आईडी:
                            </td>

                            <td class="payment-value" colspan="3">
                                {{ $transactionId }}
                            </td>

                        </tr>

                    </table>

                </div>


                <!-- Remarks -->

                <div class="remarks">

                    <div class="remarks-label">
                        टिप्पणी:-
                    </div>

                    <div class="remarks-value">
                        {{ data_get($invoice, 'remarks') ?? '-' }}
                    </div>

                </div>


                <!-- Footer -->

                <table class="footer">

                    <tr>

                        <td class="thanks">
                            धन्यवाद
                        </td>

                        <td class="signature">
                            <br>    
                            <span class="signature-line"></span>
                            प्राप्तकर्ता हस्ताक्षर<br>
                            @if ($invoice->status === 'paid')
                                <img src="/assets/images/PAID.jpeg" style="width: auto; height: 50px;" alt="PAID">
                            @endif
                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>


    <script src="{{ asset('assets/js/html2pdf.bundle.min.js') }}"></script>

    <script>

        const pdfFileName = @json($fileName);

        async function waitForImages(element) {

            const images = Array.from(
                element.querySelectorAll("img")
            );

            await Promise.all(
                images.map((image) => {

                    if (image.complete) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve) => {

                        image.addEventListener(
                            "load",
                            resolve,
                            { once: true }
                        );

                        image.addEventListener(
                            "error",
                            resolve,
                            { once: true }
                        );

                    });

                })
            );
        }


        async function downloadDonationReceiptPdf() {

            const button =
                document.getElementById("downloadPdfButton");

            const status =
                document.getElementById("downloadStatus");

            const receipt =
                document.getElementById("receiptPdf");


            if (typeof html2pdf !== "function") {

                console.error(
                    "html2pdf library is not available."
                );

                alert(
                    "PDF library load नहीं हुई। कृपया administrator से संपर्क करें।"
                );

                return;
            }


            button.disabled = true;

            button.textContent =
                "PDF तैयार हो रही है...";

            status.textContent =
                "फ़ॉन्ट और चित्र लोड किए जा रहे हैं...";


            try {

                if (
                    document.fonts &&
                    document.fonts.ready
                ) {
                    await document.fonts.ready;
                }


                await waitForImages(receipt);


                receipt.classList.add(
                    "pdf-export-mode"
                );


                const options = {
                    margin: 0,
                    filename: pdfFileName,

                    image: {
                        type: "jpeg",
                        quality: 0.98,
                    },

                    html2canvas: {
                        scale: 3,
                        useCORS: true,
                        allowTaint: false,
                        backgroundColor: "#ffffff",
                        logging: false,
                        letterRendering: true,
                        scrollX: 0,
                        scrollY: 0,
                    },

                    jsPDF: {
                        unit: "mm",
                        format: [105, 148],
                        orientation: "portrait",
                        compress: true,
                    },

                    pagebreak: {
                        mode: ["css", "legacy"],
                    },
                };
                status.textContent =
                    "PDF डाउनलोड की जा रही है...";


                await html2pdf()
                    .set(options)
                    .from(receipt)
                    .save();


                status.textContent =
                    "PDF सफलतापूर्वक डाउनलोड हो गई।";


            } catch (error) {

                console.error(
                    "Donation receipt PDF generation failed:",
                    error
                );

                status.textContent =
                    "PDF डाउनलोड नहीं हो सकी।";

                alert(
                    "PDF बनाते समय त्रुटि हुई। कृपया दोबारा प्रयास करें।"
                );

            } finally {

                receipt.classList.remove(
                    "pdf-export-mode"
                );

                button.disabled = false;

                button.textContent =
                    "PDF डाउनलोड करें";
            }
        }


        window.addEventListener(
            "load",
            function () {

                console.log(
                    "html2pdf loaded:",
                    typeof html2pdf
                );

            }
        );

    </script>

</body>

</html>
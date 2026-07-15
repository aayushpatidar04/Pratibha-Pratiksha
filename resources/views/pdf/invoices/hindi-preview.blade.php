<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $invoice->invoice_number }} - हिंदी रसीद</title>

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

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #6b7280;
            --background: #eef1f5;
            --border: #111;
            --muted: #555;
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
            background: var(--background);
            color: #111;
            font-family: "Aparajita", "Nirmala UI", "Mangal", serif;
            font-size: 11px;
            line-height: 1.3;
        }

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
            gap: 6px;
            min-width: 125px;
            border: 0;
            border-radius: 7px;
            padding: 9px 16px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .download-button {
            background: var(--primary);
        }

        .download-button:hover {
            background: var(--primary-dark);
        }

        .download-button:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .close-button {
            background: var(--secondary);
        }

        .close-button:hover {
            background: #4b5563;
        }

        .preview-message {
            color: #4b5563;
            font-size: 12px;
        }

        .receipt-wrapper {
            padding: 18px;
        }

        /*
         * Exact A6 dimensions.
         * Internal padding acts as the page margin.
         */
        .receipt-page {
            width: 105mm;
            min-height: 148mm;
            margin: 0 auto;
            padding: 4mm;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.15);
        }

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            width: 35mm;
            max-width: 100%;
            max-height: 15mm;
            margin: 0 auto 2px;
            object-fit: contain;
        }

        .title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.15;
        }

        .subtitle {
            margin-top: 2px;
            font-size: 10.5px;
            line-height: 1.35;
            text-align: center;
        }

        .receipt-title {
            margin: 5px 0 6px;
            padding: 3px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 700;
            text-align: center;
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
            overflow-wrap: anywhere;
        }

        .info-label {
            width: 20%;
            font-weight: 700;
        }

        .info-value {
            width: 30%;
        }

        .items-table {
            margin-top: 4px;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            border: 1px solid var(--border);
            padding: 2.5px 3px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .items-table th {
            text-align: center;
            font-weight: 700;
        }

        .right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }

        .muted {
            color: var(--muted);
            font-size: 9px;
        }

        .payment-box {
            margin-top: 5px;
            padding: 3px;
            border: 1px solid var(--border);
        }

        .payment-table {
            table-layout: fixed;
        }

        .payment-table td {
            padding: 1px;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .summary {
            margin-top: 5px;
            font-size: 10px;
        }

        .summary p {
            margin: 2px 0;
        }

        .footer {
            width: 100%;
            margin-top: 14px;
        }

        .footer td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
            font-size: 9px;
        }

        .footer-note {
            color: var(--muted);
            line-height: 1.25;
        }

        .signature {
            text-align: right;
            font-weight: 700;
        }

        /*
         * Applied only while generating the PDF.
         * Removes preview shadow without changing the browser preview.
         */
        .pdf-export-mode {
            margin: 0 !important;
            box-shadow: none !important;
        }

        @media (max-width: 600px) {
            .receipt-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            .preview-toolbar {
                flex-wrap: wrap;
            }

            .preview-message {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    @php
        $isRegistrationInvoice = $invoice->fee_type === 'registration_fee';

        $resident = $invoice->resident;
        $application = $invoice->application;

        $personName = $resident
            ? trim(
                ($resident->first_name ?? '') .
                ' ' .
                ($resident->last_name ?? '')
            )
            : ($application?->student_name ?? '-');

        $fatherName = $resident
            ? ($resident->father_name ?? '-')
            : ($application?->father_name ?? '-');

        $mobile = $resident
            ? (
                $resident->phone
                ?? $resident->whatsapp_number
                ?? '-'
            )
            : ($application?->student_mobile ?? '-');

        $referenceLabel = $resident
            ? 'प्रवेश क्रमांक'
            : 'आवेदन क्रमांक';

        $referenceNumber = $resident
            ? ($resident->resident_code ?? '-')
            : ($application?->application_no ?? '-');

        $receiptTitle = $isRegistrationInvoice
            ? 'पंजीकरण शुल्क रसीद'
            : 'छात्रावास शुल्क रसीद';

        $payment = $invoice->payments
            ->sortByDesc('payment_date')
            ->first();

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
            : 'लंबित';

        $invoiceDate = optional($invoice->created_at)->format('d-m-Y') ?? '-';

        $paymentDate = $payment?->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y')
            : '-';

        $dueDate = optional($invoice->due_date)->format('d-m-Y') ?? '-';

        $billingMonth = $invoice->monthlyConfig?->billing_month
            ? \Carbon\Carbon::parse($invoice->monthlyConfig->billing_month)->format('M-Y')
            : optional($invoice->due_date)->format('M-Y');

        $itemLabels = [
            'rent' => 'छात्रावास शुल्क',
            'hostel_fee' => 'छात्रावास शुल्क',
            'mess' => 'भोजन शुल्क',
            'mess_fee' => 'भोजन शुल्क',
            'caution_money' => 'सावधानी राशि',
            'security_deposit' => 'सुरक्षा जमा राशि',
            'registration_fee' => 'पंजीकरण शुल्क',
            'amenity' => 'सुविधा शुल्क',
            'other' => 'अन्य शुल्क',
        ];

        $numberFormatter = \NumberFormatter::create(
            'en_IN',
            \NumberFormatter::SPELLOUT
        );

        $amountInWords = ucfirst(
            $numberFormatter->format((float) $invoice->amount + (float) $invoice->late_fee_amount)
        );

        $logoUrl = asset(
            'assets/images/pratibha-pratiksha-logo-text.png'
        );

        $fileName = $invoice->invoice_number . '-hindi.pdf';
    @endphp

    <div class="preview-toolbar">
        <button type="button" id="downloadPdfButton" class="toolbar-button download-button"
            onclick="downloadReceiptPdf()">
            PDF डाउनलोड करें
        </button>

        <button type="button" class="toolbar-button close-button" onclick="window.close()">
            बंद करें
        </button>

        <span id="downloadStatus" class="preview-message">
            यह रसीद A6 आकार में डाउनलोड होगी।
        </span>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-page" id="receiptPdf">
            <div class="center">
                <img src="{{ $logoUrl }}" class="logo" alt="प्रतिभा प्रतिष्ठान" crossorigin="anonymous">

                <div class="subtitle">
                    श्री चंद्रप्रभ दिगंबर जैन मंदिर 11-12, उदयनगर बिचौली मर्दाना<br>
                    रोड फीनिक्स अस्पताल के पीछे इंदौर-452016 म.प्र
                </div>
            </div>

            <div class="receipt-title">
                {{ $receiptTitle }}
            </div>

            <table class="info-table">
                <tr>
                    <td class="info-label">रसीद संख्या</td>
                    <td class="info-value">
                        {{ $invoice->invoice_number }}
                    </td>

                    <td class="info-label">दिनांक</td>
                    <td class="info-value">
                        {{ $invoiceDate }}
                    </td>
                </tr>

                <tr>
                    <td class="info-label">नाम</td>
                    <td class="info-value">
                        {{ $personName }}
                    </td>

                    <td class="info-label">{{ $referenceLabel }}</td>
                    <td class="info-value">
                        {{ $referenceNumber }}
                    </td>
                </tr>

                <tr>
                    <td class="info-label">पिता का नाम</td>
                    <td class="info-value">
                        {{ $fatherName }}
                    </td>

                    <td class="info-label">मोबाइल</td>
                    <td class="info-value">
                        {{ $mobile }}
                    </td>
                </tr>

                @if (!$isRegistrationInvoice && $resident)
                    <tr>
                        <td class="info-label">कमरा / बेड</td>
                        <td class="info-value">
                            @if ($invoice->stay)
                                {{ $invoice->stay->room?->room_number ?? '-' }}

                                @if ($invoice->stay->bed)
                                            /
                                            {{ $invoice->stay->bed->bed_number
                                    ?? $invoice->stay->bed->id }}
                                @endif
                            @else
                                -
                            @endif
                        </td>

                        <td class="info-label">देय दिनांक</td>
                        <td class="info-value">
                            {{ $dueDate }}
                        </td>
                    </tr>
                @endif
            </table>

            @if ($isRegistrationInvoice)
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 72%;">विवरण</th>
                            <th style="width: 28%;">राशि (₹)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($invoice->items as $item)
                                    <tr>
                                        <td>
                                            {{ $itemLabels[$item->item_type]
                            ?? $item->title }}

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
                                <td>पंजीकरण शुल्क</td>
                                <td class="right">
                                    {{ number_format((float) $invoice->amount, 2) }}
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <td class="bold">कुल राशि</td>
                            <td class="right bold">
                                {{ number_format((float) $invoice->amount, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="bold">प्राप्त राशि</td>
                            <td class="right bold">
                                {{ number_format((float) $invoice->paid_amount, 2) }}
                            </td>
                        </tr>

                        @if ((float) $invoice->balance_due > 0)
                            <tr>
                                <td class="bold">बकाया राशि</td>
                                <td class="right bold">
                                    {{ number_format((float) $invoice->balance_due, 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @else
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">विवरण</th>
                            <th style="width: 22%;">महीना</th>
                            <th style="width: 28%;">राशि (₹)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($invoice->items as $item)
                                    <tr>
                                        <td>
                                            {{ $itemLabels[$item->item_type]
                            ?? $item->title }}

                                            @if ($item->description)
                                                <br>
                                                <span class="muted">
                                                    {{ $item->description }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            {{ $billingMonth ?? '-' }}
                                        </td>

                                        <td class="right">
                                            {{ number_format((float) $item->amount, 2) }}
                                        </td>
                                    </tr>
                        @empty
                                    <tr>
                                        <td>
                                            {{ $itemLabels[$invoice->fee_type]
                            ?? ucwords(str_replace('_', ' ', $invoice->fee_type)) }}
                                        </td>

                                        <td class="text-center">
                                            {{ $billingMonth ?? '-' }}
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

                        {{-- Late Fee --}}
                        @if ($showLateFee)
                            <tr>
                                <td>विलंब शुल्क</td>
                                <td class="text-center">-</td>

                                <td class="right">
                                    {{ number_format($lateFeeAmount, 2) }}
                                </td>
                            </tr>
                        @endif

                        {{-- Late Fee Waived --}}
                        @if (
                                $invoice->late_fee_waived &&
                                (float) $invoice->late_fee_amount > 0
                            )
                            <tr>
                                <td>विलंब शुल्क (माफ)</td>
                                <td class="text-center">-</td>
                                <td class="right">
                                    -{{ number_format((float) $invoice->late_fee_amount, 2) }}
                                </td>
                            </tr>
                        @endif

                        {{-- Total Payable --}}
                        <tr>
                            <td colspan="2" class="bold">
                                कुल देय राशि
                            </td>

                            <td class="right bold">
                                {{ number_format((float) $invoice->total_payable, 2) }}
                            </td>
                        </tr>

                        {{-- Paid Amount --}}
                        @if ((float) $invoice->paid_amount > 0)
                            <tr>
                                <td colspan="2" class="bold">
                                    प्राप्त राशि
                                </td>

                                <td class="right bold">
                                    {{ number_format((float) $invoice->paid_amount, 2) }}
                                </td>
                            </tr>
                        @endif

                        {{-- Balance --}}
                        @if ((float) $invoice->balance_due > 0)
                            <tr>
                                <td colspan="2" class="bold">
                                    बकाया राशि
                                </td>

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
                        <td class="info-label">भुगतान माध्यम</td>
                        <td class="info-value">
                            {{ $paymentMode }}
                        </td>

                        <td class="info-label">भुगतान दिनांक</td>
                        <td class="info-value">
                            {{ $paymentDate }}
                        </td>
                    </tr>

                    <tr>
                        <td class="info-label">प्राप्ति क्रमांक</td>
                        <td class="info-value">
                            {{ $payment?->receipt_number ?? '-' }}
                        </td>

                        <td class="info-label">लेनदेन क्रमांक</td>
                        <td class="info-value">
                            {{ $payment?->transaction_id ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="summary">
                <p>
                    <strong>शब्दों में:</strong>
                    {{ $amountInWords }} Rupees Only
                </p>

                <p>
                    <strong>टिप्पणी:</strong>
                    {{ $invoice->description ?? '-' }}
                </p>

                @if ($invoice->late_fee_waived)
                    <p>
                        <strong>विलंब शुल्क माफ:</strong>
                        हाँ
                        @if ($invoice->waive_reason)
                            — {{ $invoice->waive_reason }}
                        @endif
                    </p>
                @endif
            </div>

            <table class="footer">
                <tr>
                    <td class="footer-note">
                        जारी दिनांक:
                        {{ now()->format('d-m-Y h:i A') }}
                        <br>
                        यह कंप्यूटर द्वारा तैयार की गई रसीद है।
                    </td>

                    <td class="signature">
                        अधिकृत हस्ताक्षर
                    </td>
                </tr>
            </table>
            @if (
                    $invoice->status !== 'paid' &&
                    !$invoice->is_overdue &&
                    (float) $invoice->late_fee_amount > 0 &&
                    !$invoice->late_fee_waived
                )
                <p style="font-size:10px; margin-top:6px;">
                    <strong>नोट:</strong>
                    यदि भुगतान
                    {{ optional($invoice->due_date)->format('d-m-Y') }}
                    के बाद किया जाता है, तो
                    ₹{{ number_format((float) $invoice->late_fee_amount, 2) }}
                    विलंब शुल्क देय होगा।
                </p>
            @endif
        </div>
    </div>

    <script src="{{ asset('assets/js/html2pdf.bundle.min.js') }}"></script>

    <script>
        const pdfFileName = @json($fileName);

        async function waitForImages(element) {
            const images = Array.from(element.querySelectorAll("img"));

            await Promise.all(
                images.map((image) => {
                    if (image.complete) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve) => {
                        image.addEventListener("load", resolve, {
                            once: true,
                        });

                        image.addEventListener("error", resolve, {
                            once: true,
                        });
                    });
                }),
            );
        }

        async function downloadReceiptPdf() {
            const button = document.getElementById(
                "downloadPdfButton",
            );

            const status = document.getElementById(
                "downloadStatus",
            );

            const receipt = document.getElementById("receiptPdf");

            if (typeof html2pdf !== "function") {
                console.error("html2pdf library is not available.");

                alert(
                    "PDF library load नहीं हुई। कृपया administrator से संपर्क करें।"
                );

                return;
            }

            button.disabled = true;
            button.textContent = "PDF तैयार हो रही है...";
            status.textContent =
                "फ़ॉन्ट और चित्र लोड किए जा रहे हैं...";

            try {
                if (
                    document.fonts
                    && document.fonts.ready
                ) {
                    await document.fonts.ready;
                }

                await waitForImages(receipt);

                receipt.classList.add("pdf-export-mode");

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
                        format: "a6",
                        orientation: "portrait",
                        compress: true,
                    },

                    pagebreak: {
                        mode: ["avoid-all", "css", "legacy"],
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
                    "Hindi receipt PDF generation failed:",
                    error,
                );

                status.textContent =
                    "PDF डाउनलोड नहीं हो सकी।";

                alert(
                    "PDF बनाते समय त्रुटि हुई। कृपया दोबारा प्रयास करें।",
                );
            } finally {
                receipt.classList.remove("pdf-export-mode");

                button.disabled = false;
                button.textContent = "PDF डाउनलोड करें";
            }
        }

        window.addEventListener("load", function () {
            console.log("html2pdf loaded:", typeof html2pdf);

            if (typeof html2pdf !== "function") {
                console.error(
                    "html2pdf.bundle.min.js was not loaded. Check the file path or browser network tab."
                );
            }
        });
    </script>
</body>

</html>
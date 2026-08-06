<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $invoice->invoice_number }} - Receipt</title>

    <style>
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
            font-family: DejaVu Sans, "Arial", sans-serif;
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
            font-family: DejaVu Sans, Arial, sans-serif;
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
            width: 24%;
            font-weight: 700;
        }

        .info-value {
            width: 26%;
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

        @media print {
            .preview-toolbar {
                display: none;
            }

            .receipt-wrapper {
                padding: 0;
                background: #fff;
            }

            .receipt-page {
                box-shadow: none;
                margin: 0;
                padding: 0;
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
            ? 'Admission No.'
            : 'Application No.';

        $referenceNumber = $resident
            ? ($resident->resident_code ?? '-')
            : ($application?->application_no ?? '-');

        $receiptTitle = $isRegistrationInvoice
            ? 'Registration Fee Receipt'
            : 'Hostel Fee Receipt';

        $payment = $invoice->payments
            ->sortByDesc('payment_date')
            ->first();

        $paymentMode = $payment
            ? ucwords(str_replace('_', ' ', $payment->payment_mode))
            : 'Pending';

        $invoiceDate = optional($invoice->created_at)->format('d-m-Y') ?? '-';

        $paymentDate = $payment?->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y')
            : '-';

        $dueDate = optional($invoice->due_date)->format('d-m-Y') ?? '-';

        $billingMonth = $invoice->monthlyConfig?->billing_month
            ? \Carbon\Carbon::parse($invoice->monthlyConfig->billing_month)->format('M-Y')
            : optional($invoice->due_date)->format('M-Y');

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

        $fileName = $invoice->invoice_number . '-english.pdf';
    @endphp

    <div class="preview-toolbar">
        <button type="button" id="downloadPdfButton" class="toolbar-button download-button"
            onclick="downloadReceiptPdf()">
            Download PDF
        </button>

        <button type="button" class="toolbar-button close-button" onclick="window.close()">
            Close
        </button>

        <span id="downloadStatus" class="preview-message">
            This receipt will be downloaded in A6 size.
        </span>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-page" id="receiptPdf">
            <div class="center">
                <img src="{{ $logoUrl }}" class="logo" alt="Pratibha Pratiksha" crossorigin="anonymous">

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
                    <td class="info-value">
                        {{ $invoice->invoice_number }}
                    </td>

                    <td class="info-label">Date</td>
                    <td class="info-value">
                        {{ $invoiceDate }}
                    </td>
                </tr>

                <tr>
                    <td class="info-label">Student Name</td>
                    <td class="info-value">
                        {{ $personName }}
                    </td>

                    <td class="info-label">{{ $referenceLabel }}</td>
                    <td class="info-value">
                        {{ $referenceNumber }}
                    </td>
                </tr>

                <tr>
                    <td class="info-label">Father Name</td>
                    <td class="info-value">
                        {{ $fatherName }}
                    </td>

                    <td class="info-label">Mobile No.</td>
                    <td class="info-value">
                        {{ $mobile }}
                    </td>
                </tr>

                @if (!$isRegistrationInvoice && $resident)
                    <tr>
                        <td class="info-label">Room / Stay</td>
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

                        <td class="info-label"></td>
                        <td class="info-value"></td>
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

                        @if ((float) $invoice->balance_due > 0)
                            <tr>
                                <td class="bold">Balance Due</td>
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
                            <th style="width: 50%;">Particular</th>
                            <th style="width: 22%;">Month</th>
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
                                            {{ ucwords(str_replace('_', ' ', $invoice->fee_type)) }}
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
                                <td>Late Fee</td>
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
                                <td>Late Fee (Waived)</td>
                                <td class="text-center">-</td>
                                <td class="right">
                                    -{{ number_format((float) $invoice->late_fee_amount, 2) }}
                                </td>
                            </tr>
                        @endif

                        {{-- Total Payable --}}
                        <tr>
                            <td colspan="2" class="bold">
                                Total Payable
                            </td>

                            <td class="right bold">
                                {{ number_format((float) $invoice->total_payable, 2) }}
                            </td>
                        </tr>

                        {{-- Paid Amount --}}
                        @if ((float) $invoice->paid_amount > 0)
                            <tr>
                                <td colspan="2" class="bold">
                                    Paid
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
                                    Balance Due
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
                        <td class="info-label">Payment Mode</td>
                        <td class="info-value">
                            {{ $paymentMode }}
                        </td>

                        <td class="info-label">Payment Date</td>
                        <td class="info-value">
                            {{ $paymentDate }}
                        </td>
                    </tr>

                    <tr>
                        <td class="info-label">Receipt No.</td>
                        <td class="info-value">
                            {{ $payment?->receipt_number ?? '-' }}
                        </td>

                        <td class="info-label">Transaction ID</td>
                        <td class="info-value">
                            {{ $payment?->transaction_id ?? '-' }}
                        </td>
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
                        Generated on {{ now()->format('d-m-Y h:i A') }}<br>
                        This is a computer-generated receipt.
                    </td>

                    <td class="signature">
                        Authorized Signature
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
                    <strong>Note:</strong>
                    A late fee of ₹{{ number_format((float) $invoice->late_fee_amount, 2) }}
                    will be applicable if payment is made after
                    {{ optional($invoice->due_date)->format('d-m-Y') }}.
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
            const button = document.getElementById("downloadPdfButton");
            const status = document.getElementById("downloadStatus");
            const receipt = document.getElementById("receiptPdf");

            if (typeof html2pdf !== "function") {
                console.error("html2pdf library is not available.");
                alert(
                    "PDF library failed to load. Please contact administrator."
                );
                return;
            }

            button.disabled = true;
            button.textContent = "Generating PDF...";
            status.textContent = "Loading fonts and images...";

            try {
                if (document.fonts && document.fonts.ready) {
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

                status.textContent = "Downloading PDF...";

                await html2pdf()
                    .set(options)
                    .from(receipt)
                    .save();

                status.textContent = "PDF downloaded successfully.";
            } catch (error) {
                console.error(
                    "English receipt PDF generation failed:",
                    error,
                );

                status.textContent = "Failed to download PDF.";

                alert(
                    "Error generating PDF. Please try again.",
                );
            } finally {
                receipt.classList.remove("pdf-export-mode");
                button.disabled = false;
                button.textContent = "Download PDF";
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
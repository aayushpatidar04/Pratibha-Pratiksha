<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Payment Receipt {{ $payment->receipt_number ?? $payment->id }}
    </title>

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
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.25;
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
        }

        .toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            border: 0;
            border-radius: 7px;
            padding: 9px 16px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
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

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            width: 35mm;
            max-width: 100%;
            max-height: 13mm;
            margin: 0 auto 2px;
            object-fit: contain;
        }

        .subtitle {
            margin-top: 1px;
            font-size: 7.8px;
            line-height: 1.3;
            text-align: center;
        }

        .receipt-title {
            margin: 4px 0 5px;
            padding: 2.5px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            table-layout: fixed;
            margin-bottom: 4px;
        }

        .info-table td {
            padding: 1.4px 1px;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .label {
            width: 20%;
            font-weight: 700;
        }

        .value {
            width: 30%;
        }

        .amount-box {
            margin-top: 5px;
            table-layout: fixed;
            border: 1px solid var(--border);
        }

        .amount-box td {
            padding: 3px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .amount-box tr:last-child td {
            border-bottom: 0;
        }

        .amount-label {
            width: 38%;
        }

        .amount-value {
            width: 62%;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .note {
            margin-top: 5px;
            font-size: 8.5px;
            overflow-wrap: anywhere;
        }

        .footer {
            width: 100%;
            margin-top: 10px;
            table-layout: fixed;
        }

        .footer td {
            padding: 0;
            vertical-align: bottom;
            border: 0;
            font-size: 7.5px;
        }

        .footer-note {
            width: 65%;
            color: var(--muted);
            line-height: 1.25;
        }

        .signature {
            width: 35%;
            text-align: right;
            font-weight: 700;
        }

        .receipt-wrapper {
            padding: 18px;
        }

        .receipt-page {
            width: 105mm;
            min-height: 148mm;
            margin: 0 auto;
            padding: 4mm;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.15);
        }

        .pdf-export-mode {
            margin: 0 !important;
            box-shadow: none !important;
        }

        @media (max-width: 600px) {
            .preview-toolbar {
                flex-wrap: wrap;
            }

            .preview-message {
                width: 100%;
                text-align: center;
            }

            .receipt-wrapper {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    @php
        $resident = $invoice?->resident;
        $application = $invoice?->application;

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

        $referenceLabel = $resident
            ? 'Resident Code'
            : 'Application No.';

        $referenceNumber = $resident
            ? ($resident->resident_code ?? '-')
            : ($application?->application_no ?? '-');

        $paymentMode = ucwords(
            str_replace('_', ' ', $payment->payment_mode)
        );

        $numberFormatter = \NumberFormatter::create(
            'en_IN',
            \NumberFormatter::SPELLOUT
        );

        $amountInWords = ucfirst(
            $numberFormatter->format((float) $payment->amount)
        );

        $logoUrl = asset(
            'assets/images/pratibha-pratiksha-logo-text.png'
        );

        $fileName = 'Payment-Receipt-' .
            ($payment->receipt_number ?? $payment->id) .
            '.pdf';
    @endphp

    <div class="preview-toolbar">
        <button type="button" id="downloadPdfButton" class="toolbar-button download-button"
            onclick="downloadReceiptPdf()">
            Save PDF
        </button>

        <button type="button" class="toolbar-button close-button" onclick="window.close()">
            Close
        </button>

        <span id="downloadStatus" class="preview-message">
            The receipt will be downloaded in A6 size.
        </span>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-page" id="receiptPdf">
            <div class="center">
                <img src="{{ $logoUrl }}" class="logo" alt="Pratibha Pratiksha" crossorigin="anonymous">

                <div class="subtitle">
                    Shri Chandraprabh Digambar Jain Mandir 11-12,
                    Udayanagar<br>
                    Bicholi Mardana Road, Near Phoenix Hospital,
                    Indore - 452016 M.P.
                </div>
            </div>

            <div class="receipt-title">
                Payment Receipt
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Receipt No.</td>
                    <td class="value">
                        {{ $payment->receipt_number ?? '-' }}
                    </td>

                    <td class="label">Payment Date</td>
                    <td class="value">
                        {{ optional($payment->payment_date)->format('d-m-Y') }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Invoice No.</td>
                    <td class="value">
                        {{ $invoice?->invoice_number ?? '-' }}
                    </td>

                    <td class="label">{{ $referenceLabel }}</td>
                    <td class="value">
                        {{ $referenceNumber }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Name</td>
                    <td class="value">
                        {{ $personName }}
                    </td>

                    <td class="label">Father Name</td>
                    <td class="value">
                        {{ $fatherName }}
                    </td>
                </tr>

                <tr>
                    <td class="label">Payment Mode</td>
                    <td class="value">
                        {{ $paymentMode }}
                    </td>

                    <td class="label">Transaction ID</td>
                    <td class="value">
                        {{ $payment->transaction_id ?: '-' }}
                    </td>
                </tr>
            </table>

            <table class="amount-box">
                <tr>
                    <td class="amount-label bold">
                        Amount Received
                    </td>

                    <td class="amount-value right bold">
                        ₹{{ number_format((float) $payment->amount, 2) }}
                    </td>
                </tr>

                <tr>
                    <td class="amount-label bold">
                        Amount in Words
                    </td>

                    <td class="amount-value right">
                        {{ $amountInWords }} Rupees Only
                    </td>
                </tr>
            </table>

            <div class="note">
                <strong>Notes:</strong>
                {{ $payment->notes ?: '-' }}
            </div>

            <table class="footer">
                <tr>
                    <td class="footer-note">
                        Generated on
                        {{ now()->format('d-m-Y h:i A') }}
                        <br>
                        This is a computer-generated payment receipt.
                    </td>

                    <td class="signature">
                        Authorized Signature
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script src="{{ asset('assets/js/html2pdf.bundle.min.js') }}"></script>

    <script>
        const pdfFileName = @json(
            'Payment-Receipt-' .
            ($payment->receipt_number ?? $payment->id) .
            '.pdf'
        );

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

            const receipt = document.getElementById(
                "receiptPdf",
            );

            if (typeof html2pdf !== "function") {
                console.error(
                    "html2pdf library is not available.",
                );

                alert(
                    "PDF library could not be loaded. Please refresh and try again.",
                );

                return;
            }

            button.disabled = true;
            button.textContent = "Preparing PDF...";
            status.textContent =
                "Loading receipt content and image...";

            try {
                if (
                    document.fonts &&
                    document.fonts.ready
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
                        mode: [
                            "avoid-all",
                            "css",
                            "legacy",
                        ],
                    },
                };

                status.textContent =
                    "Downloading PDF...";

                await html2pdf()
                    .set(options)
                    .from(receipt)
                    .save();

                status.textContent =
                    "PDF downloaded successfully.";
            } catch (error) {
                console.error(
                    "Payment receipt PDF generation failed:",
                    error,
                );

                status.textContent =
                    "PDF download failed.";

                alert(
                    "An error occurred while generating the PDF.",
                );
            } finally {
                receipt.classList.remove(
                    "pdf-export-mode",
                );

                button.disabled = false;
                button.textContent = "Save PDF";
            }
        }

        window.addEventListener("load", function () {
            console.log(
                "html2pdf loaded:",
                typeof html2pdf,
            );
        });
    </script>
</body>

</html>
<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    BedDouble,
    Building2,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    CreditCard,
    Download,
    ExternalLink,
    FileImage,
    FileText,
    IndianRupee,
    Printer,
    ReceiptText,
    ShieldCheck,
    WalletCards,
} from "lucide-vue-next";

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },

    payments: {
        type: Array,
        default: () => [],
    },
});

const money = (value) => {
    return Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (value) => {
    if (!value) return "—";

    const date = String(value).includes("T")
        ? new Date(value)
        : new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const formatDateTime = (value) => {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const labelize = (value) => {
    if (!value) return "—";

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const statusClasses = {
    paid: "bg-emerald-100 text-emerald-700 border-emerald-200",
    unpaid: "bg-amber-100 text-amber-700 border-amber-200",
    partial: "bg-blue-100 text-blue-700 border-blue-200",
    overdue: "bg-red-100 text-red-700 border-red-200",
    cancelled: "bg-slate-100 text-slate-600 border-slate-200",
};

const statusClass = (status) => {
    return (
        statusClasses[status] ||
        "bg-slate-100 text-slate-600 border-slate-200"
    );
};

const paymentModeClasses = {
    cash: "bg-emerald-50 text-emerald-700",
    card: "bg-indigo-50 text-indigo-700",
    razorpay: "bg-blue-50 text-blue-700",
    upi: "bg-purple-50 text-purple-700",
    bank_transfer: "bg-cyan-50 text-cyan-700",
};

const paymentModeClass = (mode) => {
    return (
        paymentModeClasses[mode] ||
        "bg-slate-100 text-slate-600"
    );
};

const proofUrl = (proof) => {
    if (!proof?.file_path) return "#";

    if (
        proof.file_path.startsWith("http://") ||
        proof.file_path.startsWith("https://") ||
        proof.file_path.startsWith("/storage/")
    ) {
        return proof.file_path;
    }

    return `/storage/${proof.file_path}`;
};

const proofIsImage = (proof) => {
    const value = String(
        proof?.original_name || proof?.file_path || "",
    ).toLowerCase();

    return [
        ".jpg",
        ".jpeg",
        ".png",
        ".webp",
        ".gif",
    ].some((extension) => value.endsWith(extension));
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <ResidentLayout title="Invoice Details">
        <div class="space-y-6">
            <!-- Navigation and actions -->
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between print:hidden"
            >
                <Link
                    :href="route('resident.billing.index')"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Billing
                </Link>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="
                            route('resident.billing.print.hi', {
                                invoice: invoice.id,
                            })
                        " target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    >
                        <Download class="h-4 w-4" />
                        PDF/Print (Hindi)
                    </a>

                    <a
                        :href="
                            route('resident.billing.pdf.en', {
                                invoice: invoice.id,
                            })
                        " target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    >
                        <Download class="h-4 w-4" />
                        PDF/Print (English)
                    </a>
                </div>
            </section>

            <!-- Invoice document -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm print:rounded-none print:border-0 print:shadow-none"
            >
                <!-- Header -->
                <div
                    class="border-b border-slate-100 bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-7 text-white sm:px-8"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15"
                                >
                                    <ReceiptText class="h-6 w-6" />
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100"
                                    >
                                        Pratibha Pratiksha Hostel
                                    </p>

                                    <h1 class="mt-1 text-2xl font-bold">
                                        Fee Invoice
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-5 text-sm text-indigo-100">
                                Invoice Number
                            </p>

                            <p class="mt-1 text-xl font-bold">
                                {{ invoice.invoice_number }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl bg-white/10 p-4 text-left backdrop-blur sm:min-w-56 sm:text-right"
                        >
                            <span
                                class="inline-flex rounded-full border px-3 py-1 text-xs font-bold capitalize"
                                :class="
                                    invoice.status === 'paid'
                                        ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
                                        : invoice.status === 'overdue'
                                          ? 'border-red-200 bg-red-100 text-red-700'
                                          : 'border-white/20 bg-white/15 text-white'
                                "
                            >
                                {{ labelize(invoice.status) }}
                            </span>

                            <p class="mt-4 text-xs text-indigo-100">
                                Invoice Amount
                            </p>

                            <p class="mt-1 text-2xl font-bold">
                                {{ money(invoice.amount) }}
                            </p>

                            <p class="mt-3 text-xs text-indigo-100">
                                Due {{ formatDate(invoice.due_date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Invoice metadata -->
                <div
                    class="grid grid-cols-1 gap-4 border-b border-slate-100 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-8"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Fee Type
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ labelize(invoice.fee_type) }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Invoice Date
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ formatDate(invoice.created_at) }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Due Date
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold"
                            :class="
                                invoice.status === 'overdue'
                                    ? 'text-red-600'
                                    : 'text-slate-900'
                            "
                        >
                            {{ formatDate(invoice.due_date) }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Status
                        </p>

                        <span
                            class="mt-1 inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold capitalize"
                            :class="statusClass(invoice.status)"
                        >
                            {{ labelize(invoice.status) }}
                        </span>
                    </div>
                </div>

                <!-- Description and stay -->
                <div
                    class="grid grid-cols-1 gap-5 border-b border-slate-100 px-6 py-6 lg:grid-cols-2 sm:px-8"
                >
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <div class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-indigo-600" />

                            <h2 class="text-sm font-bold text-slate-900">
                                Invoice Description
                            </h2>
                        </div>

                        <p
                            class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600"
                        >
                            {{
                                invoice.description ||
                                "Hostel fee invoice"
                            }}
                        </p>
                    </div>

                    <div
                        v-if="invoice.stay"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <div class="flex items-center gap-2">
                            <Building2 class="h-5 w-5 text-blue-600" />

                            <h2 class="text-sm font-bold text-slate-900">
                                Stay Information
                            </h2>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2 gap-4 text-sm"
                        >
                            <div>
                                <p class="text-xs text-slate-400">
                                    Building
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    {{ invoice.stay.building || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Room and Bed
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    Room {{ invoice.stay.room || "—" }}
                                    · Bed {{ invoice.stay.bed || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Check-in
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    {{
                                        formatDate(
                                            invoice.stay.check_in_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Check-out
                                </p>

                                <p class="mt-1 font-semibold text-slate-900">
                                    {{
                                        formatDate(
                                            invoice.stay.check_out_date,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="px-6 py-6 sm:px-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Fee Breakdown
                            </h2>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Line items included in this invoice.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                        >
                            {{ invoice.items?.length || 0 }}
                            item{{
                                invoice.items?.length === 1 ? "" : "s"
                            }}
                        </span>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
                                >
                                    <tr>
                                        <th class="px-4 py-3 text-left">
                                            Description
                                        </th>

                                        <th class="px-4 py-3 text-left">
                                            Type
                                        </th>

                                        <th class="px-4 py-3 text-right">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(item, index) in invoice.items"
                                        :key="`${item.item_type}-${index}`"
                                        :class="
                                            item.is_late_fee
                                                ? 'bg-red-50/50'
                                                : ''
                                        "
                                    >
                                        <td class="px-4 py-4">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                                    :class="
                                                        item.is_late_fee
                                                            ? 'bg-red-100 text-red-600'
                                                            : 'bg-indigo-50 text-indigo-600'
                                                    "
                                                >
                                                    <CircleAlert
                                                        v-if="
                                                            item.is_late_fee
                                                        "
                                                        class="h-4 w-4"
                                                    />

                                                    <ReceiptText
                                                        v-else
                                                        class="h-4 w-4"
                                                    />
                                                </div>

                                                <div class="min-w-0">
                                                    <p
                                                        class="font-semibold text-slate-900"
                                                    >
                                                        {{
                                                            item.title ||
                                                            labelize(
                                                                item.item_type,
                                                            )
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            item.description
                                                        "
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        {{
                                                            item.description
                                                        }}
                                                    </p>

                                                    <span
                                                        v-if="
                                                            item.is_late_fee
                                                        "
                                                        class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-600"
                                                    >
                                                        Late Fee
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-slate-600">
                                            {{
                                                labelize(
                                                    item.item_type,
                                                )
                                            }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-4 py-4 text-right font-bold"
                                            :class="
                                                item.is_late_fee
                                                    ? 'text-red-600'
                                                    : 'text-slate-900'
                                            "
                                        >
                                            {{ money(item.amount) }}
                                        </td>
                                    </tr>

                                    <tr v-if="!invoice.items?.length">
                                        <td
                                            colspan="3"
                                            class="px-4 py-10 text-center text-sm text-slate-400"
                                        >
                                            No invoice items available.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div
                    class="border-t border-slate-100 bg-slate-50 px-6 py-6 sm:px-8"
                >
                    <div class="ml-auto max-w-md space-y-3">
                        <div
                            class="flex items-center justify-between gap-4 text-sm"
                        >
                            <span class="text-slate-500">
                                Invoice Amount
                            </span>

                            <span class="font-semibold text-slate-900">
                                {{ money(invoice.amount) }}
                            </span>
                        </div>

                        <div
                            v-if="Number(invoice.late_fee) > 0"
                            class="flex items-center justify-between gap-4 text-sm"
                        >
                            <span class="text-red-600">
                                Included Late Fee
                            </span>

                            <span class="font-semibold text-red-600">
                                {{ money(invoice.late_fee) }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 text-sm"
                        >
                            <span class="text-slate-500">
                                Amount Paid
                            </span>

                            <span class="font-semibold text-emerald-700">
                                {{ money(invoice.paid_amount) }}
                            </span>
                        </div>

                        <div
                            class="border-t border-slate-200 pt-3"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <span class="text-base font-bold text-slate-900">
                                    Balance Due
                                </span>

                                <span
                                    class="text-xl font-bold"
                                    :class="
                                        Number(invoice.balance_amount) > 0
                                            ? 'text-red-700'
                                            : 'text-emerald-700'
                                    "
                                >
                                    {{ money(invoice.balance_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Outstanding alert -->
            <section
                v-if="Number(invoice.balance_amount) > 0"
                class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"
                    >
                        <CircleAlert class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-amber-900">
                            Payment Pending
                        </h3>

                        <p class="mt-1 text-sm text-amber-700">
                            A balance of
                            <strong>
                                {{ money(invoice.balance_amount) }}
                            </strong>
                            remains due for this invoice.
                        </p>

                        <p class="mt-1 text-xs text-amber-600">
                            Due date:
                            {{ formatDate(invoice.due_date) }}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700"
                >
                    <CreditCard class="h-4 w-4" />
                    Pay Invoice
                </button>
            </section>

            <!-- Payment history -->
            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Payment History
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            All payments recorded against this invoice.
                        </p>
                    </div>

                    <span
                        class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                    >
                        {{ payments.length }}
                        payment{{ payments.length === 1 ? "" : "s" }}
                    </span>
                </div>

                <div
                    v-if="payments.length"
                    class="divide-y divide-slate-100"
                >
                    <article
                        v-for="payment in payments"
                        :key="payment.id"
                        class="px-5 py-5"
                    >
                        <div
                            class="flex flex-col gap-4 md:flex-row md:items-start"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <WalletCards class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">
                                            {{
                                                payment.receipt_number ||
                                                "Payment Received"
                                            }}
                                        </p>

                                        <div
                                            class="mt-2 flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="rounded-full px-2.5 py-1 text-[10px] font-bold"
                                                :class="
                                                    paymentModeClass(
                                                        payment.payment_mode,
                                                    )
                                                "
                                            >
                                                {{
                                                    labelize(
                                                        payment.payment_mode,
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="text-xs text-slate-400"
                                            >
                                                {{
                                                    formatDate(
                                                        payment.payment_date,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <p
                                        class="text-lg font-bold text-emerald-700"
                                    >
                                        {{ money(payment.amount) }}
                                    </p>
                                </div>

                                <div
                                    class="mt-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2"
                                >
                                    <div>
                                        <p class="text-xs text-slate-400">
                                            Transaction ID
                                        </p>

                                        <p
                                            class="mt-1 break-all text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                payment.transaction_id ||
                                                "—"
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-slate-400">
                                            Payment Date
                                        </p>

                                        <p
                                            class="mt-1 text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                formatDate(
                                                    payment.payment_date,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="payment.notes"
                                        class="sm:col-span-2"
                                    >
                                        <p class="text-xs text-slate-400">
                                            Notes
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-sm text-slate-700"
                                        >
                                            {{ payment.notes }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Payment proofs -->
                                <div
                                    v-if="payment.proofs?.length"
                                    class="mt-4"
                                >
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                    >
                                        Payment Proofs
                                    </p>

                                    <div
                                        class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <a
                                            v-for="proof in payment.proofs"
                                            :key="proof.id"
                                            :href="proofUrl(proof)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-indigo-300 hover:shadow-sm"
                                        >
                                            <div
                                                v-if="
                                                    proofIsImage(proof)
                                                "
                                                class="h-36 overflow-hidden bg-slate-100"
                                            >
                                                <img
                                                    :src="proofUrl(proof)"
                                                    :alt="
                                                        proof.original_name ||
                                                        'Payment proof'
                                                    "
                                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                                />
                                            </div>

                                            <div
                                                v-else
                                                class="flex h-36 items-center justify-center bg-slate-50"
                                            >
                                                <FileImage
                                                    class="h-10 w-10 text-slate-300"
                                                />
                                            </div>

                                            <div
                                                class="flex items-center gap-3 border-t border-slate-100 p-3"
                                            >
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                                >
                                                    <FileText
                                                        class="h-4 w-4"
                                                    />
                                                </div>

                                                <div
                                                    class="min-w-0 flex-1"
                                                >
                                                    <p
                                                        class="truncate text-xs font-semibold text-slate-700"
                                                    >
                                                        {{
                                                            proof.original_name ||
                                                            "Payment proof"
                                                        }}
                                                    </p>

                                                    <p
                                                        class="mt-0.5 text-[10px] text-slate-400"
                                                    >
                                                        Open proof
                                                    </p>
                                                </div>

                                                <ExternalLink
                                                    class="h-4 w-4 text-slate-400"
                                                />
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="px-5 py-14 text-center">
                    <WalletCards
                        class="mx-auto h-11 w-11 text-slate-300"
                    />

                    <h3 class="mt-3 text-sm font-bold text-slate-600">
                        No Payments Recorded
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Payments made against this invoice will appear
                        here.
                    </p>
                </div>
            </section>

            <!-- Invoice status timeline -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h2 class="text-base font-bold text-slate-900">
                    Invoice Timeline
                </h2>

                <div class="mt-5 space-y-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700"
                        >
                            <ReceiptText class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Invoice Generated
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ formatDateTime(invoice.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                        >
                            <CalendarDays class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Payment Due Date
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ formatDate(invoice.due_date) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-for="payment in [...payments].reverse()"
                        :key="`timeline-${payment.id}`"
                        class="flex items-start gap-3"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Payment of {{ money(payment.amount) }}
                                Received
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ formatDate(payment.payment_date) }}
                                ·
                                {{
                                    labelize(
                                        payment.payment_mode,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="invoice.status === 'paid'"
                        class="flex items-start gap-3"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                        >
                            <ShieldCheck class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Invoice Fully Paid
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                No outstanding balance remains.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>

<style scoped>
@media print {
    :global(body) {
        background: white !important;
    }

    :global(aside),
    :global(header) {
        display: none !important;
    }

    :global(.lg\:pl-72) {
        padding-left: 0 !important;
    }

    :global(main) {
        padding: 0 !important;
    }

    :global(main > div) {
        max-width: none !important;
    }
}
</style>
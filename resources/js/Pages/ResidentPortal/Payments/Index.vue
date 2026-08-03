<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    ArrowDownWideNarrow,
    CalendarDays,
    CreditCard,
    Eye,
    FileImage,
    ReceiptText,
    Search,
    SlidersHorizontal,
    WalletCards,
    X,
} from "lucide-vue-next";
import { reactive, ref, watch } from "vue";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },

    payments: {
        type: Object,
        required: true,
    },

    summary: {
        type: Object,
        default: () => ({}),
    },

    paymentModes: {
        type: Array,
        default: () => [],
    },
});

const filterPanelOpen = ref(false);

const form = reactive({
    search: props.filters?.search || "",
    payment_mode: props.filters?.payment_mode || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    sort: props.filters?.sort || "newest",
});

let searchTimer = null;

const applyFilters = () => {
    router.get(
        route("resident.payments.index"),
        {
            search: form.search || undefined,
            payment_mode: form.payment_mode || undefined,
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
            sort: form.sort && form.sort !== "newest" ? form.sort : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    form.search = "";
    form.payment_mode = "";
    form.date_from = "";
    form.date_to = "";
    form.sort = "newest";

    router.get(
        route("resident.payments.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => form.search,
    () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            if (form.search.length === 0 || form.search.length >= 3) {
                applyFilters();
            }
        }, 450);
    },
);

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

const labelize = (value) => {
    if (!value) return "—";

    return String(value)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const paymentModeClasses = {
    cash: "bg-emerald-100 text-emerald-700",
    card: "bg-indigo-100 text-indigo-700",
    razorpay: "bg-blue-100 text-blue-700",
    upi: "bg-purple-100 text-purple-700",
    bank_transfer: "bg-cyan-100 text-cyan-700",
    online: "bg-sky-100 text-sky-700",
};

const paymentModeClass = (mode) => {
    return paymentModeClasses[mode] || "bg-slate-100 text-slate-600";
};

const invoiceStatusClasses = {
    paid: "bg-emerald-100 text-emerald-700",
    unpaid: "bg-amber-100 text-amber-700",
    partial: "bg-blue-100 text-blue-700",
    overdue: "bg-red-100 text-red-700",
};

const invoiceStatusClass = (status) => {
    return invoiceStatusClasses[status] || "bg-slate-100 text-slate-600";
};
</script>

<template>
    <Head title="Payments" />

    <ResidentLayout title="Payments">
        <div class="space-y-6">
            <!-- Header -->
            <section
                class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold text-slate-900">
                        My Payments
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        View all payments recorded against your hostel invoices.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('resident.billing.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <ReceiptText class="h-4 w-4" />
                        Billing
                    </Link>
                </div>
            </section>

            <!-- Summary -->
            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Total Received
                            </p>

                            <p class="mt-2 text-2xl font-bold text-emerald-700">
                                {{ money(summary.total_received) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ summary.total_payments || 0 }}
                                payment records
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
                        >
                            <WalletCards class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Cash Payments
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ money(summary.cash_received) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Payments received in cash
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700"
                        >
                            <CreditCard class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Online Payments
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ money(summary.online_received) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                UPI, card, Razorpay and bank transfer
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"
                        >
                            <WalletCards class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                            >
                                This Month
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ money(summary.amount_this_month) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ summary.payments_this_month || 0 }}
                                payments this month
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filters and table -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between"
                >
                    <div class="relative w-full md:max-w-md">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="form.search"
                            type="text"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search receipt, transaction or invoice"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="form.sort"
                            class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="applyFilters"
                        >
                            <option value="newest">Newest First</option>

                            <option value="oldest">Oldest First</option>

                            <option value="amount_high">
                                Amount: High to Low
                            </option>

                            <option value="amount_low">
                                Amount: Low to High
                            </option>
                        </select>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="filterPanelOpen = !filterPanelOpen"
                        >
                            <SlidersHorizontal class="h-4 w-4" />
                            Filters
                        </button>

                        <button
                            v-if="
                                form.search ||
                                form.payment_mode ||
                                form.date_from ||
                                form.date_to ||
                                form.sort !== 'newest'
                            "
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                            @click="clearFilters"
                        >
                            <X class="h-4 w-4" />
                            Clear
                        </button>
                    </div>
                </div>

                <div
                    v-if="filterPanelOpen"
                    class="grid grid-cols-1 gap-4 border-b border-slate-100 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Payment Mode
                        </label>

                        <select
                            v-model="form.payment_mode"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Payment Modes</option>

                            <option
                                v-for="mode in paymentModes"
                                :key="mode"
                                :value="mode"
                            >
                                {{ labelize(mode) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Date From
                        </label>

                        <input
                            v-model="form.date_from"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Date To
                        </label>

                        <input
                            v-model="form.date_to"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            @click="applyFilters"
                        >
                            <ArrowDownWideNarrow class="h-4 w-4" />
                            Apply Filters
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-5 py-3 text-left">Payment</th>

                                <th class="px-5 py-3 text-left">Invoice</th>

                                <th class="px-5 py-3 text-left">
                                    Payment Date
                                </th>

                                <th class="px-5 py-3 text-left">Mode</th>

                                <th class="px-5 py-3 text-left">Transaction</th>

                                <th class="px-5 py-3 text-center">Proof</th>

                                <th class="px-5 py-3 text-right">Amount</th>

                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                                class="transition hover:bg-indigo-50/30"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                        >
                                            <WalletCards class="h-5 w-5" />
                                        </div>

                                        <div class="min-w-0">
                                            <Link
                                                :href="
                                                    route(
                                                        'resident.payments.show',
                                                        {
                                                            payment: payment.id,
                                                        },
                                                    )
                                                "
                                                class="truncate font-semibold text-slate-900 hover:text-indigo-700"
                                            >
                                                {{
                                                    payment.receipt_number ||
                                                    `Payment #${payment.id}`
                                                }}
                                            </Link>

                                            <p
                                                v-if="payment.notes"
                                                class="mt-0.5 max-w-xs truncate text-xs text-slate-400"
                                            >
                                                {{ payment.notes }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <template v-if="payment.invoice">
                                        <Link
                                            :href="
                                                route('resident.billing.show', {
                                                    invoice: payment.invoice.id,
                                                })
                                            "
                                            class="font-semibold text-indigo-600 hover:text-indigo-700"
                                        >
                                            {{ payment.invoice.invoice_number }}
                                        </Link>

                                        <p
                                            class="mt-1 max-w-xs truncate text-xs text-slate-400"
                                        >
                                            {{
                                                payment.invoice.description ||
                                                labelize(
                                                    payment.invoice.fee_type,
                                                )
                                            }}
                                        </p>

                                        <span
                                            class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                            :class="
                                                invoiceStatusClass(
                                                    payment.invoice.status,
                                                )
                                            "
                                        >
                                            {{
                                                labelize(payment.invoice.status)
                                            }}
                                        </span>
                                    </template>

                                    <span v-else class="text-xs text-slate-400">
                                        Invoice unavailable
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-medium text-slate-700">
                                        {{ formatDate(payment.payment_date) }}
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold"
                                        :class="
                                            paymentModeClass(
                                                payment.payment_mode,
                                            )
                                        "
                                    >
                                        {{ labelize(payment.payment_mode) }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <p
                                        class="max-w-40 break-all text-xs font-medium text-slate-600"
                                    >
                                        {{ payment.transaction_id || "—" }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <div
                                        v-if="payment.proofs?.length"
                                        class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700"
                                    >
                                        <FileImage class="h-3.5 w-3.5" />

                                        {{ payment.proofs.length }}
                                    </div>

                                    <span v-else class="text-xs text-slate-300">
                                        —
                                    </span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right text-base font-bold text-emerald-700"
                                >
                                    {{ money(payment.amount) }}
                                </td>

                                <td class="px-5 py-4">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'resident.payments.show',
                                                    {
                                                        payment: payment.id,
                                                    },
                                                )
                                            "
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                            title="View payment"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!payments.data?.length">
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <WalletCards
                                        class="mx-auto h-11 w-11 text-slate-300"
                                    />

                                    <p
                                        class="mt-3 text-sm font-semibold text-slate-600"
                                    >
                                        No payments found
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Try changing or clearing the current
                                        filters.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="payments.links?.length > 3"
                    class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-xs text-slate-500">
                        Showing
                        {{ payments.from || 0 }}
                        to
                        {{ payments.to || 0 }}
                        of
                        {{ payments.total || 0 }}
                        payments
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <template
                            v-for="link in payments.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                :class="
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-slate-600 hover:bg-slate-100'
                                "
                                preserve-scroll
                            />

                            <span
                                v-else
                                v-html="link.label"
                                class="cursor-not-allowed rounded-lg px-3 py-1.5 text-xs text-slate-300"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>
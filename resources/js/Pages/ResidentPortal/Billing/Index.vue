<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    ArrowDownWideNarrow,
    CalendarDays,
    CreditCard,
    Eye,
    FileDown,
    IndianRupee,
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
    invoices: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    feeTypes: {
        type: Array,
        default: () => [],
    },
    recentPayments: {
        type: Array,
        default: () => [],
    },
});

const filterPanelOpen = ref(false);

const form = reactive({
    search: props.filters?.search || "",
    status: props.filters?.status || "all",
    fee_type: props.filters?.fee_type || "",
    due_from: props.filters?.due_from || "",
    due_to: props.filters?.due_to || "",
    sort: props.filters?.sort || "newest",
});

let searchTimer = null;

const applyFilters = () => {
    router.get(
        route("resident.billing.index"),
        {
            search: form.search || undefined,
            status:
                form.status && form.status !== "all" ? form.status : undefined,
            fee_type: form.fee_type || undefined,
            due_from: form.due_from || undefined,
            due_to: form.due_to || undefined,
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
    form.status = "all";
    form.fee_type = "";
    form.due_from = "";
    form.due_to = "";
    form.sort = "newest";

    router.get(
        route("resident.billing.index"),
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
    });
};

const formatDate = (value) => {
    if (!value) return "—";
    const date = new Date(value);
    if (isNaN(date)) return "—"; // invalid date
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

const statusClasses = {
    paid: "bg-emerald-100 text-emerald-700",
    pending: "bg-amber-100 text-amber-700",
    partial: "bg-blue-100 text-blue-700",
    overdue: "bg-red-100 text-red-700",
    cancelled: "bg-slate-100 text-slate-600",
};

const statusClass = (status) => {
    return statusClasses[status] || "bg-slate-100 text-slate-600";
};

const balanceClass = (balance) => {
    return Number(balance || 0) > 0 ? "text-red-600" : "text-emerald-600";
};
</script>

<template>
    <Head title="Billing" />

    <ResidentLayout title="Billing">
        <div class="space-y-6">
            <!-- Page heading -->
            <section
                class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold text-slate-900">My Billing</h2>

                    <p class="mt-1 text-sm text-slate-500">
                        View invoices, outstanding amounts, due dates, payments,
                        and receipts.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('resident.payments.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <WalletCards class="h-4 w-4" />
                        Payments
                    </Link>

                </div>
            </section>

            <!-- Summary cards -->
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
                                Total Billed
                            </p>

                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ money(summary.total_billed) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ summary.total_invoices || 0 }}
                                total invoices
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"
                        >
                            <ReceiptText class="h-5 w-5" />
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
                                Total Paid
                            </p>

                            <p class="mt-2 text-2xl font-bold text-emerald-700">
                                {{ money(summary.total_paid) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ summary.paid_count || 0 }}
                                paid invoices
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
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
                                Outstanding
                            </p>

                            <p
                                class="mt-2 text-2xl font-bold"
                                :class="
                                    Number(summary.outstanding_amount) > 0
                                        ? 'text-red-700'
                                        : 'text-emerald-700'
                                "
                            >
                                {{ money(summary.outstanding_amount) }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    (summary.pending_count || 0) +
                                    (summary.partial_count || 0) +
                                    (summary.overdue_count || 0)
                                }}
                                open invoices
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-700"
                        >
                            <IndianRupee class="h-5 w-5" />
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
                                Next Due
                            </p>

                            <p class="mt-2 text-lg font-bold text-slate-900">
                                {{
                                    summary.next_due_invoice
                                        ? money(
                                              summary.next_due_invoice
                                                  .balance_amount,
                                          )
                                        : "No Due"
                                }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    summary.next_due_invoice
                                        ? `${summary.next_due_invoice.invoice_number} · ${formatDate(summary.next_due_invoice.due_date)}`
                                        : "No pending invoice"
                                }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick status filters -->
            <section
                class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <button
                    v-for="item in [
                        {
                            key: 'all',
                            label: 'All',
                            count: summary.total_invoices,
                        },
                        {
                            key: 'paid',
                            label: 'Paid',
                            count: summary.paid_count,
                        },
                        {
                            key: 'pending',
                            label: 'Pending',
                            count: summary.pending_count,
                        },
                        {
                            key: 'partial',
                            label: 'Partial',
                            count: summary.partial_count,
                        },
                        {
                            key: 'overdue',
                            label: 'Overdue',
                            count: summary.overdue_count,
                        },
                    ]"
                    :key="item.key"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        form.status === item.key
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700'
                    "
                    @click="
                        form.status = item.key;
                        applyFilters();
                    "
                >
                    {{ item.label }}

                    <span
                        class="rounded-full px-1.5 py-0.5 text-[10px]"
                        :class="
                            form.status === item.key
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-500'
                        "
                    >
                        {{ item.count || 0 }}
                    </span>
                </button>
            </section>

            <!-- Filters -->
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
                            placeholder="Search invoice number, fee type or description"
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
                            <option value="due_soon">Due Soon</option>
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
                                form.status !== 'all' ||
                                form.fee_type ||
                                form.due_from ||
                                form.due_to ||
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
                            Fee Type
                        </label>

                        <select
                            v-model="form.fee_type"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Fee Types</option>

                            <option
                                v-for="type in feeTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ labelize(type) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Due From
                        </label>

                        <input
                            v-model="form.due_from"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Due To
                        </label>

                        <input
                            v-model="form.due_to"
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

                <!-- Invoice table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-5 py-3 text-left">Invoice</th>

                                <th class="px-5 py-3 text-left">Fee Type</th>

                                <th class="px-5 py-3 text-left">Due Date</th>

                                <th class="px-5 py-3 text-right">Amount</th>

                                <th class="px-5 py-3 text-right">Paid</th>

                                <th class="px-5 py-3 text-right">Balance</th>

                                <th class="px-5 py-3 text-center">Status</th>

                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                class="transition hover:bg-indigo-50/30"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                        >
                                            <ReceiptText class="h-5 w-5" />
                                        </div>

                                        <div class="min-w-0">
                                            <Link
                                                :href="
                                                    route(
                                                        'resident.billing.show',
                                                        {
                                                            invoice: invoice.id,
                                                        },
                                                    )
                                                "
                                                class="truncate font-semibold text-slate-900 hover:text-indigo-700"
                                            >
                                                {{ invoice.invoice_number }}
                                            </Link>

                                            <p
                                                class="mt-0.5 max-w-xs truncate text-xs text-slate-400"
                                            >
                                                {{
                                                    invoice.description ||
                                                    "Hostel fee invoice"
                                                }}
                                            </p>

                                            <div
                                                class="mt-1 flex items-center gap-2 text-[10px] text-slate-400"
                                            >
                                                <span>
                                                    {{ invoice.items_count }}
                                                    item{{
                                                        invoice.items_count ===
                                                        1
                                                            ? ""
                                                            : "s"
                                                    }}
                                                </span>

                                                <span
                                                    v-if="
                                                        Number(
                                                            invoice.late_fee_amount,
                                                        ) > 0
                                                    "
                                                    class="rounded-full bg-red-50 px-2 py-0.5 font-semibold text-red-600"
                                                >
                                                    Late fee
                                                    {{
                                                        money(
                                                            invoice.late_fee_amount,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-slate-600">
                                    {{ labelize(invoice.fee_type) }}
                                </td>

                                <td class="px-5 py-4">
                                    <p
                                        class="font-medium"
                                        :class="
                                            invoice.status === 'overdue'
                                                ? 'text-red-600'
                                                : 'text-slate-700'
                                        "
                                    >
                                        {{ formatDate(invoice.due_date) }}
                                    </p>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900"
                                >
                                    {{ money(invoice.amount) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-medium text-emerald-700"
                                >
                                    {{ money(invoice.paid_amount) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-right font-bold"
                                    :class="
                                        balanceClass(invoice.balance_amount)
                                    "
                                >
                                    {{ money(invoice.balance_amount) }}
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold capitalize"
                                        :class="statusClass(invoice.status)"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route('resident.billing.show', {
                                                    invoice: invoice.id,
                                                })
                                            "
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                            title="View invoice"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>

                                        <a
                                            :href="
                                                route(
                                                    'resident.billing.pdf.en',
                                                    {
                                                        invoice: invoice.id,
                                                    },
                                                )
                                            " target="_blank"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                            title="Download invoice"
                                        >
                                            <FileDown class="h-4 w-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!invoices.data?.length">
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <ReceiptText
                                        class="mx-auto h-11 w-11 text-slate-300"
                                    />

                                    <p
                                        class="mt-3 text-sm font-semibold text-slate-600"
                                    >
                                        No invoices found
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
                    v-if="invoices.links?.length > 3"
                    class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-xs text-slate-500">
                        Showing
                        {{ invoices.from || 0 }}
                        to
                        {{ invoices.to || 0 }}
                        of
                        {{ invoices.total || 0 }}
                        invoices
                    </p>

                    <div class="flex flex-wrap items-center gap-1">
                        <template
                            v-for="link in invoices.links"
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

            <!-- Recent payments -->
            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                >
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Recent Payments
                        </h3>

                        <p class="text-xs text-slate-400">
                            Your latest recorded payments.
                        </p>
                    </div>

                    <Link
                        :href="route('resident.payments.index')"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                        View all
                    </Link>
                </div>

                <div
                    v-if="recentPayments?.length"
                    class="divide-y divide-slate-100"
                >
                    <div
                        v-for="payment in recentPayments"
                        :key="payment.id"
                        class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                        >
                            <WalletCards class="h-5 w-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-slate-800"
                            >
                                {{
                                    payment.receipt_number || "Payment received"
                                }}
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ payment.invoice_number || "Invoice" }}
                                ·
                                {{ formatDate(payment.payment_date) }}
                            </p>
                        </div>

                        <div class="sm:text-right">
                            <p class="text-sm font-bold text-emerald-700">
                                {{ money(payment.amount) }}
                            </p>

                            <p class="mt-0.5 text-xs capitalize text-slate-400">
                                {{ labelize(payment.payment_mode) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="px-5 py-12 text-center">
                    <WalletCards class="mx-auto h-9 w-9 text-slate-300" />

                    <p class="mt-2 text-sm font-medium text-slate-500">
                        No payment records found
                    </p>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>

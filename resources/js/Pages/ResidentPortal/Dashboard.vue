<script setup>
import StatCard from "@/Components/ResidentPortal/StatCard.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowRight,
    BedDouble,
    Building2,
    CalendarDays,
    CircleAlert,
    CreditCard,
    FileText,
    Home,
    IndianRupee,
    MessageSquareWarning,
    ReceiptText,
    Send,
    ShieldCheck,
    WalletCards,
} from "lucide-vue-next";

const props = defineProps({
    resident: Object,
    currentStay: Object,
    billingSummary: Object,
    recentInvoices: Array,
    recentPayments: Array,
    summaryCounts: Object,
});

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


const statusClass = (status) => {
    return {
        paid: "bg-emerald-100 text-emerald-700",
        unpaid: "bg-amber-100 text-amber-700",
        partial: "bg-blue-100 text-blue-700",
        overdue: "bg-red-100 text-red-700",
    }[status] ?? "bg-slate-100 text-slate-600";
};
</script>

<template>
    <Head title="Resident Dashboard" />

    <ResidentLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Welcome panel -->
            <section
                class="overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-700 to-indigo-500 p-6 text-white shadow-lg"
            >
                <div
                    class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p class="text-sm text-indigo-100">
                            Welcome back
                        </p>

                        <h2 class="mt-1 text-2xl font-bold">
                            {{ resident.name }}
                        </h2>

                        <div
                            class="mt-3 flex flex-wrap items-center gap-2 text-xs text-indigo-100"
                        >
                            <span
                                class="rounded-full bg-white/15 px-3 py-1"
                            >
                                {{ resident.resident_code }}
                            </span>

                            <span
                                v-if="resident.course"
                                class="rounded-full bg-white/15 px-3 py-1"
                            >
                                {{ resident.course }}
                            </span>

                            <span
                                v-if="resident.institute"
                                class="rounded-full bg-white/15 px-3 py-1"
                            >
                                {{ resident.institute }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="currentStay"
                        class="rounded-2xl bg-white/10 p-4 backdrop-blur"
                    >
                        <p class="text-xs text-indigo-100">
                            Current accommodation
                        </p>

                        <p class="mt-1 text-lg font-bold">
                            {{ currentStay.building || "—" }}
                        </p>

                        <p class="text-sm text-indigo-100">
                            Floor {{ currentStay.floor || "—" }}
                            · Room {{ currentStay.room || "—" }}
                            · Bed {{ currentStay.bed || "—" }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Stats -->
            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <StatCard
                    title="Current Room"
                    :value="
                        currentStay
                            ? `Room ${currentStay.room || '—'}`
                            : 'Not Assigned'
                    "
                    :subtitle="
                        currentStay
                            ? `${currentStay.building || '—'} · Bed ${currentStay.bed || '—'}`
                            : 'Contact hostel office'
                    "
                    tone="indigo"
                >
                    <template #icon>
                        <BedDouble class="h-5 w-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="Outstanding Balance"
                    :value="
                        money(
                            billingSummary?.outstanding_amount,
                        )
                    "
                    :subtitle="
                        billingSummary?.next_due_date
                            ? `Next due ${formatDate(billingSummary.next_due_date)}`
                            : 'No upcoming due'
                    "
                    :tone="
                        Number(
                            billingSummary?.outstanding_amount,
                        ) > 0
                            ? 'amber'
                            : 'emerald'
                    "
                >
                    <template #icon>
                        <IndianRupee class="h-5 w-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="Security Deposit"
                    :value="
                        money(currentStay?.deposit_amount)
                    "
                    subtitle="Refundable as per hostel policy"
                    tone="emerald"
                >
                    <template #icon>
                        <ShieldCheck class="h-5 w-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="Current Stay"
                    :value="
                        formatDate(
                            currentStay?.check_in_date,
                        )
                    "
                    :subtitle="
                        currentStay?.expected_check_out_date
                            ? `Until ${formatDate(currentStay.expected_check_out_date)}`
                            : 'No expected checkout date'
                    "
                    tone="blue"
                >
                    <template #icon>
                        <CalendarDays class="h-5 w-5" />
                    </template>
                </StatCard>
            </section>

            <!-- Quick actions -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Quick Actions
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Access frequently used resident services.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6"
                >
                    <Link
                        :href="route('resident.my-stay.index')"
                        class="group rounded-xl border border-slate-200 p-4 text-center transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:shadow-sm"
                    >
                        <Home
                            class="mx-auto h-6 w-6 text-indigo-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            My Stay
                        </p>
                    </Link>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <CalendarDays
                            class="mx-auto h-6 w-6 text-blue-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Apply Leave
                        </p>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <MessageSquareWarning
                            class="mx-auto h-6 w-6 text-amber-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Complaint
                        </p>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <Send
                            class="mx-auto h-6 w-6 text-purple-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            New Request
                        </p>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <CreditCard
                            class="mx-auto h-6 w-6 text-emerald-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Pay Bill
                        </p>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <FileText
                            class="mx-auto h-6 w-6 text-slate-600"
                        />

                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Documents
                        </p>
                    </button>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <!-- Recent invoices -->
                <section
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Recent Invoices
                            </h3>

                            <p class="text-xs text-slate-400">
                                Your latest hostel charges.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div
                        v-if="recentInvoices?.length"
                        class="divide-y divide-slate-100"
                    >
                        <div
                            v-for="invoice in recentInvoices"
                            :key="invoice.id"
                            class="flex items-center gap-3 px-5 py-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            >
                                <ReceiptText class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{
                                        invoice.description ||
                                        invoice.invoice_number
                                    }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{
                                        formatDate(
                                            invoice.due_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ money(invoice.amount) }}
                                </p>

                                <span
                                    class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                    :class="
                                        statusClass(
                                            invoice.status,
                                        )
                                    "
                                >
                                    {{ invoice.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="px-5 py-12 text-center"
                    >
                        <ReceiptText
                            class="mx-auto h-9 w-9 text-slate-300"
                        />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            No invoices found
                        </p>
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
                                Recently received payments.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div
                        v-if="recentPayments?.length"
                        class="divide-y divide-slate-100"
                    >
                        <div
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="flex items-center gap-3 px-5 py-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <WalletCards class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{
                                        payment.receipt_number ||
                                        "Payment received"
                                    }}
                                </p>

                                <p class="mt-0.5 text-xs capitalize text-slate-400">
                                    {{
                                        payment.payment_mode
                                    }}
                                    ·
                                    {{
                                        formatDate(
                                            payment.payment_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <p class="text-sm font-bold text-emerald-700">
                                {{ money(payment.amount) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else
                        class="px-5 py-12 text-center"
                    >
                        <WalletCards
                            class="mx-auto h-9 w-9 text-slate-300"
                        />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            No payments found
                        </p>
                    </div>
                </section>
            </div>

            <!-- Service summary -->
            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <CalendarDays class="h-5 w-5 text-blue-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.pending_leaves ?? 0 }}
                    </p>

                    <p class="text-xs text-slate-500">
                        Pending leave requests
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <MessageSquareWarning
                        class="h-5 w-5 text-amber-600"
                    />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.open_complaints ?? 0 }}
                    </p>

                    <p class="text-xs text-slate-500">
                        Open complaints
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <Send class="h-5 w-5 text-purple-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.pending_requests ?? 0 }}
                    </p>

                    <p class="text-xs text-slate-500">
                        Pending requests
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <CircleAlert class="h-5 w-5 text-red-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.unread_notices ?? 0 }}
                    </p>

                    <p class="text-xs text-slate-500">
                        Unread notices
                    </p>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>
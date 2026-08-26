<script setup>
import StatCard from "@/Components/ResidentPortal/StatCard.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowRight,
    BedDouble,
    Bike,
    Building2,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Coffee,
    Cookie,
    CreditCard,
    FileText,
    FolderOpen,
    Home,
    IndianRupee,
    KeyRound,
    Megaphone,
    MessageSquareWarning,
    Moon,
    ReceiptText,
    Send,
    ShieldCheck,
    Siren,
    Soup,
    UtensilsCrossed,
    WalletCards,
} from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
    resident: Object,
    currentStay: Object,
    billingSummary: Object,
    recentInvoices: Array,
    recentPayments: Array,
    summaryCounts: Object,
    leaveSummary: Object,
    latestComplaint: Object,
    latestRoomRequest: Object,
    activeEmergency: Object,
    recentNotices: Array,
    todayMenus: Array,
    kycSummary: Object,
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

    if (Number.isNaN(date.getTime())) {
        return "—";
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
        return "—";
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const humanize = (value) => {
    return String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const statusClass = (status) => {
    return (
        {
            paid: "bg-emerald-100 text-emerald-700",
            unpaid: "bg-amber-100 text-amber-700",
            partial: "bg-blue-100 text-blue-700",
            overdue: "bg-red-100 text-red-700",
            pending: "bg-amber-100 text-amber-700",
            parent_approval_pending: "bg-orange-100 text-orange-700",
            approved: "bg-emerald-100 text-emerald-700",
            rejected: "bg-red-100 text-red-700",
            cancelled: "bg-slate-100 text-slate-600",
            open: "bg-blue-100 text-blue-700",
            in_progress: "bg-amber-100 text-amber-700",
            escalated: "bg-red-100 text-red-700",
            resolved: "bg-emerald-100 text-emerald-700",
        }[status] ?? "bg-slate-100 text-slate-600"
    );
};

const noticePriorityClass = {
    normal: "border-blue-200 bg-blue-50 text-blue-700",
    important: "border-amber-200 bg-amber-50 text-amber-700",
    urgent: "border-red-200 bg-red-50 text-red-700",
};

const kycClass = computed(() => {
    return (
        {
            complete: "border-emerald-200 bg-emerald-50 text-emerald-700",
            pending_verification: "border-amber-200 bg-amber-50 text-amber-700",
            incomplete: "border-red-200 bg-red-50 text-red-700",
        }[props.kycSummary?.status] ??
        "border-slate-200 bg-slate-50 text-slate-700"
    );
});

const meals = [
    {
        key: "breakfast",
        label: "प्रातराश",
        icon: Coffee,
    },
    {
        key: "lunch",
        label: "भोजन",
        icon: Soup,
    },
    {
        key: "snacks",
        label: "स्वल्पाहार",
        icon: Cookie,
    },
    {
        key: "dinner",
        label: "संध्याकालीन भोज",
        icon: Moon,
    },
];

const menuFor = (mealType) => {
    return props.todayMenus?.find((menu) => menu.meal_type === mealType);
};

const splitItems = (items) => {
    return String(items || "")
        .split(/\n|,/)
        .map((item) => item.trim())
        .filter(Boolean);
};

const hasUrgentAttention = computed(() => {
    return Boolean(
        props.activeEmergency ||
        Number(props.summaryCounts?.acknowledgements_pending || 0) > 0 ||
        props.resident?.must_change_password,
    );
});
</script>

<template>
    <Head title="Resident Dashboard" />

    <ResidentLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Welcome -->
            <section
                class="overflow-hidden rounded-3xl border border-indigo-200 bg-[linear-gradient(135deg,#1e1b4b_0%,#4338ca_52%,#6366f1_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <p class="text-sm font-medium text-white">
                            Welcome back
                        </p>

                        <h2
                            class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                        >
                            {{ resident.name }}
                        </h2>

                        <div
                            class="mt-4 flex flex-wrap items-center gap-2 text-xs"
                        >
                            <span
                                class="rounded-full border border-white/20 bg-black/10 px-3 py-1.5 font-semibold text-white"
                            >
                                {{ resident.resident_code }}
                            </span>

                            <span
                                v-if="resident.course"
                                class="rounded-full border border-white/20 bg-black/10 px-3 py-1.5 font-semibold text-white"
                            >
                                {{ resident.course }}
                            </span>

                            <span
                                v-if="resident.institute"
                                class="rounded-full border border-white/20 bg-black/10 px-3 py-1.5 font-semibold text-white"
                            >
                                {{ resident.institute }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="currentStay"
                        class="rounded-2xl border border-white/20 bg-black/10 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-white"
                        >
                            Current Accommodation
                        </p>

                        <p class="mt-2 text-lg font-bold text-white">
                            {{ currentStay.building || "—" }}
                        </p>

                        <p class="mt-1 text-sm text-white">
                            Floor {{ currentStay.floor || "—" }} · Room
                            {{ currentStay.room || "—" }} · Bed
                            {{ currentStay.bed || "—" }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Attention alerts -->
            <section v-if="hasUrgentAttention" class="space-y-3">
                <Link
                    v-if="activeEmergency"
                    :href="route('resident.emergency.index')"
                    class="flex items-start gap-3 rounded-2xl border border-red-300 bg-red-50 p-5"
                >
                    <Siren class="mt-0.5 h-6 w-6 shrink-0 text-red-700" />

                    <div class="flex-1">
                        <p class="text-sm font-bold text-red-900">
                            Emergency alert is active
                        </p>

                        <p class="mt-1 text-xs leading-5 text-red-700">
                            {{ humanize(activeEmergency.category) }}
                            ·
                            {{
                                activeEmergency.status === "escalated"
                                    ? "Escalated"
                                    : "Awaiting response"
                            }}
                        </p>
                    </div>

                    <ArrowRight class="h-5 w-5 text-red-600" />
                </Link>

                <Link
                    v-if="
                        Number(summaryCounts?.acknowledgements_pending || 0) > 0
                    "
                    :href="route('resident.notices.index')"
                    class="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-5"
                >
                    <Megaphone class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-900">
                            Notice acknowledgement required
                        </p>

                        <p class="mt-1 text-xs text-amber-700">
                            {{ summaryCounts.acknowledgements_pending }}
                            notice{{
                                Number(
                                    summaryCounts.acknowledgements_pending,
                                ) === 1
                                    ? ""
                                    : "s"
                            }}
                            awaiting confirmation.
                        </p>
                    </div>

                    <ArrowRight class="h-5 w-5 text-amber-600" />
                </Link>

                <Link
                    v-if="resident.must_change_password"
                    :href="route('resident.profile.index')"
                    class="flex items-start gap-3 rounded-2xl border border-blue-300 bg-blue-50 p-5"
                >
                    <KeyRound class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                    <div class="flex-1">
                        <p class="text-sm font-bold text-blue-900">
                            Change your temporary password
                        </p>

                        <p class="mt-1 text-xs text-blue-700">
                            Secure your portal account by creating a private
                            password.
                        </p>
                    </div>

                    <ArrowRight class="h-5 w-5 text-blue-600" />
                </Link>
            </section>

            <!-- Main stats -->
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
                    :value="money(billingSummary?.outstanding_amount)"
                    :subtitle="
                        billingSummary?.next_due_date
                            ? `Next due ${formatDate(
                                  billingSummary.next_due_date,
                              )}`
                            : 'No upcoming due'
                    "
                    :tone="
                        Number(billingSummary?.outstanding_amount) > 0
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
                    :value="money(currentStay?.deposit_amount)"
                    subtitle="As recorded for the current stay"
                    tone="emerald"
                >
                    <template #icon>
                        <ShieldCheck class="h-5 w-5" />
                    </template>
                </StatCard>

                <StatCard
                    title="KYC Progress"
                    :value="`${kycSummary?.percentage || 0}%`"
                    :subtitle="`${kycSummary?.verified || 0} of ${
                        kycSummary?.required || 0
                    } verified`"
                    :tone="
                        kycSummary?.status === 'complete'
                            ? 'emerald'
                            : kycSummary?.status === 'incomplete'
                              ? 'amber'
                              : 'blue'
                    "
                >
                    <template #icon>
                        <FileText class="h-5 w-5" />
                    </template>
                </StatCard>
            </section>

            <!-- Quick actions -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        Quick Actions
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500">
                        Access frequently used resident services.
                    </p>
                </div>

                <div
                    class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-8"
                >
                    <Link
                        :href="route('resident.my-stay.index')"
                        class="group rounded-xl border border-slate-200 p-4 text-center transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50"
                    >
                        <Home class="mx-auto h-6 w-6 text-indigo-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            My Stay
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.leaves.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-blue-50"
                    >
                        <CalendarDays class="mx-auto h-6 w-6 text-blue-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Apply Leave
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.complaints.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-amber-50"
                    >
                        <MessageSquareWarning
                            class="mx-auto h-6 w-6 text-amber-600"
                        />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Complaint
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.room-change-requests.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-purple-50"
                    >
                        <Send class="mx-auto h-6 w-6 text-purple-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Room Request
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.emergency.index')"
                        class="rounded-xl border border-red-200 p-4 text-center transition hover:bg-red-50"
                    >
                        <Siren class="mx-auto h-6 w-6 text-red-600" />
                        <p class="mt-2 text-xs font-semibold text-red-700">
                            Emergency
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.billing.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-emerald-50"
                    >
                        <CreditCard class="mx-auto h-6 w-6 text-emerald-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Billing
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.documents.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-cyan-50"
                    >
                        <FolderOpen class="mx-auto h-6 w-6 text-cyan-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Documents
                        </p>
                    </Link>

                    <Link
                        :href="route('resident.vehicles.index')"
                        class="rounded-xl border border-slate-200 p-4 text-center transition hover:bg-slate-50"
                    >
                        <Bike class="mx-auto h-6 w-6 text-slate-600" />
                        <p class="mt-2 text-xs font-semibold text-slate-700">
                            Vehicles
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Today menu and notices -->
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h3
                                class="flex items-center gap-2 text-sm font-bold text-slate-900"
                            >
                                <UtensilsCrossed
                                    class="h-4 w-4 text-orange-600"
                                />
                                Today's Mess Menu
                            </h3>

                            <p class="text-xs text-slate-400">
                                Meals planned for today.
                            </p>
                        </div>

                        <Link
                            :href="route('resident.mess-menu.index')"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            Weekly menu
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div
                        v-if="todayMenus?.length"
                        class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2"
                    >
                        <div
                            v-for="meal in meals"
                            :key="meal.key"
                            class="rounded-xl border border-slate-200 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <component
                                    :is="meal.icon"
                                    class="h-4 w-4 text-orange-600"
                                />

                                <p class="text-xs font-bold text-slate-900">
                                    {{ meal.label }}
                                </p>
                            </div>

                            <template v-if="menuFor(meal.key)">
                                <p
                                    class="mt-2 text-xs leading-5 text-slate-600"
                                >
                                    {{
                                        splitItems(
                                            menuFor(meal.key).items,
                                        ).join(", ")
                                    }}
                                </p>

                                <p
                                    v-if="menuFor(meal.key).special_notes"
                                    class="mt-2 text-[10px] text-amber-700"
                                >
                                    {{ menuFor(meal.key).special_notes }}
                                </p>
                            </template>

                            <p v-else class="mt-2 text-xs text-slate-300">
                                Not added
                            </p>
                        </div>
                    </div>

                    <div v-else class="px-5 py-12 text-center">
                        <UtensilsCrossed
                            class="mx-auto h-9 w-9 text-slate-300"
                        />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            Today's menu is not available
                        </p>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                    >
                        <div>
                            <h3
                                class="flex items-center gap-2 text-sm font-bold text-slate-900"
                            >
                                <Megaphone class="h-4 w-4 text-indigo-600" />
                                Latest Notices
                            </h3>

                            <p class="text-xs text-slate-400">
                                {{ summaryCounts?.unread_notices || 0 }}
                                unread notice{{
                                    Number(
                                        summaryCounts?.unread_notices || 0,
                                    ) === 1
                                        ? ""
                                        : "s"
                                }}
                            </p>
                        </div>

                        <Link
                            :href="route('resident.notices.index')"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div
                        v-if="recentNotices?.length"
                        class="divide-y divide-slate-100"
                    >
                        <Link
                            v-for="notice in recentNotices"
                            :key="notice.id"
                            :href="
                                route('resident.notices.show', {
                                    notice: notice.id,
                                })
                            "
                            class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50"
                        >
                            <div
                                class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                                :class="
                                    notice.is_read
                                        ? 'bg-slate-200'
                                        : 'bg-indigo-600'
                                "
                            ></div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-800"
                                    >
                                        {{ notice.title }}
                                    </p>

                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[9px] font-bold capitalize"
                                        :class="
                                            noticePriorityClass[notice.priority]
                                        "
                                    >
                                        {{ notice.priority }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500"
                                >
                                    {{
                                        notice.summary ||
                                        "Open the notice to view complete details."
                                    }}
                                </p>

                                <p class="mt-2 text-[10px] text-slate-400">
                                    {{ formatDateTime(notice.published_at) }}
                                </p>
                            </div>

                            <ArrowRight
                                class="mt-1 h-4 w-4 shrink-0 text-slate-300"
                            />
                        </Link>
                    </div>

                    <div v-else class="px-5 py-12 text-center">
                        <Megaphone class="mx-auto h-9 w-9 text-slate-300" />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            No notices available
                        </p>
                    </div>
                </section>
            </div>

            <!-- Billing lists -->
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
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

                        <Link
                            :href="route('resident.billing.index')"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
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
                                <p
                                    class="truncate text-sm font-semibold text-slate-800"
                                >
                                    {{
                                        invoice.description ||
                                        invoice.invoice_number
                                    }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    Due
                                    {{ formatDate(invoice.due_date) }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ money(invoice.amount) }}
                                </p>

                                <span
                                    class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                    :class="statusClass(invoice.status)"
                                >
                                    {{ invoice.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="px-5 py-12 text-center">
                        <ReceiptText class="mx-auto h-9 w-9 text-slate-300" />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            No invoices found
                        </p>
                    </div>
                </section>

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

                        <Link
                            :href="route('resident.payments.index')"
                            class="flex items-center gap-1 text-xs font-semibold text-indigo-600"
                        >
                            View payments
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
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
                                <p
                                    class="truncate text-sm font-semibold text-slate-800"
                                >
                                    {{
                                        payment.receipt_number ||
                                        "Payment received"
                                    }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs capitalize text-slate-400"
                                >
                                    {{ payment.payment_mode }}
                                    ·
                                    {{ formatDate(payment.payment_date) }}
                                </p>
                            </div>

                            <p class="text-sm font-bold text-emerald-700">
                                {{ money(payment.amount) }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="px-5 py-12 text-center">
                        <WalletCards class="mx-auto h-9 w-9 text-slate-300" />

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            No payments found
                        </p>
                    </div>
                </section>
            </div>

            <!-- Service status -->
            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <Link
                    :href="route('resident.leaves.index')"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:bg-blue-50"
                >
                    <div class="flex items-start justify-between">
                        <CalendarDays class="h-5 w-5 text-blue-600" />

                        <span
                            v-if="leaveSummary?.latest"
                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="
                                statusClass(leaveSummary.latest.final_status)
                            "
                        >
                            {{ humanize(leaveSummary.latest.final_status) }}
                        </span>
                    </div>

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.pending_leaves || 0 }}
                    </p>

                    <p class="text-xs text-slate-500">Pending leave requests</p>
                </Link>

                <Link
                    :href="route('resident.complaints.index')"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:bg-amber-50"
                >
                    <div class="flex items-start justify-between">
                        <MessageSquareWarning class="h-5 w-5 text-amber-600" />

                        <span
                            v-if="latestComplaint"
                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="statusClass(latestComplaint.status)"
                        >
                            {{ humanize(latestComplaint.status) }}
                        </span>
                    </div>

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.open_complaints || 0 }}
                    </p>

                    <p class="text-xs text-slate-500">Open complaints</p>
                </Link>

                <Link
                    :href="route('resident.room-change-requests.index')"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-purple-200 hover:bg-purple-50"
                >
                    <Send class="h-5 w-5 text-purple-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.pending_requests || 0 }}
                    </p>

                    <p class="text-xs text-slate-500">Pending room requests</p>
                </Link>

                <Link
                    :href="route('resident.notices.index')"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50"
                >
                    <CircleAlert class="h-5 w-5 text-indigo-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ summaryCounts?.unread_notices || 0 }}
                    </p>

                    <p class="text-xs text-slate-500">Unread notices</p>
                </Link>
            </section>

            <!-- KYC -->
            <Link
                :href="route('resident.documents.index')"
                class="block rounded-2xl border p-5"
                :class="kycClass"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            v-if="kycSummary?.status === 'complete'"
                            class="mt-0.5 h-6 w-6 shrink-0"
                        />

                        <Clock3
                            v-else-if="
                                kycSummary?.status === 'pending_verification'
                            "
                            class="mt-0.5 h-6 w-6 shrink-0"
                        />

                        <AlertTriangle v-else class="mt-0.5 h-6 w-6 shrink-0" />

                        <div>
                            <p class="text-sm font-bold">
                                KYC:
                                {{ humanize(kycSummary?.status) }}
                            </p>

                            <p class="mt-1 text-xs">
                                {{ kycSummary?.verified || 0 }}
                                of
                                {{ kycSummary?.required || 0 }}
                                required documents verified.
                                <template
                                    v-if="Number(kycSummary?.missing || 0) > 0"
                                >
                                    {{ kycSummary.missing }}
                                    document{{
                                        Number(kycSummary.missing) === 1
                                            ? ""
                                            : "s"
                                    }}
                                    still missing.
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-sm font-semibold">
                        Manage Documents
                        <ArrowRight class="h-4 w-4" />
                    </div>
                </div>
            </Link>
        </div>
    </ResidentLayout>
</template>
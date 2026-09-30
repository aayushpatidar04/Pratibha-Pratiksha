<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import {
    Building2,
    BedDouble,
    Users,
    Wallet,
    MessageSquareWarning,
    CalendarDays,
    TrendingUp,
    Download,
    ChevronRight,
    AlertTriangle,
    FileText,
    Clock,
    IndianRupee,
    Receipt,
    XCircle,
    CheckCircle,
    LogOut,
    CalendarCheck,
    ClipboardList,
    Siren,
    ClipboardCheck,
    UserCheck,
    Gavel,
    Ticket,
    Inbox,
} from "lucide-vue-next";

const props = defineProps({
    stats: Object,
    occupancyTrend: Array,
    recentActivity: Array,
    sessionBilling: Object,
    latestComplaints: Array,
    latestLeaves: Array,
    latestCheckouts: Array,
    latestRoomChanges: Array,
    latestNotices: Array,
    latestEmergencies: Array,
    latestApplications: Array,
    misReports: Array,
});

const occupancyRate = () => {
    const cap = props.stats.rooms.totalCapacity || 0;
    const occ = props.stats.rooms.occupiedBeds || 0;
    return cap ? Math.round((occ / cap) * 100) : 0;
};

const cards = [
    {
        label: "Total Buildings",
        value: () => props.stats.buildings.total,
        sub: () => `${props.stats.buildings.active} active`,
        icon: Building2,
        color: "blue",
    },
    {
        label: "Active Residents",
        value: () => props.stats.residents.active,
        sub: () => `${props.stats.residents.total} total`,
        icon: Users,
        color: "green",
    },
    {
        label: "Occupancy Rate",
        value: () => occupancyRate() + "%",
        sub: () => "of total capacity",
        icon: TrendingUp,
        color: "amber",
    },
    {
        label: "Vacant Beds",
        value: () => props.stats.beds.vacant,
        sub: () => `of ${props.stats.beds.total} total`,
        icon: BedDouble,
        color: "green",
    },
    {
        label: "Fee Collection",
        value: () =>
            "₹" +
            Number(props.stats.fees.paidAmount || 0).toLocaleString("en-IN"),
        sub: () => `${props.stats.fees.pending} pending invoices`,
        icon: Wallet,
        color: "blue",
    },
    {
        label: "Open Complaints",
        value: () => props.stats.complaints.open,
        sub: () => `${props.stats.complaints.resolved} resolved`,
        icon: MessageSquareWarning,
        color: "red",
    },
    {
        label: "Pending Leaves",
        value: () => props.stats.leaves.pending,
        sub: () => `${props.stats.leaves.approved} approved`,
        icon: CalendarDays,
        color: "purple",
    },
    {
        label: "Pending Checkouts",
        value: () => props.stats.checkouts.pending,
        sub: () => `${props.stats.checkouts.readyForExit} ready for exit`,
        icon: LogOut,
        color: "amber",
    },
    {
        label: "Room Changes",
        value: () => props.stats.roomChanges.pending,
        sub: () => `${props.stats.roomChanges.approved} approved`,
        icon: ClipboardList,
        color: "indigo",
    },
    {
        label: "Active Notices",
        value: () => props.stats.notices.published,
        sub: () =>
            props.stats.notices.requiresAck > 0
                ? props.stats.notices.requiresAck + " need acknowledgement"
                : "no ack required",
        icon: Inbox,
        color: "blue",
    },
    {
        label: "Emergency Alerts",
        value: () => props.stats.emergencies.active,
        sub: () =>
            props.stats.emergencies.escalated > 0
                ? props.stats.emergencies.escalated + " escalated"
                : "all clear",
        icon: Siren,
        color: "red",
    },
    {
        label: "Applications",
        value: () => props.stats.applications.pending,
        sub: () => `${props.stats.applications.approved} approved`,
        icon: UserCheck,
        color: "teal",
    },
];

const colorClasses = {
    blue: "bg-blue-50 text-blue-600",
    purple: "bg-purple-50 text-purple-600",
    green: "bg-green-50 text-green-600",
    amber: "bg-amber-50 text-amber-600",
    red: "bg-red-50 text-red-600",
    indigo: "bg-indigo-50 text-indigo-600",
    teal: "bg-teal-50 text-teal-600",
};

const maxTrend = () => Math.max(...props.occupancyTrend.map((m) => m.total), 1);

const statusColors = {
    open: "bg-red-100 text-red-700",
    in_progress: "bg-amber-100 text-amber-700",
    pending: "bg-amber-100 text-amber-700",
    parent_approval_pending: "bg-amber-100 text-amber-700",
    approved: "bg-green-100 text-green-700",
    resolved: "bg-green-100 text-green-700",
    under_admin_review: "bg-amber-100 text-amber-700",
    assigned_to_warden: "bg-blue-100 text-blue-700",
    warden_review_in_progress: "bg-purple-100 text-purple-700",
    warden_approved: "bg-green-100 text-green-700",
    ready_for_exit: "bg-teal-100 text-teal-700",
    completed: "bg-gray-100 text-gray-700",
    active: "bg-red-100 text-red-700",
    escalated: "bg-orange-100 text-orange-700",
    published: "bg-green-100 text-green-700",
    rejected: "bg-red-100 text-red-700",
    cancelled: "bg-gray-100 text-gray-700",
};

const formatCurrency = (amount) => {
    return (
        "₹" +
        Number(amount || 0).toLocaleString("en-IN", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const activityIcon = (icon) => {
    const map = {
        Users,
        LogOut,
        CheckCircle,
        CalendarCheck,
        ClipboardList,
        Siren,
        Ticket,
    };
    return map[icon] || Users;
};

const activityColor = (color) => {
    const map = {
        green: "bg-green-100 text-green-600",
        blue: "bg-blue-100 text-blue-600",
        purple: "bg-purple-100 text-purple-600",
        red: "bg-red-100 text-red-600",
        amber: "bg-amber-100 text-amber-600",
    };
    return map[color] || "bg-gray-100 text-gray-600";
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>Dashboard</template>

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-700 mt-0.5">
                    Real-time overview of all your hostels
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="card in cards"
                    :key="card.label"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-700">
                                {{ card.label }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ card.value() }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ card.sub() }}
                            </p>
                        </div>
                        <div
                            class="h-9 w-9 rounded-lg flex items-center justify-center"
                            :class="colorClasses[card.color]"
                        >
                            <component :is="card.icon" class="h-4.5 w-4.5" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 1: Occupancy Trend + Session Billing -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Occupancy Trend -->
                <div
                    class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">
                        Occupancy Trend
                    </h2>
                    <div class="flex items-end gap-2 h-40">
                        <div
                            v-for="m in occupancyTrend"
                            :key="m.month"
                            class="flex-1 flex flex-col items-center gap-1"
                        >
                            <div
                                class="w-full flex flex-col justify-end h-32 gap-0.5"
                            >
                                <div
                                    class="w-full bg-blue-500 rounded-t"
                                    :style="{
                                        height:
                                            (m.occupied / maxTrend()) *
                                            100 +
                                            '%',
                                    }"
                                />
                            </div>
                            <span class="text-[10px] text-gray-600">{{
                                m.month
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Session-wise Billing -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900">
                            Collection
                        </h2>
                        <span class="text-xs font-medium text-gray-700"
                            >{{ sessionBilling?.collectionRate ?? 0 }}%</span
                        >
                    </div>

                    <!-- Donut Chart -->
                    <div class="relative w-28 h-28 mx-auto mb-4">
                        <svg
                            class="w-full h-full -rotate-90"
                            viewBox="0 0 36 36"
                        >
                            <path
                                class="text-gray-100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                            />
                            <path
                                class="text-green-500"
                                :stroke-dasharray="`${sessionBilling?.collectionRate ?? 0}, 100`"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex items-center justify-center"
                        >
                            <span class="text-lg font-bold text-gray-800"
                                >{{
                                    sessionBilling?.collectionRate ?? 0
                                }}%</span
                            >
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Session Name</span>
                            <span
                                class="font-medium text-gray-900 text-right"
                                >{{ sessionBilling?.name ?? "-" }}</span
                            >
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Total Amount</span>
                            <span class="font-semibold text-gray-900">{{
                                formatCurrency(sessionBilling?.totalAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Total Paid</span>
                            <span class="font-semibold text-green-600">{{
                                formatCurrency(sessionBilling?.paidAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Total Pending</span>
                            <span class="font-semibold text-red-600">{{
                                formatCurrency(sessionBilling?.pendingAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Total Refund</span>
                            <span class="font-semibold text-gray-900">{{
                                formatCurrency(sessionBilling?.refundAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">Bills Processed</span>
                            <span class="font-semibold text-gray-900"
                                >{{ sessionBilling?.billsProcessed ?? 0 }}/{{
                                    sessionBilling?.totalBills ?? 0
                                }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Complaints + Checkout Requests -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Open Complaints -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <AlertTriangle class="w-4 h-4 text-red-500" />
                            Open Complaints
                        </h2>
                        <a
                            href="support/complaints"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestComplaints?.length"
                            v-for="complaint in latestComplaints"
                            :key="complaint.id"
                            class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0"
                                >
                                    <MessageSquareWarning
                                        class="h-4 w-4 text-gray-700"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ complaint.category }}
                                    </p>
                                    <p class="text-xs text-gray-700">
                                        {{ complaint.residentName }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-600 mt-0.5 line-clamp-1"
                                    >
                                        {{ complaint.description }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wide flex-shrink-0 ml-2"
                                :class="statusColors[complaint.status]"
                            >
                                {{ complaint.status.replace("_", " ") }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No open complaints
                        </div>
                    </div>
                </div>

                <!-- Checkout Requests -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <LogOut class="w-4 h-4 text-amber-500" />
                            Checkout Requests
                        </h2>
                        <a
                            href="checkout-requests"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestCheckouts?.length"
                            v-for="checkout in latestCheckouts"
                            :key="checkout.id"
                            class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="h-8 w-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0"
                                >
                                    <LogOut
                                        class="h-4 w-4 text-amber-600"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ checkout.residentName }}
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Checkout request
                                    </p>
                                    <p
                                        class="text-xs text-gray-600 mt-0.5"
                                    >
                                        {{ formatDate(checkout.createdAt) }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wide flex-shrink-0 ml-2"
                                :class="statusColors[checkout.status]"
                            >
                                {{
                                    checkout.status.replace(/_/g, " ")
                                }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No pending checkout requests
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Leaves + Room Changes + Notices -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Leave Requests -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <CalendarDays class="w-4 h-4 text-purple-500" />
                            Leave Requests
                        </h2>
                        <a
                            href="support/leaves"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestLeaves?.length"
                            v-for="leave in latestLeaves"
                            :key="leave.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div
                                class="h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0"
                            >
                                <Users
                                    class="h-4 w-4 text-purple-600"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ leave.residentName }}
                                    </p>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase flex-shrink-0 ml-2"
                                        :class="statusColors[leave.status]"
                                    >
                                        {{ leave.status.replace("_", " ") }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-gray-700 mt-0.5 line-clamp-1"
                                >
                                    {{ leave.reason }}
                                </p>
                                <div
                                    class="flex items-center gap-1 mt-1.5 text-xs text-gray-600"
                                >
                                    <Clock class="w-3 h-3" />
                                    <span
                                        >{{ formatDate(leave.fromDate) }} -
                                        {{ formatDate(leave.toDate) }}</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No pending leave requests
                        </div>
                    </div>
                </div>

                <!-- Room Change Requests -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <ClipboardList
                                class="w-4 h-4 text-indigo-500"
                            />
                            Room Change Requests
                        </h2>
                        <a
                            href="residents/room-change-requests"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestRoomChanges?.length"
                            v-for="change in latestRoomChanges"
                            :key="change.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div
                                class="h-8 w-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0"
                            >
                                <ClipboardList
                                    class="h-4 w-4 text-indigo-600"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-900 truncate"
                                >
                                    {{ change.residentName }}
                                </p>
                                <p
                                    class="text-xs text-gray-700 mt-0.5 line-clamp-1"
                                >
                                    {{ change.reason }}
                                </p>
                                <p
                                    class="text-xs text-gray-600 mt-0.5"
                                >
                                    {{ formatDate(change.createdAt) }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase flex-shrink-0 ml-2"
                                :class="statusColors[change.status]"
                            >
                                {{ change.status }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No pending room changes
                        </div>
                    </div>
                </div>

                <!-- Notices -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <Inbox class="w-4 h-4 text-blue-500" />
                            Published Notices
                        </h2>
                        <a
                            href="notices"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestNotices?.length"
                            v-for="notice in latestNotices"
                            :key="notice.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div
                                class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0"
                            >
                                <Inbox
                                    class="h-4 w-4 text-blue-600"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ notice.title }}
                                    </p>
                                    <span
                                        v-if="notice.requiresAck"
                                        class="px-1.5 py-0.5 rounded text-[9px] font-medium bg-amber-100 text-amber-700"
                                    >
                                        ACK
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-gray-700 capitalize mt-0.5"
                                >
                                    {{ notice.category }}
                                </p>
                                <p
                                    class="text-xs text-gray-600 mt-0.5"
                                >
                                    {{ formatDate(notice.publishedAt) }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase flex-shrink-0 ml-2"
                                :class="statusColors[notice.priority] || 'bg-gray-100 text-gray-700'"
                            >
                                {{ notice.priority }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No published notices
                        </div>
                    </div>
                </div>
            
                <!-- Emergency Alerts -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <Siren class="w-4 h-4 text-red-500" />
                            Emergency Alerts
                        </h2>
                        <a
                            href="support/emergency"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestEmergencies?.length"
                            v-for="alert in latestEmergencies"
                            :key="alert.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-red-100 bg-red-50/30 hover:border-red-200 transition-colors"
                        >
                            <div
                                class="h-8 w-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0"
                            >
                                <Siren
                                    class="h-4 w-4 text-red-600"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-900 truncate"
                                >
                                    {{ alert.residentName }}
                                </p>
                                <p
                                    class="text-xs text-gray-700 mt-0.5 line-clamp-1"
                                >
                                    {{ alert.alertType }}: {{ alert.description }}
                                </p>
                                <p
                                    class="text-xs text-gray-600 mt-0.5"
                                >
                                    {{ formatDate(alert.createdAt) }}
                                </p>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase flex-shrink-0 ml-2"
                                :class="statusColors[alert.status]"
                            >
                                {{ alert.status }}
                            </span>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No active emergency alerts
                        </div>
                    </div>
                </div>

                <!-- Registration Applications -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <UserCheck class="w-4 h-4 text-teal-500" />
                            Applications
                        </h2>
                        <a
                            href="registrations"
                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-0.5"
                        >
                            See All <ChevronRight class="w-3 h-3" />
                        </a>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-if="latestApplications?.length"
                            v-for="app in latestApplications"
                            :key="app.id"
                            class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="h-8 w-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0"
                                >
                                    <UserCheck
                                        class="h-4 w-4 text-teal-600"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ app.studentName }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-600"
                                    >
                                        {{ app.applicationNo }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-medium"
                                    :class="statusColors[app.status] || 'bg-gray-100 text-gray-700'"
                                >
                                    {{ app.status }}
                                </span>
                                <span
                                    v-if="app.paymentStatus === 'pending'"
                                    class="text-[10px] text-amber-600 font-medium"
                                >
                                    Payment pending
                                </span>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-sm text-gray-600 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No pending applications
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">
                        Recent Activity
                    </h2>
                    <ul class="space-y-3">
                        <li
                            v-for="a in recentActivity"
                            :key="a.id + a.type"
                            class="flex items-start gap-2 text-sm"
                        >
                            <div
                                class="h-6 w-6 mt-0.5 rounded-full flex items-center justify-center flex-shrink-0"
                                :class="activityColor(a.color)"
                            >
                                <component
                                    :is="activityIcon(a.icon)"
                                    class="h-3 w-3"
                                />
                            </div>
                            <div>
                                <p class="text-gray-700">
                                    <span class="font-medium">{{
                                        a.name
                                    }}</span>
                                    {{ a.action }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    {{ formatDate(a.date) }}
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="!recentActivity.length"
                            class="text-sm text-gray-600"
                        >
                            No recent activity
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Row 5: MIS Reports -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2
                        class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <FileText class="w-4 h-4 text-blue-500" />
                        Monthly MIS Reports
                    </h2>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="report in misReports"
                        :key="report.id"
                        class="flex items-center justify-between p-3 rounded-lg bg-blue-50/50 hover:bg-blue-50 transition-colors group"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center"
                            >
                                <FileText
                                    class="h-4 w-4 text-blue-600"
                                />
                            </div>
                            <div>
                                <p
                                    class="text-sm font-medium text-gray-900"
                                >
                                    {{ report.label }}
                                </p>
                                <p class="text-xs text-gray-600">
                                    Generated
                                    {{ formatDate(report.generatedAt) }}
                                </p>
                            </div>
                        </div>
                        <a
                            :href="report.url"
                            target="_blank"
                            class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors"
                            title="Download Report"
                        >
                            <Download class="h-4 w-4" />
                        </a>
                    </div>
                    <div
                        v-if="!misReports?.length"
                        class="text-sm text-gray-600 text-center py-4"
                    >
                        No reports available
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
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
} from "lucide-vue-next";

const props = defineProps({
    stats: Object,
    occupancyTrend: Array,
    recentActivity: Array,
    sessionBilling: Object,
    latestComplaints: Array,
    latestLeaves: Array,
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
        label: "Room Capacity",
        value: () => props.stats.rooms.totalCapacity,
        sub: () => `${props.stats.rooms.occupiedBeds} beds occupied`,
        icon: BedDouble,
        color: "purple",
    },
    {
        label: "Total Residents",
        value: () => props.stats.residents.total,
        sub: () => `${props.stats.residents.active} active`,
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
        label: "Fee Collection",
        value: () =>
            "₹" +
            Number(props.stats.fees.paidAmount || 0).toLocaleString("en-IN"),
        sub: () => `${props.stats.fees.pending} pending`,
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
        label: "Vacant Beds",
        value: () => props.stats.beds.vacant,
        sub: () => `of ${props.stats.beds.total} total`,
        icon: BedDouble,
        color: "green",
    },
];

const colorClasses = {
    blue: "bg-blue-50 text-blue-600",
    purple: "bg-purple-50 text-purple-600",
    green: "bg-green-50 text-green-600",
    amber: "bg-amber-50 text-amber-600",
    red: "bg-red-50 text-red-600",
};

const maxTrend = () => Math.max(...props.occupancyTrend.map((m) => m.total), 1);

const statusColors = {
    open: "bg-red-100 text-red-700",
    in_progress: "bg-amber-100 text-amber-700",
    pending: "bg-amber-100 text-amber-700",
    approved: "bg-green-100 text-green-700",
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
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>Dashboard</template>

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">
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
                            <p class="text-xs text-gray-500">
                                {{ card.label }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ card.value() }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
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
                                            (m.occupied / maxTrend()) * 100 +
                                            '%',
                                    }"
                                />
                            </div>
                            <span class="text-[10px] text-gray-400">{{
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
                        <span class="text-xs font-medium text-gray-500"
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
                            <span class="text-gray-500">Session Name</span>
                            <span
                                class="font-medium text-gray-900 text-right"
                                >{{ sessionBilling?.name ?? "-" }}</span
                            >
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Amount</span>
                            <span class="font-semibold text-gray-900">{{
                                formatCurrency(sessionBilling?.totalAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Paid</span>
                            <span class="font-semibold text-green-600">{{
                                formatCurrency(sessionBilling?.paidAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Pending</span>
                            <span class="font-semibold text-red-600">{{
                                formatCurrency(sessionBilling?.pendingAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Refund</span>
                            <span class="font-semibold text-gray-900">{{
                                formatCurrency(sessionBilling?.refundAmount)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Bills Processed</span>
                            <span class="font-semibold text-gray-900"
                                >{{ sessionBilling?.billsProcessed ?? 0 }}/{{
                                    sessionBilling?.totalBills ?? 0
                                }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: MIS Reports + Open Complaints -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- MIS Reports -->
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
                                    <FileText class="h-4 w-4 text-blue-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ report.label }}
                                    </p>
                                    <p class="text-xs text-gray-400">
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
                            class="text-sm text-gray-400 text-center py-4"
                        >
                            No reports available
                        </div>
                    </div>
                </div>

                <!-- Open Complaints -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h2
                            class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                        >
                            <AlertTriangle class="w-4 h-4 text-red-500" />
                            Open SubjectWise Complaints
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
                                        class="h-4 w-4 text-gray-500"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium text-gray-900 truncate"
                                    >
                                        {{ complaint.category }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ complaint.residentName }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-400 mt-0.5 line-clamp-1"
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
                            class="text-sm text-gray-400 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No open complaints
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Leave Requests + Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
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
                                <Users class="h-4 w-4 text-purple-600" />
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
                                        {{ leave.status }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs text-gray-500 mt-0.5 line-clamp-1"
                                >
                                    {{ leave.reason }}
                                </p>
                                <div
                                    class="flex items-center gap-1 mt-1.5 text-xs text-gray-400"
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
                            class="text-sm text-gray-400 text-center py-4 flex flex-col items-center gap-2"
                        >
                            <XCircle class="w-8 h-8 text-gray-300" />
                            No pending leave requests
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div
                    class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">
                        Recent Activity
                    </h2>
                    <ul class="space-y-3">
                        <li
                            v-for="a in recentActivity"
                            :key="a.id"
                            class="flex items-start gap-2 text-sm"
                        >
                            <div
                                class="h-2 w-2 mt-1.5 rounded-full bg-green-500 shrink-0"
                            />
                            <div>
                                <p class="text-gray-700">
                                    <span class="font-medium">{{
                                        a.name
                                    }}</span>
                                    {{ a.action }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ new Date(a.date).toLocaleDateString() }}
                                </p>
                            </div>
                        </li>
                        <li
                            v-if="!recentActivity.length"
                            class="text-sm text-gray-400"
                        >
                            No recent activity
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
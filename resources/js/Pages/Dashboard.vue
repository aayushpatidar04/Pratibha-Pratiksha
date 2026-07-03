<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Building2, BedDouble, Users, Wallet, MessageSquareWarning, CalendarDays, TrendingUp,
} from 'lucide-vue-next';

const props = defineProps({
    stats: Object,
    occupancyTrend: Array,
    recentActivity: Array,
});

const occupancyRate = () => {
    const cap = props.stats.rooms.totalCapacity || 0;
    const occ = props.stats.rooms.occupiedBeds || 0;
    return cap ? Math.round((occ / cap) * 100) : 0;
};

const cards = [
    { label: 'Total Buildings', value: () => props.stats.buildings.total, sub: () => `${props.stats.buildings.active} active`, icon: Building2, color: 'blue' },
    { label: 'Room Capacity', value: () => props.stats.rooms.totalCapacity, sub: () => `${props.stats.rooms.occupiedBeds} beds occupied`, icon: BedDouble, color: 'purple' },
    { label: 'Total Residents', value: () => props.stats.residents.total, sub: () => `${props.stats.residents.active} active`, icon: Users, color: 'green' },
    { label: 'Occupancy Rate', value: () => occupancyRate() + '%', sub: () => 'of total capacity', icon: TrendingUp, color: 'amber' },
    { label: 'Fee Collection', value: () => '₹' + Number(props.stats.fees.paidAmount || 0).toLocaleString('en-IN'), sub: () => `${props.stats.fees.pending} pending`, icon: Wallet, color: 'blue' },
    { label: 'Open Complaints', value: () => props.stats.complaints.open, sub: () => `${props.stats.complaints.resolved} resolved`, icon: MessageSquareWarning, color: 'red' },
    { label: 'Pending Leaves', value: () => props.stats.leaves.pending, sub: () => `${props.stats.leaves.approved} approved`, icon: CalendarDays, color: 'purple' },
    { label: 'Vacant Beds', value: () => props.stats.beds.vacant, sub: () => `of ${props.stats.beds.total} total`, icon: BedDouble, color: 'green' },
];

const colorClasses = {
    blue: 'bg-blue-50 text-blue-600',
    purple: 'bg-purple-50 text-purple-600',
    green: 'bg-green-50 text-green-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
};

const maxTrend = () => Math.max(...props.occupancyTrend.map((m) => m.total), 1);
</script>

<template>

    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>Dashboard</template>

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">Real-time overview of all your hostels</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="card in cards" :key="card.label"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500">{{ card.label }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ card.value() }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ card.sub() }}</p>
                        </div>
                        <div class="h-9 w-9 rounded-lg flex items-center justify-center"
                            :class="colorClasses[card.color]">
                            <component :is="card.icon" class="h-4.5 w-4.5" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Occupancy Trend</h2>
                    <div class="flex items-end gap-2 h-40">
                        <div v-for="m in occupancyTrend" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full flex flex-col justify-end h-32 gap-0.5">
                                <div class="w-full bg-blue-500 rounded-t"
                                    :style="{ height: (m.occupied / maxTrend()) * 100 + '%' }" />
                            </div>
                            <span class="text-[10px] text-gray-400">{{ m.month }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Recent Activity</h2>
                    <ul class="space-y-3">
                        <li v-for="a in recentActivity" :key="a.id" class="flex items-start gap-2 text-sm">
                            <div class="h-2 w-2 mt-1.5 rounded-full bg-green-500 shrink-0" />
                            <div>
                                <p class="text-gray-700"><span class="font-medium">{{ a.name }}</span> {{ a.action }}
                                </p>
                                <p class="text-xs text-gray-400">{{ new Date(a.date).toLocaleDateString() }}</p>
                            </div>
                        </li>
                        <li v-if="!recentActivity.length" class="text-sm text-gray-400">No recent activity</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
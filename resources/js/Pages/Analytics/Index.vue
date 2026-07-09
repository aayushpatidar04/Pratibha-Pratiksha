<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Occupancy from "@/Pages/Analytics/Tabs/Occupancy.vue";
import Leaves from "@/Pages/Analytics/Tabs/Leaves.vue";
import Complaints from "@/Pages/Analytics/Tabs/Complaints.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import { Users, CalendarClock, MessageCircleWarning } from "lucide-vue-next";

defineProps({
    occupancy: Object,
    leaves: Object,
    complaints: Object,
    buildings: Array,
    filterOptions: Object,
});

const tab = ref("occupancy");
const tabs = [
    { key: "occupancy", label: "Occupancy", icon: Users },
    { key: "leaves", label: "Leaves", icon: CalendarClock },
    { key: "complaints", label: "Complains", icon: MessageCircleWarning },
];
</script>

<template>
    <Head title="Analytics" />
    <AuthenticatedLayout>
        <template #header>Analytics</template>

        <div class="space-y-5">
            <div
                class="flex gap-2 bg-white p-1.5 rounded-xl border border-gray-100 shadow-sm w-fit"
            >
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    @click="tab = t.key"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition"
                    :class="
                        tab === t.key
                            ? 'bg-blue-600 text-white'
                            : 'text-gray-600 hover:bg-gray-50'
                    "
                >
                    <component :is="t.icon" class="h-4 w-4" /> {{ t.label }}
                </button>
            </div>

            <Occupancy
                v-if="tab === 'occupancy'"
                :occupancy="occupancy"
                :buildings="buildings"
                :filter-options="filterOptions"
            />
            <Leaves
                v-else-if="tab === 'leaves'"
                :leaves="leaves"
                :buildings="buildings"
            />
            <Complaints
                v-else
                :complaints="complaints"
                :buildings="buildings"
            />
        </div>
    </AuthenticatedLayout>
</template>

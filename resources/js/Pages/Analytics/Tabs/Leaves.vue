<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { ChevronDown, ChevronUp } from "lucide-vue-next";

const props = defineProps({ leaves: Object, buildings: Array });

const presets = [
    { key: "all", label: "All" },
    { key: "today", label: "Today" },
    { key: "current_week", label: "Current Week" },
    { key: "last_7_days", label: "Last 7 days" },
    { key: "last_week", label: "Last week" },
    { key: "this_month", label: "This month" },
    { key: "last_month", label: "Last month" },
    { key: "last_30_days", label: "Last 30 days" },
    { key: "custom", label: "Custom Date" },
];

const active = ref("all");
const customFrom = ref("");
const customTo = ref("");
const expandedBuilding = ref(null);

const selectPreset = (key) => {
    active.value = key;
    if (key !== "custom") {
        router.get(
            "/analytics",
            { leave_range: key },
            { preserveState: true, preserveScroll: true, only: ["leaves"] },
        );
    }
};

const applyCustom = () => {
    router.get(
        "/analytics",
        {
            leave_range: "custom",
            leave_from: customFrom.value,
            leave_to: customTo.value,
        },
        { preserveState: true, preserveScroll: true, only: ["leaves"] },
    );
};

const frequencyMax = computed(() =>
    Math.max(...props.leaves.frequency.map((f) => f.count), 1),
);

const toggleBuilding = (id) => {
    expandedBuilding.value = expandedBuilding.value === id ? null : id;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Date range pills -->
        <div class="flex flex-wrap gap-2 items-center">
            <button
                v-for="p in presets"
                :key="p.key"
                @click="selectPreset(p.key)"
                class="px-3 py-1.5 text-xs rounded-full border"
                :class="
                    active === p.key
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'border-gray-300 text-gray-600'
                "
            >
                {{ p.label }}
            </button>
        </div>
        <div v-if="active === 'custom'" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">From</label>
                <input
                    type="date"
                    v-model="customFrom"
                    class="rounded-lg border-gray-300 text-sm"
                />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">To</label>
                <input
                    type="date"
                    v-model="customTo"
                    class="rounded-lg border-gray-300 text-sm"
                />
            </div>
            <button
                class="px-4 py-1.5 text-sm rounded-lg bg-blue-600 text-white"
                @click="applyCustom"
            >
                Apply
            </button>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-3 max-w-md">
            <div
                class="bg-white rounded-xl border border-gray-100 p-5 text-center"
            >
                <p class="text-2xl font-bold text-gray-900">
                    {{ leaves.total_requests }}
                </p>
                <p class="text-xs text-gray-400 mt-1">Total Leave Requests</p>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-100 p-5 text-center"
            >
                <p class="text-2xl font-bold text-gray-900">
                    {{ leaves.total_students_on_leave }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Total students on Leave
                </p>
            </div>
        </div>

        <!-- Hostel-wise leaves -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">
                Hostel Wise Leaves
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button
                    v-for="b in leaves.hostel_wise"
                    :key="b.building_id"
                    @click="toggleBuilding(b.building_id)"
                    class="border rounded-xl p-4 text-center"
                    :class="
                        expandedBuilding === b.building_id
                            ? 'border-blue-400 bg-blue-50'
                            : 'border-gray-100'
                    "
                >
                    <p class="text-sm font-medium text-gray-900 mb-2">
                        {{ b.name }}
                    </p>
                    <p class="text-xl font-bold text-gray-900">
                        {{ b.total_requests }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Total Leave requests
                    </p>
                    <p class="text-xl font-bold text-gray-900 mt-2">
                        {{ b.students_on_leave }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Total students on Leave
                    </p>
                    <component
                        :is="
                            expandedBuilding === b.building_id
                                ? ChevronUp
                                : ChevronDown
                        "
                        class="h-4 w-4 text-gray-400 mx-auto mt-2"
                    />
                </button>
            </div>
            <p
                v-if="!leaves.hostel_wise.length"
                class="text-sm text-gray-400 text-center py-6"
            >
                No leave requests in this period
            </p>
        </div>

        <!-- Leaves frequency by day of week -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">
                Leaves Frequency
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                <div
                    v-for="day in leaves.frequency"
                    :key="day.day"
                    class="flex items-center gap-3"
                >
                    <span
                        class="h-6 w-6 rounded-full bg-gray-100 text-xs font-semibold flex items-center justify-center shrink-0"
                        >{{ day.count }}</span
                    >
                    <span class="text-sm text-gray-600 w-20 shrink-0">{{
                        day.day
                    }}</span>
                    <div
                        class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden"
                    >
                        <div
                            class="h-full bg-blue-500 rounded-full"
                            :style="{
                                width: (day.count / frequencyMax) * 100 + '%',
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
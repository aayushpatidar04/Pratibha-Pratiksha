<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import { HandMetal, ThumbsUp, Clock, ThumbsDown } from "lucide-vue-next";

const props = defineProps({ complaints: Object, buildings: Array });

const presets = [
    { key: "all", label: "All" },
    { key: "today", label: "Today" },
    { key: "this_week", label: "This Week" },
    { key: "last_week", label: "Last week" },
    { key: "this_month", label: "This month" },
    { key: "last_month", label: "Last month" },
    { key: "custom", label: "Custom Date" },
];
// Map the "This Week" label used on this tab to the same backend preset key
// used by Leaves ("current_week") so one resolver handles both tabs.
const presetKeyMap = { this_week: "current_week" };

const active = ref("all");
const customFrom = ref("");
const customTo = ref("");
const buildingFilter = ref("all");

const selectPreset = (key) => {
    active.value = key;
    if (key !== "custom") {
        router.get(
            "/analytics",
            { complaint_range: presetKeyMap[key] || key },
            { preserveState: true, preserveScroll: true, only: ["complaints"] },
        );
    }
};

const applyCustom = () => {
    router.get(
        "/analytics",
        {
            complaint_range: "custom",
            complaint_from: customFrom.value,
            complaint_to: customTo.value,
        },
        { preserveState: true, preserveScroll: true, only: ["complaints"] },
    );
};

const priorityColors = {
    urgent: "#ef4444",
    high: "#f97316",
    medium: "#f59e0b",
    low: "#9ca3af",
};

const donutData = computed(() => ({
    labels: props.complaints.by_priority.map((p) => p.priority),
    values: props.complaints.by_priority.map((p) => p.count),
    colors: props.complaints.by_priority.map((p) => priorityColors[p.priority]),
}));

const visibleHostelWise = computed(() => {
    if (buildingFilter.value === "all") return props.complaints.hostel_wise;
    return props.complaints.hostel_wise.filter(
        (b) => b.building_id === buildingFilter.value,
    );
});

const aggregate = computed(() => {
    const list = visibleHostelWise.value;
    return {
        raised: list.reduce((s, b) => s + b.raised, 0),
        resolved: list.reduce((s, b) => s + b.resolved, 0),
        pending: list.reduce((s, b) => s + b.pending, 0),
        rejected: list.reduce((s, b) => s + b.rejected, 0),
    };
});

const aggregateSuccessRate = computed(() =>
    aggregate.value.raised > 0
        ? Math.round((aggregate.value.resolved / aggregate.value.raised) * 100)
        : 0,
);
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
            >
                <HandMetal class="h-5 w-5 text-blue-500" />
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ complaints.raised }}
                    </p>
                    <p class="text-[11px] text-gray-400">Complaints Raised</p>
                </div>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
            >
                <ThumbsUp class="h-5 w-5 text-pink-500" />
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ complaints.resolved }}
                    </p>
                    <p class="text-[11px] text-gray-400">Complaints Resolved</p>
                </div>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
            >
                <Clock class="h-5 w-5 text-amber-500" />
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ complaints.pending }}
                    </p>
                    <p class="text-[11px] text-gray-400">Complaints Pending</p>
                </div>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
            >
                <ThumbsDown class="h-5 w-5 text-cyan-500" />
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        {{ complaints.rejected }}
                    </p>
                    <p class="text-[11px] text-gray-400">Complaints Rejected</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Hostel wise complaints -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <h2 class="text-sm font-semibold text-gray-900 mb-3">
                    Hostel wise Complaints
                </h2>
                <div class="flex gap-2 mb-4 flex-wrap">
                    <button
                        class="px-3 py-1 text-xs rounded-full border"
                        :class="
                            buildingFilter === 'all'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600'
                        "
                        @click="buildingFilter = 'all'"
                    >
                        All Buildings
                    </button>
                    <button
                        v-for="b in buildings"
                        :key="b.id"
                        @click="buildingFilter = b.id"
                        class="px-3 py-1 text-xs rounded-full border"
                        :class="
                            buildingFilter === b.id
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'border-gray-300 text-gray-600'
                        "
                    >
                        {{ b.name }}
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div class="space-y-1.5 text-sm">
                        <p class="text-blue-600">
                            Complaints raised - {{ aggregate.raised }}
                        </p>
                        <p class="text-green-600">
                            Complaints resolved - {{ aggregate.resolved }}
                        </p>
                        <p class="text-amber-500">
                            Complaints pending - {{ aggregate.pending }}
                        </p>
                        <p class="text-red-500">
                            Complaints rejected - {{ aggregate.rejected }}
                        </p>
                    </div>
                    <div class="text-center">
                        <DoughnutChart
                            :labels="['Success', 'Remaining']"
                            :values="[
                                aggregateSuccessRate,
                                100 - aggregateSuccessRate,
                            ]"
                            :colors="['#10b981', '#e5e7eb']"
                            :size="100"
                            :center-label="aggregateSuccessRate + '%'"
                        />
                        <p class="text-xs text-gray-400 mt-1">Success rate</p>
                    </div>
                </div>
            </div>

            <!-- Type of complaints (by priority) -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <h2
                    class="text-sm font-semibold text-gray-900 mb-4 text-center"
                >
                    Type of Complaints
                </h2>
                <div class="flex flex-col items-center gap-4">
                    <DoughnutChart
                        :labels="donutData.labels"
                        :values="donutData.values"
                        :colors="donutData.colors"
                        :size="140"
                        cutout="55%"
                    />
                    <div class="space-y-1 text-sm w-full max-w-[200px]">
                        <div
                            v-for="p in complaints.by_priority"
                            :key="p.priority"
                            class="flex items-center justify-between"
                        >
                            <span
                                class="flex items-center gap-1.5 capitalize text-gray-600"
                            >
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            priorityColors[p.priority],
                                    }"
                                />
                                {{ p.priority }}
                            </span>
                            <span class="font-medium text-gray-900">{{
                                p.count
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
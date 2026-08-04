<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
    Building2,
    CalendarCheck,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock3,
    Percent,
    UserRoundCheck,
    XCircle,
} from "lucide-vue-next";

const props = defineProps({
    leaves: {
        type: Object,
        required: true,
    },

    buildings: {
        type: Array,
        default: () => [],
    },
});

const presets = [
    {
        key: "all",
        label: "All",
    },
    {
        key: "today",
        label: "Today",
    },
    {
        key: "current_week",
        label: "Current Week",
    },
    {
        key: "last_7_days",
        label: "Last 7 days",
    },
    {
        key: "last_week",
        label: "Last week",
    },
    {
        key: "this_month",
        label: "This month",
    },
    {
        key: "last_month",
        label: "Last month",
    },
    {
        key: "last_30_days",
        label: "Last 30 days",
    },
    {
        key: "custom",
        label: "Custom Date",
    },
];

const active = ref("all");
const customFrom = ref("");
const customTo = ref("");
const expandedBuilding = ref(null);

const selectPreset = (key) => {
    active.value = key;

    if (key === "custom") {
        return;
    }

    router.get(
        "/analytics",
        {
            leave_range: key,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["leaves"],
        },
    );
};

const applyCustom = () => {
    if (!customFrom.value || !customTo.value) {
        return;
    }

    router.get(
        "/analytics",
        {
            leave_range: "custom",
            leave_from: customFrom.value,
            leave_to: customTo.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["leaves"],
        },
    );
};

const frequencyMax = computed(() => {
    const counts = props.leaves?.frequency?.map(
        (item) => Number(item.count || 0),
    ) || [];

    return Math.max(...counts, 1);
});

const leaveTypeMax = computed(() => {
    const counts = props.leaves?.leave_types?.map(
        (item) => Number(item.count || 0),
    ) || [];

    return Math.max(...counts, 1);
});

const toggleBuilding = (id) => {
    expandedBuilding.value =
        expandedBuilding.value === id
            ? null
            : id;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Date range -->
        <section
            class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="preset in presets"
                    :key="preset.key"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="
                        active === preset.key
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-gray-300 text-gray-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700'
                    "
                    @click="selectPreset(preset.key)"
                >
                    {{ preset.label }}
                </button>
            </div>

            <div
                v-if="active === 'custom'"
                class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4"
            >
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-gray-700"
                    >
                        From
                    </label>

                    <input
                        v-model="customFrom"
                        type="date"
                        class="rounded-lg border-gray-300 text-sm"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-gray-700"
                    >
                        To
                    </label>

                    <input
                        v-model="customTo"
                        type="date"
                        :min="customFrom"
                        class="rounded-lg border-gray-300 text-sm"
                    />
                </div>

                <button
                    type="button"
                    :disabled="
                        !customFrom ||
                        !customTo
                    "
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    @click="applyCustom"
                >
                    Apply
                </button>
            </div>
        </section>

        <!-- Summary cards -->
        <section
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5"
        >
            <div
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                >
                    <CalendarCheck class="h-5 w-5" />
                </div>

                <p class="mt-4 text-2xl font-bold text-gray-900">
                    {{ leaves.total_requests || 0 }}
                </p>

                <p class="mt-1 text-xs font-medium text-gray-600">
                    Total Requests
                </p>

                <p class="mt-1 text-[10px] text-gray-400">
                    Submitted in selected period
                </p>
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600"
                >
                    <CheckCircle2 class="h-5 w-5" />
                </div>

                <p class="mt-4 text-2xl font-bold text-green-700">
                    {{ leaves.approved_leaves || 0 }}
                </p>

                <p class="mt-1 text-xs font-medium text-gray-600">
                    Approved Leaves
                </p>

                <p class="mt-1 text-[10px] text-gray-400">
                    Successful leave requests
                </p>
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                >
                    <Clock3 class="h-5 w-5" />
                </div>

                <p class="mt-4 text-2xl font-bold text-amber-700">
                    {{ leaves.pending_requests || 0 }}
                </p>

                <p class="mt-1 text-xs font-medium text-gray-600">
                    Pending Requests
                </p>

                <p class="mt-1 text-[10px] text-gray-400">
                    Parent or admin decision pending
                </p>
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600"
                >
                    <UserRoundCheck class="h-5 w-5" />
                </div>

                <p class="mt-4 text-2xl font-bold text-purple-700">
                    {{ leaves.currently_on_leave || 0 }}
                </p>

                <p class="mt-1 text-xs font-medium text-gray-600">
                    Currently on Leave
                </p>

                <p class="mt-1 text-[10px] text-gray-400">
                    Approved leaves active today
                </p>
            </div>

            <div
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                >
                    <Percent class="h-5 w-5" />
                </div>

                <p class="mt-4 text-2xl font-bold text-indigo-700">
                    {{ leaves.approval_rate || 0 }}%
                </p>

                <p class="mt-1 text-xs font-medium text-gray-600">
                    Approval Rate
                </p>

                <p class="mt-1 text-[10px] text-gray-400">
                    Approved among resolved requests
                </p>
            </div>
        </section>

        <!-- Request status breakdown -->
        <section
            class="grid grid-cols-1 gap-4 sm:grid-cols-3"
        >
            <div
                class="flex items-center gap-4 rounded-xl border border-red-100 bg-red-50 p-4"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-red-600"
                >
                    <XCircle class="h-5 w-5" />
                </div>

                <div>
                    <p class="text-xl font-bold text-red-700">
                        {{ leaves.rejected_requests || 0 }}
                    </p>

                    <p class="text-xs text-red-600">
                        Rejected requests
                    </p>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-gray-600"
                >
                    <XCircle class="h-5 w-5" />
                </div>

                <div>
                    <p class="text-xl font-bold text-gray-700">
                        {{ leaves.cancelled_requests || 0 }}
                    </p>

                    <p class="text-xs text-gray-600">
                        Cancelled requests
                    </p>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-xl border border-orange-100 bg-orange-50 p-4"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-orange-600"
                >
                    <Clock3 class="h-5 w-5" />
                </div>

                <div>
                    <p class="text-xl font-bold text-orange-700">
                        {{ leaves.expired_requests || 0 }}
                    </p>

                    <p class="text-xs text-orange-600">
                        Expired requests
                    </p>
                </div>
            </div>
        </section>

        <!-- Hostel-wise analytics -->
        <section
            class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
        >
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-900">
                    Hostel-wise Leave Analytics
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Requests and approved leaves in the selected period.
                    Currently-on-leave counts always represent today.
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="building in leaves.hostel_wise"
                    :key="building.building_id"
                    class="overflow-hidden rounded-xl border"
                    :class="
                        expandedBuilding ===
                        building.building_id
                            ? 'border-blue-300 bg-blue-50'
                            : 'border-gray-100 bg-white'
                    "
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 p-4 text-left"
                        @click="
                            toggleBuilding(
                                building.building_id,
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>

                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {{ building.name }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{
                                        building.total_requests
                                    }}
                                    requests
                                </p>
                            </div>
                        </div>

                        <component
                            :is="
                                expandedBuilding ===
                                building.building_id
                                    ? ChevronUp
                                    : ChevronDown
                            "
                            class="h-4 w-4 text-gray-500"
                        />
                    </button>

                    <div
                        v-if="
                            expandedBuilding ===
                            building.building_id
                        "
                        class="grid grid-cols-3 gap-2 border-t border-blue-100 p-4"
                    >
                        <div
                            class="rounded-lg bg-white p-3 text-center"
                        >
                            <p
                                class="text-lg font-bold text-green-700"
                            >
                                {{
                                    building.approved_leaves
                                }}
                            </p>

                            <p class="text-[10px] text-gray-500">
                                Approved
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-white p-3 text-center"
                        >
                            <p
                                class="text-lg font-bold text-amber-700"
                            >
                                {{
                                    building.pending_requests
                                }}
                            </p>

                            <p class="text-[10px] text-gray-500">
                                Pending
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-white p-3 text-center"
                        >
                            <p
                                class="text-lg font-bold text-purple-700"
                            >
                                {{
                                    building.currently_on_leave
                                }}
                            </p>

                            <p class="text-[10px] text-gray-500">
                                On leave today
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <p
                v-if="!leaves.hostel_wise?.length"
                class="py-8 text-center text-sm text-gray-500"
            >
                No leave activity found for this period.
            </p>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <!-- Approved leave frequency -->
            <section
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <h2 class="text-sm font-semibold text-gray-900">
                    Approved Leave Frequency
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Day on which approved leaves start.
                </p>

                <div class="mt-5 space-y-3">
                    <div
                        v-for="day in leaves.frequency"
                        :key="day.day"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold"
                        >
                            {{ day.count }}
                        </span>

                        <span
                            class="w-20 shrink-0 text-sm text-gray-600"
                        >
                            {{ day.day }}
                        </span>

                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"
                        >
                            <div
                                class="h-full rounded-full bg-blue-500"
                                :style="{
                                    width:
                                        (day.count /
                                            frequencyMax) *
                                            100 +
                                        '%',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Leave type breakdown -->
            <section
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <h2 class="text-sm font-semibold text-gray-900">
                    Approved Leaves by Type
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Only successful approved leaves are counted.
                </p>

                <div class="mt-5 space-y-3">
                    <div
                        v-for="type in leaves.leave_types"
                        :key="type.type"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700"
                        >
                            {{ type.count }}
                        </span>

                        <span
                            class="w-32 shrink-0 text-sm text-gray-600"
                        >
                            {{ type.label }}
                        </span>

                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"
                        >
                            <div
                                class="h-full rounded-full bg-indigo-500"
                                :style="{
                                    width:
                                        (type.count /
                                            leaveTypeMax) *
                                            100 +
                                        '%',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
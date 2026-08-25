<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Calendar, Gift, MapPin, Phone } from "lucide-vue-next";

const props = defineProps({
    residents: {
        type: Array,
        default: () => [],
    },

    selectedFilter: {
        type: String,
        default: "today",
    },
});

const filters = [
    {
        key: "today",
        label: "Today",
    },
    {
        key: "tomorrow",
        label: "Tomorrow",
    },
    {
        key: "3_days",
        label: "Next 3 Days",
    },
    {
        key: "5_days",
        label: "Next 5 Days",
    },
    {
        key: "7_days",
        label: "Next 7 Days",
    },
    {
        key: "1_month",
        label: "Next Month",
    },
];

const selectFilter = (filter) => {
    router.get(
        route("residents.birthdays"),
        {
            filter,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const formatDate = (date) => {
    if (!date) return "-";

    return new Intl.DateTimeFormat("en-IN", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(new Date(`${date}T00:00:00`));
};

const formatBirthdayDate = (date) => {
    if (!date) return "-";

    return new Intl.DateTimeFormat("en-IN", {
        day: "numeric",
        month: "short",
    }).format(new Date(`${date}T00:00:00`));
};

const title = computed(() => {
    const active = filters.find(
        (filter) => filter.key === props.selectedFilter,
    );

    return `${active?.label || "Upcoming"} Birthdays`;
});
</script>

<template>
    <Head title="Students Birthday" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    Students Birthday
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    View upcoming birthdays of active residents.
                </p>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white shadow-sm rounded-xl border border-gray-100"
                >
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-pink-50 flex items-center justify-center"
                            >
                                <Gift class="h-5 w-5 text-pink-600" />
                            </div>

                            <div>
                                <h3
                                    class="text-base font-semibold text-gray-900"
                                >
                                    {{ title }}
                                </h3>

                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ residents.length }}
                                    student{{
                                        residents.length === 1 ? "" : "s"
                                    }}
                                    found
                                </p>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="filter in filters"
                                :key="filter.key"
                                type="button"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                :class="
                                    selectedFilter === filter.key
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                "
                                @click="selectFilter(filter.key)"
                            >
                                {{ filter.label }}
                            </button>
                        </div>
                    </div>

                    <!-- List -->
                    <div
                        v-if="residents.length"
                        class="divide-y divide-gray-100"
                    >
                        <div
                            v-for="resident in residents"
                            :key="resident.id"
                            class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 hover:bg-gray-50 transition"
                        >
                            <!-- Student -->
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="h-11 w-11 rounded-full bg-pink-50 text-pink-600 flex items-center justify-center shrink-0"
                                >
                                    <Calendar class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ resident.name }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ resident.resident_code }}
                                    </p>
                                </div>
                            </div>

                            <!-- Birthday -->
                            <div class="min-w-[150px]">
                                <p
                                    class="text-[10px] uppercase tracking-wide text-gray-400"
                                >
                                    Date of Birth
                                </p>

                                <p
                                    class="text-sm font-medium text-gray-800 mt-1"
                                >
                                    {{ formatDate(resident.date_of_birth) }}
                                </p>

                                <p class="text-xs text-pink-600 mt-0.5">
                                    Upcoming:
                                    {{
                                        formatBirthdayDate(
                                            resident.birthday_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <!-- Location -->
                            <div class="min-w-[200px]">
                                <p
                                    class="text-[10px] uppercase tracking-wide text-gray-400"
                                >
                                    Room
                                </p>

                                <div
                                    class="flex items-center gap-1.5 text-sm text-gray-700 mt-1"
                                >
                                    <MapPin class="h-3.5 w-3.5 text-gray-400" />

                                    <span>
                                        <template v-if="resident.room">
                                            {{
                                                resident.building ||
                                                "Building -"
                                            }}
                                            ·
                                            {{ resident.floor || "Floor -" }}
                                            · Room {{ resident.room }}
                                        </template>

                                        <template v-else>
                                            Not allotted
                                        </template>
                                    </span>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="min-w-[150px]">
                                <p
                                    class="text-[10px] uppercase tracking-wide text-gray-400"
                                >
                                    Phone Number
                                </p>

                                <div
                                    class="flex items-center gap-1.5 text-sm text-gray-700 mt-1"
                                >
                                    <Phone class="h-3.5 w-3.5 text-gray-400" />

                                    {{ resident.phone || "-" }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="py-16 text-center">
                        <Gift class="h-10 w-10 mx-auto text-gray-300" />

                        <p class="mt-3 text-sm font-medium text-gray-700">
                            No birthdays found
                        </p>

                        <p class="text-xs text-gray-500 mt-1">
                            There are no student birthdays for the selected
                            period.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

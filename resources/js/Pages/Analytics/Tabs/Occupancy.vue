<script setup>
import { ref, reactive, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import OccupancyHeatmapModal from "@/Components/OccupancyHeatmapModal.vue";
import HeatmapGrid from "@/Components/HeatmapGrid.vue";
import axios from "axios";
import { exportToExcel } from "@/utils/exportXlsx";
import {
    Search,
    Download,
    Info,
    ChevronDown,
    ChevronUp,
    Building2,
} from "lucide-vue-next";

const props = defineProps({
    occupancy: Object,
    buildings: Array,
    filterOptions: Object,
});

// --- Top filters (Gender / Course / Institute / Batch / Year) ---
const filters = reactive({
    gender: "",
    course: "",
    institute: "",
    batch: "",
    year: "",
});
const applyFilters = () => {
    router.get(
        "/analytics",
        { ...filters },
        { preserveState: true, preserveScroll: true, only: ["occupancy"] },
    );
};
const clearFilters = () => {
    filters.gender = "";
    filters.course = "";
    filters.institute = "";
    filters.batch = "";
    filters.year = "";
    applyFilters();
};

// --- Building-wise vs Room-wise chart toggle ---
const chartMode = ref("building"); // 'building' | 'room'

const buildingChartLabels = computed(() =>
    props.occupancy.building_wise_chart.map((b) => b.name),
);
const buildingChartDatasets = computed(() => [
    {
        label: "Active Occupied",
        backgroundColor: "#10b981",
        data: props.occupancy.building_wise_chart.map((b) => b.active_occupied),
    },
    {
        label: "Suspended Occupied",
        backgroundColor: "#6b7280",
        data: props.occupancy.building_wise_chart.map(
            (b) => b.suspended_occupied,
        ),
    },
    {
        label: "Inactive Occupied",
        backgroundColor: "#ef4444",
        data: props.occupancy.building_wise_chart.map(
            (b) => b.inactive_occupied,
        ),
    },
    {
        label: "Vacant",
        backgroundColor: "#d1d5db",
        data: props.occupancy.building_wise_chart.map((b) => b.vacant),
    },
]);

const roomChartLabels = computed(() =>
    props.occupancy.unit_wise.map((u) => u.room_type),
);
const roomChartDatasets = computed(() => [
    {
        label: "Occupied",
        backgroundColor: "#3b82f6",
        data: props.occupancy.unit_wise.map((u) => u.occupied),
    },
    {
        label: "Vacant",
        backgroundColor: "#d1d5db",
        data: props.occupancy.unit_wise.map((u) => u.vacant),
    },
]);

// --- Hostel-Wise / Unit-Wise section toggle ---
const sectionMode = ref("hostel"); // 'hostel' | 'unit'
const search = ref("");
const horizontalView = ref(false);

const filteredBuildings = computed(() => {
    if (!search.value) return props.occupancy.buildings;
    return props.occupancy.buildings.filter((b) =>
        b.name.toLowerCase().includes(search.value.toLowerCase()),
    );
});

// --- Building expansion + Bed-Wise / Room-Wise / Full building detail toggle ---
const expandedBuilding = ref(null);
const tableMode = ref("room"); // 'bed' | 'room' | 'full'

const toggleBuilding = (buildingId) => {
    expandedBuilding.value =
        expandedBuilding.value === buildingId ? null : buildingId;
    tableMode.value = "room";
};

const buildingName = (id) => props.buildings.find((x) => x.id === id)?.name;
const breakdownFor = (buildingId) =>
    props.occupancy.building_breakdown[buildingId] || [];

// --- Unit-wise expansion ---
const expandedUnit = ref(null);
const toggleUnit = (roomType) => {
    expandedUnit.value = expandedUnit.value === roomType ? null : roomType;
};

// --- Per-unit-type heat-map modal (info icon in Bed-Wise/Room-Wise tables) ---
const heatmapOpen = ref(false);
const heatmapBuilding = ref(null);
const heatmapRoomType = ref(null);
const openHeatmap = (building, roomType) => {
    heatmapBuilding.value = building;
    heatmapRoomType.value = roomType;
    heatmapOpen.value = true;
};

// --- Full building detail: inline heat map covering every room type at once ---
const fullBuildingFloors = ref([]);
const fullBuildingLoading = ref(false);

const loadFullBuildingHeatmap = async (buildingId) => {
    fullBuildingLoading.value = true;
    try {
        const { data } = await window.axios.get(
            "/analytics/occupancy/heatmap",
            { params: { building_id: buildingId } },
        );
        fullBuildingFloors.value = data.floors;
    } finally {
        fullBuildingLoading.value = false;
    }
};

watch([tableMode, expandedBuilding], ([mode, buildingId]) => {
    if (mode === "full" && buildingId) loadFullBuildingHeatmap(buildingId);
});

// --- Exports: match exactly whatever columns/rows the current view is showing ---
const exportHostelSummary = () => {
    exportToExcel(
        "Hostel Summary",
        props.occupancy.buildings.map((b) => ({
            Building: b.name,
            Capacity: b.capacity,
            Occupied: b.occupied,
            Vacant: b.vacant,
            "Occupancy %": b.capacity
                ? Math.round((b.occupied / b.capacity) * 100)
                : 0,
        })),
    );
};

const exportRoomWise = (buildingId) => {
    exportToExcel(
        `${buildingName(buildingId)} - Room Wise`,
        breakdownFor(buildingId).map((r) => ({
            "Bed Type": r.room_type,
            "Total Room": r.total_rooms,
            "Fully Occupied": r.fully_occupied,
            "Partially Occupied": r.partially_occupied,
            "Male Occupied": r.male_occupied,
            "Female Occupied": r.female_occupied,
            Vacant: r.vacant,
            "Total students": r.total_students,
        })),
    );
};

const exportBedWise = (buildingId) => {
    exportToExcel(
        `${buildingName(buildingId)} - Bed Wise`,
        breakdownFor(buildingId).map((r) => ({
            "Bed Type": r.room_type,
            "Total Beds": r.total_beds,
            "Occupied Beds": r.occupied_beds,
            "Vacant Beds": r.vacant_beds,
            "Male Occupied": r.male_occupied,
            "Female Occupied": r.female_occupied,
            "Total students": r.total_students,
        })),
    );
};

const exportFullBuildingDetail = (buildingId) => {
    const rows = fullBuildingFloors.value.flatMap((floor) =>
        floor.rooms.map((room) => ({
            Floor: floor.floor_name,
            "Room Number": room.room_number,
            "Bed Type": room.room_type,
            Capacity: room.capacity,
            "Occupied Beds": room.occupied_beds,
            "Vacant Beds": room.capacity - room.occupied_beds,
            Status: room.status.replace("_", " "),
        })),
    );
    exportToExcel(`${buildingName(buildingId)} - Full Building Detail`, rows);
};

// Single "Export" button in the header routes to whichever export matches the
// currently visible table/heat-map, so it always matches what's on screen.
const exportCurrentView = () => {
    if (tableMode.value === "bed") exportBedWise(expandedBuilding.value);
    else if (tableMode.value === "full")
        exportFullBuildingDetail(expandedBuilding.value);
    else exportRoomWise(expandedBuilding.value);
};

const exportUnitSummary = () => {
    exportToExcel(
        "Unit Summary",
        props.occupancy.unit_wise.map((u) => ({
            "Room Type": u.room_type,
            Capacity: u.capacity,
            Occupied: u.occupied,
            Vacant: u.vacant,
        })),
    );
};

const forecastDate = ref("");
const forecastLoading = ref(false);
const occupancyForecast = ref(null);
const forecastSelectedBed = ref(null);
const forecastBedModalOpen = ref(false);

const loadOccupancyForecast = async () => {
    if (!forecastDate.value) {
        occupancyForecast.value = null;
        return;
    }

    forecastLoading.value = true;

    try {
        const response = await axios.get(
            route("analytics.occupancy-forecast"),
            {
                params: {
                    date: forecastDate.value,
                },
            },
        );

        occupancyForecast.value = response.data;
    } catch (error) {
        console.error("Failed to load occupancy forecast:", error);

        occupancyForecast.value = null;
    } finally {
        forecastLoading.value = false;
    }
};

watch(forecastDate, () => {
    loadOccupancyForecast();
});

const formatForecastDate = (date) => {
    if (!date) return "";

    const d = new Date(`${date}T00:00:00`);

    return d.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const roomForecastBorderClass = (room) => {
    const statuses = room.beds?.map((bed) => bed.status) ?? [];

    if (statuses.length && statuses.every((status) => status === "occupied")) {
        return "border-red-200";
    }

    if (statuses.length && statuses.every((status) => status === "vacant")) {
        return "border-green-200";
    }

    return "border-amber-200";
};

const roomForecastStatusLabel = (room) => {
    const beds = room.beds ?? [];

    if (!beds.length) {
        return "No Beds";
    }

    const occupied = beds.filter((bed) => bed.status === "occupied").length;

    const available = beds.filter((bed) => bed.status === "vacant").length;

    if (occupied === beds.length) {
        return "Fully Occupied";
    }

    if (available === beds.length) {
        return "Fully Available";
    }

    return `${available} Available`;
};

const roomForecastBadgeClass = (room) => {
    const beds = room.beds ?? [];

    if (!beds.length) {
        return "bg-gray-100 text-gray-600";
    }

    const occupied = beds.filter((bed) => bed.status === "occupied").length;

    const available = beds.filter((bed) => bed.status === "vacant").length;

    if (occupied === beds.length) {
        return "bg-red-50 text-red-700";
    }

    if (available === beds.length) {
        return "bg-green-50 text-green-700";
    }

    return "bg-amber-50 text-amber-700";
};

const bedForecastClass = (bed) => {
    if (bed.status === "occupied") {
        return "border-red-100 bg-red-50";
    }

    if (bed.upcoming_booking) {
        return "border-amber-100 bg-amber-50";
    }

    return "border-green-100 bg-green-50";
};

const bedForecastIconClass = (bed) => {
    if (bed.status === "occupied") {
        return "bg-red-100 text-red-700";
    }

    if (bed.upcoming_booking) {
        return "bg-amber-100 text-amber-700";
    }

    return "bg-green-100 text-green-700";
};

const openForecastBed = (building, floor, room, bed) => {
    forecastSelectedBed.value = {
        building,
        floor,
        room,
        bed,
    };

    forecastBedModalOpen.value = true;
};

const expandedBuildingId = ref(null);
const expandedFloorId = ref(null);
const expandedRoomId = ref(null);

const toggleBuilding2 = (buildingId) => {
    if (expandedBuildingId.value === buildingId) {
        expandedBuildingId.value = null;
        expandedFloorId.value = null;
        expandedRoomId.value = null;
        return;
    }

    expandedBuildingId.value = buildingId;
    expandedFloorId.value = null;
    expandedRoomId.value = null;
};

const toggleFloor = (floorId) => {
    if (expandedFloorId.value === floorId) {
        expandedFloorId.value = null;
        expandedRoomId.value = null;
        return;
    }

    expandedFloorId.value = floorId;
    expandedRoomId.value = null;
};

const toggleRoom = (roomId) => {
    if (expandedRoomId.value === roomId) {
        expandedRoomId.value = null;
        return;
    }

    expandedRoomId.value = roomId;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Filters -->
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-700 mb-1">Gender</label>
                <select
                    v-model="filters.gender"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-700 mb-1">Course</label>
                <select
                    v-model="filters.course"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Course</option>
                    <option
                        v-for="c in filterOptions.courses"
                        :key="c"
                        :value="c"
                    >
                        {{ c }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-700 mb-1"
                    >Institute</label
                >
                <select
                    v-model="filters.institute"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">Select a Course first</option>
                    <option
                        v-for="i in filterOptions.institutes"
                        :key="i"
                        :value="i"
                    >
                        {{ i }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-700 mb-1">Batch</label>
                <select
                    v-model="filters.batch"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Batches</option>
                    <option
                        v-for="b in filterOptions.batches"
                        :key="b"
                        :value="b"
                    >
                        {{ b }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-700 mb-1">Year</label>
                <select
                    v-model="filters.year"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">Select a Course Year first</option>
                    <option
                        v-for="y in filterOptions.years"
                        :key="y"
                        :value="y"
                    >
                        {{ y }}
                    </option>
                </select>
            </div>
            <button
                class="px-4 py-1.5 text-sm rounded-lg bg-blue-600 text-white"
                @click="applyFilters"
            >
                Apply
            </button>
            <button
                class="px-4 py-1.5 text-sm rounded-lg bg-red-500 text-white"
                @click="clearFilters"
            >
                Clear
            </button>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-2xl font-bold text-gray-900">
                    {{ occupancy.summary.total_capacity }}
                </p>
                <p class="text-xs text-gray-600">Total Capacity</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-2xl font-bold text-blue-600">
                    {{ occupancy.summary.filled_capacity }}
                </p>
                <p class="text-xs text-gray-600">Filled Capacity</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-2xl font-bold text-green-600">
                    {{ occupancy.summary.vacant_capacity }}
                </p>
                <p class="text-xs text-gray-600">Vacant Capacity</p>
            </div>
            <div
                class="bg-white rounded-xl border border-gray-100 p-4 flex items-center gap-3"
            >
                <DoughnutChart
                    :labels="['Active', 'Available']"
                    :values="[
                        occupancy.summary.occupancy_percent,
                        100 - occupancy.summary.occupancy_percent,
                    ]"
                    :colors="['#7c3aed', '#e5e7eb']"
                    :size="56"
                    :center-label="occupancy.summary.occupancy_percent + '%'"
                />
                <div>
                    <p class="text-xs text-gray-600">Occupancy (Capacity) %</p>
                    <p class="text-xs text-gray-700 mt-1">
                        Active <b>{{ occupancy.summary.filled_capacity }}</b>
                    </p>
                    <p class="text-xs text-gray-700">
                        Available <b>{{ occupancy.summary.vacant_capacity }}</b>
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs font-semibold text-gray-700 mb-2">
                    Room-Wise Distribution
                </p>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-red-500" /> Fully
                        Occupied:
                        {{ occupancy.room_wise_distribution.fully_occupied }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-gray-300" />
                        Vacant: {{ occupancy.room_wise_distribution.vacant }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-400" />
                        Partially Filled:
                        {{ occupancy.room_wise_distribution.partially_filled }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Building/Room-wise chart -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{
                        chartMode === "building" ? "Building-Wise" : "Room-Wise"
                    }}
                    Capacity Chart
                </h2>
                <div
                    class="flex rounded-lg border border-gray-200 overflow-hidden text-xs"
                >
                    <button
                        class="px-3 py-1.5"
                        :class="
                            chartMode === 'building'
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600'
                        "
                        @click="chartMode = 'building'"
                    >
                        Building-Wise
                    </button>
                    <button
                        class="px-3 py-1.5"
                        :class="
                            chartMode === 'room'
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600'
                        "
                        @click="chartMode = 'room'"
                    >
                        Room-Wise
                    </button>
                </div>
            </div>
            <BarChart
                v-if="chartMode === 'building'"
                :labels="buildingChartLabels"
                :datasets="buildingChartDatasets"
                :stacked="false"
            />
            <BarChart
                v-else
                :labels="roomChartLabels"
                :datasets="roomChartDatasets"
                :stacked="false"
            />
        </div>

        <!-- Hostel-Wise / Unit-Wise Occupancy -->
        <div
            class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ sectionMode === "hostel" ? "Hostel-Wise" : "Unit-Wise" }}
                    Occupancy
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-600"
                        />
                        <input
                            v-model="search"
                            placeholder="Search Hostel..."
                            class="pl-8 rounded-lg border-gray-300 text-xs py-1.5"
                        />
                    </div>
                    <button
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-300"
                        @click="horizontalView = !horizontalView"
                    >
                        {{ horizontalView ? "Grid View" : "Horizontal View" }}
                    </button>
                    <button
                        class="px-3 py-1.5 text-xs rounded-lg bg-amber-400 text-white flex items-center gap-1"
                        @click="exportHostelSummary"
                    >
                        <Download class="h-3 w-3" /> Export Hostel Summary
                    </button>
                    <button
                        class="px-3 py-1.5 text-xs rounded-lg bg-amber-400 text-white flex items-center gap-1"
                        @click="exportHostelSummary"
                    >
                        <Download class="h-3 w-3" /> Export as Excel
                    </button>
                    <div
                        class="flex rounded-lg border border-gray-200 overflow-hidden text-xs"
                    >
                        <button
                            class="px-3 py-1.5"
                            :class="
                                sectionMode === 'unit'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600'
                            "
                            @click="sectionMode = 'unit'"
                        >
                            Unit-Wise
                        </button>
                        <button
                            class="px-3 py-1.5"
                            :class="
                                sectionMode === 'hostel'
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600'
                            "
                            @click="sectionMode = 'hostel'"
                        >
                            Hostel-Wise
                        </button>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-600">
                Click on a Hostel to check detailed occupancy!
            </p>

            <!-- Hostel-wise cards -->
            <template v-if="sectionMode === 'hostel'">
                <div
                    class="grid gap-3"
                    :class="
                        horizontalView
                            ? 'grid-cols-1'
                            : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
                    "
                >
                    <div
                        v-for="b in filteredBuildings"
                        :key="b.id"
                        class="border border-gray-100 rounded-xl p-4"
                    >
                        <button
                            class="w-full flex items-center justify-between gap-3"
                            @click="toggleBuilding(b.id)"
                        >
                            <div class="flex items-center gap-3">
                                <div class="text-left">
                                    <p
                                        class="font-semibold text-gray-900 flex items-center gap-1.5"
                                    >
                                        <Building2
                                            class="h-3.5 w-3.5 text-blue-500"
                                        />
                                        {{ b.name }}
                                    </p>
                                    <div
                                        class="flex gap-4 mt-1.5 text-xs text-gray-700"
                                    >
                                        <span
                                            >{{ b.capacity }}<br /><span
                                                class="text-[10px] text-gray-600"
                                                >Capacity</span
                                            ></span
                                        >
                                        <span
                                            >{{ b.vacant }}<br /><span
                                                class="text-[10px] text-gray-600"
                                                >Vacant</span
                                            ></span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <DoughnutChart
                                    :labels="['Occupied', 'Vacant']"
                                    :values="[b.occupied, b.vacant]"
                                    :colors="['#3b82f6', '#e5e7eb']"
                                    :size="44"
                                    :center-label="
                                        (b.capacity
                                            ? Math.round(
                                                  (b.occupied / b.capacity) *
                                                      100,
                                              )
                                            : 0) + '%'
                                    "
                                />
                                <component
                                    :is="
                                        expandedBuilding === b.id
                                            ? ChevronUp
                                            : ChevronDown
                                    "
                                    class="h-4 w-4 text-gray-600"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Expanded detail: Bed-Wise / Room-Wise table, or Full building detail heat map -->
                <div v-if="expandedBuilding" class="pt-2">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 mb-2"
                    >
                        <div
                            class="flex rounded-lg border border-gray-200 overflow-hidden text-xs"
                        >
                            <button
                                class="px-3 py-1.5"
                                :class="
                                    tableMode === 'bed'
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600'
                                "
                                @click="tableMode = 'bed'"
                            >
                                Bed-Wise
                            </button>
                            <button
                                class="px-3 py-1.5"
                                :class="
                                    tableMode === 'room'
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600'
                                "
                                @click="tableMode = 'room'"
                            >
                                Room-Wise
                            </button>
                            <button
                                class="px-3 py-1.5"
                                :class="
                                    tableMode === 'full'
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600'
                                "
                                @click="tableMode = 'full'"
                            >
                                Full building detail
                            </button>
                        </div>
                        <button
                            class="px-3 py-1.5 text-xs rounded-lg bg-amber-400 text-white flex items-center gap-1"
                            @click="exportCurrentView"
                        >
                            <Download class="h-3 w-3" />
                            {{
                                tableMode === "bed"
                                    ? "Export Bed Wise"
                                    : tableMode === "full"
                                      ? "Export Full Building Detail"
                                      : "Export Room Wise"
                            }}
                        </button>
                    </div>

                    <!-- Room-Wise table -->
                    <div
                        v-if="tableMode === 'room'"
                        class="overflow-x-auto border border-gray-100 rounded-lg"
                    >
                        <table class="w-full text-sm">
                            <thead
                                class="bg-gray-50 text-gray-700 text-xs uppercase"
                            >
                                <tr>
                                    <th class="text-left px-3 py-2">
                                        Bed type
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Total Room
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Fully Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Partially Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Male Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Female Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">Vacant</th>
                                    <th class="text-left px-3 py-2">
                                        Total students
                                    </th>
                                    <th class="text-left px-3 py-2">Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in breakdownFor(
                                        expandedBuilding,
                                    )"
                                    :key="row.room_type"
                                >
                                    <td
                                        class="px-3 py-2 font-medium text-gray-900"
                                    >
                                        {{ row.room_type }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.total_rooms }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.fully_occupied }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.partially_occupied }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.male_occupied }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.female_occupied }}
                                    </td>
                                    <td class="px-3 py-2">{{ row.vacant }}</td>
                                    <td class="px-3 py-2">
                                        {{ row.total_students }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <button
                                            class="h-6 w-6 inline-flex items-center justify-center rounded-full bg-blue-50 text-blue-600"
                                            @click="
                                                openHeatmap(
                                                    {
                                                        id: expandedBuilding,
                                                        name: buildingName(
                                                            expandedBuilding,
                                                        ),
                                                    },
                                                    row.room_type,
                                                )
                                            "
                                        >
                                            <Info class="h-3.5 w-3.5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !breakdownFor(expandedBuilding).length
                                    "
                                >
                                    <td
                                        colspan="9"
                                        class="px-3 py-6 text-center text-gray-600"
                                    >
                                        No rooms of any type in this building
                                        yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bed-Wise table: same rows, but bed-level counts instead of room-level -->
                    <div
                        v-else-if="tableMode === 'bed'"
                        class="overflow-x-auto border border-gray-100 rounded-lg"
                    >
                        <table class="w-full text-sm">
                            <thead
                                class="bg-gray-50 text-gray-700 text-xs uppercase"
                            >
                                <tr>
                                    <th class="text-left px-3 py-2">
                                        Bed type
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Total Beds
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Occupied Beds
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Vacant Beds
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Male Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Female Occupied
                                    </th>
                                    <th class="text-left px-3 py-2">
                                        Total students
                                    </th>
                                    <th class="text-left px-3 py-2">Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in breakdownFor(
                                        expandedBuilding,
                                    )"
                                    :key="row.room_type"
                                >
                                    <td
                                        class="px-3 py-2 font-medium text-gray-900"
                                    >
                                        {{ row.room_type }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.total_beds }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.occupied_beds }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.vacant_beds }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.male_occupied }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.female_occupied }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ row.total_students }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <button
                                            class="h-6 w-6 inline-flex items-center justify-center rounded-full bg-blue-50 text-blue-600"
                                            @click="
                                                openHeatmap(
                                                    {
                                                        id: expandedBuilding,
                                                        name: buildingName(
                                                            expandedBuilding,
                                                        ),
                                                    },
                                                    row.room_type,
                                                )
                                            "
                                        >
                                            <Info class="h-3.5 w-3.5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !breakdownFor(expandedBuilding).length
                                    "
                                >
                                    <td
                                        colspan="8"
                                        class="px-3 py-6 text-center text-gray-600"
                                    >
                                        No rooms of any type in this building
                                        yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Full building detail: heat map across every room type on every floor -->
                    <div v-else class="border border-gray-100 rounded-lg p-4">
                        <HeatmapGrid
                            :floors="fullBuildingFloors"
                            :loading="fullBuildingLoading"
                            :show-room-type="true"
                        />
                    </div>
                </div>
            </template>

            <!-- Unit-wise rollup -->
            <template v-else>
                <div class="flex justify-end">
                    <button
                        class="px-3 py-1.5 text-xs rounded-lg bg-amber-400 text-white flex items-center gap-1"
                        @click="exportUnitSummary"
                    >
                        <Download class="h-3 w-3" /> Export Unit Summary
                    </button>
                </div>
                <div
                    v-for="unit in occupancy.unit_wise"
                    :key="unit.room_type"
                    class="border-t border-gray-100 pt-3"
                >
                    <button
                        class="w-full flex items-center justify-between text-sm"
                        @click="toggleUnit(unit.room_type)"
                    >
                        <span class="text-gray-700">
                            <b>{{ unit.room_type }}</b> · Total Capacity:
                            {{ unit.capacity }} · Occupied:
                            {{ unit.occupied }} · Vacant: {{ unit.vacant }}
                        </span>
                        <component
                            :is="
                                expandedUnit === unit.room_type
                                    ? ChevronUp
                                    : ChevronDown
                            "
                            class="h-4 w-4 text-gray-600"
                        />
                    </button>
                    <div
                        v-if="expandedUnit === unit.room_type"
                        class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3"
                    >
                        <div
                            v-for="b in unit.buildings"
                            :key="b.building_id"
                            class="border border-gray-100 rounded-lg p-3"
                        >
                            <p class="text-sm font-medium text-gray-900">
                                {{ b.name }}
                            </p>
                            <div class="flex gap-4 mt-1 text-xs text-gray-700">
                                <span
                                    >{{ b.occupied }} / {{ b.capacity
                                    }}<br /><span class="text-[10px]"
                                        >Occupied</span
                                    ></span
                                >
                                <span
                                    >{{ b.vacant }}<br /><span
                                        class="text-[10px]"
                                        >Vacant</span
                                    ></span
                                >
                            </div>
                            <button
                                class="mt-2 text-xs text-blue-600 hover:underline"
                                @click="
                                    openHeatmap(
                                        { id: b.building_id, name: b.name },
                                        unit.room_type,
                                    )
                                "
                            >
                                View heat map
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <OccupancyHeatmapModal
            :show="heatmapOpen"
            :building-id="heatmapBuilding?.id"
            :building-name="heatmapBuilding?.name"
            :room-type="heatmapRoomType"
            @close="heatmapOpen = false"
        />

        <!-- Occupancy Forecast Date -->
        <div
            class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">
                        Occupancy Forecast
                    </h2>

                    <p class="text-xs text-gray-600 mt-1">
                        Select a date to see which rooms and beds are expected
                        to be occupied or available.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <label
                        for="occupancy-forecast-date"
                        class="text-xs font-medium text-gray-700"
                    >
                        Forecast Date
                    </label>

                    <input
                        id="occupancy-forecast-date"
                        v-model="forecastDate"
                        type="date"
                        class="rounded-lg border-gray-300 text-sm py-1.5"
                    />
                    <div
                        v-if="forecastLoading"
                        class="bg-white rounded-xl border border-gray-100 p-5 text-sm text-gray-600"
                    >
                        Loading occupancy forecast...
                    </div>
                    <button
                        v-if="forecastDate"
                        type="button"
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                        @click="forecastDate = ''"
                    >
                        Clear
                    </button>
                </div>
            </div>
            <!-- Occupancy Forecast -->
            <div
                v-if="forecastDate"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4"
                >
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">
                            Occupancy Forecast
                        </h2>

                        <p class="text-xs text-gray-500 mt-1">
                            Room and bed availability forecast for
                            <span class="font-semibold text-gray-700">
                                {{ formatForecastDate(forecastDate) }}
                            </span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-red-500"
                            ></span>
                            Occupied
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-green-500"
                            ></span>
                            Available
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-400"
                            ></span>
                            Upcoming Booking
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div
                    v-if="forecastLoading"
                    class="py-12 text-center text-sm text-gray-500"
                >
                    Loading occupancy forecast...
                </div>

                <!-- No data -->
                <div
                    v-else-if="!occupancyForecast?.buildings?.length"
                    class="py-12 text-center text-sm text-gray-500"
                >
                    No rooms available for forecast.
                </div>

                <!-- Buildings -->
                <div v-else class="space-y-3">
                    <div
                        v-for="building in occupancyForecast.buildings"
                        :key="building.id"
                        class="border border-gray-200 rounded-xl overflow-hidden bg-white"
                    >
                        <!-- ========================= -->
                        <!-- BUILDING -->
                        <!-- ========================= -->
                        <button
                            type="button"
                            class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition"
                            @click="toggleBuilding2(building.id)"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Chevron -->
                                <svg
                                    class="h-4 w-4 text-gray-500 transition-transform shrink-0"
                                    :class="{
                                        'rotate-90':
                                            expandedBuildingId === building.id,
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>

                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-gray-900 truncate"
                                    >
                                        {{ building.name }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-0.5">
                                        <!-- {{ building.total_rooms }} rooms · -->
                                        {{ building.occupied_beds }} occupied ·
                                        {{ building.vacant_beds }} available
                                    </p>
                                </div>
                            </div>

                            <!-- Building Summary -->
                            <div
                                class="flex items-center gap-2 text-[10px] shrink-0"
                            >
                                <span
                                    class="px-2 py-1 rounded-md bg-red-50 text-red-700"
                                >
                                    {{ building.occupied_beds }} occupied
                                </span>

                                <span
                                    class="px-2 py-1 rounded-md bg-green-50 text-green-700"
                                >
                                    {{ building.vacant_beds }} available
                                </span>
                            </div>
                        </button>

                        <!-- ========================= -->
                        <!-- FLOORS -->
                        <!-- ========================= -->
                        <div
                            v-if="expandedBuildingId === building.id"
                            class="border-t border-gray-100 bg-gray-50/50"
                        >
                            <div class="p-3 space-y-2">
                                <div
                                    v-for="floor in building.floors"
                                    :key="floor.id"
                                    class="border border-gray-200 rounded-lg overflow-hidden bg-white"
                                >
                                    <!-- Floor Header -->
                                    <button
                                        type="button"
                                        class="w-full px-3 py-2.5 flex items-center justify-between text-left hover:bg-gray-50 transition"
                                        @click.stop="toggleFloor(floor.id)"
                                    >
                                        <div class="flex items-center gap-2">
                                            <svg
                                                class="h-3.5 w-3.5 text-gray-400 transition-transform"
                                                :class="{
                                                    'rotate-90':
                                                        expandedFloorId ===
                                                        floor.id,
                                                }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 5l7 7-7 7"
                                                />
                                            </svg>

                                            <div>
                                                <p
                                                    class="text-xs font-semibold text-gray-800"
                                                >
                                                    {{ floor.name }}
                                                </p>

                                                <p
                                                    class="text-[10px] text-gray-400 mt-0.5"
                                                >
                                                    {{
                                                        floor.rooms.length
                                                    }}
                                                    rooms
                                                </p>
                                            </div>
                                        </div>

                                        <span class="text-[10px] text-gray-400">
                                            Floor {{ floor.floor_number }}
                                        </span>
                                    </button>

                                    <!-- ========================= -->
                                    <!-- ROOMS -->
                                    <!-- ========================= -->
                                    <div
                                        v-if="expandedFloorId === floor.id"
                                        class="border-t border-gray-100 p-3 bg-gray-50/30"
                                    >
                                        <div class="space-y-2">
                                            <div
                                                v-for="room in floor.rooms"
                                                :key="room.id"
                                                class="rounded-xl border overflow-hidden bg-white"
                                                :class="
                                                    roomForecastBorderClass(
                                                        room,
                                                    )
                                                "
                                            >
                                                <!-- Room Header -->
                                                <button
                                                    type="button"
                                                    class="w-full px-3 py-2.5 flex items-center justify-between text-left hover:bg-gray-50 transition"
                                                    @click.stop="
                                                        toggleRoom(room.id)
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center gap-2 min-w-0"
                                                    >
                                                        <svg
                                                            class="h-3.5 w-3.5 text-gray-400 transition-transform shrink-0"
                                                            :class="{
                                                                'rotate-90':
                                                                    expandedRoomId ===
                                                                    room.id,
                                                            }"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 5l7 7-7 7"
                                                            />
                                                        </svg>

                                                        <div class="min-w-0">
                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <p
                                                                    class="text-sm font-semibold text-gray-900"
                                                                >
                                                                    Room
                                                                    {{
                                                                        room.room_number
                                                                    }}
                                                                </p>

                                                                <span
                                                                    class="px-2 py-1 rounded-md text-[10px] font-semibold"
                                                                    :class="
                                                                        roomForecastBadgeClass(
                                                                            room,
                                                                        )
                                                                    "
                                                                >
                                                                    {{
                                                                        roomForecastStatusLabel(
                                                                            room,
                                                                        )
                                                                    }}
                                                                </span>
                                                            </div>

                                                            <p
                                                                class="text-[10px] text-gray-500 mt-0.5"
                                                            >
                                                                {{
                                                                    room.room_type
                                                                }}
                                                                ·
                                                                {{
                                                                    room.capacity
                                                                }}
                                                                beds ·
                                                                {{
                                                                    room.occupied_beds
                                                                }}
                                                                occupied ·
                                                                {{
                                                                    room.vacant_beds
                                                                }}
                                                                vacant
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Room status -->
                                                    <div
                                                        class="flex items-center gap-2 shrink-0"
                                                    >
                                                        <span
                                                            class="text-[10px] text-red-600"
                                                        >
                                                            {{
                                                                room.occupied_beds
                                                            }}
                                                        </span>

                                                        <span
                                                            class="text-gray-300"
                                                        >
                                                            /
                                                        </span>

                                                        <span
                                                            class="text-[10px] text-green-600"
                                                        >
                                                            {{
                                                                room.vacant_beds
                                                            }}
                                                        </span>
                                                    </div>
                                                </button>

                                                <!-- ========================= -->
                                                <!-- BEDS -->
                                                <!-- ========================= -->
                                                <div
                                                    v-if="
                                                        expandedRoomId ===
                                                        room.id
                                                    "
                                                    class="border-t border-gray-100 p-3 bg-gray-50/30"
                                                >
                                                    <div
                                                        v-if="room.beds?.length"
                                                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2"
                                                    >
                                                        <div
                                                            v-for="bed in room.beds"
                                                            :key="bed.id"
                                                            class="flex items-center justify-between gap-2 p-2.5 rounded-lg border"
                                                            :class="
                                                                bedForecastClass(
                                                                    bed,
                                                                )
                                                            "
                                                        >
                                                            <div
                                                                class="flex items-center gap-2 min-w-0"
                                                            >
                                                                <!-- Bed Number -->
                                                                <div
                                                                    class="h-7 w-7 rounded-md flex items-center justify-center text-[10px] font-semibold shrink-0"
                                                                    :class="
                                                                        bedForecastIconClass(
                                                                            bed,
                                                                        )
                                                                    "
                                                                >
                                                                    {{
                                                                        bed.bed_number
                                                                    }}
                                                                </div>

                                                                <!-- Bed Information -->
                                                                <div
                                                                    class="min-w-0"
                                                                >
                                                                    <p
                                                                        class="text-xs font-medium text-gray-900 truncate"
                                                                    >
                                                                        Bed
                                                                        {{
                                                                            bed.bed_number
                                                                        }}
                                                                    </p>

                                                                    <p
                                                                        v-if="
                                                                            bed.resident
                                                                        "
                                                                        class="text-[10px] text-gray-500 truncate"
                                                                    >
                                                                        {{
                                                                            bed
                                                                                .resident
                                                                                .name
                                                                        }}
                                                                    </p>

                                                                    <p
                                                                        v-else-if="
                                                                            bed.upcoming_booking
                                                                        "
                                                                        class="text-[10px] text-amber-700 truncate"
                                                                    >
                                                                        Upcoming
                                                                        booking
                                                                    </p>

                                                                    <p
                                                                        v-else
                                                                        class="text-[10px] text-green-700"
                                                                    >
                                                                        Available
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- Details -->
                                                            <button
                                                                v-if="
                                                                    bed.status ===
                                                                        'vacant' ||
                                                                    bed.upcoming_booking
                                                                "
                                                                type="button"
                                                                class="text-[10px] text-blue-600 hover:text-blue-800 hover:underline shrink-0"
                                                                @click.stop="
                                                                    openForecastBed(
                                                                        building,
                                                                        floor,
                                                                        room,
                                                                        bed,
                                                                    )
                                                                "
                                                            >
                                                                Details
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div
                                                        v-else
                                                        class="text-xs text-gray-500 text-center py-4"
                                                    >
                                                        No beds found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!building.floors?.length"
                                    class="text-xs text-gray-500 text-center py-4"
                                >
                                    No floors found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Forecast Bed Details Modal -->
        <div
            v-if="forecastBedModalOpen && forecastSelectedBed"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/40"
                @click="forecastBedModalOpen = false"
            ></div>

            <!-- Modal -->
            <div
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden"
            >
                <!-- Header -->
                <div
                    class="px-5 py-4 border-b border-gray-100 flex items-start justify-between"
                >
                    <div>
                        <p class="text-base font-semibold text-gray-900">
                            Bed {{ forecastSelectedBed.bed.bed_number }} Details
                        </p>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ forecastSelectedBed.building.name }}
                            ·
                            {{ forecastSelectedBed.floor.name }}
                            ·
                            Room {{ forecastSelectedBed.room.room_number }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100"
                        @click="forecastBedModalOpen = false"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4">
                    <!-- Location -->
                    <div
                        class="grid grid-cols-3 gap-2"
                    >
                        <div
                            class="rounded-lg bg-gray-50 border border-gray-100 p-3"
                        >
                            <p class="text-[10px] text-gray-500">
                                Building
                            </p>

                            <p class="text-xs font-semibold text-gray-900 mt-1">
                                {{ forecastSelectedBed.building.name }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-gray-50 border border-gray-100 p-3"
                        >
                            <p class="text-[10px] text-gray-500">
                                Floor
                            </p>

                            <p class="text-xs font-semibold text-gray-900 mt-1">
                                {{ forecastSelectedBed.floor.name }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-gray-50 border border-gray-100 p-3"
                        >
                            <p class="text-[10px] text-gray-500">
                                Room
                            </p>

                            <p class="text-xs font-semibold text-gray-900 mt-1">
                                {{ forecastSelectedBed.room.room_number }}
                            </p>
                        </div>
                    </div>

                    <!-- Bed Status -->
                    <div
                        class="rounded-xl border p-4"
                        :class="
                            forecastSelectedBed.bed.upcoming_booking
                                ? 'border-amber-200 bg-amber-50'
                                : 'border-green-200 bg-green-50'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">
                                    Bed
                                </p>

                                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                                    Bed {{ forecastSelectedBed.bed.bed_number }}
                                </p>
                            </div>

                            <span
                                class="px-2.5 py-1 rounded-md text-[10px] font-semibold"
                                :class="
                                    forecastSelectedBed.bed.upcoming_booking
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-green-100 text-green-700'
                                "
                            >
                                {{
                                    forecastSelectedBed.bed.upcoming_booking
                                        ? 'Upcoming Booking'
                                        : 'Available'
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Upcoming Booking -->
                    <div
                        v-if="forecastSelectedBed.bed.upcoming_booking"
                        class="space-y-3"
                    >
                        <div>
                            <p class="text-xs font-semibold text-gray-900">
                                Upcoming Booking
                            </p>

                            <p class="text-[10px] text-gray-500 mt-0.5">
                                This bed is currently available but has a future
                                reservation.
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-amber-100 bg-amber-50/50 p-4 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    Resident
                                </span>

                                <span class="text-xs font-medium text-gray-900">
                                    {{
                                        forecastSelectedBed.bed.upcoming_booking
                                            .resident_name
                                            || 'Not assigned'
                                    }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    Check-in
                                </span>

                                <span class="text-xs font-medium text-gray-900">
                                    {{
                                        forecastSelectedBed.bed.upcoming_booking
                                            .check_in_date
                                            || '-'
                                    }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    Expected checkout
                                </span>

                                <span class="text-xs font-medium text-gray-900">
                                    {{
                                        forecastSelectedBed.bed.upcoming_booking
                                            .expected_check_out_date
                                            || '-'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Currently Available -->
                    <div
                        v-else
                        class="rounded-xl border border-green-100 bg-green-50/50 p-4"
                    >
                        <p class="text-xs font-semibold text-green-800">
                            Currently Available
                        </p>

                        <p class="text-[10px] text-green-700 mt-1">
                            No upcoming booking is currently associated with this
                            bed.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-5 py-3 border-t border-gray-100 flex justify-end"
                >
                    <button
                        type="button"
                        class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        @click="forecastBedModalOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

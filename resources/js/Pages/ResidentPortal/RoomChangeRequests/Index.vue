<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowRight,
    ArrowRightLeft,
    BedDouble,
    Building2,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Eye,
    IndianRupee,
    MapPin,
    Plus,
    Search,
    X,
    XCircle,
} from "lucide-vue-next";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    requests: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    currentStay: {
        type: Object,
        default: null,
    },

    hasPendingRequest: {
        type: Boolean,
        default: false,
    },

    buildings: {
        type: Array,
        default: () => [],
    },

    floors: {
        type: Array,
        default: () => [],
    },

    rooms: {
        type: Array,
        default: () => [],
    },
});

const createOpen = ref(false);

const filterForm = reactive({
    status: props.filters?.status || "all",
    search: props.filters?.search || "",
});

let searchTimer = null;

const createForm = useForm({
    reason: "",
    requested_building_id: "",
    requested_floor_id: "",
    requested_room_id: "",
    requested_bed_id: "",
});

const floorsForBuilding = computed(() => {
    return props.floors.filter(
        (floor) =>
            Number(floor.building_id) ===
            Number(createForm.requested_building_id),
    );
});

const roomsForFloor = computed(() => {
    return props.rooms.filter(
        (room) =>
            Number(room.floor_id) === Number(createForm.requested_floor_id),
    );
});

const selectedRoom = computed(() => {
    return props.rooms.find(
        (room) => Number(room.id) === Number(createForm.requested_room_id),
    );
});

const vacantBeds = computed(() => {
    return (
        selectedRoom.value?.beds?.filter((bed) => bed.status === "vacant") || []
    );
});

const selectedBuilding = computed(() => {
    return props.buildings.find(
        (building) =>
            Number(building.id) === Number(createForm.requested_building_id),
    );
});

const selectedFloor = computed(() => {
    return props.floors.find(
        (floor) => Number(floor.id) === Number(createForm.requested_floor_id),
    );
});

const selectedBed = computed(() => {
    return vacantBeds.value.find(
        (bed) => Number(bed.id) === Number(createForm.requested_bed_id),
    );
});

const statusOptions = [
    {
        value: "all",
        label: "All Requests",
    },
    {
        value: "pending",
        label: "Pending",
    },
    {
        value: "approved",
        label: "Approved",
    },
    {
        value: "rejected",
        label: "Rejected",
    },
    {
        value: "cancelled",
        label: "Cancelled",
    },
];

const statusClasses = {
    pending: "border-amber-200 bg-amber-50 text-amber-700",

    approved: "border-emerald-200 bg-emerald-50 text-emerald-700",

    rejected: "border-red-200 bg-red-50 text-red-700",

    cancelled: "border-slate-200 bg-slate-100 text-slate-600",
};

const statusIcon = (status) => {
    if (status === "approved") {
        return CheckCircle2;
    }

    if (status === "rejected") {
        return XCircle;
    }

    if (status === "cancelled") {
        return X;
    }

    return Clock3;
};

const applyFilters = () => {
    router.get(
        route("resident.room-change-requests.index"),
        {
            status: filterForm.status !== "all" ? filterForm.status : undefined,

            search: filterForm.search || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterForm.status = "all";
    filterForm.search = "";

    router.get(
        route("resident.room-change-requests.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => filterForm.search,
    () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            if (
                filterForm.search.length === 0 ||
                filterForm.search.length >= 3
            ) {
                applyFilters();
            }
        }, 400);
    },
);

watch(
    () => createForm.requested_building_id,
    () => {
        createForm.requested_floor_id = "";
        createForm.requested_room_id = "";
        createForm.requested_bed_id = "";
    },
);

watch(
    () => createForm.requested_floor_id,
    () => {
        createForm.requested_room_id = "";
        createForm.requested_bed_id = "";
    },
);

watch(
    () => createForm.requested_room_id,
    () => {
        createForm.requested_bed_id = "";
    },
);

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();

    createOpen.value = true;
};

const submitRequest = () => {
    createForm.post(route("resident.room-change-requests.store"), {
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
            createForm.clearErrors();
        },
    });
};

const cancelRequest = (requestItem) => {
    if (
        !confirm(
            "Cancel this room-change request? This action cannot be reversed.",
        )
    ) {
        return;
    }

    router.post(
        route("resident.room-change-requests.cancel", {
            roomChangeRequest: requestItem.id,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
};

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    const date = String(value).includes("T")
        ? new Date(value)
        : new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const formatDateTime = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const money = (value) => {
    return Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2,
    });
};

const currentRoomLabel = (requestItem) => {
    const stay = requestItem.current_stay;

    if (!stay) {
        return "No previous stay available";
    }

    return [
        stay.building_name,
        stay.floor_name,
        stay.room_number ? `Room ${stay.room_number}` : null,
        stay.bed_number ? `Bed ${stay.bed_number}` : null,
    ]
        .filter(Boolean)
        .join(" · ");
};

const requestedRoomLabel = (requestItem) => {
    const room = requestItem.requested_room;

    if (!room) {
        return "No requested room";
    }

    return [
        room.building_name,
        room.floor_name,
        room.room_number ? `Room ${room.room_number}` : null,
        room.bed_number ? `Bed ${room.bed_number}` : null,
    ]
        .filter(Boolean)
        .join(" · ");
};
</script>

<template>
    <Head title="Room Change Requests" />

    <ResidentLayout title="Room Change Requests">
        <div class="space-y-6">
            <!-- Header -->
            <section
                class="overflow-hidden rounded-3xl border border-violet-200 bg-[linear-gradient(135deg,#4c1d95_0%,#7c3aed_55%,#a855f7_100%)] p-6 text-white shadow-xl"
            >
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                    <div>

                        <div class="flex items-center gap-3">

                            <div class="rounded-2xl bg-white/15 p-3">
                                <ArrowRightLeft class="h-7 w-7" />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-violet-100">
                                    Room Transfer
                                </p>

                                <h2 class="text-2xl font-bold">
                                    Room Change Requests
                                </h2>
                            </div>

                        </div>

                        <p class="mt-4 max-w-2xl text-sm leading-6 text-violet-50">
                            Request a different room, bed or building and
                            monitor approval from hostel administration.
                        </p>

                    </div>

                    <button
                        type="button"
                        :disabled="!currentStay || hasPendingRequest"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-violet-700 shadow-lg transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openCreateModal"
                    >
                        <Plus class="h-4 w-4" />

                        {{
                            hasPendingRequest
                                ? "Request Pending"
                                : "Request Room Change"
                        }}
                    </button>

                </div>
            </section>

            <!-- Current stay -->
            <section
                v-if="currentStay"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">
                        Current Room
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Your presently allotted room and billing information.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Building
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ currentStay.building_name || "—" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Floor
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ currentStay.floor_name || "—" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Room and Bed
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            Room
                            {{ currentStay.room_number || "—" }}
                            · Bed
                            {{ currentStay.bed_number || "—" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Billing Basis
                        </p>

                        <p
                            class="mt-1 text-sm font-semibold capitalize text-slate-900"
                        >
                            {{ currentStay.billing_basis || "monthly" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Current Charge
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            <template
                                v-if="currentStay.billing_basis === 'daily'"
                            >
                                {{ money(currentStay.daily_rate) }}
                                / day
                            </template>

                            <template v-else>
                                {{ money(currentStay.rent_amount) }}
                                / month
                            </template>
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-else
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"
            >
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div>
                    <p class="text-sm font-bold text-amber-900">
                        No active room allotment
                    </p>

                    <p class="mt-1 text-xs text-amber-700">
                        A room-change request can only be submitted after a room
                        has been allotted.
                    </p>
                </div>
            </section>

            <!-- Pending notice -->
            <section
                v-if="hasPendingRequest"
                class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4"
            >
                <Clock3 class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                <div>
                    <p class="text-sm font-bold text-blue-900">
                        You already have a pending request
                    </p>

                    <p class="mt-1 text-xs text-blue-700">
                        A second request cannot be submitted until the current
                        request is approved, rejected or cancelled.
                    </p>
                </div>
            </section>

            <!-- Stats -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <button
                    v-for="item in [
                        {
                            key: 'all',
                            label: 'Total',
                            count: stats.total,
                        },
                        {
                            key: 'pending',
                            label: 'Pending',
                            count: stats.pending,
                        },
                        {
                            key: 'approved',
                            label: 'Approved',
                            count: stats.approved,
                        },
                        {
                            key: 'rejected',
                            label: 'Rejected',
                            count: stats.rejected,
                        },
                        {
                            key: 'cancelled',
                            label: 'Cancelled',
                            count: stats.cancelled,
                        },
                    ]"
                    :key="item.key"
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.status === item.key
                            ? 'border-indigo-400 ring-2 ring-indigo-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.status = item.key;
                        applyFilters();
                    "
                >
                    <p class="text-2xl font-bold text-slate-900">
                        {{ item.count || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ item.label }}
                    </p>
                </button>
            </section>

            <!-- Filters -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search reason, building, room or admin notes"
                        />
                    </div>

                    <select
                        v-model="filterForm.status"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="applyFilters"
                    >
                        <option
                            v-for="status in statusOptions"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>

                    <button
                        v-if="filterForm.search || filterForm.status !== 'all'"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                        @click="clearFilters"
                    >
                        <X class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </section>

            <!-- Request cards -->
            <section
                v-if="requests.data?.length"
                class="grid grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <article
                    v-for="requestItem in requests.data"
                    :key="requestItem.id"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <ArrowRightLeft class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <h3
                                        class="text-base font-bold text-slate-900"
                                    >
                                        Room Change Request #{{
                                            requestItem.id
                                        }}
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Submitted
                                        {{
                                            formatDateTime(
                                                requestItem.created_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="statusClasses[requestItem.status]"
                            >
                                <component
                                    :is="statusIcon(requestItem.status)"
                                    class="h-3.5 w-3.5"
                                />

                                {{ requestItem.status_label }}
                            </span>
                        </div>

                        <!-- Room comparison -->
                        <div
                            class="mt-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3"
                        >
                            <div
                                class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Current
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold leading-6 text-slate-800"
                                >
                                    {{ currentRoomLabel(requestItem) }}
                                </p>
                            </div>

                            <ArrowRight
                                class="h-5 w-5 shrink-0 text-indigo-500"
                            />

                            <div
                                class="min-w-0 rounded-xl border border-indigo-200 bg-indigo-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wide text-indigo-500"
                                >
                                    Requested
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold leading-6 text-indigo-900"
                                >
                                    {{ requestedRoomLabel(requestItem) }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-xl border border-slate-200 p-4"
                        >
                            <p class="text-xs font-semibold text-slate-500">
                                Reason
                            </p>

                            <p
                                class="mt-2 line-clamp-3 whitespace-pre-line text-sm leading-6 text-slate-700"
                            >
                                {{
                                    requestItem.reason || "No reason provided."
                                }}
                            </p>
                        </div>

                        <!-- Approval details -->
                        <div
                            v-if="requestItem.status === 'approved'"
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle2
                                    class="h-5 w-5 text-emerald-700"
                                />

                                <p class="text-sm font-bold text-emerald-900">
                                    Transfer Approved
                                </p>
                            </div>

                            <div
                                class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <div>
                                    <p class="text-xs text-emerald-600">
                                        Effective From
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-semibold text-emerald-900"
                                    >
                                        {{
                                            formatDate(
                                                requestItem.effective_from,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-emerald-600">
                                        New Billing
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-semibold capitalize text-emerald-900"
                                    >
                                        {{ requestItem.new_billing_basis }}

                                        <template
                                            v-if="
                                                requestItem.new_billing_basis ===
                                                'daily'
                                            "
                                        >
                                            ·
                                            {{
                                                money(
                                                    requestItem.new_daily_rate,
                                                )
                                            }}
                                            / day
                                        </template>

                                        <template v-else>
                                            ·
                                            {{
                                                money(
                                                    requestItem.new_rent_amount,
                                                )
                                            }}
                                            / month
                                        </template>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="requestItem.admin_notes"
                            class="mt-4 rounded-xl border p-4"
                            :class="
                                requestItem.status === 'rejected'
                                    ? 'border-red-200 bg-red-50'
                                    : 'border-blue-200 bg-blue-50'
                            "
                        >
                            <p
                                class="text-xs font-semibold"
                                :class="
                                    requestItem.status === 'rejected'
                                        ? 'text-red-600'
                                        : 'text-blue-600'
                                "
                            >
                                Admin Notes
                            </p>

                            <p
                                class="mt-2 whitespace-pre-line text-sm leading-6"
                                :class="
                                    requestItem.status === 'rejected'
                                        ? 'text-red-800'
                                        : 'text-blue-800'
                                "
                            >
                                {{ requestItem.admin_notes }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-3"
                    >
                        <p class="text-[10px] text-slate-400">
                            Last updated
                            {{ formatDate(requestItem.updated_at) }}
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="requestItem.can_cancel"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                @click="cancelRequest(requestItem)"
                            >
                                <X class="h-4 w-4" />
                                Cancel
                            </button>

                            <Link
                                :href="
                                    route(
                                        'resident.room-change-requests.show',
                                        {
                                            roomChangeRequest: requestItem.id,
                                        },
                                    )
                                "
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700"
                            >
                                <Eye class="h-4 w-4" />
                                View Details
                            </Link>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Empty -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <ArrowRightLeft class="mx-auto h-12 w-12 text-slate-300" />

                <h3 class="mt-4 text-base font-bold text-slate-700">
                    No room-change requests found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Submit a request when you need to move to another room.
                </p>

                <button
                    v-if="currentStay && !hasPendingRequest"
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="openCreateModal"
                >
                    <Plus class="h-4 w-4" />
                    Request Room Change
                </button>
            </section>

            <!-- Pagination -->
            <div
                v-if="requests.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template v-for="link in requests.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-lg px-3 py-2 text-xs font-medium"
                        :class="
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-slate-600 hover:bg-slate-100'
                        "
                        preserve-scroll
                    />

                    <span
                        v-else
                        v-html="link.label"
                        class="cursor-not-allowed rounded-lg bg-white px-3 py-2 text-xs text-slate-300"
                    />
                </template>
            </div>
        </div>

        <!-- Create request modal -->
        <Modal :show="createOpen" maxWidth="2xl" @close="createOpen = false">
            <form
                class="flex max-h-[90vh] flex-col overflow-hidden"
                @submit.prevent="submitRequest"
            >
                <div
                    class="flex shrink-0 items-start justify-between border-b border-slate-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Request Room Change
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Choose a vacant bed and explain why you would like
                            to move.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="createOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <!-- Current room preview -->
                    <div
                        v-if="currentStay"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Current Room
                        </p>

                        <div
                            class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <Building2 class="h-4 w-4 text-slate-400" />

                                {{ currentStay.building_name }}
                            </span>

                            <span class="inline-flex items-center gap-1.5">
                                <BedDouble class="h-4 w-4 text-slate-400" />

                                Room
                                {{ currentStay.room_number }}
                                · Bed
                                {{ currentStay.bed_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Building and floor -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Requested Building *" />

                            <select
                                v-model="createForm.requested_building_id"
                                required
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" disabled>
                                    Select building
                                </option>

                                <option
                                    v-for="building in buildings"
                                    :key="building.id"
                                    :value="building.id"
                                >
                                    {{ building.name }}
                                </option>
                            </select>

                            <InputError
                                class="mt-1"
                                :message="
                                    createForm.errors.requested_building_id
                                "
                            />
                        </div>

                        <div>
                            <InputLabel value="Requested Floor *" />

                            <select
                                v-model="createForm.requested_floor_id"
                                required
                                :disabled="!createForm.requested_building_id"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100"
                            >
                                <option value="" disabled>Select floor</option>

                                <option
                                    v-for="floor in floorsForBuilding"
                                    :key="floor.id"
                                    :value="floor.id"
                                >
                                    {{ floor.name }}
                                </option>
                            </select>

                            <InputError
                                class="mt-1"
                                :message="createForm.errors.requested_floor_id"
                            />
                        </div>
                    </div>

                    <!-- Room and bed -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Requested Room *" />

                            <select
                                v-model="createForm.requested_room_id"
                                required
                                :disabled="!createForm.requested_floor_id"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100"
                            >
                                <option value="" disabled>Select room</option>

                                <option
                                    v-for="room in roomsForFloor"
                                    :key="room.id"
                                    :value="room.id"
                                    :disabled="
                                        room.occupied_beds >= room.capacity
                                    "
                                >
                                    Room
                                    {{ room.room_number }}
                                    ·
                                    {{ room.room_type }}
                                    ·
                                    {{ room.occupied_beds }}/{{ room.capacity }}
                                    occupied
                                </option>
                            </select>

                            <InputError
                                class="mt-1"
                                :message="createForm.errors.requested_room_id"
                            />
                        </div>

                        <div>
                            <InputLabel value="Requested Bed *" />

                            <select
                                v-model="createForm.requested_bed_id"
                                required
                                :disabled="!createForm.requested_room_id"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100"
                            >
                                <option value="" disabled>
                                    Select vacant bed
                                </option>

                                <option
                                    v-for="bed in vacantBeds"
                                    :key="bed.id"
                                    :value="bed.id"
                                >
                                    {{ bed.bed_number }}
                                </option>
                            </select>

                            <InputError
                                class="mt-1"
                                :message="createForm.errors.requested_bed_id"
                            />
                        </div>
                    </div>

                    <!-- Selected room preview -->
                    <div
                        v-if="selectedRoom && selectedBed"
                        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-indigo-500"
                        >
                            Selected New Room
                        </p>

                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs text-indigo-500">Location</p>

                                <p
                                    class="mt-1 text-sm font-semibold text-indigo-900"
                                >
                                    {{ selectedBuilding?.name }}
                                    ·
                                    {{ selectedFloor?.name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-indigo-500">
                                    Room and Bed
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-indigo-900"
                                >
                                    Room
                                    {{ selectedRoom.room_number }}
                                    · Bed
                                    {{ selectedBed.bed_number }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-indigo-500">
                                    Standard Rent
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-indigo-900"
                                >
                                    {{
                                        money(selectedRoom.monthly_rent_per_bed)
                                    }}
                                    / month
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-indigo-700">
                            Final billing amount and move date will be confirmed
                            by the hostel administration during approval.
                        </p>
                    </div>

                    <!-- Reason -->
                    <div>
                        <InputLabel value="Reason for Room Change *" />

                        <textarea
                            v-model="createForm.reason"
                            rows="6"
                            required
                            maxlength="3000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain why you want to change your current room..."
                        ></textarea>

                        <div
                            class="mt-1 flex items-start justify-between gap-4"
                        >
                            <InputError :message="createForm.errors.reason" />

                            <span class="text-[10px] text-slate-400">
                                {{ createForm.reason.length }}/3000
                            </span>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-700"
                    >
                        The selected bed is only a preference until the
                        administration approves the request. Availability and
                        billing will be checked again at approval time.
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="createForm.processing">
                        {{
                            createForm.processing
                                ? "Submitting..."
                                : "Submit Request"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>
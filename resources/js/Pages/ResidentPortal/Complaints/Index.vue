<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    Armchair,
    ArrowRight,
    Eraser,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Droplets,
    Eye,
    Filter,
    Flame,
    MapPin,
    MessageSquareWarning,
    Plus,
    Search,
    ShieldCheck,
    Sparkles,
    Star,
    Trash2,
    Utensils,
    Wifi,
    X,
    XCircle,
    Zap,
} from "lucide-vue-next";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    complaints: {
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
});

const createOpen = ref(false);
const filterOpen = ref(false);

const filterForm = reactive({
    search: props.filters?.search || "",
    status: props.filters?.status || "all",
    priority: props.filters?.priority || "all",
    category: props.filters?.category || "all",
});

let searchTimer = null;

const createForm = useForm({
    category: "electrical",
    priority: "medium",
    title: "",
    description: "",
});

const categories = [
    {
        value: "electrical",
        label: "Electrical",
        description: "Fan, light, switch, socket or power issue",
        icon: Zap,
    },
    {
        value: "plumbing",
        label: "Plumbing",
        description: "Water supply, leakage, tap or drainage issue",
        icon: Droplets,
    },
    {
        value: "furniture",
        label: "Furniture",
        description: "Bed, chair, table, cupboard or fixture issue",
        icon: Armchair,
    },
    {
        value: "wifi",
        label: "Wi-Fi",
        description: "Internet speed, connectivity or network issue",
        icon: Wifi,
    },
    {
        value: "cleaning",
        label: "Cleaning",
        description: "Room, bathroom or common-area cleanliness",
        icon: Eraser,
    },
    {
        value: "security",
        label: "Security",
        description: "Safety, lock, access or security concern",
        icon: ShieldCheck,
    },
    {
        value: "food",
        label: "Food / Mess",
        description: "Food quality, quantity or mess-related issue",
        icon: Utensils,
    },
    {
        value: "other",
        label: "Other",
        description: "Any issue not covered by another category",
        icon: Sparkles,
    },
];

const priorities = [
    {
        value: "low",
        label: "Low",
        description: "Can be handled when convenient",
        className: "border-slate-200 bg-slate-50 text-slate-700",
    },
    {
        value: "medium",
        label: "Medium",
        description: "Needs attention within normal working time",
        className: "border-blue-200 bg-blue-50 text-blue-700",
    },
    {
        value: "high",
        label: "High",
        description: "Significantly affecting daily use",
        className: "border-amber-200 bg-amber-50 text-amber-700",
    },
    {
        value: "urgent",
        label: "Urgent",
        description: "Safety risk or essential service unavailable",
        className: "border-red-200 bg-red-50 text-red-700",
    },
];

const statusOptions = [
    {
        value: "all",
        label: "All",
    },
    {
        value: "open",
        label: "Open",
    },
    {
        value: "in_progress",
        label: "In Progress",
    },
    {
        value: "resolved",
        label: "Resolved",
    },
    {
        value: "escalated",
        label: "Escalated",
    },
    {
        value: "rejected",
        label: "Rejected",
    },
];

const statusClasses = {
    open: "border-amber-200 bg-amber-50 text-amber-700",
    in_progress: "border-blue-200 bg-blue-50 text-blue-700",
    resolved: "border-emerald-200 bg-emerald-50 text-emerald-700",
    escalated: "border-red-200 bg-red-50 text-red-700",
    rejected: "border-slate-200 bg-slate-100 text-slate-600",
};

const priorityClasses = {
    low: "bg-slate-100 text-slate-600",
    medium: "bg-blue-100 text-blue-700",
    high: "bg-amber-100 text-amber-700",
    urgent: "bg-red-100 text-red-700",
};

const statusIcon = (status) => {
    if (status === "resolved") {
        return CheckCircle2;
    }

    if (status === "rejected") {
        return XCircle;
    }

    if (status === "escalated") {
        return AlertTriangle;
    }

    if (status === "in_progress") {
        return Clock3;
    }

    return CircleAlert;
};

const categoryIcon = (category) => {
    return (
        categories.find((item) => item.value === category)?.icon ||
        MessageSquareWarning
    );
};

const activeFiltersCount = computed(() => {
    let count = 0;

    if (filterForm.status !== "all") {
        count++;
    }

    if (filterForm.priority !== "all") {
        count++;
    }

    if (filterForm.category !== "all") {
        count++;
    }

    return count;
});

const applyFilters = () => {
    router.get(
        route("resident.complaints.index"),
        {
            search: filterForm.search || undefined,

            status: filterForm.status !== "all" ? filterForm.status : undefined,

            priority:
                filterForm.priority !== "all" ? filterForm.priority : undefined,

            category:
                filterForm.category !== "all" ? filterForm.category : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterForm.search = "";
    filterForm.status = "all";
    filterForm.priority = "all";
    filterForm.category = "all";

    router.get(
        route("resident.complaints.index"),
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

const submitComplaint = () => {
    createForm.post(route("resident.complaints.store"), {
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();

            createForm.category = "electrical";

            createForm.priority = "medium";
        },
    });
};

const destroyComplaint = (complaint) => {
    if (!confirm("Delete this complaint? This action cannot be reversed.")) {
        return;
    }

    router.delete(
        route("resident.complaints.destroy", {
            complaint: complaint.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const formatDate = (value) => {
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

const complaintLocation = (complaint) => {
    const parts = [];

    if (complaint.building?.name) {
        parts.push(complaint.building.name);
    }

    if (complaint.room?.room_number) {
        parts.push(`Room ${complaint.room.room_number}`);
    }

    return parts.length ? parts.join(" · ") : "No room linked";
};
</script>

<template>
    <Head title="Complaints" />

    <ResidentLayout title="Complaints">
        <div class="space-y-6">
            <!-- Header -->
            <section
                class="overflow-hidden rounded-3xl border border-orange-200 bg-[linear-gradient(135deg,#9a3412_0%,#ea580c_55%,#fb923c_100%)] p-6 text-white shadow-xl"
            >
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                    <div>
                        <div class="flex items-center gap-3">

                            <div class="rounded-2xl bg-white/15 p-3">
                                <MessageSquareWarning class="h-7 w-7" />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-100">
                                    Complaint Portal
                                </p>

                                <h2 class="text-2xl font-bold">
                                    My Complaints
                                </h2>
                            </div>

                        </div>

                        <p class="mt-4 max-w-2xl text-sm leading-6 text-orange-50">
                            Report maintenance issues, cleanliness problems,
                            electrical faults and track their resolution.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-orange-700 shadow-lg transition hover:scale-105"
                        @click="createOpen = true"
                    >
                        <Plus class="h-4 w-4" />
                        Raise Complaint
                    </button>

                </div>
            </section>

            <!-- Current stay notice -->
            <section
                v-if="currentStay"
                class="flex flex-col gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600"
                    >
                        <MapPin class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-sm font-bold text-blue-900">
                            Complaint location
                        </p>

                        <p class="mt-1 text-xs text-blue-700">
                            {{ currentStay.building_name || "Building" }}
                            · Room
                            {{ currentStay.room_number || "—" }}
                            · Bed
                            {{ currentStay.bed_number || "—" }}
                        </p>
                    </div>
                </div>

                <p class="text-xs text-blue-700">
                    Your current building and room will be attached
                    automatically.
                </p>
            </section>

            <section
                v-else
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"
            >
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div>
                    <p class="text-sm font-bold text-amber-900">
                        No current room is linked
                    </p>

                    <p class="mt-1 text-xs text-amber-700">
                        You can still submit a complaint, but it will not
                        contain building or room information.
                    </p>
                </div>
            </section>

            <!-- Summary cards -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.status === 'all'
                            ? 'border-indigo-400 ring-2 ring-indigo-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.status = 'all';
                        applyFilters();
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                    >
                        <MessageSquareWarning class="h-5 w-5" />
                    </div>

                    <p class="mt-3 text-2xl font-bold text-slate-900">
                        {{ stats.total || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Total Complaints</p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.status === 'open'
                            ? 'border-amber-400 ring-2 ring-amber-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.status = 'open';
                        applyFilters();
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                    >
                        <CircleAlert class="h-5 w-5" />
                    </div>

                    <p class="mt-3 text-2xl font-bold text-amber-700">
                        {{ stats.open || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Open</p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.status === 'in_progress'
                            ? 'border-blue-400 ring-2 ring-blue-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.status = 'in_progress';
                        applyFilters();
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                    >
                        <Clock3 class="h-5 w-5" />
                    </div>

                    <p class="mt-3 text-2xl font-bold text-blue-700">
                        {{ stats.in_progress || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">In Progress</p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.status === 'resolved'
                            ? 'border-emerald-400 ring-2 ring-emerald-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.status = 'resolved';
                        applyFilters();
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >
                        <CheckCircle2 class="h-5 w-5" />
                    </div>

                    <p class="mt-3 text-2xl font-bold text-emerald-700">
                        {{ stats.resolved || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Resolved</p>
                </button>
            </section>

            <!-- Urgent warning -->
            <section
                v-if="Number(stats.urgent_active || 0) > 0"
                class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4"
            >
                <Flame class="mt-0.5 h-5 w-5 shrink-0 text-red-700" />

                <div>
                    <p class="text-sm font-bold text-red-900">
                        {{ stats.urgent_active }}
                        urgent complaint{{
                            Number(stats.urgent_active) === 1 ? "" : "s"
                        }}
                        currently active
                    </p>

                    <p class="mt-1 text-xs text-red-700">
                        Urgent complaints are highlighted for faster attention.
                    </p>
                </div>
            </section>

            <!-- Filters -->
            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between"
                >
                    <div class="relative w-full md:max-w-md">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search title, description or resolution"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
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
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="filterOpen = !filterOpen"
                        >
                            <Filter class="h-4 w-4" />
                            Filters

                            <span
                                v-if="activeFiltersCount > 0"
                                class="flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 text-[10px] font-bold text-indigo-700"
                            >
                                {{ activeFiltersCount }}
                            </span>
                        </button>

                        <button
                            v-if="filterForm.search || activeFiltersCount > 0"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                            @click="clearFilters"
                        >
                            <X class="h-4 w-4" />
                            Clear
                        </button>
                    </div>
                </div>

                <div
                    v-if="filterOpen"
                    class="grid grid-cols-1 gap-4 border-t border-slate-100 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Priority
                        </label>

                        <select
                            v-model="filterForm.priority"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All Priorities</option>

                            <option
                                v-for="priority in priorities"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Category
                        </label>

                        <select
                            v-model="filterForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">All Categories</option>

                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                            @click="applyFilters"
                        >
                            Apply Filters
                        </button>
                    </div>
                </div>
            </section>

            <!-- Complaint cards -->
            <section
                v-if="complaints.data?.length"
                class="grid grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <article
                    v-for="complaint in complaints.data"
                    :key="complaint.id"
                    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        complaint.priority === 'urgent' &&
                        !['resolved', 'rejected'].includes(complaint.status)
                            ? 'border-red-300'
                            : 'border-slate-200'
                    "
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <component
                                        :is="categoryIcon(complaint.category)"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <h3
                                        class="truncate text-base font-bold text-slate-900"
                                    >
                                        {{ complaint.title }}
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ complaint.category_label }}
                                        ·
                                        {{
                                            formatDateTime(complaint.created_at)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="statusClasses[complaint.status]"
                            >
                                <component
                                    :is="statusIcon(complaint.status)"
                                    class="h-3.5 w-3.5"
                                />

                                {{ complaint.status_label }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold"
                                :class="priorityClasses[complaint.priority]"
                            >
                                {{ complaint.priority_label }}
                                Priority
                            </span>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600"
                            >
                                <MapPin class="h-3 w-3" />

                                {{ complaintLocation(complaint) }}
                            </span>
                        </div>

                        <p
                            class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600"
                        >
                            {{ complaint.description }}
                        </p>

                        <div
                            v-if="complaint.assigned_to"
                            class="mt-4 rounded-xl bg-blue-50 px-4 py-3"
                        >
                            <p class="text-xs text-blue-500">Assigned To</p>

                            <p class="mt-1 text-sm font-semibold text-blue-900">
                                {{ complaint.assigned_to.name }}
                            </p>
                        </div>

                        <div
                            v-if="
                                complaint.status === 'resolved' &&
                                complaint.resolution_notes
                            "
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-emerald-600"
                            >
                                Resolution
                            </p>

                            <p
                                class="mt-1 line-clamp-2 text-sm text-emerald-800"
                            >
                                {{ complaint.resolution_notes }}
                            </p>
                        </div>

                        <div
                            v-if="complaint.rating"
                            class="mt-4 flex items-center gap-1"
                        >
                            <Star
                                v-for="star in 5"
                                :key="star"
                                class="h-4 w-4"
                                :class="
                                    star <= complaint.rating
                                        ? 'fill-amber-400 text-amber-400'
                                        : 'text-slate-300'
                                "
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-3"
                    >
                        <p class="text-[10px] text-slate-400">
                            Last updated
                            {{ formatDate(complaint.updated_at) }}
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="complaint.can_delete"
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50"
                                title="Delete complaint"
                                @click="destroyComplaint(complaint)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>

                            <Link
                                :href="
                                    route('resident.complaints.show', {
                                        complaint: complaint.id,
                                    })
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

            <!-- Empty state -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <MessageSquareWarning
                    class="mx-auto h-12 w-12 text-slate-300"
                />

                <h3 class="mt-4 text-base font-bold text-slate-700">
                    No complaints found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Raise a complaint whenever you need hostel assistance.
                </p>

                <button
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="createOpen = true"
                >
                    <Plus class="h-4 w-4" />
                    Raise Complaint
                </button>
            </section>

            <!-- Pagination -->
            <div
                v-if="complaints.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template v-for="link in complaints.links" :key="link.label">
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

        <!-- Create complaint modal -->
        <Modal :show="createOpen" maxWidth="2xl" @close="createOpen = false">
            <form
                class="overflow-y-auto"
                @submit.prevent="submitComplaint"
            >
                <div
                    class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Raise Complaint
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Describe the problem clearly so the support team can
                            resolve it quickly.
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

                <div class="space-y-6 p-6">
                    <div
                        v-if="currentStay"
                        class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4"
                    >
                        <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />

                        <div>
                            <p class="text-sm font-bold text-blue-900">
                                Complaint will be linked to
                            </p>

                            <p class="mt-1 text-xs text-blue-700">
                                {{ currentStay.building_name }}
                                · Room
                                {{ currentStay.room_number }}
                                · Bed
                                {{ currentStay.bed_number || "—" }}
                            </p>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <InputLabel value="Complaint Category *" />

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label
                                v-for="category in categories"
                                :key="category.value"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                                :class="
                                    createForm.category === category.value
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                                        : 'border-slate-200 hover:border-indigo-200'
                                "
                            >
                                <input
                                    v-model="createForm.category"
                                    type="radio"
                                    :value="category.value"
                                    class="sr-only"
                                />

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm"
                                >
                                    <component
                                        :is="category.icon"
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ category.label }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-4 text-slate-500"
                                    >
                                        {{ category.description }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="createForm.errors.category"
                        />
                    </div>

                    <!-- Priority -->
                    <div>
                        <InputLabel value="Priority *" />

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label
                                v-for="priority in priorities"
                                :key="priority.value"
                                class="cursor-pointer rounded-xl border p-4 transition"
                                :class="
                                    createForm.priority === priority.value
                                        ? `${priority.className} ring-2 ring-offset-1`
                                        : 'border-slate-200 bg-white hover:border-indigo-200'
                                "
                            >
                                <input
                                    v-model="createForm.priority"
                                    type="radio"
                                    :value="priority.value"
                                    class="sr-only"
                                />

                                <p class="text-sm font-bold">
                                    {{ priority.label }}
                                </p>

                                <p class="mt-1 text-xs opacity-80">
                                    {{ priority.description }}
                                </p>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="createForm.errors.priority"
                        />
                    </div>

                    <!-- Title -->
                    <div>
                        <InputLabel value="Complaint Title *" />

                        <input
                            v-model="createForm.title"
                            type="text"
                            required
                            maxlength="200"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Example: Ceiling fan is not working"
                        />

                        <div class="mt-1 flex items-center justify-between">
                            <InputError :message="createForm.errors.title" />

                            <span class="text-[10px] text-slate-400">
                                {{ createForm.title.length }}/200
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel value="Description *" />

                        <textarea
                            v-model="createForm.description"
                            rows="6"
                            required
                            maxlength="5000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain the problem, when it started, and any other useful details"
                        ></textarea>

                        <div class="mt-1 flex items-center justify-between">
                            <InputError
                                :message="createForm.errors.description"
                            />

                            <span class="text-[10px] text-slate-400">
                                {{ createForm.description.length }}/5000
                            </span>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <p class="text-xs leading-5 text-amber-700">
                            Use
                            <strong>Urgent</strong>
                            only for safety risks, complete power or water loss,
                            security incidents, or another issue that requires
                            immediate attention.
                        </p>
                    </div>
                </div>

                <div
                    class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4"
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
                                : "Submit Complaint"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>

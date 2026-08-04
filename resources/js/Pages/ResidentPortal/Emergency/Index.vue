<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertOctagon,
    AlertTriangle,
    Brain,
    CheckCircle2,
    ChevronRight,
    CircleAlert,
    Clock3,
    Flame,
    HeartPulse,
    HelpCircle,
    Home,
    MapPin,
    MessageSquareText,
    ShieldAlert,
    Siren,
    Sparkles,
    Utensils,
    UserRoundX,
    Waves,
    X,
} from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps({
    alerts: {
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

    hasActiveAlert: {
        type: Boolean,
        default: false,
    },
});

const createOpen = ref(false);
const confirmOpen = ref(false);

const filterStatus = ref(props.filters?.status || "all");

const filterCategory = ref(props.filters?.category || "all");

const form = useForm({
    category: "medical",
    description: "",
    location: "",
});

const categories = [
    {
        value: "medical",
        label: "Medical Emergency",
        description:
            "Injury, severe illness, breathing issue, unconsciousness or urgent medical help.",
        icon: HeartPulse,
        className: "border-red-200 bg-red-50 text-red-700",
    },
    {
        value: "fire",
        label: "Fire",
        description:
            "Fire, smoke, burning smell, electrical spark or gas-related danger.",
        icon: Flame,
        className: "border-orange-200 bg-orange-50 text-orange-700",
    },
    {
        value: "theft",
        label: "Theft",
        description:
            "Theft, missing belongings, suspicious person or attempted break-in.",
        icon: ShieldAlert,
        className: "border-amber-200 bg-amber-50 text-amber-700",
    },
    {
        value: "stuck_in_lift",
        label: "Stuck in Lift",
        description: "Lift is stuck or you are unable to exit safely.",
        icon: AlertOctagon,
        className: "border-purple-200 bg-purple-50 text-purple-700",
    },
    {
        value: "need_food",
        label: "Need Food",
        description:
            "Food is urgently required due to illness, isolation or another emergency.",
        icon: Utensils,
        className: "border-green-200 bg-green-50 text-green-700",
    },
    {
        value: "disaster",
        label: "Disaster",
        description:
            "Flooding, earthquake, structural damage or another major emergency.",
        icon: Waves,
        className: "border-blue-200 bg-blue-50 text-blue-700",
    },
    {
        value: "domestic_violence",
        label: "Domestic Violence",
        description:
            "Immediate support is needed regarding abuse or domestic violence.",
        icon: UserRoundX,
        className: "border-rose-200 bg-rose-50 text-rose-700",
    },
    {
        value: "threat",
        label: "Threat",
        description: "Someone is threatening, stalking or intimidating you.",
        icon: ShieldAlert,
        className: "border-red-200 bg-red-50 text-red-700",
    },
    {
        value: "violence",
        label: "Violence",
        description:
            "Physical fight, assault or violent behaviour is occurring.",
        icon: AlertTriangle,
        className: "border-red-200 bg-red-50 text-red-700",
    },
    {
        value: "suicidal",
        label: "Self-Harm Emergency",
        description:
            "You or someone nearby may be at immediate risk of self-harm.",
        icon: Brain,
        className: "border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700",
    },
    {
        value: "mental_depression",
        label: "Mental Health Emergency",
        description:
            "Severe distress, panic, depression or urgent emotional support is needed.",
        icon: Brain,
        className: "border-indigo-200 bg-indigo-50 text-indigo-700",
    },
    {
        value: "others",
        label: "Other Emergency",
        description:
            "Any urgent situation that does not match the available categories.",
        icon: HelpCircle,
        className: "border-slate-200 bg-slate-50 text-slate-700",
    },
];

const statusOptions = [
    {
        value: "all",
        label: "All Alerts",
    },
    {
        value: "active",
        label: "Active",
    },
    {
        value: "escalated",
        label: "Escalated",
    },
    {
        value: "resolved",
        label: "Resolved",
    },
];

const statusClasses = {
    active: "border-red-200 bg-red-50 text-red-700",
    escalated: "border-amber-200 bg-amber-50 text-amber-700",
    resolved: "border-emerald-200 bg-emerald-50 text-emerald-700",
};

const statusIcon = (status) => {
    if (status === "resolved") {
        return CheckCircle2;
    }

    if (status === "escalated") {
        return AlertTriangle;
    }

    return Siren;
};

const categoryIcon = (category) => {
    return (
        categories.find((item) => item.value === category)?.icon || CircleAlert
    );
};

const selectedCategory = computed(() => {
    return categories.find((category) => category.value === form.category);
});

const applyFilters = () => {
    router.get(
        route("resident.emergency.index"),
        {
            status:
                filterStatus.value !== "all" ? filterStatus.value : undefined,

            category:
                filterCategory.value !== "all"
                    ? filterCategory.value
                    : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterStatus.value = "all";
    filterCategory.value = "all";

    router.get(
        route("resident.emergency.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openAlertModal = () => {
    form.reset();
    form.clearErrors();

    form.category = "medical";
    form.location = props.currentStay
        ? [
              props.currentStay.building_name,
              props.currentStay.room_number
                  ? `Room ${props.currentStay.room_number}`
                  : null,
              props.currentStay.bed_number
                  ? `Bed ${props.currentStay.bed_number}`
                  : null,
          ]
              .filter(Boolean)
              .join(" · ")
        : "";

    createOpen.value = true;
};

const requestSubmitConfirmation = () => {
    form.clearErrors();

    if (!form.category) {
        form.setError("category", "Please select an emergency category.");

        return;
    }

    createOpen.value = false;
    confirmOpen.value = true;
};

const submitAlert = () => {
    form.post(route("resident.emergency.store"), {
        preserveScroll: true,

        onSuccess: () => {
            confirmOpen.value = false;
            createOpen.value = false;

            form.reset();
            form.clearErrors();
        },

        onError: () => {
            confirmOpen.value = false;
            createOpen.value = true;
        },
    });
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

const activeAlert = computed(() => {
    return props.alerts.data?.find((alert) =>
        ["active", "escalated"].includes(alert.status),
    );
});
</script>

<template>
    <Head title="Emergency SOS" />

    <ResidentLayout title="Emergency SOS">
        <div class="space-y-6">
            <!-- Emergency hero -->
            <section
                class="overflow-hidden rounded-3xl border border-red-200 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 text-white shadow-xl"
            >
                <div
                    class="grid grid-cols-1 gap-8 p-6 md:grid-cols-[1fr_auto] md:items-center md:p-8"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15"
                            >
                                <Siren class="h-8 w-8" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.2em] text-red-100"
                                >
                                    Emergency Assistance
                                </p>

                                <h1 class="mt-1 text-2xl font-bold md:text-3xl">
                                    Need urgent help?
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-6 text-red-100"
                        >
                            Raise an SOS alert only when immediate assistance is
                            required. Your resident details and current hostel
                            location will be shared with the administration.
                        </p>

                        <div
                            v-if="currentStay"
                            class="mt-5 inline-flex flex-wrap items-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-xs text-red-50"
                        >
                            <MapPin class="h-4 w-4" />

                            {{
                                currentStay.building_name || "Current Building"
                            }}

                            <span>·</span>

                            Room
                            {{ currentStay.room_number || "—" }}

                            <span>·</span>

                            Bed
                            {{ currentStay.bed_number || "—" }}
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="hasActiveAlert"
                        class="group relative flex h-36 w-36 shrink-0 flex-col items-center justify-center rounded-full border-8 border-white/20 bg-white text-red-700 shadow-2xl transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60 md:h-40 md:w-40"
                        @click="openAlertModal"
                    >
                        <Siren
                            class="h-10 w-10 transition group-hover:scale-110"
                        />

                        <span class="mt-2 text-lg font-black"> SOS </span>

                        <span
                            class="mt-1 text-[10px] font-semibold uppercase tracking-wider"
                        >
                            {{
                                hasActiveAlert ? "Alert Active" : "Raise Alert"
                            }}
                        </span>
                    </button>
                </div>
            </section>

            <!-- Active alert -->
            <section
                v-if="activeAlert"
                class="overflow-hidden rounded-2xl border border-red-300 bg-red-50 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700"
                        >
                            <component
                                :is="statusIcon(activeAlert.status)"
                                class="h-6 w-6"
                            />
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-red-900">
                                    {{ activeAlert.category_label }}
                                </h2>

                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                    :class="statusClasses[activeAlert.status]"
                                >
                                    {{ activeAlert.status_label }}
                                </span>
                            </div>

                            <p class="mt-1 text-xs text-red-700">
                                Raised
                                {{ formatDateTime(activeAlert.created_at) }}
                                ·
                                {{
                                    activeAlert.location ||
                                    "Location not provided"
                                }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-red-800">
                                {{
                                    activeAlert.description ||
                                    "Emergency assistance requested."
                                }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="
                            route('resident.emergency.show', {
                                alert: activeAlert.id,
                            })
                        "
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
                    >
                        Track Alert
                        <ChevronRight class="h-4 w-4" />
                    </Link>
                </div>
            </section>

            <!-- Safety warning -->
            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <AlertTriangle class="h-6 w-6 text-amber-700" />

                    <h3 class="mt-3 text-sm font-bold text-amber-900">
                        Use for genuine emergencies
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-amber-700">
                        Routine maintenance and normal complaints should be
                        submitted from the Complaints section.
                    </p>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <MapPin class="h-6 w-6 text-blue-700" />

                    <h3 class="mt-3 text-sm font-bold text-blue-900">
                        Mention your exact location
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-blue-700">
                        Update the location field if you are not currently in
                        your assigned room.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                >
                    <MessageSquareText class="h-6 w-6 text-emerald-700" />

                    <h3 class="mt-3 text-sm font-bold text-emerald-900">
                        Give brief, useful details
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-emerald-700">
                        Describe what happened and whether anyone is injured or
                        in immediate danger.
                    </p>
                </div>
            </section>

            <!-- Stats -->
            <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <button
                    v-for="item in [
                        {
                            key: 'all',
                            label: 'Total Alerts',
                            count: stats.total,
                            icon: Siren,
                        },
                        {
                            key: 'active',
                            label: 'Active',
                            count: stats.active,
                            icon: CircleAlert,
                        },
                        {
                            key: 'escalated',
                            label: 'Escalated',
                            count: stats.escalated,
                            icon: AlertTriangle,
                        },
                        {
                            key: 'resolved',
                            label: 'Resolved',
                            count: stats.resolved,
                            icon: CheckCircle2,
                        },
                    ]"
                    :key="item.key"
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterStatus === item.key
                            ? 'border-red-400 ring-2 ring-red-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterStatus = item.key;
                        applyFilters();
                    "
                >
                    <component :is="item.icon" class="h-5 w-5 text-red-600" />

                    <p class="mt-3 text-2xl font-bold text-slate-900">
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
                    <select
                        v-model="filterStatus"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 sm:w-auto"
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

                    <select
                        v-model="filterCategory"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 sm:w-auto"
                        @change="applyFilters"
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

                    <button
                        v-if="
                            filterStatus !== 'all' || filterCategory !== 'all'
                        "
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        @click="clearFilters"
                    >
                        <X class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </section>

            <!-- History -->
            <section v-if="alerts.data?.length" class="space-y-4">
                <article
                    v-for="alert in alerts.data"
                    :key="alert.id"
                    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-md"
                    :class="
                        alert.status === 'active'
                            ? 'border-red-300'
                            : alert.status === 'escalated'
                              ? 'border-amber-300'
                              : 'border-slate-200'
                    "
                >
                    <div class="p-5">
                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"
                                >
                                    <component
                                        :is="categoryIcon(alert.category)"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div>
                                    <h3
                                        class="text-base font-bold text-slate-900"
                                    >
                                        {{ alert.category_label }}
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ formatDateTime(alert.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="statusClasses[alert.status]"
                            >
                                <component
                                    :is="statusIcon(alert.status)"
                                    class="h-3.5 w-3.5"
                                />

                                {{ alert.status_label }}
                            </span>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-slate-400">Location</p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    {{ alert.location || "Not provided" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Assigned To
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    {{
                                        alert.assigned_to || "Not assigned yet"
                                    }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="alert.description"
                            class="mt-4 line-clamp-3 whitespace-pre-line text-sm leading-6 text-slate-600"
                        >
                            {{ alert.description }}
                        </p>

                        <div
                            v-if="
                                alert.status === 'resolved' &&
                                alert.resolution_notes
                            "
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-emerald-600"
                            >
                                Resolution
                            </p>

                            <p
                                class="mt-2 line-clamp-2 text-sm text-emerald-800"
                            >
                                {{ alert.resolution_notes }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-3"
                    >
                        <p class="text-[10px] text-slate-400">
                            Alert #{{ alert.id }}
                        </p>

                        <Link
                            :href="
                                route('resident.emergency.show', {
                                    alert: alert.id,
                                })
                            "
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-red-300 hover:text-red-700"
                        >
                            View Details
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>
                </article>
            </section>

            <!-- Empty -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <ShieldAlert class="mx-auto h-12 w-12 text-slate-300" />

                <h3 class="mt-4 text-base font-bold text-slate-700">
                    No emergency alerts found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Your emergency alert history will appear here.
                </p>
            </section>

            <!-- Pagination -->
            <div
                v-if="alerts.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template v-for="link in alerts.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-lg px-3 py-2 text-xs font-medium"
                        :class="
                            link.active
                                ? 'bg-red-600 text-white'
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

        <!-- Create SOS modal -->
        <Modal :show="createOpen" maxWidth="2xl" @close="createOpen = false">
            <form
                class="flex max-h-[90vh] flex-col overflow-hidden"
                @submit.prevent="requestSubmitConfirmation"
            >
                <div
                    class="flex shrink-0 items-start justify-between border-b border-red-100 bg-red-50 px-6 py-5"
                >
                    <div>
                        <h2
                            class="flex items-center gap-2 text-lg font-bold text-red-900"
                        >
                            <Siren class="h-5 w-5" />
                            Raise Emergency Alert
                        </h2>

                        <p class="mt-1 text-xs text-red-700">
                            Select the emergency type and give your exact
                            location.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-red-400 hover:bg-red-100"
                        @click="createOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <div>
                        <InputLabel value="Emergency Category *" />

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label
                                v-for="category in categories"
                                :key="category.value"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                                :class="
                                    form.category === category.value
                                        ? `${category.className} ring-2 ring-offset-1`
                                        : 'border-slate-200 bg-white hover:border-red-200'
                                "
                            >
                                <input
                                    v-model="form.category"
                                    type="radio"
                                    :value="category.value"
                                    class="sr-only"
                                />

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm"
                                >
                                    <component
                                        :is="category.icon"
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div>
                                    <p class="text-sm font-bold">
                                        {{ category.label }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-5 opacity-80"
                                    >
                                        {{ category.description }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="form.errors.category"
                        />
                    </div>

                    <div>
                        <InputLabel value="Current Location" />

                        <div class="relative mt-1">
                            <MapPin
                                class="absolute left-3 top-3 h-4 w-4 text-slate-400"
                            />

                            <input
                                v-model="form.location"
                                type="text"
                                maxlength="200"
                                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="Example: Main Building, Room 202, Bathroom"
                            />
                        </div>

                        <InputError
                            class="mt-1"
                            :message="form.errors.location"
                        />

                        <p class="mt-1 text-[10px] text-slate-400">
                            Update this if you are not in your assigned room.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Description" />

                        <textarea
                            v-model="form.description"
                            rows="5"
                            maxlength="3000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                            placeholder="Briefly explain what happened, who needs help and whether anyone is injured..."
                        ></textarea>

                        <div
                            class="mt-1 flex items-start justify-between gap-4"
                        >
                            <InputError :message="form.errors.description" />

                            <span class="text-[10px] text-slate-400">
                                {{ form.description.length }}/3000
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-xs font-semibold leading-5 text-red-700">
                            By submitting this alert, you confirm that immediate
                            hostel assistance is required. Your resident and
                            room details will be shared with staff.
                        </p>
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

                    <PrimaryButton class="!bg-red-600 hover:!bg-red-700">
                        <Siren class="mr-2 h-4 w-4" />
                        Continue
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Final confirmation -->
        <Modal :show="confirmOpen" maxWidth="md" @close="confirmOpen = false">
            <div class="p-6">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-700"
                >
                    <Siren class="h-8 w-8" />
                </div>

                <h2 class="mt-5 text-center text-xl font-bold text-slate-900">
                    Confirm Emergency Alert
                </h2>

                <p class="mt-2 text-center text-sm leading-6 text-slate-500">
                    You are about to send an urgent emergency notification to
                    hostel staff.
                </p>

                <div
                    class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4"
                >
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-red-500"
                    >
                        Emergency Type
                    </p>

                    <div class="mt-2 flex items-center gap-3">
                        <component
                            :is="selectedCategory?.icon || Siren"
                            class="h-5 w-5 text-red-700"
                        />

                        <p class="font-bold text-red-900">
                            {{ selectedCategory?.label }}
                        </p>
                    </div>

                    <p
                        class="mt-4 text-xs font-semibold uppercase tracking-wide text-red-500"
                    >
                        Location
                    </p>

                    <p class="mt-1 text-sm text-red-900">
                        {{ form.location || "No location provided" }}
                    </p>
                </div>

                <div class="mt-6 flex justify-center gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="
                            confirmOpen = false;
                            createOpen = true;
                        "
                    >
                        Go Back
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50"
                        @click="submitAlert"
                    >
                        <Siren class="h-4 w-4" />

                        {{ form.processing ? "Sending..." : "Send SOS Alert" }}
                    </button>
                </div>
            </div>
        </Modal>
    </ResidentLayout>
</template>
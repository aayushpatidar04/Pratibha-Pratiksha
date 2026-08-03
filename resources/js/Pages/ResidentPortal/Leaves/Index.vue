<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Eye,
    Home,
    MapPin,
    Moon,
    Plus,
    Search,
    Stethoscope,
    Sun,
    X,
    XCircle,
} from "lucide-vue-next";
import { reactive, ref, watch } from "vue";

const props = defineProps({
    leaves: {
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

    parentPhoneAvailable: {
        type: Boolean,
        default: false,
    },
});

const applyOpen = ref(false);

const filters = reactive({
    status: props.filters?.status || "all",
    leave_type: props.filters?.leave_type || "",
    search: props.filters?.search || "",
});

let searchTimer = null;

const applyFilters = () => {
    router.get(
        route("resident.leaves.index"),
        {
            status: filters.status !== "all" ? filters.status : undefined,

            leave_type: filters.leave_type || undefined,

            search: filters.search || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filters.status = "all";
    filters.leave_type = "";
    filters.search = "";

    router.get(
        route("resident.leaves.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            if (filters.search.length === 0 || filters.search.length >= 3) {
                applyFilters();
            }
        }, 400);
    },
);

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    leave_type: "home_leave",
    from_date: today,
    to_date: today,
    reason: "",
    destination: "",
});

const submitLeave = () => {
    form.post(route("resident.leaves.store"), {
        preserveScroll: true,

        onSuccess: () => {
            applyOpen.value = false;
            form.reset();

            form.leave_type = "home_leave";
            form.from_date = today;
            form.to_date = today;
        },
    });
};

const leaveTypes = [
    {
        value: "home_leave",
        label: "Home Leave",
        description: "Leave to visit your home or family.",
        icon: Home,
    },
    {
        value: "medical_leave",
        label: "Medical Leave",
        description: "Leave for treatment or recovery.",
        icon: Stethoscope,
    },
    {
        value: "emergency_leave",
        label: "Emergency Leave",
        description: "Urgent leave due to an emergency.",
        icon: AlertTriangle,
    },
    {
        value: "day_out",
        label: "Day Out",
        description: "Permission to remain outside during the day.",
        icon: Sun,
    },
    {
        value: "night_pass",
        label: "Night Pass",
        description: "Permission to remain outside overnight.",
        icon: Moon,
    },
];

const formatDate = (value) => {
    if (!value) return "—";

    const date = String(value).includes("T")
        ? new Date(value)
        : new Date(`${value}T00:00:00`);

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const statusClasses = {
    pending: "border-amber-200 bg-amber-50 text-amber-700",

    parent_approval_pending: "border-blue-200 bg-blue-50 text-blue-700",

    approved: "border-emerald-200 bg-emerald-50 text-emerald-700",

    rejected: "border-red-200 bg-red-50 text-red-700",

    cancelled: "border-slate-200 bg-slate-100 text-slate-600",

    expired: "border-gray-200 bg-gray-100 text-gray-500",
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

const typeIcon = (type) => {
    return leaveTypes.find((item) => item.value === type)?.icon || CalendarDays;
};
</script>

<template>
    <Head title="Leaves" />

    <ResidentLayout title="Leaves">
        <div class="space-y-6">
            <section
                class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold text-slate-900">My Leaves</h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Apply for leave and track parent and administration
                        approval.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    @click="applyOpen = true"
                >
                    <Plus class="h-4 w-4" />
                    Apply Leave
                </button>
            </section>

            <div
                v-if="!parentPhoneAvailable"
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4"
            >
                <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div>
                    <p class="text-sm font-bold text-amber-900">
                        Parent contact is unavailable
                    </p>

                    <p class="mt-1 text-xs text-amber-700">
                        Leave requests can still be submitted, but WhatsApp
                        approval cannot be sent until your father or mother
                        phone number is updated.
                    </p>
                </div>
            </div>

            <section class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <button
                    v-for="item in [
                        {
                            key: 'all',
                            label: 'Total',
                            count: stats.total,
                        },
                        {
                            key: 'parent_approval_pending',
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
                        filters.status === item.key
                            ? 'border-indigo-400 ring-2 ring-indigo-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filters.status = item.key;
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

            <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="filters.search"
                            type="text"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search by reason, destination or gate pass"
                        />
                    </div>

                    <select
                        v-model="filters.leave_type"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="applyFilters"
                    >
                        <option value="">All Leave Types</option>

                        <option
                            v-for="type in leaveTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>

                    <button
                        v-if="
                            filters.search ||
                            filters.leave_type ||
                            filters.status !== 'all'
                        "
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                        @click="clearFilters"
                    >
                        <X class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </section>

            <section
                v-if="leaves.data?.length"
                class="grid grid-cols-1 gap-4 lg:grid-cols-2"
            >
                <article
                    v-for="leave in leaves.data"
                    :key="leave.id"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <component
                                        :is="typeIcon(leave.leave_type)"
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <h3
                                        class="truncate text-base font-bold text-slate-900"
                                    >
                                        {{ leave.leave_type_label }}
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ formatDate(leave.from_date) }}
                                        →
                                        {{ formatDate(leave.to_date) }}
                                        ·
                                        {{ leave.total_days }}
                                        day{{
                                            leave.total_days === 1 ? "" : "s"
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="statusClasses[leave.final_status]"
                            >
                                <component
                                    :is="statusIcon(leave.final_status)"
                                    class="h-3.5 w-3.5"
                                />

                                {{ leave.final_status_label }}
                            </span>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4"
                        >
                            <div>
                                <p class="text-xs text-slate-400">
                                    Parent Approval
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-slate-700"
                                >
                                    {{ leave.parent_approval_status }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Admin Approval
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-slate-700"
                                >
                                    {{ leave.admin_approval_status }}
                                </p>
                            </div>

                            <div v-if="leave.destination" class="col-span-2">
                                <p class="text-xs text-slate-400">
                                    Destination
                                </p>

                                <p
                                    class="mt-1 truncate text-sm font-semibold text-slate-700"
                                >
                                    {{ leave.destination }}
                                </p>
                            </div>
                        </div>

                        <p class="mt-4 line-clamp-2 text-sm text-slate-600">
                            {{ leave.reason }}
                        </p>

                        <div
                            v-if="leave.gate_pass_code"
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-emerald-600"
                            >
                                Gate Pass Code
                            </p>

                            <p
                                class="mt-1 font-mono text-lg font-bold tracking-wider text-emerald-800"
                            >
                                {{ leave.gate_pass_code }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end border-t border-slate-100 bg-slate-50 px-5 py-3"
                    >
                        <Link
                            :href="
                                route('resident.leaves.show', {
                                    residentLeave: leave.id,
                                })
                            "
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700"
                        >
                            <Eye class="h-4 w-4" />
                            View Details
                        </Link>
                    </div>
                </article>
            </section>

            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <CalendarCheck class="mx-auto h-12 w-12 text-slate-300" />

                <h3 class="mt-4 text-base font-bold text-slate-700">
                    No leave requests found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Submit a leave request to start the approval process.
                </p>

                <button
                    type="button"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="applyOpen = true"
                >
                    <Plus class="h-4 w-4" />
                    Apply Leave
                </button>
            </section>

            <div
                v-if="leaves.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template v-for="link in leaves.links" :key="link.label">
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

        <Modal :show="applyOpen" maxWidth="2xl" @close="applyOpen = false">
            <form
                class="max-h-[90vh] overflow-y-auto"
                @submit.prevent="submitLeave"
            >
                <div
                    class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Apply for Leave
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Your request will be sent to your parent for
                            WhatsApp approval.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="applyOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <InputLabel value="Leave Type *" />

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label
                                v-for="type in leaveTypes"
                                :key="type.value"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                                :class="
                                    form.leave_type === type.value
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                                        : 'border-slate-200 hover:border-indigo-200'
                                "
                            >
                                <input
                                    v-model="form.leave_type"
                                    type="radio"
                                    :value="type.value"
                                    class="sr-only"
                                />

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm"
                                >
                                    <component
                                        :is="type.icon"
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ type.label }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ type.description }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="form.errors.leave_type"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="From Date *" />

                            <input
                                v-model="form.from_date"
                                type="date"
                                :min="today"
                                required
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="form.errors.from_date"
                            />
                        </div>

                        <div>
                            <InputLabel value="To Date *" />

                            <input
                                v-model="form.to_date"
                                type="date"
                                :min="form.from_date || today"
                                required
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <InputError
                                class="mt-1"
                                :message="form.errors.to_date"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Destination" />

                        <div class="relative mt-1">
                            <MapPin
                                class="absolute left-3 top-3 h-4 w-4 text-slate-400"
                            />

                            <input
                                v-model="form.destination"
                                type="text"
                                maxlength="200"
                                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="City, address or destination"
                            />
                        </div>

                        <InputError
                            class="mt-1"
                            :message="form.errors.destination"
                        />
                    </div>

                    <div>
                        <InputLabel value="Reason *" />

                        <textarea
                            v-model="form.reason"
                            rows="5"
                            required
                            maxlength="2000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain the reason for your leave request"
                        ></textarea>

                        <div class="mt-1 flex items-center justify-between">
                            <InputError :message="form.errors.reason" />

                            <span class="text-[10px] text-slate-400">
                                {{ form.reason.length }}/2000
                            </span>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs leading-5 text-blue-700"
                    >
                        After submission, your parent will receive a WhatsApp
                        message with an approval link. Parent approval will
                        automatically approve the request and generate your
                        gate-pass code.
                    </div>
                </div>

                <div
                    class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="applyOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="form.processing">
                        {{
                            form.processing
                                ? "Submitting..."
                                : "Submit Leave Request"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>

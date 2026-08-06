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
    BedDouble,
    Building2,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    CircleDollarSign,
    Clock3,
    FileClock,
    History,
    IndianRupee,
    KeyRound,
    LogOut,
    ShieldAlert,
    UserRoundCheck,
    X,
    XCircle,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";

const props = defineProps({
    currentStay: {
        type: Object,
        default: null,
    },

    requests: {
        type: Array,
        default: () => [],
    },

    activeRequest: {
        type: Object,
        default: null,
    },

    policy: {
        type: Object,
        required: true,
    },

    outstandingSummary: {
        type: Object,
        default: () => ({
            invoice_count: 0,
            amount: 0,
        }),
    },
});

const createOpen = ref(false);
const detailsOpen = ref(false);
const cancelOpen = ref(false);

const selectedRequest = ref(null);

const form = useForm({
    requested_checkout_date: props.policy.minimum_recommended_date,

    reason: "",
    resident_notes: "",

    short_notice_warning_accepted: false,
});

const cancelForm = useForm({
    cancellation_reason: "",
});

const selectedDate = computed(() => {
    if (!form.requested_checkout_date) {
        return null;
    }

    return new Date(`${form.requested_checkout_date}T00:00:00`);
});

const minimumRecommendedDate = computed(
    () => new Date(`${props.policy.minimum_recommended_date}T00:00:00`),
);

const isShortNotice = computed(() => {
    if (!selectedDate.value) {
        return false;
    }

    return selectedDate.value < minimumRecommendedDate.value;
});

const actualNoticeDays = computed(() => {
    if (!selectedDate.value) {
        return 0;
    }

    const today = new Date(`${props.policy.today}T00:00:00`);

    const difference = selectedDate.value.getTime() - today.getTime();

    return Math.max(0, Math.floor(difference / (1000 * 60 * 60 * 24)));
});

watch(
    () => form.requested_checkout_date,
    () => {
        if (!isShortNotice.value) {
            form.short_notice_warning_accepted = false;
        }

        form.clearErrors("short_notice_warning_accepted");
    },
);

const statusMeta = {
    pending: {
        label: "Pending",
        classes: "border-amber-200 bg-amber-50 text-amber-700",
    },

    under_admin_review: {
        label: "Under Admin Review",
        classes: "border-blue-200 bg-blue-50 text-blue-700",
    },

    assigned_to_warden: {
        label: "Assigned to Warden",
        classes: "border-violet-200 bg-violet-50 text-violet-700",
    },

    warden_review_in_progress: {
        label: "Warden Review in Progress",
        classes: "border-indigo-200 bg-indigo-50 text-indigo-700",
    },

    warden_approved: {
        label: "Warden Approved",
        classes: "border-emerald-200 bg-emerald-50 text-emerald-700",
    },

    warden_rejected: {
        label: "Warden Rejected",
        classes: "border-red-200 bg-red-50 text-red-700",
    },

    admin_approved: {
        label: "Admin Approved",
        classes: "border-emerald-200 bg-emerald-50 text-emerald-700",
    },

    admin_rejected: {
        label: "Admin Rejected",
        classes: "border-red-200 bg-red-50 text-red-700",
    },

    on_hold: {
        label: "On Hold",
        classes: "border-orange-200 bg-orange-50 text-orange-700",
    },

    ready_for_exit: {
        label: "Ready for Exit",
        classes: "border-teal-200 bg-teal-50 text-teal-700",
    },

    completed: {
        label: "Checkout Completed",
        classes: "border-slate-200 bg-slate-50 text-slate-700",
    },

    cancelled: {
        label: "Cancelled",
        classes: "border-slate-200 bg-slate-50 text-slate-600",
    },

    expired: {
        label: "Expired",
        classes: "border-slate-200 bg-slate-50 text-slate-600",
    },
};

const openCreate = () => {
    form.reset();
    form.clearErrors();

    form.requested_checkout_date = props.policy.minimum_recommended_date;

    form.short_notice_warning_accepted = false;

    createOpen.value = true;
};

const submitRequest = () => {
    form.post(route("resident.checkout-requests.store"), {
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
};

const openDetails = (request) => {
    selectedRequest.value = request;
    detailsOpen.value = true;
};

const openCancel = (request) => {
    selectedRequest.value = request;

    cancelForm.reset();
    cancelForm.clearErrors();

    cancelOpen.value = true;
};

const submitCancel = () => {
    if (!selectedRequest.value) {
        return;
    }

    cancelForm.put(
        route("resident.checkout-requests.cancel", {
            checkoutRequest: selectedRequest.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                cancelOpen.value = false;
                selectedRequest.value = null;
                cancelForm.reset();
            },
        },
    );
};

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);

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

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(new Date(value));
};

const money = (value) => {
    return Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
    });
};

const humanize = (value) => {
    return String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const historyIcon = (action) => {
    if (action.includes("approved") || action === "request_created") {
        return CheckCircle2;
    }

    if (action.includes("rejected") || action.includes("cancelled")) {
        return XCircle;
    }

    if (action.includes("warning") || action.includes("hold")) {
        return AlertTriangle;
    }

    return Clock3;
};
</script>

<template>
    <Head title="Checkout Requests" />

    <ResidentLayout title="Checkout Request">
        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-rose-200 bg-[linear-gradient(135deg,#881337_0%,#e11d48_52%,#fb7185_100%)] p-6 text-white shadow-xl md:p-8"
            >
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <LogOut class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.2em] text-white"
                                >
                                    Departure Management
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    Checkout Requests
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Submit your planned checkout date at least 30 days
                            in advance and track the complete approval process.
                        </p>
                    </div>

                    <button
                        type="button"
                        :disabled="!currentStay || Boolean(activeRequest)"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-rose-700 shadow-lg transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openCreate"
                    >
                        <CalendarClock class="h-4 w-4" />

                        {{
                            activeRequest
                                ? "Request Already Active"
                                : "Create Checkout Request"
                        }}
                    </button>
                </div>
            </section>

            <section
                v-if="currentStay"
                class="grid grid-cols-1 gap-4 md:grid-cols-3"
            >
                <div
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                >
                    <Building2 class="h-5 w-5 text-indigo-700" />

                    <p class="mt-3 text-xs text-indigo-600">
                        Current Accommodation
                    </p>

                    <p class="mt-1 text-sm font-bold text-indigo-900">
                        {{ currentStay.building_name || "—" }}
                        · Room
                        {{ currentStay.room_number || "—" }}
                        · Bed
                        {{ currentStay.bed_number || "—" }}
                    </p>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <CalendarDays class="h-5 w-5 text-blue-700" />

                    <p class="mt-3 text-xs text-blue-600">
                        Recommended Checkout Date
                    </p>

                    <p class="mt-1 text-sm font-bold text-blue-900">
                        {{ formatDate(policy.minimum_recommended_date) }}
                    </p>

                    <p class="mt-1 text-[10px] text-blue-600">
                        Minimum 30 days from today
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <IndianRupee class="h-5 w-5 text-amber-700" />

                    <p class="mt-3 text-xs text-amber-600">
                        Current Outstanding
                    </p>

                    <p class="mt-1 text-sm font-bold text-amber-900">
                        {{ money(outstandingSummary.amount) }}
                    </p>

                    <p class="mt-1 text-[10px] text-amber-600">
                        {{ outstandingSummary.invoice_count }}
                        unpaid or partial invoice(s)
                    </p>
                </div>
            </section>

            <div
                v-if="
                    activeRequest.status === 'completed'
                "
                class="rounded-xl border border-slate-200 bg-slate-50 p-4"
            >
                <div
                    class="flex items-start gap-3"
                >
                    <CheckCircle2
                        class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"
                    />

                    <div>
                        <p
                            class="text-sm font-bold text-slate-900"
                        >
                            Checkout Completed
                        </p>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            Your hostel checkout and physical
                            exit have been completed.
                        </p>
                    </div>
                </div>
            </div>

            <section
                v-if="activeRequest"
                class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-blue-600"
                        >
                            Active Checkout Request
                        </p>

                        <h2 class="mt-1 text-lg font-bold text-blue-900">
                            Planned checkout:
                            {{
                                formatDate(
                                    activeRequest.requested_checkout_date,
                                )
                            }}
                        </h2>

                        <p class="mt-2 text-sm text-blue-700">
                            {{
                                statusMeta[activeRequest.status]?.label ||
                                humanize(activeRequest.status)
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white mr-2"
                        @click="openDetails(activeRequest)"
                    >
                        View Progress
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </div>
            </section>

            <div
                v-if="
                    activeRequest.status ===
                    'ready_for_exit'
                "
                class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p
                            class="text-sm font-bold text-emerald-900"
                        >
                            Your checkout is approved
                        </p>

                        <p
                            class="mt-1 text-xs leading-5 text-emerald-700"
                        >
                            Your exit code is ready. Present
                            it to gate staff when leaving the
                            hostel.
                        </p>
                    </div>

                    <Link
                        :href="
                            route(
                                'resident.checkout-requests.exit-pass',
                                {
                                    checkoutRequest:
                                        activeRequest.id,
                                },
                            )
                        "
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white"
                    >
                        <KeyRound class="h-4 w-4" />
                        View Exit Pass
                    </Link>
                </div>
            </div>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2
                        class="flex items-center gap-2 text-base font-bold text-slate-900"
                    >
                        <History class="h-5 w-5 text-indigo-600" />

                        Request History
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        All checkout requests associated with your hostel stays.
                    </p>
                </div>

                <div v-if="requests.length" class="divide-y divide-slate-100">
                    <article
                        v-for="request in requests"
                        :key="request.id"
                        class="px-5 py-5"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900">
                                        Checkout
                                        {{
                                            formatDate(
                                                request.requested_checkout_date,
                                            )
                                        }}
                                    </p>

                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                        :class="
                                            statusMeta[request.status]?.classes
                                        "
                                    >
                                        {{
                                            statusMeta[request.status]?.label ||
                                            humanize(request.status)
                                        }}
                                    </span>

                                    <span
                                        v-if="request.is_short_notice"
                                        class="rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700"
                                    >
                                        Short Notice
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    Requested
                                    {{ formatDateTime(request.requested_at) }}
                                    ·
                                    {{ request.actual_notice_days }}
                                    days notice
                                </p>

                                <p
                                    class="mt-2 line-clamp-2 text-sm text-slate-600"
                                >
                                    {{ request.reason }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 px-4 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                    @click="openDetails(request)"
                                >
                                    View Details
                                </button>

                                <Link
                                    v-if="
                                        request.status ===
                                        'ready_for_exit'
                                    "
                                    :href="
                                        route(
                                            'resident.checkout-requests.exit-pass',
                                            {
                                                checkoutRequest:
                                                    request.id,
                                            },
                                        )
                                    "
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    <KeyRound class="h-4 w-4" />
                                    Open Exit Pass
                                </Link>

                                <button
                                    v-if="request.can_cancel"
                                    type="button"
                                    class="rounded-xl border border-red-200 px-4 py-2 text-xs font-semibold text-red-700 hover:bg-red-50"
                                    @click="openCancel(request)"
                                >
                                    Cancel Request
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="px-6 py-14 text-center">
                    <FileClock class="mx-auto h-11 w-11 text-slate-300" />

                    <p class="mt-3 text-sm font-bold text-slate-700">
                        No checkout requests
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Your submitted checkout requests will appear here.
                    </p>
                </div>
            </section>
        </div>

        <!-- Create modal -->
        <Modal :show="createOpen" maxWidth="lg" @close="createOpen = false">
            <form class="p-6" @submit.prevent="submitRequest">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Create Checkout Request
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Select your planned departure date and provide a
                            reason.
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

                <div class="mt-6 space-y-5">
                    <div>
                        <InputLabel value="Requested Checkout Date *" />

                        <input
                            v-model="form.requested_checkout_date"
                            type="date"
                            :min="policy.today"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />

                        <InputError
                            class="mt-1"
                            :message="form.errors.requested_checkout_date"
                        />

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[10px] uppercase text-slate-400">
                                    Notice Given
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{ actualNoticeDays }}
                                    days
                                </p>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-3">
                                <p class="text-[10px] uppercase text-slate-400">
                                    Required Notice
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{ policy.required_notice_days }}
                                    days
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="isShortNotice"
                        class="rounded-2xl border border-red-300 bg-red-50 p-5"
                    >
                        <div class="flex items-start gap-3">
                            <ShieldAlert
                                class="mt-0.5 h-6 w-6 shrink-0 text-red-700"
                            />

                            <div>
                                <p class="text-sm font-bold text-red-900">
                                    Short-notice checkout
                                </p>

                                <p class="mt-1 text-xs leading-5 text-red-700">
                                    {{ policy.short_notice_message }}
                                </p>
                            </div>
                        </div>

                        <label
                            class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-red-200 bg-white p-4"
                        >
                            <input
                                v-model="form.short_notice_warning_accepted"
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500"
                            />

                            <span
                                class="text-xs font-semibold leading-5 text-red-800"
                            >
                                I understand that charges may be applicable
                                under the hostel policy and terms and
                                conditions.
                            </span>
                        </label>

                        <InputError
                            class="mt-2"
                            :message="form.errors.short_notice_warning_accepted"
                        />
                    </div>

                    <div>
                        <InputLabel value="Reason for Checkout *" />

                        <textarea
                            v-model="form.reason"
                            rows="4"
                            required
                            maxlength="3000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Explain why you are planning to leave the hostel"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="form.errors.reason"
                        />
                    </div>

                    <div>
                        <InputLabel value="Additional Notes" />

                        <textarea
                            v-model="form.resident_notes"
                            rows="3"
                            maxlength="3000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional details for the hostel administration"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="form.errors.resident_notes"
                        />
                    </div>

                    <div
                        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <AlertTriangle
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                        />

                        <p class="text-xs leading-5 text-amber-700">
                            Submitting a request does not immediately complete
                            checkout. Room, asset, dues, warden and
                            administration clearance must still be completed.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="
                            form.processing ||
                            (isShortNotice &&
                                !form.short_notice_warning_accepted)
                        "
                    >
                        {{
                            form.processing
                                ? "Submitting..."
                                : "Submit Checkout Request"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Details modal -->
        <Modal :show="detailsOpen" maxWidth="2xl" @close="detailsOpen = false">
            <div
                v-if="selectedRequest"
                class="flex max-h-[92vh] flex-col overflow-hidden"
            >
                <div
                    class="flex items-start justify-between border-b border-slate-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Checkout Request Details
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Planned departure:
                            {{
                                formatDate(
                                    selectedRequest.requested_checkout_date,
                                )
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="detailsOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Current Status</p>

                            <span
                                class="mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                :class="
                                    statusMeta[selectedRequest.status]?.classes
                                "
                            >
                                {{
                                    statusMeta[selectedRequest.status]?.label ||
                                    humanize(selectedRequest.status)
                                }}
                            </span>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Notice Period</p>

                            <p class="mt-2 text-sm font-bold text-slate-900">
                                {{ selectedRequest.actual_notice_days }}
                                /
                                {{ selectedRequest.required_notice_days }}
                                days
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedRequest.is_short_notice"
                        class="rounded-xl border border-red-200 bg-red-50 p-4"
                    >
                        <p class="text-sm font-bold text-red-900">
                            Short-notice policy accepted
                        </p>

                        <p class="mt-1 text-xs text-red-700">
                            Additional charges may be applied during final
                            settlement.
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-slate-400"
                        >
                            Reason
                        </p>

                        <p
                            class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"
                        >
                            {{ selectedRequest.reason }}
                        </p>
                    </div>

                    <div v-if="selectedRequest.resident_notes">
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-slate-400"
                        >
                            Additional Notes
                        </p>

                        <p
                            class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"
                        >
                            {{ selectedRequest.resident_notes }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Admin Review</p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{
                                    humanize(
                                        selectedRequest.admin_review_status,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Warden Review</p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{
                                    humanize(
                                        selectedRequest.warden_review_status,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">Dues Clearance</p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{
                                    humanize(
                                        selectedRequest.dues_clearance_status,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs text-slate-500">
                                Outstanding at Request
                            </p>

                            <p class="mt-1 text-sm font-bold text-slate-900">
                                {{
                                    money(
                                        selectedRequest.outstanding_amount_at_request,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="selectedRequest.histories?.length">
                        <h3
                            class="flex items-center gap-2 text-sm font-bold text-slate-900"
                        >
                            <History class="h-4 w-4 text-indigo-600" />
                            Request Timeline
                        </h3>

                        <div
                            class="mt-4 space-y-4 border-l-2 border-slate-200 pl-5"
                        >
                            <div
                                v-for="history in selectedRequest.histories"
                                :key="history.id"
                                class="relative"
                            >
                                <div
                                    class="absolute -left-[30px] top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-white ring-2 ring-indigo-500"
                                ></div>

                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="historyIcon(history.action)"
                                            class="h-4 w-4 text-indigo-600"
                                        />

                                        <p
                                            class="text-xs font-bold text-slate-900"
                                        >
                                            {{ humanize(history.action) }}
                                        </p>
                                    </div>

                                    <p
                                        v-if="history.notes"
                                        class="mt-2 text-xs leading-5 text-slate-600"
                                    >
                                        {{ history.notes }}
                                    </p>

                                    <p class="mt-2 text-[10px] text-slate-400">
                                        {{ formatDateTime(history.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end border-t border-slate-100 px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        @click="detailsOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Cancel modal -->
        <Modal :show="cancelOpen" maxWidth="md" @close="cancelOpen = false">
            <form class="p-6" @submit.prevent="submitCancel">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Cancel Checkout Request
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Explain why you are withdrawing this request.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="cancelOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6">
                    <InputLabel value="Cancellation Reason *" />

                    <textarea
                        v-model="cancelForm.cancellation_reason"
                        rows="5"
                        required
                        maxlength="2000"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                    ></textarea>

                    <InputError
                        class="mt-1"
                        :message="cancelForm.errors.cancellation_reason"
                    />
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="cancelOpen = false"
                    >
                        Keep Request
                    </button>

                    <button
                        type="submit"
                        :disabled="cancelForm.processing"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                    >
                        {{
                            cancelForm.processing
                                ? "Cancelling..."
                                : "Cancel Checkout Request"
                        }}
                    </button>
                </div>
            </form>
        </Modal>
    </ResidentLayout>
</template>
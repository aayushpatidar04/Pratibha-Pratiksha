<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    CalendarClock,
    CheckCircle2,
    Clock3,
    Eye,
    Filter,
    History,
    Loader2,
    IndianRupee,
    LogOut,
    PauseCircle,
    Plus,
    Search,
    ShieldAlert,
    UserRoundCheck,
    Users,
    WalletCards,
    X,
    XCircle,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    requests: Object,
    stats: Object,
    filters: Object,
    statuses: Array,
    eligibleResidents: Array,
    wardens: Array,
    buildings: Array,
    policy: Object,
});

const {
    can,
} = usePermissions();

const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const buildingId = ref(props.filters.building_id || "");
const assignedWardenId = ref(props.filters.assigned_warden_id || "");

const createOpen = ref(false);
const detailsOpen = ref(false);
const assignOpen = ref(false);
const holdOpen = ref(false);
const rejectOpen = ref(false);

const selectedRequest = ref(null);

const createForm = useForm({
    resident_id: "",
    resident_stay_id: "",
    requested_checkout_date: props.policy.minimum_recommended_date,
    reason: "",
    resident_notes: "",
    short_notice_warning_accepted: false,
});

const assignForm = useForm({
    assigned_warden_id: "",
    admin_review_notes: "",
    short_notice_charge: 0,
    short_notice_charge_notes: "",
    dues_clearance_status: "pending",
});

const holdForm = useForm({
    admin_review_notes: "",
});

const rejectForm = useForm({
    admin_review_notes: "",
});

let searchTimer = null;

watch([search, status, buildingId, assignedWardenId], () => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        router.get(
            route("checkout-requests.index"),
            {
                search: search.value || undefined,

                status: status.value || undefined,

                building_id: buildingId.value || undefined,

                assigned_warden_id: assignedWardenId.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const selectedResidentStay = computed(() =>
    props.eligibleResidents.find(
        (item) =>
            Number(item.resident_stay_id) ===
            Number(createForm.resident_stay_id),
    ),
);

const selectedCreateDate = computed(() => {
    if (!createForm.requested_checkout_date) {
        return null;
    }

    return new Date(`${createForm.requested_checkout_date}T00:00:00`);
});

const minimumRecommendedDate = computed(
    () => new Date(`${props.policy.minimum_recommended_date}T00:00:00`),
);

const isShortNotice = computed(() => {
    if (!selectedCreateDate.value) {
        return false;
    }

    return selectedCreateDate.value < minimumRecommendedDate.value;
});

watch(
    () => createForm.resident_stay_id,
    (value) => {
        const stay = props.eligibleResidents.find(
            (item) => Number(item.resident_stay_id) === Number(value),
        );

        createForm.resident_id = stay?.resident_id || "";
    },
);

watch(
    () => createForm.requested_checkout_date,
    () => {
        if (!isShortNotice.value) {
            createForm.short_notice_warning_accepted = false;
        }
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
        label: "Warden Review",
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
        label: "Completed",
        classes: "border-slate-200 bg-slate-50 text-slate-700",
    },

    cancelled: {
        label: "Cancelled",
        classes: "border-slate-200 bg-slate-50 text-slate-600",
    },
};

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();

    createForm.requested_checkout_date = props.policy.minimum_recommended_date;

    createForm.short_notice_warning_accepted = false;

    createOpen.value = true;
};

const submitCreate = () => {
    createForm.post(route("checkout-requests.store"), {
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
};

const openDetails = (request) => {
    selectedRequest.value = request;
    detailsOpen.value = true;
};

const startReview = (request) => {
    router.put(
        route("checkout-requests.start-review", {
            checkoutRequest: request.id,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
};

const openAssign = (request) => {
    selectedRequest.value = request;

    assignForm.reset();
    assignForm.clearErrors();

    assignForm.assigned_warden_id = request.assigned_warden?.id || "";

    assignForm.admin_review_notes = request.admin_review_notes || "";

    assignForm.short_notice_charge = Number(request.short_notice_charge || 0);

    assignForm.short_notice_charge_notes =
        request.short_notice_charge_notes || "";

    assignForm.dues_clearance_status =
        request.dues_clearance_status || "pending";

    assignOpen.value = true;
};

const submitAssign = () => {
    if (!selectedRequest.value) {
        return;
    }

    assignForm.put(
        route("checkout-requests.assign-warden", {
            checkoutRequest: selectedRequest.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                assignOpen.value = false;
                selectedRequest.value = null;
            },
        },
    );
};

const openHold = (request) => {
    selectedRequest.value = request;

    holdForm.reset();
    holdForm.clearErrors();

    holdForm.admin_review_notes = "";

    holdOpen.value = true;
};

const submitHold = () => {
    if (!selectedRequest.value) {
        return;
    }

    holdForm.put(
        route("checkout-requests.hold", {
            checkoutRequest: selectedRequest.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                holdOpen.value = false;
                selectedRequest.value = null;
            },
        },
    );
};

const openReject = (request) => {
    selectedRequest.value = request;

    rejectForm.reset();
    rejectForm.clearErrors();

    rejectForm.admin_review_notes = "";

    rejectOpen.value = true;
};

const submitReject = () => {
    if (!selectedRequest.value) {
        return;
    }

    rejectForm.put(
        route("checkout-requests.reject", {
            checkoutRequest: selectedRequest.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                rejectOpen.value = false;
                selectedRequest.value = null;
            },
        },
    );
};

const formatDate = (value) => {
    if (!value) return "—";

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
};

const formatDateTime = (value) => {
    if (!value) return "—";

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(new Date(value));
};

const money = (value) =>
    Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
    });

const humanize = (value) =>
    String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());

const refundModalOpen = ref(false);

const refundLoading = ref(false);

const refundSubmitting = ref(false);

const refundRequest = ref(null);

const refundDetails = ref(null);

const refundTransactionId = ref("");

const refundNotes = ref("");

const canRefundSecurityDeposit = computed(() =>
    can(
        "billing",
        "refund_security_deposit",
    ),
);

const openRefundModal = async (checkoutRequest) => {
    refundRequest.value =
        checkoutRequest;

    refundModalOpen.value = true;

    refundLoading.value = true;

    refundDetails.value = null;

    refundTransactionId.value = "";

    refundNotes.value = "";

    try {
        const response =
            await axios.get(
                route(
                    "checkout-requests.security-deposit.refund-details",
                    checkoutRequest.id,
                ),
            );
        refundDetails.value =
            response.data;
    } catch (error) {
        refundModalOpen.value = false;

        alert(
            error?.response?.data?.message ||
                "Unable to load refund details.",
        );
    } finally {
        refundLoading.value = false;
    }
};

const submitSecurityDepositRefund = () => {
    if (!refundRequest.value) {
        return;
    }

    if (!refundTransactionId.value.trim()) {
        return;
    }

    refundSubmitting.value = true;

    router.post(
        route(
            "checkout-requests.security-deposit.refund",
            refundRequest.value.id,
        ),
        {
            refund_transaction_id:
                refundTransactionId.value.trim(),

            refund_notes:
                refundNotes.value || null,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                refundModalOpen.value = false;

                refundRequest.value = null;

                refundDetails.value = null;

                refundTransactionId.value = "";

                refundNotes.value = "";
            },

            onFinish: () => {
                refundSubmitting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Checkout Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Checkout Requests
            </h2>
        </template>

        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-rose-200 bg-[linear-gradient(135deg,#881337_0%,#e11d48_52%,#fb7185_100%)] p-6 text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between"
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
                                    Departure Workflow
                                </p>

                                <h1 class="text-2xl font-bold text-white">
                                    Checkout Requests
                                </h1>
                            </div>
                        </div>

                        <p class="mt-4 max-w-2xl text-sm leading-6 text-white">
                            Review resident departure requests, assign wardens
                            and monitor clearance stages.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-rose-700 shadow-lg"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />
                        Create Request
                    </button>
                </div>
            </section>

            <section
                class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7"
            >
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-2xl font-bold text-amber-700">
                        {{ stats.pending || 0 }}
                    </p>
                    <p class="text-xs text-amber-600">Pending</p>
                </div>

                <div
                    class="rounded-xl border border-violet-200 bg-violet-50 p-4"
                >
                    <p class="text-2xl font-bold text-violet-700">
                        {{ stats.assigned_to_warden || 0 }}
                    </p>
                    <p class="text-xs text-violet-600">With Warden</p>
                </div>

                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ stats.warden_approved || 0 }}
                    </p>
                    <p class="text-xs text-emerald-600">Warden Approved</p>
                </div>

                <div
                    class="rounded-xl border border-orange-200 bg-orange-50 p-4"
                >
                    <p class="text-2xl font-bold text-orange-700">
                        {{ stats.on_hold || 0 }}
                    </p>
                    <p class="text-xs text-orange-600">On Hold</p>
                </div>

                <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                    <p class="text-2xl font-bold text-teal-700">
                        {{ stats.ready_for_exit || 0 }}
                    </p>
                    <p class="text-xs text-teal-600">Ready for Exit</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-2xl font-bold text-slate-800">
                        {{ stats.completed || 0 }}
                    </p>
                    <p class="text-xs text-slate-500">Completed</p>
                </div>

                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <p class="text-2xl font-bold text-red-700">
                        {{ stats.short_notice || 0 }}
                    </p>
                    <p class="text-xs text-red-600">Short Notice</p>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="relative">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search resident..."
                            class="w-full rounded-xl border-slate-300 pl-10 text-sm"
                        />
                    </div>

                    <select
                        v-model="status"
                        class="rounded-xl border-slate-300 text-sm"
                    >
                        <option
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>

                    <select
                        v-model="buildingId"
                        class="rounded-xl border-slate-300 text-sm"
                    >
                        <option value="">All Buildings</option>

                        <option
                            v-for="building in buildings"
                            :key="building.id"
                            :value="building.id"
                        >
                            {{ building.name }}
                        </option>
                    </select>

                    <select
                        v-model="assignedWardenId"
                        class="rounded-xl border-slate-300 text-sm"
                    >
                        <option value="">All Wardens</option>

                        <option
                            v-for="warden in wardens"
                            :key="warden.id"
                            :value="warden.id"
                        >
                            {{ warden.name }}
                        </option>
                    </select>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    v-if="requests.data.length"
                    class="divide-y divide-slate-100"
                >
                    <article
                        v-for="request in requests.data"
                        :key="request.id"
                        class="p-5"
                    >
                        <div
                            class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between"
                        >
                            <div class="flex min-w-0 items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600"
                                >
                                    <LogOut class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{ request.resident.name }}
                                        </h3>

                                        <span class="text-xs text-slate-400">
                                            {{ request.resident.resident_code }}
                                        </span>

                                        <span
                                            class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                            :class="
                                                statusMeta[request.status]
                                                    ?.classes
                                            "
                                        >
                                            {{
                                                statusMeta[request.status]
                                                    ?.label ||
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
                                        {{ request.stay.building || "—" }}
                                        · Room
                                        {{ request.stay.room || "—" }}
                                        · Bed
                                        {{ request.stay.bed || "—" }}
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-semibold text-slate-700"
                                    >
                                        Requested checkout:
                                        {{
                                            formatDate(
                                                request.requested_checkout_date,
                                            )
                                        }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ request.actual_notice_days }}
                                        days notice · Created by
                                        {{ request.requested_by_label }}
                                    </p>

                                    <p
                                        class="mt-2 line-clamp-2 text-sm text-slate-600"
                                    >
                                        {{ request.reason }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 xl:justify-end">
                                <Link
                                    :href="
                                        route(
                                            'checkout-requests.show',
                                            {
                                                checkoutRequest:
                                                    request.id,
                                            },
                                        )
                                    "
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    Open Workflow
                                </Link>

                                <button
                                    v-if="request.status === 'pending'"
                                    type="button"
                                    class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700"
                                    @click="startReview(request)"
                                >
                                    Start Review
                                </button>

                                <button
                                    v-if="
                                        [
                                            'pending',
                                            'under_admin_review',
                                            'on_hold',
                                        ].includes(request.status)
                                    "
                                    type="button"
                                    class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-semibold text-white"
                                    @click="openAssign(request)"
                                >
                                    Assign Warden
                                </button>

                                <button
                                    v-if="
                                        ![
                                            'completed',
                                            'cancelled',
                                            'admin_rejected',
                                        ].includes(request.status)
                                    "
                                    type="button"
                                    class="rounded-xl border border-orange-200 px-3 py-2 text-xs font-semibold text-orange-700"
                                    @click="openHold(request)"
                                >
                                    Hold
                                </button>

                                <button
                                    v-if="
                                        ![
                                            'completed',
                                            'cancelled',
                                            'admin_rejected',
                                        ].includes(request.status)
                                    "
                                    type="button"
                                    class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700"
                                    @click="openReject(request)"
                                >
                                    Reject
                                </button>

                                <button
                                    v-if="canRefundSecurityDeposit && request.status === 'completed' && request.security_deposit?.refund_status != 'refunded'"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                    @click="
                                        openRefundModal(request)
                                    "
                                >
                                    <WalletCards class="h-4 w-4" />

                                    Refund Security Deposit
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <LogOut class="mx-auto h-12 w-12 text-slate-300" />

                    <p class="mt-3 text-sm font-bold text-slate-700">
                        No checkout requests found
                    </p>
                </div>
            </section>
        </div>

        <!-- Create modal -->
        <Modal :show="createOpen" maxWidth="lg" @close="createOpen = false">
            <form class="space-y-5 p-6" @submit.prevent="submitCreate">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Create Checkout Request
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Create a request on behalf of a resident.
                        </p>
                    </div>

                    <button type="button" @click="createOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div>
                    <InputLabel value="Resident Stay *" />

                    <select
                        v-model="createForm.resident_stay_id"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    >
                        <option value="">Select resident</option>

                        <option
                            v-for="item in eligibleResidents"
                            :key="item.resident_stay_id"
                            :value="item.resident_stay_id"
                        >
                            {{ item.name }} · {{ item.resident_code }} ·
                            {{ item.building_name }} / Room
                            {{ item.room_number }}
                        </option>
                    </select>

                    <InputError :message="createForm.errors.resident_stay_id" />
                </div>

                <div>
                    <InputLabel value="Checkout Date *" />

                    <input
                        v-model="createForm.requested_checkout_date"
                        type="date"
                        :min="policy.today"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    />

                    <InputError
                        :message="createForm.errors.requested_checkout_date"
                    />
                </div>

                <div
                    v-if="isShortNotice"
                    class="rounded-xl border border-red-200 bg-red-50 p-4"
                >
                    <p class="text-sm font-bold text-red-900">
                        Short-notice request
                    </p>

                    <p class="mt-1 text-xs leading-5 text-red-700">
                        {{ policy.short_notice_message }}
                    </p>

                    <label class="mt-3 flex items-start gap-2">
                        <input
                            v-model="createForm.short_notice_warning_accepted"
                            type="checkbox"
                            class="mt-0.5 rounded text-red-600"
                        />

                        <span class="text-xs font-semibold text-red-800">
                            I confirm this warning has been explained and
                            accepted.
                        </span>
                    </label>

                    <InputError
                        :message="
                            createForm.errors.short_notice_warning_accepted
                        "
                    />
                </div>

                <div>
                    <InputLabel value="Reason *" />

                    <textarea
                        v-model="createForm.reason"
                        rows="4"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>

                    <InputError :message="createForm.errors.reason" />
                </div>

                <div>
                    <InputLabel value="Resident Notes" />

                    <textarea
                        v-model="createForm.resident_notes"
                        rows="3"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 border-t pt-4">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="
                            createForm.processing ||
                            (isShortNotice &&
                                !createForm.short_notice_warning_accepted)
                        "
                    >
                        Create Request
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Assign warden modal -->
        <Modal :show="assignOpen" maxWidth="lg" @close="assignOpen = false">
            <form class="space-y-5 p-6" @submit.prevent="submitAssign">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold">Assign Warden</h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Approve preliminary review and assign room and asset
                            inspection.
                        </p>
                    </div>

                    <button type="button" @click="assignOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div>
                    <InputLabel value="Warden *" />

                    <select
                        v-model="assignForm.assigned_warden_id"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    >
                        <option value="">Select warden</option>

                        <option
                            v-for="warden in wardens"
                            :key="warden.id"
                            :value="warden.id"
                        >
                            {{ warden.name }}
                        </option>
                    </select>

                    <InputError
                        :message="assignForm.errors.assigned_warden_id"
                    />
                </div>

                <div>
                    <InputLabel value="Dues Clearance Status *" />

                    <select
                        v-model="assignForm.dues_clearance_status"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    >
                        <option value="pending">Pending</option>
                        <option value="clear">Clear</option>
                        <option value="dues_pending">Dues Pending</option>
                        <option value="waived">Waived</option>
                    </select>
                </div>

                <div
                    v-if="selectedRequest?.is_short_notice"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                >
                    <div>
                        <InputLabel value="Provisional Short-Notice Charge" />

                        <input
                            v-model="assignForm.short_notice_charge"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>

                    <div>
                        <InputLabel value="Charge Notes" />

                        <input
                            v-model="assignForm.short_notice_charge_notes"
                            type="text"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Admin Review Notes" />

                    <textarea
                        v-model="assignForm.admin_review_notes"
                        rows="4"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 border-t pt-4">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm"
                        @click="assignOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="assignForm.processing">
                        Assign Warden
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Hold modal -->
        <Modal :show="holdOpen" maxWidth="md" @close="holdOpen = false">
            <form class="p-6" @submit.prevent="submitHold">
                <h2 class="text-lg font-bold">Put Request on Hold</h2>

                <div class="mt-5">
                    <InputLabel value="Reason *" />

                    <textarea
                        v-model="holdForm.admin_review_notes"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>

                    <InputError :message="holdForm.errors.admin_review_notes" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2"
                        @click="holdOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-orange-600 px-4 py-2 text-white"
                    >
                        Put on Hold
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Reject modal -->
        <Modal :show="rejectOpen" maxWidth="md" @close="rejectOpen = false">
            <form class="p-6" @submit.prevent="submitReject">
                <h2 class="text-lg font-bold">Reject Checkout Request</h2>

                <div class="mt-5">
                    <InputLabel value="Rejection Reason *" />

                    <textarea
                        v-model="rejectForm.admin_review_notes"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>

                    <InputError
                        :message="rejectForm.errors.admin_review_notes"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2"
                        @click="rejectOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-4 py-2 text-white"
                    >
                        Reject Request
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Details modal -->
        <Modal :show="detailsOpen" maxWidth="2xl" @close="detailsOpen = false">
            <div
                v-if="selectedRequest"
                class="max-h-[90vh] overflow-y-auto p-6"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold">
                            Checkout Request Details
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ selectedRequest.resident.name }}
                            ·
                            {{ selectedRequest.resident.resident_code }}
                        </p>
                    </div>

                    <button type="button" @click="detailsOpen = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-slate-500">Requested Checkout</p>

                        <p class="mt-1 font-bold">
                            {{
                                formatDate(
                                    selectedRequest.requested_checkout_date,
                                )
                            }}
                        </p>
                    </div>

                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-slate-500">Notice Period</p>

                        <p class="mt-1 font-bold">
                            {{ selectedRequest.actual_notice_days }}
                            /
                            {{ selectedRequest.required_notice_days }}
                            days
                        </p>
                    </div>

                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-slate-500">
                            Outstanding at Request
                        </p>

                        <p class="mt-1 font-bold">
                            {{
                                money(
                                    selectedRequest.outstanding_amount_at_request,
                                )
                            }}
                        </p>
                    </div>

                    <div class="rounded-xl border p-4">
                        <p class="text-xs text-slate-500">Assigned Warden</p>

                        <p class="mt-1 font-bold">
                            {{
                                selectedRequest.assigned_warden?.name ||
                                "Not assigned"
                            }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-bold uppercase text-slate-400">
                        Reason
                    </p>

                    <p
                        class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"
                    >
                        {{ selectedRequest.reason }}
                    </p>
                </div>

                <div v-if="selectedRequest.histories?.length" class="mt-6">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <History class="h-4 w-4 text-indigo-600" />
                        Timeline
                    </h3>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="history in selectedRequest.histories"
                            :key="history.id"
                            class="rounded-xl bg-slate-50 p-4"
                        >
                            <p class="text-xs font-bold">
                                {{ humanize(history.action) }}
                            </p>

                            <p
                                v-if="history.notes"
                                class="mt-1 text-xs text-slate-600"
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
        </Modal>

        <div
            v-if="refundModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div
                class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="border-b border-slate-200 bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <h2
                                class="text-xl font-bold"
                            >
                                Security Deposit Refund
                            </h2>

                            <p
                                class="mt-1 text-sm text-emerald-50"
                            >
                                Review the complete deduction
                                and refund calculation before
                                confirming.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white"
                            @click="
                                refundModalOpen = false
                            "
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div
                    class="max-h-[75vh] overflow-y-auto p-6"
                >
                    <div
                        v-if="refundLoading"
                        class="flex items-center justify-center py-12"
                    >
                        <div
                            class="text-sm font-medium text-slate-500"
                        >
                            Loading refund details...
                        </div>
                    </div>

                    <template
                        v-else-if="refundDetails"
                    >
                        <div
                            class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div
                                class="flex items-center justify-between"
                            >
                                <div>
                                    <p
                                        class="text-sm font-medium text-slate-500"
                                    >
                                        Security Deposit
                                    </p>

                                    <p
                                        class="mt-1 text-2xl font-bold text-slate-900"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.security_deposit_amount,
                                            ).toFixed(2)
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl bg-white px-3 py-2 text-right shadow-sm"
                                >
                                    <p
                                        class="text-xs text-slate-500"
                                    >
                                        Invoice
                                    </p>

                                    <p
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        {{
                                            refundDetails.invoice
                                                ?.invoice_number
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <h3
                                class="text-sm font-bold text-slate-900"
                            >
                                Checkout Deductions
                            </h3>

                            <div
                                class="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200"
                            >
                                <div
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="text-sm text-slate-600"
                                    >
                                        Short Notice Charge
                                    </span>

                                    <span
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.short_notice_charge,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="text-sm text-slate-600"
                                    >
                                        Asset Damage Charge
                                    </span>

                                    <span
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.asset_damage_charge,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="text-sm text-slate-600"
                                    >
                                        Outstanding Dues
                                    </span>

                                    <span
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.outstanding_dues_deduction,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="text-sm text-slate-600"
                                    >
                                        Other Checkout Charges
                                    </span>

                                    <span
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.other_checkout_charge,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between bg-slate-50 px-4 py-3"
                                >
                                    <span
                                        class="text-sm font-bold text-slate-700"
                                    >
                                        Total Deductions
                                    </span>

                                    <span
                                        class="text-sm font-bold text-red-600"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.total_deductions,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-sm font-medium text-emerald-700"
                                    >
                                        Refundable Amount
                                    </p>

                                    <p
                                        class="mt-1 text-3xl font-extrabold text-emerald-700"
                                    >
                                        ₹{{
                                            Number(
                                                refundDetails.refund_amount,
                                            ).toFixed(2)
                                        }}
                                    </p>
                                </div>

                                <WalletCards
                                    class="h-8 w-8 text-emerald-600"
                                />
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                >
                                    Refund Transaction ID
                                    <span class="text-red-500">
                                        *
                                    </span>
                                </label>

                                <input
                                    v-model="
                                        refundTransactionId
                                    "
                                    type="text"
                                    maxlength="150"
                                    placeholder="Enter bank / UPI / transaction reference"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                />
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                >
                                    Refund Notes
                                </label>

                                <textarea
                                    v-model="
                                        refundNotes
                                    "
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Optional refund notes..."
                                    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                        >
                            <strong>
                                Please verify before confirming.
                            </strong>

                            The refund amount is calculated by
                            the system from the security deposit
                            and finalized checkout deductions.
                        </div>

                        <div
                            class="mt-6 flex justify-end gap-3"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="
                                    refundModalOpen = false
                                "
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                :disabled="
                                    refundSubmitting ||
                                    !refundTransactionId.trim() ||
                                    Number(
                                        refundDetails.refund_amount,
                                    ) <= 0
                                "
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="
                                    submitSecurityDepositRefund
                                "
                            >
                                <Loader2
                                    v-if="refundSubmitting"
                                    class="h-4 w-4 animate-spin"
                                />

                                {{
                                    refundSubmitting
                                        ? "Processing..."
                                        : "Confirm Refund"
                                }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
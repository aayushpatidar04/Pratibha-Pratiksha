<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    ArrowRightLeft,
    BedDouble,
    Building2,
    CalendarDays,
    CheckCircle2,
    Clock3,
    IndianRupee,
    MapPin,
    ReceiptIndianRupee,
    ShieldCheck,
    UserCheck,
    X,
    XCircle,
} from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
    request: {
        type: Object,
        required: true,
    },
});

const statusClasses = {
    pending: "border-amber-200 bg-amber-50 text-amber-700",
    approved: "border-emerald-200 bg-emerald-50 text-emerald-700",
    rejected: "border-red-200 bg-red-50 text-red-700",
    cancelled: "border-slate-200 bg-slate-100 text-slate-600",
};

const statusIcon = computed(() => {
    if (props.request.status === "approved") {
        return CheckCircle2;
    }

    if (props.request.status === "rejected") {
        return XCircle;
    }

    if (props.request.status === "cancelled") {
        return X;
    }

    return Clock3;
});

const formatDate = (value) => {
    if (!value) return "—";

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
    if (!value) return "—";

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
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const roomLabel = (room) => {
    if (!room) return "Not available";

    return [
        room.building_name,
        room.floor_name,
        room.room_number ? `Room ${room.room_number}` : null,
        room.bed_number ? `Bed ${room.bed_number}` : null,
    ]
        .filter(Boolean)
        .join(" · ");
};

const currentCharge = computed(() => {
    const stay = props.request.current_stay;

    if (!stay) return "—";

    if (stay.billing_basis === "daily") {
        return `${money(stay.daily_rate)} / day`;
    }

    return `${money(stay.rent_amount)} / month`;
});

const newCharge = computed(() => {
    if (props.request.new_billing_basis === "daily") {
        return `${money(props.request.new_daily_rate)} / day`;
    }

    if (props.request.new_billing_basis === "monthly") {
        return `${money(props.request.new_rent_amount)} / month`;
    }

    return "Will be confirmed during review";
});

const cancelRequest = () => {
    if (
        !confirm(
            "Cancel this room-change request? This action cannot be reversed.",
        )
    ) {
        return;
    }

    router.post(
        route("resident.room-change-requests.cancel", {
            roomChangeRequest: props.request.id,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head :title="`Room Change Request #${request.id}`" />

    <ResidentLayout title="Room Change Request">
        <div class="space-y-6">
            <!-- Back and actions -->
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <Link
                    :href="route('resident.room-change-requests.index')"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Requests
                </Link>

                <button
                    v-if="request.can_cancel"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                    @click="cancelRequest"
                >
                    <X class="h-4 w-4" />
                    Cancel Request
                </button>
            </section>

            <!-- Header -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="bg-gradient-to-r from-indigo-700 to-indigo-500 px-6 py-7 text-white"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15"
                                >
                                    <ArrowRightLeft class="h-6 w-6" />
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100"
                                    >
                                        Room Change Request
                                    </p>

                                    <h1 class="mt-1 text-2xl font-bold">
                                        Request #{{ request.id }}
                                    </h1>
                                </div>
                            </div>

                            <p class="mt-5 text-sm text-indigo-100">
                                Submitted
                                {{ formatDateTime(request.created_at) }}
                            </p>
                        </div>

                        <span
                            class="inline-flex w-fit items-center gap-2 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-xs font-bold"
                        >
                            <component :is="statusIcon" class="h-4 w-4" />

                            {{ request.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Room comparison -->
                <div
                    class="grid grid-cols-1 gap-5 border-b border-slate-100 p-6 lg:grid-cols-[1fr_auto_1fr]"
                >
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <div class="flex items-center gap-2">
                            <MapPin class="h-5 w-5 text-slate-500" />

                            <h2 class="text-sm font-bold text-slate-900">
                                Current Room
                            </h2>
                        </div>

                        <p
                            class="mt-4 text-base font-semibold leading-7 text-slate-900"
                        >
                            {{ roomLabel(request.current_stay) }}
                        </p>

                        <div
                            v-if="request.current_stay"
                            class="mt-5 grid grid-cols-2 gap-4"
                        >
                            <div>
                                <p class="text-xs text-slate-400">
                                    Billing Basis
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-slate-700"
                                >
                                    {{ request.current_stay.billing_basis }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Current Charge
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    {{ currentCharge }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-center">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-indigo-700"
                        >
                            <ArrowRight class="h-5 w-5" />
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                    >
                        <div class="flex items-center gap-2">
                            <BedDouble class="h-5 w-5 text-indigo-600" />

                            <h2 class="text-sm font-bold text-indigo-900">
                                Requested Room
                            </h2>
                        </div>

                        <p
                            class="mt-4 text-base font-semibold leading-7 text-indigo-900"
                        >
                            {{ roomLabel(request.requested_room) }}
                        </p>

                        <div class="mt-5 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-indigo-500">
                                    Bed Status
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-indigo-900"
                                >
                                    {{
                                        request.requested_room?.bed_status ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-indigo-500">
                                    Standard Monthly Rent
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-indigo-900"
                                >
                                    {{
                                        request.requested_room
                                            ?.monthly_rent_per_bed !== null
                                            ? money(
                                                  request.requested_room
                                                      .monthly_rent_per_bed,
                                              )
                                            : "—"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">
                    <!-- Main information -->
                    <div class="space-y-6 lg:col-span-2">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Reason for Room Change
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                            >
                                <p
                                    class="whitespace-pre-line text-sm leading-7 text-slate-700"
                                >
                                    {{
                                        request.reason ||
                                        "No reason was provided."
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Approved details -->
                        <div
                            v-if="request.status === 'approved'"
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700"
                                >
                                    <CheckCircle2 class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-sm font-bold text-emerald-900"
                                    >
                                        Room Transfer Approved
                                    </h2>

                                    <p
                                        class="mt-1 text-xs leading-5 text-emerald-700"
                                    >
                                        The new room and future billing details
                                        have been confirmed by administration.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2"
                            >
                                <div class="rounded-xl bg-white p-4">
                                    <div class="flex items-center gap-2">
                                        <CalendarDays
                                            class="h-4 w-4 text-emerald-600"
                                        />

                                        <p
                                            class="text-xs font-semibold text-emerald-600"
                                        >
                                            Effective From
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-bold text-emerald-900"
                                    >
                                        {{ formatDate(request.effective_from) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-white p-4">
                                    <div class="flex items-center gap-2">
                                        <ReceiptIndianRupee
                                            class="h-4 w-4 text-emerald-600"
                                        />

                                        <p
                                            class="text-xs font-semibold text-emerald-600"
                                        >
                                            New Billing
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-bold capitalize text-emerald-900"
                                    >
                                        {{ request.new_billing_basis }}
                                        · {{ newCharge }}
                                    </p>
                                </div>

                                <div
                                    v-if="request.new_expected_check_out_date"
                                    class="rounded-xl bg-white p-4"
                                >
                                    <p
                                        class="text-xs font-semibold text-emerald-600"
                                    >
                                        Expected Check-out
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-bold text-emerald-900"
                                    >
                                        {{
                                            formatDate(
                                                request.new_expected_check_out_date,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div
                                    v-if="request.new_stay"
                                    class="rounded-xl bg-white p-4"
                                >
                                    <p
                                        class="text-xs font-semibold text-emerald-600"
                                    >
                                        New Stay
                                    </p>

                                    <p
                                        class="mt-2 text-sm font-bold text-emerald-900"
                                    >
                                        {{ roomLabel(request.new_stay) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Rejection -->
                        <div
                            v-else-if="request.status === 'rejected'"
                            class="rounded-2xl border border-red-200 bg-red-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-red-600"
                                >
                                    <XCircle class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2 class="text-sm font-bold text-red-900">
                                        Request Rejected
                                    </h2>

                                    <p
                                        class="mt-2 whitespace-pre-line text-sm leading-6 text-red-800"
                                    >
                                        {{
                                            request.admin_notes ||
                                            "No rejection reason was provided."
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Cancelled -->
                        <div
                            v-else-if="request.status === 'cancelled'"
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-600"
                                >
                                    <X class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        Request Cancelled
                                    </h2>

                                    <p class="mt-1 text-sm text-slate-600">
                                        This request was cancelled on
                                        {{
                                            formatDateTime(
                                                request.cancelled_at,
                                            )
                                        }}.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Pending -->
                        <div
                            v-else
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                        >
                            <div class="flex items-start gap-3">
                                <Clock3
                                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                />

                                <div>
                                    <h2
                                        class="text-sm font-bold text-amber-900"
                                    >
                                        Awaiting Administration Review
                                    </h2>

                                    <p
                                        class="mt-1 text-xs leading-5 text-amber-700"
                                    >
                                        The requested bed is still only a
                                        preference. Availability, effective date
                                        and new billing will be confirmed during
                                        review.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                request.admin_notes &&
                                request.status !== 'rejected'
                            "
                        >
                            <h2 class="text-sm font-bold text-slate-900">
                                Administration Notes
                            </h2>

                            <div
                                class="mt-3 rounded-2xl border border-blue-200 bg-blue-50 p-5"
                            >
                                <p
                                    class="whitespace-pre-line text-sm leading-7 text-blue-800"
                                >
                                    {{ request.admin_notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h2 class="text-sm font-bold text-slate-900">
                                Request Status
                            </h2>

                            <div class="mt-5 space-y-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700"
                                    >
                                        <ArrowRightLeft class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Request Submitted
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{
                                                formatDateTime(
                                                    request.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            request.status === 'pending'
                                                ? 'bg-amber-100 text-amber-700'
                                                : request.status === 'approved'
                                                  ? 'bg-emerald-100 text-emerald-700'
                                                  : request.status ===
                                                      'rejected'
                                                    ? 'bg-red-100 text-red-700'
                                                    : 'bg-slate-100 text-slate-600'
                                        "
                                    >
                                        <component
                                            :is="statusIcon"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            {{ request.status_label }}
                                        </p>

                                        <p
                                            v-if="request.reviewed_at"
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    request.reviewed_at,
                                                )
                                            }}
                                        </p>

                                        <p
                                            v-else-if="request.cancelled_at"
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    request.cancelled_at,
                                                )
                                            }}
                                        </p>

                                        <p
                                            v-else
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            Waiting for review
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="request.reviewed_by"
                                    class="flex items-start gap-3"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700"
                                    >
                                        <UserCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Reviewed By
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ request.reviewed_by }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="request.status === 'approved'"
                                    class="flex items-start gap-3"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Transfer Completed
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            New stay record created
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h2 class="text-sm font-bold text-slate-900">
                                Billing Comparison
                            </h2>

                            <div class="mt-4 space-y-4">
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs text-slate-400">
                                        Current Charge
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-slate-900"
                                    >
                                        {{ currentCharge }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-indigo-50 p-4">
                                    <p class="text-xs text-indigo-500">
                                        Approved New Charge
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-indigo-900"
                                    >
                                        {{ newCharge }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>
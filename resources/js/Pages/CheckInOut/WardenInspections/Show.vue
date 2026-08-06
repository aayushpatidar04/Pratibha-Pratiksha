<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

import { Head, Link, useForm } from "@inertiajs/vue3";

import {
    AlertTriangle,
    ArrowLeft,
    BedDouble,
    Boxes,
    Building2,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    FileText,
    History,
    IndianRupee,
    PackageCheck,
    Save,
    ShieldAlert,
    UserRound,
    X,
    XCircle,
} from "lucide-vue-next";

import { computed, ref } from "vue";

const props = defineProps({
    checkoutRequest: {
        type: Object,
        required: true,
    },
});

const holdOpen = ref(false);
const rejectOpen = ref(false);
const approveOpen = ref(false);

const form = useForm({
    warden_review_notes:
        props.checkoutRequest.warden_review_notes || "",

    inventory_reviews:
        props.checkoutRequest.inventory_reviews.map(
            (review) => ({
                id: review.id,

                item_name: review.item_name,

                category: review.category,

                unit: review.unit,

                assigned_quantity:
                    Number(review.assigned_quantity || 0),

                condition_at_issue:
                    review.condition_at_issue,

                issue_notes:
                    review.issue_notes,

                returned_good_quantity:
                    Number(
                        review.returned_good_quantity || 0,
                    ),

                returned_damaged_quantity:
                    Number(
                        review.returned_damaged_quantity || 0,
                    ),

                missing_quantity:
                    Number(review.missing_quantity || 0),

                condition_at_review:
                    review.condition_at_review || "good",

                damage_charge:
                    Number(review.damage_charge || 0),

                review_notes:
                    review.review_notes || "",
            }),
        ),
});

const holdForm = useForm({
    warden_review_notes: "",
});

const rejectForm = useForm({
    warden_review_notes: "",
});

const reviewedQuantity = (review) =>
    Number(review.returned_good_quantity || 0) +
    Number(review.returned_damaged_quantity || 0) +
    Number(review.missing_quantity || 0);

const remainingQuantity = (review) =>
    Math.max(
        0,
        Number(review.assigned_quantity || 0) -
            reviewedQuantity(review),
    );

const hasQuantityMismatch = (review) =>
    reviewedQuantity(review) !==
    Number(review.assigned_quantity || 0);

const inventoryIsComplete = computed(() =>
    form.inventory_reviews.every(
        (review) => !hasQuantityMismatch(review),
    ),
);

const totalAssignedQuantity = computed(() =>
    form.inventory_reviews.reduce(
        (total, review) =>
            total + Number(review.assigned_quantity || 0),
        0,
    ),
);

const totalGoodQuantity = computed(() =>
    form.inventory_reviews.reduce(
        (total, review) =>
            total +
            Number(review.returned_good_quantity || 0),
        0,
    ),
);

const totalDamagedQuantity = computed(() =>
    form.inventory_reviews.reduce(
        (total, review) =>
            total +
            Number(review.returned_damaged_quantity || 0),
        0,
    ),
);

const totalMissingQuantity = computed(() =>
    form.inventory_reviews.reduce(
        (total, review) =>
            total + Number(review.missing_quantity || 0),
        0,
    ),
);

const totalDamageCharge = computed(() =>
    form.inventory_reviews.reduce(
        (total, review) =>
            total + Number(review.damage_charge || 0),
        0,
    ),
);

const canModifyInspection = computed(() =>
    [
        "assigned_to_warden",
        "warden_review_in_progress",
        "on_hold",
    ].includes(props.checkoutRequest.status),
);

const canApproveInspection = computed(
    () =>
        canModifyInspection.value &&
        inventoryIsComplete.value,
);

const normalizeQuantity = (
    review,
    field,
) => {
    review[field] = Math.max(
        0,
        Number(review[field] || 0),
    );
};

const saveDraft = () => {
    form.put(
        route(
            "warden-checkout-inspections.save",
            {
                checkoutRequest:
                    props.checkoutRequest.id,
            },
        ),
        {
            preserveScroll: true,
        },
    );
};

const openApprove = () => {
    if (!inventoryIsComplete.value) {
        return;
    }

    approveOpen.value = true;
};

const approveInspection = () => {
    form.put(
        route(
            "warden-checkout-inspections.approve",
            {
                checkoutRequest:
                    props.checkoutRequest.id,
            },
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                approveOpen.value = false;
            },
        },
    );
};

const openHold = () => {
    holdForm.reset();
    holdForm.clearErrors();

    holdForm.warden_review_notes =
        props.checkoutRequest.warden_review_notes || "";

    holdOpen.value = true;
};

const submitHold = () => {
    holdForm.put(
        route(
            "warden-checkout-inspections.hold",
            {
                checkoutRequest:
                    props.checkoutRequest.id,
            },
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                holdOpen.value = false;
            },
        },
    );
};

const openReject = () => {
    rejectForm.reset();
    rejectForm.clearErrors();

    rejectOpen.value = true;
};

const submitReject = () => {
    rejectForm.put(
        route(
            "warden-checkout-inspections.reject",
            {
                checkoutRequest:
                    props.checkoutRequest.id,
            },
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                rejectOpen.value = false;
            },
        },
    );
};

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    return new Intl.DateTimeFormat(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
        },
    ).format(
        new Date(
            `${String(value).slice(0, 10)}T00:00:00`,
        ),
    );
};

const formatDateTime = (value) => {
    if (!value) {
        return "—";
    }

    return new Intl.DateTimeFormat(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        },
    ).format(new Date(value));
};

const money = (value) =>
    Number(value || 0).toLocaleString(
        "en-IN",
        {
            style: "currency",
            currency: "INR",
            minimumFractionDigits: 2,
        },
    );

const humanize = (value) =>
    String(value || "")
        .replaceAll("_", " ")
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase(),
        );

const statusClasses = {
    assigned_to_warden:
        "border-violet-200 bg-violet-50 text-violet-700",

    warden_review_in_progress:
        "border-blue-200 bg-blue-50 text-blue-700",

    warden_approved:
        "border-emerald-200 bg-emerald-50 text-emerald-700",

    on_hold:
        "border-orange-200 bg-orange-50 text-orange-700",

    warden_rejected:
        "border-red-200 bg-red-50 text-red-700",
};

const conditionClasses = {
    new:
        "border-blue-200 bg-blue-50 text-blue-700",

    good:
        "border-emerald-200 bg-emerald-50 text-emerald-700",

    fair:
        "border-amber-200 bg-amber-50 text-amber-700",

    damaged:
        "border-red-200 bg-red-50 text-red-700",

    missing:
        "border-red-200 bg-red-50 text-red-700",
};
</script>

<template>
    <Head title="Checkout Inspection" />

    <AuthenticatedLayout>
        <template #header>
            Checkout Inspection
        </template>

        <div class="space-y-6">
            <div>
                <Link
                    :href="
                        route(
                            'warden-checkout-inspections.index',
                        )
                    "
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-violet-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Inspections
                </Link>
            </div>

            <section
                class="overflow-hidden rounded-3xl border border-violet-200 bg-[linear-gradient(135deg,#3b0764_0%,#7c3aed_52%,#a78bfa_100%)] p-6 text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <ClipboardCheck
                                    class="h-7 w-7"
                                />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.2em] text-violet-100"
                                >
                                    Room and Asset Review
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-bold text-white"
                                >
                                    {{
                                        checkoutRequest
                                            .resident.name
                                    }}
                                </h1>

                                <p
                                    class="mt-1 text-sm text-violet-100"
                                >
                                    {{
                                        checkoutRequest
                                            .resident
                                            .resident_code
                                    }}
                                </p>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-6 text-white"
                        >
                            Verify all issued hostel assets,
                            record damaged or missing items,
                            and submit your inspection
                            recommendation.
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full border border-white/30 bg-white/15 px-4 py-2 text-xs font-bold text-white"
                    >
                        {{
                            humanize(
                                checkoutRequest.status,
                            )
                        }}
                    </span>
                </div>
            </section>

            <section
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
            >
                <article
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                >
                    <Building2
                        class="h-5 w-5 text-indigo-700"
                    />

                    <p
                        class="mt-3 text-xs text-indigo-600"
                    >
                        Accommodation
                    </p>

                    <p
                        class="mt-1 text-sm font-bold text-indigo-900"
                    >
                        {{
                            checkoutRequest.stay
                                .building || "—"
                        }}
                        · Room
                        {{
                            checkoutRequest.stay.room ||
                            "—"
                        }}
                        · Bed
                        {{
                            checkoutRequest.stay.bed ||
                            "—"
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                >
                    <CalendarDays
                        class="h-5 w-5 text-blue-700"
                    />

                    <p
                        class="mt-3 text-xs text-blue-600"
                    >
                        Requested Checkout
                    </p>

                    <p
                        class="mt-1 text-sm font-bold text-blue-900"
                    >
                        {{
                            formatDate(
                                checkoutRequest
                                    .requested_checkout_date,
                            )
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <IndianRupee
                        class="h-5 w-5 text-amber-700"
                    />

                    <p
                        class="mt-3 text-xs text-amber-600"
                    >
                        Outstanding at Request
                    </p>

                    <p
                        class="mt-1 text-sm font-bold text-amber-900"
                    >
                        {{
                            money(
                                checkoutRequest
                                    .outstanding_amount_at_request,
                            )
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-5"
                >
                    <AlertTriangle
                        class="h-5 w-5 text-rose-700"
                    />

                    <p
                        class="mt-3 text-xs text-rose-600"
                    >
                        Notice Period
                    </p>

                    <p
                        class="mt-1 text-sm font-bold text-rose-900"
                    >
                        {{
                            checkoutRequest
                                .actual_notice_days
                        }}
                        /
                        {{
                            checkoutRequest
                                .required_notice_days
                        }}
                        days
                    </p>

                    <p
                        v-if="
                            checkoutRequest
                                .is_short_notice
                        "
                        class="mt-1 text-[10px] font-semibold text-red-700"
                    >
                        Short-notice request
                    </p>
                </article>
            </section>

            <section
                class="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_1fr]"
            >
                <div class="space-y-6">
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-100 px-5 py-4"
                        >
                            <h2
                                class="flex items-center gap-2 text-base font-bold text-slate-900"
                            >
                                <UserRound
                                    class="h-5 w-5 text-violet-600"
                                />
                                Resident and Stay Details
                            </h2>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2"
                        >
                            <div>
                                <p
                                    class="text-xs text-slate-400"
                                >
                                    Resident Name
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        checkoutRequest
                                            .resident.name
                                    }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-xs text-slate-400"
                                >
                                    Contact Number
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        checkoutRequest
                                            .resident.phone ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-xs text-slate-400"
                                >
                                    Check-In Date
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        formatDate(
                                            checkoutRequest
                                                .stay
                                                .check_in_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-xs text-slate-400"
                                >
                                    Billing Basis
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-slate-900"
                                >
                                    {{
                                        checkoutRequest
                                            .stay
                                            .billing_basis ||
                                        "—"
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="border-b border-slate-100 px-5 py-4"
                        >
                            <h2
                                class="flex items-center gap-2 text-base font-bold text-slate-900"
                            >
                                <FileText
                                    class="h-5 w-5 text-violet-600"
                                />
                                Checkout Reason
                            </h2>
                        </div>

                        <div class="space-y-4 p-5">
                            <div>
                                <p
                                    class="whitespace-pre-line text-sm leading-6 text-slate-700"
                                >
                                    {{
                                        checkoutRequest.reason
                                    }}
                                </p>
                            </div>

                            <div
                                v-if="
                                    checkoutRequest
                                        .resident_notes
                                "
                                class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-wide text-blue-700"
                                >
                                    Resident Notes
                                </p>

                                <p
                                    class="mt-1 whitespace-pre-line text-xs leading-5 text-blue-800"
                                >
                                    {{
                                        checkoutRequest
                                            .resident_notes
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <h2
                                    class="flex items-center gap-2 text-base font-bold text-slate-900"
                                >
                                    <Boxes
                                        class="h-5 w-5 text-violet-600"
                                    />
                                    Asset Inspection
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    Every assigned quantity
                                    must be marked as good,
                                    damaged or missing before
                                    approval.
                                </p>
                            </div>

                            <span
                                class="rounded-full border px-3 py-1.5 text-xs font-bold"
                                :class="
                                    inventoryIsComplete
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : 'border-amber-200 bg-amber-50 text-amber-700'
                                "
                            >
                                {{
                                    inventoryIsComplete
                                        ? "Inspection Complete"
                                        : "Quantities Pending"
                                }}
                            </span>
                        </div>

                        <div
                            v-if="
                                form.inventory_reviews
                                    .length
                            "
                            class="space-y-4 p-5"
                        >
                            <article
                                v-for="(
                                    review, index
                                ) in form.inventory_reviews"
                                :key="review.id"
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div
                                        class="flex items-start gap-3"
                                    >
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700"
                                        >
                                            <PackageCheck
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div>
                                            <h3
                                                class="text-sm font-bold text-slate-900"
                                            >
                                                {{
                                                    review.item_name
                                                }}
                                            </h3>

                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Assigned:
                                                {{
                                                    review.assigned_quantity
                                                }}
                                                {{
                                                    review.unit
                                                }}
                                            </p>

                                            <span
                                                class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                                :class="
                                                    conditionClasses[
                                                        review
                                                            .condition_at_issue
                                                    ] ||
                                                    conditionClasses.good
                                                "
                                            >
                                                Issued:
                                                {{
                                                    humanize(
                                                        review.condition_at_issue,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-xl px-4 py-3 text-center"
                                        :class="
                                            hasQuantityMismatch(
                                                review,
                                            )
                                                ? 'bg-amber-50'
                                                : 'bg-emerald-50'
                                        "
                                    >
                                        <p
                                            class="text-[10px] font-semibold uppercase tracking-wide"
                                            :class="
                                                hasQuantityMismatch(
                                                    review,
                                                )
                                                    ? 'text-amber-600'
                                                    : 'text-emerald-600'
                                            "
                                        >
                                            Remaining
                                        </p>

                                        <p
                                            class="mt-1 text-lg font-bold"
                                            :class="
                                                hasQuantityMismatch(
                                                    review,
                                                )
                                                    ? 'text-amber-900'
                                                    : 'text-emerald-900'
                                            "
                                        >
                                            {{
                                                remainingQuantity(
                                                    review,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        review.issue_notes
                                    "
                                    class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-3"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wide text-blue-700"
                                    >
                                        Issue Notes
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-blue-800"
                                    >
                                        {{
                                            review.issue_notes
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3"
                                >
                                    <div>
                                        <InputLabel
                                            value="Returned Good"
                                        />

                                        <input
                                            v-model.number="
                                                review.returned_good_quantity
                                            "
                                            type="number"
                                            min="0"
                                            :max="
                                                review.assigned_quantity
                                            "
                                            :disabled="
                                                !canModifyInspection
                                            "
                                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                            @input="
                                                normalizeQuantity(
                                                    review,
                                                    'returned_good_quantity',
                                                )
                                            "
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            value="Damaged"
                                        />

                                        <input
                                            v-model.number="
                                                review.returned_damaged_quantity
                                            "
                                            type="number"
                                            min="0"
                                            :max="
                                                review.assigned_quantity
                                            "
                                            :disabled="
                                                !canModifyInspection
                                            "
                                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                            @input="
                                                normalizeQuantity(
                                                    review,
                                                    'returned_damaged_quantity',
                                                )
                                            "
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            value="Missing"
                                        />

                                        <input
                                            v-model.number="
                                                review.missing_quantity
                                            "
                                            type="number"
                                            min="0"
                                            :max="
                                                review.assigned_quantity
                                            "
                                            :disabled="
                                                !canModifyInspection
                                            "
                                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                            @input="
                                                normalizeQuantity(
                                                    review,
                                                    'missing_quantity',
                                                )
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        hasQuantityMismatch(
                                            review,
                                        )
                                    "
                                    class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3"
                                >
                                    <AlertTriangle
                                        class="mt-0.5 h-4 w-4 shrink-0 text-amber-700"
                                    />

                                    <p
                                        class="text-xs text-amber-700"
                                    >
                                        Good, damaged and
                                        missing quantities
                                        must total
                                        {{
                                            review.assigned_quantity
                                        }}.
                                    </p>
                                </div>

                                <div
                                    class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2"
                                >
                                    <div>
                                        <InputLabel
                                            value="Condition at Review"
                                        />

                                        <select
                                            v-model="
                                                review.condition_at_review
                                            "
                                            :disabled="
                                                !canModifyInspection
                                            "
                                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                        >
                                            <option
                                                value="new"
                                            >
                                                New
                                            </option>

                                            <option
                                                value="good"
                                            >
                                                Good
                                            </option>

                                            <option
                                                value="fair"
                                            >
                                                Fair
                                            </option>

                                            <option
                                                value="damaged"
                                            >
                                                Damaged
                                            </option>

                                            <option
                                                value="missing"
                                            >
                                                Missing
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel
                                            value="Suggested Damage Charge"
                                        />

                                        <input
                                            v-model.number="
                                                review.damage_charge
                                            "
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :disabled="
                                                !canModifyInspection
                                            "
                                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel
                                        value="Inspection Notes"
                                    />

                                    <textarea
                                        v-model="
                                            review.review_notes
                                        "
                                        rows="3"
                                        maxlength="1000"
                                        :disabled="
                                            !canModifyInspection
                                        "
                                        class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                                        placeholder="Describe damage, missing item, condition or any other observation"
                                    ></textarea>
                                </div>

                                <InputError
                                    class="mt-2"
                                    :message="
                                        form.errors[
                                            `inventory_reviews.${index}.returned_good_quantity`
                                        ] ||
                                        form.errors[
                                            `inventory_reviews.${index}.returned_damaged_quantity`
                                        ] ||
                                        form.errors[
                                            `inventory_reviews.${index}.missing_quantity`
                                        ]
                                    "
                                />
                            </article>
                        </div>

                        <div
                            v-else
                            class="px-6 py-14 text-center"
                        >
                            <Boxes
                                class="mx-auto h-11 w-11 text-slate-300"
                            />

                            <p
                                class="mt-3 text-sm font-bold text-slate-700"
                            >
                                No assets assigned
                            </p>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                This resident has no active
                                inventory assignments for
                                this stay.
                            </p>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <InputLabel
                            value="Overall Warden Inspection Notes"
                        />

                        <textarea
                            v-model="
                                form.warden_review_notes
                            "
                            rows="5"
                            maxlength="3000"
                            :disabled="
                                !canModifyInspection
                            "
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"
                            placeholder="Add an overall summary of room condition, returned assets and any recommendations"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="
                                form.errors
                                    .warden_review_notes
                            "
                        />
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="text-base font-bold text-slate-900"
                        >
                            Inspection Summary
                        </h2>

                        <div
                            class="mt-5 grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-slate-50 p-4"
                            >
                                <p
                                    class="text-2xl font-bold text-slate-900"
                                >
                                    {{
                                        totalAssignedQuantity
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    Assigned
                                </p>
                            </div>

                            <div
                                class="rounded-xl bg-emerald-50 p-4"
                            >
                                <p
                                    class="text-2xl font-bold text-emerald-700"
                                >
                                    {{
                                        totalGoodQuantity
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-emerald-600"
                                >
                                    Returned Good
                                </p>
                            </div>

                            <div
                                class="rounded-xl bg-orange-50 p-4"
                            >
                                <p
                                    class="text-2xl font-bold text-orange-700"
                                >
                                    {{
                                        totalDamagedQuantity
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-orange-600"
                                >
                                    Damaged
                                </p>
                            </div>

                            <div
                                class="rounded-xl bg-red-50 p-4"
                            >
                                <p
                                    class="text-2xl font-bold text-red-700"
                                >
                                    {{
                                        totalMissingQuantity
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-red-600"
                                >
                                    Missing
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4"
                        >
                            <p
                                class="text-xs font-semibold text-violet-600"
                            >
                                Suggested Asset Charge
                            </p>

                            <p
                                class="mt-1 text-xl font-bold text-violet-900"
                            >
                                {{
                                    money(
                                        totalDamageCharge,
                                    )
                                }}
                            </p>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <History
                                class="h-5 w-5 text-violet-600"
                            />
                            Request Timeline
                        </h2>

                        <div
                            v-if="
                                checkoutRequest
                                    .histories.length
                            "
                            class="mt-5 space-y-4 border-l-2 border-slate-200 pl-5"
                        >
                            <article
                                v-for="history in checkoutRequest.histories"
                                :key="history.id"
                                class="relative"
                            >
                                <div
                                    class="absolute -left-[29px] top-1 h-3.5 w-3.5 rounded-full bg-white ring-2 ring-violet-500"
                                ></div>

                                <p
                                    class="text-xs font-bold text-slate-900"
                                >
                                    {{
                                        humanize(
                                            history.action,
                                        )
                                    }}
                                </p>

                                <p
                                    v-if="history.notes"
                                    class="mt-1 text-xs leading-5 text-slate-600"
                                >
                                    {{
                                        history.notes
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-[10px] text-slate-400"
                                >
                                    {{
                                        formatDateTime(
                                            history.created_at,
                                        )
                                    }}
                                </p>
                            </article>
                        </div>

                        <p
                            v-else
                            class="mt-4 text-xs text-slate-500"
                        >
                            No timeline entries available.
                        </p>
                    </section>

                    <section
                        v-if="
                            canModifyInspection
                        "
                        class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-bold text-violet-700 transition hover:bg-violet-100 disabled:opacity-60"
                            @click="saveDraft"
                        >
                            <Save class="h-4 w-4" />

                            {{
                                form.processing
                                    ? "Saving..."
                                    : "Save Inspection Draft"
                            }}
                        </button>

                        <button
                            type="button"
                            :disabled="
                                !canApproveInspection ||
                                form.processing
                            "
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="openApprove"
                        >
                            <CheckCircle2
                                class="h-4 w-4"
                            />
                            Approve Inspection
                        </button>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-bold text-orange-700 transition hover:bg-orange-100"
                            @click="openHold"
                        >
                            <Clock3
                                class="h-4 w-4"
                            />
                            Put on Hold
                        </button>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-100"
                            @click="openReject"
                        >
                            <XCircle
                                class="h-4 w-4"
                            />
                            Reject Inspection
                        </button>

                        <div
                            v-if="
                                !inventoryIsComplete
                            "
                            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3"
                        >
                            <AlertTriangle
                                class="mt-0.5 h-4 w-4 shrink-0 text-amber-700"
                            />

                            <p
                                class="text-xs leading-5 text-amber-700"
                            >
                                Approval is disabled until
                                every assigned quantity has
                                been classified.
                            </p>
                        </div>
                    </section>

                    <section
                        v-else
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <ShieldAlert
                                class="mt-0.5 h-5 w-5 shrink-0 text-slate-600"
                            />

                            <div>
                                <p
                                    class="text-sm font-bold text-slate-800"
                                >
                                    Inspection locked
                                </p>

                                <p
                                    class="mt-1 text-xs leading-5 text-slate-600"
                                >
                                    This inspection has
                                    already been finalized or
                                    cannot be modified in its
                                    current status.
                                </p>
                            </div>
                        </div>
                    </section>
                </aside>
            </section>
        </div>

        <!-- Approve confirmation -->
        <Modal
            :show="approveOpen"
            maxWidth="md"
            @close="approveOpen = false"
        >
            <div class="p-6">
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900"
                        >
                            Approve Inspection
                        </h2>

                        <p
                            class="mt-1 text-sm text-slate-500"
                        >
                            This sends the request to
                            administration for final review.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="approveOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p
                        class="text-sm font-bold text-emerald-900"
                    >
                        Inspection Summary
                    </p>

                    <div
                        class="mt-3 space-y-2 text-xs text-emerald-800"
                    >
                        <div
                            class="flex justify-between"
                        >
                            <span>Returned good</span>
                            <strong>{{
                                totalGoodQuantity
                            }}</strong>
                        </div>

                        <div
                            class="flex justify-between"
                        >
                            <span>Damaged</span>
                            <strong>{{
                                totalDamagedQuantity
                            }}</strong>
                        </div>

                        <div
                            class="flex justify-between"
                        >
                            <span>Missing</span>
                            <strong>{{
                                totalMissingQuantity
                            }}</strong>
                        </div>

                        <div
                            class="flex justify-between border-t border-emerald-200 pt-2"
                        >
                            <span>Suggested charge</span>
                            <strong>{{
                                money(
                                    totalDamageCharge,
                                )
                            }}</strong>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="approveOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="form.processing"
                        @click="approveInspection"
                    >
                        {{
                            form.processing
                                ? "Approving..."
                                : "Confirm Approval"
                        }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Hold modal -->
        <Modal
            :show="holdOpen"
            maxWidth="md"
            @close="holdOpen = false"
        >
            <form
                class="p-6"
                @submit.prevent="submitHold"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900"
                        >
                            Put Inspection on Hold
                        </h2>

                        <p
                            class="mt-1 text-sm text-slate-500"
                        >
                            Explain what must be completed
                            before inspection can continue.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="holdOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6">
                    <InputLabel
                        value="Hold Reason *"
                    />

                    <textarea
                        v-model="
                            holdForm.warden_review_notes
                        "
                        rows="5"
                        required
                        maxlength="3000"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500"
                    ></textarea>

                    <InputError
                        class="mt-1"
                        :message="
                            holdForm.errors
                                .warden_review_notes
                        "
                    />
                </div>

                <div
                    class="mt-6 flex justify-end gap-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="holdOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="
                            holdForm.processing
                        "
                        class="rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                    >
                        {{
                            holdForm.processing
                                ? "Saving..."
                                : "Put on Hold"
                        }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Reject modal -->
        <Modal
            :show="rejectOpen"
            maxWidth="md"
            @close="rejectOpen = false"
        >
            <form
                class="p-6"
                @submit.prevent="submitReject"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900"
                        >
                            Reject Inspection
                        </h2>

                        <p
                            class="mt-1 text-sm text-slate-500"
                        >
                            Provide a clear rejection reason
                            for administration and resident
                            records.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="rejectOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6">
                    <InputLabel
                        value="Rejection Reason *"
                    />

                    <textarea
                        v-model="
                            rejectForm.warden_review_notes
                        "
                        rows="5"
                        required
                        maxlength="3000"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                    ></textarea>

                    <InputError
                        class="mt-1"
                        :message="
                            rejectForm.errors
                                .warden_review_notes
                        "
                    />
                </div>

                <div
                    class="mt-6 flex justify-end gap-3"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="rejectOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="
                            rejectForm.processing
                        "
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                    >
                        {{
                            rejectForm.processing
                                ? "Rejecting..."
                                : "Reject Inspection"
                        }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
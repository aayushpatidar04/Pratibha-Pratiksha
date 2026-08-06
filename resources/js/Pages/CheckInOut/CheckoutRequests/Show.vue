<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

import { Head, Link, router, useForm } from "@inertiajs/vue3";

import {
    AlertTriangle,
    ArrowLeft,
    Boxes,
    Building2,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    History,
    IndianRupee,
    KeyRound,
    PackageCheck,
    RefreshCw,
    ShieldCheck,
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

const approveOpen = ref(false);
const holdOpen = ref(false);
const rejectOpen = ref(false);

const form = useForm({
    dues_clearance_status: ["clear", "waived"].includes(
        props.checkoutRequest.dues_clearance_status,
    )
        ? props.checkoutRequest.dues_clearance_status
        : "clear",

    short_notice_charge_final: Number(
        props.checkoutRequest.short_notice_charge_final ||
            props.checkoutRequest.short_notice_charge ||
            0,
    ),

    asset_damage_charge: Number(props.checkoutRequest.asset_damage_charge || 0),

    other_checkout_charge: Number(
        props.checkoutRequest.other_checkout_charge || 0,
    ),

    charge_notes: props.checkoutRequest.charge_notes || "",

    final_approval_notes: props.checkoutRequest.final_approval_notes || "",
});

const holdForm = useForm({
    final_approval_notes: "",
});

const rejectForm = useForm({
    final_approval_notes: "",
});

const wardenSuggestedCharge = computed(() =>
    props.checkoutRequest.inventory_reviews.reduce(
        (total, item) => total + Number(item.damage_charge || 0),
        0,
    ),
);

const finalCharges = computed(
    () =>
        Number(form.short_notice_charge_final || 0) +
        Number(form.asset_damage_charge || 0) +
        Number(form.other_checkout_charge || 0),
);

const canFinalApprove = computed(
    () =>
        props.checkoutRequest.status === "warden_approved" &&
        props.checkoutRequest.warden_review_status === "approved",
);

const isReadyForExit = computed(
    () => props.checkoutRequest.status === "ready_for_exit",
);

const submitFinalApproval = () => {
    form.put(
        route("checkout-requests.final-approve", {
            checkoutRequest: props.checkoutRequest.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                approveOpen.value = false;
            },
        },
    );
};

const submitHold = () => {
    holdForm.put(
        route("checkout-requests.final-hold", {
            checkoutRequest: props.checkoutRequest.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                holdOpen.value = false;
            },
        },
    );
};

const submitReject = () => {
    rejectForm.put(
        route("checkout-requests.final-reject", {
            checkoutRequest: props.checkoutRequest.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const regenerateToken = () => {
    router.put(
        route("checkout-requests.regenerate-exit-token", {
            checkoutRequest: props.checkoutRequest.id,
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

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
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
</script>

<template>
    <Head title="Checkout Workflow" />

    <AuthenticatedLayout>
        <template #header> Checkout Workflow </template>

        <div class="space-y-6">
            <Link
                :href="route('checkout-requests.index')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-rose-700"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Checkout Requests
            </Link>

            <section
                class="overflow-hidden rounded-3xl border border-rose-200 bg-[linear-gradient(135deg,#881337_0%,#e11d48_52%,#fb7185_100%)] p-6 text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15"
                            >
                                <ShieldCheck class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.2em]"
                                >
                                    Final Administration Review
                                </p>

                                <h1 class="mt-1 text-2xl font-bold">
                                    {{ checkoutRequest.resident.name }}
                                </h1>

                                <p class="mt-1 text-sm">
                                    {{ checkoutRequest.resident.resident_code }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <span
                        class="w-fit rounded-full border border-white/30 bg-white/15 px-4 py-2 text-xs font-bold"
                    >
                        {{ humanize(checkoutRequest.status) }}
                    </span>
                </div>
            </section>

            <section
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
            >
                <article
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"
                >
                    <Building2 class="h-5 w-5 text-indigo-700" />

                    <p class="mt-3 text-xs text-indigo-600">Accommodation</p>

                    <p class="mt-1 text-sm font-bold text-indigo-900">
                        {{ checkoutRequest.stay.building }}
                        · Room
                        {{ checkoutRequest.stay.room }}
                        · Bed
                        {{ checkoutRequest.stay.bed }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                >
                    <CalendarDays class="h-5 w-5 text-blue-700" />

                    <p class="mt-3 text-xs text-blue-600">Checkout Date</p>

                    <p class="mt-1 text-sm font-bold text-blue-900">
                        {{
                            formatDate(checkoutRequest.requested_checkout_date)
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                >
                    <IndianRupee class="h-5 w-5 text-amber-700" />

                    <p class="mt-3 text-xs text-amber-600">
                        Outstanding at Request
                    </p>

                    <p class="mt-1 text-sm font-bold text-amber-900">
                        {{
                            money(checkoutRequest.outstanding_amount_at_request)
                        }}
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-violet-200 bg-violet-50 p-5"
                >
                    <ClipboardCheck class="h-5 w-5 text-violet-700" />

                    <p class="mt-3 text-xs text-violet-600">Warden Review</p>

                    <p class="mt-1 text-sm font-bold text-violet-900">
                        {{ humanize(checkoutRequest.warden_review_status) }}
                    </p>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.5fr_1fr]">
                <div class="space-y-6">
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <UserRound class="h-5 w-5 text-rose-600" />
                            Resident Details
                        </h2>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-slate-400">Contact</p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{ checkoutRequest.resident.phone || "—" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Check-In Date
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        formatDate(
                                            checkoutRequest.stay.check_in_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Billing Basis
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold capitalize text-slate-900"
                                >
                                    {{ checkoutRequest.stay.billing_basis }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">Deposit</p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        money(
                                            checkoutRequest.stay.deposit_amount,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2
                                class="flex items-center gap-2 text-base font-bold text-slate-900"
                            >
                                <Boxes class="h-5 w-5 text-rose-600" />
                                Warden Asset Review
                            </h2>
                        </div>

                        <div
                            v-if="checkoutRequest.inventory_reviews.length"
                            class="divide-y divide-slate-100"
                        >
                            <article
                                v-for="item in checkoutRequest.inventory_reviews"
                                :key="item.id"
                                class="p-5"
                            >
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div>
                                        <h3
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{ item.item_name }}
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Assigned:
                                            {{ item.assigned_quantity }}
                                            {{ item.unit }}
                                        </p>
                                    </div>

                                    <p class="text-sm font-bold text-rose-700">
                                        {{ money(item.damage_charge) }}
                                    </p>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-3">
                                    <div
                                        class="rounded-xl bg-emerald-50 p-3 text-center"
                                    >
                                        <p
                                            class="text-lg font-bold text-emerald-700"
                                        >
                                            {{ item.returned_good_quantity }}
                                        </p>

                                        <p class="text-[10px] text-emerald-600">
                                            Good
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-xl bg-orange-50 p-3 text-center"
                                    >
                                        <p
                                            class="text-lg font-bold text-orange-700"
                                        >
                                            {{ item.returned_damaged_quantity }}
                                        </p>

                                        <p class="text-[10px] text-orange-600">
                                            Damaged
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-xl bg-red-50 p-3 text-center"
                                    >
                                        <p
                                            class="text-lg font-bold text-red-700"
                                        >
                                            {{ item.missing_quantity }}
                                        </p>

                                        <p class="text-[10px] text-red-600">
                                            Missing
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="item.review_notes"
                                    class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600"
                                >
                                    {{ item.review_notes }}
                                </div>
                            </article>
                        </div>

                        <div
                            v-else
                            class="px-6 py-12 text-center text-sm text-slate-500"
                        >
                            No assigned assets were recorded for this stay.
                        </div>
                    </section>

                    <section
                        v-if="canFinalApprove"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-base font-bold text-slate-900">
                            Final Charges and Clearance
                        </h2>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Dues Clearance *" />

                                <select
                                    v-model="form.dues_clearance_status"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                                >
                                    <option value="clear">Clear</option>

                                    <option value="waived">Waived</option>
                                </select>

                                <InputError
                                    :message="form.errors.dues_clearance_status"
                                />
                            </div>

                            <div>
                                <InputLabel value="Short-Notice Charge" />

                                <input
                                    v-model.number="
                                        form.short_notice_charge_final
                                    "
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                                />
                            </div>

                            <div>
                                <InputLabel value="Final Asset Damage Charge" />

                                <input
                                    v-model.number="form.asset_damage_charge"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                                />

                                <p class="mt-1 text-[10px] text-slate-500">
                                    Warden suggested:
                                    {{ money(wardenSuggestedCharge) }}
                                </p>
                            </div>

                            <div>
                                <InputLabel value="Other Checkout Charge" />

                                <input
                                    v-model.number="form.other_checkout_charge"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                                />
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel value="Charge Notes" />

                            <textarea
                                v-model="form.charge_notes"
                                rows="3"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel value="Final Approval Notes" />

                            <textarea
                                v-model="form.final_approval_notes"
                                rows="4"
                                class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                            ></textarea>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2 class="text-base font-bold text-slate-900">
                            Charge Summary
                        </h2>

                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    Short notice
                                </span>

                                <strong>{{
                                    money(form.short_notice_charge_final)
                                }}</strong>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    Asset damage
                                </span>

                                <strong>{{
                                    money(form.asset_damage_charge)
                                }}</strong>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">
                                    Other charges
                                </span>

                                <strong>{{
                                    money(form.other_checkout_charge)
                                }}</strong>
                            </div>

                            <div
                                class="flex justify-between border-t border-slate-100 pt-3 text-base"
                            >
                                <span class="font-bold text-slate-900">
                                    Total
                                </span>

                                <strong class="text-rose-700">
                                    {{ money(finalCharges) }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="isReadyForExit"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                    >
                        <div class="flex items-center gap-2">
                            <KeyRound class="h-5 w-5 text-emerald-700" />

                            <h2 class="text-base font-bold text-emerald-900">
                                Exit Authorization
                            </h2>
                        </div>

                        <div class="mt-4 rounded-xl bg-white p-4 text-center">
                            <p
                                class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                            >
                                Exit Token
                            </p>

                            <p
                                class="mt-2 break-all font-mono text-lg font-bold text-emerald-800"
                            >
                                {{ checkoutRequest.exit_token }}
                            </p>
                        </div>

                        <p class="mt-3 text-xs text-emerald-700">
                            Expires:
                            {{
                                formatDateTime(
                                    checkoutRequest.exit_token_expires_at,
                                )
                            }}
                        </p>

                        <button
                            type="button"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700"
                            @click="regenerateToken"
                        >
                            <RefreshCw class="h-4 w-4" />
                            Regenerate Token
                        </button>
                    </section>

                    <section
                        v-if="canFinalApprove"
                        class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white"
                            @click="approveOpen = true"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Final Approve
                        </button>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-bold text-orange-700"
                            @click="holdOpen = true"
                        >
                            <Clock3 class="h-4 w-4" />
                            Put on Hold
                        </button>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"
                            @click="rejectOpen = true"
                        >
                            <XCircle class="h-4 w-4" />
                            Reject Checkout
                        </button>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <History class="h-5 w-5 text-rose-600" />
                            Timeline
                        </h2>

                        <div class="mt-5 space-y-4">
                            <article
                                v-for="history in checkoutRequest.histories"
                                :key="history.id"
                                class="rounded-xl bg-slate-50 p-3"
                            >
                                <p class="text-xs font-bold text-slate-900">
                                    {{ humanize(history.action) }}
                                </p>

                                <p
                                    v-if="history.notes"
                                    class="mt-1 text-xs leading-5 text-slate-600"
                                >
                                    {{ history.notes }}
                                </p>

                                <p class="mt-2 text-[10px] text-slate-400">
                                    {{ formatDateTime(history.created_at) }}
                                </p>
                            </article>
                        </div>
                    </section>
                </aside>
            </section>
        </div>

        <Modal :show="approveOpen" maxWidth="md" @close="approveOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Confirm Final Approval
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    This will generate the resident’s exit authorization. The
                    stay will remain active until guard verification.
                </p>

                <div
                    class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p class="text-sm font-bold text-emerald-900">
                        Final checkout charges:
                        {{ money(finalCharges) }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="approveOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="form.processing"
                        @click="submitFinalApproval"
                    >
                        {{
                            form.processing
                                ? "Approving..."
                                : "Approve and Generate Exit Token"
                        }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="holdOpen" maxWidth="md" @close="holdOpen = false">
            <form class="p-6" @submit.prevent="submitHold">
                <h2 class="text-lg font-bold text-slate-900">
                    Put Checkout on Hold
                </h2>

                <div class="mt-5">
                    <InputLabel value="Hold Reason *" />

                    <textarea
                        v-model="holdForm.final_approval_notes"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>

                    <InputError
                        :message="holdForm.errors.final_approval_notes"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm"
                        @click="holdOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Put on Hold
                    </button>
                </div>
            </form>
        </Modal>

        <Modal :show="rejectOpen" maxWidth="md" @close="rejectOpen = false">
            <form class="p-6" @submit.prevent="submitReject">
                <h2 class="text-lg font-bold text-slate-900">
                    Reject Checkout
                </h2>

                <div class="mt-5">
                    <InputLabel value="Rejection Reason *" />

                    <textarea
                        v-model="rejectForm.final_approval_notes"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                    ></textarea>

                    <InputError
                        :message="rejectForm.errors.final_approval_notes"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm"
                        @click="rejectOpen = false"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Reject Checkout
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";

import { Head, router, useForm } from "@inertiajs/vue3";

import {
    AlertTriangle,
    BedDouble,
    Building2,
    CalendarCheck,
    Camera,
    CheckCircle2,
    Clock3,
    DoorOpen,
    IndianRupee,
    KeyRound,
    LogOut,
    MapPin,
    RefreshCw,
    ScanLine,
    ShieldCheck,
    UserCheck,
    UserRound,
    X,
} from "lucide-vue-next";

import { computed, ref } from "vue";

const props = defineProps({
    verifiedRequest: {
        type: Object,
        default: null,
    },

    searchedToken: {
        type: String,
        default: "",
    },
});

const completionOpen = ref(false);
const scannerOpen = ref(false);

const verifyForm = useForm({
    exit_token: props.searchedToken || "",
});

const completionForm = useForm({
    exit_token: props.searchedToken || "",

    gate_verification_notes: "",

    completion_notes: "",

    confirmed_resident_identity: false,

    confirmed_physical_exit: false,
});

const verifiedRequest = computed(() => props.verifiedRequest);

const hasVerifiedRequest = computed(() => Boolean(verifiedRequest.value?.id));

const canCompleteCheckout = computed(
    () =>
        hasVerifiedRequest.value &&
        completionForm.confirmed_resident_identity &&
        completionForm.confirmed_physical_exit &&
        !completionForm.processing,
);

const residentPhotoUrl = computed(() => {
    const value = verifiedRequest.value?.resident?.photo_url;

    if (!value) {
        return null;
    }

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/storage/")
    ) {
        return value;
    }

    return `/storage/${value}`;
});

const verifyExitToken = () => {
    verifyForm.clearErrors();

    verifyForm
        .transform((data) => ({
            exit_token: data.exit_token?.trim().toUpperCase(),
        }))
        .post(route("checkout-gate.verify"), {
            preserveScroll: true,

            onSuccess: () => {
                completionForm.reset();

                completionForm.exit_token = verifyForm.exit_token
                    ?.trim()
                    .toUpperCase();

                completionForm.confirmed_resident_identity = false;

                completionForm.confirmed_physical_exit = false;
            },
        });
};

const openCompletionModal = () => {
    if (!hasVerifiedRequest.value) {
        return;
    }

    completionForm.clearErrors();

    completionForm.exit_token =
        verifyForm.exit_token?.trim().toUpperCase() ||
        props.searchedToken?.trim().toUpperCase();

    completionForm.confirmed_resident_identity = false;

    completionForm.confirmed_physical_exit = false;

    completionOpen.value = true;
};

const closeCompletionModal = () => {
    if (completionForm.processing) {
        return;
    }

    completionOpen.value = false;
    completionForm.clearErrors();
};

const completeCheckout = () => {
    if (!verifiedRequest.value?.id || !canCompleteCheckout.value) {
        return;
    }

    completionForm
        .transform((data) => ({
            exit_token: data.exit_token?.trim().toUpperCase(),

            gate_verification_notes:
                data.gate_verification_notes?.trim() || null,

            completion_notes: data.completion_notes?.trim() || null,

            confirmed_resident_identity: Boolean(
                data.confirmed_resident_identity,
            ),

            confirmed_physical_exit: Boolean(data.confirmed_physical_exit),
        }))
        .post(
            route("checkout-gate.complete", {
                checkoutRequest: verifiedRequest.value.id,
            }),
            {
                preserveScroll: true,

                onSuccess: () => {
                    completionOpen.value = false;

                    completionForm.reset();

                    verifyForm.reset();
                },
            },
        );
};

const resetVerification = () => {
    verifyForm.reset();
    verifyForm.clearErrors();

    completionForm.reset();
    completionForm.clearErrors();

    router.visit(route("checkout-gate.index"), {
        preserveScroll: false,
        replace: true,
    });
};

/*
|--------------------------------------------------------------------------
| QR scanner hook
|--------------------------------------------------------------------------
|
| This modal is ready for camera integration. When a scanner library returns
| a decoded token, call applyScannedToken(decodedValue).
|
*/

const openScanner = () => {
    scannerOpen.value = true;
};

const closeScanner = () => {
    scannerOpen.value = false;
};

const applyScannedToken = (decodedValue) => {
    const token = String(decodedValue || "")
        .trim()
        .toUpperCase();

    if (!token) {
        return;
    }

    verifyForm.exit_token = token;
    scannerOpen.value = false;

    verifyExitToken();
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
    <Head title="Checkout Gate Verification" />

    <AuthenticatedLayout>
        <template #header> Checkout Gate Verification </template>

        <div class="space-y-6">
            <!-- Hero -->
            <section
                class="overflow-hidden rounded-3xl border border-cyan-200 bg-[linear-gradient(135deg,#164e63_0%,#0891b2_52%,#22d3ee_100%)] p-6 text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/15"
                            >
                                <ShieldCheck class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-100"
                                >
                                    Gate Operations
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-bold text-white md:text-3xl"
                                >
                                    Verify Resident Exit
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-6 text-cyan-50"
                        >
                            Scan or enter the approved exit code, verify the
                            resident’s identity, and confirm physical departure
                            from the hostel.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-white/20 bg-white/10 px-5 py-4"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-cyan-100"
                        >
                            Important
                        </p>

                        <p class="mt-1 max-w-xs text-xs leading-5 text-white">
                            Completing this step releases the room and bed,
                            finalizes inventory returns, and marks the resident
                            as left.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Token verification form -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h2
                            class="flex items-center gap-2 text-lg font-bold text-slate-900"
                        >
                            <KeyRound class="h-5 w-5 text-cyan-700" />
                            Exit Authorization Code
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Enter the code shown on the resident’s approved exit
                            pass.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100"
                        @click="openScanner"
                    >
                        <ScanLine class="h-4 w-4" />
                        Scan QR Code
                    </button>
                </div>

                <form class="mt-5" @submit.prevent="verifyExitToken">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <div class="min-w-0 flex-1">
                            <InputLabel for="exit_token" value="Exit Code *" />

                            <TextInput
                                id="exit_token"
                                v-model="verifyForm.exit_token"
                                type="text"
                                required
                                autocomplete="off"
                                class="mt-1 w-full font-mono uppercase tracking-wider"
                                placeholder="Enter approved exit code"
                            />

                            <InputError
                                class="mt-1"
                                :message="verifyForm.errors.exit_token"
                            />
                        </div>

                        <div class="flex items-end">
                            <PrimaryButton
                                type="submit"
                                class="w-full justify-center sm:w-auto"
                                :disabled="verifyForm.processing"
                            >
                                <RefreshCw
                                    class="mr-2 h-4 w-4"
                                    :class="{
                                        'animate-spin': verifyForm.processing,
                                    }"
                                />

                                {{
                                    verifyForm.processing
                                        ? "Verifying..."
                                        : "Verify Exit Code"
                                }}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Verified result -->
            <template v-if="hasVerifiedRequest">
                <section
                    class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-emerald-100 bg-emerald-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
                            >
                                <CheckCircle2 class="h-6 w-6" />
                            </div>

                            <div>
                                <h2
                                    class="text-base font-bold text-emerald-900"
                                >
                                    Valid Exit Authorization
                                </h2>

                                <p class="mt-0.5 text-xs text-emerald-700">
                                    Review the resident details carefully before
                                    completing checkout.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                            @click="resetVerification"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                            Verify Another
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-[auto_1fr]"
                    >
                        <!-- Resident identity -->
                        <div
                            class="flex flex-col items-center rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center lg:w-64"
                        >
                            <img
                                v-if="residentPhotoUrl"
                                :src="residentPhotoUrl"
                                class="h-32 w-32 rounded-2xl border-4 border-white object-cover shadow"
                                alt="Resident photo"
                            />

                            <div
                                v-else
                                class="flex h-32 w-32 items-center justify-center rounded-2xl border-4 border-white bg-slate-200 text-slate-500 shadow"
                            >
                                <UserRound class="h-12 w-12" />
                            </div>

                            <h3 class="mt-4 text-lg font-bold text-slate-900">
                                {{ verifiedRequest.resident.name }}
                            </h3>

                            <p class="mt-1 text-sm font-semibold text-cyan-700">
                                {{ verifiedRequest.resident.resident_code }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{
                                    verifiedRequest.resident.phone ||
                                    "No phone available"
                                }}
                            </p>

                            <span
                                class="mt-4 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700"
                            >
                                {{ humanize(verifiedRequest.resident.status) }}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="space-y-5">
                            <div
                                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                            >
                                <article
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"
                                >
                                    <Building2
                                        class="h-5 w-5 text-indigo-700"
                                    />

                                    <p class="mt-3 text-xs text-indigo-600">
                                        Building
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-indigo-900"
                                    >
                                        {{
                                            verifiedRequest.stay.building || "—"
                                        }}
                                    </p>
                                </article>

                                <article
                                    class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                                >
                                    <BedDouble class="h-5 w-5 text-blue-700" />

                                    <p class="mt-3 text-xs text-blue-600">
                                        Room and Bed
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-blue-900"
                                    >
                                        Room
                                        {{ verifiedRequest.stay.room || "—" }}
                                        · Bed
                                        {{ verifiedRequest.stay.bed || "—" }}
                                    </p>
                                </article>

                                <article
                                    class="rounded-xl border border-violet-200 bg-violet-50 p-4"
                                >
                                    <CalendarCheck
                                        class="h-5 w-5 text-violet-700"
                                    />

                                    <p class="mt-3 text-xs text-violet-600">
                                        Approved Exit Date
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-violet-900"
                                    >
                                        {{
                                            formatDate(
                                                verifiedRequest.requested_checkout_date,
                                            )
                                        }}
                                    </p>
                                </article>

                                <article
                                    class="rounded-xl border border-amber-200 bg-amber-50 p-4"
                                >
                                    <IndianRupee
                                        class="h-5 w-5 text-amber-700"
                                    />

                                    <p class="mt-3 text-xs text-amber-600">
                                        Final Charges
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-amber-900"
                                    >
                                        {{
                                            money(
                                                verifiedRequest.total_checkout_charges,
                                            )
                                        }}
                                    </p>
                                </article>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div
                                    class="rounded-xl border border-slate-200 p-4"
                                >
                                    <div class="flex items-center gap-2">
                                        <Clock3
                                            class="h-4 w-4 text-slate-500"
                                        />

                                        <p
                                            class="text-xs font-bold uppercase tracking-wide text-slate-500"
                                        >
                                            Token Expiry
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-semibold text-slate-900"
                                    >
                                        {{
                                            formatDateTime(
                                                verifiedRequest.exit_token_expires_at,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                                >
                                    <div class="flex items-center gap-2">
                                        <ShieldCheck
                                            class="h-4 w-4 text-emerald-700"
                                        />

                                        <p
                                            class="text-xs font-bold uppercase tracking-wide text-emerald-600"
                                        >
                                            Dues Clearance
                                        </p>
                                    </div>

                                    <p
                                        class="mt-2 text-sm font-bold capitalize text-emerald-900"
                                    >
                                        {{
                                            humanize(
                                                verifiedRequest.dues_clearance_status,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-cyan-200 bg-cyan-50 p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <UserCheck
                                        class="mt-0.5 h-5 w-5 shrink-0 text-cyan-700"
                                    />

                                    <div>
                                        <p
                                            class="text-sm font-bold text-cyan-900"
                                        >
                                            Final approval confirmed
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 text-cyan-700"
                                        >
                                            Approved by
                                            {{
                                                verifiedRequest.final_approver
                                                    ?.name || "Administration"
                                            }}. The resident is authorized for
                                            physical exit.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700"
                                @click="openCompletionModal"
                            >
                                <LogOut class="h-5 w-5" />
                                Confirm Physical Exit
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-red-200 bg-red-50 p-5"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            class="mt-0.5 h-5 w-5 shrink-0 text-red-700"
                        />

                        <div>
                            <p class="text-sm font-bold text-red-900">
                                Final and irreversible operation
                            </p>

                            <p class="mt-1 text-xs leading-5 text-red-700">
                                After confirmation, inventory returns are
                                finalized, the stay ends, the bed becomes
                                vacant, room occupancy is reduced, and the
                                resident is marked as having left.
                            </p>
                        </div>
                    </div>
                </section>
            </template>

            <!-- Empty state -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                >
                    <DoorOpen class="h-8 w-8" />
                </div>

                <h2 class="mt-4 text-base font-bold text-slate-800">
                    No exit pass verified
                </h2>

                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500"
                >
                    Scan the resident’s QR code or enter the exit authorization
                    code above to load the approved checkout details.
                </p>
            </section>
        </div>

        <!-- Final completion confirmation -->
        <Modal
            :show="completionOpen"
            maxWidth="lg"
            @close="closeCompletionModal"
        >
            <form
                v-if="verifiedRequest"
                class="flex max-h-[92vh] flex-col overflow-hidden"
                @submit.prevent="completeCheckout"
            >
                <div class="shrink-0 border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Complete Resident Checkout
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Confirm identity and physical departure before
                                completing checkout.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100"
                            :disabled="completionForm.processing"
                            @click="closeCompletionModal"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="flex items-center gap-4 rounded-2xl border border-cyan-200 bg-cyan-50 p-4"
                    >
                        <img
                            v-if="residentPhotoUrl"
                            :src="residentPhotoUrl"
                            class="h-16 w-16 rounded-xl object-cover"
                            alt="Resident photo"
                        />

                        <div
                            v-else
                            class="flex h-16 w-16 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700"
                        >
                            <UserRound class="h-7 w-7" />
                        </div>

                        <div>
                            <p class="text-base font-bold text-cyan-900">
                                {{ verifiedRequest.resident.name }}
                            </p>

                            <p class="mt-1 text-xs text-cyan-700">
                                {{ verifiedRequest.resident.resident_code }}
                                · Room
                                {{ verifiedRequest.stay.room }}
                                · Bed
                                {{ verifiedRequest.stay.bed }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                            :class="
                                completionForm.confirmed_resident_identity
                                    ? 'border-emerald-300 bg-emerald-50'
                                    : 'border-slate-200 bg-white'
                            "
                        >
                            <input
                                v-model="
                                    completionForm.confirmed_resident_identity
                                "
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />

                            <span>
                                <span
                                    class="block text-sm font-bold text-slate-900"
                                >
                                    Resident identity verified
                                </span>

                                <span
                                    class="mt-1 block text-xs leading-5 text-slate-500"
                                >
                                    I have matched the resident with the photo,
                                    name and resident code shown above.
                                </span>
                            </span>
                        </label>

                        <InputError
                            :message="
                                completionForm.errors
                                    .confirmed_resident_identity
                            "
                        />

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                            :class="
                                completionForm.confirmed_physical_exit
                                    ? 'border-emerald-300 bg-emerald-50'
                                    : 'border-slate-200 bg-white'
                            "
                        >
                            <input
                                v-model="completionForm.confirmed_physical_exit"
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            />

                            <span>
                                <span
                                    class="block text-sm font-bold text-slate-900"
                                >
                                    Physical exit confirmed
                                </span>

                                <span
                                    class="mt-1 block text-xs leading-5 text-slate-500"
                                >
                                    The resident is currently at the gate and is
                                    physically leaving the hostel premises.
                                </span>
                            </span>
                        </label>

                        <InputError
                            :message="
                                completionForm.errors.confirmed_physical_exit
                            "
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="gate_verification_notes"
                            value="Gate Verification Notes"
                        />

                        <textarea
                            id="gate_verification_notes"
                            v-model="completionForm.gate_verification_notes"
                            rows="3"
                            maxlength="2000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                            placeholder="Optional identity or gate verification notes"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="
                                completionForm.errors.gate_verification_notes
                            "
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="completion_notes"
                            value="Checkout Completion Notes"
                        />

                        <textarea
                            id="completion_notes"
                            v-model="completionForm.completion_notes"
                            rows="3"
                            maxlength="2000"
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                            placeholder="Optional checkout completion notes"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="completionForm.errors.completion_notes"
                        />
                    </div>

                    <InputError
                        :message="
                            completionForm.errors.exit_token ||
                            completionForm.errors.checkout_request ||
                            completionForm.errors.inventory
                        "
                    />

                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <div class="flex items-start gap-3">
                            <AlertTriangle
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-700"
                            />

                            <p class="text-xs leading-5 text-red-700">
                                This action cannot be undone from the gate page.
                                Confirm only after the resident is physically
                                departing.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                        :disabled="completionForm.processing"
                        @click="closeCompletionModal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canCompleteCheckout"
                    >
                        <LogOut class="h-4 w-4" />

                        {{
                            completionForm.processing
                                ? "Completing..."
                                : "Complete Checkout"
                        }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Scanner placeholder -->
        <Modal :show="scannerOpen" maxWidth="md" @close="closeScanner">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Scan Exit QR Code
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Camera scanning can be connected here using your
                            preferred QR scanner library.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="closeScanner"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-6 rounded-2xl border border-dashed border-cyan-300 bg-cyan-50 px-6 py-14 text-center"
                >
                    <Camera class="mx-auto h-12 w-12 text-cyan-600" />

                    <p class="mt-3 text-sm font-bold text-cyan-900">
                        Camera scanner area
                    </p>

                    <p
                        class="mx-auto mt-1 max-w-sm text-xs leading-5 text-cyan-700"
                    >
                        When the scanner decodes the QR, pass its value to:
                    </p>

                    <code
                        class="mt-3 inline-block rounded-lg bg-white px-3 py-2 text-xs text-cyan-800"
                    >
                        applyScannedToken(decodedValue)
                    </code>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="closeScanner"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
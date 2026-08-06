<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";

import { Head, Link } from "@inertiajs/vue3";

import {
    AlertTriangle,
    ArrowLeft,
    BedDouble,
    Building2,
    CalendarCheck,
    Check,
    CheckCircle2,
    Clock3,
    Copy,
    DoorOpen,
    Eye,
    EyeOff,
    History,
    IndianRupee,
    KeyRound,
    LogOut,
    ShieldCheck,
    UserRound,
} from "lucide-vue-next";

import { computed, ref } from "vue";

const props = defineProps({
    exitPass: {
        type: Object,
        required: true,
    },
});

const revealCode = ref(false);
const copied = ref(false);

const exitCode = computed(() => props.exitPass.exit_token || "");

const maskedCode = computed(() => {
    const code = exitCode.value;

    if (!code) {
        return "No code available";
    }

    if (code.length <= 6) {
        return "•".repeat(code.length);
    }

    return `${code.slice(0, 3)}${"•".repeat(
        Math.max(4, code.length - 6),
    )}${code.slice(-3)}`;
});

const displayedCode = computed(() =>
    revealCode.value ? exitCode.value : maskedCode.value,
);

const residentPhotoUrl = computed(() => {
    const value = props.exitPass.resident?.photo_url;

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

const tokenIsExpired = computed(() => {
    const expiresAt = props.exitPass.exit_token_expires_at;

    if (!expiresAt) {
        return false;
    }

    return new Date(expiresAt).getTime() < Date.now();
});

const copyExitCode = async () => {
    if (!exitCode.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(exitCode.value);

        copied.value = true;

        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (error) {
        revealCode.value = true;
    }
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
    <Head title="My Exit Pass" />

    <ResidentLayout>
        <div class="space-y-6">
            <Link
                :href="route('resident.checkout-requests.index')"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-emerald-700"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Checkout Requests
            </Link>

            <section
                class="overflow-hidden rounded-3xl border border-emerald-200 bg-[linear-gradient(135deg,#064e3b_0%,#059669_52%,#34d399_100%)] p-6 text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
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
                                    class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100"
                                >
                                    Approved Checkout
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-bold text-white md:text-3xl"
                                >
                                    Your Exit Pass
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm leading-6 text-emerald-50"
                        >
                            Your checkout has received final approval. Present
                            the exit code below to the gate staff when you are
                            physically leaving the hostel.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-white/20 bg-white/10 px-5 py-4"
                    >
                        <p
                            class="text-[10px] font-bold uppercase tracking-wide text-emerald-100"
                        >
                            Status
                        </p>

                        <p class="mt-1 text-sm font-bold text-white">
                            Ready for Exit
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="tokenIsExpired"
                class="rounded-2xl border border-red-300 bg-red-50 p-5"
            >
                <div class="flex items-start gap-3">
                    <AlertTriangle
                        class="mt-0.5 h-6 w-6 shrink-0 text-red-700"
                    />

                    <div>
                        <h2 class="text-sm font-bold text-red-900">
                            Exit code expired
                        </h2>

                        <p class="mt-1 text-xs leading-5 text-red-700">
                            This exit code can no longer be used. Contact the
                            administration to generate a new code.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_1fr]">
                <div class="space-y-6">
                    <section
                        class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-lg"
                    >
                        <div
                            class="border-b border-emerald-100 bg-emerald-50 px-6 py-5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"
                                >
                                    <KeyRound class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-base font-bold text-emerald-900"
                                    >
                                        Exit Authorization Code
                                    </h2>

                                    <p class="mt-0.5 text-xs text-emerald-700">
                                        Show this code only to authorized gate
                                        staff.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div
                                class="rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50 px-5 py-8 text-center"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600"
                                >
                                    Exit Code
                                </p>

                                <p
                                    class="mt-4 break-all font-mono text-2xl font-black tracking-[0.18em] text-emerald-900 sm:text-3xl"
                                >
                                    {{ displayedCode }}
                                </p>

                                <div
                                    class="mt-6 flex flex-col justify-center gap-3 sm:flex-row"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                        @click="revealCode = !revealCode"
                                    >
                                        <EyeOff
                                            v-if="revealCode"
                                            class="h-4 w-4"
                                        />

                                        <Eye v-else class="h-4 w-4" />

                                        {{
                                            revealCode
                                                ? "Hide Code"
                                                : "Reveal Code"
                                        }}
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                        @click="copyExitCode"
                                    >
                                        <Check v-if="copied" class="h-4 w-4" />

                                        <Copy v-else class="h-4 w-4" />

                                        {{ copied ? "Copied" : "Copy Code" }}
                                    </button>
                                </div>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wide text-slate-400"
                                    >
                                        Generated At
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-semibold text-slate-800"
                                    >
                                        {{
                                            formatDateTime(
                                                exitPass.exit_token_generated_at,
                                            )
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border p-4"
                                    :class="
                                        tokenIsExpired
                                            ? 'border-red-200 bg-red-50'
                                            : 'border-blue-200 bg-blue-50'
                                    "
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wide"
                                        :class="
                                            tokenIsExpired
                                                ? 'text-red-500'
                                                : 'text-blue-500'
                                        "
                                    >
                                        Valid Until
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-semibold"
                                        :class="
                                            tokenIsExpired
                                                ? 'text-red-800'
                                                : 'text-blue-800'
                                        "
                                    >
                                        {{
                                            formatDateTime(
                                                exitPass.exit_token_expires_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <UserRound class="h-5 w-5 text-emerald-600" />
                            Resident Information
                        </h2>

                        <div
                            class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center"
                        >
                            <img
                                v-if="residentPhotoUrl"
                                :src="residentPhotoUrl"
                                class="h-24 w-24 shrink-0 rounded-2xl border border-slate-200 object-cover"
                                alt="Resident photo"
                            />

                            <div
                                v-else
                                class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                            >
                                <UserRound class="h-10 w-10" />
                            </div>

                            <div>
                                <h3 class="text-xl font-bold text-slate-900">
                                    {{ exitPass.resident.name }}
                                </h3>

                                <p
                                    class="mt-1 text-sm font-semibold text-emerald-700"
                                >
                                    {{ exitPass.resident.resident_code }}
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    {{ exitPass.resident.phone || "—" }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="exitPass.final_approval_notes"
                        class="rounded-2xl border border-blue-200 bg-blue-50 p-5"
                    >
                        <p
                            class="text-xs font-bold uppercase tracking-wide text-blue-600"
                        >
                            Administration Notes
                        </p>

                        <p
                            class="mt-2 whitespace-pre-line text-sm leading-6 text-blue-900"
                        >
                            {{ exitPass.final_approval_notes }}
                        </p>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <Building2 class="h-5 w-5 text-emerald-600" />
                            Stay Details
                        </h2>

                        <div class="mt-5 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500">
                                    Building
                                </span>

                                <strong
                                    class="text-right text-sm text-slate-900"
                                >
                                    {{ exitPass.stay.building || "—" }}
                                </strong>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500">
                                    Floor
                                </span>

                                <strong
                                    class="text-right text-sm text-slate-900"
                                >
                                    {{ exitPass.stay.floor || "—" }}
                                </strong>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500">
                                    Room
                                </span>

                                <strong
                                    class="text-right text-sm text-slate-900"
                                >
                                    {{ exitPass.stay.room || "—" }}
                                </strong>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500">
                                    Bed
                                </span>

                                <strong
                                    class="text-right text-sm text-slate-900"
                                >
                                    {{ exitPass.stay.bed || "—" }}
                                </strong>
                            </div>

                            <div
                                class="flex items-start justify-between gap-4 border-t border-slate-100 pt-4"
                            >
                                <span class="text-sm text-slate-500">
                                    Check-In
                                </span>

                                <strong
                                    class="text-right text-sm text-slate-900"
                                >
                                    {{
                                        formatDate(exitPass.stay.check_in_date)
                                    }}
                                </strong>
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500">
                                    Approved Exit
                                </span>

                                <strong
                                    class="text-right text-sm text-emerald-700"
                                >
                                    {{
                                        formatDate(
                                            exitPass.requested_checkout_date,
                                        )
                                    }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <h2
                            class="flex items-center gap-2 text-base font-bold text-slate-900"
                        >
                            <IndianRupee class="h-5 w-5 text-emerald-600" />
                            Final Checkout Charges
                        </h2>

                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">
                                    Short-notice charge
                                </span>

                                <strong>
                                    {{
                                        money(
                                            exitPass.short_notice_charge_final,
                                        )
                                    }}
                                </strong>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">
                                    Asset charge
                                </span>

                                <strong>
                                    {{ money(exitPass.asset_damage_charge) }}
                                </strong>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">
                                    Other charge
                                </span>

                                <strong>
                                    {{ money(exitPass.other_checkout_charge) }}
                                </strong>
                            </div>

                            <div
                                class="flex justify-between gap-4 border-t border-slate-100 pt-3"
                            >
                                <span class="font-bold text-slate-900">
                                    Total
                                </span>

                                <strong class="text-lg text-emerald-700">
                                    {{ money(exitPass.total_checkout_charges) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wide text-emerald-600"
                            >
                                Dues Clearance
                            </p>

                            <p class="mt-1 text-sm font-bold text-emerald-900">
                                {{ humanize(exitPass.dues_clearance_status) }}
                            </p>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-5"
                    >
                        <div class="flex items-start gap-3">
                            <LogOut
                                class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                            />

                            <div>
                                <h2 class="text-sm font-bold text-amber-900">
                                    At the hostel gate
                                </h2>

                                <div
                                    class="mt-2 space-y-2 text-xs leading-5 text-amber-800"
                                >
                                    <p>1. Open this exit pass.</p>

                                    <p>2. Reveal the exit code.</p>

                                    <p>
                                        3. Show the code and your resident
                                        details to gate staff.
                                    </p>

                                    <p>
                                        4. The guard will verify and complete
                                        your checkout.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <div class="flex items-start gap-3">
                            <DoorOpen
                                class="mt-0.5 h-5 w-5 shrink-0 text-slate-600"
                            />

                            <p class="text-xs leading-5 text-slate-600">
                                Your room and bed remain assigned until gate
                                staff confirm your physical exit. Do not share
                                this code with another resident.
                            </p>
                        </div>
                    </section>
                </aside>
            </section>
        </div>
    </ResidentLayout>
</template>
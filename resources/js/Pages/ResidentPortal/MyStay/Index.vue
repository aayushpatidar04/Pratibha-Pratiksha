<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head } from "@inertiajs/vue3";
import {
    BedDouble,
    Building2,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    DoorOpen,
    IndianRupee,
    MapPin,
    ShieldCheck,
    UserCheck,
} from "lucide-vue-next";

defineProps({
    stay: Object,
});

const money = (value) => {
    return Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
    });
};

const formatDate = (value) => {
    if (!value) return "—";
    const date = new Date(value);
    if (isNaN(date)) return "—"; // invalid date
    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
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
</script>

<template>
    <Head title="My Stay" />

    <ResidentLayout title="My Stay">
        <div v-if="stay" class="space-y-6">
            <section
                class="overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-700 to-blue-600 p-6 text-white shadow-lg"
            >
                <div
                    class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <p class="text-sm text-cyan-100">
                            Current accommodation
                        </p>

                        <h2 class="mt-1 text-2xl font-bold">
                            {{ stay.building?.name || "Building" }}
                        </h2>

                        <p class="mt-2 text-sm text-cyan-100">
                            {{
                                stay.floor?.name ||
                                `Floor ${stay.floor?.floor_number || "—"}`
                            }}
                            · Room
                            {{ stay.room?.room_number || "—" }}
                            · Bed
                            {{ stay.bed?.bed_number || "—" }}
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full bg-white/15 px-4 py-2 text-xs font-bold uppercase tracking-wide"
                    >
                        {{ stay.status }}
                    </span>
                </div>
            </section>

            <section
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <Building2 class="h-6 w-6 text-indigo-600" />

                    <p class="mt-3 text-xs text-slate-400">
                        Building and Floor
                    </p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        {{ stay.building?.name || "—" }}
                    </p>

                    <p class="text-xs text-slate-500">
                        {{
                            stay.floor?.name ||
                            `Floor ${stay.floor?.floor_number || "—"}`
                        }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <DoorOpen class="h-6 w-6 text-blue-600" />

                    <p class="mt-3 text-xs text-slate-400">Room</p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        {{ stay.room?.room_number || "—" }}
                    </p>

                    <p class="text-xs text-slate-500">
                        Capacity:
                        {{ stay.room?.capacity || "—" }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <BedDouble class="h-6 w-6 text-emerald-600" />

                    <p class="mt-3 text-xs text-slate-400">Bed</p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        {{ stay.bed?.bed_number || "—" }}
                    </p>

                    <p class="text-xs capitalize text-slate-500">
                        {{ stay.bed?.status || "—" }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <ShieldCheck class="h-6 w-6 text-amber-600" />

                    <p class="mt-3 text-xs text-slate-400">Security Deposit</p>

                    <p class="mt-1 text-lg font-bold text-slate-900">
                        {{ money(stay.deposit_amount) }}
                    </p>

                    <p class="text-xs text-slate-500">Refundable deposit</p>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-bold text-slate-900">
                            Stay Timeline
                        </h3>

                        <p class="text-xs text-slate-400">
                            Check-in and checkout information.
                        </p>
                    </div>

                    <div class="space-y-5 p-5">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <CalendarDays class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Check-in Date
                                </p>

                                <p class="text-sm font-semibold text-slate-900">
                                    {{ formatDate(stay.check_in_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                            >
                                <CalendarClock class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Expected Check-out
                                </p>

                                <p class="text-sm font-semibold text-slate-900">
                                    {{
                                        formatDate(stay.expected_check_out_date)
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"
                            >
                                <CheckCircle2 class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Actual Check-out
                                </p>

                                <p class="text-sm font-semibold text-slate-900">
                                    {{ formatDate(stay.actual_check_out_date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h3 class="text-sm font-bold text-slate-900">
                            Billing Information
                        </h3>

                        <p class="text-xs text-slate-400">
                            Rent and billing basis for this stay.
                        </p>
                    </div>

                    <div class="space-y-5 p-5">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                            >
                                <IndianRupee class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Billing Basis
                                </p>

                                <p
                                    class="text-sm font-semibold capitalize text-slate-900"
                                >
                                    {{
                                        (
                                            stay.billing_basis ||
                                            stay.bill_type ||
                                            "monthly"
                                        ).replaceAll("_", " ")
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="stay.billing_basis === 'daily'"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <IndianRupee class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">Daily Rate</p>

                                <p class="text-sm font-semibold text-slate-900">
                                    {{ money(stay.daily_rate) }} / day
                                </p>
                            </div>
                        </div>

                        <div v-else class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <IndianRupee class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Monthly Rent
                                </p>

                                <p class="text-sm font-semibold text-slate-900">
                                    {{ money(stay.rent_amount) }} / month
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">
                        Check-in and Checkout Status
                    </h3>
                </div>

                <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center gap-2">
                            <UserCheck class="h-5 w-5 text-emerald-600" />

                            <p class="text-sm font-semibold text-slate-900">
                                Check-in
                            </p>
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Status</dt>

                                <dd
                                    class="font-semibold"
                                    :class="
                                        stay.check_in_status
                                            ? 'text-emerald-700'
                                            : 'text-amber-700'
                                    "
                                >
                                    {{
                                        stay.check_in_status
                                            ? "Completed"
                                            : "Pending"
                                    }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Checked in at</dt>

                                <dd
                                    class="text-right font-medium text-slate-900"
                                >
                                    {{ formatDateTime(stay.checked_in_at) }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Processed by</dt>

                                <dd
                                    class="text-right font-medium text-slate-900"
                                >
                                    {{ stay.checked_in_by || "—" }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center gap-2">
                            <CircleAlert class="h-5 w-5 text-amber-600" />

                            <p class="text-sm font-semibold text-slate-900">
                                Checkout
                            </p>
                        </div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Request Status</dt>

                                <dd
                                    class="font-semibold capitalize text-slate-900"
                                >
                                    {{
                                        (
                                            stay.checkout_status ||
                                            "not_requested"
                                        ).replaceAll("_", " ")
                                    }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Reviewed by</dt>

                                <dd
                                    class="text-right font-medium text-slate-900"
                                >
                                    {{ stay.checkout_reviewed_by || "—" }}
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Reviewed at</dt>

                                <dd
                                    class="text-right font-medium text-slate-900"
                                >
                                    {{
                                        formatDateTime(
                                            stay.checkout_reviewed_at,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <section
                v-if="stay.notes || stay.checkout_notes"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h3 class="text-sm font-bold text-slate-900">Stay Notes</h3>

                <div class="mt-4 space-y-4">
                    <div v-if="stay.notes">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            General Notes
                        </p>

                        <p
                            class="mt-1 whitespace-pre-line text-sm text-slate-700"
                        >
                            {{ stay.notes }}
                        </p>
                    </div>

                    <div v-if="stay.checkout_notes">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                        >
                            Checkout Notes
                        </p>

                        <p
                            class="mt-1 whitespace-pre-line text-sm text-slate-700"
                        >
                            {{ stay.checkout_notes }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <div
            v-else
            class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
        >
            <MapPin class="mx-auto h-12 w-12 text-slate-300" />

            <h2 class="mt-4 text-lg font-bold text-slate-800">
                No Current Stay Found
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                You do not currently have an active or upcoming room allotment.
                Contact the hostel office for assistance.
            </p>
        </div>
    </ResidentLayout>
</template>

<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Copy,
    Home,
    MapPin,
    ShieldCheck,
    TicketCheck,
    Trash2,
    UserCheck,
    XCircle,
} from "lucide-vue-next";
import { ref } from "vue";

const props = defineProps({
    leave: {
        type: Object,
        required: true,
    },
});

const copied = ref(false);

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

const statusClasses = {
    pending: "border-amber-200 bg-amber-50 text-amber-700",

    parent_approval_pending: "border-blue-200 bg-blue-50 text-blue-700",

    approved: "border-emerald-200 bg-emerald-50 text-emerald-700",

    rejected: "border-red-200 bg-red-50 text-red-700",

    cancelled: "border-slate-200 bg-slate-100 text-slate-600",

    expired: "border-gray-200 bg-gray-100 text-gray-500",
};

const copyGatePass = async () => {
    if (!props.leave.gate_pass_code) {
        return;
    }

    await navigator.clipboard.writeText(props.leave.gate_pass_code);

    copied.value = true;

    setTimeout(() => {
        copied.value = false;
    }, 1500);
};

const cancelLeave = () => {
    if (
        !confirm("Cancel this leave request? This action cannot be reversed.")
    ) {
        return;
    }

    router.post(
        route("resident.leaves.cancel", {
            residentLeave: props.leave.id,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Leave Details" />

    <ResidentLayout title="Leave Details">
        <div class="space-y-6">
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <Link
                    :href="route('resident.leaves.index')"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to Leaves
                </Link>

                <button
                    v-if="leave.can_cancel"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"
                    @click="cancelLeave"
                >
                    <Trash2 class="h-4 w-4" />
                    Cancel Leave
                </button>
            </section>

            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="bg-gradient-to-r from-indigo-700 to-indigo-500 p-6 text-white"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-widest text-indigo-100"
                            >
                                Leave Request
                            </p>

                            <h1 class="mt-2 text-2xl font-bold">
                                {{ leave.leave_type_label }}
                            </h1>

                            <p class="mt-2 text-sm text-indigo-100">
                                Submitted on
                                {{ formatDateTime(leave.created_at) }}
                            </p>
                        </div>

                        <span
                            class="inline-flex w-fit items-center rounded-full border border-white/20 bg-white/15 px-4 py-2 text-xs font-bold"
                        >
                            {{ leave.final_status_label }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-6 p-6 lg:grid-cols-3">
                    <div class="space-y-5 lg:col-span-2">
                        <div
                            class="grid grid-cols-1 gap-4 rounded-2xl bg-slate-50 p-5 sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs text-slate-400">From Date</p>

                                <p class="mt-1 font-bold text-slate-900">
                                    {{ formatDate(leave.from_date) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">To Date</p>

                                <p class="mt-1 font-bold text-slate-900">
                                    {{ formatDate(leave.to_date) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">Duration</p>

                                <p class="mt-1 font-bold text-slate-900">
                                    {{ leave.total_days }}
                                    day{{ leave.total_days === 1 ? "" : "s" }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400">
                                    Destination
                                </p>

                                <p class="mt-1 font-bold text-slate-900">
                                    {{ leave.destination || "Not provided" }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                Reason
                            </h2>

                            <p
                                class="mt-2 whitespace-pre-line rounded-xl border border-slate-200 p-4 text-sm leading-6 text-slate-700"
                            >
                                {{ leave.reason }}
                            </p>
                        </div>

                        <div
                            v-if="leave.gate_pass_code"
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                        >
                            <div class="flex items-center gap-2">
                                <TicketCheck class="h-5 w-5 text-emerald-700" />

                                <h2 class="text-sm font-bold text-emerald-900">
                                    Gate Pass Code
                                </h2>
                            </div>

                            <div
                                class="mt-4 flex items-center justify-between gap-4 rounded-xl bg-white px-4 py-4"
                            >
                                <p
                                    class="font-mono text-2xl font-bold tracking-widest text-emerald-800"
                                >
                                    {{ leave.gate_pass_code }}
                                </p>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700"
                                    @click="copyGatePass"
                                >
                                    <CheckCircle2
                                        v-if="copied"
                                        class="h-4 w-4"
                                    />

                                    <Copy v-else class="h-4 w-4" />

                                    {{ copied ? "Copied" : "Copy" }}
                                </button>
                            </div>

                            <p class="mt-3 text-xs text-emerald-700">
                                Present this gate-pass code at the hostel gate
                                when leaving.
                            </p>
                        </div>
                    </div>

                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h2 class="text-sm font-bold text-slate-900">
                                Approval Status
                            </h2>

                            <div class="mt-5 space-y-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            leave.parent_approval_status ===
                                            'approved'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : leave.parent_approval_status ===
                                                    'rejected'
                                                  ? 'bg-red-100 text-red-700'
                                                  : 'bg-blue-100 text-blue-700'
                                        "
                                    >
                                        <UserCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Parent Approval
                                        </p>

                                        <p
                                            class="mt-1 text-xs capitalize text-slate-500"
                                        >
                                            {{ leave.parent_approval_status }}
                                        </p>

                                        <p
                                            v-if="leave.parent_responded_at"
                                            class="mt-1 text-[10px] text-slate-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    leave.parent_responded_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            leave.admin_approval_status ===
                                            'approved'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : leave.admin_approval_status ===
                                                    'rejected'
                                                  ? 'bg-red-100 text-red-700'
                                                  : 'bg-amber-100 text-amber-700'
                                        "
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            Admin Approval
                                        </p>

                                        <p
                                            class="mt-1 text-xs capitalize text-slate-500"
                                        >
                                            {{ leave.admin_approval_status }}
                                        </p>

                                        <p
                                            v-if="leave.approved_at"
                                            class="mt-1 text-[10px] text-slate-400"
                                        >
                                            {{
                                                formatDateTime(
                                                    leave.approved_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="leave.parent_remarks || leave.admin_remarks"
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <h2 class="text-sm font-bold text-slate-900">
                                Remarks
                            </h2>

                            <div class="mt-4 space-y-4">
                                <div v-if="leave.parent_remarks">
                                    <p class="text-xs text-slate-400">
                                        Parent Remarks
                                    </p>

                                    <p
                                        class="mt-1 whitespace-pre-line text-sm text-slate-700"
                                    >
                                        {{ leave.parent_remarks }}
                                    </p>
                                </div>

                                <div v-if="leave.admin_remarks">
                                    <p class="text-xs text-slate-400">
                                        Admin Remarks
                                    </p>

                                    <p
                                        class="mt-1 whitespace-pre-line text-sm text-slate-700"
                                    >
                                        {{ leave.admin_remarks }}
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

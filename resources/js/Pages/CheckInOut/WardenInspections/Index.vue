<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    ClipboardCheck,
    Clock3,
    Eye,
    Search,
} from "lucide-vue-next";
import { ref, watch } from "vue";

const props = defineProps({
    requests: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");

let timer = null;

watch([search, status], () => {
    clearTimeout(timer);

    timer = setTimeout(() => {
        router.get(
            route(
                "warden-checkout-inspections.index",
            ),
            {
                search:
                    search.value || undefined,

                status:
                    status.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const statusClass = {
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

const label = (value) =>
    String(value || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        );

const formatDate = (value) => {
    if (!value) return "—";

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

const money = (value) =>
    Number(value || 0).toLocaleString(
        "en-IN",
        {
            style: "currency",
            currency: "INR",
        },
    );
</script>

<template>
    <Head title="Checkout Inspections" />

    <AuthenticatedLayout>
        <template #header>
            Checkout Inspections
        </template>

        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-3xl border border-violet-200 bg-[linear-gradient(135deg,#3b0764_0%,#7c3aed_52%,#a78bfa_100%)] p-6 text-white shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15"
                    >
                        <ClipboardCheck
                            class="h-7 w-7"
                        />
                    </div>

                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-[0.2em]"
                        >
                            Warden Operations
                        </p>

                        <h1
                            class="mt-1 text-2xl font-bold"
                        >
                            Checkout Inspections
                        </h1>

                        <p
                            class="mt-2 text-sm text-violet-50"
                        >
                            Review assigned rooms and
                            resident assets before final
                            checkout approval.
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="grid grid-cols-2 gap-3 md:grid-cols-4"
            >
                <div
                    class="rounded-xl border border-violet-200 bg-violet-50 p-4"
                >
                    <p
                        class="text-2xl font-bold text-violet-700"
                    >
                        {{ stats.pending || 0 }}
                    </p>
                    <p class="text-xs text-violet-600">
                        Pending
                    </p>
                </div>

                <div
                    class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                >
                    <p
                        class="text-2xl font-bold text-blue-700"
                    >
                        {{ stats.in_progress || 0 }}
                    </p>
                    <p class="text-xs text-blue-600">
                        In Progress
                    </p>
                </div>

                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p
                        class="text-2xl font-bold text-emerald-700"
                    >
                        {{ stats.approved || 0 }}
                    </p>
                    <p
                        class="text-xs text-emerald-600"
                    >
                        Approved
                    </p>
                </div>

                <div
                    class="rounded-xl border border-orange-200 bg-orange-50 p-4"
                >
                    <p
                        class="text-2xl font-bold text-orange-700"
                    >
                        {{ stats.on_hold || 0 }}
                    </p>
                    <p class="text-xs text-orange-600">
                        On Hold
                    </p>
                </div>
            </section>

            <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div
                    class="grid grid-cols-1 gap-3 md:grid-cols-2"
                >
                    <div class="relative">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-xl border-slate-300 pl-10 text-sm"
                            placeholder="Search resident..."
                        />
                    </div>

                    <select
                        v-model="status"
                        class="rounded-xl border-slate-300 text-sm"
                    >
                        <option value="">
                            All Inspections
                        </option>

                        <option
                            value="assigned_to_warden"
                        >
                            Pending
                        </option>

                        <option
                            value="warden_review_in_progress"
                        >
                            In Progress
                        </option>

                        <option
                            value="warden_approved"
                        >
                            Approved
                        </option>

                        <option value="on_hold">
                            On Hold
                        </option>

                        <option
                            value="warden_rejected"
                        >
                            Rejected
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
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <h2
                                        class="text-sm font-bold text-slate-900"
                                    >
                                        {{
                                            request.resident
                                                .name
                                        }}
                                    </h2>

                                    <span
                                        class="text-xs text-slate-400"
                                    >
                                        {{
                                            request.resident
                                                .resident_code
                                        }}
                                    </span>

                                    <span
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                        :class="
                                            statusClass[
                                                request.status
                                            ]
                                        "
                                    >
                                        {{
                                            label(
                                                request.status,
                                            )
                                        }}
                                    </span>

                                    <span
                                        v-if="
                                            request.is_short_notice
                                        "
                                        class="rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700"
                                    >
                                        Short Notice
                                    </span>
                                </div>

                                <p
                                    class="mt-2 text-xs text-slate-500"
                                >
                                    {{
                                        request.stay
                                            .building
                                    }}
                                    · Room
                                    {{
                                        request.stay.room
                                    }}
                                    · Bed
                                    {{
                                        request.stay.bed
                                    }}
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold text-slate-700"
                                >
                                    Planned checkout:
                                    {{
                                        formatDate(
                                            request.requested_checkout_date,
                                        )
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    Outstanding:
                                    {{
                                        money(
                                            request.outstanding_amount_at_request,
                                        )
                                    }}
                                </p>
                            </div>

                            <Link
                                :href="
                                    route(
                                        'warden-checkout-inspections.show',
                                        {
                                            checkoutRequest:
                                                request.id,
                                        },
                                    )
                                "
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                <Eye class="h-4 w-4" />

                                {{
                                    request.status ===
                                    'assigned_to_warden'
                                        ? 'Start Inspection'
                                        : 'Open Inspection'
                                }}

                                <ArrowRight
                                    class="h-4 w-4"
                                />
                            </Link>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="px-6 py-16 text-center"
                >
                    <CheckCircle2
                        class="mx-auto h-12 w-12 text-emerald-300"
                    />

                    <p
                        class="mt-3 text-sm font-bold text-slate-700"
                    >
                        No inspections found
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Assigned checkout inspections will
                        appear here.
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
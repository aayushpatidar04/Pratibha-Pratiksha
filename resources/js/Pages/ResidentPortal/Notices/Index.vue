<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    AlertTriangle,
    BellRing,
    CheckCircle2,
    ChevronRight,
    CircleCheck,
    Clock3,
    FileText,
    Filter,
    Megaphone,
    Paperclip,
    Pin,
    Search,
    X,
} from "lucide-vue-next";
import { reactive, ref, watch } from "vue";

const props = defineProps({
    notices: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterOpen = ref(false);

const filterForm = reactive({
    search: props.filters?.search || "",
    category: props.filters?.category || "all",
    priority: props.filters?.priority || "all",
    read_status: props.filters?.read_status || "all",
});

let searchTimer = null;

const categories = [
    {
        value: "all",
        label: "All Categories",
    },
    {
        value: "general",
        label: "General",
    },
    {
        value: "academic",
        label: "Academic",
    },
    {
        value: "hostel",
        label: "Hostel",
    },
    {
        value: "mess",
        label: "Mess",
    },
    {
        value: "maintenance",
        label: "Maintenance",
    },
    {
        value: "event",
        label: "Event",
    },
    {
        value: "payment",
        label: "Payment",
    },
    {
        value: "emergency",
        label: "Emergency",
    },
    {
        value: "policy",
        label: "Policy",
    },
    {
        value: "other",
        label: "Other",
    },
];

const priorities = [
    {
        value: "all",
        label: "All Priorities",
    },
    {
        value: "normal",
        label: "Normal",
    },
    {
        value: "important",
        label: "Important",
    },
    {
        value: "urgent",
        label: "Urgent",
    },
];

const readStatuses = [
    {
        value: "all",
        label: "All Notices",
    },
    {
        value: "unread",
        label: "Unread",
    },
    {
        value: "read",
        label: "Read",
    },
    {
        value: "acknowledgement_pending",
        label: "Acknowledgement Pending",
    },
    {
        value: "acknowledged",
        label: "Acknowledged",
    },
];

const priorityClasses = {
    normal:
        "border-slate-200 bg-slate-50 text-slate-600",

    important:
        "border-amber-200 bg-amber-50 text-amber-700",

    urgent:
        "border-red-200 bg-red-50 text-red-700",
};

const categoryClasses = {
    general:
        "bg-slate-100 text-slate-700",

    academic:
        "bg-blue-100 text-blue-700",

    hostel:
        "bg-indigo-100 text-indigo-700",

    mess:
        "bg-orange-100 text-orange-700",

    maintenance:
        "bg-yellow-100 text-yellow-700",

    event:
        "bg-purple-100 text-purple-700",

    payment:
        "bg-emerald-100 text-emerald-700",

    emergency:
        "bg-red-100 text-red-700",

    policy:
        "bg-cyan-100 text-cyan-700",

    other:
        "bg-gray-100 text-gray-700",
};

const applyFilters = () => {
    router.get(
        route("resident.notices.index"),
        {
            search:
                filterForm.search || undefined,

            category:
                filterForm.category !== "all"
                    ? filterForm.category
                    : undefined,

            priority:
                filterForm.priority !== "all"
                    ? filterForm.priority
                    : undefined,

            read_status:
                filterForm.read_status !== "all"
                    ? filterForm.read_status
                    : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterForm.search = "";
    filterForm.category = "all";
    filterForm.priority = "all";
    filterForm.read_status = "all";

    router.get(
        route("resident.notices.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => filterForm.search,
    () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            if (
                filterForm.search.length === 0 ||
                filterForm.search.length >= 3
            ) {
                applyFilters();
            }
        }, 400);
    },
);

const setReadFilter = (value) => {
    filterForm.read_status = value;
    applyFilters();
};

const formatDateTime = (value) => {
    if (!value) {
        return "—";
    }

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

const formatDate = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
};

const publishedDate = (notice) => {
    return (
        notice.published_at ||
        notice.publish_at ||
        notice.created_at
    );
};
</script>

<template>
    <Head title="Notices & Circulars" />

    <ResidentLayout title="Notices & Circulars">
        <div class="space-y-6">
            <!-- Header -->
            <section
                class="overflow-hidden rounded-3xl border border-indigo-200 bg-[linear-gradient(135deg,#312e81_0%,#4f46e5_55%,#6366f1_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-5 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <Megaphone class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                >
                                    Resident Communication
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    Notices & Circulars
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Read hostel announcements, circulars,
                            payment reminders, policy updates and
                            important instructions.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 rounded-2xl border border-white/20 bg-black/10 p-4"
                    >
                        <div class="text-center">
                            <p
                                class="text-2xl font-black text-white"
                            >
                                {{ stats.unread || 0 }}
                            </p>

                            <p
                                class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                            >
                                Unread
                            </p>
                        </div>

                        <div class="text-center">
                            <p
                                class="text-2xl font-black text-white"
                            >
                                {{
                                    stats.acknowledgement_pending ||
                                    0
                                }}
                            </p>

                            <p
                                class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                            >
                                Action Needed
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pending acknowledgement warning -->
            <section
                v-if="
                    Number(
                        stats.acknowledgement_pending || 0,
                    ) > 0
                "
                class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <AlertTriangle
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                    />

                    <div>
                        <p
                            class="text-sm font-bold text-amber-900"
                        >
                            Acknowledgement required
                        </p>

                        <p
                            class="mt-1 text-xs leading-5 text-amber-700"
                        >
                            You have
                            {{
                                stats.acknowledgement_pending
                            }}
                            notice{{
                                Number(
                                    stats.acknowledgement_pending,
                                ) === 1
                                    ? ""
                                    : "s"
                            }}
                            waiting for confirmation.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700"
                    @click="
                        setReadFilter(
                            'acknowledgement_pending',
                        )
                    "
                >
                    Review Now
                    <ChevronRight class="h-4 w-4" />
                </button>
            </section>

            <!-- Stats -->
            <section
                class="grid grid-cols-2 gap-4 lg:grid-cols-5"
            >
                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.read_status === 'all'
                            ? 'border-indigo-400 ring-2 ring-indigo-100'
                            : 'border-slate-200'
                    "
                    @click="setReadFilter('all')"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                    >
                        <Megaphone class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold text-slate-900"
                    >
                        {{ stats.total || 0 }}
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Total Notices
                    </p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.read_status ===
                        'unread'
                            ? 'border-blue-400 ring-2 ring-blue-100'
                            : 'border-slate-200'
                    "
                    @click="setReadFilter('unread')"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                    >
                        <BellRing class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold text-blue-700"
                    >
                        {{ stats.unread || 0 }}
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Unread
                    </p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.priority ===
                        'important'
                            ? 'border-amber-400 ring-2 ring-amber-100'
                            : 'border-slate-200'
                    "
                    @click="
                        filterForm.priority =
                            'important';
                        applyFilters();
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold text-amber-700"
                    >
                        {{ stats.important || 0 }}
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Important
                    </p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.read_status ===
                        'acknowledgement_pending'
                            ? 'border-red-400 ring-2 ring-red-100'
                            : 'border-slate-200'
                    "
                    @click="
                        setReadFilter(
                            'acknowledgement_pending',
                        )
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600"
                    >
                        <Clock3 class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold text-red-700"
                    >
                        {{
                            stats.acknowledgement_pending ||
                            0
                        }}
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Pending Action
                    </p>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filterForm.read_status ===
                        'acknowledged'
                            ? 'border-emerald-400 ring-2 ring-emerald-100'
                            : 'border-slate-200'
                    "
                    @click="
                        setReadFilter('acknowledged')
                    "
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >
                        <CircleCheck class="h-5 w-5" />
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold text-emerald-700"
                    >
                        {{ stats.acknowledged || 0 }}
                    </p>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Acknowledged
                    </p>
                </button>
            </section>

            <!-- Search and filters -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center"
                >
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />

                        <input
                            v-model="filterForm.search"
                            type="text"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search notice title, summary or content"
                        />
                    </div>

                    <select
                        v-model="filterForm.read_status"
                        class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="applyFilters"
                    >
                        <option
                            v-for="item in readStatuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="
                            filterOpen = !filterOpen
                        "
                    >
                        <Filter class="h-4 w-4" />
                        Filters
                    </button>

                    <button
                        v-if="
                            filterForm.search ||
                            filterForm.category !==
                                'all' ||
                            filterForm.priority !==
                                'all' ||
                            filterForm.read_status !==
                                'all'
                        "
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                        @click="clearFilters"
                    >
                        <X class="h-4 w-4" />
                        Clear
                    </button>
                </div>

                <div
                    v-if="filterOpen"
                    class="grid grid-cols-1 gap-4 border-t border-slate-100 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Category
                        </label>

                        <select
                            v-model="filterForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold text-slate-500"
                        >
                            Priority
                        </label>

                        <select
                            v-model="filterForm.priority"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="priority in priorities"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                            @click="applyFilters"
                        >
                            Apply Filters
                        </button>
                    </div>
                </div>
            </section>

            <!-- Notice cards -->
            <section
                v-if="notices.data?.length"
                class="grid grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <Link
                    v-for="notice in notices.data"
                    :key="notice.id"
                    :href="
                        route(
                            'resident.notices.show',
                            {
                                notice: notice.id,
                            },
                        )
                    "
                    class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="[
                        notice.priority === 'urgent'
                            ? 'border-red-300'
                            : notice.priority ===
                                'important'
                              ? 'border-amber-300'
                              : 'border-slate-200',

                        !notice.is_read
                            ? 'ring-2 ring-indigo-100'
                            : '',
                    ]"
                >
                    <div
                        v-if="!notice.is_read"
                        class="h-1 bg-indigo-600"
                    ></div>

                    <div class="p-5">
                        <div
                            class="flex items-start justify-between gap-4"
                        >
                            <div
                                class="flex min-w-0 items-start gap-3"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <Megaphone
                                        class="h-5 w-5"
                                    />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <Pin
                                            v-if="
                                                notice.is_pinned
                                            "
                                            class="h-4 w-4 shrink-0 text-indigo-600"
                                        />

                                        <h2
                                            class="truncate text-base font-bold text-slate-900 transition group-hover:text-indigo-700"
                                        >
                                            {{ notice.title }}
                                        </h2>
                                    </div>

                                    <p
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        Published
                                        {{
                                            formatDateTime(
                                                publishedDate(
                                                    notice,
                                                ),
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                v-if="!notice.is_read"
                                class="inline-flex shrink-0 rounded-full bg-indigo-600 px-2.5 py-1 text-[10px] font-bold text-white"
                            >
                                New
                            </span>

                            <span
                                v-else-if="
                                    notice.is_acknowledged
                                "
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700"
                            >
                                <CheckCircle2
                                    class="h-3 w-3"
                                />
                                Acknowledged
                            </span>
                        </div>

                        <div
                            class="mt-4 flex flex-wrap items-center gap-2"
                        >
                            <span
                                class="rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                :class="
                                    categoryClasses[
                                        notice.category
                                    ]
                                "
                            >
                                {{ notice.category_label }}
                            </span>

                            <span
                                class="rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="
                                    priorityClasses[
                                        notice.priority
                                    ]
                                "
                            >
                                {{ notice.priority_label }}
                            </span>

                            <span
                                v-if="
                                    notice.requires_acknowledgement &&
                                    !notice.is_acknowledged
                                "
                                class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700"
                            >
                                <Clock3 class="h-3 w-3" />
                                Confirmation required
                            </span>
                        </div>

                        <p
                            v-if="notice.summary"
                            class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600"
                        >
                            {{ notice.summary }}
                        </p>

                        <p
                            v-else
                            class="mt-4 line-clamp-3 whitespace-pre-line text-sm leading-6 text-slate-600"
                        >
                            {{ notice.content }}
                        </p>

                        <div
                            class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4"
                        >
                            <div
                                class="flex flex-wrap items-center gap-3 text-[10px] text-slate-400"
                            >
                                <span
                                    v-if="
                                        notice.attachments
                                            ?.length
                                    "
                                    class="inline-flex items-center gap-1"
                                >
                                    <Paperclip
                                        class="h-3.5 w-3.5"
                                    />

                                    {{
                                        notice.attachments
                                            .length
                                    }}
                                    attachment{{
                                        notice.attachments
                                            .length === 1
                                            ? ""
                                            : "s"
                                    }}
                                </span>

                                <span
                                    v-if="notice.expires_at"
                                >
                                    Valid until
                                    {{
                                        formatDate(
                                            notice.expires_at,
                                        )
                                    }}
                                </span>
                            </div>

                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600"
                            >
                                Read Notice
                                <ChevronRight
                                    class="h-4 w-4"
                                />
                            </span>
                        </div>
                    </div>
                </Link>
            </section>

            <!-- Empty state -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            >
                <FileText
                    class="mx-auto h-12 w-12 text-slate-300"
                />

                <h3
                    class="mt-4 text-base font-bold text-slate-700"
                >
                    No notices found
                </h3>

                <p
                    class="mt-1 text-sm text-slate-500"
                >
                    There are no notices matching the
                    selected filters.
                </p>

                <button
                    v-if="
                        filterForm.search ||
                        filterForm.category !==
                            'all' ||
                        filterForm.priority !==
                            'all' ||
                        filterForm.read_status !==
                            'all'
                    "
                    type="button"
                    class="mt-5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="clearFilters"
                >
                    Clear Filters
                </button>
            </section>

            <!-- Pagination -->
            <div
                v-if="notices.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template
                    v-for="link in notices.links"
                    :key="link.label"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-lg px-3 py-2 text-xs font-medium"
                        :class="
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-slate-600 hover:bg-slate-100'
                        "
                        preserve-scroll
                    />

                    <span
                        v-else
                        v-html="link.label"
                        class="cursor-not-allowed rounded-lg bg-white px-3 py-2 text-xs text-slate-300"
                    />
                </template>
            </div>
        </div>
    </ResidentLayout>
</template>
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    Archive,
    BellRing,
    Building2,
    CalendarClock,
    Check,
    CheckCircle2,
    ChevronDown,
    CircleAlert,
    Clock3,
    Download,
    Eye,
    FileText,
    Filter,
    Megaphone,
    Paperclip,
    Pencil,
    Pin,
    Plus,
    Search,
    Send,
    Trash2,
    Upload,
    UserRound,
    Users,
    X,
} from "lucide-vue-next";
import { computed, reactive, ref, watch } from "vue";

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

    buildings: {
        type: Array,
        default: () => [],
    },

    residents: {
        type: Array,
        default: () => [],
    },
});

const createOpen = ref(false);
const editOpen = ref(false);
const viewOpen = ref(false);
const filterOpen = ref(false);

const viewingNotice = ref(null);
const editingNotice = ref(null);

const buildingSearch = ref("");
const residentSearch = ref("");

const selectedFiles = ref([]);
const editSelectedFiles = ref([]);

const filters = reactive({
    search: props.filters?.search || "",
    status: props.filters?.status || "all",
    priority: props.filters?.priority || "all",
    category: props.filters?.category || "all",
});

let searchTimer = null;

const categories = [
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

const statuses = [
    {
        value: "draft",
        label: "Draft",
    },
    {
        value: "published",
        label: "Publish Now",
    },
    {
        value: "scheduled",
        label: "Schedule",
    },
    {
        value: "archived",
        label: "Archived",
    },
];

const filterStatuses = [
    {
        value: "all",
        label: "All Statuses",
    },
    {
        value: "draft",
        label: "Draft",
    },
    {
        value: "scheduled",
        label: "Scheduled",
    },
    {
        value: "published",
        label: "Published",
    },
    {
        value: "expired",
        label: "Expired",
    },
    {
        value: "archived",
        label: "Archived",
    },
];

const audienceTypes = [
    {
        value: "all",
        label: "All Residents",
        description: "Every active or upcoming resident can see this notice.",
        icon: Users,
    },
    {
        value: "buildings",
        label: "Selected Buildings",
        description: "Only residents currently staying in selected buildings.",
        icon: Building2,
    },
    {
        value: "residents",
        label: "Selected Residents",
        description: "Only individually selected residents.",
        icon: UserRound,
    },
];

const createForm = useForm({
    title: "",
    summary: "",
    content: "",
    category: "general",
    priority: "normal",
    status: "draft",
    audience_type: "all",
    building_ids: [],
    resident_ids: [],
    is_pinned: false,
    requires_acknowledgement: false,
    publish_at: "",
    expires_at: "",
    attachments: [],
});

const editForm = useForm({
    _method: "put",
    title: "",
    summary: "",
    content: "",
    category: "general",
    priority: "normal",
    status: "draft",
    audience_type: "all",
    building_ids: [],
    resident_ids: [],
    is_pinned: false,
    requires_acknowledgement: false,
    publish_at: "",
    expires_at: "",
    attachments: [],
    update_remarks: "",
});

const filteredBuildings = computed(() => {
    const search = buildingSearch.value.trim().toLowerCase();

    if (!search) {
        return props.buildings;
    }

    return props.buildings.filter((building) =>
        building.name?.toLowerCase().includes(search),
    );
});

const filteredResidents = computed(() => {
    const search = residentSearch.value.trim().toLowerCase();

    if (!search) {
        return props.residents;
    }

    return props.residents.filter((resident) => {
        return (
            resident.name?.toLowerCase().includes(search) ||
            resident.resident_code?.toLowerCase().includes(search)
        );
    });
});

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.status !== "all") {
        count++;
    }

    if (filters.priority !== "all") {
        count++;
    }

    if (filters.category !== "all") {
        count++;
    }

    return count;
});

const statusClasses = {
    draft: "border-slate-200 bg-slate-100 text-slate-700",

    scheduled: "border-blue-200 bg-blue-50 text-blue-700",

    published: "border-emerald-200 bg-emerald-50 text-emerald-700",

    expired: "border-amber-200 bg-amber-50 text-amber-700",

    archived: "border-gray-200 bg-gray-100 text-gray-600",
};

const priorityClasses = {
    normal: "border-slate-200 bg-slate-50 text-slate-600",

    important: "border-amber-200 bg-amber-50 text-amber-700",

    urgent: "border-red-200 bg-red-50 text-red-700",
};

const applyFilters = () => {
    router.get(
        route("notices.index"),
        {
            search: filters.search || undefined,

            status: filters.status !== "all" ? filters.status : undefined,

            priority: filters.priority !== "all" ? filters.priority : undefined,

            category: filters.category !== "all" ? filters.category : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(
    () => filters.search,
    () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            if (filters.search.length === 0 || filters.search.length >= 3) {
                applyFilters();
            }
        }, 400);
    },
);

const clearFilters = () => {
    filters.search = "";
    filters.status = "all";
    filters.priority = "all";
    filters.category = "all";

    router.get(
        route("notices.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const formatDateTimeInput = (value) => {
    if (!value) {
        return "";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const offset = date.getTimezoneOffset();
    const localDate = new Date(date.getTime() - offset * 60000);

    return localDate.toISOString().slice(0, 16);
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

const audienceLabel = (notice) => {
    if (notice.audience_type === "all") {
        return "All residents";
    }

    if (notice.audience_type === "buildings") {
        const names =
            notice.buildings?.map((building) => building.name).join(", ") || "";

        return names || "Selected buildings";
    }

    const names =
        notice.residents
            ?.slice(0, 3)
            .map((resident) => resident.name)
            .join(", ") || "";

    const extra = (notice.residents?.length || 0) - 3;

    return extra > 0
        ? `${names} +${extra} more`
        : names || "Selected residents";
};

const resetCreateForm = () => {
    createForm.reset();
    createForm.clearErrors();

    createForm.category = "general";
    createForm.priority = "normal";
    createForm.status = "draft";
    createForm.audience_type = "all";
    createForm.building_ids = [];
    createForm.resident_ids = [];
    createForm.is_pinned = false;
    createForm.requires_acknowledgement = false;

    buildingSearch.value = "";
    residentSearch.value = "";
    selectedFiles.value = [];
};

const openCreate = () => {
    resetCreateForm();
    createOpen.value = true;
};

const handleCreateFiles = (event) => {
    selectedFiles.value = Array.from(event.target.files || []);

    createForm.attachments = selectedFiles.value;
};

const removeCreateFile = (index) => {
    selectedFiles.value.splice(index, 1);

    createForm.attachments = [...selectedFiles.value];
};

const submitCreate = () => {
    createForm.post(route("notices.store"), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            createOpen.value = false;
            resetCreateForm();
        },
    });
};

const openEdit = (notice) => {
    editingNotice.value = notice;

    editForm.reset();
    editForm.clearErrors();

    Object.assign(editForm, {
        _method: "put",
        title: notice.title || "",
        summary: notice.summary || "",
        content: notice.content || "",
        category: notice.category || "general",
        priority: notice.priority || "normal",

        status: notice.status === "expired" ? "published" : notice.status,

        audience_type: notice.audience_type || "all",

        building_ids: notice.buildings?.map((building) => building.id) || [],

        resident_ids: notice.residents?.map((resident) => resident.id) || [],

        is_pinned: Boolean(notice.is_pinned),

        requires_acknowledgement: Boolean(notice.requires_acknowledgement),

        publish_at: formatDateTimeInput(notice.publish_at),

        expires_at: formatDateTimeInput(notice.expires_at),

        attachments: [],
        update_remarks: "",
    });

    editSelectedFiles.value = [];
    buildingSearch.value = "";
    residentSearch.value = "";

    editOpen.value = true;
};

const handleEditFiles = (event) => {
    editSelectedFiles.value = Array.from(event.target.files || []);

    editForm.attachments = editSelectedFiles.value;
};

const removeEditFile = (index) => {
    editSelectedFiles.value.splice(index, 1);

    editForm.attachments = [...editSelectedFiles.value];
};

const submitEdit = () => {
    if (!editingNotice.value) {
        return;
    }

    editForm.post(
        route("notices.update", {
            notice: editingNotice.value.id,
        }),
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                editOpen.value = false;
                editingNotice.value = null;

                editForm.reset();
                editSelectedFiles.value = [];
            },
        },
    );
};

const openView = (notice) => {
    viewingNotice.value = notice;
    viewOpen.value = true;
};

const destroyNotice = (notice) => {
    if (!confirm(`Delete "${notice.title}"? This action cannot be reversed.`)) {
        return;
    }

    router.delete(
        route("notices.destroy", {
            notice: notice.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const deleteAttachment = (notice, attachment) => {
    if (!confirm(`Remove attachment "${attachment.original_name}"?`)) {
        return;
    }

    router.delete(
        route("notices.attachments.destroy", {
            notice: notice.id,
            attachment: attachment.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                if (editingNotice.value) {
                    editingNotice.value.attachments =
                        editingNotice.value.attachments.filter(
                            (item) => item.id !== attachment.id,
                        );
                }
            },
        },
    );
};

const toggleId = (collection, id) => {
    const index = collection.indexOf(id);

    if (index >= 0) {
        collection.splice(index, 1);
    } else {
        collection.push(id);
    }
};

const selectAllBuildings = (form) => {
    form.building_ids = filteredBuildings.value.map((building) => building.id);
};

const clearBuildings = (form) => {
    form.building_ids = [];
};

const selectAllResidents = (form) => {
    form.resident_ids = filteredResidents.value.map((resident) => resident.id);
};

const clearResidents = (form) => {
    form.resident_ids = [];
};

const fileSize = (bytes) => {
    if (!bytes) {
        return "Unknown size";
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <Head title="Notices & Circulars" />

    <AuthenticatedLayout>
        <template #header> Notices & Circulars </template>

        <div class="space-y-6">
            <!-- Header -->
            <section
                class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-gray-900"
                    >
                        <Megaphone class="h-6 w-6 text-indigo-600" />

                        Notices & Circulars
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Publish announcements to all residents, selected
                        hostels, or individual residents.
                    </p>
                </div>

                <PrimaryButton type="button" @click="openCreate">
                    <Plus class="h-4 w-4" />
                    Create Notice
                </PrimaryButton>
            </section>

            <!-- Stats -->
            <section
                class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6"
            >
                <button
                    v-for="item in [
                        {
                            key: 'all',
                            label: 'Total',
                            count: stats.total,
                        },
                        {
                            key: 'draft',
                            label: 'Draft',
                            count: stats.draft,
                        },
                        {
                            key: 'scheduled',
                            label: 'Scheduled',
                            count: stats.scheduled,
                        },
                        {
                            key: 'published',
                            label: 'Published',
                            count: stats.published,
                        },
                        {
                            key: 'expired',
                            label: 'Expired',
                            count: stats.expired,
                        },
                        {
                            key: 'archived',
                            label: 'Archived',
                            count: stats.archived,
                        },
                    ]"
                    :key="item.key"
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        filters.status === item.key
                            ? 'border-indigo-400 ring-2 ring-indigo-100'
                            : 'border-gray-100'
                    "
                    @click="
                        filters.status = item.key;
                        applyFilters();
                    "
                >
                    <p class="text-2xl font-bold text-gray-900">
                        {{ item.count || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        {{ item.label }}
                    </p>
                </button>
            </section>

            <!-- Filters -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center"
                >
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />

                        <input
                            v-model="filters.search"
                            type="text"
                            class="w-full rounded-xl border-gray-300 py-2.5 pl-10 text-sm"
                            placeholder="Search title, summary or notice content"
                        />
                    </div>

                    <select
                        v-model="filters.status"
                        class="rounded-xl border-gray-300 text-sm"
                        @change="applyFilters"
                    >
                        <option
                            v-for="status in filterStatuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="filterOpen = !filterOpen"
                    >
                        <Filter class="h-4 w-4" />
                        Filters

                        <span
                            v-if="activeFilterCount > 0"
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 text-[10px] font-bold text-indigo-700"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </button>

                    <button
                        v-if="filters.search || activeFilterCount > 0"
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
                    class="grid grid-cols-1 gap-4 border-t border-gray-100 bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div>
                        <InputLabel value="Priority" />

                        <select
                            v-model="filters.priority"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                        >
                            <option value="all">All Priorities</option>

                            <option
                                v-for="priority in priorities"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Category" />

                        <select
                            v-model="filters.category"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                        >
                            <option value="all">All Categories</option>

                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                            @click="applyFilters"
                        >
                            Apply Filters
                        </button>
                    </div>
                </div>
            </section>

            <!-- Notices -->
            <section
                v-if="notices.data?.length"
                class="grid grid-cols-1 gap-4 xl:grid-cols-2"
            >
                <article
                    v-for="notice in notices.data"
                    :key="notice.id"
                    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    :class="
                        notice.priority === 'urgent'
                            ? 'border-red-200'
                            : notice.priority === 'important'
                              ? 'border-amber-200'
                              : 'border-gray-100'
                    "
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <Megaphone class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <Pin
                                            v-if="notice.is_pinned"
                                            class="h-4 w-4 shrink-0 text-indigo-600"
                                        />

                                        <h2
                                            class="truncate text-base font-bold text-gray-900"
                                        >
                                            {{ notice.title }}
                                        </h2>
                                    </div>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ notice.category_label }}
                                        · Created
                                        {{ formatDateTime(notice.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="statusClasses[notice.status]"
                            >
                                {{ notice.status_label }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span
                                class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold"
                                :class="priorityClasses[notice.priority]"
                            >
                                {{ notice.priority_label }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600"
                            >
                                <Users class="h-3 w-3" />
                                {{ audienceLabel(notice) }}
                            </span>

                            <span
                                v-if="notice.requires_acknowledgement"
                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700"
                            >
                                <CheckCircle2 class="h-3 w-3" />
                                Acknowledgement required
                            </span>
                        </div>

                        <p
                            v-if="notice.summary"
                            class="mt-4 line-clamp-2 text-sm leading-6 text-gray-600"
                        >
                            {{ notice.summary }}
                        </p>

                        <p
                            v-else
                            class="mt-4 line-clamp-3 whitespace-pre-line text-sm leading-6 text-gray-600"
                        >
                            {{ notice.content }}
                        </p>

                        <div
                            class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-gray-50 p-4 text-xs"
                        >
                            <div>
                                <p class="text-gray-400">Publish Date</p>

                                <p class="mt-1 font-semibold text-gray-700">
                                    {{
                                        formatDateTime(
                                            notice.publish_at ||
                                                notice.published_at,
                                        )
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Expiry</p>

                                <p class="mt-1 font-semibold text-gray-700">
                                    {{ formatDateTime(notice.expires_at) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Read Count</p>

                                <p class="mt-1 font-semibold text-gray-700">
                                    {{ notice.read_count }}
                                </p>
                            </div>

                            <div>
                                <p class="text-gray-400">Acknowledged</p>

                                <p class="mt-1 font-semibold text-gray-700">
                                    {{ notice.acknowledged_count }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="notice.attachments?.length"
                            class="mt-4 flex items-center gap-2 text-xs text-gray-500"
                        >
                            <Paperclip class="h-4 w-4" />

                            {{ notice.attachments.length }}
                            attachment{{
                                notice.attachments.length === 1 ? "" : "s"
                            }}
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3"
                    >
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-100"
                            title="View notice"
                            @click="openView(notice)"
                        >
                            <Eye class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-white text-blue-600 hover:bg-blue-50"
                            title="Edit notice"
                            @click="openEdit(notice)"
                        >
                            <Pencil class="h-4 w-4" />
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50"
                            title="Delete notice"
                            @click="destroyNotice(notice)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </article>
            </section>

            <!-- Empty -->
            <section
                v-else
                class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center"
            >
                <Megaphone class="mx-auto h-12 w-12 text-gray-300" />

                <h3 class="mt-4 text-base font-bold text-gray-700">
                    No notices found
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Create your first announcement or adjust the filters.
                </p>

                <PrimaryButton type="button" class="mt-5" @click="openCreate">
                    <Plus class="h-4 w-4" />
                    Create Notice
                </PrimaryButton>
            </section>

            <!-- Pagination -->
            <div
                v-if="notices.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <template v-for="link in notices.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="rounded-lg px-3 py-2 text-xs font-medium"
                        :class="
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-100'
                        "
                        preserve-scroll
                    />

                    <span
                        v-else
                        v-html="link.label"
                        class="cursor-not-allowed rounded-lg bg-white px-3 py-2 text-xs text-gray-300"
                    />
                </template>
            </div>
        </div>

        <!-- Create modal -->
        <Modal :show="createOpen" maxWidth="2xl" @close="createOpen = false">
            <form
                class="flex flex-col overflow-hidden"
                @submit.prevent="submitCreate"
            >
                <div
                    class="flex shrink-0 items-start justify-between border-b border-gray-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            Create Notice
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Publish or schedule a notice for residents.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                        @click="createOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <!-- Basic fields -->
                    <div>
                        <InputLabel value="Title *" />

                        <input
                            v-model="createForm.title"
                            type="text"
                            required
                            maxlength="255"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Notice title"
                        />

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.title"
                        />
                    </div>

                    <div>
                        <InputLabel value="Short Summary" />

                        <textarea
                            v-model="createForm.summary"
                            rows="2"
                            maxlength="1000"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Brief summary shown on notice cards"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.summary"
                        />
                    </div>

                    <div>
                        <InputLabel value="Full Notice Content *" />

                        <textarea
                            v-model="createForm.content"
                            rows="8"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Enter the complete notice or circular..."
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.content"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <InputLabel value="Category *" />

                            <select
                                v-model="createForm.category"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
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
                            <InputLabel value="Priority *" />

                            <select
                                v-model="createForm.priority"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
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

                        <div>
                            <InputLabel value="Publishing Status *" />

                            <select
                                v-model="createForm.status"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            >
                                <option
                                    v-for="status in statuses"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Audience -->
                    <div>
                        <InputLabel value="Audience *" />

                        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <label
                                v-for="audience in audienceTypes"
                                :key="audience.value"
                                class="cursor-pointer rounded-xl border p-4 transition"
                                :class="
                                    createForm.audience_type === audience.value
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                                        : 'border-gray-200 hover:border-indigo-200'
                                "
                            >
                                <input
                                    v-model="createForm.audience_type"
                                    type="radio"
                                    :value="audience.value"
                                    class="sr-only"
                                />

                                <component
                                    :is="audience.icon"
                                    class="h-5 w-5 text-indigo-600"
                                />

                                <p class="mt-3 text-sm font-bold text-gray-900">
                                    {{ audience.label }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-gray-500">
                                    {{ audience.description }}
                                </p>
                            </label>
                        </div>

                        <InputError
                            class="mt-1"
                            :message="createForm.errors.audience_type"
                        />
                    </div>

                    <!-- Building selector -->
                    <div
                        v-if="createForm.audience_type === 'buildings'"
                        class="rounded-2xl border border-gray-200 p-4"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    Select Buildings
                                </p>

                                <p class="text-xs text-gray-500">
                                    {{ createForm.building_ids.length }}
                                    selected
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600"
                                    @click="selectAllBuildings(createForm)"
                                >
                                    Select all visible
                                </button>

                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600"
                                    @click="clearBuildings(createForm)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <input
                            v-model="buildingSearch"
                            type="text"
                            class="mt-4 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Search buildings"
                        />

                        <div
                            class="mt-3 grid max-h-52 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2"
                        >
                            <label
                                v-for="building in filteredBuildings"
                                :key="building.id"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 hover:bg-gray-50"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        createForm.building_ids.includes(
                                            building.id,
                                        )
                                    "
                                    class="rounded border-gray-300 text-indigo-600"
                                    @change="
                                        toggleId(
                                            createForm.building_ids,
                                            building.id,
                                        )
                                    "
                                />

                                <span class="text-sm text-gray-700">
                                    {{ building.name }}
                                </span>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="createForm.errors.building_ids"
                        />
                    </div>

                    <!-- Resident selector -->
                    <div
                        v-if="createForm.audience_type === 'residents'"
                        class="rounded-2xl border border-gray-200 p-4"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    Select Residents
                                </p>

                                <p class="text-xs text-gray-500">
                                    {{ createForm.resident_ids.length }}
                                    selected
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600"
                                    @click="selectAllResidents(createForm)"
                                >
                                    Select all visible
                                </button>

                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600"
                                    @click="clearResidents(createForm)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <input
                            v-model="residentSearch"
                            type="text"
                            class="mt-4 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Search resident name or code"
                        />

                        <div
                            class="mt-3 grid max-h-64 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2"
                        >
                            <label
                                v-for="resident in filteredResidents"
                                :key="resident.id"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 hover:bg-gray-50"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        createForm.resident_ids.includes(
                                            resident.id,
                                        )
                                    "
                                    class="rounded border-gray-300 text-indigo-600"
                                    @change="
                                        toggleId(
                                            createForm.resident_ids,
                                            resident.id,
                                        )
                                    "
                                />

                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800"
                                    >
                                        {{ resident.name }}
                                    </p>

                                    <p class="text-[10px] text-gray-400">
                                        {{ resident.resident_code }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="createForm.errors.resident_ids"
                        />
                    </div>

                    <!-- Options -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4"
                        >
                            <input
                                v-model="createForm.is_pinned"
                                type="checkbox"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600"
                            />

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Pin Notice
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Show above normal notices.
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4"
                        >
                            <input
                                v-model="createForm.requires_acknowledgement"
                                type="checkbox"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600"
                            />

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Require Acknowledgement
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Residents must confirm they have read the
                                    notice.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Publish At" />

                            <input
                                v-model="createForm.publish_at"
                                type="datetime-local"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            />

                            <InputError
                                class="mt-1"
                                :message="createForm.errors.publish_at"
                            />
                        </div>

                        <div>
                            <InputLabel value="Expires At" />

                            <input
                                v-model="createForm.expires_at"
                                type="datetime-local"
                                :min="createForm.publish_at"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            />

                            <InputError
                                class="mt-1"
                                :message="createForm.errors.expires_at"
                            />
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div>
                        <InputLabel value="Attachments" />

                        <label
                            class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-8 text-center hover:border-indigo-300 hover:bg-indigo-50"
                        >
                            <Upload class="h-8 w-8 text-gray-400" />

                            <p class="mt-3 text-sm font-semibold text-gray-700">
                                Select notice attachments
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                PDF, image, Word or Excel. Maximum 10 MB each.
                            </p>

                            <input
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                class="sr-only"
                                @change="handleCreateFiles"
                            />
                        </label>

                        <div v-if="selectedFiles.length" class="mt-3 space-y-2">
                            <div
                                v-for="(file, index) in selectedFiles"
                                :key="`${file.name}-${index}`"
                                class="flex items-center justify-between rounded-xl border border-gray-200 p-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <FileText
                                        class="h-5 w-5 shrink-0 text-indigo-600"
                                    />

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-800"
                                        >
                                            {{ file.name }}
                                        </p>

                                        <p class="text-[10px] text-gray-400">
                                            {{ fileSize(file.size) }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-red-500 hover:bg-red-50"
                                    @click="removeCreateFile(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <InputError
                            class="mt-2"
                            :message="createForm.errors.attachments"
                        />
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="createForm.processing">
                        {{
                            createForm.processing ? "Saving..." : "Save Notice"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Edit modal -->
        <Modal :show="editOpen" maxWidth="2xl" @close="editOpen = false">
            <form
                v-if="editingNotice"
                class="flex flex-col overflow-hidden"
                @submit.prevent="submitEdit"
            >
                <div
                    class="flex shrink-0 items-start justify-between border-b border-gray-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            Edit Notice
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ editingNotice.title }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                        @click="editOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <div>
                        <InputLabel value="Title *" />

                        <input
                            v-model="editForm.title"
                            type="text"
                            required
                            maxlength="255"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                        />

                        <InputError
                            class="mt-1"
                            :message="editForm.errors.title"
                        />
                    </div>

                    <div>
                        <InputLabel value="Short Summary" />

                        <textarea
                            v-model="editForm.summary"
                            rows="2"
                            maxlength="1000"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                        ></textarea>
                    </div>

                    <div>
                        <InputLabel value="Full Notice Content *" />

                        <textarea
                            v-model="editForm.content"
                            rows="8"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                        ></textarea>

                        <InputError
                            class="mt-1"
                            :message="editForm.errors.content"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <InputLabel value="Category" />

                            <select
                                v-model="editForm.category"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
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
                            <InputLabel value="Priority" />

                            <select
                                v-model="editForm.priority"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
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

                        <div>
                            <InputLabel value="Status" />

                            <select
                                v-model="editForm.status"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            >
                                <option
                                    v-for="status in statuses"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Audience" />

                        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <label
                                v-for="audience in audienceTypes"
                                :key="audience.value"
                                class="cursor-pointer rounded-xl border p-4"
                                :class="
                                    editForm.audience_type === audience.value
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100'
                                        : 'border-gray-200'
                                "
                            >
                                <input
                                    v-model="editForm.audience_type"
                                    type="radio"
                                    :value="audience.value"
                                    class="sr-only"
                                />

                                <component
                                    :is="audience.icon"
                                    class="h-5 w-5 text-indigo-600"
                                />

                                <p class="mt-3 text-sm font-bold text-gray-900">
                                    {{ audience.label }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ audience.description }}
                                </p>
                            </label>
                        </div>
                    </div>

                    <div
                        v-if="editForm.audience_type === 'buildings'"
                        class="rounded-2xl border border-gray-200 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-gray-900">
                                Select Buildings
                            </p>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600"
                                    @click="selectAllBuildings(editForm)"
                                >
                                    Select all
                                </button>

                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600"
                                    @click="clearBuildings(editForm)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <input
                            v-model="buildingSearch"
                            type="text"
                            class="mt-4 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Search buildings"
                        />

                        <div
                            class="mt-3 grid max-h-52 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2"
                        >
                            <label
                                v-for="building in filteredBuildings"
                                :key="building.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        editForm.building_ids.includes(
                                            building.id,
                                        )
                                    "
                                    class="rounded border-gray-300 text-indigo-600"
                                    @change="
                                        toggleId(
                                            editForm.building_ids,
                                            building.id,
                                        )
                                    "
                                />

                                <span class="text-sm text-gray-700">
                                    {{ building.name }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div
                        v-if="editForm.audience_type === 'residents'"
                        class="rounded-2xl border border-gray-200 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-gray-900">
                                Select Residents
                            </p>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600"
                                    @click="selectAllResidents(editForm)"
                                >
                                    Select all
                                </button>

                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600"
                                    @click="clearResidents(editForm)"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <input
                            v-model="residentSearch"
                            type="text"
                            class="mt-4 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Search residents"
                        />

                        <div
                            class="mt-3 grid max-h-64 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2"
                        >
                            <label
                                v-for="resident in filteredResidents"
                                :key="resident.id"
                                class="flex items-center gap-3 rounded-xl border border-gray-200 p-3"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        editForm.resident_ids.includes(
                                            resident.id,
                                        )
                                    "
                                    class="rounded border-gray-300 text-indigo-600"
                                    @change="
                                        toggleId(
                                            editForm.resident_ids,
                                            resident.id,
                                        )
                                    "
                                />

                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800"
                                    >
                                        {{ resident.name }}
                                    </p>

                                    <p class="text-[10px] text-gray-400">
                                        {{ resident.resident_code }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label
                            class="flex items-start gap-3 rounded-xl border border-gray-200 p-4"
                        >
                            <input
                                v-model="editForm.is_pinned"
                                type="checkbox"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600"
                            />

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Pin Notice
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Keep above normal notices.
                                </p>
                            </div>
                        </label>

                        <label
                            class="flex items-start gap-3 rounded-xl border border-gray-200 p-4"
                        >
                            <input
                                v-model="editForm.requires_acknowledgement"
                                type="checkbox"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600"
                            />

                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    Require Acknowledgement
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Track resident confirmation.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Publish At" />

                            <input
                                v-model="editForm.publish_at"
                                type="datetime-local"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            />
                        </div>

                        <div>
                            <InputLabel value="Expires At" />

                            <input
                                v-model="editForm.expires_at"
                                type="datetime-local"
                                :min="editForm.publish_at"
                                class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            />
                        </div>
                    </div>

                    <div v-if="editingNotice.attachments?.length">
                        <InputLabel value="Existing Attachments" />

                        <div class="mt-2 space-y-2">
                            <div
                                v-for="attachment in editingNotice.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-xl border border-gray-200 p-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <FileText
                                        class="h-5 w-5 shrink-0 text-indigo-600"
                                    />

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-800"
                                        >
                                            {{ attachment.original_name }}
                                        </p>

                                        <p class="text-[10px] text-gray-400">
                                            {{ attachment.formatted_size }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-1">
                                    <a
                                        :href="attachment.file_url"
                                        target="_blank"
                                        class="rounded-lg p-2 text-blue-600 hover:bg-blue-50"
                                    >
                                        <Download class="h-4 w-4" />
                                    </a>

                                    <button
                                        type="button"
                                        class="rounded-lg p-2 text-red-600 hover:bg-red-50"
                                        @click="
                                            deleteAttachment(
                                                editingNotice,
                                                attachment,
                                            )
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Add More Attachments" />

                        <input
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                            class="mt-2 block w-full text-sm"
                            @change="handleEditFiles"
                        />

                        <div
                            v-if="editSelectedFiles.length"
                            class="mt-3 space-y-2"
                        >
                            <div
                                v-for="(file, index) in editSelectedFiles"
                                :key="`${file.name}-${index}`"
                                class="flex items-center justify-between rounded-xl border border-gray-200 p-3"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800"
                                    >
                                        {{ file.name }}
                                    </p>

                                    <p class="text-[10px] text-gray-400">
                                        {{ fileSize(file.size) }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-red-500"
                                    @click="removeEditFile(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Update Remarks" />

                        <textarea
                            v-model="editForm.update_remarks"
                            rows="3"
                            maxlength="2000"
                            class="mt-1 w-full rounded-xl border-gray-300 text-sm"
                            placeholder="Optional internal note explaining this update"
                        ></textarea>
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700"
                        @click="editOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="editForm.processing">
                        {{
                            editForm.processing
                                ? "Updating..."
                                : "Update Notice"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- View modal -->
        <Modal :show="viewOpen" maxWidth="2xl" @close="viewOpen = false">
            <div v-if="viewingNotice" class="overflow-y-auto">
                <div
                    class="border-b border-gray-100 bg-indigo-600 px-6 py-6 text-white"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <Pin
                                    v-if="viewingNotice.is_pinned"
                                    class="h-4 w-4"
                                />

                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-indigo-100"
                                >
                                    {{ viewingNotice.category_label }}
                                </span>
                            </div>

                            <h2 class="mt-2 text-2xl font-bold">
                                {{ viewingNotice.title }}
                            </h2>

                            <p class="mt-2 text-xs text-indigo-100">
                                Created by
                                {{ viewingNotice.created_by || "System" }}
                                ·
                                {{ formatDateTime(viewingNotice.created_at) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-white/80 hover:bg-white/10"
                            @click="viewOpen = false"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="space-y-6 p-6">
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                            :class="statusClasses[viewingNotice.status]"
                        >
                            {{ viewingNotice.status_label }}
                        </span>

                        <span
                            class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                            :class="priorityClasses[viewingNotice.priority]"
                        >
                            {{ viewingNotice.priority_label }}
                        </span>
                    </div>

                    <div
                        v-if="viewingNotice.summary"
                        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm font-medium leading-6 text-blue-800"
                    >
                        {{ viewingNotice.summary }}
                    </div>

                    <div>
                        <p
                            class="whitespace-pre-line text-sm leading-7 text-gray-700"
                        >
                            {{ viewingNotice.content }}
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 rounded-xl bg-gray-50 p-4 sm:grid-cols-2"
                    >
                        <div>
                            <p class="text-xs text-gray-400">Audience</p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ audienceLabel(viewingNotice) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">Acknowledgement</p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{
                                    viewingNotice.requires_acknowledgement
                                        ? "Required"
                                        : "Not required"
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">Publish At</p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{
                                    formatDateTime(
                                        viewingNotice.publish_at ||
                                            viewingNotice.published_at,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">Expires At</p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                {{ formatDateTime(viewingNotice.expires_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="viewingNotice.attachments?.length">
                        <h3 class="text-sm font-bold text-gray-900">
                            Attachments
                        </h3>

                        <div class="mt-3 space-y-2">
                            <a
                                v-for="attachment in viewingNotice.attachments"
                                :key="attachment.id"
                                :href="attachment.file_url"
                                target="_blank"
                                class="flex items-center justify-between rounded-xl border border-gray-200 p-3 hover:bg-gray-50"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <FileText
                                        class="h-5 w-5 shrink-0 text-indigo-600"
                                    />

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-800"
                                        >
                                            {{ attachment.original_name }}
                                        </p>

                                        <p class="text-[10px] text-gray-400">
                                            {{ attachment.formatted_size }}
                                        </p>
                                    </div>
                                </div>

                                <Download class="h-4 w-4 text-gray-500" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
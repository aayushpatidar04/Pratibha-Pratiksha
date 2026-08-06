<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import {
    AlertCircle,
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    CircleCheck,
    Clock3,
    Download,
    Eye,
    FileImage,
    FileText,
    FolderOpen,
    Plus,
    RefreshCcw,
    Search,
    ShieldCheck,
    Trash2,
    Upload,
    X,
} from "lucide-vue-next";
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
    kycDocuments: {
        type: Array,
        default: () => [],
    },

    additionalDocuments: {
        type: Object,
        required: true,
    },

    requirements: {
        type: Array,
        default: () => [],
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

const activeTab = ref("kyc");
const uploadOpen = ref(false);
const previewOpen = ref(false);

const previewDocument = ref(null);
const uploadingRequirement = ref(null);
const selectedFileName = ref("");

const filterForm = reactive({
    search: props.filters?.search || "",
    status: props.filters?.status || "all",
});

const uploadForm = useForm({
    document_type: "",
    notes: "",
    file: null,
});

let searchTimer = null;

const statusClasses = {
    pending: "border-amber-200 bg-amber-50 text-amber-700",

    verified: "border-emerald-200 bg-emerald-50 text-emerald-700",

    rejected: "border-red-200 bg-red-50 text-red-700",
};

const kycStatusClasses = {
    complete: "border-emerald-200 bg-emerald-50 text-emerald-700",

    pending_verification: "border-amber-200 bg-amber-50 text-amber-700",

    incomplete: "border-red-200 bg-red-50 text-red-700",
};

const kycStatusLabel = computed(() => {
    return (
        {
            complete: "KYC Complete",
            pending_verification: "Pending Verification",
            incomplete: "KYC Incomplete",
        }[props.stats.kyc_status] || "KYC Status"
    );
});

const completionStyle = computed(() => ({
    width: `${Math.min(Number(props.stats.completion_percentage || 0), 100)}%`,
}));

const requiredDocuments = computed(() =>
    props.kycDocuments.filter((item) => item.is_required),
);

const optionalKycDocuments = computed(() =>
    props.kycDocuments.filter((item) => !item.is_required),
);

const openKycUpload = (requirement) => {
    uploadingRequirement.value = requirement;

    uploadForm.reset();
    uploadForm.clearErrors();

    uploadForm.document_type = requirement.document_type;

    uploadForm.notes = "";
    uploadForm.file = null;

    selectedFileName.value = "";
    uploadOpen.value = true;
};

const openAdditionalUpload = () => {
    uploadingRequirement.value = null;

    uploadForm.reset();
    uploadForm.clearErrors();

    uploadForm.document_type = "other";
    uploadForm.notes = "";
    uploadForm.file = null;

    selectedFileName.value = "";
    uploadOpen.value = true;
};

const onFileChange = (event) => {
    const file = event.target.files?.[0] || null;

    uploadForm.file = file;

    selectedFileName.value = file?.name || "";

    uploadForm.clearErrors("file");
};

const submitUpload = () => {
    uploadForm.post(route("resident.documents.store"), {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            uploadOpen.value = false;
            uploadForm.reset();
            selectedFileName.value = "";
        },
    });
};

const openPreview = (document) => {
    previewDocument.value = document;

    if (document.is_pdf) {
        window.open(document.file_url, "_blank", "noopener,noreferrer");

        return;
    }

    previewOpen.value = true;
};

const deleteAdditionalDocument = (document) => {
    if (!confirm(`Delete "${document.document_label}"?`)) {
        return;
    }

    router.delete(
        route("resident.documents.destroy", {
            document: document.id,
        }),
        {
            preserveScroll: true,
        },
    );
};

const applyFilters = () => {
    router.get(
        route("resident.documents.index"),
        {
            search: filterForm.search || undefined,

            status: filterForm.status !== "all" ? filterForm.status : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    filterForm.search = "";
    filterForm.status = "all";

    router.get(
        route("resident.documents.index"),
        {},
        {
            preserveScroll: true,
            preserveState: true,
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

const documentIcon = (document) => {
    return document?.is_image ? FileImage : FileText;
};
</script>

<template>
    <Head title="My Documents" />

    <ResidentLayout title="My Documents">
        <div class="space-y-6">
            <!-- Hero -->
            <section
                class="overflow-hidden rounded-3xl border border-blue-200 bg-[linear-gradient(135deg,#172554_0%,#1d4ed8_52%,#3b82f6_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <FolderOpen class="h-7 w-7" />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                >
                                    Resident Records
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    My Documents
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Upload your required KYC documents and keep
                            additional personal records available in your
                            resident portal.
                        </p>
                    </div>

                    <div
                        class="min-w-64 rounded-2xl border border-white/20 bg-black/10 p-5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-white"
                                >
                                    KYC Progress
                                </p>

                                <p class="mt-1 text-2xl font-black text-white">
                                    {{ stats.completion_percentage || 0 }}%
                                </p>
                            </div>

                            <ShieldCheck class="h-9 w-9 text-white" />
                        </div>

                        <div
                            class="mt-4 h-2 overflow-hidden rounded-full bg-white/25"
                        >
                            <div
                                class="h-full rounded-full bg-white transition-all"
                                :style="completionStyle"
                            ></div>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-white">
                            {{ stats.verified_required || 0 }}
                            of
                            {{ stats.required_count || 0 }}
                            required documents verified
                        </p>
                    </div>
                </div>
            </section>

            <!-- Status -->
            <section
                class="flex flex-col gap-4 rounded-2xl border bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                :class="kycStatusClasses[stats.kyc_status]"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2
                        v-if="stats.kyc_status === 'complete'"
                        class="mt-0.5 h-5 w-5 shrink-0"
                    />

                    <Clock3
                        v-else-if="stats.kyc_status === 'pending_verification'"
                        class="mt-0.5 h-5 w-5 shrink-0"
                    />

                    <AlertTriangle v-else class="mt-0.5 h-5 w-5 shrink-0" />

                    <div>
                        <p class="text-sm font-bold">
                            {{ kycStatusLabel }}
                        </p>

                        <p class="mt-1 text-xs leading-5 opacity-90">
                            <template v-if="stats.kyc_status === 'complete'">
                                All required documents have been uploaded and
                                verified.
                            </template>

                            <template
                                v-else-if="
                                    stats.kyc_status === 'pending_verification'
                                "
                            >
                                All required documents are uploaded. Some are
                                awaiting administration verification.
                            </template>

                            <template v-else>
                                Upload all missing required documents to
                                complete your KYC.
                            </template>
                        </p>
                    </div>
                </div>

                <button
                    v-if="Number(stats.missing_required || 0) > 0"
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white"
                    @click="activeTab = 'kyc'"
                >
                    <Upload class="h-4 w-4" />
                    Upload Missing Documents
                </button>
            </section>

            <!-- Stats -->
            <section
                class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6"
            >
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-900">
                        {{ stats.required_count || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Required</p>
                </div>

                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"
                >
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ stats.verified_required || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-emerald-600">Verified</p>
                </div>

                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4"
                >
                    <p class="text-2xl font-bold text-amber-700">
                        {{ stats.pending_required || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-amber-600">Pending</p>
                </div>

                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-2xl font-bold text-red-700">
                        {{ stats.rejected_required || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-red-600">Rejected</p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <p class="text-2xl font-bold text-slate-900">
                        {{ stats.missing_required || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">Missing</p>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-2xl font-bold text-blue-700">
                        {{ stats.additional_count || 0 }}
                    </p>

                    <p class="mt-1 text-xs text-blue-600">Additional</p>
                </div>
            </section>

            <!-- Tabs -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex overflow-x-auto border-b border-slate-100">
                    <button
                        type="button"
                        class="inline-flex min-w-fit items-center gap-2 border-b-2 px-5 py-4 text-sm font-semibold"
                        :class="
                            activeTab === 'kyc'
                                ? 'border-indigo-600 text-indigo-700'
                                : 'border-transparent text-slate-500'
                        "
                        @click="activeTab = 'kyc'"
                    >
                        <ShieldCheck class="h-4 w-4" />
                        KYC Documents
                    </button>

                    <button
                        type="button"
                        class="inline-flex min-w-fit items-center gap-2 border-b-2 px-5 py-4 text-sm font-semibold"
                        :class="
                            activeTab === 'additional'
                                ? 'border-indigo-600 text-indigo-700'
                                : 'border-transparent text-slate-500'
                        "
                        @click="activeTab = 'additional'"
                    >
                        <FolderOpen class="h-4 w-4" />
                        Additional Documents
                    </button>
                </div>

                <!-- KYC -->
                <div v-if="activeTab === 'kyc'" class="space-y-6 p-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Required Documents
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Documents marked required must be verified before
                            KYC is completed.
                        </p>
                    </div>

                    <div
                        v-if="requiredDocuments.length"
                        class="grid grid-cols-1 gap-4 lg:grid-cols-2"
                    >
                        <article
                            v-for="item in requiredDocuments"
                            :key="item.document_type"
                            class="rounded-2xl border bg-white p-5 shadow-sm"
                            :class="
                                item.document?.verification_status ===
                                'verified'
                                    ? 'border-emerald-200'
                                    : item.document?.verification_status ===
                                        'rejected'
                                      ? 'border-red-200'
                                      : item.document
                                        ? 'border-amber-200'
                                        : 'border-slate-200'
                            "
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                    >
                                        <FileText class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h3
                                                class="text-sm font-bold text-slate-900"
                                            >
                                                {{ item.label }}
                                            </h3>

                                            <span
                                                class="rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-bold text-red-600"
                                            >
                                                Required
                                            </span>
                                        </div>

                                        <p
                                            v-if="item.document"
                                            class="mt-1 truncate text-xs text-slate-500"
                                        >
                                            {{ item.document.file_name }}
                                        </p>

                                        <p
                                            v-else
                                            class="mt-1 text-xs text-red-500"
                                        >
                                            Not uploaded
                                        </p>
                                    </div>
                                </div>

                                <span
                                    v-if="item.document"
                                    class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold capitalize"
                                    :class="
                                        statusClasses[
                                            item.document.verification_status
                                        ]
                                    "
                                >
                                    {{ item.document.verification_status }}
                                </span>
                            </div>

                            <div
                                v-if="item.document"
                                class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-500"
                            >
                                Uploaded
                                {{ formatDateTime(item.document.uploaded_at) }}
                            </div>

                            <div
                                v-if="
                                    item.document?.verification_status ===
                                    'rejected'
                                "
                                class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3"
                            >
                                <p class="text-xs font-bold text-red-900">
                                    Document Rejected
                                </p>

                                <p class="mt-1 text-xs leading-5 text-red-700">
                                    {{
                                        item.document.notes ||
                                        "Please upload a clear and valid replacement."
                                    }}
                                </p>
                            </div>

                            <div class="mt-5 flex flex-wrap justify-end gap-2">
                                <template v-if="item.document">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                        @click="openPreview(item.document)"
                                    >
                                        <Eye class="h-4 w-4" />
                                        View
                                    </button>

                                    <a
                                        :href="
                                            route(
                                                'resident.documents.download',
                                                {
                                                    document: item.document.id,
                                                },
                                            )
                                        "
                                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        <Download class="h-4 w-4" />
                                        Download
                                    </a>
                                </template>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                    @click="openKycUpload(item)"
                                >
                                    <RefreshCcw
                                        v-if="item.document"
                                        class="h-4 w-4"
                                    />

                                    <Upload v-else class="h-4 w-4" />

                                    {{ item.document ? "Replace" : "Upload" }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-slate-300 p-10 text-center"
                    >
                        <CircleCheck
                            class="mx-auto h-10 w-10 text-emerald-500"
                        />

                        <p class="mt-3 text-sm font-bold text-slate-700">
                            No required documents configured
                        </p>
                    </div>

                    <template v-if="optionalKycDocuments.length">
                        <div class="border-t border-slate-100 pt-6">
                            <h2 class="text-base font-bold text-slate-900">
                                Optional KYC Documents
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <article
                                v-for="item in optionalKycDocuments"
                                :key="item.document_type"
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <h3
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{ item.label }}
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{
                                                item.document
                                                    ? item.document.file_name
                                                    : "Optional document"
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="item.document"
                                        class="rounded-full border px-2.5 py-1 text-[10px] font-bold capitalize"
                                        :class="
                                            statusClasses[
                                                item.document
                                                    .verification_status
                                            ]
                                        "
                                    >
                                        {{ item.document.verification_status }}
                                    </span>
                                </div>

                                <div class="mt-4 flex justify-end gap-2">
                                    <button
                                        v-if="item.document"
                                        type="button"
                                        class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700"
                                        @click="openPreview(item.document)"
                                    >
                                        View
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white"
                                        @click="openKycUpload(item)"
                                    >
                                        {{
                                            item.document ? "Replace" : "Upload"
                                        }}
                                    </button>
                                </div>
                            </article>
                        </div>
                    </template>
                </div>

                <!-- Additional -->
                <div v-else class="space-y-5 p-5">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Additional Documents
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Upload other personal records such as college
                                ID, passport, fee receipt or driving licence.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                            @click="openAdditionalUpload"
                        >
                            <Plus class="h-4 w-4" />
                            Add Document
                        </button>
                    </div>

                    <div
                        class="flex flex-col gap-3 rounded-xl bg-slate-50 p-4 sm:flex-row"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />

                            <input
                                v-model="filterForm.search"
                                type="text"
                                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 text-sm"
                                placeholder="Search additional documents"
                            />
                        </div>

                        <select
                            v-model="filterForm.status"
                            class="rounded-xl border-slate-300 text-sm"
                            @change="applyFilters"
                        >
                            <option value="all">All Statuses</option>

                            <option value="pending">Pending</option>

                            <option value="verified">Verified</option>

                            <option value="rejected">Rejected</option>
                        </select>

                        <button
                            v-if="
                                filterForm.search || filterForm.status !== 'all'
                            "
                            type="button"
                            class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600"
                            @click="clearFilters"
                        >
                            Clear
                        </button>
                    </div>

                    <div
                        v-if="additionalDocuments.data?.length"
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <article
                            v-for="document in additionalDocuments.data"
                            :key="document.id"
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                                    >
                                        <component
                                            :is="documentIcon(document)"
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <div class="min-w-0">
                                        <h3
                                            class="truncate text-sm font-bold text-slate-900"
                                        >
                                            {{ document.document_label }}
                                        </h3>

                                        <p
                                            class="mt-1 truncate text-xs text-slate-500"
                                        >
                                            {{ document.file_name }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="rounded-full border px-2.5 py-1 text-[10px] font-bold capitalize"
                                    :class="
                                        statusClasses[
                                            document.verification_status
                                        ]
                                    "
                                >
                                    {{ document.verification_status }}
                                </span>
                            </div>

                            <p class="mt-4 text-xs text-slate-500">
                                Uploaded
                                {{ formatDateTime(document.uploaded_at) }}
                            </p>

                            <div
                                class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4"
                            >
                                <button
                                    type="button"
                                    class="rounded-lg border border-blue-200 p-2 text-blue-600 hover:bg-blue-50"
                                    title="View"
                                    @click="openPreview(document)"
                                >
                                    <Eye class="h-4 w-4" />
                                </button>

                                <a
                                    :href="
                                        route('resident.documents.download', {
                                            document: document.id,
                                        })
                                    "
                                    class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50"
                                    title="Download"
                                >
                                    <Download class="h-4 w-4" />
                                </a>

                                <button
                                    type="button"
                                    class="rounded-lg border border-red-200 p-2 text-red-600 hover:bg-red-50"
                                    title="Delete"
                                    @click="deleteAdditionalDocument(document)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-slate-300 px-6 py-14 text-center"
                    >
                        <FolderOpen class="mx-auto h-11 w-11 text-slate-300" />

                        <h3 class="mt-3 text-sm font-bold text-slate-700">
                            No additional documents
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Upload optional records you want to keep in your
                            portal.
                        </p>
                    </div>

                    <div
                        v-if="additionalDocuments.links?.length > 3"
                        class="flex flex-wrap justify-center gap-1"
                    >
                        <template
                            v-for="link in additionalDocuments.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="rounded-lg px-3 py-2 text-xs"
                                :class="
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-slate-50 text-slate-600'
                                "
                                preserve-scroll
                            />

                            <span
                                v-else
                                v-html="link.label"
                                class="rounded-lg px-3 py-2 text-xs text-slate-300"
                            />
                        </template>
                    </div>
                </div>
            </section>

            <section
                class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-5"
            >
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                <div>
                    <p class="text-sm font-bold text-blue-900">
                        Document verification
                    </p>

                    <p class="mt-1 text-xs leading-5 text-blue-700">
                        Replacing an existing document resets its verification
                        status to pending. KYC documents cannot be deleted
                        directly; upload a replacement instead.
                    </p>
                </div>
            </section>
        </div>

        <!-- Upload modal -->
        <Modal :show="uploadOpen" maxWidth="lg" @close="uploadOpen = false">
            <form class="p-6" @submit.prevent="submitUpload">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            {{
                                uploadForm.document_type === "other"
                                    ? "Upload Additional Document"
                                    : uploadingRequirement?.document
                                      ? `Replace ${uploadingRequirement.label}`
                                      : `Upload ${uploadingRequirement?.label || "Document"}`
                            }}
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            JPG, PNG, WEBP or PDF. Maximum file size 8 MB.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="uploadOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="uploadForm.document_type === 'other'" class="mt-5">
                    <InputLabel value="Document Title *" />

                    <input
                        v-model="uploadForm.notes"
                        type="text"
                        required
                        maxlength="255"
                        class="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        placeholder="Example: College ID Card"
                    />

                    <InputError
                        class="mt-1"
                        :message="uploadForm.errors.notes"
                    />
                </div>

                <div class="mt-5">
                    <InputLabel value="Select File *" />

                    <label
                        class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center hover:border-indigo-400 hover:bg-indigo-50"
                    >
                        <Upload class="h-8 w-8 text-indigo-500" />

                        <p class="mt-3 text-sm font-semibold text-slate-800">
                            {{ selectedFileName || "Choose document file" }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            JPG, JPEG, PNG, WEBP or PDF
                        </p>

                        <input
                            type="file"
                            required
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="sr-only"
                            @change="onFileChange"
                        />
                    </label>

                    <InputError
                        class="mt-1"
                        :message="uploadForm.errors.file"
                    />
                </div>

                <div
                    v-if="uploadingRequirement?.document"
                    class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4"
                >
                    <p class="text-xs leading-5 text-amber-700">
                        The existing file will be replaced and its verification
                        status will return to pending.
                    </p>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        @click="uploadOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="uploadForm.processing">
                        {{
                            uploadForm.processing
                                ? "Uploading..."
                                : "Upload Document"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Preview modal -->
        <Modal :show="previewOpen" maxWidth="4xl" @close="previewOpen = false">
            <div v-if="previewDocument" class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            {{ previewDocument.document_label }}
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ previewDocument.file_name }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        @click="previewOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                >
                    <img
                        v-if="previewDocument.is_image"
                        :src="previewDocument.file_url"
                        :alt="previewDocument.document_label"
                        class="max-h-[70vh] w-full object-contain"
                    />

                    <div
                        v-else
                        class="flex flex-col items-center justify-center p-14 text-center"
                    >
                        <FileText class="h-14 w-14 text-red-500" />

                        <p class="mt-4 text-sm font-bold text-slate-800">
                            Open this document to preview it.
                        </p>

                        <a
                            :href="previewDocument.file_url"
                            target="_blank"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <Eye class="h-4 w-4" />
                            Open Document
                        </a>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <a
                        :href="
                            route('resident.documents.download', {
                                document: previewDocument.id,
                            })
                        "
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Download class="h-4 w-4" />
                        Download
                    </a>
                </div>
            </div>
        </Modal>
    </ResidentLayout>
</template>
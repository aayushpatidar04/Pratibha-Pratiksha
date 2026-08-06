<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import {
    Check,
    Download,
    Eye,
    FileText,
    FolderOpen,
    Search,
    Settings,
    ShieldCheck,
    Trash2,
    Upload,
    X,
} from "lucide-vue-next";

const props = defineProps({
    residents: Object,
    requirements: Array,
    allRequirements: Array,
    counts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");

let searchTimer = null;

const onSearch = () => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        router.get(
            route("residents.kyc.index"),
            {
                search: search.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 400);
};

const statusMeta = {
    complete: {
        label: "Complete",
        color: "green",
    },

    pending_verification: {
        label: "Pending Verification",
        color: "amber",
    },

    incomplete: {
        label: "Incomplete",
        color: "red",
    },
};

const docStatusColor = {
    pending: "amber",
    verified: "green",
    rejected: "red",
};

const checklistOpen = ref(false);
const activeResident = ref(null);

const statusModalOpen = ref(false);
const statusDocument = ref(null);

const statusForm = useForm({
    verification_status: "",
    notes: "",
});

const additionalUploadForm = useForm({
    document_type: "other",
    notes: "",
    file: null,
});

const additionalFileName = ref("");

const activeRequirements = computed(() => {
    return props.allRequirements.filter(
        (requirement) => requirement.is_active,
    );
});

const additionalDocuments = computed(() => {
    return (
        activeResident.value?.documents?.filter(
            (document) =>
                document.document_type === "other",
        ) || []
    );
});

const openChecklist = (resident) => {
    activeResident.value = resident;
    checklistOpen.value = true;
};

const docFor = (documentType) => {
    if (!activeResident.value) {
        return null;
    }

    return activeResident.value.documents?.find(
        (document) =>
            document.document_type === documentType,
    );
};

const refreshActiveResident = () => {
    router.reload({
        only: ["residents", "counts"],

        onSuccess: (page) => {
            const updatedResident =
                page.props.residents.data.find(
                    (resident) =>
                        resident.id ===
                        activeResident.value?.id,
                );

            if (updatedResident) {
                activeResident.value =
                    updatedResident;
            }
        },
    });
};

const uploadDoc = (
    documentType,
    event,
) => {
    const file =
        event.target.files?.[0] || null;

    if (!file || !activeResident.value) {
        return;
    }

    const form = useForm({
        document_type: documentType,
        notes: "",
        file,
    });

    form.post(
        route("residents.documents.store", {
            resident: activeResident.value.id,
        }),
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                event.target.value = "";
                refreshActiveResident();
            },
        },
    );
};

const openStatusModal = (
    document,
    status,
) => {
    statusDocument.value = document;

    statusForm.reset();
    statusForm.clearErrors();

    statusForm.verification_status =
        status;

    /*
     * Existing KYC notes may hold the rejection
     * or verification remark.
     *
     * For "other", notes holds the custom title,
     * so it must not be overwritten.
     */
    statusForm.notes =
        document.document_type === "other"
            ? ""
            : document.notes || "";

    statusModalOpen.value = true;
};

const submitDocumentStatus = () => {
    if (!statusDocument.value) {
        return;
    }

    statusForm.put(
        route("documents.update", {
            document: statusDocument.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                statusModalOpen.value = false;
                statusDocument.value = null;
                statusForm.reset();

                refreshActiveResident();
            },
        },
    );
};

const onAdditionalFileChange = (
    event,
) => {
    const file =
        event.target.files?.[0] || null;

    additionalUploadForm.file = file;

    additionalFileName.value =
        file?.name || "";

    additionalUploadForm.clearErrors(
        "file",
    );
};

const uploadAdditionalDocument = () => {
    if (!activeResident.value) {
        return;
    }

    additionalUploadForm.post(
        route("residents.documents.store", {
            resident: activeResident.value.id,
        }),
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                additionalUploadForm.reset();
                additionalUploadForm.document_type =
                    "other";

                additionalFileName.value = "";

                refreshActiveResident();
            },
        },
    );
};

const destroyDocument = (document) => {
    if (
        !confirm(
            `Delete "${document.document_label || document.notes || document.file_name}"?`,
        )
    ) {
        return;
    }

    router.delete(
        route("documents.destroy", {
            document: document.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                refreshActiveResident();
            },
        },
    );
};

const formatDateTime = (value) => {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
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
    ).format(date);
};
</script>

<template>
    <Head title="KYC" />
    <AuthenticatedLayout>
        <template #header>Residents / KYC</template>

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <ShieldCheck class="h-6 w-6 text-blue-600" /> KYC
                        Verification
                    </h1>
                    <p class="text-sm text-gray-700 mt-0.5">
                        Track document submission and verification per resident
                    </p>
                </div>
                <Link
                    href="/residents/kyc/settings"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                >
                    <Settings class="h-3.5 w-3.5" /> Configure Required
                    Documents
                </Link>
            </div>

            <div
                v-if="!requirements.length"
                class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5"
            >
                No documents are currently marked required — everyone shows as
                "Complete" by default. Configure requirements to start tracking
                KYC properly.
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-lg">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ counts.complete }}
                    </p>
                    <p class="text-xs text-gray-600">Complete</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ counts.pending_verification }}
                    </p>
                    <p class="text-xs text-gray-600">Pending Verification</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ counts.incomplete }}
                    </p>
                    <p class="text-xs text-gray-600">Incomplete</p>
                </div>
            </div>

            <div class="relative w-80">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-600"
                />
                <input
                    v-model="search"
                    @input="onSearch"
                    placeholder="Search resident..."
                    class="w-full pl-9 rounded-lg border-gray-300 text-sm"
                />
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">KYC Status</th>
                            <th class="text-left px-4 py-3">Documents</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in residents.data" :key="r.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="r.photo_url"
                                        :src="`/storage/${r.photo_url}`"
                                        class="h-16 w-16 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-600"
                                    >
                                        {{ r.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ r.first_name }} {{ r.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            {{ r.resident_code }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :color="statusMeta[r.kyc_status].color"
                                    >{{ statusMeta[r.kyc_status].label }}</Badge
                                >
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700">
                                {{ r.documents?.length || 0 }} uploaded
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="text-xs font-medium text-blue-600 hover:underline inline-flex items-center gap-1"
                                    @click="openChecklist(r)"
                                >
                                    <FileText class="h-3.5 w-3.5" /> Manage
                                    Documents
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!residents.data.length">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-gray-600"
                            >
                                No residents found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="residents.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in residents.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-lg"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            "
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1 text-xs text-gray-300"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Document checklist modal -->
        <Modal
            :show="checklistOpen"
            maxWidth="3xl"
            @close="checklistOpen = false"
        >
            <div
                v-if="activeResident"
                class="flex max-h-[92vh] flex-col overflow-hidden"
            >
                <div
                    class="flex shrink-0 items-start justify-between border-b border-gray-100 px-6 py-5"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-gray-900"
                        >
                            Resident Documents
                        </h2>

                        <p
                            class="mt-1 text-sm text-gray-600"
                        >
                            {{ activeResident.first_name }}
                            {{ activeResident.last_name }}
                            ·
                            {{ activeResident.resident_code }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                        @click="checklistOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="min-h-0 flex-1 space-y-8 overflow-y-auto p-6"
                >
                    <!-- KYC checklist -->
                    <section>
                        <div
                            class="mb-4 flex items-center justify-between gap-4"
                        >
                            <div>
                                <h3
                                    class="flex items-center gap-2 text-base font-bold text-gray-900"
                                >
                                    <ShieldCheck
                                        class="h-5 w-5 text-indigo-600"
                                    />

                                    KYC Documents
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Documents configured in KYC settings.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="activeRequirements.length"
                            class="space-y-3"
                        >
                            <article
                                v-for="requirement in activeRequirements"
                                :key="requirement.document_type"
                                class="rounded-xl border border-gray-200 p-4"
                            >
                                <div
                                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                                >
                                    <div
                                        class="flex min-w-0 items-start gap-3"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                        >
                                            <FileText
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <p
                                                    class="text-sm font-semibold text-gray-900"
                                                >
                                                    {{ requirement.label }}
                                                </p>

                                                <span
                                                    v-if="requirement.is_required"
                                                    class="rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-bold text-red-600"
                                                >
                                                    Required
                                                </span>

                                                <span
                                                    v-else
                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[9px] font-semibold text-gray-500"
                                                >
                                                    Optional
                                                </span>
                                            </div>

                                            <template
                                                v-if="
                                                    docFor(
                                                        requirement.document_type,
                                                    )
                                                "
                                            >
                                                <p
                                                    class="mt-1 truncate text-xs text-gray-500"
                                                >
                                                    {{
                                                        docFor(
                                                            requirement.document_type,
                                                        ).file_name
                                                    }}
                                                </p>

                                                <div
                                                    class="mt-2 flex flex-wrap items-center gap-2"
                                                >
                                                    <Badge
                                                        :color="
                                                            docStatusColor[
                                                                docFor(
                                                                    requirement.document_type,
                                                                )
                                                                    .verification_status
                                                            ]
                                                        "
                                                    >
                                                        {{
                                                            docFor(
                                                                requirement.document_type,
                                                            )
                                                                .verification_status
                                                        }}
                                                    </Badge>

                                                    <span
                                                        class="text-[10px] text-gray-400"
                                                    >
                                                        Uploaded
                                                        {{
                                                            formatDateTime(
                                                                docFor(
                                                                    requirement.document_type,
                                                                ).uploaded_at,
                                                            )
                                                        }}
                                                    </span>
                                                </div>

                                                <div
                                                    v-if="
                                                        docFor(
                                                            requirement.document_type,
                                                        ).notes
                                                    "
                                                    class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2"
                                                >
                                                    <p
                                                        class="text-xs leading-5 text-amber-700"
                                                    >
                                                        {{
                                                            docFor(
                                                                requirement.document_type,
                                                            ).notes
                                                        }}
                                                    </p>
                                                </div>
                                            </template>

                                            <p
                                                v-else
                                                class="mt-1 text-xs text-red-500"
                                            >
                                                Not uploaded
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center justify-end gap-2"
                                    >
                                        <template
                                            v-if="
                                                docFor(
                                                    requirement.document_type,
                                                )
                                            "
                                        >
                                            <a
                                                :href="
                                                    docFor(
                                                        requirement.document_type,
                                                    ).file_url
                                                "
                                                target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                            >
                                                <Eye
                                                    class="h-3.5 w-3.5"
                                                />
                                                View
                                            </a>

                                            <a
                                                :href="
                                                    route(
                                                        'documents.download',
                                                        {
                                                            document:
                                                                docFor(
                                                                    requirement.document_type,
                                                                ).id,
                                                        },
                                                    )
                                                "
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            >
                                                <Download
                                                    class="h-3.5 w-3.5"
                                                />
                                                Download
                                            </a>

                                            <button
                                                v-if="
                                                    docFor(
                                                        requirement.document_type,
                                                    )
                                                        .verification_status !==
                                                    'verified'
                                                "
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100"
                                                title="Verify"
                                                @click="
                                                    openStatusModal(
                                                        docFor(
                                                            requirement.document_type,
                                                        ),
                                                        'verified',
                                                    )
                                                "
                                            >
                                                <Check
                                                    class="h-4 w-4"
                                                />
                                            </button>

                                            <button
                                                v-if="
                                                    docFor(
                                                        requirement.document_type,
                                                    )
                                                        .verification_status !==
                                                    'rejected'
                                                "
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100"
                                                title="Reject"
                                                @click="
                                                    openStatusModal(
                                                        docFor(
                                                            requirement.document_type,
                                                        ),
                                                        'rejected',
                                                    )
                                                "
                                            >
                                                <X
                                                    class="h-4 w-4"
                                                />
                                            </button>
                                        </template>

                                        <label
                                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Upload
                                                class="h-3.5 w-3.5"
                                            />

                                            {{
                                                docFor(
                                                    requirement.document_type,
                                                )
                                                    ? "Replace"
                                                    : "Upload"
                                            }}

                                            <input
                                                type="file"
                                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                class="hidden"
                                                @change="
                                                    uploadDoc(
                                                        requirement.document_type,
                                                        $event,
                                                    )
                                                "
                                            />
                                        </label>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center"
                        >
                            <FileText
                                class="mx-auto h-10 w-10 text-gray-300"
                            />

                            <p
                                class="mt-3 text-sm font-semibold text-gray-700"
                            >
                                No active KYC requirements
                            </p>
                        </div>
                    </section>

                    <!-- Additional documents -->
                    <section
                        class="border-t border-gray-100 pt-7"
                    >
                        <div
                            class="mb-4 flex items-center justify-between gap-4"
                        >
                            <div>
                                <h3
                                    class="flex items-center gap-2 text-base font-bold text-gray-900"
                                >
                                    <FolderOpen
                                        class="h-5 w-5 text-blue-600"
                                    />

                                    Additional Documents
                                </h3>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Other documents uploaded by the resident or administration.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                            >
                                {{ additionalDocuments.length }}
                                document{{
                                    additionalDocuments.length === 1
                                        ? ""
                                        : "s"
                                }}
                            </span>
                        </div>

                        <!-- Add additional document -->
                        <form
                            class="rounded-2xl border border-blue-200 bg-blue-50 p-4"
                            @submit.prevent="uploadAdditionalDocument"
                        >
                            <div
                                class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end"
                            >
                                <div>
                                    <InputLabel
                                        value="Document Title *"
                                    />

                                    <input
                                        v-model="
                                            additionalUploadForm.notes
                                        "
                                        type="text"
                                        required
                                        maxlength="255"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                                        placeholder="Example: College ID Card"
                                    />

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            additionalUploadForm.errors.notes
                                        "
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        value="Select File *"
                                    />

                                    <label
                                        class="mt-1 flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700"
                                    >
                                        <Upload
                                            class="h-4 w-4 text-blue-600"
                                        />

                                        <span class="truncate">
                                            {{
                                                additionalFileName ||
                                                "Choose document"
                                            }}
                                        </span>

                                        <input
                                            type="file"
                                            required
                                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                                            class="hidden"
                                            @change="
                                                onAdditionalFileChange
                                            "
                                        />
                                    </label>

                                    <InputError
                                        class="mt-1"
                                        :message="
                                            additionalUploadForm.errors.file
                                        "
                                    />
                                </div>

                                <PrimaryButton
                                    :disabled="
                                        additionalUploadForm.processing
                                    "
                                >
                                    <Plus
                                        class="mr-1 h-4 w-4"
                                    />

                                    {{
                                        additionalUploadForm.processing
                                            ? "Uploading..."
                                            : "Add Document"
                                    }}
                                </PrimaryButton>
                            </div>
                        </form>

                        <!-- Additional docs list -->
                        <div
                            v-if="additionalDocuments.length"
                            class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2"
                        >
                            <article
                                v-for="document in additionalDocuments"
                                :key="document.id"
                                class="rounded-xl border border-gray-200 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div
                                        class="flex min-w-0 items-start gap-3"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                                        >
                                            <FileText
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-semibold text-gray-900"
                                            >
                                                {{
                                                    document.notes ||
                                                    document.file_name
                                                }}
                                            </p>

                                            <p
                                                class="mt-1 truncate text-xs text-gray-500"
                                            >
                                                {{ document.file_name }}
                                            </p>

                                            <div
                                                class="mt-2 flex flex-wrap items-center gap-2"
                                            >
                                                <Badge
                                                    :color="
                                                        docStatusColor[
                                                            document.verification_status
                                                        ]
                                                    "
                                                >
                                                    {{
                                                        document.verification_status
                                                    }}
                                                </Badge>

                                                <span
                                                    class="text-[10px] text-gray-400"
                                                >
                                                    {{
                                                        formatDateTime(
                                                            document.uploaded_at,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 flex flex-wrap justify-end gap-2 border-t border-gray-100 pt-3"
                                >
                                    <a
                                        :href="document.file_url"
                                        target="_blank"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50"
                                        title="View"
                                    >
                                        <Eye
                                            class="h-4 w-4"
                                        />
                                    </a>

                                    <a
                                        :href="
                                            route(
                                                'documents.download',
                                                {
                                                    document:
                                                        document.id,
                                                },
                                            )
                                        "
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                                        title="Download"
                                    >
                                        <Download
                                            class="h-4 w-4"
                                        />
                                    </a>

                                    <button
                                        v-if="
                                            document.verification_status !==
                                            'verified'
                                        "
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100"
                                        title="Verify"
                                        @click="
                                            openStatusModal(
                                                document,
                                                'verified',
                                            )
                                        "
                                    >
                                        <Check
                                            class="h-4 w-4"
                                        />
                                    </button>

                                    <button
                                        v-if="
                                            document.verification_status !==
                                            'rejected'
                                        "
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100"
                                        title="Reject"
                                        @click="
                                            openStatusModal(
                                                document,
                                                'rejected',
                                            )
                                        "
                                    >
                                        <X
                                            class="h-4 w-4"
                                        />
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50"
                                        title="Delete"
                                        @click="
                                            destroyDocument(
                                                document,
                                            )
                                        "
                                    >
                                        <Trash2
                                            class="h-4 w-4"
                                        />
                                    </button>
                                </div>
                            </article>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center"
                        >
                            <FolderOpen
                                class="mx-auto h-10 w-10 text-gray-300"
                            />

                            <p
                                class="mt-3 text-sm font-semibold text-gray-700"
                            >
                                No additional documents
                            </p>
                        </div>
                    </section>
                </div>

                <div
                    class="flex shrink-0 justify-end border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700"
                        @click="checklistOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <Modal
            :show="statusModalOpen"
            maxWidth="md"
            @close="statusModalOpen = false"
        >
            <form
                class="p-6"
                @submit.prevent="submitDocumentStatus"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-gray-900"
                        >
                            {{
                                statusForm.verification_status ===
                                "verified"
                                    ? "Verify Document"
                                    : "Reject Document"
                            }}
                        </h2>

                        <p
                            class="mt-1 text-sm text-gray-600"
                        >
                            {{
                                statusDocument?.document_label ||
                                statusDocument?.notes ||
                                statusDocument?.file_name
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                        @click="statusModalOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="mt-5 rounded-xl border p-4"
                    :class="
                        statusForm.verification_status ===
                        'verified'
                            ? 'border-green-200 bg-green-50'
                            : 'border-red-200 bg-red-50'
                    "
                >
                    <p
                        class="text-sm font-semibold"
                        :class="
                            statusForm.verification_status ===
                            'verified'
                                ? 'text-green-900'
                                : 'text-red-900'
                        "
                    >
                        {{
                            statusForm.verification_status ===
                            "verified"
                                ? "Confirm that this document is valid and readable."
                                : "Provide a clear reason so the resident can upload a corrected document."
                        }}
                    </p>
                </div>

                <div
                    v-if="
                        statusDocument?.document_type !==
                        'other'
                    "
                    class="mt-5"
                >
                    <InputLabel
                        :value="
                            statusForm.verification_status ===
                            'rejected'
                                ? 'Rejection Reason *'
                                : 'Verification Notes'
                        "
                    />

                    <textarea
                        v-model="statusForm.notes"
                        rows="4"
                        :required="
                            statusForm.verification_status ===
                            'rejected'
                        "
                        maxlength="1000"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                        :placeholder="
                            statusForm.verification_status ===
                            'rejected'
                                ? 'Example: Image is blurred or document details are not readable.'
                                : 'Optional verification remark'
                        "
                    ></textarea>

                    <InputError
                        class="mt-1"
                        :message="statusForm.errors.notes"
                    />
                </div>

                <div
                    v-else
                    class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4"
                >
                    <p
                        class="text-xs leading-5 text-blue-700"
                    >
                        Additional document titles are stored in
                        the notes field, so the title will be
                        preserved while updating verification
                        status.
                    </p>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700"
                        @click="statusModalOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        :disabled="statusForm.processing"
                    >
                        {{
                            statusForm.processing
                                ? "Saving..."
                                : statusForm.verification_status ===
                                    "verified"
                                ? "Verify Document"
                                : "Reject Document"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
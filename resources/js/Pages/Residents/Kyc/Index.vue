<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import {
    ShieldCheck,
    Search,
    Settings,
    Upload,
    Check,
    X,
    FileText,
} from "lucide-vue-next";

const props = defineProps({
    residents: Object,
    requirements: Array,
    allRequirements: Array,
    counts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
let t = null;
const onSearch = () => {
    clearTimeout(t);
    t = setTimeout(
        () =>
            router.get(
                "/residents/kyc",
                { search: search.value },
                { preserveState: true },
            ),
        400,
    );
};

const statusMeta = {
    complete: { label: "Complete", color: "green" },
    pending_verification: { label: "Pending Verification", color: "amber" },
    incomplete: { label: "Incomplete", color: "red" },
};

const docStatusColor = { pending: "amber", verified: "green", rejected: "red" };

// --- Checklist modal ---
const checklistOpen = ref(false);
const activeResident = ref(null);
const uploadForms = reactive({});

const openChecklist = (resident) => {
    activeResident.value = resident;
    checklistOpen.value = true;
};

const docFor = (documentType) =>
    activeResident.value?.documents?.find(
        (d) => d.document_type === documentType,
    );

const uploadDoc = (documentType, event) => {
    const file = event.target.files[0];
    if (!file) return;
    const form = useForm({ document_type: documentType, file });
    form.post(`/residents/${activeResident.value.id}/documents`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Refresh just this resident's document list from the reloaded page props.
            router.reload({
                only: ["residents"],
                onSuccess: (page) => {
                    const updated = page.props.residents.data.find(
                        (r) => r.id === activeResident.value.id,
                    );
                    if (updated) activeResident.value = updated;
                },
            });
        },
    });
};

const setDocStatus = (document, status) => {
    router.put(
        `/documents/${document.id}`,
        { verification_status: status },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ["residents"],
                    onSuccess: (page) => {
                        const updated = page.props.residents.data.find(
                            (r) => r.id === activeResident.value.id,
                        );
                        if (updated) activeResident.value = updated;
                    },
                });
            },
        },
    );
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
                    <p class="text-sm text-gray-500 mt-0.5">
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
                    <p class="text-xs text-gray-400">Complete</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ counts.pending_verification }}
                    </p>
                    <p class="text-xs text-gray-400">Pending Verification</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ counts.incomplete }}
                    </p>
                    <p class="text-xs text-gray-400">Incomplete</p>
                </div>
            </div>

            <div class="relative w-80">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
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
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
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
                                        class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-400"
                                    >
                                        {{ r.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ r.first_name }} {{ r.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
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
                            <td class="px-4 py-3 text-xs text-gray-500">
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
                                class="px-4 py-10 text-center text-gray-400"
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
            @close="checklistOpen = false"
            maxWidth="lg"
        >
            <div class="p-6 space-y-4" v-if="activeResident">
                <h2 class="text-lg font-semibold text-gray-900">
                    Documents — {{ activeResident.first_name }}
                    {{ activeResident.last_name }}
                </h2>

                <div class="space-y-3">
                    <div
                        v-for="req in allRequirements"
                        :key="req.document_type"
                        class="flex items-center justify-between border border-gray-100 rounded-lg p-3"
                    >
                        <div>
                            <p
                                class="text-sm font-medium text-gray-800 flex items-center gap-1.5"
                            >
                                {{ req.label }}
                                <span
                                    v-if="req.is_required"
                                    class="text-[10px] text-red-500"
                                    >*required</span
                                >
                            </p>
                            <div
                                v-if="docFor(req.document_type)"
                                class="flex items-center gap-2 mt-1"
                            >
                                <Badge
                                    :color="
                                        docStatusColor[
                                            docFor(req.document_type)
                                                .verification_status
                                        ]
                                    "
                                    >{{
                                        docFor(req.document_type)
                                            .verification_status
                                    }}</Badge
                                >
                                <a
                                    :href="docFor(req.document_type).file_url"
                                    target="_blank"
                                    class="text-xs text-blue-600 hover:underline"
                                    >View file</a
                                >
                            </div>
                            <p v-else class="text-xs text-gray-400 mt-1">
                                Not uploaded
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <template
                                v-if="
                                    docFor(req.document_type) &&
                                    docFor(req.document_type)
                                        .verification_status === 'pending'
                                "
                            >
                                <button
                                    class="h-7 w-7 inline-flex items-center justify-center rounded-full bg-green-50 text-green-600"
                                    @click="
                                        setDocStatus(
                                            docFor(req.document_type),
                                            'verified',
                                        )
                                    "
                                >
                                    <Check class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    class="h-7 w-7 inline-flex items-center justify-center rounded-full bg-red-50 text-red-500"
                                    @click="
                                        setDocStatus(
                                            docFor(req.document_type),
                                            'rejected',
                                        )
                                    "
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </template>
                            <label
                                class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-600 cursor-pointer flex items-center gap-1"
                            >
                                <Upload class="h-3 w-3" />
                                {{
                                    docFor(req.document_type)
                                        ? "Replace"
                                        : "Upload"
                                }}
                                <input
                                    type="file"
                                    class="hidden"
                                    @change="
                                        uploadDoc(req.document_type, $event)
                                    "
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="checklistOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
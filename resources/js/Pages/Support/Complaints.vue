<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { History, MessageSquareWarning, Plus, Trash2 } from "lucide-vue-next";

const props = defineProps({
    complaints: Object,
    stats: Object,
    filters: Object,
    residents: Array,
});

const statusColor = {
    open: "amber",
    in_progress: "blue",
    resolved: "green",
    escalated: "red",
    rejected: "gray",
};
const priorityColor = {
    low: "gray",
    medium: "blue",
    high: "amber",
    urgent: "red",
};

const filters = reactive({
    status: props.filters?.status || "all",
    priority: props.filters?.priority || "all",
});
const applyFilters = () =>
    router.get(
        "/support/complaints",
        {
            status: filters.status !== "all" ? filters.status : undefined,
            priority: filters.priority !== "all" ? filters.priority : undefined,
        },
        { preserveState: true, replace: true },
    );

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    category: "electrical",
    priority: "medium",
    title: "",
    description: "",
});
const submitCreate = () =>
    createForm.post("/support/complaints", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const reviewOpen = ref(false);
const reviewingComplaint = ref(null);

const reviewForm = useForm({
    status: "",
    resolution_notes: "",
});

const openStatusUpdate = (complaint, status = null) => {
    reviewingComplaint.value = complaint;

    reviewForm.clearErrors();

    reviewForm.status = status || complaint.status;

    reviewForm.resolution_notes = complaint.resolution_notes || "";

    reviewOpen.value = true;
};

const submitStatusUpdate = () => {
    if (!reviewingComplaint.value) {
        return;
    }

    reviewForm.put(`/support/complaints/${reviewingComplaint.value.id}`, {
        preserveScroll: true,

        onSuccess: () => {
            reviewOpen.value = false;
            reviewingComplaint.value = null;

            reviewForm.reset();
            reviewForm.clearErrors();
        },
    });
};

const destroy = (c) => {
    if (confirm("Delete this complaint?"))
        router.delete(`/support/complaints/${c.id}`);
};

const historyOpen = ref(false);
const historyComplaint = ref(null);

const openHistory = (complaint) => {
    historyComplaint.value = complaint;
    historyOpen.value = true;
};
</script>

<template>
    <Head title="Complaints" />
    <AuthenticatedLayout>
        <template #header>Student Support / Complaints</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <MessageSquareWarning class="h-6 w-6 text-blue-600" />
                        Complaints
                    </h1>
                    <p class="text-sm text-gray-700 mt-0.5">
                        Track and resolve resident complaints
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Log Complaint</PrimaryButton
                >
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.open }}
                    </p>
                    <p class="text-xs text-gray-600">Open</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-blue-600">
                        {{ stats.inProgress }}
                    </p>
                    <p class="text-xs text-gray-600">In Progress</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.resolved }}
                    </p>
                    <p class="text-xs text-gray-600">Resolved</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.urgent }}
                    </p>
                    <p class="text-xs text-gray-600">Urgent (open)</p>
                </div>
            </div>

            <div class="flex gap-3">
                <select
                    v-model="filters.status"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="all">All Status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select
                    v-model="filters.priority"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="all">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Complaint</th>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Priority</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in complaints.data" :key="c.id">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">
                                    {{ c.title }}
                                </p>
                                <p class="text-xs text-gray-600 capitalize">
                                    {{ c.category }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ c.resident?.first_name }}
                                {{ c.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="priorityColor[c.priority]">{{
                                    c.priority
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                    @click="openStatusUpdate(c)"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="{
                                            'bg-amber-500': c.status === 'open',
                                            'bg-blue-500':
                                                c.status === 'in_progress',
                                            'bg-green-500':
                                                c.status === 'resolved',
                                            'bg-red-500':
                                                c.status === 'escalated',
                                            'bg-gray-500':
                                                c.status === 'rejected',
                                        }"
                                    ></span>

                                    {{
                                        c.status
                                            .replaceAll("_", " ")
                                            .replace(/\b\w/g, (letter) =>
                                                letter.toUpperCase(),
                                            )
                                    }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-blue-50"
                                    title="View update history"
                                    @click="openHistory(c)"
                                >
                                    <History class="h-4 w-4 text-blue-600" />
                                </button>

                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                    @click="destroy(c)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!complaints.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-600"
                            >
                                No complaints found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="complaints.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template
                        v-for="link in complaints.links"
                        :key="link.label"
                    >
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

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Log Complaint
                </h2>
                <div>
                    <InputLabel value="Resident *" />
                    <select
                        v-model="createForm.resident_id"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="" disabled>Select resident</option>
                        <option
                            v-for="r in residents"
                            :key="r.id"
                            :value="r.id"
                        >
                            {{ r.first_name }} {{ r.last_name }}
                        </option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Category *" />
                        <select
                            v-model="createForm.category"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="electrical">Electrical</option>
                            <option value="plumbing">Plumbing</option>
                            <option value="furniture">Furniture</option>
                            <option value="wifi">WiFi</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="security">Security</option>
                            <option value="food">Food</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Priority *" />
                        <select
                            v-model="createForm.priority"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel value="Title *" /><TextInput
                        v-model="createForm.title"
                        required
                    />
                </div>
                <div>
                    <InputLabel value="Description *" /><textarea
                        v-model="createForm.description"
                        rows="3"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="createForm.processing"
                        >Submit</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <Modal :show="reviewOpen" maxWidth="lg" @close="reviewOpen = false">
            <form
                v-if="reviewingComplaint"
                class="p-6"
                @submit.prevent="submitStatusUpdate"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Update Complaint Status
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ reviewingComplaint.title }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Resident:
                        {{ reviewingComplaint.resident?.first_name }}
                        {{ reviewingComplaint.resident?.last_name }}
                    </p>
                </div>

                <div
                    class="mb-5 rounded-xl border border-gray-100 bg-gray-50 p-4"
                >
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-500">Category</p>

                            <p
                                class="mt-1 font-medium capitalize text-gray-800"
                            >
                                {{
                                    reviewingComplaint.category?.replaceAll(
                                        "_",
                                        " ",
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Priority</p>

                            <p
                                class="mt-1 font-medium capitalize text-gray-800"
                            >
                                {{ reviewingComplaint.priority }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <p class="text-xs text-gray-500">Complaint</p>

                            <p
                                class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700"
                            >
                                {{ reviewingComplaint.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <InputLabel value="New Status *" />

                        <select
                            v-model="reviewForm.status"
                            required
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="open">Open</option>

                            <option value="in_progress">In Progress</option>

                            <option value="resolved">Resolved</option>

                            <option value="escalated">Escalated</option>

                            <option value="rejected">Rejected</option>
                        </select>

                        <InputError
                            class="mt-1"
                            :message="reviewForm.errors.status"
                        />
                    </div>

                    <div>
                        <InputLabel
                            :value="
                                reviewForm.status === 'resolved'
                                    ? 'Resolution Notes *'
                                    : reviewForm.status === 'rejected'
                                      ? 'Rejection Reason *'
                                      : reviewForm.status === 'escalated'
                                        ? 'Escalation Reason *'
                                        : 'Remarks'
                            "
                        />

                        <textarea
                            v-model="reviewForm.resolution_notes"
                            rows="5"
                            maxlength="5000"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                            :placeholder="
                                reviewForm.status === 'resolved'
                                    ? 'Explain the work completed and how the problem was resolved...'
                                    : reviewForm.status === 'rejected'
                                      ? 'Explain why this complaint is being rejected...'
                                      : reviewForm.status === 'escalated'
                                        ? 'Explain why this complaint requires escalation...'
                                        : reviewForm.status === 'in_progress'
                                          ? 'Mention the work started, assigned person, or expected next action...'
                                          : 'Add any status update remarks...'
                            "
                        ></textarea>

                        <div
                            class="mt-1 flex items-start justify-between gap-4"
                        >
                            <InputError
                                :message="reviewForm.errors.resolution_notes"
                            />

                            <span class="text-[10px] text-gray-400">
                                {{
                                    reviewForm.resolution_notes?.length || 0
                                }}/5000
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="reviewForm.status === 'resolved'"
                        class="rounded-xl border border-green-200 bg-green-50 p-4 text-xs leading-5 text-green-700"
                    >
                        The resident will see these notes as the final
                        resolution and will be able to rate the complaint.
                    </div>

                    <div
                        v-else-if="reviewForm.status === 'rejected'"
                        class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs leading-5 text-red-700"
                    >
                        The rejection reason will be visible to the resident.
                    </div>

                    <div
                        v-else-if="reviewForm.status === 'escalated'"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-700"
                    >
                        Add enough information so the next team understands why
                        escalation is required.
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        @click="reviewOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="reviewForm.processing">
                        {{
                            reviewForm.processing
                                ? "Updating..."
                                : "Update Complaint"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="historyOpen" maxWidth="lg" @close="historyOpen = false">
            <div v-if="historyComplaint" class="p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Complaint History
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ historyComplaint.title }}
                    </p>
                </div>

                <div v-if="historyComplaint.updates?.length" class="space-y-4">
                    <div
                        v-for="update in historyComplaint.updates"
                        :key="update.id"
                        class="rounded-xl border border-gray-100 bg-gray-50 p-4"
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-sm font-semibold text-gray-900">
                                <template v-if="!update.old_status">
                                    Complaint Submitted
                                </template>

                                <template v-else>
                                    {{ update.old_status.replaceAll("_", " ") }}
                                    →
                                    {{ update.new_status.replaceAll("_", " ") }}
                                </template>
                            </p>

                            <p class="text-xs text-gray-500">
                                {{
                                    new Date(update.created_at).toLocaleString(
                                        "en-IN",
                                    )
                                }}
                            </p>
                        </div>

                        <p
                            v-if="update.remarks"
                            class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700"
                        >
                            {{ update.remarks }}
                        </p>

                        <p class="mt-2 text-xs text-gray-500">
                            Updated by:
                            {{ update.updated_by?.name || "Resident" }}
                        </p>
                    </div>
                </div>

                <p v-else class="py-10 text-center text-sm text-gray-500">
                    No history entries available.
                </p>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm"
                        @click="historyOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

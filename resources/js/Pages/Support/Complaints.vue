<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { MessageSquareWarning, Plus, Trash2 } from "lucide-vue-next";

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

const updateStatus = (c, status) =>
    router.put(`/support/complaints/${c.id}`, { status });
const destroy = (c) => {
    if (confirm("Delete this complaint?"))
        router.delete(`/support/complaints/${c.id}`);
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
                    <p class="text-sm text-gray-500 mt-0.5">
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
                    <p class="text-xs text-gray-400">Open</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-blue-600">
                        {{ stats.inProgress }}
                    </p>
                    <p class="text-xs text-gray-400">In Progress</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.resolved }}
                    </p>
                    <p class="text-xs text-gray-400">Resolved</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.urgent }}
                    </p>
                    <p class="text-xs text-gray-400">Urgent (open)</p>
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
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
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
                                <p class="text-xs text-gray-400 capitalize">
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
                                <select
                                    :value="c.status"
                                    @change="
                                        updateStatus(c, $event.target.value)
                                    "
                                    class="text-xs rounded-lg border-gray-300"
                                >
                                    <option value="open">Open</option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="resolved">Resolved</option>
                                    <option value="escalated">Escalated</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right">
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
                                class="px-4 py-10 text-center text-gray-400"
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
    </AuthenticatedLayout>
</template>

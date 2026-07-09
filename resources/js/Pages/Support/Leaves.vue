<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { CalendarDays, Plus, Check, X, Trash2 } from "lucide-vue-next";

const props = defineProps({
    leaves: Object,
    stats: Object,
    filters: Object,
    residents: Array,
});

const statusColor = {
    pending: "amber",
    parent_approval_pending: "amber",
    approved: "green",
    rejected: "red",
    cancelled: "gray",
    expired: "gray",
};

const filters = reactive({
    final_status: props.filters?.final_status || "all",
});
const applyFilters = () =>
    router.get(
        "/support/leaves",
        {
            final_status:
                filters.final_status !== "all"
                    ? filters.final_status
                    : undefined,
        },
        { preserveState: true, replace: true },
    );

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    leave_type: "home_leave",
    from_date: "",
    to_date: "",
    reason: "",
    destination: "",
});
const submitCreate = () =>
    createForm.post("/support/leaves", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const setStatus = (l, final_status) =>
    router.put(`/support/leaves/${l.id}`, { final_status });
const destroy = (l) => {
    if (confirm("Delete this leave request?"))
        router.delete(`/support/leaves/${l.id}`);
};
</script>

<template>
    <Head title="Leaves" />
    <AuthenticatedLayout>
        <template #header>Student Support / Leaves</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <CalendarDays class="h-6 w-6 text-blue-600" /> Leave
                        Requests
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Approve or reject resident leave / gate pass requests
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> New Leave Request</PrimaryButton
                >
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.pending }}
                    </p>
                    <p class="text-xs text-gray-400">Pending</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.approved }}
                    </p>
                    <p class="text-xs text-gray-400">Approved</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.rejected }}
                    </p>
                    <p class="text-xs text-gray-400">Rejected</p>
                </div>
            </div>

            <select
                v-model="filters.final_status"
                @change="applyFilters"
                class="rounded-lg border-gray-300 text-sm w-52"
            >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
            </select>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Dates</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="l in leaves.data" :key="l.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ l.resident?.first_name }}
                                {{ l.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 capitalize">
                                {{ l.leave_type.replace("_", " ") }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ l.from_date }} → {{ l.to_date }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[l.final_status]">{{
                                    l.final_status.replace("_", " ")
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                <template v-if="l.final_status === 'pending'">
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-green-50"
                                        @click="setStatus(l, 'approved')"
                                    >
                                        <Check
                                            class="h-3.5 w-3.5 text-green-600"
                                        />
                                    </button>
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                        @click="setStatus(l, 'rejected')"
                                    >
                                        <X class="h-3.5 w-3.5 text-red-500" />
                                    </button>
                                </template>
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                    @click="destroy(l)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!leaves.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No leave requests found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="leaves.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in leaves.links" :key="link.label">
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
                    New Leave Request
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
                <div>
                    <InputLabel value="Leave Type *" />
                    <select
                        v-model="createForm.leave_type"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="home_leave">Home Leave</option>
                        <option value="medical_leave">Medical Leave</option>
                        <option value="emergency_leave">Emergency Leave</option>
                        <option value="day_out">Day Out</option>
                        <option value="night_pass">Night Pass</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="From *" /><TextInput
                            type="date"
                            v-model="createForm.from_date"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="To *" /><TextInput
                            type="date"
                            v-model="createForm.to_date"
                            required
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Destination" /><TextInput
                        v-model="createForm.destination"
                    />
                </div>
                <div>
                    <InputLabel value="Reason *" /><textarea
                        v-model="createForm.reason"
                        rows="2"
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
                        >Submit Request</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { Gavel, Plus } from "lucide-vue-next";

defineProps({ actions: Object, stats: Object, residents: Array });

const levelColor = {
    verbal: "gray",
    written: "amber",
    final: "red",
    suspension: "red",
    expulsion: "red",
};
const statusColor = { open: "amber", resolved: "green", closed: "gray" };

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    incident_date: "",
    description: "",
    warning_level: "verbal",
    action_taken: "",
});
const submitCreate = () =>
    createForm.post("/disciplinary", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const resolve = (a) =>
    router.put(`/disciplinary/${a.id}`, { status: "resolved" });
</script>

<template>
    <Head title="Disciplinary Action" />
    <AuthenticatedLayout>
        <template #header>Disciplinary Action</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <Gavel class="h-6 w-6 text-blue-600" /> Disciplinary
                        Records
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Log incidents and warnings issued to residents
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Log Incident</PrimaryButton
                >
            </div>

            <div class="grid grid-cols-2 gap-3 w-64">
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
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.resolved }}
                    </p>
                    <p class="text-xs text-gray-400">Resolved</p>
                </div>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Incident</th>
                            <th class="text-left px-4 py-3">Level</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="a in actions.data" :key="a.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ a.resident?.first_name }}
                                {{ a.resident?.last_name }}
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 max-w-xs truncate"
                            >
                                {{ a.description }}
                                <span class="text-xs text-gray-400"
                                    >({{ a.incident_date }})</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="levelColor[a.warning_level]">{{
                                    a.warning_level
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[a.status]">{{
                                    a.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="a.status === 'open'"
                                    class="text-xs font-medium text-green-600 hover:underline"
                                    @click="resolve(a)"
                                >
                                    Resolve
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!actions.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No disciplinary records
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="actions.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in actions.links" :key="link.label">
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
                    Log Disciplinary Incident
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
                        <InputLabel value="Incident Date *" /><TextInput
                            type="date"
                            v-model="createForm.incident_date"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="Warning Level *" />
                        <select
                            v-model="createForm.warning_level"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="verbal">Verbal</option>
                            <option value="written">Written</option>
                            <option value="final">Final</option>
                            <option value="suspension">Suspension</option>
                            <option value="expulsion">Expulsion</option>
                        </select>
                    </div>
                </div>
                <div>
                    <InputLabel value="Description *" /><textarea
                        v-model="createForm.description"
                        rows="3"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
                </div>
                <div>
                    <InputLabel value="Action Taken" /><textarea
                        v-model="createForm.action_taken"
                        rows="2"
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
                        >Save Record</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { AlertTriangle, Plus, CheckCircle2 } from "lucide-vue-next";

const props = defineProps({ alerts: Object, stats: Object, residents: Array });

const statusColor = { active: "red", resolved: "green", escalated: "amber" };

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    category: "medical",
    description: "",
    location: "",
});
const submitCreate = () =>
    createForm.post("/support/emergency", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const resolve = (a) =>
    router.put(`/support/emergency/${a.id}`, { status: "resolved" });
</script>

<template>
    <Head title="Emergency Alerts" />
    <AuthenticatedLayout>
        <template #header>Student Support / Emergency Alerts</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-6 w-6 text-red-600" /> Emergency
                        Alerts
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Respond quickly to active emergencies raised by
                        residents
                    </p>
                </div>
                <DangerButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Raise Alert</DangerButton
                >
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div
                    class="bg-white rounded-xl border border-red-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.active }}
                    </p>
                    <p class="text-xs text-gray-400">Active</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.escalated }}
                    </p>
                    <p class="text-xs text-gray-400">Escalated</p>
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
                            <th class="text-left px-4 py-3">Category</th>
                            <th class="text-left px-4 py-3">Location</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="a in alerts.data"
                            :key="a.id"
                            :class="a.status === 'active' ? 'bg-red-50/40' : ''"
                        >
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ a.resident?.first_name }}
                                {{ a.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 capitalize">
                                {{ a.category.replace("_", " ") }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ a.location || "—" }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[a.status]">{{
                                    a.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="a.status === 'active'"
                                    class="text-xs font-medium text-green-600 hover:underline inline-flex items-center gap-1"
                                    @click="resolve(a)"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Mark
                                    Resolved
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!alerts.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No emergency alerts
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="alerts.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in alerts.links" :key="link.label">
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
                    Raise Emergency Alert
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
                    <InputLabel value="Category *" />
                    <select
                        v-model="createForm.category"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="medical">Medical</option>
                        <option value="fire">Fire</option>
                        <option value="theft">Theft</option>
                        <option value="stuck_in_lift">Stuck in Lift</option>
                        <option value="need_food">Need Food</option>
                        <option value="disaster">Disaster</option>
                        <option value="domestic_violence">
                            Domestic Violence
                        </option>
                        <option value="threat">Threat</option>
                        <option value="violence">Violence</option>
                        <option value="suicidal">Suicidal</option>
                        <option value="mental_depression">
                            Mental Depression
                        </option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Location" /><TextInput
                        v-model="createForm.location"
                    />
                </div>
                <div>
                    <InputLabel value="Description" /><textarea
                        v-model="createForm.description"
                        rows="3"
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
                    <DangerButton
                        type="submit"
                        :disabled="createForm.processing"
                        >Raise Alert</DangerButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

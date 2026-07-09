<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { Bike, Plus, Trash2, Pencil } from "lucide-vue-next";

const props = defineProps({
    vehicles: Object,
    filters: Object,
    residents: Array,
});

const typeColor = {
    two_wheeler: "blue",
    four_wheeler: "purple",
    bicycle: "green",
    other: "gray",
};

const filters = reactive({
    search: props.filters?.search || "",
    vehicle_type: props.filters?.vehicle_type || "",
});
let t = null;
const applyFilters = (debounce = false) => {
    clearTimeout(t);
    const fn = () =>
        router.get(
            "/residents/vehicles",
            { ...filters },
            { preserveState: true },
        );
    debounce ? (t = setTimeout(fn, 400)) : fn();
};

const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    vehicle_type: "two_wheeler",
    vehicle_number: "",
    color: "",
    model: "",
});
const submitCreate = () =>
    createForm.post("/residents/vehicles", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const editOpen = ref(false);
const editForm = useForm({
    vehicle_type: "",
    vehicle_number: "",
    color: "",
    model: "",
});
const editingId = ref(null);
const openEdit = (v) => {
    editingId.value = v.id;
    editForm.vehicle_type = v.vehicle_type;
    editForm.vehicle_number = v.vehicle_number;
    editForm.color = v.color || "";
    editForm.model = v.model || "";
    editOpen.value = true;
};
const submitEdit = () =>
    editForm.put(`/residents/vehicles/${editingId.value}`, {
        onSuccess: () => (editOpen.value = false),
    });

const destroy = (v) => {
    if (confirm("Remove this vehicle record?"))
        router.delete(`/residents/vehicles/${v.id}`);
};
</script>

<template>
    <Head title="Student Vehicles" />
    <AuthenticatedLayout>
        <template #header>Residents / Student Vehicles</template>

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <Bike class="h-6 w-6 text-blue-600" /> Student Vehicles
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Registered vehicles per resident
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Add Vehicle</PrimaryButton
                >
            </div>

            <div class="flex flex-wrap gap-3">
                <input
                    v-model="filters.search"
                    @input="applyFilters(true)"
                    placeholder="Search by number or resident..."
                    class="rounded-lg border-gray-300 text-sm flex-1 min-w-[220px]"
                />
                <select
                    v-model="filters.vehicle_type"
                    @change="applyFilters()"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Types</option>
                    <option value="two_wheeler">Two Wheeler</option>
                    <option value="four_wheeler">Four Wheeler</option>
                    <option value="bicycle">Bicycle</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Vehicle No.</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Model / Color</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="v in vehicles.data" :key="v.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ v.resident?.first_name }}
                                {{ v.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ v.vehicle_number }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="typeColor[v.vehicle_type]">{{
                                    v.vehicle_type.replace("_", " ")
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ v.model || "—" }}
                                <span v-if="v.color">· {{ v.color }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="openEdit(v)"
                                >
                                    <Pencil class="h-3.5 w-3.5 text-gray-600" />
                                </button>
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                    @click="destroy(v)"
                                >
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!vehicles.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No vehicles registered yet
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="vehicles.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in vehicles.links" :key="link.label">
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
                <h2 class="text-lg font-semibold text-gray-900">Add Vehicle</h2>
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
                            {{ r.first_name }} {{ r.last_name }} ({{
                                r.resident_code
                            }})
                        </option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type *" />
                        <select
                            v-model="createForm.vehicle_type"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="two_wheeler">Two Wheeler</option>
                            <option value="four_wheeler">Four Wheeler</option>
                            <option value="bicycle">Bicycle</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Vehicle Number *" /><TextInput
                            v-model="createForm.vehicle_number"
                            required
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Model" /><TextInput
                            v-model="createForm.model"
                        />
                    </div>
                    <div>
                        <InputLabel value="Color" /><TextInput
                            v-model="createForm.color"
                        />
                    </div>
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
                        >Add Vehicle</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <Modal :show="editOpen" @close="editOpen = false">
            <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Edit Vehicle
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type" />
                        <select
                            v-model="editForm.vehicle_type"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="two_wheeler">Two Wheeler</option>
                            <option value="four_wheeler">Four Wheeler</option>
                            <option value="bicycle">Bicycle</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Vehicle Number" /><TextInput
                            v-model="editForm.vehicle_number"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Model" /><TextInput
                            v-model="editForm.model"
                        />
                    </div>
                    <div>
                        <InputLabel value="Color" /><TextInput
                            v-model="editForm.color"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="editOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="editForm.processing"
                        >Save Changes</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
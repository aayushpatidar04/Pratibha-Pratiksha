<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { Layers, Plus, Pencil, Trash2, Building2 } from 'lucide-vue-next';

const props = defineProps({ floors: Array, buildings: Array, filters: Object });

const buildingFilter = ref(props.filters?.building_id || '');
const applyFilter = () => {
    router.get('/infrastructure/floors', { building_id: buildingFilter.value || undefined }, { preserveState: true, replace: true });
};

const createOpen = ref(false);
const editOpen = ref(false);
const editing = ref(null);

const createForm = useForm({ building_id: '', floor_number: 0, name: '' });
const editForm = useForm({ floor_number: 0, name: '' });

const submitCreate = () => createForm.post('/infrastructure/floors', { onSuccess: () => { createOpen.value = false; createForm.reset(); } });

const openEdit = (f) => {
    editing.value = f;
    editForm.floor_number = f.floor_number;
    editForm.name = f.name;
    editOpen.value = true;
};
const submitEdit = () => editForm.put(`/infrastructure/floors/${editing.value.id}`, { onSuccess: () => (editOpen.value = false) });

const destroy = (f) => {
    if (confirm('Delete this floor?')) router.delete(`/infrastructure/floors/${f.id}`);
};

const buildingName = (id) => props.buildings.find((b) => b.id === id)?.name || '—';
</script>

<template>

    <Head title="Floors" />
    <AuthenticatedLayout>
        <template #header>Infrastructure / Floors</template>

        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Layers class="h-6 w-6 text-blue-600" /> Floors
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage floors within each building</p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true">
                    <Plus class="h-4 w-4" /> Add Floor
                </PrimaryButton>
            </div>

            <select v-model="buildingFilter" @change="applyFilter" class="rounded-lg border-gray-300 text-sm w-56">
                <option value="">All Buildings</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Floor</th>
                            <th class="text-left px-4 py-3">Building</th>
                            <th class="text-left px-4 py-3">Rooms</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="f in floors" :key="f.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ f.name }} <span
                                    class="text-gray-400">(#{{
                                    f.floor_number }})</span></td>
                            <td class="px-4 py-3 text-gray-600 flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 text-gray-400" />{{ buildingName(f.building_id) }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ f.total_rooms }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="openEdit(f)">
                                    <Pencil class="h-3.5 w-3.5 text-gray-600" />
                                </button>
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                    @click="destroy(f)">
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!floors.length">
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">No floors found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Add New Floor</h2>
                <div>
                    <InputLabel value="Building *" />
                    <select v-model="createForm.building_id" required class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="" disabled>Select building</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <InputError :message="createForm.errors.building_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Floor Number *" />
                        <TextInput type="number" v-model="createForm.floor_number" required />
                    </div>
                    <div>
                        <InputLabel value="Floor Name *" />
                        <TextInput v-model="createForm.name" required placeholder="e.g. Ground Floor" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false">Cancel</button>
                    <PrimaryButton :disabled="createForm.processing">Create Floor</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="editOpen" @close="editOpen = false">
            <form @submit.prevent="submitEdit" class="p-6 space-y-4" v-if="editing">
                <h2 class="text-lg font-semibold text-gray-900">Edit Floor</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Floor Number" />
                        <TextInput type="number" v-model="editForm.floor_number" />
                    </div>
                    <div>
                        <InputLabel value="Floor Name" />
                        <TextInput v-model="editForm.name" />
                    </div>
                </div>
                <div s="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="editOpen = false">Cancel</button>
                    <PrimaryButton :disabled="editForm.processing">Save Changes</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
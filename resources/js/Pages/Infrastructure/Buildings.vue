<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { Building2, Plus, Search, Pencil, Trash2, MapPin, BedDouble, Layers } from 'lucide-vue-next';

const props = defineProps({ buildings: Array, filters: Object });

const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || 'all',
    status: props.filters?.status || 'all',
});

const applyFilters = () => {
    router.get('/infrastructure/buildings', {
        search: filters.search || undefined,
        type: filters.type !== 'all' ? filters.type : undefined,
        status: filters.status !== 'all' ? filters.status : undefined,
    }, { preserveState: true, replace: true });
};

const typeBadge = { boys: 'blue', girls: 'pink', mixed: 'purple' };
const statusBadge = { active: 'green', inactive: 'gray', maintenance: 'amber' };

const createOpen = ref(false);
const editOpen = ref(false);
const editing = ref(null);

const createForm = useForm({ name: '', code: '', type: 'boys', address: '', total_floors: 0, status: 'active' });
const editForm = useForm({ name: '', code: '', type: 'boys', address: '', total_floors: 0, status: 'active' });

const submitCreate = () => {
    createForm.post('/infrastructure/buildings', {
        onSuccess: () => { createOpen.value = false; createForm.reset(); },
    });
};

const openEdit = (b) => {
    editing.value = b;
    editForm.name = b.name; editForm.code = b.code; editForm.type = b.type;
    editForm.address = b.address || ''; editForm.total_floors = b.total_floors; editForm.status = b.status;
    editOpen.value = true;
};

const submitEdit = () => {
    editForm.put(`/infrastructure/buildings/${editing.value.id}`, {
        onSuccess: () => { editOpen.value = false; },
    });
};

const destroy = (b) => {
    if (confirm('Are you sure you want to delete this building?')) {
        router.delete(`/infrastructure/buildings/${b.id}`);
    }
};
</script>

<template>

    <Head title="Buildings" />
    <AuthenticatedLayout>
        <template #header>Infrastructure / Buildings</template>

        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Building2 class="h-6 w-6 text-blue-600" /> Buildings
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage hostel buildings and their details</p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true">
                    <Plus class="h-4 w-4" /> Add Building
                </PrimaryButton>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="filters.search" @input="applyFilters" placeholder="Search buildings..."
                        class="w-full pl-9 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <select v-model="filters.type" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All Types</option>
                    <option value="boys">Boys</option>
                    <option value="girls">Girls</option>
                    <option value="mixed">Mixed</option>
                </select>
                <select v-model="filters.status" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div v-if="!buildings.length" class="bg-white rounded-xl border border-gray-100 p-10 text-center">
                <Building2 class="h-10 w-10 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500">No buildings found</p>
                <p class="text-xs text-gray-400 mt-1">Add your first building to get started</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="b in buildings" :key="b.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ b.name }}</h3>
                            <p class="text-xs text-gray-500">{{ b.code }}</p>
                        </div>
                        <div class="flex gap-1.5">
                            <Badge :color="typeBadge[b.type]">{{ b.type }}</Badge>
                            <Badge :color="statusBadge[b.status]">{{ b.status }}</Badge>
                        </div>
                    </div>

                    <div v-if="b.address" class="flex items-start gap-1.5 mb-3 text-xs text-gray-500">
                        <MapPin class="h-3 w-3 shrink-0 mt-0.5" /> <span>{{ b.address }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="text-center p-2 bg-gray-50 rounded-md">
                            <Layers class="h-3.5 w-3.5 mx-auto mb-1 text-gray-400" />
                            <p class="text-sm font-semibold">{{ b.total_floors }}</p>
                            <p class="text-[10px] text-gray-400">Floors</p>
                        </div>
                        <div class="text-center p-2 bg-gray-50 rounded-md">
                            <BedDouble class="h-3.5 w-3.5 mx-auto mb-1 text-gray-400" />
                            <p class="text-sm font-semibold">{{ b.total_rooms }}</p>
                            <p class="text-[10px] text-gray-400">Rooms</p>
                        </div>
                        <div class="text-center p-2 bg-gray-50 rounded-md">
                            <Building2 class="h-3.5 w-3.5 mx-auto mb-1 text-gray-400" />
                            <p class="text-sm font-semibold">{{ b.occupied }}</p>
                            <p class="text-[10px] text-gray-400">Occupied</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1">
                        <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                            @click="openEdit(b)">
                            <Pencil class="h-3.5 w-3.5 text-gray-600" />
                        </button>
                        <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-red-50"
                            @click="destroy(b)">
                            <Trash2 class="h-3.5 w-3.5 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Add New Building</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building Name *" />
                        <TextInput v-model="createForm.name" required />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Code *" />
                        <TextInput v-model="createForm.code" required />
                        <InputError :message="createForm.errors.code" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type *" />
                        <select v-model="createForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="boys">Boys</option>
                            <option value="girls">Girls</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Total Floors" />
                        <TextInput type="number" v-model="createForm.total_floors" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Address" />
                    <textarea v-model="createForm.address" rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="createForm.status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false">Cancel</button>
                    <PrimaryButton :disabled="createForm.processing">{{ createForm.processing ? 'Creating...' : 'Create Building' }}</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="editOpen" @close="editOpen = false">
            <form @submit.prevent="submitEdit" class="p-6 space-y-4" v-if="editing">
                <h2 class="text-lg font-semibold text-gray-900">Edit Building</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building Name" />
                        <TextInput v-model="editForm.name" />
                    </div>
                    <div>
                        <InputLabel value="Code" />
                        <TextInput v-model="editForm.code" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Type" />
                        <select v-model="editForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="boys">Boys</option>
                            <option value="girls">Girls</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Total Floors" />
                        <TextInput type="number" v-model="editForm.total_floors" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Address" />
                    <textarea v-model="editForm.address" rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="editForm.status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="editOpen = false">Cancel</button>
                    <PrimaryButton :disabled="editForm.processing">{{ editForm.processing ? 'Saving...' : 'Save Changes'
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
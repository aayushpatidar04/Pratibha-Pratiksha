<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import { DoorOpen, Plus, Search, Pencil, Trash2, Snowflake, Wifi, Bath, Sun, Table2 } from 'lucide-vue-next';

const props = defineProps({ rooms: Array, buildings: Array, floors: Array, stats: Object, filters: Object });

const filters = reactive({
    search: props.filters?.search || '',
    building_id: props.filters?.building_id || '',
    floor_id: props.filters?.floor_id || '',
    status: props.filters?.status || 'all',
    room_type: props.filters?.room_type || 'all',
});

const applyFilters = () => {
    router.get('/infrastructure/rooms', {
        search: filters.search || undefined,
        building_id: filters.building_id || undefined,
        floor_id: filters.floor_id || undefined,
        status: filters.status !== 'all' ? filters.status : undefined,
        room_type: filters.room_type !== 'all' ? filters.room_type : undefined,
    }, { preserveState: true, replace: true });
};

const statusBadge = { available: 'green', occupied: 'blue', maintenance: 'amber', partially_occupied: 'purple' };

const createOpen = ref(false);
const editOpen = ref(false);
const editing = ref(null);

const blankAmenities = { has_ac: false, has_wifi: false, has_attached_bath: false, has_balcony: false, has_study_table: false };
const createForm = useForm({ building_id: '', floor_id: '', room_number: '', room_type: '2_seater', capacity: 2, monthly_rent_per_bed: 0, ...blankAmenities });
const editForm = useForm({ room_number: '', room_type: '2_seater', capacity: 2, monthly_rent_per_bed: 0, status: 'available', ...blankAmenities });

const floorsForBuilding = computed(() => props.floors.filter((f) => f.building_id === Number(createForm.building_id)));

const submitCreate = () => createForm.post('/infrastructure/rooms', { onSuccess: () => { createOpen.value = false; createForm.reset(); } });

const openEdit = (r) => {
    editing.value = r;
    editForm.room_number = r.room_number; editForm.room_type = r.room_type; editForm.capacity = r.capacity;
    editForm.monthly_rent_per_bed = r.monthly_rent_per_bed; editForm.status = r.status;
    editForm.has_ac = r.has_ac; editForm.has_wifi = r.has_wifi; editForm.has_attached_bath = r.has_attached_bath;
    editForm.has_balcony = r.has_balcony; editForm.has_study_table = r.has_study_table;
    editOpen.value = true;
};
const submitEdit = () => editForm.put(`/infrastructure/rooms/${editing.value.id}`, { onSuccess: () => (editOpen.value = false) });

const destroy = (r) => {
    if (confirm('Delete this room and its beds?')) router.delete(`/infrastructure/rooms/${r.id}`);
};

const amenityIcons = [
    { key: 'has_ac', icon: Snowflake, label: 'AC' },
    { key: 'has_wifi', icon: Wifi, label: 'WiFi' },
    { key: 'has_attached_bath', icon: Bath, label: 'Bath' },
    { key: 'has_balcony', icon: Sun, label: 'Balcony' },
    { key: 'has_study_table', icon: Table2, label: 'Study Table' },
];
</script>

<template>

    <Head title="Rooms" />
    <AuthenticatedLayout>
        <template #header>Infrastructure / Rooms</template>

        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <DoorOpen class="h-6 w-6 text-blue-600" /> Rooms
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage rooms, beds and amenities</p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true">
                    <Plus class="h-4 w-4" /> Add Room
                </PrimaryButton>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-gray-900">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400">Total Rooms</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-green-600">{{ stats.available }}</p>
                    <p class="text-xs text-gray-400">Available</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-blue-600">{{ stats.occupied }}</p>
                    <p class="text-xs text-gray-400">Occupied</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-blue-600">{{ stats.partiallyOccupied }}</p>
                    <p class="text-xs text-gray-400">Partially Occupied</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-amber-600">{{ stats.maintenance }}</p>
                    <p class="text-xs text-gray-400">Maintenance</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="filters.search" @input="applyFilters" placeholder="Search room number..."
                        class="w-full pl-9 rounded-lg border-gray-300 text-sm" />
                </div>
                <select v-model="filters.building_id" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All Buildings</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="filters.status" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All Status</option>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="partially_occupied">Partially Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div v-if="!rooms.length" class="bg-white rounded-xl border border-gray-100 p-10 text-center text-gray-400">
                No rooms
                found</div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="r in rooms" :key="r.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="font-semibold text-gray-900">Room {{ r.room_number }}</h3>
                            <p class="text-xs text-gray-500">{{ r.building?.name }} · {{ r.floor?.name }}</p>
                        </div>
                        <Badge :color="statusBadge[r.status]">{{ r.status.replace('_', ' ') }}</Badge>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">{{ r.room_type.replace('_', ' ') }} · {{ r.occupied_beds }}/{{
                        r.capacity }} beds · ₹{{ r.monthly_rent_per_bed }}/bed</p>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span v-for="a in amenityIcons.filter(x => r[x.key])" :key="a.key"
                            class="inline-flex items-center gap-1 text-[10px] bg-gray-50 text-gray-600 px-1.5 py-0.5 rounded">
                            <component :is="a.icon" class="h-3 w-3" />{{ a.label }}
                        </span>
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        <button class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                            @click="openEdit(r)">
                            <Pencil class="h-3.5 w-3.5 text-gray-600" />
                        </button>
                        <button class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                            @click="destroy(r)">
                            <Trash2 class="h-3.5 w-3.5 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="createOpen" @close="createOpen = false" maxWidth="lg">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Add New Room</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building *" />
                        <select v-model="createForm.building_id" required
                            class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="" disabled>Select building</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Floor *" />
                        <select v-model="createForm.floor_id" required
                            class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="" disabled>Select floor</option>
                            <option v-for="f in floorsForBuilding" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Room Number *" />
                        <TextInput v-model="createForm.room_number" required />
                    </div>
                    <div>
                        <InputLabel value="Room Type *" />
                        <select v-model="createForm.room_type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="1_seater">1 Seater</option>
                            <option value="2_seater">2 Seater</option>
                            <option value="3_seater">3 Seater</option>
                            <option value="4_seater">4 Seater</option>
                            <option value="5_seater">5 Seater</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Capacity *" />
                        <TextInput type="number" v-model="createForm.capacity" required />
                    </div>
                    <div>
                        <InputLabel value="Monthly Rent / Bed" />
                        <TextInput type="number" v-model="createForm.monthly_rent_per_bed" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <label v-for="a in amenityIcons" :key="a.key"
                        class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input type="checkbox" v-model="createForm[a.key]"
                            class="rounded border-gray-300 text-blue-600" /> {{
                        a.label }}
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false">Cancel</button>
                    <PrimaryButton :disabled="createForm.processing">Create Room</PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="editOpen" @close="editOpen = false" maxWidth="lg">
            <form @submit.prevent="submitEdit" class="p-6 space-y-4" v-if="editing">
                <h2 class="text-lg font-semibold text-gray-900">Edit Room {{ editing.room_number }}</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Room Number" />
                        <TextInput v-model="editForm.room_number" />
                    </div>
                    <div>
                        <InputLabel value="Room Type" />
                        <select v-model="editForm.room_type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="1_seater">1 Seater</option>
                            <option value="2_seater">2 Seater</option>
                            <option value="3_seater">3 Seater</option>
                            <option value="4_seater">4 Seater</option>
                            <option value="5_seater">5 Seater</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Capacity" />
                        <TextInput type="number" v-model="editForm.capacity" />
                    </div>
                    <div>
                        <InputLabel value="Monthly Rent / Bed" />
                        <TextInput type="number" v-model="editForm.monthly_rent_per_bed" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Status" />
                    <select v-model="editForm.status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="partially_occupied">Partially Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <label v-for="a in amenityIcons" :key="a.key"
                        class="flex items-center gap-1.5 text-sm text-gray-600">
                        <input type="checkbox" v-model="editForm[a.key]"
                            class="rounded border-gray-300 text-blue-600" /> {{
                        a.label }}
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="editOpen = false">Cancel</button>
                    <PrimaryButton :disabled="editForm.processing">Save Changes</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
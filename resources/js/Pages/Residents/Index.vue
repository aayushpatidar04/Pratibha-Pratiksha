<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { Users, Plus, Search, Trash2, Phone, Mail, GraduationCap, Eye } from 'lucide-vue-next';

const props = defineProps({ residents: Object, stats: Object, filters: Object });

const filters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || 'all',
    gender: props.filters?.gender || 'all',
});

const applyFilters = () => {
    router.get('/residents', {
        search: filters.search || undefined,
        status: filters.status !== 'all' ? filters.status : undefined,
        gender: filters.gender !== 'all' ? filters.gender : undefined,
    }, { preserveState: true, replace: true });
};

const statusColor = { active: 'green', inactive: 'gray', suspended: 'red', left: 'blue', upcoming: 'amber' };

const createOpen = ref(false);
const viewOpen = ref(false);
const viewing = ref(null);

const createForm = useForm({
    first_name: '', last_name: '', email: '', phone: '', whatsapp_number: '', date_of_birth: '',
    gender: 'male', blood_group: '', address: '', city: '', state: '', country: 'India', pincode: '',
    course: '', year: '', batch: '', roll_number: '', institute: '',
    father_name: '', father_phone: '', mother_name: '', mother_phone: '', status: 'upcoming',
});

const submitCreate = () => createForm.post('/residents', { onSuccess: () => { createOpen.value = false; createForm.reset(); } });

const openView = (r) => { viewing.value = r; viewOpen.value = true; };

const destroy = (r) => {
    if (confirm('Remove this resident?')) router.delete(`/residents/${r.id}`);
};
</script>

<template>

    <Head title="Residents" />
    <AuthenticatedLayout>
        <template #header>Residents / All Residents</template>

        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <Users class="h-6 w-6 text-blue-600" /> Residents
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage resident records, admissions and status</p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true">
                    <Plus class="h-4 w-4" /> Add Resident
                </PrimaryButton>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400">Total</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-green-600">{{ stats.active }}</p>
                    <p class="text-xs text-gray-400">Active</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-amber-600">{{ stats.upcoming }}</p>
                    <p class="text-xs text-gray-400">Upcoming</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-red-600">{{ stats.suspended }}</p>
                    <p class="text-xs text-gray-400">Suspended</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-lg font-bold text-blue-600">{{ stats.left }}</p>
                    <p class="text-xs text-gray-400">Left</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="filters.search" @input="applyFilters" placeholder="Search by name, phone, code..."
                        class="w-full pl-9 rounded-lg border-gray-300 text-sm" />
                </div>
                <select v-model="filters.status" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="suspended">Suspended</option>
                    <option value="left">Left</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select v-model="filters.gender" @change="applyFilters" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Contact</th>
                            <th class="text-left px-4 py-3">Course</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in residents.data" :key="r.id">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ r.first_name }} {{ r.last_name }}</p>
                                <p class="text-xs text-gray-400">{{ r.resident_code }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="flex items-center gap-1.5 text-xs">
                                    <Phone class="h-3 w-3" />{{ r.phone }}
                                </div>
                                <div v-if="r.email" class="flex items-center gap-1.5 text-xs text-gray-400">
                                    <Mail class="h-3 w-3" />{{ r.email }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div v-if="r.course" class="flex items-center gap-1.5 text-xs">
                                    <GraduationCap class="h-3 w-3" />{{ r.course }}
                                </div>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[r.status]">{{ r.status }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-gray-100"
                                    @click="openView(r)">
                                    <Eye class="h-3.5 w-3.5 text-gray-600" />
                                </button>
                                <button
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                    @click="destroy(r)">
                                    <Trash2 class="h-3.5 w-3.5 text-red-500" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!residents.data.length">
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">No residents found</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="residents.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100">
                    <template v-for="link in residents.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 text-xs rounded-lg"
                            :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'" />
                        <span v-else v-html="link.label" class="px-3 py-1 text-xs text-gray-300" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :show="createOpen" @close="createOpen = false" maxWidth="xl">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                <h2 class="text-lg font-semibold text-gray-900">Add New Resident</h2>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">Personal Details</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="First Name *" />
                        <TextInput v-model="createForm.first_name" required />
                        <InputError :message="createForm.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel value="Last Name" />
                        <TextInput v-model="createForm.last_name" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Phone *" />
                        <TextInput v-model="createForm.phone" required />
                        <InputError :message="createForm.errors.phone" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput type="email" v-model="createForm.email" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Gender *" />
                        <select v-model="createForm.gender" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Date of Birth" />
                        <TextInput type="date" v-model="createForm.date_of_birth" />
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">Academic</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Course" />
                        <TextInput v-model="createForm.course" />
                    </div>
                    <div>
                        <InputLabel value="Institute" />
                        <TextInput v-model="createForm.institute" />
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-400 uppercase pt-1">Parent / Guardian</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Father's Name" />
                        <TextInput v-model="createForm.father_name" />
                    </div>
                    <div>
                        <InputLabel value="Father's Phone" />
                        <TextInput v-model="createForm.father_phone" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Status" />
                    <select v-model="createForm.status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                        <option value="left">Left</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2 sticky bottom-0 bg-white">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false">Cancel</button>
                    <PrimaryButton :disabled="createForm.processing">{{ createForm.processing ? 'Saving...' : 'Add Resident' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- View Modal -->
        <Modal :show="viewOpen" @close="viewOpen = false">
            <div class="p-6 space-y-3" v-if="viewing">
                <h2 class="text-lg font-semibold text-gray-900">{{ viewing.first_name }} {{ viewing.last_name }}</h2>
                <p class="text-xs text-gray-400">{{ viewing.resident_code }}</p>
                <div class="grid grid-cols-2 gap-3 text-sm pt-2">
                    <div>
                        <p class="text-xs text-gray-400">Phone</p>
                        <p>{{ viewing.phone }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p>{{ viewing.email || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Gender</p>
                        <p class="capitalize">{{ viewing.gender }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Course</p>
                        <p>{{ viewing.course || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Father's Name</p>
                        <p>{{ viewing.father_name || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Father's Phone</p>
                        <p>{{ viewing.father_phone || '—' }}</p>
                    </div>
                </div>
                <div class="flex justify-end pt-4">
                    <button class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="viewOpen = false">Close</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
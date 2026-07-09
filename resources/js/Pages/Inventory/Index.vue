<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Boxes, Plus, Trash2 } from 'lucide-vue-next';

defineProps({ items: Array });

const createOpen = ref(false);
const createForm = useForm({ item_name: '', category: 'room', total_quantity: 0, unit: 'pieces' });
const submitCreate = () => createForm.post('/inventory', { onSuccess: () => { createOpen.value = false; createForm.reset(); } });

const updateInUse = (item, in_use) => router.put(`/inventory/${item.id}`, { in_use });
const destroy = (item) => { if (confirm('Remove this item?')) router.delete(`/inventory/${item.id}`); };
</script>

<template>
    <Head title="Inventory" />
    <AuthenticatedLayout>
        <template #header>Infrastructure / Inventory</template>

        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><Boxes class="h-6 w-6 text-blue-600" /> Inventory</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Track furniture and equipment across the hostel</p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"><Plus class="h-4 w-4" /> Add Item</PrimaryButton>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr><th class="text-left px-4 py-3">Item</th><th class="text-left px-4 py-3">Category</th><th class="text-left px-4 py-3">Total</th><th class="text-left px-4 py-3">In Use</th><th class="text-left px-4 py-3">Available</th><th class="text-left px-4 py-3">Damaged</th><th class="text-right px-4 py-3">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ item.item_name }}</td>
                            <td class="px-4 py-3 text-gray-600 capitalize">{{ item.category }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.total_quantity }} {{ item.unit }}</td>
                            <td class="px-4 py-3">
                                <input type="number" :value="item.in_use" @change="updateInUse(item, $event.target.value)" class="w-20 rounded-lg border-gray-300 text-sm" />
                            </td>
                            <td class="px-4 py-3 text-green-600 font-medium">{{ item.available }}</td>
                            <td class="px-4 py-3 text-red-500">{{ item.damaged }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50" @click="destroy(item)"><Trash2 class="h-3.5 w-3.5 text-red-500" /></button>
                            </td>
                        </tr>
                        <tr v-if="!items.length"><td colspan="7" class="px-4 py-10 text-center text-gray-400">No inventory items yet</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Add Inventory Item</h2>
                <div><InputLabel value="Item Name *" /><TextInput v-model="createForm.item_name" required /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Category *" />
                        <select v-model="createForm.category" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="room">Room</option><option value="student">Student</option><option value="common">Common Area</option>
                        </select>
                    </div>
                    <div><InputLabel value="Total Quantity *" /><TextInput type="number" v-model="createForm.total_quantity" required /></div>
                </div>
                <div><InputLabel value="Unit" /><TextInput v-model="createForm.unit" placeholder="pieces" /></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-gray-300" @click="createOpen = false">Cancel</button>
                    <PrimaryButton :disabled="createForm.processing">Add Item</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
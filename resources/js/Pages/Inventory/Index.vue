<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { Boxes, Plus, Trash2, Save } from "lucide-vue-next";

defineProps({ items: Array });

const createOpen = ref(false);
const createForm = useForm({
    item_name: "",
    category: "room",
    total_quantity: 0,
    unit: "pieces",
});
const submitCreate = () =>
    createForm.post("/infrastructure/inventory", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const inventoryEdits = reactive({});

const getInventoryEdit = (item) => {
    if (!inventoryEdits[item.id]) {
        inventoryEdits[item.id] = {
            total_quantity: Number(item.total_quantity || 0),
            in_use: Number(item.in_use || 0),
            damaged: Number(item.damaged || 0),
            missing: Number(item.missing || 0),
        };
    }

    return inventoryEdits[item.id];
};

const calculatedAvailable = (item) => {
    const edit = getInventoryEdit(item);

    return Math.max(
        0,
        Number(edit.total_quantity || 0) -
            Number(edit.in_use || 0) -
            Number(edit.damaged || 0) -
            Number(edit.missing || 0),
    );
};

const updateInventory = (item) => {
    const edit = getInventoryEdit(item);

    router.put(
        `/infrastructure/inventory/${item.id}`,
        {
            total_quantity: Number(edit.total_quantity || 0),
            in_use: Number(edit.in_use || 0),
            damaged: Number(edit.damaged || 0),
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                delete inventoryEdits[item.id];
            },
        },
    );
};

const inventoryChanged = (item) => {
    const edit = getInventoryEdit(item);

    return (
        Number(edit.total_quantity) !== Number(item.total_quantity) ||
        Number(edit.in_use) !== Number(item.in_use) ||
        Number(edit.damaged) !== Number(item.damaged)
    );
};

const destroy = (item) => {
    if (confirm("Remove this item?"))
        router.delete(`/infrastructure/inventory/${item.id}`);
};
</script>

<template>
    <Head title="Inventory" />
    <AuthenticatedLayout>
        <template #header>Infrastructure / Inventory</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <Boxes class="h-6 w-6 text-blue-600" /> Inventory
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Track furniture and equipment across the hostel
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> Add Item</PrimaryButton
                >
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Total</th>
                            <th class="px-4 py-3 text-left">In Use</th>
                            <th class="px-4 py-3 text-left">Available</th>
                            <th class="px-4 py-3 text-left">Damaged</th>
                            <th class="px-4 py-3 text-left">Missing</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ item.item_name }}

                                <p
                                    class="mt-0.5 text-xs font-normal text-gray-400"
                                >
                                    {{ item.unit }}
                                </p>
                            </td>

                            <td class="px-4 py-3 capitalize text-gray-600">
                                {{ item.category }}
                            </td>

                            <!-- Total quantity -->
                            <td class="px-4 py-3">
                                <input
                                    v-model.number="
                                        getInventoryEdit(item).total_quantity
                                    "
                                    type="number"
                                    min="0"
                                    class="w-24 rounded-lg border-gray-300 text-sm"
                                />
                            </td>

                            <!-- In use -->
                            <td class="px-4 py-3">
                                <input
                                    v-model.number="
                                        getInventoryEdit(item).in_use
                                    "
                                    type="number"
                                    min="0"
                                    class="w-24 rounded-lg border-gray-300 text-sm"
                                />
                            </td>

                            <!-- Available -->
                            <td class="px-4 py-3 font-medium text-green-600">
                                {{ calculatedAvailable(item) }}
                                {{ item.unit }}
                            </td>

                            <!-- Damaged -->
                            <td class="px-4 py-3">
                                <input
                                    v-model.number="
                                        getInventoryEdit(item).damaged
                                    "
                                    type="number"
                                    min="0"
                                    class="w-24 rounded-lg border-gray-300 text-sm"
                                />
                            </td>

                            <!-- Missing -->
                            <td class="px-4 py-3 font-medium text-orange-600">
                                {{ item.missing || 0 }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                                        :class="
                                            inventoryChanged(item)
                                                ? 'bg-blue-50 text-blue-600 hover:bg-blue-100'
                                                : 'cursor-not-allowed text-gray-300'
                                        "
                                        :disabled="!inventoryChanged(item)"
                                        title="Save inventory changes"
                                        @click="updateInventory(item)"
                                    >
                                        <Save class="h-3.5 w-3.5" />
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-red-50"
                                        title="Delete inventory item"
                                        @click="destroy(item)"
                                    >
                                        <Trash2
                                            class="h-3.5 w-3.5 text-red-500"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.length">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No inventory items yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Add Inventory Item
                </h2>
                <div>
                    <InputLabel value="Item Name *" /><TextInput
                        v-model="createForm.item_name"
                        required
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Category *" />
                        <select
                            v-model="createForm.category"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="room">Room</option>
                            <option value="student">Student</option>
                            <option value="common">Common Area</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Total Quantity *" /><TextInput
                            type="number"
                            v-model="createForm.total_quantity"
                            required
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Unit" /><TextInput
                        v-model="createForm.unit"
                        placeholder="pieces"
                    />
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
                        >Add Item</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

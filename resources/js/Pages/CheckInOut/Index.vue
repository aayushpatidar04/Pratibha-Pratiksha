<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { LogIn, LogOut, DoorOpen, Search } from "lucide-vue-next";

const props = defineProps({
    activeStays: Array,
    unassignedResidents: Array,
    buildings: Array,
    floors: Array,
    rooms: Array,
});

const checkinOpen = ref(false);
const checkoutOpen = ref(false);
const checkingOutStay = ref(null);
const search = ref("");

const filteredUnassigned = computed(() => {
    if (!search.value) return props.unassignedResidents;
    const s = search.value.toLowerCase();
    return props.unassignedResidents.filter(
        (r) =>
            `${r.first_name} ${r.last_name}`.toLowerCase().includes(s) ||
            r.resident_code.toLowerCase().includes(s) ||
            r.phone.includes(s),
    );
});

const checkinForm = useForm({
    resident_id: "",
    building_id: "",
    floor_id: "",
    room_id: "",
    bed_id: "",
    check_in_date: new Date().toISOString().slice(0, 10),
    expected_check_out_date: "",
    rent_amount: "",
    deposit_amount: "",
    bill_type: "monthly",
    notes: "",
});

const floorsForBuilding = computed(() =>
    props.floors.filter(
        (f) => f.building_id === Number(checkinForm.building_id),
    ),
);
const roomsForFloor = computed(() =>
    props.rooms.filter((r) => r.floor_id === Number(checkinForm.floor_id)),
);
const selectedRoom = computed(() =>
    props.rooms.find((r) => r.id === Number(checkinForm.room_id)),
);
const vacantBeds = computed(
    () => selectedRoom.value?.beds?.filter((b) => b.status === "vacant") || [],
);

const onRoomChange = () => {
    checkinForm.bed_id = "";
    if (selectedRoom.value)
        checkinForm.rent_amount = selectedRoom.value.monthly_rent_per_bed;
};

const openCheckin = (resident) => {
    checkinForm.reset();
    checkinForm.resident_id = resident.id;
    checkinForm.check_in_date = new Date().toISOString().slice(0, 10);
    checkinOpen.value = true;
};

const submitCheckin = () =>
    checkinForm.post("/checkinout/checkin", {
        onSuccess: () => (checkinOpen.value = false),
    });

const checkoutForm = useForm({
    actual_check_out_date: new Date().toISOString().slice(0, 10),
});
const openCheckout = (stay) => {
    checkingOutStay.value = stay;
    checkoutOpen.value = true;
};
const submitCheckout = () =>
    checkoutForm.post(`/checkinout/${checkingOutStay.value.id}/checkout`, {
        onSuccess: () => (checkoutOpen.value = false),
    });
</script>

<template>
    <Head title="Check-In / Check-Out" />
    <AuthenticatedLayout>
        <template #header>Check-In / Check-Out</template>

        <div class="space-y-6">
            <div>
                <h1
                    class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                >
                    <DoorOpen class="h-6 w-6 text-blue-600" /> Check-In /
                    Check-Out
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Allot beds to residents and manage checkouts
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Awaiting check-in -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2
                        class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2"
                    >
                        <LogIn class="h-4 w-4 text-green-600" /> Awaiting Room
                        Allotment
                    </h2>
                    <div class="relative mb-3">
                        <Search
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                        />
                        <input
                            v-model="search"
                            placeholder="Search residents..."
                            class="w-full pl-9 rounded-lg border-gray-300 text-sm"
                        />
                    </div>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <div
                            v-for="r in filteredUnassigned"
                            :key="r.id"
                            class="flex items-center justify-between p-2.5 rounded-lg border border-gray-100 hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-2">
                                <img
                                    v-if="r.photo_url"
                                    :src="`/storage/${r.photo_url}`"
                                    class="h-16 w-16 rounded-full object-cover"
                                />
                                <div
                                    v-else
                                    class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-400"
                                >
                                    {{ r.first_name?.charAt(0) }}
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ r.first_name }} {{ r.last_name }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ r.resident_code }} · {{ r.phone }}
                                    </p>
                                </div>
                            </div>
                            <button
                                class="text-xs font-medium text-blue-600 hover:underline"
                                @click="openCheckin(r)"
                            >
                                Allot Room
                            </button>
                        </div>
                        <p
                            v-if="!filteredUnassigned.length"
                            class="text-sm text-gray-400 text-center py-6"
                        >
                            Everyone has a room assigned 🎉
                        </p>
                    </div>
                </div>

                <!-- Active stays -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                >
                    <h2
                        class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2"
                    >
                        <LogOut class="h-4 w-4 text-red-500" /> Currently
                        Checked In
                    </h2>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <div
                            v-for="s in activeStays"
                            :key="s.id"
                            class="flex items-center justify-between p-2.5 rounded-lg border border-gray-100 hover:bg-gray-50"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ s.resident?.first_name }}
                                    {{ s.resident?.last_name }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ s.room?.room_number }} · Bed
                                    {{ s.bed?.bed_number }} · since
                                    {{ s.check_in_date }}
                                </p>
                            </div>
                            <button
                                class="text-xs font-medium text-red-600 hover:underline"
                                @click="openCheckout(s)"
                            >
                                Check Out
                            </button>
                        </div>
                        <p
                            v-if="!activeStays.length"
                            class="text-sm text-gray-400 text-center py-6"
                        >
                            No active stays
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="checkinOpen" @close="checkinOpen = false" maxWidth="lg">
            <form @submit.prevent="submitCheckin" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Allot Room</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building *" />
                        <select
                            v-model="checkinForm.building_id"
                            required
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="" disabled>Select building</option>
                            <option
                                v-for="b in buildings"
                                :key="b.id"
                                :value="b.id"
                            >
                                {{ b.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Floor *" />
                        <select
                            v-model="checkinForm.floor_id"
                            required
                            :disabled="!checkinForm.building_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="" disabled>Select floor</option>
                            <option
                                v-for="f in floorsForBuilding"
                                :key="f.id"
                                :value="f.id"
                            >
                                {{ f.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Room *" />
                        <select
                            v-model="checkinForm.room_id"
                            @change="onRoomChange"
                            required
                            :disabled="!checkinForm.floor_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="" disabled>Select room</option>
                            <option
                                v-for="r in roomsForFloor"
                                :key="r.id"
                                :value="r.id"
                                :disabled="r.occupied_beds >= r.capacity"
                            >
                                {{ r.room_number }} ({{ r.occupied_beds }}/{{
                                    r.capacity
                                }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Bed *" />
                        <select
                            v-model="checkinForm.bed_id"
                            required
                            :disabled="!checkinForm.room_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="" disabled>Select bed</option>
                            <option
                                v-for="bed in vacantBeds"
                                :key="bed.id"
                                :value="bed.id"
                            >
                                {{ bed.bed_number }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Check-in Date" /><TextInput
                            type="date"
                            v-model="checkinForm.check_in_date"
                        />
                    </div>
                    <div>
                        <InputLabel value="Expected Check-out" /><TextInput
                            type="date"
                            v-model="checkinForm.expected_check_out_date"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Monthly Rent" /><TextInput
                            type="number"
                            v-model="checkinForm.rent_amount"
                        />
                    </div>
                    <div>
                        <InputLabel value="Security Deposit" /><TextInput
                            type="number"
                            v-model="checkinForm.deposit_amount"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="checkinOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="checkinForm.processing"
                        >Confirm Check-In</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <Modal :show="checkoutOpen" @close="checkoutOpen = false">
            <form
                @submit.prevent="submitCheckout"
                class="p-6 space-y-4"
                v-if="checkingOutStay"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    Check Out {{ checkingOutStay.resident?.first_name }}
                </h2>
                <p class="text-sm text-gray-500">
                    Room {{ checkingOutStay.room?.room_number }} · Bed
                    {{ checkingOutStay.bed?.bed_number }} will be freed up.
                </p>
                <div>
                    <InputLabel value="Check-out Date" /><TextInput
                        type="date"
                        v-model="checkoutForm.actual_check_out_date"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="checkoutOpen = false"
                    >
                        Cancel
                    </button>
                    <DangerButton
                        type="submit"
                        :disabled="checkoutForm.processing"
                        >Confirm Check-Out</DangerButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

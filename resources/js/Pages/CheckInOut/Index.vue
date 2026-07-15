<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

import {
    BedDouble,
    Boxes,
    Building2,
    CalendarDays,
    CheckCircle2,
    Clock3,
    DoorOpen,
    LogIn,
    LogOut,
    Search,
    UserRoundCheck,
    UserRoundPlus,
} from "lucide-vue-next";

const props = defineProps({
    awaitingCheckIn: {
        type: Array,
        default: () => [],
    },

    checkedInStays: {
        type: Array,
        default: () => [],
    },

    studentInventory: {
        type: Array,
        default: () => [],
    },

    unassignedResidents: {
        type: Array,
        default: () => [],
    },

    buildings: {
        type: Array,
        default: () => [],
    },

    floors: {
        type: Array,
        default: () => [],
    },

    rooms: {
        type: Array,
        default: () => [],
    },
});

const today = new Date().toISOString().slice(0, 10);

/*
|--------------------------------------------------------------------------
| Page search
|--------------------------------------------------------------------------
*/

const unassignedSearch = ref("");
const awaitingSearch = ref("");
const checkedInSearch = ref("");

const normalizeText = (value) => String(value ?? "").toLowerCase();

const matchesStaySearch = (stay, search) => {
    if (!search) {
        return true;
    }

    const keyword = normalizeText(search);

    return [
        stay.resident?.first_name,
        stay.resident?.last_name,
        stay.resident?.resident_code,
        stay.resident?.phone,
        stay.room?.room_number,
        stay.bed?.bed_number,
        stay.building?.name,
    ].some((value) => normalizeText(value).includes(keyword));
};

const filteredUnassigned = computed(() => {
    const keyword = normalizeText(unassignedSearch.value);

    if (!keyword) {
        return props.unassignedResidents;
    }

    return props.unassignedResidents.filter((resident) => {
        return [
            resident.first_name,
            resident.last_name,
            resident.resident_code,
            resident.phone,
        ].some((value) => normalizeText(value).includes(keyword));
    });
});

const filteredAwaitingCheckIn = computed(() =>
    props.awaitingCheckIn.filter((stay) =>
        matchesStaySearch(stay, awaitingSearch.value),
    ),
);

const filteredCheckedIn = computed(() =>
    props.checkedInStays.filter((stay) =>
        matchesStaySearch(stay, checkedInSearch.value),
    ),
);

/*
|--------------------------------------------------------------------------
| Room allotment
|--------------------------------------------------------------------------
*/

const allotmentOpen = ref(false);
const allottingResident = ref(null);

const allotmentForm = useForm({
    resident_id: "",

    building_id: "",
    floor_id: "",
    room_id: "",
    bed_id: "",

    check_in_date: today,
    expected_check_out_date: "",

    billing_basis: "monthly",
    bill_type: "monthly",

    rent_amount: "",
    daily_rate: 350,
    deposit_amount: "",

    notes: "",
});

const floorsForBuilding = computed(() => {
    return props.floors.filter(
        (floor) =>
            Number(floor.building_id) === Number(allotmentForm.building_id),
    );
});

const roomsForFloor = computed(() => {
    return props.rooms.filter(
        (room) => Number(room.floor_id) === Number(allotmentForm.floor_id),
    );
});

const selectedRoom = computed(() => {
    return props.rooms.find(
        (room) => Number(room.id) === Number(allotmentForm.room_id),
    );
});

const vacantBeds = computed(() => {
    return (
        selectedRoom.value?.beds?.filter((bed) => bed.status === "vacant") ?? []
    );
});

const estimatedStayDays = computed(() => {
    if (
        allotmentForm.billing_basis !== "daily" ||
        !allotmentForm.check_in_date ||
        !allotmentForm.expected_check_out_date
    ) {
        return 0;
    }

    const checkIn = new Date(`${allotmentForm.check_in_date}T00:00:00`);

    const checkOut = new Date(
        `${allotmentForm.expected_check_out_date}T00:00:00`,
    );

    if (
        Number.isNaN(checkIn.getTime()) ||
        Number.isNaN(checkOut.getTime()) ||
        checkOut < checkIn
    ) {
        return 0;
    }

    const millisecondsPerDay = 1000 * 60 * 60 * 24;

    return (
        Math.floor(
            (checkOut.getTime() - checkIn.getTime()) / millisecondsPerDay,
        ) + 1
    );
});

const estimatedDailyAmount = computed(() => {
    return estimatedStayDays.value * Number(allotmentForm.daily_rate || 0);
});

watch(
    () => allotmentForm.building_id,
    () => {
        allotmentForm.floor_id = "";
        allotmentForm.room_id = "";
        allotmentForm.bed_id = "";
    },
);

watch(
    () => allotmentForm.floor_id,
    () => {
        allotmentForm.room_id = "";
        allotmentForm.bed_id = "";
    },
);

watch(
    () => allotmentForm.room_id,
    () => {
        allotmentForm.bed_id = "";

        if (selectedRoom.value) {
            allotmentForm.rent_amount =
                selectedRoom.value.monthly_rent_per_bed ?? "";
        }
    },
);

watch(
    () => allotmentForm.billing_basis,
    (billingBasis) => {
        allotmentForm.bill_type = billingBasis;

        if (billingBasis === "monthly") {
            allotmentForm.daily_rate = 350;
            allotmentForm.expected_check_out_date = "";
        } else {
            allotmentForm.rent_amount = "";
            allotmentForm.daily_rate = Number(allotmentForm.daily_rate) || 350;
        }

        allotmentForm.clearErrors(
            "rent_amount",
            "daily_rate",
            "expected_check_out_date",
        );
    },
);

const openRoomAllotment = (resident) => {
    allottingResident.value = resident;

    allotmentForm.reset();
    allotmentForm.clearErrors();

    allotmentForm.resident_id = resident.id;
    allotmentForm.check_in_date = today;
    allotmentForm.billing_basis = "monthly";
    allotmentForm.bill_type = "monthly";
    allotmentForm.daily_rate = 350;

    allotmentOpen.value = true;
};

const closeRoomAllotment = () => {
    if (allotmentForm.processing) {
        return;
    }

    allotmentOpen.value = false;
    allottingResident.value = null;
    allotmentForm.clearErrors();
};

const submitRoomAllotment = () => {
    allotmentForm
        .transform((data) => ({
            ...data,

            rent_amount:
                data.billing_basis === "monthly" ? data.rent_amount : null,

            daily_rate: data.billing_basis === "daily" ? data.daily_rate : null,

            expected_check_out_date:
                data.billing_basis === "daily"
                    ? data.expected_check_out_date
                    : data.expected_check_out_date || null,

            deposit_amount: data.deposit_amount || 0,
        }))
        .post(route("checkinout.allot"), {
            preserveScroll: true,

            onSuccess: () => {
                allotmentOpen.value = false;
                allottingResident.value = null;
                allotmentForm.reset();
            },
        });
};

/*
|--------------------------------------------------------------------------
| Actual student check-in
|--------------------------------------------------------------------------
*/

const actualCheckinOpen = ref(false);
const checkingInStay = ref(null);

const actualCheckinForm = useForm({
    check_in_date: today,
    inventory: [],
});

const getInventoryItem = (inventoryId) => {
    return props.studentInventory.find(
        (item) => Number(item.id) === Number(inventoryId),
    );
};

const openActualCheckin = (stay) => {
    checkingInStay.value = stay;

    actualCheckinForm.reset();
    actualCheckinForm.clearErrors();

    actualCheckinForm.check_in_date = stay.check_in_date || today;

    actualCheckinForm.inventory = props.studentInventory.map((item) => ({
        inventory_id: item.id,
        selected: false,
        quantity: 1,
        condition_at_issue: "good",
        notes: "",
    }));

    actualCheckinOpen.value = true;
};

const closeActualCheckin = () => {
    if (actualCheckinForm.processing) {
        return;
    }

    actualCheckinOpen.value = false;
    checkingInStay.value = null;
    actualCheckinForm.reset();
};

const submitActualCheckin = () => {
    if (!checkingInStay.value) {
        return;
    }

    actualCheckinForm
        .transform((data) => ({
            check_in_date: data.check_in_date,

            inventory: data.inventory
                .filter((assignment) => assignment.selected)
                .map((assignment) => ({
                    inventory_id: assignment.inventory_id,
                    quantity: Number(assignment.quantity),
                    condition_at_issue: assignment.condition_at_issue,
                    notes: assignment.notes || null,
                })),
        }))
        .post(route("checkinout.confirm-checkin", checkingInStay.value.id), {
            preserveScroll: true,

            onSuccess: () => {
                actualCheckinOpen.value = false;
                checkingInStay.value = null;
                actualCheckinForm.reset();
            },
        });
};

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

const checkoutOpen = ref(false);
const checkingOutStay = ref(null);

const checkoutForm = useForm({
    actual_check_out_date: today,
    decision: "",
    checkout_notes: "",
    inventory_returns: [],
});

const openCheckout = (stay) => {
    checkingOutStay.value = stay;

    checkoutForm.reset();
    checkoutForm.clearErrors();

    checkoutForm.actual_check_out_date = today;
    checkoutForm.decision = "";
    checkoutForm.checkout_notes = "";

    checkoutForm.inventory_returns =
        stay.inventory_assignments?.map((assignment) => ({
            assignment_id: assignment.id,
            inventory_id: assignment.inventory_id,
            item_name: assignment.inventory?.item_name || "Inventory Item",
            assigned_quantity: Number(assignment.quantity || 0),

            returned_good_quantity: Number(
                assignment.returned_good_quantity || 0,
            ),

            returned_damaged_quantity: Number(
                assignment.returned_damaged_quantity || 0,
            ),

            missing_quantity: Number(assignment.missing_quantity || 0),

            return_notes: assignment.return_notes || "",
        })) || [];

    checkoutOpen.value = true;
};

const closeCheckout = () => {
    if (checkoutForm.processing) {
        return;
    }

    checkoutOpen.value = false;
    checkingOutStay.value = null;
    checkoutForm.reset();
};

const reviewedQuantity = (item) => {
    return (
        Number(item.returned_good_quantity || 0) +
        Number(item.returned_damaged_quantity || 0) +
        Number(item.missing_quantity || 0)
    );
};

const allAssetsReviewed = computed(() => {
    return checkoutForm.inventory_returns.every(
        (item) => reviewedQuantity(item) === Number(item.assigned_quantity),
    );
});

const submitCheckoutDecision = (decision) => {
    if (!checkingOutStay.value) return;

    checkoutForm.decision = decision;

    checkoutForm
        .transform((data) => ({
            actual_check_out_date: data.actual_check_out_date,
            decision,
            checkout_notes: data.checkout_notes || null,

            inventory_returns: data.inventory_returns.map((item) => ({
                assignment_id: item.assignment_id,

                returned_good_quantity: Number(
                    item.returned_good_quantity || 0,
                ),

                returned_damaged_quantity: Number(
                    item.returned_damaged_quantity || 0,
                ),

                missing_quantity: Number(item.missing_quantity || 0),

                return_notes: item.return_notes || null,
            })),
        }))
        .post(route("checkinout.checkout-review", checkingOutStay.value.id), {
            preserveScroll: true,

            onSuccess: () => {
                checkoutOpen.value = false;
                checkingOutStay.value = null;
                checkoutForm.reset();
            },
        });
};

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatCurrency = (amount) => {
    return Number(amount || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (date) => {
    if (!date) {
        return "—";
    }

    return new Date(`${date}T00:00:00`).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};
</script>

<template>
    <Head title="Check-In / Check-Out" />

    <AuthenticatedLayout>
        <template #header>Check-In / Check-Out</template>

        <div class="space-y-6">
            <!-- Page header -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-gray-900"
                    >
                        <DoorOpen class="h-6 w-6 text-blue-600" />
                        Check-In / Check-Out
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Reserve rooms, confirm physical check-in, assign student
                        assets, and manage checkout.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 text-xs">
                    <span
                        class="rounded-full bg-blue-50 px-3 py-1.5 font-medium text-blue-700"
                    >
                        {{ unassignedResidents.length }} unassigned
                    </span>

                    <span
                        class="rounded-full bg-amber-50 px-3 py-1.5 font-medium text-amber-700"
                    >
                        {{ awaitingCheckIn.length }} awaiting check-in
                    </span>

                    <span
                        class="rounded-full bg-green-50 px-3 py-1.5 font-medium text-green-700"
                    >
                        {{ checkedInStays.length }} checked in
                    </span>
                </div>
            </div>

            <!-- Workflow information -->
            <div
                class="grid grid-cols-1 gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 md:grid-cols-3"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm"
                    >
                        <BedDouble class="h-4 w-4" />
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-blue-900">
                            1. Allot Room
                        </p>

                        <p class="mt-0.5 text-xs text-blue-700">
                            Reserve a building, room and bed for the resident.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm"
                    >
                        <Boxes class="h-4 w-4" />
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-blue-900">
                            2. Confirm Check-In
                        </p>

                        <p class="mt-0.5 text-xs text-blue-700">
                            Confirm arrival date and issue student inventory.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-blue-600 shadow-sm"
                    >
                        <LogOut class="h-4 w-4" />
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-blue-900">
                            3. Check Out
                        </p>

                        <p class="mt-0.5 text-xs text-blue-700">
                            Finalize the stay and release the room and bed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main columns -->
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <!-- Unassigned residents -->
                <section
                    class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2
                                    class="flex items-center gap-2 text-sm font-semibold text-gray-900"
                                >
                                    <UserRoundPlus
                                        class="h-4 w-4 text-blue-600"
                                    />

                                    Awaiting Room Allotment
                                </h2>

                                <p class="mt-1 text-xs text-gray-500">
                                    Residents who do not have an active or
                                    upcoming stay.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"
                            >
                                {{ filteredUnassigned.length }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="relative mb-3">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <input
                                v-model="unassignedSearch"
                                type="search"
                                placeholder="Search residents..."
                                class="w-full rounded-lg border-gray-300 pl-9 text-sm"
                            />
                        </div>

                        <div class="max-h-[520px] space-y-2 overflow-y-auto">
                            <div
                                v-for="resident in filteredUnassigned"
                                :key="resident.id"
                                class="rounded-xl border border-gray-100 p-3 transition hover:border-blue-200 hover:bg-blue-50/40"
                            >
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="resident.photo_url"
                                        :src="`/storage/${resident.photo_url}`"
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-500"
                                    >
                                        {{
                                            resident.first_name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900"
                                        >
                                            {{ resident.first_name }}
                                            {{ resident.last_name }}
                                        </p>

                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-500"
                                        >
                                            {{ resident.resident_code }} ·
                                            {{ resident.phone }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                    @click="openRoomAllotment(resident)"
                                >
                                    <BedDouble class="h-3.5 w-3.5" />
                                    Allot Room
                                </button>
                            </div>

                            <div
                                v-if="!filteredUnassigned.length"
                                class="rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center"
                            >
                                <CheckCircle2
                                    class="mx-auto h-8 w-8 text-green-500"
                                />

                                <p
                                    class="mt-2 text-sm font-medium text-gray-700"
                                >
                                    No residents waiting for allotment
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Room allotted, check-in pending -->
                <section
                    class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                >
                    <div
                        class="border-b border-amber-100 bg-amber-50 px-5 py-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2
                                    class="flex items-center gap-2 text-sm font-semibold text-amber-900"
                                >
                                    <Clock3 class="h-4 w-4 text-amber-600" />

                                    Room Allotted — Check-In Pending
                                </h2>

                                <p class="mt-1 text-xs text-amber-700">
                                    Room is reserved but physical arrival is not
                                    confirmed.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            >
                                {{ filteredAwaitingCheckIn.length }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="relative mb-3">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <input
                                v-model="awaitingSearch"
                                type="search"
                                placeholder="Search allotted residents..."
                                class="w-full rounded-lg border-gray-300 pl-9 text-sm"
                            />
                        </div>

                        <div class="max-h-[520px] space-y-2 overflow-y-auto">
                            <div
                                v-for="stay in filteredAwaitingCheckIn"
                                :key="stay.id"
                                class="rounded-xl border border-amber-100 bg-amber-50/40 p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <img
                                        v-if="stay.resident?.photo_url"
                                        :src="`/storage/${stay.resident.photo_url}`"
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-500"
                                    >
                                        {{
                                            stay.resident?.first_name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900"
                                        >
                                            {{ stay.resident?.first_name }}
                                            {{ stay.resident?.last_name }}
                                        </p>

                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-500"
                                        >
                                            {{ stay.resident?.resident_code }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-3 text-xs"
                                >
                                    <div>
                                        <p class="text-gray-400">Room</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.room?.room_number || "—" }}
                                            · Bed
                                            {{ stay.bed?.bed_number || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">
                                            Planned Arrival
                                        </p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ formatDate(stay.check_in_date) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">Building</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.building?.name || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">Billing</p>
                                        <p
                                            class="mt-0.5 font-medium capitalize text-gray-800"
                                        >
                                            {{
                                                stay.billing_basis || "monthly"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                    @click="openActualCheckin(stay)"
                                >
                                    <UserRoundCheck class="h-3.5 w-3.5" />
                                    Confirm Actual Check-In
                                </button>
                            </div>

                            <div
                                v-if="!filteredAwaitingCheckIn.length"
                                class="rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center"
                            >
                                <CheckCircle2
                                    class="mx-auto h-8 w-8 text-green-500"
                                />

                                <p
                                    class="mt-2 text-sm font-medium text-gray-700"
                                >
                                    No pending check-ins
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Checked-in stays -->
                <section
                    class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                >
                    <div
                        class="border-b border-green-100 bg-green-50 px-5 py-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2
                                    class="flex items-center gap-2 text-sm font-semibold text-green-900"
                                >
                                    <LogIn class="h-4 w-4 text-green-600" />
                                    Currently Checked In
                                </h2>

                                <p class="mt-1 text-xs text-green-700">
                                    Residents whose physical arrival has been
                                    confirmed.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                            >
                                {{ filteredCheckedIn.length }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="relative mb-3">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <input
                                v-model="checkedInSearch"
                                type="search"
                                placeholder="Search checked-in residents..."
                                class="w-full rounded-lg border-gray-300 pl-9 text-sm"
                            />
                        </div>

                        <div class="max-h-[520px] space-y-2 overflow-y-auto">
                            <div
                                v-for="stay in filteredCheckedIn"
                                :key="stay.id"
                                class="rounded-xl border border-green-100 bg-green-50/30 p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <img
                                        v-if="stay.resident?.photo_url"
                                        :src="`/storage/${stay.resident.photo_url}`"
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-500"
                                    >
                                        {{
                                            stay.resident?.first_name
                                                ?.charAt(0)
                                                ?.toUpperCase()
                                        }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900"
                                        >
                                            {{ stay.resident?.first_name }}
                                            {{ stay.resident?.last_name }}
                                        </p>

                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-500"
                                        >
                                            {{ stay.resident?.resident_code }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-semibold text-green-700"
                                    >
                                        Checked In
                                    </span>
                                </div>

                                <div
                                    class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-3 text-xs"
                                >
                                    <div>
                                        <p class="text-gray-400">Room</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.room?.room_number || "—" }}
                                            · Bed
                                            {{ stay.bed?.bed_number || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">Checked In</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ formatDate(stay.check_in_date) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">
                                            Assigned Assets
                                        </p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{
                                                stay.inventory_assignments
                                                    ?.length || 0
                                            }}
                                            item type(s)
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-400">Billing</p>
                                        <p
                                            class="mt-0.5 font-medium capitalize text-gray-800"
                                        >
                                            {{
                                                stay.billing_basis || "monthly"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="stay.inventory_assignments?.length"
                                    class="mt-3 flex flex-wrap gap-1.5"
                                >
                                    <span
                                        v-for="assignment in stay.inventory_assignments"
                                        :key="assignment.id"
                                        class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700"
                                    >
                                        {{ assignment.inventory?.item_name }}
                                        × {{ assignment.quantity }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                    @click="openCheckout(stay)"
                                >
                                    <LogOut class="h-3.5 w-3.5" />
                                    Check Out
                                </button>
                            </div>

                            <div
                                v-if="!filteredCheckedIn.length"
                                class="rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center"
                            >
                                <DoorOpen
                                    class="mx-auto h-8 w-8 text-gray-300"
                                />

                                <p
                                    class="mt-2 text-sm font-medium text-gray-600"
                                >
                                    No residents currently checked in
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Room allotment modal -->
        <Modal :show="allotmentOpen" @close="closeRoomAllotment" maxWidth="2xl">
            <form
                v-if="allottingResident"
                @submit.prevent="submitRoomAllotment"
                class="flex max-h-[90vh] flex-col overflow-hidden"
            >
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Allot Room
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Reserve a room and bed for
                        <strong class="text-gray-700">
                            {{ allottingResident.first_name }}
                            {{ allottingResident.last_name }}
                        </strong>
                        . This does not confirm physical check-in.
                    </p>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="rounded-xl border border-blue-100 bg-blue-50 p-4"
                    >
                        <p class="text-sm font-semibold text-blue-900">
                            Room reservation only
                        </p>

                        <p class="mt-1 text-xs text-blue-700">
                            The resident will remain in check-in pending status
                            until actual arrival is confirmed and inventory is
                            issued.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                for="allot_building_id"
                                value="Building *"
                            />

                            <select
                                id="allot_building_id"
                                v-model="allotmentForm.building_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm"
                            >
                                <option value="" disabled>
                                    Select building
                                </option>

                                <option
                                    v-for="building in buildings"
                                    :key="building.id"
                                    :value="building.id"
                                >
                                    {{ building.name }}
                                </option>
                            </select>

                            <InputError
                                :message="allotmentForm.errors.building_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="allot_floor_id" value="Floor *" />

                            <select
                                id="allot_floor_id"
                                v-model="allotmentForm.floor_id"
                                required
                                :disabled="!allotmentForm.building_id"
                                class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
                            >
                                <option value="" disabled>Select floor</option>

                                <option
                                    v-for="floor in floorsForBuilding"
                                    :key="floor.id"
                                    :value="floor.id"
                                >
                                    {{ floor.name }}
                                </option>
                            </select>

                            <InputError
                                :message="allotmentForm.errors.floor_id"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="allot_room_id" value="Room *" />

                            <select
                                id="allot_room_id"
                                v-model="allotmentForm.room_id"
                                required
                                :disabled="!allotmentForm.floor_id"
                                class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
                            >
                                <option value="" disabled>Select room</option>

                                <option
                                    v-for="room in roomsForFloor"
                                    :key="room.id"
                                    :value="room.id"
                                    :disabled="
                                        room.occupied_beds >= room.capacity
                                    "
                                >
                                    {{ room.room_number }} ·
                                    {{ room.occupied_beds }}/{{ room.capacity }}
                                    occupied
                                </option>
                            </select>

                            <InputError
                                :message="allotmentForm.errors.room_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="allot_bed_id" value="Bed *" />

                            <select
                                id="allot_bed_id"
                                v-model="allotmentForm.bed_id"
                                required
                                :disabled="!allotmentForm.room_id"
                                class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
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

                            <InputError
                                :message="allotmentForm.errors.bed_id"
                            />
                        </div>
                    </div>

                    <div
                        class="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">
                                Stay & Billing Details
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Select the applicable billing method for this
                                stay.
                            </p>
                        </div>

                        <div>
                            <InputLabel value="Billing Basis *" />

                            <div
                                class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2"
                            >
                                <label
                                    class="flex min-h-[92px] cursor-pointer flex-col justify-center rounded-xl border p-4 transition"
                                    :class="
                                        allotmentForm.billing_basis ===
                                        'monthly'
                                            ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                                            : 'border-gray-200 bg-white hover:border-gray-300'
                                    "
                                >
                                    <input
                                        v-model="allotmentForm.billing_basis"
                                        type="radio"
                                        value="monthly"
                                        class="sr-only"
                                    />

                                    <div class="flex items-start gap-3">
                                        <CalendarDays
                                            class="mt-0.5 h-5 w-5 text-blue-600"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                Monthly Billing
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                Regular resident billed every
                                                month.
                                            </p>
                                        </div>
                                    </div>
                                </label>

                                <label
                                    class="flex min-h-[92px] cursor-pointer flex-col justify-center rounded-xl border p-4 transition"
                                    :class="
                                        allotmentForm.billing_basis === 'daily'
                                            ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500'
                                            : 'border-gray-200 bg-white hover:border-gray-300'
                                    "
                                >
                                    <input
                                        v-model="allotmentForm.billing_basis"
                                        type="radio"
                                        value="daily"
                                        class="sr-only"
                                    />

                                    <div class="flex items-start gap-3">
                                        <Clock3
                                            class="mt-0.5 h-5 w-5 text-emerald-600"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                Daily Short Stay
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                Resident charged for occupied
                                                days.
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <InputError
                                :message="allotmentForm.errors.billing_basis"
                            />
                        </div>

                        <div
                            class="grid grid-cols-1 items-start gap-4 md:grid-cols-2"
                        >
                            <div class="min-w-0">
                                <InputLabel
                                    for="allot_check_in_date"
                                    value="Planned Check-in Date *"
                                />

                                <TextInput
                                    id="allot_check_in_date"
                                    v-model="allotmentForm.check_in_date"
                                    type="date"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="
                                        allotmentForm.errors.check_in_date
                                    "
                                />
                            </div>

                            <div
                                v-if="allotmentForm.billing_basis === 'monthly'"
                                class="min-w-0"
                            >
                                <InputLabel
                                    for="allot_rent_amount"
                                    value="Monthly Rent (₹) *"
                                />

                                <TextInput
                                    id="allot_rent_amount"
                                    v-model="allotmentForm.rent_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="allotmentForm.errors.rent_amount"
                                />
                            </div>

                            <div v-else class="min-w-0">
                                <InputLabel
                                    for="allot_daily_rate"
                                    value="Daily Rate (₹) *"
                                />

                                <TextInput
                                    id="allot_daily_rate"
                                    v-model="allotmentForm.daily_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="allotmentForm.errors.daily_rate"
                                />
                            </div>
                        </div>

                        <div
                            v-if="allotmentForm.billing_basis === 'daily'"
                            class="grid grid-cols-1 items-stretch gap-4 md:grid-cols-2"
                        >
                            <div class="min-w-0">
                                <InputLabel
                                    for="allot_expected_checkout"
                                    value="Expected Check-out Date *"
                                />

                                <TextInput
                                    id="allot_expected_checkout"
                                    v-model="
                                        allotmentForm.expected_check_out_date
                                    "
                                    type="date"
                                    :min="allotmentForm.check_in_date"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="
                                        allotmentForm.errors
                                            .expected_check_out_date
                                    "
                                />
                            </div>

                            <div
                                class="flex min-h-[82px] min-w-0 flex-col justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                            >
                                <p class="text-xs font-medium text-emerald-700">
                                    Estimated accommodation charge
                                </p>

                                <p
                                    class="mt-1 text-xl font-bold text-emerald-900"
                                >
                                    {{ formatCurrency(estimatedDailyAmount) }}
                                </p>

                                <p class="mt-1 text-xs text-emerald-700">
                                    {{ estimatedStayDays }} day(s) ×
                                    {{
                                        formatCurrency(allotmentForm.daily_rate)
                                    }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                for="allot_deposit_amount"
                                value="Refundable Security Deposit (₹)"
                            />

                            <TextInput
                                id="allot_deposit_amount"
                                v-model="allotmentForm.deposit_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full"
                            />

                            <p class="mt-1 text-xs text-gray-500">
                                A one-time deposit invoice will be generated
                                only after actual check-in.
                            </p>

                            <InputError
                                :message="allotmentForm.errors.deposit_amount"
                            />
                        </div>

                        <div>
                            <InputLabel for="allot_notes" value="Notes" />

                            <textarea
                                id="allot_notes"
                                v-model="allotmentForm.notes"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"
                                placeholder="Optional room allotment notes"
                            ></textarea>

                            <InputError :message="allotmentForm.errors.notes" />
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                        :disabled="allotmentForm.processing"
                        @click="closeRoomAllotment"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        type="submit"
                        :disabled="allotmentForm.processing"
                    >
                        {{
                            allotmentForm.processing
                                ? "Allotting..."
                                : "Allot Room"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Actual check-in modal -->
        <Modal
            :show="actualCheckinOpen"
            @close="closeActualCheckin"
            maxWidth="2xl"
        >
            <form
                v-if="checkingInStay"
                @submit.prevent="submitActualCheckin"
                class="flex max-h-[90vh] flex-col overflow-hidden"
            >
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Confirm Actual Check-In
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ checkingInStay.resident?.first_name }}
                        {{ checkingInStay.resident?.last_name }}
                        · Room
                        {{ checkingInStay.room?.room_number }}
                        · Bed
                        {{ checkingInStay.bed?.bed_number }}
                    </p>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="rounded-xl border border-emerald-100 bg-emerald-50 p-4"
                    >
                        <p class="text-sm font-semibold text-emerald-900">
                            Final check-in confirmation
                        </p>

                        <p class="mt-1 text-xs text-emerald-700">
                            You may update the actual arrival date and select
                            any student assets being handed over.
                        </p>
                    </div>

                    <div>
                        <InputLabel
                            for="actual_check_in_date"
                            value="Actual Check-in Date *"
                        />

                        <TextInput
                            id="actual_check_in_date"
                            v-model="actualCheckinForm.check_in_date"
                            type="date"
                            required
                            class="w-full"
                        />

                        <InputError
                            :message="actualCheckinForm.errors.check_in_date"
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3
                                    class="flex items-center gap-2 text-sm font-semibold text-gray-900"
                                >
                                    <Boxes class="h-4 w-4 text-blue-600" />
                                    Assign Student Inventory
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Inventory assignment is optional. Select
                                    only items physically issued to the
                                    resident.
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600"
                            >
                                {{ studentInventory.length }} available item
                                type(s)
                            </span>
                        </div>

                        <InputError
                            :message="actualCheckinForm.errors.inventory"
                            class="mt-2"
                        />
                    </div>

                    <div
                        class="overflow-x-auto rounded-xl border border-gray-200"
                    >
                        <table class="min-w-[760px] w-full text-sm">
                            <thead
                                class="bg-gray-50 text-xs uppercase text-gray-500"
                            >
                                <tr>
                                    <th class="w-14 px-3 py-3 text-center">
                                        Issue
                                    </th>

                                    <th class="px-3 py-3 text-left">Item</th>

                                    <th class="px-3 py-3 text-left">
                                        Available
                                    </th>

                                    <th class="px-3 py-3 text-left">
                                        Quantity
                                    </th>

                                    <th class="px-3 py-3 text-left">
                                        Condition
                                    </th>

                                    <th class="px-3 py-3 text-left">Notes</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(
                                        assignment, index
                                    ) in actualCheckinForm.inventory"
                                    :key="assignment.inventory_id"
                                    :class="
                                        assignment.selected
                                            ? 'bg-blue-50/40'
                                            : 'bg-white'
                                    "
                                >
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            v-model="assignment.selected"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>

                                    <td
                                        class="px-3 py-3 font-medium text-gray-900"
                                    >
                                        {{
                                            getInventoryItem(
                                                assignment.inventory_id,
                                            )?.item_name
                                        }}
                                    </td>

                                    <td class="px-3 py-3 text-gray-500">
                                        {{
                                            getInventoryItem(
                                                assignment.inventory_id,
                                            )?.available
                                        }}
                                        {{
                                            getInventoryItem(
                                                assignment.inventory_id,
                                            )?.unit
                                        }}
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="assignment.quantity"
                                            type="number"
                                            min="1"
                                            :max="
                                                getInventoryItem(
                                                    assignment.inventory_id,
                                                )?.available
                                            "
                                            :disabled="!assignment.selected"
                                            class="w-24"
                                        />

                                        <InputError
                                            :message="
                                                actualCheckinForm.errors[
                                                    `inventory.${index}.quantity`
                                                ]
                                            "
                                        />
                                    </td>

                                    <td class="px-3 py-3">
                                        <select
                                            v-model="
                                                assignment.condition_at_issue
                                            "
                                            :disabled="!assignment.selected"
                                            class="w-28 rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
                                        >
                                            <option value="new">New</option>

                                            <option value="good">Good</option>

                                            <option value="fair">Fair</option>
                                        </select>
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="assignment.notes"
                                            type="text"
                                            :disabled="!assignment.selected"
                                            class="w-44"
                                            placeholder="Optional note"
                                        />
                                    </td>
                                </tr>

                                <tr v-if="!actualCheckinForm.inventory.length">
                                    <td
                                        colspan="6"
                                        class="px-4 py-10 text-center text-gray-400"
                                    >
                                        No student-category inventory is
                                        currently available. Check-in can still
                                        be completed without assigning assets.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                        :disabled="actualCheckinForm.processing"
                        @click="closeActualCheckin"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        type="submit"
                        :disabled="actualCheckinForm.processing"
                    >
                        {{
                            actualCheckinForm.processing
                                ? "Checking In..."
                                : "Confirm Check-In"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Checkout modal -->
        <Modal :show="checkoutOpen" @close="closeCheckout" maxWidth="2xl">
            <form
                v-if="checkingOutStay"
                class="flex max-h-[92vh] flex-col overflow-hidden"
                @submit.prevent
            >
                <!-- Header -->
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Checkout Review
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ checkingOutStay.resident?.first_name }}
                        {{ checkingOutStay.resident?.last_name }}
                        · Room {{ checkingOutStay.room?.room_number }} · Bed
                        {{ checkingOutStay.bed?.bed_number }}
                    </p>
                </div>

                <!-- Body -->
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Actual Check-out Date *" />

                            <TextInput
                                v-model="checkoutForm.actual_check_out_date"
                                type="date"
                                :min="checkingOutStay.check_in_date"
                                required
                                class="w-full"
                            />

                            <InputError
                                :message="
                                    checkoutForm.errors.actual_check_out_date
                                "
                            />
                        </div>

                        <div
                            class="rounded-xl border p-4"
                            :class="
                                allAssetsReviewed
                                    ? 'border-green-200 bg-green-50'
                                    : 'border-amber-200 bg-amber-50'
                            "
                        >
                            <p
                                class="text-sm font-semibold"
                                :class="
                                    allAssetsReviewed
                                        ? 'text-green-900'
                                        : 'text-amber-900'
                                "
                            >
                                {{
                                    allAssetsReviewed
                                        ? "All assets reviewed"
                                        : "Asset review incomplete"
                                }}
                            </p>

                            <p
                                class="mt-1 text-xs"
                                :class="
                                    allAssetsReviewed
                                        ? 'text-green-700'
                                        : 'text-amber-700'
                                "
                            >
                                Every issued quantity must be marked as
                                returned, damaged, or missing before checkout
                                approval.
                            </p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Assigned Inventory
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Review every item before approving checkout.
                        </p>
                    </div>

                    <div
                        v-if="checkoutForm.inventory_returns.length"
                        class="overflow-x-auto rounded-xl border border-gray-200"
                    >
                        <table class="w-full min-w-[900px] text-sm">
                            <thead
                                class="bg-gray-50 text-xs uppercase text-gray-500"
                            >
                                <tr>
                                    <th class="px-3 py-3 text-left">Item</th>
                                    <th class="px-3 py-3 text-center">
                                        Assigned
                                    </th>
                                    <th class="px-3 py-3 text-center">
                                        Returned Good
                                    </th>
                                    <th class="px-3 py-3 text-center">
                                        Damaged
                                    </th>
                                    <th class="px-3 py-3 text-center">
                                        Missing
                                    </th>
                                    <th class="px-3 py-3 text-center">
                                        Reviewed
                                    </th>
                                    <th class="px-3 py-3 text-left">Notes</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(
                                        item, index
                                    ) in checkoutForm.inventory_returns"
                                    :key="item.assignment_id"
                                >
                                    <td
                                        class="px-3 py-3 font-medium text-gray-900"
                                    >
                                        {{ item.item_name }}
                                    </td>

                                    <td
                                        class="px-3 py-3 text-center font-semibold"
                                    >
                                        {{ item.assigned_quantity }}
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="
                                                item.returned_good_quantity
                                            "
                                            type="number"
                                            min="0"
                                            :max="item.assigned_quantity"
                                            class="mx-auto w-20"
                                        />
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="
                                                item.returned_damaged_quantity
                                            "
                                            type="number"
                                            min="0"
                                            :max="item.assigned_quantity"
                                            class="mx-auto w-20"
                                        />
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="item.missing_quantity"
                                            type="number"
                                            min="0"
                                            :max="item.assigned_quantity"
                                            class="mx-auto w-20"
                                        />
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                            :class="
                                                reviewedQuantity(item) ===
                                                item.assigned_quantity
                                                    ? 'bg-green-100 text-green-700'
                                                    : reviewedQuantity(item) >
                                                        item.assigned_quantity
                                                      ? 'bg-red-100 text-red-700'
                                                      : 'bg-amber-100 text-amber-700'
                                            "
                                        >
                                            {{ reviewedQuantity(item) }}/{{
                                                item.assigned_quantity
                                            }}
                                        </span>

                                        <InputError
                                            :message="
                                                checkoutForm.errors[
                                                    `inventory_returns.${index}`
                                                ]
                                            "
                                        />
                                    </td>

                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="item.return_notes"
                                            class="w-52"
                                            placeholder="Condition or damage notes"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500"
                    >
                        No inventory was assigned to this resident.
                    </div>

                    <div>
                        <InputLabel value="Checkout Review Notes" />

                        <textarea
                            v-model="checkoutForm.checkout_notes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            placeholder="Add checkout, damage, missing-item, or approval notes"
                        ></textarea>

                        <InputError
                            :message="checkoutForm.errors.checkout_notes"
                        />
                    </div>

                    <div
                        v-if="checkoutForm.errors.decision"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                    >
                        {{ checkoutForm.errors.decision }}
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 flex-col gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="checkoutForm.processing"
                        @click="closeCheckout"
                    >
                        Cancel
                    </button>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
                            :disabled="checkoutForm.processing"
                            @click="submitCheckoutDecision('hold')"
                        >
                            Hold Checkout
                        </button>

                        <button
                            type="button"
                            class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                            :disabled="checkoutForm.processing"
                            @click="submitCheckoutDecision('rejected')"
                        >
                            Reject Checkout
                        </button>

                        <PrimaryButton
                            type="button"
                            :disabled="
                                checkoutForm.processing || !allAssetsReviewed
                            "
                            @click="submitCheckoutDecision('approved')"
                        >
                            {{
                                checkoutForm.processing
                                    ? "Processing..."
                                    : "Approve Checkout"
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

import {
    AlertTriangle,
    BedDouble,
    Boxes,
    CalendarDays,
    CheckCircle2,
    Clock3,
    DoorOpen,
    LogIn,
    LogOut,
    Search,
    UserRoundCheck,
    UserRoundPlus,
    X,
} from "lucide-vue-next";

const props = defineProps({
    awaitingCheckIn: { type: Array, default: () => [] },
    checkedInStays: { type: Array, default: () => [] },
    studentInventory: { type: Array, default: () => [] },
    unassignedResidents: { type: Array, default: () => [] },
    buildings: { type: Array, default: () => [] },
    floors: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    checkoutPolicy: { type: Object, required: true },
});

const today = new Date().toISOString().slice(0, 10);

const unassignedSearch = ref("");
const awaitingSearch = ref("");
const checkedInSearch = ref("");

const normalizeText = (value) => String(value ?? "").toLowerCase();

const matchesStaySearch = (stay, search) => {
    if (!search) return true;
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
    if (!keyword) return props.unassignedResidents;

    return props.unassignedResidents.filter((resident) =>
        [
            resident.first_name,
            resident.last_name,
            resident.resident_code,
            resident.phone,
        ].some((value) => normalizeText(value).includes(keyword)),
    );
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

const floorsForBuilding = computed(() =>
    props.floors.filter(
        (floor) =>
            Number(floor.building_id) === Number(allotmentForm.building_id),
    ),
);

const roomsForFloor = computed(() =>
    props.rooms.filter(
        (room) => Number(room.floor_id) === Number(allotmentForm.floor_id),
    ),
);

const selectedRoom = computed(() =>
    props.rooms.find(
        (room) => Number(room.id) === Number(allotmentForm.room_id),
    ),
);

const vacantBeds = computed(
    () =>
        selectedRoom.value?.beds?.filter((bed) => bed.status === "vacant") ??
        [],
);

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

    return (
        Math.floor(
            (checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60 * 24),
        ) + 1
    );
});

const estimatedDailyAmount = computed(
    () => estimatedStayDays.value * Number(allotmentForm.daily_rate || 0),
);

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
    if (allotmentForm.processing) return;

    allotmentOpen.value = false;
    allottingResident.value = null;
    allotmentForm.reset();
    allotmentForm.clearErrors();
};

const submitRoomAllotment = () => {
    allotmentForm
        .transform((data) => ({
            ...data,
            rent_amount:
                data.billing_basis === "monthly" ? data.rent_amount : null,
            daily_rate: data.billing_basis === "daily" ? data.daily_rate : null,
            expected_check_out_date: data.expected_check_out_date || null,
            deposit_amount: data.deposit_amount || 0,
            notes: data.notes || null,
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

const actualCheckinOpen = ref(false);
const checkingInStay = ref(null);

const actualCheckinForm = useForm({
    check_in_date: today,
    inventory: [],
});

const getInventoryItem = (inventoryId) =>
    props.studentInventory.find(
        (item) => Number(item.id) === Number(inventoryId),
    );

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
    if (actualCheckinForm.processing) return;

    actualCheckinOpen.value = false;
    checkingInStay.value = null;
    actualCheckinForm.reset();
};

const submitActualCheckin = () => {
    if (!checkingInStay.value) return;

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

const checkoutRequestOpen = ref(false);
const selectedStay = ref(null);

const checkoutRequestForm = useForm({
    resident_id: "",
    resident_stay_id: "",
    requested_checkout_date: props.checkoutPolicy.minimum_recommended_date,
    reason: "",
    resident_notes: "",
    short_notice_warning_accepted: false,
});

const selectedCheckoutDate = computed(() => {
    if (!checkoutRequestForm.requested_checkout_date) return null;

    const value = new Date(
        `${checkoutRequestForm.requested_checkout_date}T00:00:00`,
    );

    return Number.isNaN(value.getTime()) ? null : value;
});

const minimumRecommendedCheckoutDate = computed(() => {
    const value = new Date(
        `${props.checkoutPolicy.minimum_recommended_date}T00:00:00`,
    );

    return Number.isNaN(value.getTime()) ? null : value;
});

const checkoutNoticeDays = computed(() => {
    if (!selectedCheckoutDate.value) return 0;

    const currentDate = new Date(`${props.checkoutPolicy.today}T00:00:00`);
    if (Number.isNaN(currentDate.getTime())) return 0;

    return Math.max(
        0,
        Math.floor(
            (selectedCheckoutDate.value.getTime() - currentDate.getTime()) /
                (1000 * 60 * 60 * 24),
        ),
    );
});

const checkoutIsShortNotice = computed(() => {
    if (!selectedCheckoutDate.value || !minimumRecommendedCheckoutDate.value) {
        return false;
    }

    return selectedCheckoutDate.value < minimumRecommendedCheckoutDate.value;
});

watch(
    () => checkoutRequestForm.requested_checkout_date,
    () => {
        checkoutRequestForm.clearErrors(
            "requested_checkout_date",
            "short_notice_warning_accepted",
        );

        if (!checkoutIsShortNotice.value) {
            checkoutRequestForm.short_notice_warning_accepted = false;
        }
    },
);

const openCreateCheckoutRequest = (stay) => {
    selectedStay.value = stay;

    checkoutRequestForm.reset();
    checkoutRequestForm.clearErrors();

    checkoutRequestForm.resident_id = stay.resident?.id || "";
    checkoutRequestForm.resident_stay_id = stay.id;
    checkoutRequestForm.requested_checkout_date =
        props.checkoutPolicy.minimum_recommended_date;
    checkoutRequestForm.reason = "";
    checkoutRequestForm.resident_notes = "";
    checkoutRequestForm.short_notice_warning_accepted = false;

    checkoutRequestOpen.value = true;
};

const closeCheckoutRequest = () => {
    if (checkoutRequestForm.processing) return;

    checkoutRequestOpen.value = false;
    selectedStay.value = null;
    checkoutRequestForm.reset();
    checkoutRequestForm.clearErrors();
};

const submitCheckoutRequest = () => {
    if (!selectedStay.value) return;

    checkoutRequestForm
        .transform((data) => ({
            resident_id: Number(data.resident_id),
            resident_stay_id: Number(data.resident_stay_id),
            requested_checkout_date: data.requested_checkout_date,
            reason: data.reason?.trim(),
            resident_notes: data.resident_notes?.trim() || null,
            short_notice_warning_accepted: Boolean(
                data.short_notice_warning_accepted,
            ),
        }))
        .post(route("checkout-requests.store"), {
            preserveScroll: true,
            onSuccess: () => {
                checkoutRequestOpen.value = false;
                selectedStay.value = null;
                checkoutRequestForm.reset();
            },
        });
};

const checkoutStatusLabel = (status) =>
    ({
        pending: "Pending",
        under_admin_review: "Under Admin Review",
        assigned_to_warden: "Assigned to Warden",
        warden_review_in_progress: "Warden Inspection",
        warden_approved: "Warden Approved",
        warden_rejected: "Warden Rejected",
        admin_approved: "Admin Approved",
        admin_rejected: "Admin Rejected",
        on_hold: "On Hold",
        ready_for_exit: "Ready for Exit",
        completed: "Completed",
        cancelled: "Cancelled",
        expired: "Expired",
    })[status] ||
    String(status || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());

const checkoutButtonLabel = (stay) => {
    const request = stay.checkout_request;

    if (!request) return "Create Checkout Request";

    return (
        {
            pending: "View Pending Request",
            under_admin_review: "Continue Admin Review",
            assigned_to_warden: "Awaiting Warden Review",
            warden_review_in_progress: "Inspection in Progress",
            warden_approved: "Final Admin Review",
            warden_rejected: "Warden Rejected",
            admin_approved: "Approved — Awaiting Exit",
            admin_rejected: "Request Rejected",
            on_hold: "View Held Request",
            ready_for_exit: "Ready for Exit",
            completed: "Checkout Completed",
            cancelled: "Request Cancelled",
            expired: "Request Expired",
        }[request.status] || "View Checkout Request"
    );
};

const checkoutButtonClasses = (stay) => {
    const status = stay.checkout_request?.status;

    if (!status) {
        return "bg-rose-600 text-white hover:bg-rose-700";
    }

    if (
        ["warden_approved", "admin_approved", "ready_for_exit"].includes(status)
    ) {
        return "bg-emerald-600 text-white hover:bg-emerald-700";
    }

    if (["admin_rejected", "warden_rejected"].includes(status)) {
        return "border border-red-200 bg-red-50 text-red-700 hover:bg-red-100";
    }

    if (status === "on_hold") {
        return "border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100";
    }

    if (["cancelled", "expired"].includes(status)) {
        return "border border-slate-200 bg-slate-50 text-slate-600";
    }

    return "border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100";
};

const handleCheckoutAction = (stay) => {
    if (!stay.checkout_request) {
        openCreateCheckoutRequest(stay);
        return;
    }

    router.visit(
        route("checkout-requests.index", {
            search: stay.resident?.resident_code,
        }),
    );
};

const formatCurrency = (amount) =>
    Number(amount || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatDate = (date) => {
    if (!date) return "—";

    return new Date(`${String(date).slice(0, 10)}T00:00:00`).toLocaleDateString(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
        },
    );
};

const residentPhotoUrl = (value) => {
    if (!value) return null;

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/storage/")
    ) {
        return value;
    }

    return `/storage/${value}`;
};
</script>

<template>
    <Head title="Check-In / Check-Out" />

    <AuthenticatedLayout>
        <template #header>Check-In / Check-Out</template>

        <div class="space-y-6">
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

                    <p class="mt-1 text-sm text-gray-700">
                        Reserve rooms, confirm physical check-in, assign student
                        assets, and manage checkout requests.
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
                    <a :href="route('checkout-requests.index')" class="border border-blue-600 text-blue-600 hover:bg-blue-100 focus:ring-blue-500 rounded p-2 flex items-center gap-2">
                        <LogOut class="h-4 w-4" />
                        Checkout Requests
                    </a>
                </div>
            </div>

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
                            Reserve a building, room and bed.
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
                            Confirm arrival and issue inventory.
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
                            3. Checkout Workflow
                        </p>
                        <p class="mt-0.5 text-xs text-blue-700">
                            Create or continue a checkout request.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
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
                                <p class="mt-1 text-xs text-gray-700">
                                    Residents without a current stay.
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
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
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
                                        v-if="
                                            residentPhotoUrl(resident.photo_url)
                                        "
                                        :src="
                                            residentPhotoUrl(resident.photo_url)
                                        "
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700"
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
                                            class="mt-0.5 truncate text-xs text-gray-700"
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
                                    Room reserved; arrival not confirmed.
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
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
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
                                        v-if="
                                            residentPhotoUrl(
                                                stay.resident?.photo_url,
                                            )
                                        "
                                        :src="
                                            residentPhotoUrl(
                                                stay.resident?.photo_url,
                                            )
                                        "
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-700"
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
                                            class="mt-0.5 truncate text-xs text-gray-700"
                                        >
                                            {{ stay.resident?.resident_code }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-3 text-xs"
                                >
                                    <div>
                                        <p class="text-gray-500">Room</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.room?.room_number || "—" }}
                                            · Bed
                                            {{ stay.bed?.bed_number || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">
                                            Planned Arrival
                                        </p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ formatDate(stay.check_in_date) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Building</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.building?.name || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Billing</p>
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
                                    Residents currently occupying rooms.
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
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
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
                                        v-if="
                                            residentPhotoUrl(
                                                stay.resident?.photo_url,
                                            )
                                        "
                                        :src="
                                            residentPhotoUrl(
                                                stay.resident?.photo_url,
                                            )
                                        "
                                        class="h-12 w-12 shrink-0 rounded-full border border-gray-200 object-cover"
                                        alt="Resident photo"
                                    />

                                    <div
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-700"
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
                                            class="mt-0.5 truncate text-xs text-gray-700"
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
                                        <p class="text-gray-500">Room</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ stay.room?.room_number || "—" }}
                                            · Bed
                                            {{ stay.bed?.bed_number || "—" }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Checked In</p>
                                        <p
                                            class="mt-0.5 font-medium text-gray-800"
                                        >
                                            {{ formatDate(stay.check_in_date) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">
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
                                        <p class="text-gray-500">Billing</p>
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

                                <div
                                    v-if="stay.checkout_request"
                                    class="mt-3 rounded-xl border border-indigo-100 bg-indigo-50 p-3"
                                >
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-semibold uppercase tracking-wide text-indigo-500"
                                            >
                                                Checkout Request
                                            </p>
                                            <p
                                                class="mt-1 text-xs font-bold text-indigo-900"
                                            >
                                                {{
                                                    checkoutStatusLabel(
                                                        stay.checkout_request
                                                            .status,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <span
                                            v-if="
                                                stay.checkout_request
                                                    .is_short_notice
                                            "
                                            class="rounded-full border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-bold text-red-700"
                                        >
                                            Short Notice
                                        </span>
                                    </div>

                                    <div
                                        class="mt-2 grid grid-cols-1 gap-2 text-[11px] sm:grid-cols-2"
                                    >
                                        <div>
                                            <p class="text-indigo-500">
                                                Planned Checkout
                                            </p>
                                            <p
                                                class="mt-0.5 font-semibold text-indigo-800"
                                            >
                                                {{
                                                    formatDate(
                                                        stay.checkout_request
                                                            .requested_checkout_date,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-indigo-500">
                                                Assigned Warden
                                            </p>
                                            <p
                                                class="mt-0.5 font-semibold text-indigo-800"
                                            >
                                                {{
                                                    stay.checkout_request
                                                        .assigned_warden
                                                        ?.name || "Not assigned"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition"
                                    :class="checkoutButtonClasses(stay)"
                                    @click="handleCheckoutAction(stay)"
                                >
                                    <LogOut class="h-4 w-4" />
                                    {{ checkoutButtonLabel(stay) }}
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

        <Modal :show="allotmentOpen" @close="closeRoomAllotment" maxWidth="2xl">
            <form
                v-if="allottingResident"
                class="flex max-h-[90vh] flex-col overflow-hidden"
                @submit.prevent="submitRoomAllotment"
            >
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Allot Room
                            </h2>
                            <p class="mt-1 text-sm text-gray-700">
                                Reserve a room and bed for
                                <strong>
                                    {{ allottingResident.first_name }}
                                    {{ allottingResident.last_name }} </strong
                                >. This does not confirm physical check-in.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                            @click="closeRoomAllotment"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="rounded-xl border border-blue-100 bg-blue-50 p-4"
                    >
                        <p class="text-sm font-semibold text-blue-900">
                            Room reservation only
                        </p>
                        <p class="mt-1 text-xs text-blue-700">
                            Actual arrival and inventory issue will be confirmed
                            separately.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Building *" />
                            <select
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
                            <InputLabel value="Floor *" />
                            <select
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

                        <div>
                            <InputLabel value="Room *" />
                            <select
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
                                        Number(room.occupied_beds) >=
                                        Number(room.capacity)
                                    "
                                >
                                    {{ room.room_number }} ·
                                    {{ room.occupied_beds }}/{{
                                        room.capacity
                                    }}
                                    occupied
                                </option>
                            </select>
                            <InputError
                                :message="allotmentForm.errors.room_id"
                            />
                        </div>

                        <div>
                            <InputLabel value="Bed *" />
                            <select
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

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Planned Check-In Date *" />
                            <TextInput
                                v-model="allotmentForm.check_in_date"
                                type="date"
                                required
                                class="w-full"
                            />
                            <InputError
                                :message="allotmentForm.errors.check_in_date"
                            />
                        </div>

                        <div>
                            <InputLabel value="Expected Checkout Date" />
                            <TextInput
                                v-model="allotmentForm.expected_check_out_date"
                                type="date"
                                :min="allotmentForm.check_in_date"
                                class="w-full"
                            />
                            <InputError
                                :message="
                                    allotmentForm.errors.expected_check_out_date
                                "
                            />
                        </div>
                    </div>

                    <div
                        class="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <div>
                            <InputLabel value="Billing Basis *" />

                            <div
                                class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2"
                            >
                                <label
                                    class="cursor-pointer rounded-xl border p-4"
                                    :class="
                                        allotmentForm.billing_basis ===
                                        'monthly'
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 bg-white'
                                    "
                                >
                                    <input
                                        v-model="allotmentForm.billing_basis"
                                        type="radio"
                                        value="monthly"
                                        class="sr-only"
                                    />
                                    <p class="text-sm font-semibold">
                                        Monthly Billing
                                    </p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        Use the room monthly rent per bed.
                                    </p>
                                </label>

                                <label
                                    class="cursor-pointer rounded-xl border p-4"
                                    :class="
                                        allotmentForm.billing_basis === 'daily'
                                            ? 'border-emerald-500 bg-emerald-50'
                                            : 'border-gray-200 bg-white'
                                    "
                                >
                                    <input
                                        v-model="allotmentForm.billing_basis"
                                        type="radio"
                                        value="daily"
                                        class="sr-only"
                                    />
                                    <p class="text-sm font-semibold">
                                        Daily Billing
                                    </p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        Charge by daily rate and stay duration.
                                    </p>
                                </label>
                            </div>
                        </div>

                        <div v-if="allotmentForm.billing_basis === 'monthly'">
                            <InputLabel value="Monthly Rent (₹) *" />
                            <TextInput
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

                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <InputLabel value="Daily Rate (₹) *" />
                                <TextInput
                                    v-model="allotmentForm.daily_rate"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="w-full"
                                />
                                <InputError
                                    :message="allotmentForm.errors.daily_rate"
                                />
                            </div>

                            <div
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
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
                                value="Refundable Security Deposit (₹)"
                            />
                            <TextInput
                                v-model="allotmentForm.deposit_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full"
                            />
                            <p class="mt-1 text-xs text-gray-600">
                                Deposit invoice will be generated after actual
                                check-in.
                            </p>
                            <InputError
                                :message="allotmentForm.errors.deposit_amount"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Notes" />
                        <textarea
                            v-model="allotmentForm.notes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        ></textarea>
                        <InputError :message="allotmentForm.errors.notes" />
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
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

        <Modal
            :show="actualCheckinOpen"
            @close="closeActualCheckin"
            maxWidth="4xl"
        >
            <form
                v-if="checkingInStay"
                class="flex max-h-[92vh] flex-col overflow-hidden"
                @submit.prevent="submitActualCheckin"
            >
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Confirm Actual Check-In
                            </h2>
                            <p class="mt-1 text-sm text-gray-700">
                                {{ checkingInStay.resident?.first_name }}
                                {{ checkingInStay.resident?.last_name }}
                                · Room {{ checkingInStay.room?.room_number }} ·
                                Bed {{ checkingInStay.bed?.bed_number }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                            @click="closeActualCheckin"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <InputLabel value="Actual Check-In Date *" />
                        <TextInput
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
                        <h3 class="text-sm font-semibold text-gray-900">
                            Student Inventory
                        </h3>
                        <p class="mt-1 text-xs text-gray-600">
                            Select items being issued during check-in.
                        </p>
                    </div>

                    <div
                        v-if="actualCheckinForm.inventory.length"
                        class="overflow-x-auto rounded-xl border border-gray-200"
                    >
                        <table class="w-full min-w-[850px] text-sm">
                            <thead
                                class="bg-gray-50 text-xs uppercase text-gray-600"
                            >
                                <tr>
                                    <th class="px-3 py-3 text-left">Issue</th>
                                    <th class="px-3 py-3 text-left">Item</th>
                                    <th class="px-3 py-3 text-center">
                                        Available
                                    </th>
                                    <th class="px-3 py-3 text-center">
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
                                >
                                    <td class="px-3 py-3">
                                        <input
                                            v-model="assignment.selected"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600"
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
                                    <td class="px-3 py-3 text-center">
                                        {{
                                            getInventoryItem(
                                                assignment.inventory_id,
                                            )?.available ?? 0
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
                                                )?.available || 1
                                            "
                                            :disabled="!assignment.selected"
                                            class="mx-auto w-24"
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
                                            class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100"
                                        >
                                            <option value="new">New</option>
                                            <option value="good">Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="damaged">
                                                Damaged
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-3">
                                        <TextInput
                                            v-model="assignment.notes"
                                            :disabled="!assignment.selected"
                                            class="w-56"
                                            placeholder="Optional issue notes"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-600"
                    >
                        No student inventory is currently available. Check-in
                        can still be completed without assigning assets.
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
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

        <Modal
            :show="checkoutRequestOpen"
            maxWidth="lg"
            @close="closeCheckoutRequest"
        >
            <form
                v-if="selectedStay"
                class="flex max-h-[92vh] flex-col overflow-hidden"
                @submit.prevent="submitCheckoutRequest"
            >
                <div class="shrink-0 border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Create Checkout Request
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Create a request on behalf of
                                <strong>
                                    {{ selectedStay.resident?.first_name }}
                                    {{
                                        selectedStay.resident?.last_name
                                    }} </strong
                                >.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                            :disabled="checkoutRequestForm.processing"
                            @click="closeCheckoutRequest"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4"
                    >
                        <p class="text-sm font-bold text-rose-900">
                            {{ selectedStay.resident?.name }}
                        </p>
                        <p class="mt-1 text-xs text-rose-700">
                            {{ selectedStay.building?.name || "—" }} · Room
                            {{ selectedStay.room?.room_number || "—" }} · Bed
                            {{ selectedStay.bed?.bed_number || "—" }}
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Requested Checkout Date *" />
                        <TextInput
                            v-model="
                                checkoutRequestForm.requested_checkout_date
                            "
                            type="date"
                            :min="checkoutPolicy.today"
                            required
                            class="w-full"
                        />
                        <InputError
                            :message="
                                checkoutRequestForm.errors
                                    .requested_checkout_date
                            "
                        />

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div
                                class="rounded-xl border border-slate-200 bg-slate-50 p-3"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    Notice Provided
                                </p>
                                <p
                                    class="mt-1 text-sm font-bold text-slate-900"
                                >
                                    {{ checkoutNoticeDays }} days
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-blue-200 bg-blue-50 p-3"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-wide text-blue-500"
                                >
                                    Required Notice
                                </p>
                                <p class="mt-1 text-sm font-bold text-blue-900">
                                    {{
                                        checkoutPolicy.required_notice_days
                                    }}
                                    days
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="checkoutIsShortNotice"
                        class="rounded-2xl border border-red-300 bg-red-50 p-5"
                    >
                        <div class="flex items-start gap-3">
                            <AlertTriangle
                                class="mt-0.5 h-6 w-6 shrink-0 text-red-700"
                            />
                            <div>
                                <p class="text-sm font-bold text-red-900">
                                    Short-notice checkout
                                </p>
                                <p class="mt-1 text-xs leading-5 text-red-700">
                                    {{ checkoutPolicy.short_notice_message }}
                                </p>
                            </div>
                        </div>

                        <label
                            class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-red-200 bg-white p-4"
                        >
                            <input
                                v-model="
                                    checkoutRequestForm.short_notice_warning_accepted
                                "
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-red-600"
                            />
                            <span
                                class="text-xs font-semibold leading-5 text-red-800"
                            >
                                I confirm that the short-notice policy and
                                possible charges have been explained and
                                accepted.
                            </span>
                        </label>

                        <InputError
                            class="mt-2"
                            :message="
                                checkoutRequestForm.errors
                                    .short_notice_warning_accepted
                            "
                        />
                    </div>

                    <div>
                        <InputLabel value="Reason for Checkout *" />
                        <textarea
                            v-model="checkoutRequestForm.reason"
                            rows="4"
                            required
                            maxlength="3000"
                            class="w-full rounded-xl border-slate-300 text-sm"
                            placeholder="Enter the reason for leaving the hostel"
                        ></textarea>
                        <InputError
                            :message="checkoutRequestForm.errors.reason"
                        />
                    </div>

                    <div>
                        <InputLabel value="Additional Notes" />
                        <textarea
                            v-model="checkoutRequestForm.resident_notes"
                            rows="3"
                            maxlength="3000"
                            class="w-full rounded-xl border-slate-300 text-sm"
                            placeholder="Optional notes"
                        ></textarea>
                        <InputError
                            :message="checkoutRequestForm.errors.resident_notes"
                        />
                    </div>

                    <div
                        class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <Clock3
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                        />
                        <p class="text-xs leading-5 text-amber-700">
                            Creating this request does not release the room, bed
                            or assigned inventory. Checkout completes only after
                            all reviews and approvals.
                        </p>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
                        :disabled="checkoutRequestForm.processing"
                        @click="closeCheckoutRequest"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        type="submit"
                        :disabled="
                            checkoutRequestForm.processing ||
                            (checkoutIsShortNotice &&
                                !checkoutRequestForm.short_notice_warning_accepted)
                        "
                    >
                        {{
                            checkoutRequestForm.processing
                                ? "Creating..."
                                : "Create Checkout Request"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
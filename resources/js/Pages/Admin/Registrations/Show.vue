<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import {
    ArrowLeft,
    User,
    Phone,
    GraduationCap,
    Building2,
    IndianRupee,
    Banknote,
    CreditCard,
    CheckCircle,
    XCircle,
    Snowflake,
    Wifi,
    Bath,
    Sun,
    Table2,
    DoorOpen,
    Calendar,
    Clock3,
} from "lucide-vue-next";

const props = defineProps({
    application: Object,
    buildings: Array,
    floors: Array,
    rooms: Array,
    registrationFeePaymentId: [Number, String],
});

const formatDate = (date, opts = {}) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        ...opts,
    });
};
const formatCurrency = (amount) =>
    "₹" +
    Number(amount || 0).toLocaleString("en-IN", { minimumFractionDigits: 2 });

const amenityIcons = [
    { key: "has_ac", icon: Snowflake, label: "AC" },
    { key: "has_wifi", icon: Wifi, label: "WiFi" },
    { key: "has_attached_bath", icon: Bath, label: "Attached Bath" },
    { key: "has_balcony", icon: Sun, label: "Balcony" },
    { key: "has_study_table", icon: Table2, label: "Study Table" },
];

// --- Approve modal: pick building/floor/room/bed, see amenities + rent ---
const approveOpen = ref(false);
const approveForm = useForm({
    building_id: "",
    floor_id: "",
    room_id: "",
    bed_id: "",
    check_in_date:
        props.application.stay_duration_from?.slice(0, 10) ||
        new Date().toISOString().slice(0, 10),
    expected_check_out_date:
        props.application.stay_duration_to?.slice(0, 10) ||
        new Date().toISOString().slice(0, 10),
    billing_basis: "monthly",
    rent_amount: "",
    daily_rate: 350,
    deposit_amount: "",
    remarks: "",
});

const floorsForBuilding = computed(() =>
    props.floors.filter(
        (f) => f.building_id === Number(approveForm.building_id),
    ),
);
const roomsForFloor = computed(() =>
    props.rooms.filter((r) => r.floor_id === Number(approveForm.floor_id)),
);
const selectedRoom = computed(() =>
    props.rooms.find((r) => r.id === Number(approveForm.room_id)),
);
const vacantBeds = computed(
    () => selectedRoom.value?.beds?.filter((b) => b.status === "vacant") || [],
);

const onRoomChange = () => {
    approveForm.bed_id = "";
    if (selectedRoom.value)
        approveForm.rent_amount = selectedRoom.value.monthly_rent_per_bed;
};

const submitApprove = () => {
    approveForm.post(`/registrations/${props.application.id}/approve`, {
        onSuccess: () => (approveOpen.value = false),
    });
};

// --- Reject / Mark cash paid ---
const reject = () => {
    const remarks = prompt("Rejection reason (optional):");
    if (remarks === null) return;
    router.post(`/registrations/${props.application.id}/reject`, {
        remarks,
    });
};

const markCashPaid = () => {
    if (!confirm("Mark this cash payment as received?")) return;
    router.post(`/registrations/${props.application.id}/mark-cash-paid`);
};

const estimatedStayDays = computed(() => {
    if (
        approveForm.billing_basis !== "daily" ||
        !approveForm.check_in_date ||
        !approveForm.expected_check_out_date
    ) {
        return 0;
    }

    const checkIn = new Date(`${approveForm.check_in_date}T00:00:00`);

    const checkOut = new Date(
        `${approveForm.expected_check_out_date}T00:00:00`,
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
    return estimatedStayDays.value * Number(approveForm.daily_rate || 0);
});

watch(
    () => approveForm.billing_basis,
    (basis) => {
        if (basis === "monthly") {
            approveForm.daily_rate = 350;
            approveForm.expected_check_out_date = props.application.stay_duration_to?.slice(0, 10) || new Date().toISOString().slice(0, 10);
        } else {
            approveForm.rent_amount = "";
            approveForm.daily_rate = approveForm.daily_rate || 350;
        }

        approveForm.clearErrors(
            "rent_amount",
            "daily_rate",
            "expected_check_out_date",
        );
    },
);
</script>

<template>
    <Head :title="`Application #${application.application_no}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    href="/registrations"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                    ><ArrowLeft class="w-5 h-5"
                /></Link>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Application Details
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ application.application_no }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Status Banner -->
            <div
                class="rounded-xl p-4 flex items-center justify-between flex-wrap gap-3"
                :class="{
                    'bg-amber-50 border border-amber-200':
                        application.status === 'pending',
                    'bg-blue-50 border border-blue-200':
                        application.status === 'paid',
                    'bg-green-50 border border-green-200':
                        application.status === 'approved',
                    'bg-red-50 border border-red-200':
                        application.status === 'rejected',
                }"
            >
                <span
                    class="text-sm font-medium capitalize"
                    :class="{
                        'text-amber-800': application.status === 'pending',
                        'text-blue-800': application.status === 'paid',
                        'text-green-800': application.status === 'approved',
                        'text-red-800': application.status === 'rejected',
                    }"
                >
                    Status: {{ application.status }}
                    <span
                        v-if="application.status === 'rejected'"
                        class="font-normal"
                        >— can still be approved later</span
                    >
                </span>
                <div class="flex gap-2">
                    <button
                        v-if="
                            application.payment_method === 'cash' &&
                            application.payment_status ===
                                'pending_verification'
                        "
                        @click="markCashPaid"
                        class="px-3 py-1.5 bg-orange-500 text-white text-xs font-medium rounded-lg hover:bg-orange-600 flex items-center gap-1.5"
                    >
                        <Banknote class="w-3.5 h-3.5" /> Mark Cash Paid
                    </button>
                    <button
                        v-if="application.status !== 'approved'"
                        @click="approveOpen = true"
                        class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 flex items-center gap-1.5"
                    >
                        <CheckCircle class="w-3.5 h-3.5" /> Approve & Allot Room
                    </button>
                    <button
                        v-if="
                            application.status !== 'rejected' &&
                            application.status !== 'approved'
                        "
                        @click="reject"
                        class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>
            </div>

            <!-- Allotment summary once approved -->
            <div
                v-if="application.status === 'approved'"
                class="bg-green-50 border border-green-200 rounded-xl p-4"
            >
                <h3
                    class="text-sm font-semibold text-green-900 mb-2 flex items-center gap-2"
                >
                    <DoorOpen class="h-4 w-4" /> Room Allotted
                </h3>
                <p class="text-sm text-green-800">
                    {{ application.allotted_building?.name }} — Room
                    {{ application.allotted_room?.room_number }}, Bed
                    {{ application.allotted_bed?.bed_number }}
                </p>
                <Link
                    v-if="application.resident_id"
                    :href="`/residents?search=${encodeURIComponent(application.student_name.split(' ')[0])}`"
                    class="text-xs text-green-700 hover:underline mt-1 inline-block"
                >
                    View resident record →
                </Link>
                <a
                    v-if="registrationFeePaymentId"
                    :href="`/billing/payments/${registrationFeePaymentId}/receipt`"
                    target="_blank"
                    class="text-xs text-green-700 hover:underline mt-1 ml-4 inline-block"
                >
                    View payment receipt →
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Photos -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4"
                >
                    <h3 class="text-sm font-semibold text-gray-900">Photos</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Student</p>
                            <img
                                v-if="application.student_photo"
                                :src="`/storage/${application.student_photo}`"
                                class="w-full h-auto object-cover rounded-lg border"
                            />
                            <div
                                v-else
                                class="w-full h-40 rounded-lg border bg-gray-50 flex items-center justify-center text-gray-300"
                            >
                                <User class="h-8 w-8" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Father</p>
                                <img
                                    v-if="application.father_photo"
                                    :src="`/storage/${application.father_photo}`"
                                    class="w-full h-24 object-cover rounded-lg border"
                                />
                                <div
                                    v-else
                                    class="w-full h-40 rounded-lg border bg-gray-50 flex items-center justify-center text-gray-300"
                                >
                                    <User class="h-8 w-8" />
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Mother</p>
                                <img
                                    v-if="application.mother_photo"
                                    :src="`/storage/${application.mother_photo}`"
                                    class="w-full h-24 object-cover rounded-lg border"
                                />
                                <div
                                    v-else
                                    class="w-full h-40 rounded-lg border bg-gray-50 flex items-center justify-center text-gray-300"
                                >
                                    <User class="h-8 w-8" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <User class="w-4 h-4 text-blue-500" /> Personal
                            Information
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Name: </span>
                                <span class="text-gray-500">{{
                                    application.student_name
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Father: </span>
                                <span class="text-gray-500">{{
                                    application.father_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Mother: </span>
                                <span class="text-gray-500">{{
                                    application.mother_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">DOB: </span>
                                <span class="text-gray-500">{{
                                    formatDate(application.dob)
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Age: </span>
                                <span class="text-gray-500">{{
                                    application.age || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Blood Group: </span>
                                <span class="text-gray-500">{{
                                    application.blood_group || "-"
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="font-medium">Address: </span>
                                <span class="text-gray-500">{{
                                    application.permanent_address || "-"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <Phone class="w-4 h-4 text-blue-500" /> Contact
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Student: </span>
                                <span class="text-gray-500">{{
                                    application.student_mobile
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Father: </span>
                                <span class="text-gray-500">{{
                                    application.father_mobile || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Mother: </span>
                                <span class="text-gray-500">{{
                                    application.mother_mobile || "-"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <GraduationCap class="w-4 h-4 text-blue-500" />
                            Academic
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Institution: </span>
                                <span class="text-gray-500">{{
                                    application.institution_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Course: </span>
                                <span class="text-gray-500">{{
                                    application.course_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Duration: </span>
                                <span class="text-gray-500">{{
                                    application.course_duration || "-"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <Building2 class="w-4 h-4 text-blue-500" /> Hostel
                            Preference
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Stay Duration: </span>
                                <span class="text-gray-500"
                                    >{{
                                        formatDate(
                                            application.stay_duration_from,
                                        )
                                    }}
                                    →
                                    {{
                                        formatDate(application.stay_duration_to)
                                    }}</span
                                >
                            </div>
                            <div>
                                <span class="font-medium"
                                    >Room Preference:
                                </span>
                                <span class="text-gray-500">{{
                                    application.room_type?.replace("_", " ") ||
                                    "Any"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="application.vehicle_number"
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            Vehicle
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Type: </span>
                                <span class="font-medium capitalize">{{
                                    application.vehicle_type?.replace("_", " ")
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Number: </span>
                                <span class="text-gray-500">{{
                                    application.vehicle_number
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            Local Guardians
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-if="application.guardian1_name"
                                class="p-3 rounded-lg bg-gray-50"
                            >
                                <p class="font-medium text-sm">
                                    {{ application.guardian1_name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ application.guardian1_mobile }} •
                                    {{
                                        application.guardian1_occupation ||
                                        "N/A"
                                    }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ application.guardian1_address }}
                                </p>
                            </div>
                            <div
                                v-if="application.guardian2_name"
                                class="p-3 rounded-lg bg-gray-50"
                            >
                                <p class="font-medium text-sm">
                                    {{ application.guardian2_name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ application.guardian2_mobile }} •
                                    {{
                                        application.guardian2_occupation ||
                                        "N/A"
                                    }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ application.guardian2_address }}
                                </p>
                            </div>
                            <p
                                v-if="
                                    !application.guardian1_name &&
                                    !application.guardian2_name
                                "
                                class="text-sm text-gray-400"
                            >
                                No guardians listed
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <IndianRupee class="w-4 h-4 text-blue-500" />
                            Payment
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex">
                                <span class="font-medium me-5">Mode: </span>
                                <span
                                    class="text-gray-500 flex items-center gap-1"
                                >
                                    <CreditCard
                                        v-if="
                                            application.payment_method ===
                                            'razorpay'
                                        "
                                        class="w-3 h-3"
                                    />
                                    <Banknote v-else class="w-3 h-3" />
                                    {{ application.payment_method }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Amount: </span>
                                <span class="text-gray-500">{{
                                    formatCurrency(application.registration_fee)
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Status: </span>
                                <span class="text-gray-500 capitalize">{{
                                    application.payment_status?.replace(
                                        "_",
                                        " ",
                                    )
                                }}</span>
                            </div>
                            <div v-if="application.razorpay_payment_id">
                                <span class="font-medium">Razorpay ID: </span>
                                <span class="font-mono text-xs">{{
                                    application.razorpay_payment_id
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="application.admin_remarks"
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">
                            Admin Remarks
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ application.admin_remarks }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve & Allot Room modal -->
        <Modal :show="approveOpen" @close="approveOpen = false" maxWidth="lg">
            <form @submit.prevent="submitApprove" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Approve & Allot Room
                </h2>
                <p class="text-sm text-gray-500">
                    This creates a resident record for
                    <b>{{ application.student_name }}</b> and allots the bed you
                    pick below. Gender wasn't collected on the application form,
                    so it defaults to "Other" — edit it afterwards from the
                    Residents module.
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building *" />
                        <select
                            v-model="approveForm.building_id"
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
                            v-model="approveForm.floor_id"
                            required
                            :disabled="!approveForm.building_id"
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

                <div>
                    <InputLabel value="Room *" />
                    <select
                        v-model="approveForm.room_id"
                        @change="onRoomChange"
                        required
                        :disabled="!approveForm.floor_id"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="" disabled>Select room</option>
                        <option
                            v-for="r in roomsForFloor"
                            :key="r.id"
                            :value="r.id"
                            :disabled="r.occupied_beds >= r.capacity"
                        >
                            {{ r.room_number }} — {{ r.room_type }} ({{
                                r.occupied_beds
                            }}/{{ r.capacity }})
                        </option>
                    </select>
                </div>

                <!-- Amenities preview for the selected room -->
                <div v-if="selectedRoom" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="a in amenityIcons.filter(
                            (x) => selectedRoom[x.key],
                        )"
                        :key="a.key"
                        class="inline-flex items-center gap-1 text-[11px] bg-blue-50 text-blue-700 px-2 py-1 rounded-full"
                    >
                        <component :is="a.icon" class="h-3 w-3" /> {{ a.label }}
                    </span>
                    <span
                        v-if="!amenityIcons.some((x) => selectedRoom[x.key])"
                        class="text-xs text-gray-400"
                        >No listed amenities for this room</span
                    >
                </div>

                <div>
                    <InputLabel value="Bed *" />
                    <select
                        v-model="approveForm.bed_id"
                        required
                        :disabled="!approveForm.room_id"
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

                <div
                    class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Stay & Billing Details
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Choose monthly billing for regular residents or
                            daily billing for short stays.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Billing Basis *" />

                        <div class="mt-2 grid grid-cols-2 gap-3">
                            <label
                                class="cursor-pointer rounded-xl border p-3 transition"
                                :class="
                                    approveForm.billing_basis === 'monthly'
                                        ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500'
                                        : 'border-gray-200 bg-white hover:border-gray-300'
                                "
                            >
                                <input
                                    v-model="approveForm.billing_basis"
                                    type="radio"
                                    value="monthly"
                                    class="sr-only"
                                />

                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg"
                                        :class="
                                            approveForm.billing_basis ===
                                            'monthly'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        <Calendar class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Monthly
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Regular resident billed month-wise.
                                        </p>
                                    </div>
                                </div>
                            </label>

                            <label
                                class="cursor-pointer rounded-xl border p-3 transition"
                                :class="
                                    approveForm.billing_basis === 'daily'
                                        ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500'
                                        : 'border-gray-200 bg-white hover:border-gray-300'
                                "
                            >
                                <input
                                    v-model="approveForm.billing_basis"
                                    type="radio"
                                    value="daily"
                                    class="sr-only"
                                />

                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg"
                                        :class="
                                            approveForm.billing_basis ===
                                            'daily'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        <Clock3 class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Daily Short Stay
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Charged per occupied day.
                                        </p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <InputError
                            :message="approveForm.errors.billing_basis"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                value="Check-in Date *"
                                for="approve_check_in_date"
                            />

                            <TextInput
                                id="approve_check_in_date"
                                v-model="approveForm.check_in_date"
                                type="date"
                                required
                                class="w-full"
                            />

                            <InputError
                                :message="approveForm.errors.check_in_date"
                            />
                        </div>

                        <div v-if="approveForm.billing_basis === 'monthly'">
                            <InputLabel
                                value="Monthly Rent (₹) *"
                                for="approve_rent_amount"
                            />

                            <TextInput
                                id="approve_rent_amount"
                                v-model="approveForm.rent_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                class="w-full"
                                placeholder="Enter monthly rent"
                            />

                            <InputError
                                :message="approveForm.errors.rent_amount"
                            />
                        </div>

                        <div v-else>
                            <InputLabel
                                value="Daily Rate (₹) *"
                                for="approve_daily_rate"
                            />

                            <TextInput
                                id="approve_daily_rate"
                                v-model="approveForm.daily_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                class="w-full"
                                placeholder="350"
                            />

                            <InputError
                                :message="approveForm.errors.daily_rate"
                            />
                        </div>
                    </div>

                    <div
                        v-if="approveForm.billing_basis === 'daily'"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <InputLabel
                                value="Expected Check-out Date *"
                                for="approve_expected_check_out_date"
                            />

                            <TextInput
                                id="approve_expected_check_out_date"
                                v-model="approveForm.expected_check_out_date"
                                type="date"
                                :min="approveForm.check_in_date"
                                required
                                class="w-full"
                            />

                            <InputError
                                :message="
                                    approveForm.errors.expected_check_out_date
                                "
                            />
                        </div>

                        <div
                            class="rounded-lg border border-emerald-200 bg-emerald-50 p-3"
                        >
                            <p class="text-xs font-medium text-emerald-700">
                                Estimated short-stay amount
                            </p>

                            <p class="mt-1 text-lg font-bold text-emerald-900">
                                ₹{{
                                    estimatedDailyAmount.toLocaleString("en-IN")
                                }}
                            </p>

                            <p class="mt-0.5 text-xs text-emerald-700">
                                {{ estimatedStayDays }} day(s) × ₹{{
                                    Number(
                                        approveForm.daily_rate || 0,
                                    ).toLocaleString("en-IN")
                                }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            value="Refundable Security Deposit (₹)"
                            for="approve_deposit_amount"
                        />

                        <TextInput
                            id="approve_deposit_amount"
                            v-model="approveForm.deposit_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full"
                        />

                        <p class="mt-1 text-xs text-gray-400">
                            One-time refundable deposit for this stay. It will not be included in
                            monthly billing.
                        </p>

                        <InputError
                            :message="approveForm.errors.deposit_amount"
                        />
                    </div>
                </div>
                <div>
                    <InputLabel value="Remarks" /><textarea
                        v-model="approveForm.remarks"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="approveOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="approveForm.processing">{{
                        approveForm.processing
                            ? "Approving..."
                            : "Approve & Create Resident"
                    }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

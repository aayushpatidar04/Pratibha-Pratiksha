<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
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
    Eye,
    FileText,
    Download,
    Upload,
    QrCode,
    X,
    AlertTriangle,
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

const paymentProofOpen = ref(false);

const registrationPaymentProofUrl = computed(() => {
    if (!props.application.registration_payment_proof) {
        return null;
    }

    const path = String(props.application.registration_payment_proof);

    /*
     * Supports either:
     * registration-payment-proofs/file.jpg
     *
     * or:
     * /storage/registration-payment-proofs/file.jpg
     */
    if (
        path.startsWith("http://") ||
        path.startsWith("https://") ||
        path.startsWith("/storage/")
    ) {
        return path;
    }

    return `/storage/${path.replace(/^\/+/, "")}`;
});

const registrationPaymentProofExtension = computed(() => {
    const path = props.application.registration_payment_proof || "";

    return String(path).split("?")[0].split(".").pop()?.toLowerCase();
});

const registrationPaymentProofIsPdf = computed(() => {
    return registrationPaymentProofExtension.value === "pdf";
});

const registrationPaymentProofIsImage = computed(() => {
    return ["jpg", "jpeg", "png", "webp", "gif"].includes(
        registrationPaymentProofExtension.value,
    );
});

const openRegistrationPaymentProof = () => {
    if (!registrationPaymentProofUrl.value) {
        return;
    }

    if (registrationPaymentProofIsPdf.value) {
        window.open(
            registrationPaymentProofUrl.value,
            "_blank",
            "noopener,noreferrer",
        );

        return;
    }

    paymentProofOpen.value = true;
};
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

const manualPaymentOpen = ref(false);

const manualPaymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),

    notes: "",

    /*
     * Optional replacement/additional proof uploaded
     * by the administrator.
     */
    proof: null,
});

const openManualPayment = () => {
    /*
     * UPI must already have a proof uploaded by the
     * applicant before verification.
     */
    if (
        props.application.payment_method === "upi" &&
        !props.application.registration_payment_proof
    ) {
        alert(
            "UPI payment cannot be verified because no payment proof was uploaded.",
        );

        return;
    }

    manualPaymentForm.reset();
    manualPaymentForm.clearErrors();

    manualPaymentForm.payment_date = new Date().toISOString().slice(0, 10);

    manualPaymentOpen.value = true;
};

const onManualProofChange = (event) => {
    manualPaymentForm.proof = event.target.files?.[0] || null;
};

const submitManualPayment = () => {
    manualPaymentForm.post(
        `/registrations/${props.application.id}/mark-cash-paid`,
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                manualPaymentOpen.value = false;
                manualPaymentForm.reset();
            },
        },
    );
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
            approveForm.expected_check_out_date =
                props.application.stay_duration_to?.slice(0, 10) ||
                new Date().toISOString().slice(0, 10);
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
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-700"
                    ><ArrowLeft class="w-5 h-5"
                /></Link>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Application Details
                    </h2>
                    <p class="text-sm text-gray-700">
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
                    <a
                        v-if="registrationFeePaymentId"
                        :href="`/billing/payments/${registrationFeePaymentId}/receipt`"
                        target="_blank"
                        class="text-xs text-green-700 hover:underline mt-1 ml-4 inline-block"
                    >
                        View payment receipt →
                    </a>
                </span>
                <div class="flex gap-2">
                    <button
                        v-if="
                            ['cash', 'upi'].includes(
                                application.payment_method,
                            ) &&
                            application.payment_status ===
                                'pending_verification'
                        "
                        type="button"
                        :disabled="
                            application.payment_method === 'upi' &&
                            !application.registration_payment_proof
                        "
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-white transition disabled:cursor-not-allowed disabled:bg-gray-400"
                        :class="
                            application.payment_method === 'upi'
                                ? 'bg-purple-600 hover:bg-purple-700'
                                : 'bg-orange-500 hover:bg-orange-600'
                        "
                        @click="openManualPayment"
                    >
                        <QrCode
                            v-if="application.payment_method === 'upi'"
                            class="h-3.5 w-3.5"
                        />

                        <Banknote v-else class="h-3.5 w-3.5" />

                        {{
                            application.payment_method === "upi"
                                ? "Verify UPI Payment"
                                : "Mark Cash Paid"
                        }}
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
                            <p class="text-xs text-gray-700 mb-1">Student</p>
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
                                <p class="text-xs text-gray-700 mb-1">Father</p>
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
                                <p class="text-xs text-gray-700 mb-1">Mother</p>
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
                                <span class="text-gray-700">{{
                                    application.student_name
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Father: </span>
                                <span class="text-gray-700">{{
                                    application.father_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Mother: </span>
                                <span class="text-gray-700">{{
                                    application.mother_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">DOB: </span>
                                <span class="text-gray-700">{{
                                    formatDate(application.dob)
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Age: </span>
                                <span class="text-gray-700">{{
                                    application.age || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Blood Group: </span>
                                <span class="text-gray-700">{{
                                    application.blood_group || "-"
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="font-medium">Address: </span>
                                <span class="text-gray-700">{{
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
                                <span class="text-gray-700">{{
                                    application.student_mobile
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Father: </span>
                                <span class="text-gray-700">{{
                                    application.father_mobile || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Mother: </span>
                                <span class="text-gray-700">{{
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
                                <span class="text-gray-700">{{
                                    application.institution_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Course: </span>
                                <span class="text-gray-700">{{
                                    application.course_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Duration: </span>
                                <span class="text-gray-700">{{
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
                                <span class="text-gray-700"
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
                                <span class="text-gray-700">{{
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
                                <span class="text-gray-700">{{
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
                                <p class="text-xs text-gray-700">
                                    {{ application.guardian1_mobile }} •
                                    {{
                                        application.guardian1_occupation ||
                                        "N/A"
                                    }}
                                </p>
                                <p class="text-xs text-gray-700">
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
                                <p class="text-xs text-gray-700">
                                    {{ application.guardian2_mobile }} •
                                    {{
                                        application.guardian2_occupation ||
                                        "N/A"
                                    }}
                                </p>
                                <p class="text-xs text-gray-700">
                                    {{ application.guardian2_address }}
                                </p>
                            </div>
                            <p
                                v-if="
                                    !application.guardian1_name &&
                                    !application.guardian2_name
                                "
                                class="text-sm text-gray-600"
                            >
                                No guardians listed
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <h3
                                class="flex items-center gap-2 text-sm font-semibold text-gray-900"
                            >
                                <IndianRupee class="h-4 w-4 text-blue-500" />
                                Payment
                            </h3>

                            <span
                                class="inline-flex w-fit rounded-full px-2.5 py-1 text-[10px] font-bold capitalize"
                                :class="
                                    application.payment_status === 'paid'
                                        ? 'bg-green-100 text-green-700'
                                        : application.payment_status ===
                                            'pending_verification'
                                          ? 'bg-amber-100 text-amber-700'
                                          : 'bg-gray-100 text-gray-700'
                                "
                            >
                                {{
                                    application.payment_status?.replaceAll(
                                        "_",
                                        " ",
                                    ) || "Pending"
                                }}
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <span class="font-medium"> Mode: </span>

                                <span
                                    class="ml-2 inline-flex items-center gap-1 capitalize text-gray-700"
                                >
                                    <CreditCard
                                        v-if="
                                            application.payment_method ===
                                            'razorpay'
                                        "
                                        class="h-3.5 w-3.5"
                                    />

                                    <QrCode
                                        v-else-if="
                                            application.payment_method === 'upi'
                                        "
                                        class="h-3.5 w-3.5 text-purple-600"
                                    />

                                    <Banknote
                                        v-else
                                        class="h-3.5 w-3.5 text-green-600"
                                    />

                                    {{ application.payment_method || "-" }}
                                </span>
                            </div>

                            <div>
                                <span class="font-medium"> Amount: </span>

                                <span class="ml-2 text-gray-700">
                                    {{
                                        formatCurrency(
                                            application.registration_fee,
                                        )
                                    }}
                                </span>
                            </div>

                            <div>
                                <span class="font-medium"> Status: </span>

                                <span class="ml-2 capitalize text-gray-700">
                                    {{
                                        application.payment_status?.replaceAll(
                                            "_",
                                            " ",
                                        ) || "-"
                                    }}
                                </span>
                            </div>

                            <div v-if="application.razorpay_payment_id">
                                <span class="font-medium"> Razorpay ID: </span>

                                <span class="ml-2 break-all font-mono text-xs">
                                    {{ application.razorpay_payment_id }}
                                </span>
                            </div>

                            <div v-if="application.paid_at">
                                <span class="font-medium"> Paid At: </span>

                                <span class="ml-2 text-gray-700">
                                    {{ formatDate(application.paid_at) }}
                                </span>
                            </div>
                        </div>

                        <!-- Applicant payment proof -->
                        <div
                            v-if="
                                ['cash', 'upi'].includes(
                                    application.payment_method,
                                )
                            "
                            class="mt-5 border-t border-gray-100 pt-5"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Registration Payment Proof
                                    </p>

                                    <p class="mt-1 text-xs text-gray-600">
                                        <template
                                            v-if="
                                                application.registration_payment_proof
                                            "
                                        >
                                            Uploaded by the applicant during
                                            registration.
                                        </template>

                                        <template v-else>
                                            No payment proof was uploaded during
                                            registration.
                                        </template>
                                    </p>
                                </div>

                                <div
                                    v-if="
                                        application.registration_payment_proof
                                    "
                                    class="flex gap-2"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                        @click="openRegistrationPaymentProof"
                                    >
                                        <Eye class="h-4 w-4" />
                                        View Proof
                                    </button>

                                    <a
                                        :href="registrationPaymentProofUrl"
                                        target="_blank"
                                        download
                                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        <Download class="h-4 w-4" />
                                        Download
                                    </a>
                                </div>
                            </div>

                            <!-- Image thumbnail -->
                            <button
                                v-if="
                                    registrationPaymentProofUrl &&
                                    registrationPaymentProofIsImage
                                "
                                type="button"
                                class="group mt-4 block overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
                                @click="paymentProofOpen = true"
                            >
                                <img
                                    :src="registrationPaymentProofUrl"
                                    alt="Registration payment proof"
                                    class="max-h-72 w-full object-contain transition group-hover:scale-[1.01]"
                                />
                            </button>

                            <!-- PDF card -->
                            <button
                                v-else-if="
                                    registrationPaymentProofUrl &&
                                    registrationPaymentProofIsPdf
                                "
                                type="button"
                                class="mt-4 flex w-full items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-left hover:bg-red-100"
                                @click="openRegistrationPaymentProof"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-white text-red-600"
                                    >
                                        <FileText class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-red-900"
                                        >
                                            Payment Proof PDF
                                        </p>

                                        <p class="mt-1 text-xs text-red-700">
                                            Click to open and verify the
                                            uploaded document.
                                        </p>
                                    </div>
                                </div>

                                <Eye class="h-5 w-5 text-red-600" />
                            </button>

                            <!-- Missing UPI proof -->
                            <div
                                v-else-if="application.payment_method === 'upi'"
                                class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
                            >
                                <AlertTriangle
                                    class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                                />

                                <div>
                                    <p
                                        class="text-sm font-semibold text-red-900"
                                    >
                                        UPI proof missing
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-5 text-red-700"
                                    >
                                        Do not mark this payment as received
                                        until payment proof or another valid
                                        payment record is available.
                                    </p>
                                </div>
                            </div>

                            <!-- Missing cash proof -->
                            <div
                                v-else
                                class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p class="text-xs leading-5 text-gray-600">
                                    Cash proof is optional. The administrator
                                    may upload a receipt while marking the cash
                                    payment as received.
                                </p>
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
                <p class="text-sm text-gray-700">
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
                        <p class="mt-0.5 text-xs text-gray-700">
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
                                                : 'bg-gray-100 text-gray-700'
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
                                        <p class="mt-0.5 text-xs text-gray-700">
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
                                                : 'bg-gray-100 text-gray-700'
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
                                        <p class="mt-0.5 text-xs text-gray-700">
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

                        <p class="mt-1 text-xs text-gray-600">
                            One-time refundable deposit for this stay. It will
                            not be included in monthly billing.
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

        <Modal :show="manualPaymentOpen" @close="manualPaymentOpen = false">
            <form class="space-y-5 p-6" @submit.prevent="submitManualPayment">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{
                            application.payment_method === "upi"
                                ? "Verify UPI Payment"
                                : "Mark Cash Payment Received"
                        }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        <template v-if="application.payment_method === 'upi'">
                            Review the applicant's uploaded payment proof before
                            confirming payment.
                        </template>

                        <template v-else>
                            Confirm that the cash registration fee has been
                            received.
                        </template>
                    </p>
                </div>

                <!-- Existing proof preview -->
                <div
                    v-if="application.registration_payment_proof"
                    class="rounded-xl border border-blue-200 bg-blue-50 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-blue-900">
                                Applicant Payment Proof
                            </p>

                            <p class="mt-1 text-xs text-blue-700">
                                Review this proof before confirming the payment.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                            @click="openRegistrationPaymentProof"
                        >
                            <Eye class="h-4 w-4" />
                            View
                        </button>
                    </div>

                    <img
                        v-if="registrationPaymentProofIsImage"
                        :src="registrationPaymentProofUrl"
                        alt="Payment proof"
                        class="mt-4 max-h-64 w-full rounded-lg border bg-white object-contain"
                    />
                </div>

                <div>
                    <InputLabel value="Payment Date *" />

                    <TextInput
                        v-model="manualPaymentForm.payment_date"
                        type="date"
                        required
                        class="w-full"
                    />

                    <InputError
                        :message="manualPaymentForm.errors.payment_date"
                    />
                </div>

                <div>
                    <InputLabel
                        :value="
                            application.payment_method === 'cash'
                                ? 'Receipt / Proof (Optional)'
                                : 'Additional Proof (Optional)'
                        "
                    />

                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                        class="block w-full text-sm"
                        @change="onManualProofChange"
                    />

                    <p class="mt-1 text-xs text-gray-500">
                        Upload an additional admin-side receipt or payment proof
                        where required.
                    </p>

                    <InputError :message="manualPaymentForm.errors.proof" />
                </div>

                <div>
                    <InputLabel value="Notes" />

                    <textarea
                        v-model="manualPaymentForm.notes"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Verification reference, transaction details or receipt notes..."
                    ></textarea>

                    <InputError :message="manualPaymentForm.errors.notes" />
                </div>

                <div
                    class="flex justify-end gap-2 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm"
                        @click="manualPaymentOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="manualPaymentForm.processing">
                        {{
                            manualPaymentForm.processing
                                ? "Saving..."
                                : application.payment_method === "upi"
                                  ? "Verify & Mark Paid"
                                  : "Mark Paid"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal
            :show="paymentProofOpen"
            maxWidth="4xl"
            @close="paymentProofOpen = false"
        >
            <div class="p-6">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Registration Payment Proof
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            Uploaded by
                            {{ application.student_name }}
                            using
                            <span class="font-semibold uppercase">
                                {{ application.payment_method }}
                            </span>
                            payment.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                        @click="paymentProofOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
                >
                    <img
                        v-if="registrationPaymentProofIsImage"
                        :src="registrationPaymentProofUrl"
                        alt="Registration payment proof"
                        class="max-h-[70vh] w-full object-contain"
                    />

                    <div
                        v-else
                        class="flex flex-col items-center justify-center px-6 py-16 text-center"
                    >
                        <FileText class="h-12 w-12 text-red-500" />

                        <p class="mt-4 text-sm font-semibold text-gray-900">
                            This proof cannot be previewed as an image.
                        </p>

                        <a
                            :href="registrationPaymentProofUrl"
                            target="_blank"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            <Eye class="h-4 w-4" />
                            Open File
                        </a>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <a
                        :href="registrationPaymentProofUrl"
                        target="_blank"
                        download
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                    >
                        <Download class="h-4 w-4" />
                        Download
                    </a>

                    <button
                        type="button"
                        class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white"
                        @click="paymentProofOpen = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

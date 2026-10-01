<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import {
    ArrowRightLeft,
    Plus,
    Check,
    X,
    ArrowLeft,
    Eye,
} from "lucide-vue-next";

const props = defineProps({
    requests: Object,
    stats: Object,
    filters: Object,
    residents: Array,
    buildings: Array,
    floors: Array,
    rooms: Array,
});

const statusColor = {
    pending: "amber",
    approved: "green",
    rejected: "red",
    cancelled: "gray",
};

const filters = reactive({ status: props.filters?.status || "all" });
const applyFilters = () =>
    router.get(
        "/residents/room-change-requests",
        { status: filters.status !== "all" ? filters.status : undefined },
        { preserveState: true },
    );

// --- Create request ---
const createOpen = ref(false);
const createForm = useForm({
    resident_id: "",
    reason: "",
    requested_building_id: "",
    requested_floor_id: "",
    requested_room_id: "",
    requested_bed_id: "",
});
const floorsForBuilding = computed(() =>
    props.floors.filter(
        (f) => f.building_id === Number(createForm.requested_building_id),
    ),
);
const roomsForFloor = computed(() =>
    props.rooms.filter(
        (r) => r.floor_id === Number(createForm.requested_floor_id),
    ),
);
const selectedRoom = computed(() =>
    props.rooms.find((r) => r.id === Number(createForm.requested_room_id)),
);
const vacantBeds = computed(
    () => selectedRoom.value?.beds?.filter((b) => b.status === "vacant") || [],
);
const submitCreate = () =>
    createForm.post("/residents/room-change-requests", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

// --- View request ---
const viewOpen = ref(false);
const viewing = ref(null);

const openView = (request) => {
    viewing.value = request;
    viewOpen.value = true;
};

const closeView = () => {
    viewOpen.value = false;
    viewing.value = null;
};

const formatDate = (date) => {
    if (!date) return "—";

    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatDateTime = (date) => {
    if (!date) return "—";

    return new Date(date).toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatStatus = (status) => {
    if (!status) return "—";

    return status
        .replaceAll("_", " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const residentName = (resident) => {
    if (!resident) return "—";

    return (
        [resident.first_name, resident.last_name].filter(Boolean).join(" ") ||
        "—"
    );
};

// --- Approve / reject ---
const reviewOpen = ref(false);
const reviewing = ref(null);
const reviewAction = ref("approve");
const reviewForm = useForm({
    admin_notes: "",

    effective_from: new Date().toISOString().slice(0, 10),

    new_billing_basis: "monthly",
    new_rent_amount: "",
    new_daily_rate: 350,
    new_expected_check_out_date: "",
});

const openReview = (req, action) => {
    reviewing.value = req;
    reviewAction.value = action;

    reviewForm.reset();
    reviewForm.clearErrors();

    reviewForm.admin_notes = "";
    reviewForm.effective_from = new Date().toISOString().slice(0, 10);

    reviewForm.new_billing_basis = req.current_stay?.billing_basis || "monthly";

    reviewForm.new_rent_amount =
        req.requested_room?.monthly_rent_per_bed ??
        req.current_stay?.rent_amount ??
        "";

    reviewForm.new_daily_rate = req.current_stay?.daily_rate || 350;

    reviewForm.new_expected_check_out_date =
        req.current_stay?.expected_check_out_date || "";

    reviewOpen.value = true;
};

const submitReview = () => {
    if (!reviewing.value) return;

    const url =
        `/residents/room-change-requests/` +
        `${reviewing.value.id}/${reviewAction.value}`;

    reviewForm.put(url, {
        preserveScroll: true,

        onSuccess: () => {
            reviewOpen.value = false;
            reviewing.value = null;
            reviewForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Room Change Requests" />
    <AuthenticatedLayout>
        <template #header>Residents / Room Change Requests</template>

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <ArrowRightLeft class="h-6 w-6 text-blue-600" /> Room
                        Change Requests
                    </h1>
                    <p class="text-sm text-gray-700 mt-0.5">
                        Review and action resident requests to move rooms
                    </p>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton type="button" @click="createOpen = true"
                        ><Plus class="h-4 w-4" /> New Request</PrimaryButton
                    >
                    <Link
                        href="/residents"
                        class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Residents
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 max-w-lg">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.pending }}
                    </p>
                    <p class="text-xs text-gray-600">Pending</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ stats.approved }}
                    </p>
                    <p class="text-xs text-gray-600">Approved</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.rejected }}
                    </p>
                    <p class="text-xs text-gray-600">Rejected</p>
                </div>
            </div>

            <select
                v-model="filters.status"
                @change="applyFilters"
                class="rounded-lg border-gray-300 text-sm w-52"
            >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Current Room</th>
                            <th class="text-left px-4 py-3">Requested Room</th>
                            <th class="text-left px-4 py-3">Reason</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in requests.data" :key="r.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ r.resident?.first_name }}
                                {{ r.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <span v-if="r.current_stay"
                                    >{{ r.current_stay.building?.name }} -
                                    {{ r.current_stay.room?.room_number }}</span
                                >
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <span v-if="r.requested_room"
                                    >{{ r.requested_building?.name }} -
                                    {{ r.requested_room?.room_number }}
                                    <span v-if="r.requested_bed"
                                        >({{
                                            r.requested_bed.bed_number
                                        }})</span
                                    ></span
                                >
                                <span v-else class="text-gray-300"
                                    >Not specified</span
                                >
                            </td>
                            <td
                                class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate"
                            >
                                {{ r.reason || "—" }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[r.status]">{{
                                    r.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-right space-x-1">
                                <!-- View -->
                                <button
                                    type="button"
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-blue-50"
                                    title="View request"
                                    @click="openView(r)"
                                >
                                    <Eye class="h-3.5 w-3.5 text-blue-600" />
                                </button>
                                <template v-if="r.status === 'pending'">
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-green-50"
                                        @click="openReview(r, 'approve')"
                                    >
                                        <Check
                                            class="h-3.5 w-3.5 text-green-600"
                                        />
                                    </button>
                                    <button
                                        class="h-8 w-8 inline-flex items-center justify-center rounded-lg hover:bg-red-50"
                                        @click="openReview(r, 'reject')"
                                    >
                                        <X class="h-3.5 w-3.5 text-red-500" />
                                    </button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="!requests.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-gray-600"
                            >
                                No room change requests
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="requests.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in requests.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-lg"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            "
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1 text-xs text-gray-300"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- New request -->
        <Modal :show="createOpen" @close="createOpen = false" maxWidth="lg">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    New Room Change Request
                </h2>
                <div>
                    <InputLabel value="Resident *" />
                    <select
                        v-model="createForm.resident_id"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        <option value="" disabled>Select resident</option>
                        <option
                            v-for="r in residents"
                            :key="r.id"
                            :value="r.id"
                        >
                            {{ r.name }} ({{ r.resident_code }} -
                            {{ r.building_name }} - {{ r.floor_name }} -
                            {{ r.room_name }} - {{ r.bed_name }})
                        </option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Reason" /><textarea
                        v-model="createForm.reason"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
                </div>

                <p class="text-xs font-semibold text-gray-600 uppercase pt-1">
                    Preferred Room (optional — can be decided at approval time)
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Building" />
                        <select
                            v-model="createForm.requested_building_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Not specified</option>
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
                        <InputLabel value="Floor" />
                        <select
                            v-model="createForm.requested_floor_id"
                            :disabled="!createForm.requested_building_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select floor</option>
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
                <div
                    class="grid grid-cols-2 gap-4"
                    v-if="createForm.requested_floor_id"
                >
                    <div>
                        <InputLabel value="Room" />
                        <select
                            v-model="createForm.requested_room_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select room</option>
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
                    <div v-if="createForm.requested_room_id">
                        <InputLabel value="Bed" />
                        <select
                            v-model="createForm.requested_bed_id"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="">Select bed</option>
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

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="createOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="createForm.processing"
                        >Submit Request</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <!-- Approve / Reject -->
        <Modal :show="reviewOpen" @close="reviewOpen = false" maxWidth="2xl">
            <form
                v-if="reviewing"
                @submit.prevent="submitReview"
                class="flex max-h-[90vh] flex-col overflow-hidden"
            >
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{
                            reviewAction === "approve"
                                ? "Approve Room Change"
                                : "Reject Room Change"
                        }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-700">
                        {{ reviewing.resident?.first_name }}
                        {{ reviewing.resident?.last_name }}
                    </p>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <template v-if="reviewAction === 'approve'">
                        <div
                            class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2"
                        >
                            <div>
                                <p class="text-xs font-medium text-gray-600">
                                    Current Room
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        reviewing.current_stay?.building
                                            ?.name || "—"
                                    }}
                                    · Room
                                    {{
                                        reviewing.current_stay?.room
                                            ?.room_number || "—"
                                    }}
                                    · Bed
                                    {{
                                        reviewing.current_stay?.bed
                                            ?.bed_number || "—"
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-gray-700">
                                    Current rent: ₹{{
                                        Number(
                                            reviewing.current_stay
                                                ?.rent_amount || 0,
                                        ).toLocaleString("en-IN")
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-medium text-gray-600">
                                    New Room
                                </p>

                                <p
                                    v-if="reviewing.requested_bed"
                                    class="mt-1 text-sm font-semibold text-blue-700"
                                >
                                    {{
                                        reviewing.requested_building?.name ||
                                        "—"
                                    }}
                                    · Room
                                    {{
                                        reviewing.requested_room?.room_number ||
                                        "—"
                                    }}
                                    · Bed
                                    {{
                                        reviewing.requested_bed?.bed_number ||
                                        "—"
                                    }}
                                </p>

                                <p
                                    v-else
                                    class="mt-1 text-sm font-semibold text-red-600"
                                >
                                    No specific bed has been selected.
                                </p>

                                <p class="mt-1 text-xs text-gray-700">
                                    Standard room rent: ₹{{
                                        Number(
                                            reviewing.requested_room
                                                ?.monthly_rent_per_bed || 0,
                                        ).toLocaleString("en-IN")
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-blue-100 bg-blue-50 p-4"
                        >
                            <p class="text-sm font-semibold text-blue-900">
                                Student assets will remain assigned
                            </p>

                            <p class="mt-1 text-xs text-blue-700">
                                This is an internal room transfer, not a hostel
                                checkout. Student inventory will continue under
                                the new active stay.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Move Effective From *" />

                                <TextInput
                                    v-model="reviewForm.effective_from"
                                    type="date"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="reviewForm.errors.effective_from"
                                />
                            </div>

                            <div>
                                <InputLabel value="New Billing Basis *" />

                                <select
                                    v-model="reviewForm.new_billing_basis"
                                    required
                                    class="w-full rounded-lg border-gray-300 text-sm"
                                >
                                    <option value="monthly">Monthly</option>

                                    <option value="daily">
                                        Daily Short Stay
                                    </option>
                                </select>

                                <InputError
                                    :message="
                                        reviewForm.errors.new_billing_basis
                                    "
                                />
                            </div>
                        </div>

                        <div v-if="reviewForm.new_billing_basis === 'monthly'">
                            <InputLabel value="New Monthly Rent (₹) *" />

                            <TextInput
                                v-model="reviewForm.new_rent_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                class="w-full"
                            />

                            <p class="mt-1 text-xs text-gray-700">
                                Future monthly invoices will use this rent.
                            </p>

                            <InputError
                                :message="reviewForm.errors.new_rent_amount"
                            />
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <InputLabel value="New Daily Rate (₹) *" />

                                <TextInput
                                    v-model="reviewForm.new_daily_rate"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="reviewForm.errors.new_daily_rate"
                                />
                            </div>

                            <div>
                                <InputLabel value="Expected Check-out Date *" />

                                <TextInput
                                    v-model="
                                        reviewForm.new_expected_check_out_date
                                    "
                                    type="date"
                                    :min="reviewForm.effective_from"
                                    required
                                    class="w-full"
                                />

                                <InputError
                                    :message="
                                        reviewForm.errors
                                            .new_expected_check_out_date
                                    "
                                />
                            </div>
                        </div>
                    </template>

                    <div>
                        <InputLabel
                            :value="
                                reviewAction === 'approve'
                                    ? 'Approval Notes'
                                    : 'Rejection Reason *'
                            "
                        />

                        <textarea
                            v-model="reviewForm.admin_notes"
                            rows="3"
                            :required="reviewAction === 'reject'"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            :placeholder="
                                reviewAction === 'approve'
                                    ? 'Optional transfer notes'
                                    : 'Explain why the request is rejected'
                            "
                        ></textarea>

                        <InputError :message="reviewForm.errors.admin_notes" />
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        :disabled="reviewForm.processing"
                        @click="reviewOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton
                        v-if="reviewAction === 'approve'"
                        type="submit"
                        :disabled="
                            reviewForm.processing || !reviewing.requested_bed_id
                        "
                    >
                        {{
                            reviewForm.processing
                                ? "Moving..."
                                : "Approve & Transfer"
                        }}
                    </PrimaryButton>

                    <DangerButton
                        v-else
                        type="submit"
                        :disabled="reviewForm.processing"
                    >
                        {{
                            reviewForm.processing
                                ? "Rejecting..."
                                : "Reject Request"
                        }}
                    </DangerButton>
                </div>
            </form>
        </Modal>

        <!-- View room change request -->
        <Modal :show="viewOpen" @close="closeView" maxWidth="2xl">
            <div
                v-if="viewing"
                class="flex max-h-[90vh] flex-col overflow-hidden"
            >
                <!-- Header -->
                <div class="shrink-0 border-b border-gray-100 px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-9 w-9 rounded-lg bg-blue-50 flex items-center justify-center"
                                >
                                    <ArrowRightLeft
                                        class="h-4 w-4 text-blue-600"
                                    />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Room Change Request
                                    </h2>

                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Request #{{ viewing.id }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Badge :color="statusColor[viewing.status] || 'gray'">
                            {{
                                viewing.status_label ||
                                formatStatus(viewing.status)
                            }}
                        </Badge>
                    </div>
                </div>

                <!-- Content -->
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <!-- Resident -->
                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Resident
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Name</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{ residentName(viewing.resident) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Resident ID</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{ viewing.resident?.resident_code || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Current / Requested room -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Current -->
                        <div class="rounded-xl border border-gray-200 p-4">
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500"
                            >
                                Current Room
                            </p>

                            <p class="mt-2 text-sm font-semibold text-gray-900">
                                {{
                                    viewing.current_stay?.building?.name || "—"
                                }}
                            </p>

                            <p class="mt-1 text-sm text-gray-700">
                                Room
                                {{
                                    viewing.current_stay?.room?.room_number ||
                                    "—"
                                }}
                                <span class="text-gray-400">·</span>
                                Bed
                                {{
                                    viewing.current_stay?.bed?.bed_number || "—"
                                }}
                            </p>

                            <p class="mt-2 text-xs text-gray-600">
                                Current rent:
                                <span class="font-medium text-gray-900">
                                    ₹{{
                                        Number(
                                            viewing.current_stay?.rent_amount ||
                                                0,
                                        ).toLocaleString("en-IN")
                                    }}
                                </span>
                            </p>
                        </div>

                        <!-- Requested -->
                        <div
                            class="rounded-xl border border-blue-100 bg-blue-50/50 p-4"
                        >
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-blue-600"
                            >
                                Requested Room
                            </p>

                            <template v-if="viewing.requested_room">
                                <p
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                >
                                    {{
                                        viewing.requested_building?.name || "—"
                                    }}
                                </p>

                                <p class="mt-1 text-sm text-gray-700">
                                    Room
                                    {{
                                        viewing.requested_room?.room_number ||
                                        "—"
                                    }}
                                    <span class="text-gray-400">·</span>
                                    Bed
                                    {{
                                        viewing.requested_bed?.bed_number ||
                                        "Not specified"
                                    }}
                                </p>

                                <p class="mt-2 text-xs text-gray-600">
                                    Standard room rent:
                                    <span class="font-medium text-gray-900">
                                        ₹{{
                                            Number(
                                                viewing.requested_room
                                                    ?.monthly_rent_per_bed || 0,
                                            ).toLocaleString("en-IN")
                                        }}
                                    </span>
                                </p>
                            </template>

                            <p v-else class="mt-2 text-sm text-gray-500">
                                No specific room was selected.
                            </p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Reason for Room Change
                        </h3>

                        <p
                            class="mt-2 text-sm text-gray-700 whitespace-pre-wrap"
                        >
                            {{ viewing.reason || "No reason provided." }}
                        </p>
                    </div>

                    <!-- Request information -->
                    <div class="rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Request Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Status</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        viewing.status_label ||
                                        formatStatus(viewing.status)
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Request Source
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        viewing.request_source
                                            ? formatStatus(
                                                  viewing.request_source,
                                              )
                                            : "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Requested By
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        viewing.requested_by_user?.name ||
                                        residentName(
                                            viewing.requested_by_resident,
                                        ) ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Created At</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{ formatDateTime(viewing.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval / transfer details -->
                    <div
                        v-if="
                            viewing.status === 'approved' ||
                            viewing.effective_from ||
                            viewing.new_billing_basis ||
                            viewing.new_rent_amount ||
                            viewing.new_daily_rate
                        "
                        class="rounded-xl border border-green-100 bg-green-50/50 p-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Transfer Details
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">
                                    Effective From
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{ formatDate(viewing.effective_from) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Billing Basis
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        viewing.new_billing_basis
                                            ? formatStatus(
                                                  viewing.new_billing_basis,
                                              )
                                            : "—"
                                    }}
                                </p>
                            </div>

                            <div v-if="viewing.new_billing_basis === 'monthly'">
                                <p class="text-xs text-gray-500">
                                    New Monthly Rent
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    ₹{{
                                        Number(
                                            viewing.new_rent_amount || 0,
                                        ).toLocaleString("en-IN")
                                    }}
                                </p>
                            </div>

                            <div v-if="viewing.new_billing_basis === 'daily'">
                                <p class="text-xs text-gray-500">
                                    New Daily Rate
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    ₹{{
                                        Number(
                                            viewing.new_daily_rate || 0,
                                        ).toLocaleString("en-IN")
                                    }}
                                </p>
                            </div>

                            <div v-if="viewing.new_expected_check_out_date">
                                <p class="text-xs text-gray-500">
                                    Expected Check-out
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        formatDate(
                                            viewing.new_expected_check_out_date,
                                        )
                                    }}
                                </p>
                            </div>

                            <div v-if="viewing.new_stay_id">
                                <p class="text-xs text-gray-500">New Stay ID</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    #{{ viewing.new_stay_id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin review -->
                    <div
                        v-if="
                            viewing.reviewed_at ||
                            viewing.reviewed_by ||
                            viewing.admin_notes
                        "
                        class="rounded-xl border border-gray-200 p-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            Admin Review
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Reviewed By</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        viewing.reviewed_by?.name ||
                                        viewing.reviewed_by ||
                                        "—"
                                    }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Reviewed At</p>
                                <p
                                    class="mt-1 text-sm font-medium text-gray-900"
                                >
                                    {{ formatDateTime(viewing.reviewed_at) }}
                                </p>
                            </div>
                        </div>

                        <div v-if="viewing.admin_notes" class="mt-4">
                            <p class="text-xs text-gray-500">Admin Notes</p>

                            <p
                                class="mt-1 text-sm text-gray-700 whitespace-pre-wrap"
                            >
                                {{ viewing.admin_notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Cancellation -->
                    <div
                        v-if="viewing.cancelled_at"
                        class="rounded-xl border border-red-100 bg-red-50/50 p-4"
                    >
                        <h3 class="text-sm font-semibold text-red-800">
                            Cancellation
                        </h3>

                        <p class="mt-2 text-sm text-red-700">
                            Cancelled at
                            {{ formatDateTime(viewing.cancelled_at) }}
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="closeView"
                    >
                        Close
                    </button>

                    <PrimaryButton
                        v-if="viewing.status === 'pending'"
                        type="button"
                        @click="
                            closeView();
                            openReview(viewing, 'approve');
                        "
                    >
                        <Check class="h-4 w-4" />
                        Review Request
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<!-- resources/js/Pages/Billing/Edit.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import {
    ArrowLeft,
    AlertTriangle,
    Save,
    Receipt,
    Plus,
    Trash2,
} from "lucide-vue-next";

const props = defineProps({
    invoice: { type: Object, required: true },
    residents: { type: Array, default: () => [] },
    stay: { type: Object, default: null },
    paymentCount: { type: Number, default: 0 },
    canEdit: { type: Boolean, default: true },
    canEditAmounts: { type: Boolean, default: true },
});

const feeType = computed(() => props.invoice.fee_type);
const isForResident = computed(() => !!props.invoice.resident_id);

// ─── Hostel fee: dynamic item rows ───────────────────────────────
const editableItems = ref([]);

function defaultTitleFor(itemType, amenityType) {
    if (itemType === "rent") return "Room Rent";
    if (itemType === "mess") return "Mess Charges";
    if (itemType === "amenity") {
        if (amenityType === "cooler") return "Cooler Charges";
        if (amenityType === "wifi") return "WiFi Charges";
        return "Amenity Charges";
    }
    return "Other Charges";
}

function initEditableItems() {
    const existing = (props.invoice.items || []).filter((i) => !i.is_late_fee);

    if (existing.length === 0) {
        editableItems.value = [
            {
                id: null,
                item_type: "rent",
                amenity_type: null,
                title: "Room Rent",
                amount: 0,
            },
            {
                id: null,
                item_type: "mess",
                amenity_type: null,
                title: "Mess Charges",
                amount: 0,
            },
        ];
        return;
    }

    editableItems.value = existing.map((i) => ({
        id: i.id ?? null,
        item_type: i.item_type || "other",
        amenity_type: i.amenity_type ?? null,
        title: String(i.title ?? defaultTitleFor(i.item_type, i.amenity_type)),
        // Always a Number — Vue number inputs need numeric value, not string,
        // or the field renders empty for 0
        amount: Number(i.amount ?? 0),
    }));
}

initEditableItems();

const newItemType = ref("other");

const itemTypeOptions = [
    { value: "rent", label: "Room Rent" },
    { value: "mess", label: "Mess Charges" },
    { value: "amenity", label: "Amenity (Cooler / WiFi / ...)" },
    { value: "other", label: "Other / Custom Charge" },
];

function addItem() {
    const type = newItemType.value;
    const defaults = {
        rent: { title: "Room Rent", amenity_type: null },
        mess: { title: "Mess Charges", amenity_type: null },
        amenity: { title: "", amenity_type: "" },
        other: { title: "", amenity_type: "custom" },
    };
    const d = defaults[type] || defaults.other;
    editableItems.value.push({
        id: null,
        item_type: type,
        amenity_type: d.amenity_type,
        title: d.title,
        amount: 0,
    });
}

function removeItem(index) {
    editableItems.value.splice(index, 1);
}

// ─── Find the existing item of a given type (single-item fee types) ─
function findItemByType(type) {
    return (props.invoice.items || []).find((i) => i.item_type === type);
}

const depositInitial = computed(() => {
    const it = findItemByType("security_deposit");
    return it ? Number(it.amount ?? 0) : 0;
});
const registrationInitial = computed(() => {
    const it = findItemByType("registration_fee");
    return it ? Number(it.amount ?? 0) : 0;
});
const shortStayInitial = computed(() => {
    const it = findItemByType("short_stay");
    return it ? Number(it.amount ?? 0) : 0;
});
const donationInitial = computed(() => {
    const it = findItemByType("donation");
    return it ? Number(it.amount ?? 0) : 0;
});

// ─── Form ────────────────────────────────────────────────────────
const form = useForm({
    invoice_for: isForResident.value ? "resident" : "application",
    resident_id: props.invoice.resident_id ?? "",
    application_id: props.invoice.application_id ?? "",
    stay_id: props.invoice.stay_id ?? "",
    items: [],
    deposit_amount: depositInitial.value,
    registration_amount: registrationInitial.value,
    short_stay_amount: shortStayInitial.value,

    donation_amount: donationInitial.value,
    donator_name: props.invoice.donator_name ?? "",
    donator_address: props.invoice.donator_address ?? "",
    donator_phone: props.invoice.donator_phone ?? "",

    due_date: props.invoice.due_date
        ? String(props.invoice.due_date).substring(0, 10)
        : "",
    description: props.invoice.description ?? "",
    late_fee_per_day: Number(props.invoice.late_fee_per_day ?? 0),
});

watch(
    editableItems,
    (newItems) => {
        if (feeType.value === "hostel_fee") {
            form.items = newItems.map((i) => ({
                item_type: i.item_type,
                amenity_type: i.amenity_type ?? null,
                title: String(i.title ?? ""),
                amount: Number(i.amount ?? 0),
            }));
        }
    },
    { deep: true },
);

const submit = () => {
    form.put(`/billing/${props.invoice.id}`, {
        preserveScroll: true,
    });
};

const titleMap = {
    hostel_fee: "Hostel Fee",
    security_deposit: "Security Deposit",
    registration_fee: "Registration Fee",
    short_stay: "Short Stay",
    donation: "Donation",
};

const amountLocked = computed(() => !props.canEditAmounts);
const fieldsLocked = computed(() => !props.canEdit);

const total = computed(() => {
    if (feeType.value === "hostel_fee") {
        return editableItems.value.reduce(
            (s, i) => s + Number(i.amount || 0),
            0,
        );
    }
    if (feeType.value === "security_deposit") {
        return Number(form.deposit_amount || 0);
    }
    if (feeType.value === "registration_fee") {
        return Number(form.registration_amount || 0);
    }
    if (feeType.value === "short_stay") {
        return Number(form.short_stay_amount || 0);
    }
    if (feeType.value === "donation") {
        return Number(form.donation_amount || 0);
    }
    return 0;
});
</script>

<template>
    <Head :title="`Edit Invoice ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Receipt class="w-7 h-7 text-indigo-600" />
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800">
                            Edit Invoice {{ invoice.invoice_number }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ titleMap[feeType] || feeType }} invoice
                            <span v-if="amountLocked" class="text-amber-600">
                                — amounts locked ({{ paymentCount }} payment(s)
                                recorded)
                            </span>
                            <span v-else class="text-green-600"
                                >— fully editable</span
                            >
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <Link
            :href="route('billing.index')"
            class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center gap-1"
        >
            <ArrowLeft class="w-4 h-4" /> Back to billing
        </Link>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Locked notice -->
                <div
                    v-if="amountLocked"
                    class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3"
                >
                    <AlertTriangle
                        class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0"
                    />
                    <div>
                        <p class="font-semibold text-amber-800">
                            Amounts are locked
                        </p>
                        <p class="text-sm text-amber-700">
                            {{ paymentCount }} payment(s) recorded. You can
                            update the due date, description, and late fee
                            settings, but changing amounts would corrupt the
                            payment ledger.
                        </p>
                    </div>
                </div>

                <form
                    @submit.prevent="submit"
                    class="bg-white shadow rounded-lg p-6 space-y-6"
                >
                    <!-- ─── Resident / Application toggle ─── -->
                    <div>
                        <InputLabel value="Invoice For" />
                        <div class="flex gap-4 mt-2">
                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="resident"
                                    v-model="form.invoice_for"
                                    :disabled="fieldsLocked"
                                />
                                <span>Resident</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="application"
                                    v-model="form.invoice_for"
                                    :disabled="fieldsLocked"
                                />
                                <span>Application (Pre-booking)</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="form.invoice_for === 'resident'">
                        <InputLabel for="resident_id" value="Resident" />
                        <select
                            id="resident_id"
                            v-model="form.resident_id"
                            class="mt-1 block w-full border-gray-300 rounded-md"
                            :disabled="fieldsLocked"
                        >
                            <option value="">Select resident...</option>
                            <option
                                v-for="r in residents"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.first_name }} {{ r.last_name }} ({{
                                    r.resident_code
                                }})
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.resident_id"
                            class="mt-1"
                        />
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!-- HOSTEL FEE — dynamic item rows -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div v-if="feeType === 'hostel_fee'">
                        <p class="text-sm font-medium text-gray-700 mb-2">
                            Invoice Items
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in editableItems"
                                :key="index"
                                class="flex items-start gap-2"
                            >
                                <!-- Item type tag -->
                                <span
                                    class="inline-flex items-center px-2 py-2 rounded-md text-xs font-medium bg-gray-100 text-gray-700 min-w-[80px] justify-center"
                                >
                                    {{
                                        item.item_type === "amenity"
                                            ? (
                                                  item.amenity_type || "amenity"
                                              ).toUpperCase()
                                            : item.item_type.toUpperCase()
                                    }}
                                </span>

                                <!-- Title input (plain input with explicit full-width) -->
                                <input
                                    type="text"
                                    v-model="item.title"
                                    :placeholder="
                                        item.item_type === 'amenity'
                                            ? 'e.g. Cooler, WiFi'
                                            : 'Charge name'
                                    "
                                    class="flex-1 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    :disabled="amountLocked"
                                />

                                <!-- Amenity type (only for amenity rows) -->
                                <input
                                    v-if="item.item_type === 'amenity'"
                                    type="text"
                                    v-model="item.amenity_type"
                                    placeholder="cooler"
                                    class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    :disabled="amountLocked"
                                />

                                <!-- Amount -->
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model.number="item.amount"
                                    class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                                    :disabled="amountLocked"
                                />

                                <!-- Remove -->
                                <button
                                    v-if="
                                        canEditAmounts &&
                                        editableItems.length > 1
                                    "
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded"
                                    title="Remove item"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Add item -->
                        <div
                            v-if="canEditAmounts"
                            class="flex items-center gap-2 mt-3"
                        >
                            <select
                                v-model="newItemType"
                                class="rounded-md border-gray-300 text-sm"
                            >
                                <option
                                    v-for="opt in itemTypeOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200"
                            >
                                <Plus class="w-4 h-4" /> Add
                            </button>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!-- SECURITY DEPOSIT -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div v-if="feeType === 'security_deposit'">
                        <InputLabel
                            for="deposit_amount"
                            value="Deposit Amount (₹)"
                        />
                        <TextInput
                            id="deposit_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model.number="form.deposit_amount"
                            class="mt-1 block w-full"
                            :disabled="amountLocked"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Refundable security deposit for this stay.
                        </p>
                        <InputError
                            :message="form.errors.deposit_amount"
                            class="mt-1"
                        />
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!-- REGISTRATION FEE -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div v-if="feeType === 'registration_fee'">
                        <InputLabel
                            for="registration_amount"
                            value="Registration Fee (₹)"
                        />
                        <TextInput
                            id="registration_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model.number="form.registration_amount"
                            class="mt-1 block w-full"
                            :disabled="amountLocked"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            One-time registration fee for this application.
                        </p>
                        <InputError
                            :message="form.errors.registration_amount"
                            class="mt-1"
                        />
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!-- SHORT STAY -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div v-if="feeType === 'short_stay'">
                        <InputLabel
                            for="short_stay_amount"
                            value="Stay Amount (₹)"
                        />
                        <TextInput
                            id="short_stay_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model.number="form.short_stay_amount"
                            class="mt-1 block w-full"
                            :disabled="amountLocked"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            Total accommodation charge for the short stay.
                        </p>
                        <InputError
                            :message="form.errors.short_stay_amount"
                            class="mt-1"
                        />
                    </div>

                    <!-- ═══════════════════════════════════════════════ -->
                    <!-- DONATION -->
                    <!-- ═══════════════════════════════════════════════ -->
                    <div v-if="feeType === 'donation'">
                        <InputLabel
                            for="donation_amount"
                            value="Donation Amount (₹)"
                        />

                        <TextInput
                            id="donation_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model.number="form.donation_amount"
                            class="mt-1 block w-full"
                            :disabled="amountLocked"
                        />

                        <InputError
                            :message="form.errors.donation_amount"
                            class="mt-1"
                        />

                        <!-- Donator Name -->
                        <InputLabel
                            for="donator_name"
                            value="Donator Name"
                            class="mt-3"
                        />

                        <TextInput
                            id="donator_name"
                            type="text"
                            v-model="form.donator_name"
                            class="mt-1 block w-full"
                            :disabled="fieldsLocked"
                        />

                        <InputError
                            :message="form.errors.donator_name"
                            class="mt-1"
                        />

                        <!-- Donator Address -->
                        <InputLabel
                            for="donator_address"
                            value="Donator Address"
                            class="mt-3"
                        />

                        <TextInput
                            id="donator_address"
                            type="text"
                            v-model="form.donator_address"
                            class="mt-1 block w-full"
                            :disabled="fieldsLocked"
                        />

                        <InputError
                            :message="form.errors.donator_address"
                            class="mt-1"
                        />

                        <!-- Donator Phone -->
                        <InputLabel
                            for="donator_phone"
                            value="Donator Phone"
                            class="mt-3"
                        />

                        <TextInput
                            id="donator_phone"
                            type="text"
                            v-model="form.donator_phone"
                            class="mt-1 block w-full"
                        />

                        <InputError
                            :message="form.errors.donator_phone"
                            class="mt-1"
                        />

                        <p class="text-xs text-gray-500 mt-2">
                            Donation / contribution amount. Receipt format will
                            be different.
                        </p>
                    </div>

                    <!-- ─── Common: Due Date + Late Fee ─── -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                type="date"
                                v-model="form.due_date"
                                class="mt-1 block w-full"
                                :disabled="fieldsLocked"
                            />
                            <InputError
                                :message="form.errors.due_date"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="late_fee_per_day"
                                value="Late Fee (per day)"
                            />
                            <TextInput
                                id="late_fee_per_day"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model.number="form.late_fee_per_day"
                                class="mt-1 block w-full"
                                :disabled="fieldsLocked"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md"
                            :disabled="fieldsLocked"
                        />
                    </div>

                    <!-- Total -->
                    <div
                        v-if="canEditAmounts"
                        class="bg-gray-50 rounded-md p-4 flex items-center justify-between"
                    >
                        <span class="text-sm text-gray-600">New total</span>
                        <span class="text-2xl font-bold text-indigo-700">
                            ₹{{ total.toFixed(2) }}
                        </span>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link :href="route('billing.index')">
                            <SecondaryButton type="button"
                                >Cancel</SecondaryButton
                            >
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            <Save class="w-4 h-4 mr-2" /> Save Changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

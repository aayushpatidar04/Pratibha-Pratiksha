<!-- resources/js/Pages/Billing/Edit.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import { ArrowLeft, AlertTriangle, Save, Receipt } from "lucide-vue-next";

const props = defineProps({
    invoice: { type: Object, required: true },
    residents: { type: Array, default: () => [] },
    stay: { type: Object, default: null },
    paymentCount: { type: Number, default: 0 },
    canEdit: { type: Boolean, default: false },
});

const isForResident = computed(() => !!props.invoice.resident_id);

// Pre-fill the form from the existing invoice's items.
// We map back from items to the three amount fields the controller knows.
const rentItem = props.invoice.items?.find((i) => i.item_type === "rent");
const messItem = props.invoice.items?.find((i) => i.item_type === "mess");
const otherItem = props.invoice.items?.find(
    (i) => i.item_type === "other" || i.item_type === "custom",
);

const form = useForm({
    invoice_for: isForResident.value ? "resident" : "application",
    resident_id: props.invoice.resident_id ?? "",
    application_id: props.invoice.application_id ?? "",
    stay_id: props.invoice.stay_id ?? "",
    rent_amount: rentItem ? rentItem.amount : 0,
    mess_amount: messItem ? messItem.amount : 0,
    other_amount: otherItem ? otherItem.amount : 0,
    other_title: otherItem ? otherItem.title : "Other Charges",
    due_date: props.invoice.due_date
        ? String(props.invoice.due_date).substring(0, 10)
        : "",
    description: props.invoice.description ?? "",
    late_fee_per_day: props.invoice.late_fee_per_day ?? 0,
});

const submit = () => {
    form.put(`/billing/${props.invoice.id}`, {
        preserveScroll: true,
    });
};

const total = computed(
    () =>
        Number(form.rent_amount || 0) +
        Number(form.mess_amount || 0) +
        Number(form.other_amount || 0),
);
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
                            Update the invoice details below.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div class="mb-2">
            <div class="flex gap-2 justify-end">
                <Link
                    :href="route('billing.index')"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600 hover:bg-gray-50"
                >
                    <ArrowLeft class="h-4 w-4" /> Back to billing
                </Link>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Hard lock if payments already exist -->
                <div
                    v-if="!canEdit"
                    class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3"
                >
                    <AlertTriangle
                        class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0"
                    />
                    <div>
                        <p class="font-semibold text-red-800">
                            This invoice cannot be edited.
                        </p>
                        <p class="text-sm text-red-700">
                            {{ paymentCount }} payment(s) have already been
                            recorded against this invoice. Direct edits would
                            corrupt the payment ledger. Use the refund or
                            adjustment flows instead.
                        </p>
                    </div>
                </div>

                <form
                    @submit.prevent="submit"
                    class="bg-white shadow rounded-lg p-6 space-y-6"
                >
                    <!-- Resident / Application toggle -->
                    <div>
                        <InputLabel value="Invoice For" />
                        <div class="flex gap-4 mt-2">
                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="resident"
                                    v-model="form.invoice_for"
                                    :disabled="!canEdit"
                                />
                                <span>Resident</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="application"
                                    v-model="form.invoice_for"
                                    :disabled="!canEdit"
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
                            :disabled="!canEdit"
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

                    <!-- Amounts -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="rent_amount" value="Room Rent" />
                            <TextInput
                                id="rent_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.rent_amount"
                                class="mt-1 block w-full"
                                :disabled="!canEdit"
                            />
                            <InputError
                                :message="form.errors.rent_amount"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="mess_amount"
                                value="Mess Charges"
                            />
                            <TextInput
                                id="mess_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.mess_amount"
                                class="mt-1 block w-full"
                                :disabled="!canEdit"
                            />
                            <InputError
                                :message="form.errors.mess_amount"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <InputLabel for="other_amount" value="Other" />
                            <TextInput
                                id="other_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.other_amount"
                                class="mt-1 block w-full"
                                :disabled="!canEdit"
                            />
                            <InputError
                                :message="form.errors.other_amount"
                                class="mt-1"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="other_title" value="Other Title" />
                        <TextInput
                            id="other_title"
                            v-model="form.other_title"
                            class="mt-1 block w-full"
                            :disabled="!canEdit"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                type="date"
                                v-model="form.due_date"
                                class="mt-1 block w-full"
                                :disabled="!canEdit"
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
                                v-model="form.late_fee_per_day"
                                class="mt-1 block w-full"
                                :disabled="!canEdit"
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
                            :disabled="!canEdit"
                        />
                    </div>

                    <div
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
                        <PrimaryButton
                            v-if="canEdit"
                            :disabled="form.processing"
                        >
                            <Save class="w-4 h-4 mr-2" /> Save Changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

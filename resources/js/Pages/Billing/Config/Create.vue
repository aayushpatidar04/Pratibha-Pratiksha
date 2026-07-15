<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { Plus, Trash2, ArrowLeft } from "lucide-vue-next";
import { ref, computed, watch } from "vue";

const props = defineProps({
    suggestedMonth: Number,
    suggestedYear: Number,
    suggestedGenerationDate: String,
    suggestedDueDate: String,
});

const monthName = (month) =>
    new Date(2026, month - 1).toLocaleString("default", {
        month: "long",
    });

const form = useForm({
    year: props.suggestedYear,
    month: props.suggestedMonth,
    full_label: `${monthName(props.suggestedMonth)} ${props.suggestedYear}`,
    rent_enabled: true,
    mess_enabled: true,
    default_mess_amount: 3000,
    cooler_enabled: false,
    default_cooler_amount: 500,
    custom_charges: [],
    generation_date: props.suggestedGenerationDate,
    due_date: props.suggestedDueDate,
    late_fee_per_day: 50,
    late_fee_enabled: true,
    notes: "",
});

watch(
    () => [form.month, form.year],
    ([month, year]) => {
        form.full_label = `${monthName(month)} ${year}`;
    },
    { immediate: true },
);

const addCustomCharge = () => {
    form.custom_charges.push({ name: "", amount: "" });
};

const removeCustomCharge = (i) => {
    form.custom_charges.splice(i, 1);
};

const submit = () => form.post(route("billing.config.store"));
</script>

<template>
    <Head title="New Billing Config" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('billing.config.index')"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        New Monthly Billing Configuration
                    </h1>
                    <p class="text-sm text-gray-500">
                        Set up amenities and charges for the month
                    </p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                <!-- Month & Year -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Year *" />
                        <TextInput
                            type="number"
                            v-model="form.year"
                            required
                            class="w-full"
                        />
                        <InputError :message="form.errors.year" />
                    </div>
                    <div>
                        <InputLabel value="Month *" />
                        <select
                            v-model="form.month"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            required
                        >
                            <option v-for="m in 12" :key="m" :value="m">
                                {{
                                    new Date(2026, m - 1).toLocaleString(
                                        "default",
                                        { month: "long" },
                                    )
                                }}
                            </option>
                        </select>
                        <InputError :message="form.errors.month" />
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Generation Date *" />
                        <TextInput
                            type="date"
                            v-model="form.generation_date"
                            required
                            class="w-full"
                        />
                        <p class="text-xs text-gray-400 mt-1">
                            When bills will be auto-generated
                        </p>
                        <InputError :message="form.errors.generation_date" />
                    </div>
                    <div>
                        <InputLabel value="Due Date *" />
                        <TextInput
                            type="date"
                            v-model="form.due_date"
                            required
                            class="w-full"
                        />
                        <p class="text-xs text-gray-400 mt-1">
                            Payment deadline
                        </p>
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>

                <!-- Amenities -->
                <div>
                    <InputLabel value="Included Amenities" />
                    <div class="grid grid-cols-2 gap-3 mt-2">
                        <label
                            class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="
                                form.rent_enabled
                                    ? 'border-blue-300 bg-blue-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                        >
                            <input
                                type="checkbox"
                                v-model="form.rent_enabled"
                                class="rounded text-blue-600"
                            />
                            <span class="text-sm font-medium">Room Rent</span>
                        </label>
                        <div>
                            <label
                                class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
                                :class="
                                    form.mess_enabled
                                        ? 'border-green-300 bg-green-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.mess_enabled"
                                    class="rounded text-green-600"
                                />
                                <span class="text-sm font-medium"
                                    >Mess Charges</span
                                >
                            </label>
                            <div v-if="form.mess_enabled" class="mt-2">
                                <InputLabel value="Default Mess Amount (₹)" />
                                <TextInput
                                    v-model="form.default_mess_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full"
                                />
                                <InputError
                                    :message="form.errors.default_mess_amount"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
                                :class="
                                    form.cooler_enabled
                                        ? 'border-cyan-300 bg-cyan-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                "
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.cooler_enabled"
                                    class="rounded text-cyan-600"
                                />
                                <span class="text-sm font-medium">Cooler</span>
                            </label>
                            <div v-if="form.cooler_enabled" class="mt-2">
                                <InputLabel value="Default Cooler Amount (₹)" />
                                <TextInput
                                    v-model="form.default_cooler_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full"
                                />
                                <InputError
                                    :message="form.errors.default_cooler_amount"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Charges -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <InputLabel value="Custom Charges" />
                        <button
                            type="button"
                            @click="addCustomCharge"
                            class="text-sm text-blue-600 flex items-center gap-1 hover:text-blue-700"
                        >
                            <Plus class="h-4 w-4" /> Add Charge
                        </button>
                    </div>
                    <div
                        v-for="(charge, i) in form.custom_charges"
                        :key="i"
                        class="flex gap-2 mb-2 items-start"
                    >
                        <TextInput
                            v-model="charge.name"
                            placeholder="Charge name (e.g. Event Fee)"
                            class="w-32"
                        />
                        <TextInput
                            type="number"
                            v-model="charge.amount"
                            placeholder="Amount"
                            class="w-32"
                        />
                        <button
                            type="button"
                            @click="removeCustomCharge(i)"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg mt-0.5"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <p
                        v-if="!form.custom_charges.length"
                        class="text-xs text-gray-400 italic"
                    >
                        No custom charges added
                    </p>
                </div>

                <!-- Late Fee -->
                <div class="p-4 rounded-lg border border-gray-200 bg-gray-50">
                    <label class="flex items-center gap-2 mb-3">
                        <input
                            type="checkbox"
                            v-model="form.late_fee_enabled"
                            class="rounded text-red-600"
                        />
                        <span class="text-sm font-medium">Enable Late Fee</span>
                    </label>
                    <div v-if="form.late_fee_enabled">
                        <InputLabel value="Late Fee Per Day (₹)" />
                        <TextInput
                            type="number"
                            v-model="form.late_fee_per_day"
                            class="w-48"
                            min="0"
                        />
                        <p class="text-xs text-gray-400 mt-1">
                            Charged for every day the invoice remains unpaid
                            after the due date.
                        </p>
                        <InputError :message="form.errors.late_fee_per_day" />
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <InputLabel value="Notes" />
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Any special instructions for this month..."
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <Link
                    :href="route('billing.config.index')"
                    class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg border border-gray-300"
                >
                    Cancel
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? "Saving..." : "Save Configuration" }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

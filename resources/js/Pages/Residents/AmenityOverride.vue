<!-- resources/js/Pages/Residents/AmenityOverride.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { Settings, ArrowLeft } from "lucide-vue-next";

const props = defineProps({
    resident: Object,
    override: Object,
});

const form = useForm({
    rent_enabled: props.override?.rent_enabled ?? true,
    mess_enabled: props.override?.mess_enabled ?? true,
    cooler_enabled: props.override?.cooler_enabled ?? false,
    custom_rent: props.override?.custom_rent ?? "",
    custom_mess: props.override?.custom_mess ?? "",
    custom_cooler: props.override?.custom_cooler ?? "",
    notes: props.override?.notes ?? "",
});

const submit = () => {
    form.put(route("residents.amenity-override.update", props.resident.id));
};
</script>

<template>
    <Head :title="`Amenities - ${resident.first_name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('residents.index')"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Amenity Configuration
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ resident.first_name }} {{ resident.last_name }}
                    </p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                <!-- Toggles -->
                <div class="grid grid-cols-2 gap-4">
                    <label
                        class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer"
                        :class="
                            form.rent_enabled
                                ? 'border-blue-300 bg-blue-50'
                                : 'border-gray-200'
                        "
                    >
                        <input
                            type="checkbox"
                            v-model="form.rent_enabled"
                            class="rounded text-blue-600"
                        />
                        <span class="text-sm font-medium">Room Rent</span>
                    </label>
                    <label
                        class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer"
                        :class="
                            form.mess_enabled
                                ? 'border-green-300 bg-green-50'
                                : 'border-gray-200'
                        "
                    >
                        <input
                            type="checkbox"
                            v-model="form.mess_enabled"
                            class="rounded text-green-600"
                        />
                        <span class="text-sm font-medium">Mess Charges</span>
                    </label>
                    <label
                        class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer"
                        :class="
                            form.cooler_enabled
                                ? 'border-cyan-300 bg-cyan-50'
                                : 'border-gray-200'
                        "
                    >
                        <input
                            type="checkbox"
                            v-model="form.cooler_enabled"
                            class="rounded text-cyan-600"
                        />
                        <span class="text-sm font-medium">Cooler</span>
                    </label>
                </div>

                <!-- Custom Amounts -->
                <div class="border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        Custom Amounts (Optional)
                    </h3>
                    <p class="text-xs text-gray-500 mb-3">
                        Leave blank to use default rates from room/stay
                    </p>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Custom Rent" />
                            <TextInput
                                type="number"
                                v-model="form.custom_rent"
                                placeholder="Default"
                            />
                        </div>
                        <div>
                            <InputLabel value="Custom Mess" />
                            <TextInput
                                type="number"
                                v-model="form.custom_mess"
                                placeholder="Default"
                            />
                        </div>
                        <div>
                            <InputLabel value="Custom Cooler" />
                            <TextInput
                                type="number"
                                v-model="form.custom_cooler"
                                placeholder="Default"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Why these overrides apply..."
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing"
                    >Save Configuration</PrimaryButton
                >
            </div>
        </form>
    </AuthenticatedLayout>
</template>

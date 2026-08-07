<!-- resources/js/Pages/Billing/GeneratePreview.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import {
    ArrowLeft,
    CheckCircle,
    AlertTriangle,
    User,
    Settings,
} from "lucide-vue-next";

const props = defineProps({
    config: Object,
    preview: {
        type: Array,
        default: () => [],
    },
});

const search = ref("");

const filteredPreview = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.preview;
    }

    return props.preview.filter((p) => {
        return (
            String(p.resident_name ?? "")
                .toLowerCase()
                .includes(term) ||
            String(p.resident_code ?? "")
                .toLowerCase()
                .includes(term) ||
            String(p.room ?? "")
                .toLowerCase()
                .includes(term)
        );
    });
});

const selectedResidents = ref(
    props.preview.filter((p) => !p.skip).map((p) => p.resident_id),
);

const toggleAll = () => {
    if (selectedResidents.value.length === selectableCount.value) {
        selectedResidents.value = [];
    } else {
        selectedResidents.value = props.preview
            .filter((p) => !p.skip)
            .map((p) => p.resident_id);
    }
};

const selectableCount = computed(
    () => props.preview.filter((p) => !p.skip).length,
);

const selectAll = computed(
    () =>
        selectedResidents.value.length === selectableCount.value &&
        selectableCount.value > 0,
);

const totalAmount = computed(() => {
    return props.preview
        .filter((p) => selectedResidents.value.includes(p.resident_id))
        .reduce((sum, p) => sum + p.total, 0);
});

const form = useForm({
    selected_residents: [],
});

const submit = () => {
    form.selected_residents = selectedResidents.value;
    form.post(route("billing.config.confirm-generate", props.config.id));
};

const formatCurrency = (amount) => {
    return (
        "₹" +
        Number(amount || 0).toLocaleString("en-IN", {
            minimumFractionDigits: 2,
        })
    );
};
</script>

<template>
    <Head :title="`Generate Bills - ${config.full_label}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('billing.config.index')"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-700"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Generate Bills
                    </h1>
                    <p class="text-sm text-gray-700">
                        {{ config.full_label }} • Due: {{ config.due_date }}
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Config Summary -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <h3
                    class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2"
                >
                    <Settings class="h-4 w-4 text-blue-600" /> Configuration
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-if="config.rent_enabled"
                        class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-full"
                        >Rent</span
                    >
                    <span
                        v-if="config.mess_enabled"
                        class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded-full"
                        >Mess</span
                    >
                    <span
                        v-if="config.cooler_enabled"
                        class="px-2 py-1 bg-cyan-50 text-cyan-700 text-xs rounded-full"
                        >Cooler</span
                    >
                    <span
                        v-if="config.laundry_enabled"
                        class="px-2 py-1 bg-purple-50 text-purple-700 text-xs rounded-full"
                        >Laundry</span
                    >
                    <span
                        v-if="config.wifi_enabled"
                        class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full"
                        >WiFi</span
                    >
                    <span
                        v-if="config.gym_enabled"
                        class="px-2 py-1 bg-pink-50 text-pink-700 text-xs rounded-full"
                        >Gym</span
                    >
                    <span
                        v-if="config.parking_enabled"
                        class="px-2 py-1 bg-gray-50 text-gray-700 text-xs rounded-full"
                        >Parking</span
                    >
                </div>
                <p
                    v-if="config.late_fee_enabled"
                    class="text-xs text-red-600 mt-2"
                >
                    Late Fee: {{ formatCurrency(config.late_fee_amount) }}
                </p>
            </div>

            <!-- Select All -->
            <div
                class="flex items-center justify-between bg-white rounded-xl border border-gray-100 shadow-sm p-4"
            >
                <label class="flex items-center gap-2 cursor-pointer">
                    <Checkbox
                        :checked="selectAll"
                        @update:checked="toggleAll"
                    />
                    <span class="text-sm font-medium text-gray-900">
                        Select All ({{ selectedResidents.length }}/{{
                            selectableCount
                        }})
                    </span>
                </label>
                <p class="text-sm font-bold text-gray-900">
                    Total: {{ formatCurrency(totalAmount) }}
                </p>
            </div>

            <!-- Resident Search -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Residents
                        </h3>

                        <p class="text-xs text-gray-500 mt-1">
                            Search by resident name, resident code, or room number.
                        </p>
                    </div>

                    <div class="relative w-full sm:w-80">
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search residents..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-9 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                        />

                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            @click="search = ''"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Showing
                        <span class="font-semibold text-gray-700">
                            {{ filteredPreview.length }}
                        </span>
                        of
                        <span class="font-semibold text-gray-700">
                            {{ preview.length }}
                        </span>
                        residents
                    </p>

                    <p
                        v-if="search"
                        class="text-xs text-blue-600"
                    >
                        Search: "{{ search }}"
                    </p>
                </div>
            </div>

            <!-- Resident List -->
            <div class="space-y-3">
                <div
                    v-for="p in filteredPreview"
                    :key="p.resident_id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
                    :class="{ 'opacity-50': p.skip }"
                >
                    <div class="flex items-start gap-3">
                        <Checkbox
                            v-if="!p.skip"
                            :value="p.resident_id"
                            v-model:checked="selectedResidents"
                            class="mt-1"
                        />
                        <div
                            v-else
                            class="w-5 h-5 mt-1 flex items-center justify-center"
                        >
                            <AlertTriangle class="h-4 w-4 text-amber-500" />
                        </div>

                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <User class="h-4 w-4 text-gray-600" />
                                <span class="font-medium text-gray-900">{{
                                    p.resident_name
                                }}</span>
                                <span class="text-xs text-gray-600">{{
                                    p.resident_code
                                }}</span>
                                <span
                                    v-if="p.room"
                                    class="text-xs text-gray-600"
                                    >Room {{ p.room }}</span
                                >
                                <span
                                    v-if="p.has_override"
                                    class="px-1.5 py-0.5 bg-purple-50 text-purple-700 text-[10px] rounded-full font-medium"
                                >
                                    Custom
                                </span>
                            </div>

                            <!-- Items -->
                            <div
                                v-if="p.items.length"
                                class="flex flex-wrap gap-2 mt-2"
                            >
                                <span
                                    v-for="item in p.items"
                                    :key="item.label"
                                    class="text-xs px-2 py-1 rounded-lg"
                                    :class="
                                        item.is_custom
                                            ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                            : 'bg-gray-50 text-gray-600'
                                    "
                                >
                                    {{ item.label }}:
                                    {{ formatCurrency(item.amount) }}
                                </span>
                            </div>
                            <p v-else class="text-xs text-amber-600">
                                {{ p.skip_reason || "No applicable charges" }}
                            </p>

                            <p class="text-sm font-bold text-gray-900 mt-2">
                                Total: {{ formatCurrency(p.total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="filteredPreview.length === 0"
                    class="rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center"
                >
                    <Search
                        class="mx-auto h-8 w-8 text-gray-300"
                    />

                    <p class="mt-2 text-sm font-medium text-gray-700">
                        No residents found
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Try searching with a different name, resident code, or room number.
                    </p>

                    <button
                        v-if="search"
                        type="button"
                        class="mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700"
                        @click="search = ''"
                    >
                        Clear search
                    </button>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="flex justify-end">
                <PrimaryButton
                    :disabled="
                        selectedResidents.length === 0 || form.processing
                    "
                    @click="submit"
                >
                    <CheckCircle class="h-4 w-4 mr-1" />
                    Generate {{ selectedResidents.length }} Bills
                </PrimaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

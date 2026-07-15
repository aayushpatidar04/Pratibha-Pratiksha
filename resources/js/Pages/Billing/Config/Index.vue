<!-- resources/js/Pages/Billing/Config/Index.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    Settings,
    Plus,
    Calendar,
    Play,
    CheckCircle,
    Trash2,
    Users,
    ArrowRight,
} from "lucide-vue-next";

const props = defineProps({
    configs: Object,
});

const generateBills = (config) => {
    if (!confirm(`Preview bill generation for ${config.full_label}?`)) return;
    router.get(route("billing.config.preview", config.id));
};

const destroyConfig = (config) => {
    if (
        !confirm(
            `Delete configuration for ${config.full_label}? Existing invoices will NOT be deleted.`,
        )
    )
        return;
    router.delete(route("billing.config.destroy", config.id), {
        preserveScroll: true,
    });
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
    <Head title="Monthly Billing Configuration" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Monthly Billing Configuration
                    </h1>
                    <p class="text-sm text-gray-500">
                        Set up monthly amenities and generate bills
                    </p>
                </div>
            </div>
        </template>
        <div class="mb-2">
            <div class="flex gap-2 justify-end">
                <Link
                    :href="route('billing.config.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" /> New Config
                </Link>
            </div>
        </div>
        <div class="space-y-4">
            <!-- Empty State -->
            <div
                v-if="!configs.data?.length"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center"
            >
                <Calendar class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                <h3 class="text-lg font-medium text-gray-900 mb-1">
                    No configurations yet
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Create your first monthly billing configuration to start
                    auto-generating bills.
                </p>
                <Link
                    :href="route('billing.config.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" /> Create Config
                </Link>
            </div>

            <!-- Config Cards -->
            <div
                v-for="config in configs.data"
                :key="config.id"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ config.full_label }}
                            </h3>
                            <span
                                v-if="config.invoices_count"
                                class="px-2 py-0.5 bg-green-50 text-green-700 text-xs rounded-full font-medium"
                            >
                                {{ config.invoices_count }} invoices generated
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-4 text-sm text-gray-500 mb-3"
                        >
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3.5 w-3.5" />
                                Generate: {{ config.generation_date }}
                            </span>
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3.5 w-3.5" />
                                Due: {{ config.due_date }}
                            </span>
                            <span
                                v-if="config.late_fee_enabled"
                                class="text-red-600 font-medium"
                            >
                                Late Fee:
                                {{
                                    formatCurrency(config.late_fee_per_day)
                                }}/day
                            </span>
                        </div>

                        <!-- Amenities -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span
                                v-if="config.rent_enabled"
                                class="px-2.5 py-1 bg-blue-50 text-blue-700 text-xs rounded-full font-medium border border-blue-100"
                                >Rent</span
                            >
                            <span
                                v-if="config.mess_enabled"
                                class="px-2.5 py-1 bg-green-50 text-green-700 text-xs rounded-full font-medium border border-green-100"
                                >Mess</span
                            >
                            <span
                                v-if="config.cooler_enabled"
                                class="px-2.5 py-1 bg-cyan-50 text-cyan-700 text-xs rounded-full font-medium border border-cyan-100"
                                >Cooler</span
                            >
                            <span
                                v-if="config.laundry_enabled"
                                class="px-2.5 py-1 bg-purple-50 text-purple-700 text-xs rounded-full font-medium border border-purple-100"
                                >Laundry</span
                            >
                            <span
                                v-if="config.wifi_enabled"
                                class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full font-medium border border-indigo-100"
                                >WiFi</span
                            >
                            <span
                                v-if="config.gym_enabled"
                                class="px-2.5 py-1 bg-pink-50 text-pink-700 text-xs rounded-full font-medium border border-pink-100"
                                >Gym</span
                            >
                            <span
                                v-if="config.parking_enabled"
                                class="px-2.5 py-1 bg-gray-50 text-gray-700 text-xs rounded-full font-medium border border-gray-200"
                                >Parking</span
                            >
                        </div>

                        <!-- Custom Charges -->
                        <div
                            v-if="config.custom_charges?.length"
                            class="mt-3 pt-3 border-t border-gray-100"
                        >
                            <p class="text-xs text-gray-500 mb-2 font-medium">
                                Custom Charges:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(charge, i) in config.custom_charges"
                                    :key="i"
                                    class="px-2.5 py-1 bg-gray-50 text-gray-700 text-xs rounded-lg border border-gray-200"
                                >
                                    {{ charge.name }}:
                                    {{ formatCurrency(charge.amount) }}
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="config.notes"
                            class="mt-3 text-xs text-gray-400 italic"
                        >
                            {{ config.notes }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 ml-4">
                        <button
                            @click="generateBills(config)"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <Play class="h-4 w-4" /> Generate Bills
                        </button>
                        <button
                            @click="destroyConfig(config)"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-white border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors"
                        >
                            <Trash2 class="h-4 w-4" /> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="configs.links?.length > 3"
            class="flex items-center justify-center gap-1 py-4"
        >
            <template v-for="link in configs.links" :key="link.label">
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
    </AuthenticatedLayout>
</template>

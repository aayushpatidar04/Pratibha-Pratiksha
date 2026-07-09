<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { FileText } from "lucide-vue-next";

const props = defineProps({ type: String, rows: Array });

const types = [
    { id: "occupancy", label: "Occupancy" },
    { id: "admissions", label: "Recent Admissions" },
    { id: "payments", label: "Payments" },
    { id: "outstanding", label: "Outstanding Fees" },
    { id: "checkins", label: "Active Check-Ins" },
];

const switchType = (t) => router.get("/reports", { type: t });

const columns = () => {
    switch (props.type) {
        case "admissions":
            return [
                "resident_code",
                "first_name",
                "last_name",
                "course",
                "status",
                "created_at",
            ];
        case "payments":
            return [
                "id",
                "amount",
                "payment_mode",
                "payment_date",
                "receipt_number",
            ];
        case "outstanding":
            return [
                "invoice_number",
                "amount",
                "paid_amount",
                "due_date",
                "status",
            ];
        case "checkins":
            return ["id", "check_in_date", "rent_amount", "status"];
        default:
            return ["id", "first_name", "last_name", "status"];
    }
};

const cell = (row, col) => {
    if (col === "resident_code" || col === "first_name" || col === "last_name")
        return row[col] ?? row.resident?.[col] ?? "—";
    return row[col] ?? "—";
};
</script>

<template>
    <Head title="Reports" />
    <AuthenticatedLayout>
        <template #header>Reports</template>

        <div class="space-y-5">
            <div>
                <h1
                    class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                >
                    <FileText class="h-6 w-6 text-blue-600" /> Reports
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Generate quick operational reports
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="t in types"
                    :key="t.id"
                    @click="switchType(t.id)"
                    class="px-3 py-1.5 text-sm rounded-lg border"
                    :class="
                        type === t.id
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'border-gray-300 text-gray-600'
                    "
                >
                    {{ t.label }}
                </button>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th
                                v-for="c in columns()"
                                :key="c"
                                class="text-left px-4 py-3 capitalize"
                            >
                                {{ c.replace("_", " ") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(row, i) in rows" :key="i">
                            <td
                                v-for="c in columns()"
                                :key="c"
                                class="px-4 py-2.5 text-gray-700"
                            >
                                {{ cell(row, c) }}
                            </td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td
                                :colspan="columns().length"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No data for this report
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

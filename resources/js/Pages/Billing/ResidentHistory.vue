<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Badge from "@/Components/Badge.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    Receipt,
    IndianRupee,
    Calendar,
    History,
    FileText,
} from "lucide-vue-next";

const props = defineProps({
    resident: Object,
    invoices: Object,
    payments: Array,
    summary: Object,
});

const statusColor = {
    pending: "amber",
    paid: "green",
    partial: "blue",
    overdue: "red",
    late_fee_pending: "purple",
    waived: "gray",
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
    <Head :title="`Payment History - ${resident.first_name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('billing.index')"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Payment History
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ resident.first_name }} {{ resident.last_name }} ({{
                            resident.resident_code
                        }})
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-4 text-center"
                >
                    <p class="text-lg font-bold">
                        {{ formatCurrency(summary.totalBilled) }}
                    </p>
                    <p class="text-xs text-gray-400">Total Billed</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-4 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ formatCurrency(summary.totalPaid) }}
                    </p>
                    <p class="text-xs text-gray-400">Total Paid</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-4 text-center"
                >
                    <p class="text-lg font-bold text-purple-600">
                        {{ formatCurrency(summary.totalLateFees) }}
                    </p>
                    <p class="text-xs text-gray-400">Late Fees</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-4 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ summary.pendingInvoices }}
                    </p>
                    <p class="text-xs text-gray-400">Pending Invoices</p>
                </div>
            </div>

            <!-- All Payments -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <h2
                    class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                >
                    <History class="h-4 w-4 text-blue-600" /> All Payments
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="payment in payments"
                        :key="payment.id"
                        class="flex items-center justify-between p-3 rounded-lg bg-gray-50"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ formatCurrency(payment.amount) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ payment.payment_date }} •
                                {{ payment.payment_mode }} •
                                {{ payment.receipt_number }}
                            </p>
                            <p
                                v-if="payment.transaction_id"
                                class="text-xs text-gray-400"
                            >
                                TXN: {{ payment.transaction_id }}
                            </p>
                            <p
                                v-if="payment.notes"
                                class="text-xs text-gray-400 italic"
                            >
                                {{ payment.notes }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                v-if="payment.invoice"
                                :href="
                                    route('billing.index', {
                                        resident_id: resident.id,
                                    })
                                "
                                class="text-xs text-blue-600 hover:underline"
                            >
                                {{ payment.invoice.invoice_number }}
                            </Link>
                            <div
                                v-if="payment.proofs?.length"
                                class="flex gap-1"
                            >
                                <a
                                    v-for="proof in payment.proofs"
                                    :key="proof.id"
                                    :href="`storage/${proof.file_path}`"
                                    target="_blank"
                                    class="p-1.5 rounded bg-white border hover:bg-gray-50"
                                    title="View Proof"
                                >
                                    <Receipt class="h-3 w-3 text-gray-500" />
                                </a>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="!payments.length"
                        class="text-sm text-gray-400 text-center py-4"
                    >
                        No payments recorded
                    </p>
                </div>
            </div>

            <!-- Invoice History -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <h2
                    class="text-sm font-semibold text-gray-900 p-4 border-b flex items-center gap-2"
                >
                    <Receipt class="h-4 w-4 text-blue-600" /> Invoice History
                </h2>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Invoice</th>
                            <th class="text-left px-4 py-3">Period</th>
                            <th class="text-left px-4 py-3">Core Amount</th>
                            <th class="text-left px-4 py-3">Late Fee</th>
                            <th class="text-left px-4 py-3">Paid</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices.data" :key="inv.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ inv.invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ inv.monthly_config?.full_label || "-" }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatCurrency(inv.amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="inv.late_fee_amount > 0"
                                    :class="
                                        inv.late_fee_waived
                                            ? 'text-green-600 line-through'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ formatCurrency(inv.late_fee_amount) }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatCurrency(inv.paid_amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :color="statusColor[inv.status] || 'gray'"
                                    >{{ inv.status }}</Badge
                                >
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a
                                        :href="route('billing.pdf.en', inv.id)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-green-600 hover:underline"
                                    >
                                        <FileText class="h-3 w-3" /> EN
                                    </a>
                                    <a
                                        :href="route('billing.print.hi', inv.id)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-purple-600 hover:underline"
                                    >
                                        <FileText class="h-3 w-3" /> HI
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No invoices found
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="invoices.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in invoices.links" :key="link.label">
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
    </AuthenticatedLayout>
</template>
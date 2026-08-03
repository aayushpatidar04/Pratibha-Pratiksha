<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    CreditCard,
    Download,
    ExternalLink,
    FileImage,
    FileText,
    IndianRupee,
    Receipt,
    ShieldCheck,
    WalletCards,
} from "lucide-vue-next";

const props = defineProps({
    payment: Object,
});

const money = (value) =>
    Number(value || 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
    });

const date = (value) => {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const dateTime = (value) => {
    if (!value) return "—";

    return new Date(value).toLocaleString("en-IN");
};

const title = (text) =>
    String(text || "")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (l) => l.toUpperCase());

const proofUrl = (proof) => {
    if (!proof.file_path) return "#";

    if (proof.file_path.startsWith("/storage")) return proof.file_path;

    return "/storage/" + proof.file_path;
};

const isImage = (proof) => {
    const ext = (proof.original_name || proof.file_path || "").toLowerCase();

    return [".jpg", ".jpeg", ".png", ".gif", ".webp"].some((e) =>
        ext.endsWith(e),
    );
};

</script>

<template>
    <Head title="Payment Details" />

    <ResidentLayout title="Payment Details">
        <div class="space-y-6">
            <!-- Header -->

            <div class="flex justify-between items-center">
                <Link
                    :href="route('resident.payments.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Back
                </Link>

                <div class="flex gap-2">

                    <a
                        :href="route('resident.billing.payments.receipt', payment.id)" target="_blank"
                        class="px-4 py-2 rounded-xl bg-indigo-600 text-white"
                    >
                        Download Receipt
                    </a>
                </div>
            </div>

            <!-- Receipt -->

            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="bg-indigo-600 text-white p-8">
                    <div class="flex justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">Payment Receipt</h1>

                            <p class="text-indigo-100 mt-1">
                                Pratibha Pratiksha Hostel
                            </p>
                        </div>

                        <BadgeCheck class="w-14 h-14" />
                    </div>
                </div>

                <div class="p-8 space-y-8">
                    <!-- Amount -->

                    <div class="text-center">
                        <p class="text-gray-500">Amount Paid</p>

                        <h2 class="text-4xl font-bold text-green-600 mt-2">
                            {{ money(payment.amount) }}
                        </h2>
                    </div>

                    <!-- Information -->

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase">
                                Receipt Number
                            </p>

                            <p class="font-semibold">
                                {{ payment.receipt_number }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400 uppercase">
                                Payment Date
                            </p>

                            <p class="font-semibold">
                                {{ date(payment.payment_date) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400 uppercase">
                                Payment Mode
                            </p>

                            <p class="font-semibold">
                                {{ title(payment.payment_mode) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400 uppercase">
                                Transaction ID
                            </p>

                            <p class="font-semibold break-all">
                                {{ payment.transaction_id || "—" }}
                            </p>
                        </div>
                    </div>

                    <!-- Invoice -->

                    <div
                        v-if="payment.invoice"
                        class="border rounded-xl p-5 bg-gray-50"
                    >
                        <h3 class="font-semibold mb-4">Linked Invoice</h3>

                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <p class="text-xs text-gray-400">Invoice No.</p>

                                <Link
                                    :href="
                                        route(
                                            'resident.billing.show',
                                            payment.invoice.id,
                                        )
                                    "
                                    class="font-semibold text-indigo-600"
                                >
                                    {{ payment.invoice.invoice_number }}
                                </Link>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Fee Type</p>

                                <p class="font-semibold">
                                    {{ title(payment.invoice.fee_type) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">
                                    Invoice Amount
                                </p>

                                <p class="font-semibold">
                                    {{ money(payment.invoice.amount) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Paid</p>

                                <p class="font-semibold text-green-600">
                                    {{ money(payment.invoice.paid_amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->

                    <div v-if="payment.notes">
                        <h3 class="font-semibold mb-2">Notes</h3>

                        <div
                            class="border rounded-xl p-4 bg-gray-50 whitespace-pre-line"
                        >
                            {{ payment.notes }}
                        </div>
                    </div>

                    <!-- Proofs -->

                    <div v-if="payment.proofs.length">
                        <h3 class="font-semibold mb-4">Payment Proofs</h3>

                        <div class="grid md:grid-cols-3 gap-5">
                            <a
                                v-for="proof in payment.proofs"
                                :key="proof.id"
                                :href="proofUrl(proof)"
                                target="_blank"
                                class="rounded-xl border overflow-hidden hover:shadow-md transition"
                            >
                                <img
                                    v-if="isImage(proof)"
                                    :src="proofUrl(proof)"
                                    class="h-48 w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="h-48 flex items-center justify-center bg-gray-100"
                                >
                                    <FileText class="w-12 h-12 text-gray-400" />
                                </div>

                                <div class="p-3">
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <div>
                                            <p class="font-medium truncate">
                                                {{ proof.original_name }}
                                            </p>

                                            <p class="text-xs text-gray-400">
                                                {{ dateTime(proof.created_at) }}
                                            </p>
                                        </div>

                                        <ExternalLink class="w-4 h-4" />
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ResidentLayout>
</template>

<style scoped>
@media print {
    aside,
    header {
        display: none !important;
    }

    body {
        background: white;
    }
}
</style>

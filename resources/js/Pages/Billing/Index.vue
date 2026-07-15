<!-- resources/js/Pages/Billing/Index.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import {
    Receipt,
    Plus,
    Wallet,
    IndianRupee,
    Trash2,
    CreditCard,
    FileText,
    Languages,
    Calendar,
    Settings,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Upload,
    Eye,
    Ban,
    User,
    History,
    RotateCcw,
} from "lucide-vue-next";

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
    residents: Array,
    monthlyConfigs: Array,
});

const statusColor = {
    pending: "amber",
    paid: "green",
    partial: "blue",
    overdue: "red",
    late_fee_pending: "purple",
    waived: "gray",
};

const statusLabel = {
    pending: "Pending",
    paid: "Paid",
    partial: "Partial",
    overdue: "Overdue",
    late_fee_pending: "Late Fee Pending",
    waived: "Waived",
};

const filters = reactive({
    status: props.filters?.status || "all",
    month: props.filters?.month || "",
    year: props.filters?.year || "",
    deleted: props.filters?.deleted || "active",
});
const applyFilters = () =>
    router.get(
        "/billing",
        {
            status: filters.status !== "all" ? filters.status : undefined,
            month: filters.month || undefined,
            year: filters.year || undefined,
            deleted: filters.deleted !== "active" ? filters.deleted : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );

const createOpen = ref(false);
const payOpen = ref(false);
const waiveOpen = ref(false);
const payingInvoice = ref(null);
const waivingInvoice = ref(null);
const paymentProofs = ref([]);

const createForm = useForm({
    resident_id: "",
    rent_amount: "",
    mess_amount: "",
    other_amount: "",
    other_title: "",
    due_date: "",
    description: "",
    late_fee_per_day: "",
});

const submitCreate = () =>
    createForm.post("/billing", {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const payForm = useForm({
    amount: "",
    payment_mode: "cash",
    transaction_id: "",
    payment_date: new Date().toISOString().slice(0, 10),
    notes: "",
    proofs: [],
});

const openPay = (inv) => {
    payingInvoice.value = inv;
    payForm.amount = inv.balance_due || 0;
    payForm.proofs = [];
    paymentProofs.value = [];
    payOpen.value = true;
};

const handleProofUpload = (e) => {
    const files = Array.from(e.target.files);
    payForm.proofs = files;
    paymentProofs.value = files.map((f) => URL.createObjectURL(f));
};

const submitPay = () => {
    const formData = new FormData();
    formData.append("amount", payForm.amount);
    formData.append("payment_mode", payForm.payment_mode);
    formData.append("transaction_id", payForm.transaction_id);
    formData.append("payment_date", payForm.payment_date);
    formData.append("notes", payForm.notes);

    payForm.proofs.forEach((file, i) => {
        formData.append(`proofs[${i}]`, file);
    });

    payForm.post(`/billing/${payingInvoice.value.id}/payments`, formData, {
        onSuccess: (response) => {
            payOpen.value = false;
            payForm.reset();
            paymentProofs.value = [];
        },
        onError: (errors) => {
            console.error("Payment validation errors:", errors);
        },
        onFinish: () => {
            console.log("Request finished");
        },
        preserveScroll: true,
    });
};

const openWaive = (inv) => {
    waivingInvoice.value = inv;
    waiveOpen.value = true;
};

const waiveForm = useForm({
    reason: "",
});

const submitWaive = () => {
    waiveForm.post(`/billing/${waivingInvoice.value.id}/waive-late-fee`, {
        onSuccess: () => {
            waiveOpen.value = false;
            waiveForm.reset();
        },
    });
};

const destroy = (inv) => {
    if (confirm(`Move invoice ${inv.invoice_number} to deleted invoices?`)) {
        router.delete(route("billing.destroy", inv.id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (amount) => {
    return (
        "₹" +
        Number(amount || 0).toLocaleString("en-IN", {
            minimumFractionDigits: 2,
        })
    );
};

const restoreInvoice = (inv) => {
    if (!confirm(`Restore invoice ${inv.invoice_number}?`)) {
        return;
    }

    router.patch(
        route("billing.restore", inv.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const paymentIsOverdue = computed(() => {
    if (!payingInvoice.value || !payForm.payment_date) {
        return false;
    }

    const paymentDate = new Date(`${payForm.payment_date}T00:00:00`);

    const dueDate = new Date(`${payingInvoice.value.due_date}T00:00:00`);

    return (
        paymentDate > dueDate &&
        Number(payingInvoice.value.paid_before_due_date || 0) <
            Number(payingInvoice.value.amount || 0)
    );
});

const paymentLateFee = computed(() => {
    if (!payingInvoice.value || payingInvoice.value.late_fee_waived) {
        return 0;
    }

    if (!paymentIsOverdue.value) {
        return 0;
    }

    const perDay = Number(payingInvoice.value.late_fee_per_day || 0);

    if (perDay > 0) {
        const due = new Date(`${payingInvoice.value.due_date}T00:00:00`);
        const paid = new Date(`${payForm.payment_date}T00:00:00`);
        const daysLate = Math.round((paid - due) / (1000 * 60 * 60 * 24));
        return daysLate > 0 ? Math.round(daysLate * perDay * 100) / 100 : 0;
    }

    return Number(payingInvoice.value?.late_fee_amount || 0);
});

const paymentTotalPayable = computed(() => {
    return Number(payingInvoice.value?.amount || 0) + paymentLateFee.value;
});

const paymentBalanceDue = computed(() => {
    return Math.max(
        0,
        paymentTotalPayable.value -
            Number(payingInvoice.value?.paid_amount || 0),
    );
});
</script>

<template>
    <Head title="Billing" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Billing</h1>
                    <p class="text-sm text-gray-500">
                        Fee invoices and payment collection
                    </p>
                </div>
            </div>
        </template>
        <div class="mb-2">
            <div class="flex gap-2 justify-end">
                <Link
                    :href="route('billing.config.index')"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600 hover:bg-gray-50"
                >
                    <Settings class="h-4 w-4" /> Monthly Config
                </Link>
                <PrimaryButton type="button" @click="createOpen = true">
                    <Plus class="h-4 w-4" /> New Invoice
                </PrimaryButton>
            </div>
        </div>

        <div class="space-y-5">
            <!-- Enhanced Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold">
                        {{ formatCurrency(stats.totalBilled) }}
                    </p>
                    <p class="text-xs text-gray-400">Total Billed</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        {{ formatCurrency(stats.paidAmount) }}
                    </p>
                    <p class="text-xs text-gray-400">Collected</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-amber-600">
                        {{ stats.pendingCount }}
                    </p>
                    <p class="text-xs text-gray-400">Pending</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-blue-600">
                        {{ stats.partialCount }}
                    </p>
                    <p class="text-xs text-gray-400">Partial</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-purple-600">
                        {{ stats.lateFeePendingCount }}
                    </p>
                    <p class="text-xs text-gray-400">Late Fee Pending</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.overdueCount }}
                    </p>
                    <p class="text-xs text-gray-400">Overdue</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select
                    v-model="filters.status"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="late_fee_pending">Late Fee Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="waived">Waived</option>
                </select>
                <select
                    v-model="filters.month"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Months</option>
                    <option v-for="m in 12" :key="m" :value="m">
                        {{
                            new Date(2026, m - 1).toLocaleString("default", {
                                month: "long",
                            })
                        }}
                    </option>
                </select>
                <select
                    v-model="filters.year"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="">All Years</option>
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                </select>
                <select
                    v-model="filters.deleted"
                    @change="applyFilters"
                    class="rounded-lg border-gray-300 text-sm"
                >
                    <option value="active">Active Invoices</option>
                    <option value="only">
                        Deleted Invoices ({{ stats.deletedCount || 0 }})
                    </option>
                    <option value="with">All Including Deleted</option>
                </select>
            </div>

            <!-- Table -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Invoice</th>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Items</th>
                            <th class="text-left px-4 py-3">Amount</th>
                            <th class="text-left px-4 py-3">Due</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="inv in invoices.data"
                            :key="inv.id"
                            :class="{
                                'bg-red-50/40 opacity-75': inv.deleted_at,
                            }"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">
                                    {{ inv.invoice_number }}
                                </p>
                                <span
                                    v-if="inv.deleted_at"
                                    class="inline-flex mt-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700"
                                >
                                    Deleted
                                </span>
                                <p
                                    v-if="inv.monthly_config"
                                    class="text-xs text-gray-400"
                                >
                                    {{ inv.monthly_config.full_label }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="text-gray-600">
                                        {{ inv.resident?.first_name }}
                                        {{ inv.resident?.last_name }}
                                    </div>
                                    <Link
                                        v-if="inv.resident_id"
                                        :href="
                                            route(
                                                'billing.resident.history',
                                                inv.resident_id,
                                            )
                                        "
                                        class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-blue-600"
                                        title="View Payment History"
                                    >
                                        <History class="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="space-y-1">
                                    <div
                                        v-for="item in inv.items"
                                        :key="item.id"
                                        class="text-xs"
                                        :class="
                                            item.is_late_fee
                                                ? 'text-red-600 font-medium'
                                                : 'text-gray-600'
                                        "
                                    >
                                        {{ item.title }}:
                                        {{ formatCurrency(item.amount) }}
                                        <span
                                            v-if="item.is_late_fee"
                                            class="text-[10px] bg-red-50 px-1 rounded"
                                        >
                                            Late Fee
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900 font-medium">
                                    {{ formatCurrency(inv.amount) }}
                                </p>
                                <p
                                    v-if="
                                        inv.is_overdue &&
                                        Number(inv.late_fee_amount) > 0 &&
                                        !inv.late_fee_waived
                                    "
                                    class="text-xs text-red-600"
                                >
                                    + {{ formatCurrency(inv.late_fee_amount) }}
                                    late fee applied
                                </p>

                                <p
                                    v-else-if="
                                        Number(inv.late_fee_amount) > 0 &&
                                        !inv.late_fee_waived
                                    "
                                    class="text-xs text-gray-400"
                                >
                                    Late fee after due date:
                                    {{ formatCurrency(inv.late_fee_amount) }}
                                </p>
                                <p
                                    v-if="inv.late_fee_waived"
                                    class="text-xs text-green-600 line-through"
                                >
                                    {{ formatCurrency(inv.late_fee_amount) }}
                                    waived
                                </p>
                                <p class="text-xs text-gray-400">
                                    Paid: {{ formatCurrency(inv.paid_amount) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-3 w-3" />
                                    {{ inv.due_date }}
                                </div>
                                <p
                                    v-if="inv.is_overdue"
                                    class="text-red-600 font-medium mt-1"
                                >
                                    Overdue!
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[inv.status]">
                                    {{ statusLabel[inv.status] || inv.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <div
                                        class="flex justify-end items-center gap-2 flex-wrap"
                                        v-if="!inv.deleted_at"
                                    >
                                        <!-- Record Payment -->
                                        <button
                                            v-if="inv.status !== 'paid'"
                                            @click="openPay(inv)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                                        >
                                            <CreditCard class="h-3.5 w-3.5" />
                                            Pay
                                        </button>

                                        <!-- Waive Late Fee -->
                                        <button
                                            v-if="
                                                inv.late_fee_amount > 0 &&
                                                !inv.late_fee_waived &&
                                                inv.status ===
                                                    'late_fee_pending'
                                            "
                                            @click="openWaive(inv)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700"
                                        >
                                            <Ban class="h-3.5 w-3.5" /> Waive
                                        </button>

                                        <!-- View Payments -->
                                        <button
                                            v-if="inv.payments?.length"
                                            @click="
                                                inv.showPayments =
                                                    !inv.showPayments
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            {{ inv.payments.length }} Payments
                                        </button>

                                        <!-- PDFs -->
                                        <a
                                            :href="`/billing/${inv.id}/pdf/en`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                        >
                                            <FileText class="h-3.5 w-3.5" /> EN
                                        </a>
                                        <!-- <a
                                            :href="`/billing/${inv.id}/pdf/hi`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700"
                                        >
                                            <Languages class="h-3.5 w-3.5" /> HI
                                        </a> -->
                                        <a
                                            :href="
                                                route(
                                                    'billing.print.hi',
                                                    inv.id,
                                                )
                                            "
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-purple-700"
                                        >
                                            <Languages class="h-3.5 w-3.5" />
                                            हिंदी
                                        </a>

                                        <button
                                            @click="destroy(inv)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button
                                            type="button"
                                            @click="restoreInvoice(inv)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            Restore
                                        </button>
                                    </div>
                                </div>

                                <!-- Payment History -->
                                <div
                                    v-if="inv.showPayments"
                                    class="mt-3 p-3 bg-gray-50 rounded-lg"
                                >
                                    <p
                                        class="text-xs font-medium text-gray-700 mb-2"
                                    >
                                        Payment History
                                    </p>
                                    <div
                                        v-for="payment in inv.payments"
                                        :key="payment.id"
                                        class="flex items-center justify-between py-1.5 border-b border-gray-200 last:border-0"
                                    >
                                        <div>
                                            <p class="text-xs text-gray-900">
                                                {{
                                                    formatCurrency(
                                                        payment.amount,
                                                    )
                                                }}
                                                via {{ payment.payment_mode }}
                                            </p>
                                            <p
                                                class="text-[10px] text-gray-500"
                                            >
                                                {{ payment.payment_date }} •
                                                <a
                                                    :href="
                                                        route(
                                                            'billing.payments.receipt',
                                                            payment.id,
                                                        )
                                                    "
                                                    target="_blank"
                                                    ><u>{{
                                                        payment.receipt_number
                                                    }}</u></a
                                                >
                                            </p>
                                            <p
                                                v-if="payment.transaction_id"
                                                class="text-[10px] text-gray-400"
                                            >
                                                TXN:
                                                {{ payment.transaction_id }}
                                            </p>
                                        </div>
                                        <div class="flex gap-1">
                                            <a
                                                v-for="proof in payment.proofs"
                                                :key="proof.id"
                                                :href="`storage/${proof.file_path}`"
                                                target="_blank"
                                                class="p-1 rounded bg-white border hover:bg-gray-50"
                                            >
                                                <Upload
                                                    class="h-3 w-3 text-gray-500"
                                                />
                                            </a>
                                        </div>
                                    </div>
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

        <!-- Create Modal -->
        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">New Invoice</h2>

                <!-- Global Errors -->
                <div
                    v-if="Object.keys(createForm.errors).length > 0"
                    class="p-3 bg-red-50 border border-red-200 rounded-lg"
                >
                    <p class="text-sm text-red-700 font-medium">
                        Please fix the following errors:
                    </p>
                    <ul class="text-xs text-red-600 mt-1 list-disc list-inside">
                        <li
                            v-for="(error, field) in createForm.errors"
                            :key="field"
                        >
                            {{ error }}
                        </li>
                    </ul>
                </div>

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
                            {{ r.first_name }} {{ r.last_name }} ({{
                                r.resident_code
                            }})
                        </option>
                    </select>
                    <InputError :message="createForm.errors.resident_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Rent" />
                        <TextInput
                            type="number"
                            v-model="createForm.rent_amount"
                        />
                        <InputError :message="createForm.errors.rent_amount" />
                    </div>
                    <div>
                        <InputLabel value="Mess" />
                        <TextInput
                            type="number"
                            v-model="createForm.mess_amount"
                        />
                        <InputError :message="createForm.errors.mess_amount" />
                    </div>
                    <div>
                        <InputLabel value="Other Amount" />
                        <TextInput
                            type="number"
                            v-model="createForm.other_amount"
                        />
                        <InputError :message="createForm.errors.other_amount" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Other Title" />
                    <TextInput
                        v-model="createForm.other_title"
                        placeholder="Electricity, Fine, etc."
                    />
                    <InputError :message="createForm.errors.other_title" />
                </div>
                <div>
                    <InputLabel value="Due Date *" />
                    <TextInput
                        type="date"
                        v-model="createForm.due_date"
                        required
                    />
                    <InputError :message="createForm.errors.due_date" />
                </div>
                <div>
                    <InputLabel value="Late Fee Per Day" />

                    <TextInput
                        type="number"
                        v-model="createForm.late_fee_per_day"
                        min="0"
                        step="0.01"
                        class="w-full"
                        placeholder="e.g. 50"
                    />

                    <p class="text-xs text-gray-400 mt-1">
                        Charged for every day the invoice remains unpaid after
                        the due date.
                    </p>

                    <InputError :message="createForm.errors.late_fee_per_day" />
                </div>
                <div>
                    <InputLabel value="Description" />
                    <textarea
                        v-model="createForm.description"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
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
                        >Create Invoice</PrimaryButton
                    >
                </div>
            </form>
        </Modal>

        <!-- Payment Modal with Proof Upload -->
        <Modal :show="payOpen" @close="payOpen = false">
            <form
                @submit.prevent="submitPay"
                class="p-6 space-y-4"
                v-if="payingInvoice"
            >
                <h2
                    class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                >
                    <IndianRupee class="h-4 w-4" /> Record Payment —
                    {{ payingInvoice.invoice_number }}
                </h2>

                <!-- Global Errors -->
                <div
                    v-if="Object.keys(payForm.errors).length > 0"
                    class="p-3 bg-red-50 border border-red-200 rounded-lg"
                >
                    <p class="text-sm text-red-700 font-medium">
                        Please fix the following errors:
                    </p>
                    <ul class="text-xs text-red-600 mt-1 list-disc list-inside">
                        <li
                            v-for="(error, field) in payForm.errors"
                            :key="field"
                        >
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 text-sm">
                    <p>
                        Core Amount:
                        <span class="font-bold">{{
                            formatCurrency(payingInvoice.amount)
                        }}</span>
                    </p>
                    <p v-if="paymentIsOverdue && paymentLateFee > 0">
                        Applied Late Fee:
                        <span class="font-bold text-red-600">
                            {{ formatCurrency(paymentLateFee) }}
                        </span>
                        <span
                            v-if="Number(payingInvoice.late_fee_per_day) > 0"
                            class="text-xs text-gray-400"
                        >
                            ({{
                                formatCurrency(payingInvoice.late_fee_per_day)
                            }}/day)
                        </span>
                    </p>

                    <p
                        v-else-if="
                            Number(payingInvoice.late_fee_amount) > 0 &&
                            !payingInvoice.late_fee_waived
                        "
                    >
                        Late Fee if paid after {{ payingInvoice.due_date }}:
                        <span class="font-medium text-gray-500">
                            {{ formatCurrency(payingInvoice.late_fee_amount) }}
                        </span>
                    </p>

                    <p>
                        Total Payable:
                        <span class="font-bold">
                            {{ formatCurrency(paymentTotalPayable) }}
                        </span>
                    </p>

                    <p>
                        Balance Due:
                        <span class="font-bold text-amber-600">
                            {{ formatCurrency(paymentBalanceDue) }}
                        </span>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Amount *" />
                        <TextInput
                            type="number"
                            v-model="payForm.amount"
                            :max="paymentBalanceDue"
                            step="0.01"
                            required
                        />
                        <InputError :message="payForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Mode *" />
                        <select
                            v-model="payForm.payment_mode"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                        <InputError :message="payForm.errors.payment_mode" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Payment Date *" />
                        <TextInput
                            type="date"
                            v-model="payForm.payment_date"
                            required
                        />
                        <InputError :message="payForm.errors.payment_date" />
                    </div>
                    <div>
                        <InputLabel value="Transaction ID" />
                        <TextInput v-model="payForm.transaction_id" />
                        <InputError :message="payForm.errors.transaction_id" />
                    </div>
                </div>

                <!-- Proof Upload -->
                <div>
                    <InputLabel value="Payment Proof (Screenshots/Photos)" />
                    <input
                        type="file"
                        multiple
                        accept="image/*"
                        @change="handleProofUpload"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100"
                    />
                    <div v-if="paymentProofs.length" class="flex gap-2 mt-2">
                        <img
                            v-for="(url, i) in paymentProofs"
                            :key="i"
                            :src="url"
                            class="h-16 w-16 object-cover rounded-lg border"
                        />
                    </div>
                    <InputError :message="payForm.errors.proofs" />
                    <InputError :message="payForm.errors['proofs.0']" />
                </div>

                <div>
                    <InputLabel value="Notes" />
                    <textarea
                        v-model="payForm.notes"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 text-sm"
                    ></textarea>
                    <InputError :message="payForm.errors.notes" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="payOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="payForm.processing">
                        <Wallet class="h-4 w-4 mr-1" /> Record Payment
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Waive Late Fee Modal -->
        <Modal :show="waiveOpen" @close="waiveOpen = false">
            <form
                @submit.prevent="submitWaive"
                class="p-6 space-y-4"
                v-if="waivingInvoice"
            >
                <h2
                    class="text-lg font-semibold text-gray-900 flex items-center gap-2 text-amber-600"
                >
                    <Ban class="h-5 w-5" /> Waive Late Fee
                </h2>

                <!-- Global Errors -->
                <div
                    v-if="Object.keys(waiveForm.errors).length > 0"
                    class="p-3 bg-red-50 border border-red-200 rounded-lg"
                >
                    <p class="text-sm text-red-700 font-medium">
                        Please fix the following errors:
                    </p>
                    <ul class="text-xs text-red-600 mt-1 list-disc list-inside">
                        <li
                            v-for="(error, field) in waiveForm.errors"
                            :key="field"
                        >
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <div class="bg-amber-50 rounded-lg p-3">
                    <p class="text-sm text-amber-800">
                        Invoice: {{ waivingInvoice.invoice_number }}
                    </p>
                    <p class="text-sm font-bold text-amber-900">
                        Late Fee Amount:
                        {{ formatCurrency(waivingInvoice.late_fee_amount) }}
                    </p>
                    <p class="text-xs text-amber-700 mt-1">
                        Core amount must be paid before waiving late fee.
                    </p>
                </div>

                <div>
                    <InputLabel value="Reason for Waiving *" />
                    <textarea
                        v-model="waiveForm.reason"
                        rows="3"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Enter reason for waiving late fee..."
                    ></textarea>
                    <InputError :message="waiveForm.errors.reason" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="waiveOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton
                        :disabled="waiveForm.processing"
                        class="bg-amber-600 hover:bg-amber-700"
                    >
                        <CheckCircle class="h-4 w-4 mr-1" /> Waive Late Fee
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

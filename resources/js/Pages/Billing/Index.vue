<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import {
    Receipt,
    Plus,
    Wallet,
    IndianRupee,
    Trash2,
    CreditCard,
    FileText,
    Languages,
} from "lucide-vue-next";

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
    residents: Array,
});

const statusColor = {
    pending: "amber",
    paid: "green",
    partial: "blue",
    overdue: "red",
    waived: "gray",
};

const filters = reactive({ status: props.filters?.status || "all" });
const applyFilters = () =>
    router.get(
        "/billing",
        { status: filters.status !== "all" ? filters.status : undefined },
        { preserveState: true, replace: true },
    );

const createOpen = ref(false);
const payOpen = ref(false);
const payingInvoice = ref(null);

const createForm = useForm({
    resident_id: "",
    rent_amount: "",
    mess_amount: "",
    caution_money: "",
    other_amount: "",
    other_title: "",
    due_date: "",
    description: "",
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
});
const openPay = (inv) => {
    payingInvoice.value = inv;
    payForm.amount = (inv.amount - inv.paid_amount).toFixed(2);
    payOpen.value = true;
};
const submitPay = () =>
    payForm.post(`/billing/${payingInvoice.value.id}/payments`, {
        onSuccess: () => (payOpen.value = false),
    });

const destroy = (inv) => {
    if (confirm("Delete this invoice?")) router.delete(`/billing/${inv.id}`);
};
</script>

<template>
    <Head title="Billing" />
    <AuthenticatedLayout>
        <template #header>Billing</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <Receipt class="h-6 w-6 text-blue-600" /> Billing
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Fee invoices and payment collection
                    </p>
                </div>
                <PrimaryButton type="button" @click="createOpen = true"
                    ><Plus class="h-4 w-4" /> New Invoice</PrimaryButton
                >
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold">
                        ₹{{ Number(stats.totalAmount).toLocaleString("en-IN") }}
                    </p>
                    <p class="text-xs text-gray-400">Total Billed</p>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 p-3 text-center"
                >
                    <p class="text-lg font-bold text-green-600">
                        ₹{{ Number(stats.paidAmount).toLocaleString("en-IN") }}
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
                    <p class="text-lg font-bold text-red-600">
                        {{ stats.overdueCount }}
                    </p>
                    <p class="text-xs text-gray-400">Overdue</p>
                </div>
            </div>

            <select
                v-model="filters.status"
                @change="applyFilters"
                class="rounded-lg border-gray-300 text-sm w-52"
            >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="waived">Waived</option>
            </select>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Invoice</th>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Amount</th>
                            <th class="text-left px-4 py-3">Due</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices.data" :key="inv.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ inv.invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ inv.resident?.first_name }}
                                {{ inv.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 capitalize">
                                <div class="space-y-1">
                                    <div
                                        v-for="item in inv.items"
                                        :key="item.id"
                                        class="text-xs text-gray-600"
                                    >
                                        {{ item.title }}: ₹{{
                                            Number(item.amount).toLocaleString(
                                                "en-IN",
                                            )
                                        }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                ₹{{
                                    Number(inv.amount).toLocaleString("en-IN")
                                }}
                                <span class="text-xs text-gray-400"
                                    >(paid ₹{{
                                        Number(inv.paid_amount).toLocaleString(
                                            "en-IN",
                                        )
                                    }})</span
                                >
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ inv.due_date }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :color="statusColor[inv.status]">{{
                                    inv.status
                                }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex justify-end items-center gap-2"
                                >
                                    <!-- Record Payment -->
                                    <button
                                        v-if="inv.status !== 'paid'"
                                        @click="openPay(inv)"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition"
                                    >
                                        <CreditCard class="h-3.5 w-3.5" />
                                        Payment
                                    </button>

                                    <!-- English PDF -->
                                    <a
                                        :href="`/billing/${inv.id}/pdf/en`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700 transition"
                                    >
                                        <FileText class="h-3.5 w-3.5" />
                                        EN
                                    </a>

                                    <!-- Hindi PDF -->
                                    <a
                                        :href="`/billing/${inv.id}/pdf/hi`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 transition"
                                    >
                                        <Languages class="h-3.5 w-3.5" />
                                        हिंदी
                                    </a>

                                    <!-- Delete -->
                                    <button
                                        @click="destroy(inv)"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 transition"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Delete
                                    </button>
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

        <Modal :show="createOpen" @close="createOpen = false">
            <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">New Invoice</h2>
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
                        <InputLabel value="Rent Amount" />
                        <TextInput
                            type="number"
                            v-model="createForm.rent_amount"
                        />
                        <InputError :message="createForm.errors.rent_amount" />
                    </div>

                    <div>
                        <InputLabel value="Mess Amount" />
                        <TextInput
                            type="number"
                            v-model="createForm.mess_amount"
                        />
                        <InputError :message="createForm.errors.mess_amount" />
                    </div>

                    <div>
                        <InputLabel value="Caution Money" />
                        <TextInput
                            type="number"
                            v-model="createForm.caution_money"
                        />
                        <InputError
                            :message="createForm.errors.caution_money"
                        />
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
                        placeholder="Electricity, Fine, Extra Charges etc."
                    />
                    <InputError :message="createForm.errors.other_title" />
                </div>
                <div>
                    <InputLabel value="Due Date *" /><TextInput
                        type="date"
                        v-model="createForm.due_date"
                        required
                    />
                </div>
                <div>
                    <InputLabel value="Description" /><textarea
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Amount *" /><TextInput
                            type="number"
                            v-model="payForm.amount"
                            required
                        />
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
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Payment Date *" /><TextInput
                            type="date"
                            v-model="payForm.payment_date"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="Transaction ID" /><TextInput
                            v-model="payForm.transaction_id"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="payOpen = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton :disabled="payForm.processing"
                        ><Wallet class="h-4 w-4" /> Record
                        Payment</PrimaryButton
                    >
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

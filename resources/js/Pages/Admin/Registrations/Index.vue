<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    Search,
    Eye,
    CheckCircle,
    XCircle,
    Banknote,
    IndianRupee,
    AlertTriangle,
    Phone,
    User,
} from "lucide-vue-next";
import { ref } from "vue";

const props = defineProps({
    applications: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "all");

const statusColors = {
    pending: "bg-amber-100 text-amber-800 border-amber-200",
    paid: "bg-blue-100 text-blue-800 border-blue-200",
    approved: "bg-green-100 text-green-800 border-green-200",
    rejected: "bg-red-100 text-red-800 border-red-200",
};

const paymentStatusColors = {
    pending_verification: "bg-orange-100 text-orange-700",
    paid: "bg-green-100 text-green-700",
    failed: "bg-red-100 text-red-700",
};

const applyFilters = () => {
    router.get(
        "/registrations",
        { search: search.value, status: statusFilter.value },
        { preserveState: true, replace: true },
    );
};

let searchTimeout = null;
const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};

// Rejecting can be done right from the list. Approving needs a room/bed picked,
// so that always happens on the detail page instead of a one-click action here.
const reject = (id) => {
    const remarks = prompt("Rejection reason (optional):");
    if (remarks === null) return; // cancelled
    router.post(
        `/registrations/${id}/reject`,
        { remarks },
        { preserveScroll: true },
    );
};

const markCashPaid = (id) => {
    if (!confirm("Mark this cash payment as received?")) return;
    router.post(
        `/registrations/${id}/mark-cash-paid`,
        {},
        { preserveScroll: true },
    );
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatCurrency = (amount) =>
    "₹" +
    Number(amount || 0).toLocaleString("en-IN", { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Registration Applications" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    Registration Applications
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Review and process incoming resident registrations
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">Pending Review</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ stats.pending }}
                            </p>
                        </div>
                        <div
                            class="h-9 w-9 rounded-lg bg-amber-50 flex items-center justify-center"
                        >
                            <AlertTriangle class="h-4.5 w-4.5 text-amber-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">Paid (Online)</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ stats.paid }}
                            </p>
                        </div>
                        <div
                            class="h-9 w-9 rounded-lg bg-blue-50 flex items-center justify-center"
                        >
                            <IndianRupee class="h-4.5 w-4.5 text-blue-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">Approved</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ stats.approved }}
                            </p>
                        </div>
                        <div
                            class="h-9 w-9 rounded-lg bg-green-50 flex items-center justify-center"
                        >
                            <CheckCircle class="h-4.5 w-4.5 text-green-600" />
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">Cash Pending</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ stats.cash_pending }}
                            </p>
                        </div>
                        <div
                            class="h-9 w-9 rounded-lg bg-orange-50 flex items-center justify-center"
                        >
                            <Banknote class="h-4.5 w-4.5 text-orange-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-4"
            >
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                        />
                        <input
                            v-model="search"
                            @input="debounceSearch"
                            type="text"
                            placeholder="Search by name, reference, mobile..."
                            class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 w-32"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100"
                        >
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">Fee</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Applied</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="app in applications.data"
                                :key="app.id"
                                class="hover:bg-gray-50/50"
                            >
                                <td class="px-4 py-3">
                                    <span
                                        class="font-mono text-xs font-semibold text-gray-900 bg-gray-100 px-2 py-1 rounded"
                                        >{{ app.application_no }}</span
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img
                                            v-if="app.student_photo"
                                            :src="`/storage/${app.student_photo}`"
                                            class="w-16 h-16 rounded-full object-cover border border-gray-200"
                                        />
                                        <div
                                            v-else
                                            class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center"
                                        >
                                            <User
                                                class="w-4 h-4 text-gray-400"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{ app.student_name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ app.institution_name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        class="flex items-center gap-1 text-gray-600"
                                    >
                                        <Phone class="w-3.5 h-3.5" /><span>{{
                                            app.student_mobile
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="
                                                app.payment_method ===
                                                'razorpay'
                                            "
                                            class="text-blue-600"
                                            ><IndianRupee class="w-3.5 h-3.5"
                                        /></span>
                                        <span v-else class="text-green-600"
                                            ><Banknote class="w-3.5 h-3.5"
                                        /></span>
                                        <span class="text-xs capitalize">{{
                                            app.payment_method
                                        }}</span>
                                    </div>
                                    <span
                                        class="inline-block mt-1 text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                                        :class="
                                            paymentStatusColors[
                                                app.payment_status
                                            ] || 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            app.payment_status?.replace(
                                                "_",
                                                " ",
                                            ) ?? "N/A"
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ formatCurrency(app.registration_fee) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        :class="statusColors[app.status]"
                                        >{{ app.status }}</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ formatDate(app.created_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="`/registrations/${app.id}`"
                                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-blue-600"
                                            title="View Details"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>

                                        <button
                                            v-if="
                                                app.payment_method === 'cash' &&
                                                app.payment_status ===
                                                    'pending_verification'
                                            "
                                            @click="markCashPaid(app.id)"
                                            class="p-1.5 rounded-lg hover:bg-green-50 text-green-600"
                                            title="Mark Cash Paid"
                                        >
                                            <Banknote class="w-4 h-4" />
                                        </button>

                                        <!-- Approve always goes through the detail page — a room/bed has to be picked -->
                                        <Link
                                            v-if="app.status !== 'approved'"
                                            :href="`/registrations/${app.id}`"
                                            class="p-1.5 rounded-lg hover:bg-green-50 text-green-600"
                                            title="Review & Approve"
                                        >
                                            <CheckCircle class="w-4 h-4" />
                                        </Link>

                                        <button
                                            v-if="
                                                app.status !== 'rejected' &&
                                                app.status !== 'approved'
                                            "
                                            @click="reject(app.id)"
                                            class="p-1.5 rounded-lg hover:bg-red-50 text-red-600"
                                            title="Reject"
                                        >
                                            <XCircle class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!applications.data.length">
                                <td
                                    colspan="8"
                                    class="px-4 py-12 text-center text-gray-400"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Search class="w-8 h-8 text-gray-300" />
                                        <p>No applications found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="applications.links?.length > 3"
                    class="px-4 py-3 border-t border-gray-100 flex items-center justify-between"
                >
                    <div class="text-xs text-gray-500">
                        Showing {{ applications.from }} to
                        {{ applications.to }} of
                        {{ applications.total }} entries
                    </div>
                    <div class="flex items-center gap-1">
                        <template
                            v-for="(link, i) in applications.links"
                            :key="i"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded-lg text-xs font-medium"
                                :class="
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-600 hover:bg-gray-100'
                                "
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1 rounded-lg text-xs font-medium opacity-50"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

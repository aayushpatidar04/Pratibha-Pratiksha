<!-- resources/js/Pages/Admin/Registrations/Show.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    User,
    Phone,
    MapPin,
    GraduationCap,
    Building2,
    Calendar,
    IndianRupee,
    Banknote,
    CreditCard,
    CheckCircle,
    XCircle,
} from "lucide-vue-next";

const props = defineProps({
    application: Object,
});

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "long",
        year: "numeric",
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
    <Head :title="`Application #${application.reference_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.registrations.index')"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Application Details
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ application.reference_number }}
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Status Banner -->
            <div
                class="rounded-xl p-4 flex items-center justify-between"
                :class="{
                    'bg-amber-50 border border-amber-200':
                        application.status === 'pending',
                    'bg-green-50 border border-green-200':
                        application.status === 'approved',
                    'bg-red-50 border border-red-200':
                        application.status === 'rejected',
                }"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="text-sm font-medium"
                        :class="{
                            'text-amber-800': application.status === 'pending',
                            'text-green-800': application.status === 'approved',
                            'text-red-800': application.status === 'rejected',
                        }"
                    >
                        Status: {{ application.status }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="application.status === 'pending'"
                        @click="
                            $inertia.post(
                                route(
                                    'admin.registrations.approve',
                                    application.id,
                                ),
                            )
                        "
                        class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700"
                    >
                        Approve
                    </button>
                    <button
                        v-if="application.status !== 'rejected'"
                        @click="
                            $inertia.post(
                                route(
                                    'admin.registrations.reject',
                                    application.id,
                                ),
                            )
                        "
                        class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Photos -->
                <div
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4"
                >
                    <h3 class="text-sm font-semibold text-gray-900">Photos</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Student</p>
                            <img
                                v-if="application.student_photo"
                                :src="`/storage/${application.student_photo}`"
                                class="w-full h-40 object-cover rounded-lg border"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Father</p>
                                <img
                                    v-if="application.father_photo"
                                    :src="`/storage/${application.father_photo}`"
                                    class="w-full h-24 object-cover rounded-lg border"
                                />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Mother</p>
                                <img
                                    v-if="application.mother_photo"
                                    :src="`/storage/${application.mother_photo}`"
                                    class="w-full h-24 object-cover rounded-lg border"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <User class="w-4 h-4 text-blue-500" /> Personal
                            Information
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Name:</span>
                                <span class="font-medium">{{
                                    application.student_name
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Father:</span>
                                <span class="font-medium">{{
                                    application.father_name
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Mother:</span>
                                <span class="font-medium">{{
                                    application.mother_name || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">DOB:</span>
                                <span class="font-medium">{{
                                    formatDate(application.dob)
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Age:</span>
                                <span class="font-medium">{{
                                    application.age
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Blood Group:</span>
                                <span class="font-medium">{{
                                    application.blood_group
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-500">Address:</span>
                                <span class="font-medium">{{
                                    application.permanent_address
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <Phone class="w-4 h-4 text-blue-500" /> Contact
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Student:</span>
                                <span class="font-medium">{{
                                    application.student_mobile
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Father:</span>
                                <span class="font-medium">{{
                                    application.father_mobile || "-"
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Mother:</span>
                                <span class="font-medium">{{
                                    application.mother_mobile || "-"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Academic -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <GraduationCap class="w-4 h-4 text-blue-500" />
                            Academic
                        </h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Institution:</span>
                                <span class="font-medium">{{
                                    application.institution_name
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Course:</span>
                                <span class="font-medium">{{
                                    application.course_name
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Duration:</span>
                                <span class="font-medium">{{
                                    application.course_duration
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hostel -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <Building2 class="w-4 h-4 text-blue-500" /> Hostel
                            Preference
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500"
                                    >Stay Duration:</span
                                >
                                <span class="font-medium">{{
                                    application.duration_of_stay
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Room:</span>
                                <span class="font-medium">{{
                                    application.room_preference?.replace(
                                        "_",
                                        " ",
                                    ) || "Any"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Guardians -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            Local Guardians
                        </h3>
                        <div
                            v-for="(g, i) in application.local_guardians"
                            :key="i"
                            class="p-3 rounded-lg bg-gray-50 mb-2"
                        >
                            <p class="font-medium text-sm">{{ g.name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ g.mobile }} • {{ g.occupation || "N/A" }}
                            </p>
                            <p class="text-xs text-gray-500">{{ g.address }}</p>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <IndianRupee class="w-4 h-4 text-blue-500" />
                            Payment
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Mode:</span>
                                <span
                                    class="font-medium flex items-center gap-1"
                                >
                                    <CreditCard
                                        v-if="
                                            application.payment_mode ===
                                            'online'
                                        "
                                        class="w-3 h-3"
                                    />
                                    <Banknote v-else class="w-3 h-3" />
                                    {{ application.payment_mode }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">Amount:</span>
                                <span class="font-medium">{{
                                    formatCurrency(application.registration_fee)
                                }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span class="font-medium">{{
                                    application.payment_status
                                }}</span>
                            </div>
                            <div v-if="application.razorpay_payment_id">
                                <span class="text-gray-500">Razorpay ID:</span>
                                <span class="font-mono text-xs">{{
                                    application.razorpay_payment_id
                                }}</span>
                            </div>
                        </div>

                        <button
                            v-if="
                                application.payment_mode === 'cash' &&
                                application.payment_status ===
                                    'pending_verification'
                            "
                            @click="
                                $inertia.post(
                                    route(
                                        'admin.registrations.verify-cash',
                                        application.id,
                                    ),
                                )
                            "
                            class="mt-4 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700"
                        >
                            Verify Cash Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

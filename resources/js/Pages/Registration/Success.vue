<!-- resources/js/Pages/Registration/Success.vue -->
<script setup>
import { Head } from "@inertiajs/vue3";
import { CheckCircle, FileText, Clock, MapPin } from "lucide-vue-next";

const props = defineProps({
    application: Object,
});
</script>

<template>
    <Head title="Registration Successful" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">
            <div
                class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"
            >
                <CheckCircle class="w-10 h-10 text-green-600" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Registration Submitted!
            </h1>
            <p class="text-gray-500 mb-8">
                Your application has been received successfully.
            </p>

            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-left space-y-4"
            >
                <div
                    class="flex items-center justify-between pb-4 border-b border-gray-100"
                >
                    <span class="text-sm text-gray-500">Application No.</span>
                    <span class="text-lg font-bold text-indigo-600">{{
                        application.application_no
                    }}</span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <FileText class="w-4 h-4 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500">Student Name</p>
                            <p class="font-medium text-gray-900">
                                {{ application.student_name }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Clock class="w-4 h-4 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="
                                    application.status === 'paid'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-amber-100 text-amber-800'
                                "
                            >
                                {{
                                    application.status === "paid"
                                        ? "Payment Received"
                                        : "Pending Payment"
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <MapPin class="w-4 h-4 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500">Hostel Address</p>
                            <p class="text-sm text-gray-700">
                                13, Ujjainagar, Dicholi Maidan Road, Indore -
                                452016
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <p class="text-sm text-gray-500">
                    Please visit the hostel office with original documents for
                    verification.
                </p>
                <p class="text-xs text-gray-400">
                    Contact: 9399140386 | pratibhapratikshaavs@gmail.com
                </p>
            </div>

            <a
                href="/"
                class="inline-block mt-6 text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
                ← Back to Home
            </a>
        </div>
    </div>
</template>

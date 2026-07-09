<!-- resources/js/Pages/Registration/QRCode.vue -->
<script setup>
import { Head } from "@inertiajs/vue3";
import { QrCode, Smartphone } from "lucide-vue-next";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

const props = defineProps({
    qrCode: String,
    registrationUrl: String,
});
</script>

<template>
    <GuestLayout>
        <Head title="Register - Scan QR Code" />

        <div
            class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 text-center"
        >
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">
                    Scan to Register
                </h2>
                <p class="text-sm text-gray-500">
                    Use your phone camera to scan
                </p>
            </div>

            <!-- QR Code Display -->
            <div class="relative inline-block mb-6">
                <div
                    class="w-64 h-64 mx-auto bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden"
                >
                    <img
                        v-if="qrCode"
                        :src="'data:image/svg+xml;base64,' + qrCode"
                        alt="Registration QR Code"
                        class="w-full h-full object-contain p-2"
                    />
                    <div v-else class="text-center p-4">
                        <QrCode class="w-16 h-16 text-gray-300 mx-auto mb-2" />
                        <p class="text-xs text-gray-400">QR Code</p>
                    </div>
                </div>

                
            </div>

            <!-- Instructions -->
            <div class="space-y-3 mb-6">
                <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl">
                    <div
                        class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"
                    >
                        <Smartphone class="w-4 h-4 text-blue-600" />
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-900">
                            Open Camera
                        </p>
                        <p class="text-xs text-gray-500">
                            Point at the QR code above
                        </p>
                    </div>
                </div>
            </div>

            <!-- Direct Link -->
            <div class="pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-2">Or visit directly:</p>
                <a
                    :href="registrationUrl"
                    class="text-sm text-blue-600 font-medium break-all hover:underline"
                >
                    {{ registrationUrl }}
                </a>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            प्रतिभा-प्रतीक्षा छात्रावास, इंदौर
        </p>
    </GuestLayout>
</template>

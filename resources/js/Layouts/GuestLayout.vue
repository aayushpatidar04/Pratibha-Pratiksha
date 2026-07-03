<script setup>
import { Link } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
        <div class="flex justify-center mb-6">
            <ApplicationLogo :width="200" />
        </div>
        <Link href="/" class="flex items-center gap-2 mb-6">
            <div class="h-10 w-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                <Building2 class="h-5 w-5" />
            </div>
            <div>
                <p class="font-bold text-gray-900 text-lg">Pratibha Pratiksha</p>
                <p class="text-sm text-gray-500 text-thin">Hostel Management</p>
            </div>
        </Link>

        <div class="w-full sm:max-w-md bg-white shadow rounded-xl border border-gray-100 px-6 py-8">
            <slot />
        </div>
    </div>
</template>
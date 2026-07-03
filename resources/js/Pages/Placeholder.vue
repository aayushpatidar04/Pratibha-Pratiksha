<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';

defineProps({ path: String });
</script>

<template>

    <Head title="Coming Soon" />
    <AuthenticatedLayout>
        <template #header>{{ path }}</template>
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="h-16 w-16 rounded-2xl bg-blue-50 flex items-center justify-center mb-4">
                <Construction class="h-8 w-8 text-blue-500" />
            </div>
            <h1 class="text-lg font-semibold text-gray-900">This module is coming soon</h1>
            <p class="text-sm text-gray-500 mt-1 max-w-sm">
                This section is part of the full Hostel Management System roadmap and hasn't been built out yet.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
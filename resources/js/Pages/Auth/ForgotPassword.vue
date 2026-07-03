<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <GuestLayout>

        <Head title="Forgot Password" />
        <h1 class="text-xl font-bold text-gray-900 mb-2">Forgot your password?</h1>
        <p class="text-sm text-gray-500 mb-6">We'll email you a link to reset it.</p>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" v-model="form.email" required autofocus />
                <InputError :message="form.errors.email" />
            </div>
            <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                Email Password Reset Link
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
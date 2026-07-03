<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () => {
    form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
};
</script>

<template>
    <GuestLayout>

        <Head title="Register" />

        <h1 class="text-xl font-bold text-gray-900 mb-1">Create an account</h1>
        <p class="text-sm text-gray-500 mb-6">Set up your admin account to get started</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput id="name" v-model="form.name" required autofocus autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" v-model="form.email" required autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required
                    autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                Register
            </PrimaryButton>

            <p class="text-center text-sm text-gray-500">
                Already have an account?
                <Link href="/login" class="text-blue-600 hover:underline">Log in</Link>
            </p>
        </form>
    </GuestLayout>
</template>
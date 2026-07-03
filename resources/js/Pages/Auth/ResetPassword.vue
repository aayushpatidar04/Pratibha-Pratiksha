<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ email: String, token: String });

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>

        <Head title="Reset Password" />
        <h1 class="text-xl font-bold text-gray-900 mb-6">Reset Password</h1>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" v-model="form.email" required />
                <InputError :message="form.errors.email" />
            </div>
            <div>
                <InputLabel for="password" value="New Password" />
                <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required />
                <InputError :message="form.errors.password_confirmation" />
            </div>
            <PrimaryButton class="w-full justify-center" :disabled="form.processing">Reset Password</PrimaryButton>
        </form>
    </GuestLayout>
</template>
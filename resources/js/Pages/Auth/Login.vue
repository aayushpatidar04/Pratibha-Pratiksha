<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
    auth: {
        type: Object,
        default: () => ({
            resident: null,
        }),
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <!-- Resident already authenticated -->
        <template v-if="props.auth?.resident">
            <div class="text-center">
                <h1 class="mb-1 text-xl font-bold text-gray-900">
                    Welcome back
                </h1>

                <p class="mb-6 text-sm text-gray-700">
                    You are already logged in as a resident.
                </p>

                <Link
                    href="/resident/dashboard"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    Open Resident Dashboard
                </Link>
            </div>
        </template>

        <!-- Normal login -->
        <template v-else>
            <h1 class="mb-1 text-xl font-bold text-gray-900">Welcome back</h1>

            <p class="mb-6 text-sm text-gray-700">
                Sign in to manage your hostel dashboard
            </p>

            <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label
                        class="flex items-center gap-2 text-sm text-gray-600"
                    >
                        <Checkbox v-model:checked="form.remember" />

                        Remember me
                    </label>

                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-sm text-blue-600 hover:underline"
                    >
                        Forgot password?
                    </Link>
                </div>

                <PrimaryButton
                    class="w-full justify-center"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>

                <p class="text-center text-sm text-gray-700">
                    Don't have an account?

                    <Link
                        href="/register"
                        class="text-blue-600 hover:underline"
                    >
                        Register
                    </Link>
                </p>
                <p class="text-center text-sm text-gray-700">
                    <Link
                        href="/resident/login"
                        class="text-blue-600 hover:underline"
                    >
                        Login as a Resident
                    </Link>
                </p>

            </form>
        </template>
    </GuestLayout>
</template>

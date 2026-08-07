<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Eye, EyeOff, LockKeyhole, UserRound } from "lucide-vue-next";
import { ref } from "vue";

const showPassword = ref(false);

const form = useForm({
    login: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("resident.login.store"), {
        onFinish: () => {
            form.reset("password");
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Resident Login" />

        <p class="mt-1 text-center text-sm text-gray-600">
            Sign in to manage your hostel account
        </p>
    
        <form class="space-y-5 p-6" @submit.prevent="submit">
            <div>
                <label
                    for="login"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                >
                    Resident Code, Phone or Email
                </label>

                <div class="relative">
                    <UserRound
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />

                    <input
                        id="login"
                        v-model="form.login"
                        type="text"
                        autocomplete="username"
                        autofocus
                        class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="PP-2026-0001"
                    />
                </div>

                <p
                    v-if="form.errors.login"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ form.errors.login }}
                </p>
            </div>

            <div>
                <label
                    for="password"
                    class="mb-1.5 block text-sm font-medium text-slate-700"
                >
                    Password
                </label>

                <div class="relative">
                    <LockKeyhole
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />

                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-11 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />

                    <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="h-4 w-4" />

                        <Eye v-else class="h-4 w-4" />
                    </button>
                </div>

                <p
                    v-if="form.errors.password"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />

                Remember me
            </label>

            <div class="text-center">
                <PrimaryButton
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Signing in..." : "Sign In" }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

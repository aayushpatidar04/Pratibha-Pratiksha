<script setup>
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
    <Head title="Resident Login" />

    <div
        class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
            <div class="bg-blue-700 px-6 py-8 text-white">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15"
                >
                    <UserRound class="h-7 w-7" />
                </div>

                <h1 class="mt-4 text-center text-2xl font-bold">
                    Resident Portal
                </h1>

                <p class="mt-1 text-center text-sm text-blue-100">
                    Sign in to manage your hostel account
                </p>
            </div>

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

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {{ form.processing ? "Signing in..." : "Sign In" }}
                </button>
            </form>
        </div>
    </div>
</template>

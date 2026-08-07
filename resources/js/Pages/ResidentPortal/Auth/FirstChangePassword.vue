<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { LockKeyhole, ShieldCheck } from "lucide-vue-next";

const form = useForm({
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("resident.password.first-change.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <ResidentLayout>
        <Head title="Set Your Password" />

        <div class="max-w-xl mx-auto py-8 px-4">
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <!-- Header -->
                <div class="p-6 border-b border-gray-100">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center mb-4"
                    >
                        <LockKeyhole class="w-6 h-6 text-blue-600" />
                    </div>

                    <h1 class="text-xl font-bold text-gray-900">
                        Set Your Password
                    </h1>

                    <p class="text-sm text-gray-600 mt-2">
                        For security reasons, you must change your temporary
                        password before accessing the resident portal.
                    </p>
                </div>

                <!-- Security notice -->
                <div class="px-6 pt-6">
                    <div
                        class="flex gap-3 rounded-xl bg-amber-50 border border-amber-100 p-4"
                    >
                        <ShieldCheck
                            class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
                        />

                        <div>
                            <p class="text-sm font-semibold text-amber-800">
                                Create a new password
                            </p>

                            <p class="text-xs text-amber-700 mt-1">
                                Choose a password that is at least 8 characters
                                long and contains uppercase, lowercase and
                                numeric characters.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div>
                        <InputLabel for="password" value="New Password" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            autocomplete="new-password"
                            required
                            autofocus
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Confirm New Password"
                        />

                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                            required
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <PrimaryButton
                        class="w-full justify-center"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? "Updating..."
                                : "Set Password & Continue"
                        }}
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </ResidentLayout>
</template>
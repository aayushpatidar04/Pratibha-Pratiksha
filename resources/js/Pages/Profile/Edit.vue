<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;

const profileForm = useForm({ name: user.name, email: user.email, phone: user.phone });
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const deleteForm = useForm({ password: '' });
const confirmingDeletion = ref(false);

const updateProfile = () => profileForm.patch('/profile');
const updatePassword = () => passwordForm.put('/profile/password', { onSuccess: () => passwordForm.reset() });
const confirmDelete = () => (confirmingDeletion.value = true);
const deleteAccount = () => deleteForm.delete('/profile', { onFinish: () => (confirmingDeletion.value = false) });
</script>

<template>

    <Head title="Profile" />
    <AuthenticatedLayout>
        <template #header>Account Settings</template>

        <div class="max-w-2xl space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Profile Information</h2>
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput v-model="profileForm.name" required />
                        <InputError :message="profileForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput type="email" v-model="profileForm.email" required />
                        <InputError :message="profileForm.errors.email" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="profileForm.phone" />
                        <InputError :message="profileForm.errors.phone" />
                    </div>
                    <PrimaryButton :disabled="profileForm.processing">Save</PrimaryButton>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Update Password</h2>
                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <InputLabel value="Current Password" />
                        <TextInput type="password" v-model="passwordForm.current_password" />
                        <InputError :message="passwordForm.errors.current_password" />
                    </div>
                    <div>
                        <InputLabel value="New Password" />
                        <TextInput type="password" v-model="passwordForm.password" />
                        <InputError :message="passwordForm.errors.password" />
                    </div>
                    <div>
                        <InputLabel value="Confirm Password" />
                        <TextInput type="password" v-model="passwordForm.password_confirmation" />
                    </div>
                    <PrimaryButton :disabled="passwordForm.processing">Update Password</PrimaryButton>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-red-100 shadow-sm p-6">
                <h2 class="text-base font-semibold text-red-700 mb-2">Delete Account</h2>
                <p class="text-sm text-gray-500 mb-4">Once deleted, all resources will be permanently removed.</p>
                <DangerButton @click="confirmDelete">Delete Account</DangerButton>
            </div>
        </div>

        <Modal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Are you sure?</h2>
                <p class="text-sm text-gray-500 mt-1 mb-4">Enter your password to confirm account deletion.</p>
                <TextInput type="password" v-model="deleteForm.password" placeholder="Password" />
                <InputError :message="deleteForm.errors.password" />
                <div class="mt-6 flex justify-end gap-2">
                    <button class="px-4 py-2 text-sm rounded-lg border border-gray-300"
                        @click="confirmingDeletion = false">Cancel</button>
                    <DangerButton @click="deleteAccount" :disabled="deleteForm.processing">Delete Account</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
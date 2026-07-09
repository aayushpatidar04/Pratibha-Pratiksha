<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Badge from "@/Components/Badge.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { UserCheck, LogIn, LogOut } from "lucide-vue-next";

defineProps({ logs: Object, residents: Array });

const form = useForm({ resident_id: "", log_type: "entry", notes: "" });
const submit = () =>
    form.post("/tracking", { onSuccess: () => form.reset("notes") });
</script>

<template>
    <Head title="Student Tracking" />
    <AuthenticatedLayout>
        <template #header>Student Tracking</template>

        <div class="space-y-5">
            <div>
                <h1
                    class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                >
                    <UserCheck class="h-6 w-6 text-blue-600" /> Student Tracking
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Manual entry/exit log for residents at the gate
                </p>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
            >
                <form
                    @submit.prevent="submit"
                    class="flex flex-col sm:flex-row items-end gap-3"
                >
                    <div class="flex-1 w-full">
                        <InputLabel value="Resident" />
                        <select
                            v-model="form.resident_id"
                            required
                            class="w-full rounded-lg border-gray-300 text-sm"
                        >
                            <option value="" disabled>Select resident</option>
                            <option
                                v-for="r in residents"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.first_name }} {{ r.last_name }} ({{
                                    r.resident_code
                                }})
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="form.log_type = 'entry'"
                            class="px-3 py-2 rounded-lg text-sm border flex items-center gap-1.5"
                            :class="
                                form.log_type === 'entry'
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'border-gray-300 text-gray-600'
                            "
                        >
                            <LogIn class="h-4 w-4" /> Entry
                        </button>
                        <button
                            type="button"
                            @click="form.log_type = 'exit'"
                            class="px-3 py-2 rounded-lg text-sm border flex items-center gap-1.5"
                            :class="
                                form.log_type === 'exit'
                                    ? 'bg-red-600 text-white border-red-600'
                                    : 'border-gray-300 text-gray-600'
                            "
                        >
                            <LogOut class="h-4 w-4" /> Exit
                        </button>
                    </div>
                    <PrimaryButton :disabled="form.processing"
                        >Log</PrimaryButton
                    >
                </form>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Type</th>
                            <th class="text-left px-4 py-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="l in logs.data" :key="l.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ l.resident?.first_name }}
                                {{ l.resident?.last_name }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :color="
                                        l.log_type === 'entry' ? 'green' : 'red'
                                    "
                                    >{{ l.log_type }}</Badge
                                >
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ new Date(l.created_at).toLocaleString() }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data.length">
                            <td
                                colspan="3"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No logs yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<!-- resources/js/Pages/Residents/Kyc/Settings.vue -->
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import {
    Settings,
    ArrowLeft,
    Plus,
    Trash2,
    GripVertical,
    Check,
    X,
    Pencil,
    Save,
} from "lucide-vue-next";
import { ref } from "vue";

const props = defineProps({ requirements: Array });

const showAddForm = ref(false);

const form = useForm({
    requirements: props.requirements.map((r) => ({
        id: r.id,
        label: r.label,
        document_type: r.document_type,
        is_required: r.is_required,
        is_active: r.is_active,
        sort_order: r.sort_order,
    })),
});

const newRequirement = useForm({
    document_type: "",
    label: "",
    is_required: false,
});

const toggleRequired = (index) => {
    form.requirements[index].is_required =
        !form.requirements[index].is_required;
};

const toggleActive = (index) => {
    form.requirements[index].is_active = !form.requirements[index].is_active;
};

const moveUp = (index) => {
    if (index === 0) return;
    const temp = form.requirements[index].sort_order;
    form.requirements[index].sort_order =
        form.requirements[index - 1].sort_order;
    form.requirements[index - 1].sort_order = temp;
    form.requirements.sort((a, b) => a.sort_order - b.sort_order);
};

const moveDown = (index) => {
    if (index === form.requirements.length - 1) return;
    const temp = form.requirements[index].sort_order;
    form.requirements[index].sort_order =
        form.requirements[index + 1].sort_order;
    form.requirements[index + 1].sort_order = temp;
    form.requirements.sort((a, b) => a.sort_order - b.sort_order);
};

const submit = () => form.put(route("kyc.settings.update"));

const submitNew = () => {
    newRequirement.post(route("residents.kyc.requirements.store"), {
        onSuccess: () => {
            newRequirement.reset();
            showAddForm.value = false;
        },
    });
};

const destroyRequirement = (id) => {
    if (!confirm("Are you sure? This will remove the requirement.")) return;
    router.delete(route("residents.kyc.requirements.destroy", id));
};
</script>

<template>
    <Head title="KYC Settings" />
    <AuthenticatedLayout>
        <template #header>Residents / KYC / Settings</template>

        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <Settings class="h-6 w-6 text-blue-600" /> KYC
                        Requirements
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Manage which documents residents must submit. Drag to
                        reorder, toggle to enable/disable.
                    </p>
                </div>
                <Link
                    href="/residents/kyc"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600 hover:bg-gray-50"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Back to KYC
                </Link>
            </div>

            <!-- Add New Button -->
            <div class="flex justify-end">
                <button
                    v-if="!showAddForm"
                    @click="showAddForm = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" /> Add New Requirement
                </button>
            </div>

            <!-- Add New Form -->
            <div
                v-if="showAddForm"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4"
            >
                <h3
                    class="text-sm font-semibold text-gray-900 flex items-center gap-2"
                >
                    <Plus class="h-4 w-4 text-blue-600" /> Add New Document
                    Requirement
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="doc_type" value="Document Type" />

                        <select
                            id="doc_type"
                            v-model="newRequirement.document_type"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select Document Type</option>
                            <option value="aadhar_card">Aadhaar Card</option>
                            <option value="pan_card">PAN Card</option>
                            <option value="photo">Photograph</option>
                            <option value="marksheet">Marksheet</option>
                            <option value="caste_certificate">
                                Caste Certificate
                            </option>
                            <option value="medical_certificate">
                                Medical Certificate
                            </option>
                            <option value="parent_consent">
                                Parent Consent Letter
                            </option>
                            <option value="other">Other</option>
                        </select>

                        <InputError
                            :message="newRequirement.errors.document_type"
                        />
                    </div>
                    <div>
                        <InputLabel for="label" value="Display Label" />
                        <TextInput
                            id="label"
                            v-model="newRequirement.label"
                            placeholder="e.g. Aadhaar Card"
                            class="w-full"
                        />
                        <InputError :message="newRequirement.errors.label" />
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="newRequirement.is_required"
                        class="rounded border-gray-300 text-blue-600"
                    />
                    <span class="text-sm text-gray-700"
                        >Required by default</span
                    >
                </label>

                <div class="flex gap-2 justify-end">
                    <button
                        @click="showAddForm = false"
                        type="button"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                    >
                        Cancel
                    </button>
                    <PrimaryButton
                        :disabled="newRequirement.processing"
                        @click="submitNew"
                    >
                        <Save class="h-4 w-4 mr-1" />
                        {{
                            newRequirement.processing
                                ? "Adding..."
                                : "Add Requirement"
                        }}
                    </PrimaryButton>
                </div>
            </div>

            <!-- Existing Requirements List -->
            <form
                @submit.prevent="submit"
                class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100"
            >
                <div
                    v-if="!form.requirements.length"
                    class="px-5 py-8 text-center text-gray-400"
                >
                    <Settings class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                    <p>
                        No requirements configured yet. Add your first one
                        above.
                    </p>
                </div>

                <div
                    v-for="(req, i) in form.requirements"
                    :key="req.id"
                    class="flex items-center gap-3 px-5 py-4"
                    :class="{ 'opacity-50': !req.is_active }"
                >
                    <!-- Reorder -->
                    <div class="flex flex-col gap-0.5">
                        <button
                            type="button"
                            @click="moveUp(i)"
                            :disabled="i === 0"
                            class="p-0.5 rounded hover:bg-gray-100 disabled:opacity-30"
                        >
                            <GripVertical class="h-3 w-3 text-gray-400" />
                        </button>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-900">
                                {{ req.label }}
                            </p>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-mono"
                            >
                                {{ req.document_type }}
                            </span>
                            <span
                                v-if="req.is_required"
                                class="text-[10px] px-1.5 py-0.5 rounded bg-red-50 text-red-600 font-medium"
                            >
                                Required
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Order: {{ req.sort_order }}
                            {{ !req.is_active ? "• Inactive" : "" }}
                        </p>
                    </div>

                    <!-- Toggles -->
                    <div class="flex items-center gap-4">
                        <!-- Active/Inactive -->
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                type="hidden"
                                :name="`requirements[${i}][is_active]`"
                                :value="req.is_active"
                            />
                            <button
                                type="button"
                                @click="toggleActive(i)"
                                class="relative inline-flex h-5 w-9 rounded-full transition-colors"
                                :class="
                                    req.is_active
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                            >
                                <span
                                    class="inline-block h-3.5 w-3.5 rounded-full bg-white transform transition-transform mt-0.5"
                                    :class="
                                        req.is_active
                                            ? 'translate-x-5 ml-0.5'
                                            : 'translate-x-0.5'
                                    "
                                />
                            </button>
                            <span class="text-xs text-gray-500">{{
                                req.is_active ? "Active" : "Inactive"
                            }}</span>
                        </label>

                        <!-- Required toggle -->
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                type="hidden"
                                :name="`requirements[${i}][is_required]`"
                                :value="req.is_required"
                            />
                            <input
                                type="checkbox"
                                :checked="req.is_required"
                                @change="toggleRequired(i)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-xs text-gray-500">Required</span>
                        </label>

                        <!-- Delete -->
                        <button
                            type="button"
                            @click="destroyRequirement(req.id)"
                            class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </form>

            <!-- Save Button -->
            <div v-if="form.requirements.length" class="flex justify-end">
                <PrimaryButton :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4 mr-1" />
                    {{ form.processing ? "Saving..." : "Save Changes" }}
                </PrimaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

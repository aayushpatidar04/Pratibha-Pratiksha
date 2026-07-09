<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { UtensilsCrossed, Plus, Pencil, Trash2 } from "lucide-vue-next";

const props = defineProps({
    menus: Array,
    weekStart: String,
});

const normalizeDate = (date) => {
    if (!date) return "";
    return String(date).slice(0, 10);
};

const days = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const d = new Date(props.weekStart);
        d.setDate(d.getDate() + i);
        return d.toISOString().slice(0, 10);
    }),
);

const meals = ["breakfast", "lunch", "snacks", "dinner"];

const menuFor = (date, meal) => {
    return props.menus.find(
        (m) => normalizeDate(m.menu_date) === date && m.meal_type === meal,
    );
};

const editOpen = ref(false);

const form = useForm({
    id: null,
    menu_date: "",
    meal_type: "",
    items: "",
    special_notes: "",
});

const openEdit = (date, meal) => {
    const existing = menuFor(date, meal);

    form.clearErrors();
    form.reset();

    form.id = existing?.id || null;
    form.menu_date = date;
    form.meal_type = meal;
    form.items = existing?.items || "";
    form.special_notes = existing?.special_notes || "";

    editOpen.value = true;
};

const submit = () => {
    form.post("/mess", {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
            form.reset();
        },
    });
};

const deleteMenu = (menu) => {
    if (!menu?.id) return;

    if (!confirm("Are you sure you want to delete this menu item?")) {
        return;
    }

    router.delete(`/mess/${menu.id}`, {
        preserveScroll: true,
    });
};

const prevWeek = () => {
    const d = new Date(props.weekStart);
    d.setDate(d.getDate() - 7);
    router.get("/mess", { week: d.toISOString().slice(0, 10) });
};

const nextWeek = () => {
    const d = new Date(props.weekStart);
    d.setDate(d.getDate() + 7);
    router.get("/mess", { week: d.toISOString().slice(0, 10) });
};

const currentWeekLabel = computed(() => {
    const start = new Date(props.weekStart);
    const end = new Date(props.weekStart);
    end.setDate(end.getDate() + 6);

    return (
        start.toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        }) +
        " - " +
        end.toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        })
    );
});
</script>

<template>
    <Head title="Hostel Mess" />

    <AuthenticatedLayout>
        <template #header>Hostel Mess / Weekly Menu</template>

        <div class="space-y-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <UtensilsCrossed class="h-6 w-6 text-blue-600" />
                        Mess Menu
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Plan and publish the weekly mess menu
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ currentWeekLabel }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50"
                        @click="prevWeek"
                    >
                        ← Prev
                    </button>
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50"
                        @click="nextWeek"
                    >
                        Next →
                    </button>
                </div>
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto"
            >
                <table class="w-full text-sm min-w-[1000px]">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-3 py-3 w-28">Meal</th>
                            <th
                                v-for="d in days"
                                :key="d"
                                class="text-left px-3 py-3"
                            >
                                {{
                                    new Date(d).toLocaleDateString("en-IN", {
                                        weekday: "short",
                                        day: "numeric",
                                        month: "short",
                                    })
                                }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="meal in meals" :key="meal">
                            <td
                                class="px-3 py-4 font-medium text-gray-900 capitalize bg-gray-50/40"
                            >
                                {{ meal }}
                            </td>

                            <td
                                v-for="d in days"
                                :key="d"
                                class="px-3 py-3 align-top"
                            >
                                <div
                                    v-if="menuFor(d, meal)"
                                    class="group rounded-lg border border-gray-100 bg-white p-2 hover:border-blue-200 hover:bg-blue-50/30 transition"
                                >
                                    <div class="flex justify-between gap-2">
                                        <p
                                            class="text-xs text-gray-800 whitespace-pre-line leading-relaxed"
                                        >
                                            {{ menuFor(d, meal).items }}
                                        </p>

                                        <div
                                            class="flex gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition"
                                        >
                                            <button
                                                type="button"
                                                class="h-7 w-7 rounded-md flex items-center justify-center text-blue-600 hover:bg-blue-100"
                                                title="Edit"
                                                @click="openEdit(d, meal)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>

                                            <button
                                                type="button"
                                                class="h-7 w-7 rounded-md flex items-center justify-center text-red-600 hover:bg-red-100"
                                                title="Delete"
                                                @click="
                                                    deleteMenu(menuFor(d, meal))
                                                "
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <p
                                        v-if="menuFor(d, meal).special_notes"
                                        class="text-[11px] text-amber-600 mt-2 border-t border-gray-100 pt-1"
                                    >
                                        Note:
                                        {{ menuFor(d, meal).special_notes }}
                                    </p>
                                </div>

                                <button
                                    v-else
                                    type="button"
                                    class="w-full min-h-[74px] rounded-lg border border-dashed border-gray-200 text-gray-300 hover:border-blue-300 hover:text-blue-500 hover:bg-blue-50/30 flex items-center justify-center gap-1 text-xs transition"
                                    @click="openEdit(d, meal)"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Add
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="editOpen" @close="editOpen = false">
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 capitalize">
                    {{ form.id ? "Edit" : "Add" }} {{ form.meal_type }} —
                    {{ form.menu_date }}
                </h2>

                <div>
                    <InputLabel value="Items *" />

                    <textarea
                        v-model="form.items"
                        rows="4"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="e.g. Poha, Bread Butter, Tea"
                    ></textarea>

                    <p
                        v-if="form.errors.items"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.items }}
                    </p>
                </div>

                <div>
                    <InputLabel value="Special Notes" />

                    <TextInput
                        v-model="form.special_notes"
                        class="w-full"
                        placeholder="Optional"
                    />

                    <p
                        v-if="form.errors.special_notes"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.special_notes }}
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50"
                        @click="editOpen = false"
                    >
                        Cancel
                    </button>

                    <PrimaryButton :disabled="form.processing">
                        Save Menu
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

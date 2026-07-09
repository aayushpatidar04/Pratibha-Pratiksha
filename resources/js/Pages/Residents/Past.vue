<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { History, Search, ArrowLeft } from "lucide-vue-next";

const props = defineProps({ residents: Object, filters: Object });

const search = ref(props.filters?.search || "");
let t = null;
const onSearch = () => {
    clearTimeout(t);
    t = setTimeout(
        () =>
            router.get(
                "/residents/past",
                { search: search.value },
                { preserveState: true },
            ),
        400,
    );
};
</script>

<template>
    <Head title="Past Residents" />
    <AuthenticatedLayout>
        <template #header>Residents / Past Residents</template>

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 flex items-center gap-2"
                    >
                        <History class="h-6 w-6 text-blue-600" /> Past Residents
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Residents whose stay has ended (checked out)
                    </p>
                </div>
                <Link
                    href="/residents"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 flex items-center gap-1.5 text-gray-600"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Back to Residents
                </Link>
            </div>

            <div class="relative w-80">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                />
                <input
                    v-model="search"
                    @input="onSearch"
                    placeholder="Search by name..."
                    class="w-full pl-9 rounded-lg border-gray-300 text-sm"
                />
            </div>

            <div
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Resident</th>
                            <th class="text-left px-4 py-3">Last Room</th>
                            <th class="text-left px-4 py-3">Checked Out</th>
                            <th class="text-left px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in residents.data" :key="r.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img
                                        v-if="r.photo_url"
                                        :src="`/storage/${r.photo_url}`"
                                        class="h-16 w-16 rounded-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center text-xs text-gray-400"
                                    >
                                        {{ r.first_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ r.first_name }} {{ r.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ r.resident_code }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                <span v-if="r.stays?.[0]?.room"
                                    >{{ r.stays[0].building?.name }} -
                                    {{ r.stays[0].room?.room_number }}</span
                                >
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ r.stays?.[0]?.actual_check_out_date || "—" }}
                            </td>
                            <td
                                class="px-4 py-3 text-xs capitalize text-gray-500"
                            >
                                {{ r.status }}
                            </td>
                        </tr>
                        <tr v-if="!residents.data.length">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-gray-400"
                            >
                                No past residents yet
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="residents.links?.length > 3"
                    class="flex items-center justify-center gap-1 py-3 border-t border-gray-100"
                >
                    <template v-for="link in residents.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-lg"
                            :class="
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100'
                            "
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1 text-xs text-gray-300"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

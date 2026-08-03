<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { ChevronDown, LogOut, Settings, UserRound } from "lucide-vue-next";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

const page = usePage();

const resident = computed(() => page.props.auth?.resident ?? null);

const open = ref(false);
const wrapper = ref(null);

const closeWhenOutside = (event) => {
    if (wrapper.value && !wrapper.value.contains(event.target)) {
        open.value = false;
    }
};

const logout = () => {
    open.value = false;

    router.post(route("resident.logout"));
};

onMounted(() => {
    document.addEventListener("click", closeWhenOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeWhenOutside);
});
</script>

<template>
    <div ref="wrapper" class="relative">
        <button
            type="button"
            class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left transition hover:bg-slate-50"
            @click.stop="open = !open"
        >
            <img
                v-if="resident?.photo_url"
                :src="`/storage/${resident.photo_url}`"
                class="h-9 w-9 rounded-lg object-cover"
                alt="Resident"
            />

            <div
                v-else
                class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 font-bold text-indigo-700"
            >
                {{ resident?.first_name?.charAt(0) || "R" }}
            </div>

            <div class="hidden min-w-0 sm:block">
                <p
                    class="max-w-36 truncate text-sm font-semibold text-slate-800"
                >
                    {{ resident?.name }}
                </p>

                <p class="truncate text-[10px] text-slate-400">
                    {{ resident?.resident_code }}
                </p>
            </div>

            <ChevronDown
                class="h-4 w-4 text-slate-400 transition"
                :class="open ? 'rotate-180' : ''"
            />
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
        >
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-semibold text-slate-900">
                    {{ resident?.name }}
                </p>

                <p class="truncate text-xs text-slate-500">
                    {{ resident?.email || resident?.phone }}
                </p>
            </div>

            <div class="p-1.5">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
                >
                    <UserRound class="h-4 w-4" />
                    My Profile
                </button>

                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
                >
                    <Settings class="h-4 w-4" />
                    Account Settings
                </button>
            </div>

            <div class="border-t border-slate-100 p-1.5">
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                    @click="logout"
                >
                    <LogOut class="h-4 w-4" />
                    Logout
                </button>
            </div>
        </div>
    </div>
</template>

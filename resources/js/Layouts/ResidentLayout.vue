<script setup>
import NavItem from "@/Components/ResidentPortal/NavItem.vue";
import ProfileDropdown from "@/Components/ResidentPortal/ProfileDropdown.vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    ArrowRightLeft,
    Bell,
    Building2,
    CalendarDays,
    CreditCard,
    FileText,
    Gauge,
    HelpCircle,
    Home,
    Megaphone,
    Menu,
    MessageSquareWarning,
    ReceiptText,
    Send,
    Siren,
    UserRound,
    WalletCards,
    X,
} from "lucide-vue-next";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

defineProps({
    title: {
        type: String,
        default: "Resident Portal",
    },
});

const page = usePage();

const resident = computed(
    () => page.props.auth?.resident ?? null,
);

const currentRoute = computed(() => route().current());

const mobileSidebarOpen = ref(false);

const closeMobileSidebar = () => {
    mobileSidebarOpen.value = false;
};

const isActive = (pattern) => {
    return route().current(pattern);
};

const handleEscape = (event) => {
    if (event.key === "Escape") {
        closeMobileSidebar();
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", handleEscape);
});
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Mobile overlay -->
        <div
            v-if="mobileSidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
            @click="closeMobileSidebar"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="
                mobileSidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full'
            "
        >
            <div
                class="flex h-18 items-center justify-between border-b border-slate-100 px-5 py-2"
            >
                <Link
                    :href="route('resident.dashboard')"
                    class="flex items-center gap-3"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-sm font-bold text-slate-900">
                            Pratibha Pratiksha
                        </p>

                        <p class="text-[10px] uppercase tracking-wider text-slate-400">
                            Resident Portal
                        </p>
                    </div>
                </Link>

                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                    @click="closeMobileSidebar"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-3">
                    <img
                        v-if="resident?.photo_url"
                        :src="`/storage/${resident.photo_url}`"
                        class="h-11 w-11 rounded-xl object-cover"
                        alt="Resident"
                    />

                    <div
                        v-else
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 font-bold text-indigo-700"
                    >
                        {{ resident?.first_name?.charAt(0) || "R" }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">
                            {{ resident?.name }}
                        </p>

                        <p class="truncate text-xs text-slate-400">
                            {{ resident?.resident_code }}
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    Overview
                </p>

                <NavItem
                    :href="route('resident.dashboard')"
                    label="Dashboard"
                    :active="isActive('resident.dashboard')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Gauge class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.my-stay.index')"
                    label="My Stay"
                    :active="isActive('resident.my-stay.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Home class="h-4 w-4" />
                    </template>
                </NavItem>

                <p class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    Finance
                </p>

                <NavItem
                    :href="route('resident.billing.index')"
                    label="Billing"
                    :active="isActive('resident.billing.index')"
                >
                    <template #icon>
                        <CreditCard class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.payments.index')"
                    label="Payments"
                    :active="isActive('resident.payments.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <WalletCards class="h-4 w-4" />
                    </template>
                </NavItem>


                <p class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    Services
                </p>

                <NavItem
                    :href="route('resident.leaves.index')"
                    label="Leaves"
                    :active="isActive('resident.leaves.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <CalendarDays class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.complaints.index')"
                    label="Complaints"
                    :active="isActive('resident.complaints.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <MessageSquareWarning class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.emergency.index')"
                    label="Emergency SOS"
                    :active="isActive('resident.emergency.*')"
                    class="text-red-600"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Siren class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.notices.index')"
                    label="Notices"
                    :active="isActive('resident.notices.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Megaphone class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="
                        route(
                            'resident.room-change-requests.index',
                        )
                    "
                    label="Room Change"
                    :active="
                        isActive(
                            'resident.room-change-requests.*',
                        )
                    "
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <ArrowRightLeft class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    href="#"
                    label="Documents"
                    disabled
                >
                    <template #icon>
                        <FileText class="h-4 w-4" />
                    </template>
                </NavItem>

                <p class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    Account
                </p>

                <NavItem
                    href="#"
                    label="My Profile"
                    disabled
                >
                    <template #icon>
                        <UserRound class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    href="#"
                    label="Help & Support"
                    disabled
                >
                    <template #icon>
                        <HelpCircle class="h-4 w-4" />
                    </template>
                </NavItem>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <div class="rounded-xl bg-indigo-50 p-3">
                    <p class="text-xs font-semibold text-indigo-900">
                        Need assistance?
                    </p>

                    <p class="mt-1 text-[11px] text-indigo-600">
                        Contact the hostel office for urgent support.
                    </p>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="lg:pl-72">
            <header
                class="sticky top-0 z-30 flex h-18 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                        @click="mobileSidebarOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </button>

                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-bold text-slate-900">
                            {{ title }}
                        </h1>

                        <p class="hidden text-xs text-slate-400 sm:block">
                            Welcome back, {{ resident?.first_name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                    >
                        <Bell class="h-4 w-4" />

                        <span
                            class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
                        ></span>
                    </button>

                    <ProfileDropdown />
                </div>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
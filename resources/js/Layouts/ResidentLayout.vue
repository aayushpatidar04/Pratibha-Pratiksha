<script setup>
import NavItem from "@/Components/ResidentPortal/NavItem.vue";
import ProfileDropdown from "@/Components/ResidentPortal/ProfileDropdown.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    ArrowRightLeft,
    Bell,
    Bike,
    Building2,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    FolderOpen,
    Gauge,
    HelpCircle,
    Home,
    LayoutGrid,
    LogOut,
    Megaphone,
    Menu,
    MessageSquareWarning,
    ReceiptText,
    Siren,
    UserRound,
    UtensilsCrossed,
    WalletCards,
    X,
} from "lucide-vue-next";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

defineProps({
    title: {
        type: String,
        default: "Resident Portal",
    },
});

const page = usePage();

const resident = computed(() => page.props.auth?.resident ?? null);

const portal = computed(() => page.props.residentPortal ?? {});

const counts = computed(() => portal.value?.counts ?? {});

const notifications = computed(() => portal.value?.notifications ?? []);

const activeEmergency = computed(() => portal.value?.active_emergency ?? null);

const flash = computed(() => page.props.flash ?? {});

const mobileSidebarOpen = ref(false);
const notificationOpen = ref(false);

const toast = ref(null);
let toastTimer = null;

const closeMobileSidebar = () => {
    mobileSidebarOpen.value = false;
};

const closeDropdowns = () => {
    notificationOpen.value = false;
};

const isActive = (pattern) => {
    return route().current(pattern);
};

const notificationCount = computed(() =>
    Number(counts.value?.total_notifications ?? 0),
);

const residentInitial = computed(() => {
    return resident.value?.first_name?.charAt(0)?.toUpperCase() || "R";
});

const notificationIcon = (type) => {
    return (
        {
            notice: Megaphone,
            leave: CalendarDays,
            complaint: MessageSquareWarning,
            request: ArrowRightLeft,
            emergency: Siren,
        }[type] || Bell
    );
};

const notificationTone = (tone) => {
    return (
        {
            red: "bg-red-50 text-red-600",
            green: "bg-emerald-50 text-emerald-600",
            amber: "bg-amber-50 text-amber-600",
            purple: "bg-purple-50 text-purple-600",
            indigo: "bg-indigo-50 text-indigo-600",
        }[tone] || "bg-slate-50 text-slate-600"
    );
};

const formatRelativeTime = (value) => {
    if (!value) {
        return "";
    }

    const date = new Date(value);
    const now = new Date();

    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return "Just now";
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 7) {
        return `${days}d ago`;
    }

    return new Intl.DateTimeFormat("en-IN", {
        day: "2-digit",
        month: "short",
    }).format(date);
};

const showToast = (type, message) => {
    if (!message) {
        return;
    }

    toast.value = {
        type,
        message,
    };

    clearTimeout(toastTimer);

    toastTimer = setTimeout(() => {
        toast.value = null;
    }, 4500);
};

const dismissToast = () => {
    toast.value = null;
    clearTimeout(toastTimer);
};

const toastClasses = computed(() => {
    return (
        {
            success: "border-emerald-200 bg-emerald-50 text-emerald-900",

            error: "border-red-200 bg-red-50 text-red-900",

            warning: "border-amber-200 bg-amber-50 text-amber-900",

            info: "border-blue-200 bg-blue-50 text-blue-900",
        }[toast.value?.type] || "border-slate-200 bg-white text-slate-900"
    );
});

const openNotification = (notification) => {
    notificationOpen.value = false;

    if (notification.href) {
        router.visit(notification.href);
    }
};

const handleEscape = (event) => {
    if (event.key === "Escape") {
        closeMobileSidebar();
        closeDropdowns();
        dismissToast();
    }
};

const handleDocumentClick = (event) => {
    if (!event.target.closest("[data-notification-dropdown]")) {
        notificationOpen.value = false;
    }
};

watch(
    () => [
        flash.value?.success,
        flash.value?.error,
        flash.value?.warning,
        flash.value?.info,
    ],
    () => {
        if (flash.value?.success) {
            showToast("success", flash.value.success);

            return;
        }

        if (flash.value?.error) {
            showToast("error", flash.value.error);

            return;
        }

        if (flash.value?.warning) {
            showToast("warning", flash.value.warning);

            return;
        }

        if (flash.value?.info) {
            showToast("info", flash.value.info);
        }
    },
    {
        immediate: true,
    },
);

onMounted(() => {
    document.addEventListener("keydown", handleEscape);

    document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", handleEscape);

    document.removeEventListener("click", handleDocumentClick);

    clearTimeout(toastTimer);
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 pb-20 lg:pb-0">
        <!-- Global toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-8 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-8 opacity-0"
        >
            <div
                v-if="toast"
                class="fixed right-4 top-20 z-[100] w-[calc(100%-2rem)] max-w-sm rounded-2xl border p-4 shadow-2xl"
                :class="toastClasses"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2
                        v-if="toast.type === 'success'"
                        class="mt-0.5 h-5 w-5 shrink-0"
                    />

                    <Siren
                        v-else-if="toast.type === 'error'"
                        class="mt-0.5 h-5 w-5 shrink-0"
                    />

                    <Bell v-else class="mt-0.5 h-5 w-5 shrink-0" />

                    <p class="min-w-0 flex-1 text-sm font-semibold leading-5">
                        {{ toast.message }}
                    </p>

                    <button
                        type="button"
                        class="rounded-lg p-1 opacity-60 hover:bg-black/5 hover:opacity-100"
                        @click="dismissToast"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Mobile overlay -->
        <div
            v-if="mobileSidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
            @click="closeMobileSidebar"
        ></div>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-5 py-3"
            >
                <Link
                    :href="route('resident.dashboard')"
                    class="flex items-center gap-3"
                    @click="closeMobileSidebar"
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

                        <p
                            class="text-[10px] uppercase tracking-wider text-slate-400"
                        >
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
                        {{ residentInitial }}
                    </div>

                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-semibold text-slate-900"
                        >
                            {{ resident?.name }}
                        </p>

                        <p class="truncate text-xs text-slate-400">
                            {{ resident?.resident_code }}
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <p
                    class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                >
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

                <p
                    class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                >
                    Finance
                </p>

                <NavItem
                    :href="route('resident.billing.index')"
                    label="Billing"
                    :active="isActive('resident.billing.*')"
                    @click="closeMobileSidebar"
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

                <p
                    class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                >
                    Services
                </p>

                <NavItem
                    :href="route('resident.leaves.index')"
                    label="Leaves"
                    :active="isActive('resident.leaves.*')"
                    :badge="counts.pending_leaves"
                    badge-tone="blue"
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
                    :badge="counts.open_complaints"
                    badge-tone="amber"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <MessageSquareWarning class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.room-change-requests.index')"
                    label="Room Change"
                    :active="isActive('resident.room-change-requests.*')"
                    :badge="counts.pending_room_requests"
                    badge-tone="purple"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <ArrowRightLeft class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="
                        route(
                            'resident.checkout-requests.index',
                        )
                    "
                    label="Checkout Request"
                    :active="
                        isActive(
                            'resident.checkout-requests.*',
                        )
                    "
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <LogOut class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.emergency.index')"
                    label="Emergency SOS"
                    :active="isActive('resident.emergency.*')"
                    :badge="counts.active_emergencies ? 'active' : null"
                    badge-tone="red"
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
                    :badge="counts.unread_notices"
                    badge-tone="indigo"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Megaphone class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.mess-menu.index')"
                    label="Mess Menu"
                    :active="isActive('resident.mess-menu.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <UtensilsCrossed class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.documents.index')"
                    label="My Documents"
                    :active="isActive('resident.documents.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <FolderOpen class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.vehicles.index')"
                    label="My Vehicles"
                    :active="isActive('resident.vehicles.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <Bike class="h-4 w-4" />
                    </template>
                </NavItem>

                <p
                    class="mb-2 mt-5 px-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                >
                    Account
                </p>

                <NavItem
                    :href="route('resident.profile.index')"
                    label="My Profile"
                    :active="isActive('resident.profile.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <UserRound class="h-4 w-4" />
                    </template>
                </NavItem>

                <NavItem
                    :href="route('resident.support.index')"
                    label="Help & Support"
                    :active="isActive('resident.support.*')"
                    @click="closeMobileSidebar"
                >
                    <template #icon>
                        <HelpCircle class="h-4 w-4" />
                    </template>
                </NavItem>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <Link
                    :href="route('resident.emergency.index')"
                    class="block rounded-xl border border-red-100 bg-red-50 p-3 transition hover:bg-red-100"
                >
                    <div class="flex items-center gap-2">
                        <Siren class="h-4 w-4 text-red-600" />

                        <p class="text-xs font-semibold text-red-900">
                            Need urgent assistance?
                        </p>
                    </div>

                    <p class="mt-1 text-[11px] leading-4 text-red-600">
                        Raise an emergency alert for immediate hostel support.
                    </p>
                </Link>
            </div>
        </aside>

        <!-- Main -->
        <div class="lg:pl-72">
            <!-- Active emergency bar -->
            <Link
                v-if="activeEmergency"
                :href="route('resident.emergency.index')"
                class="sticky top-0 z-40 flex items-center justify-between gap-3 bg-red-700 px-4 py-2 text-white lg:px-6"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <span class="relative flex h-3 w-3 shrink-0">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"
                        ></span>

                        <span
                            class="relative inline-flex h-3 w-3 rounded-full bg-white"
                        ></span>
                    </span>

                    <p class="truncate text-xs font-bold">
                        ACTIVE EMERGENCY:
                        {{
                            String(activeEmergency.category)
                                .replaceAll("_", " ")
                                .toUpperCase()
                        }}
                    </p>
                </div>

                <span class="shrink-0 text-xs font-semibold"> View Alert </span>
            </Link>

            <header
                class="sticky z-30 flex h-[72px] items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6"
                :class="activeEmergency ? 'top-8' : 'top-0'"
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
                            Welcome back,
                            {{ resident?.first_name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Notifications -->
                    <div class="relative" data-notification-dropdown>
                        <button
                            type="button"
                            class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                            @click.stop="notificationOpen = !notificationOpen"
                        >
                            <Bell class="h-4 w-4" />

                            <span
                                v-if="notificationCount > 0"
                                class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white ring-2 ring-white"
                            >
                                {{
                                    notificationCount > 99
                                        ? "99+"
                                        : notificationCount
                                }}
                            </span>
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="-translate-y-2 opacity-0"
                            enter-to-class="translate-y-0 opacity-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="translate-y-0 opacity-100"
                            leave-to-class="-translate-y-2 opacity-0"
                        >
                            <div
                                v-if="notificationOpen"
                                class="absolute right-0 top-12 z-50 w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-100 px-4 py-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            Notifications
                                        </p>

                                        <p class="text-[10px] text-slate-400">
                                            Recent portal activity
                                        </p>
                                    </div>

                                    <span
                                        v-if="notificationCount"
                                        class="rounded-full bg-red-50 px-2 py-1 text-[10px] font-bold text-red-600"
                                    >
                                        {{ notificationCount }}
                                        new
                                    </span>
                                </div>

                                <div
                                    v-if="notifications.length"
                                    class="max-h-96 divide-y divide-slate-100 overflow-y-auto"
                                >
                                    <button
                                        v-for="notification in notifications"
                                        :key="notification.id"
                                        type="button"
                                        class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-slate-50"
                                        @click="openNotification(notification)"
                                    >
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                            :class="
                                                notificationTone(
                                                    notification.tone,
                                                )
                                            "
                                        >
                                            <component
                                                :is="
                                                    notificationIcon(
                                                        notification.type,
                                                    )
                                                "
                                                class="h-4 w-4"
                                            />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-xs font-bold text-slate-900"
                                            >
                                                {{ notification.title }}
                                            </p>

                                            <p
                                                class="mt-1 line-clamp-2 text-[11px] leading-4 text-slate-500"
                                            >
                                                {{ notification.message }}
                                            </p>

                                            <p
                                                class="mt-1 text-[10px] text-slate-400"
                                            >
                                                {{
                                                    formatRelativeTime(
                                                        notification.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </button>
                                </div>

                                <div v-else class="px-5 py-10 text-center">
                                    <Bell
                                        class="mx-auto h-9 w-9 text-slate-300"
                                    />

                                    <p
                                        class="mt-2 text-sm font-semibold text-slate-600"
                                    >
                                        No recent notifications
                                    </p>
                                </div>

                                <Link
                                    :href="route('resident.notices.index')"
                                    class="flex items-center justify-center gap-2 border-t border-slate-100 px-4 py-3 text-xs font-semibold text-indigo-600 hover:bg-indigo-50"
                                    @click="notificationOpen = false"
                                >
                                    View All Notices
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <ProfileDropdown />
                </div>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile bottom navigation -->
        <nav
            class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-2 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden"
        >
            <Link
                :href="route('resident.dashboard')"
                class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold"
                :class="
                    isActive('resident.dashboard')
                        ? 'text-indigo-600'
                        : 'text-slate-400'
                "
            >
                <LayoutGrid class="h-5 w-5" />
                Home
            </Link>

            <Link
                :href="route('resident.billing.index')"
                class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold"
                :class="
                    isActive('resident.billing.*')
                        ? 'text-indigo-600'
                        : 'text-slate-400'
                "
            >
                <CreditCard class="h-5 w-5" />
                Billing
            </Link>

            <Link
                :href="route('resident.notices.index')"
                class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold"
                :class="
                    isActive('resident.notices.*')
                        ? 'text-indigo-600'
                        : 'text-slate-400'
                "
            >
                <Megaphone class="h-5 w-5" />

                <span
                    v-if="counts.unread_notices"
                    class="absolute right-5 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[8px] text-white"
                >
                    {{
                        Number(counts.unread_notices) > 9
                            ? "9+"
                            : counts.unread_notices
                    }}
                </span>

                Notices
            </Link>

            <Link
                :href="route('resident.leaves.index')"
                class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold"
                :class="
                    isActive('resident.leaves.*')
                        ? 'text-indigo-600'
                        : 'text-slate-400'
                "
            >
                <CalendarDays class="h-5 w-5" />
                Leaves
            </Link>

            <button
                type="button"
                class="flex flex-col items-center justify-center gap-1 py-2.5 text-[10px] font-semibold text-slate-400"
                @click="mobileSidebarOpen = true"
            >
                <Menu class="h-5 w-5" />
                More
            </button>
        </nav>
    </div>
</template>
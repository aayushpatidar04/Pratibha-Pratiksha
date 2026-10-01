<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { listenForNotifications, leaveUserChannel } from "@/echo";

import {
    LayoutDashboard,
    BarChart3,
    Building2,
    Users,
    UserCog,
    LogIn,
    UtensilsCrossed,
    Receipt,
    MessageCircle,
    HeadphonesIcon,
    FileText,
    ShieldCheck,
    UserCheck,
    Gavel,
    ChevronDown,
    ChevronRight,
    Megaphone,
    Menu,
    LogOut,
    Settings,
    User,
    Hotel,
    Layers,
    DoorOpen,
    ClipboardList,
    ClipboardCheck,
    AlertTriangle,
    CalendarDays,
    MessageSquareWarning,
    GraduationCap,
    FileCheck2,
    Bell,
    Check,
    X,
    Boxes,
    CheckCircle2,
    XCircle,
    Bike,
    Gift,
} from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth.user);

const notifications = ref([]);
const unreadNotificationCount = ref(0);

const notificationOpen = ref(false);

const notificationLoading = ref(false);

const notificationToasts = ref([]);

let notificationChannel = null;

const permissions = computed(() => page.props.auth.permissions || {});
const currentPath = computed(() => window.location.pathname);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const rawNav = [
    { label: "Dashboard", icon: LayoutDashboard, path: "/dashboard" },
    {
        label: "Analytics",
        icon: BarChart3,
        path: "/analytics",
        module: "analytics",
    },
    {
        label: "Infrastructure",
        icon: Building2,
        children: [
            {
                label: "Buildings",
                path: "/infrastructure/buildings",
                icon: Hotel,
                module: "buildings",
            },
            {
                label: "Floors",
                path: "/infrastructure/floors",
                icon: Layers,
                module: "floors",
            },
            {
                label: "Rooms",
                path: "/infrastructure/rooms",
                icon: DoorOpen,
                module: "rooms",
            },
            {
                label: "Inventory",
                path: "/infrastructure/inventory",
                icon: Boxes,
                module: "inventory",
            },
        ],
    },
    {
        label: "Residents",
        icon: Users,
        module: "residents",
        children: [
            {
                label: "Residents List",
                path: "/residents",
                icon: ClipboardList,
                module: "residents",
            },
            {
                label: "KYC",
                path: "/residents/kyc",
                icon: ShieldCheck,
                module: "kyc",
            },
            {
                label: "Academic Details",
                path: "/residents/academic-details",
                icon: GraduationCap,
                module: "academics",
            },
            {
                label: "Student Vehicles",
                path: "/residents/vehicles",
                icon: Bike,
                module: "student_vehicles",
            },
            {
                label: "Student Birthdays",
                path: "/residents/birthdays",
                icon: Gift,
                module: "residents",
            },
        ],
    },
    { label: "Admin", icon: UserCog, path: "/admin", module: "admin_users" },
    {
        label: "Registration Applications",
        icon: FileCheck2,
        path: "/registrations",
        module: "registrations",
    },
    {
        label: "Check-In / Out",
        icon: LogIn,
        path: "/checkinout",
        module: "checkinout",
    },
    {
        label: "Checkout Inspections",
        icon: ClipboardCheck,
        path: "/warden-checkout-inspections",
        module: "checkout_inspections",
    },
    {
        label: "Exit Verification",
        icon: ShieldCheck,
        path: "/checkout-gate",
        module: "checkout_gate",
    },
    {
        label: "Hostel Mess",
        icon: UtensilsCrossed,
        path: "/mess",
        module: "mess",
    },
    { label: "Billing", icon: Receipt, path: "/billing", module: "billing" },
    {
        label: "WhatsApp",
        icon: MessageCircle,
        path: "/whatsapp",
        module: "whatsapp",
    },
    {
        label: "Notices & Circulars",
        icon: Megaphone,
        path: "/notices",
        module: "notices",
    },
    {
        label: "Student Support",
        icon: HeadphonesIcon,
        children: [
            {
                label: "Complaints",
                path: "/support/complaints",
                icon: MessageSquareWarning,
                module: "complaints",
            },
            {
                label: "Leaves",
                path: "/support/leaves",
                icon: CalendarDays,
                module: "leaves",
            },
            {
                label: "Emergency Alerts",
                path: "/support/emergency",
                icon: AlertTriangle,
                module: "emergency",
            },
        ],
    },
    { label: "Reports", icon: FileText, path: "/reports", module: "reports" },
    {
        label: "Gate Management",
        icon: ShieldCheck,
        path: "/gate",
        module: "gate",
    },
    {
        label: "Student Tracking",
        icon: UserCheck,
        path: "/tracking",
        module: "tracking",
    },
    {
        label: "Disciplinary Action",
        icon: Gavel,
        path: "/disciplinary",
        module: "disciplinary",
    },
];

// Dynamically hide anything the logged-in user has no "view" permission for.
// Groups with children are hidden entirely once none of their children remain visible.
const nav = computed(() => {
    if (user.value?.role === "super_admin") return rawNav;

    return rawNav
        .map((item) => {
            if (item.children) {
                const children = item.children.filter((c) => canView(c.module));
                return children.length ? { ...item, children } : null;
            }
            return canView(item.module) ? item : null;
        })
        .filter(Boolean);
});

const openSections = ref(["Infrastructure", "Residents"]);
const toggleSection = (label) => {
    openSections.value = openSections.value.includes(label)
        ? openSections.value.filter((l) => l !== label)
        : [...openSections.value, label];
};

const mobileOpen = ref(false);
const userMenuOpen = ref(false);

const isActive = (path) =>
    currentPath.value === path || currentPath.value.startsWith(path + "/");

const logout = () => router.post("/logout");

const can = (moduleKey, action = "view") => {
    if (user.value?.role === "super_admin") {
        return true;
    }

    return (permissions.value[moduleKey] || []).includes(action);
};

const canView = (moduleKey) => !moduleKey || can(moduleKey, "view");

const csrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || "";

const normalizeNotification = (notification) => {
    return {
        id: notification.id,
        recipient_id: notification.recipient_id ?? null,

        title: notification.title ?? "New Notification",

        body: notification.body ?? "",

        type: notification.type ?? "general",

        payload: notification.payload ?? {},

        action_url: notification.action_url ?? null,

        action_label: notification.action_label ?? null,

        read_at: notification.read_at ?? null,

        archived_at: notification.archived_at ?? null,

        created_at: notification.created_at ?? new Date().toISOString(),
    };
};

const loadNotifications = async () => {
    if (!user.value?.id) return;

    notificationLoading.value = true;

    try {
        const response = await fetch("/notifications", {
            method: "GET",

            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },

            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error(`Notification request failed: ${response.status}`);
        }

        const data = await response.json();

        notifications.value = (data.notifications ?? []).map(
            normalizeNotification,
        );

        unreadNotificationCount.value = data.unread_count ?? 0;
    } catch (error) {
        console.error("[Notifications] Failed to load:", error);
    } finally {
        notificationLoading.value = false;
    }
};

const showNotificationToast = (notification) => {
    const toast = {
        ...normalizeNotification(notification),

        toast_id: `${notification.id ?? "notification"}-${Date.now()}-${Math.random()}`,
    };

    notificationToasts.value.push(toast);

    window.setTimeout(() => {
        removeNotificationToast(toast.toast_id);
    }, 5000);
};

const removeNotificationToast = (toastId) => {
    notificationToasts.value = notificationToasts.value.filter(
        (toast) => toast.toast_id !== toastId,
    );
};

const handleRealtimeNotification = (notification) => {
    const normalized = normalizeNotification(notification);

    /*
    |--------------------------------------------------------------------------
    | Prevent duplicates
    |--------------------------------------------------------------------------
    */

    const alreadyExists = notifications.value.some(
        (item) => Number(item.id) === Number(normalized.id),
    );

    if (!alreadyExists) {
        notifications.value.unshift(normalized);

        notifications.value = notifications.value.slice(0, 20);

        /*
        |--------------------------------------------------------------------------
        | New notification = unread
        |--------------------------------------------------------------------------
        */

        unreadNotificationCount.value += 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Show toast
    |--------------------------------------------------------------------------
    */

    showNotificationToast(normalized);

    /*
    |--------------------------------------------------------------------------
    | Notify individual pages
    |--------------------------------------------------------------------------
    |
    | Dashboard can listen to this without creating
    | another Pusher subscription.
    |
    */
    window.dispatchEvent(
        new CustomEvent("app-notification-received", {
            detail: normalized,
        })
    );
};

const markNotificationAsRead = async (notification) => {
    if (!notification.recipient_id) {
        return;
    }

    if (notification.read_at) {
        return;
    }

    try {
        const response = await fetch(
            `/notifications/${notification.recipient_id}/read`,
            {
                method: "POST",

                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",

                    "X-Requested-With": "XMLHttpRequest",

                    "X-CSRF-TOKEN": csrfToken(),
                },

                credentials: "same-origin",
            },
        );

        if (!response.ok) {
            throw new Error(`Mark read failed: ${response.status}`);
        }

        notification.read_at = new Date().toISOString();

        if (unreadNotificationCount.value > 0) {
            unreadNotificationCount.value -= 1;
        }
    } catch (error) {
        console.error("[Notifications] Failed to mark read:", error);
    }
};

const markAllNotificationsAsRead = async () => {
    if (unreadNotificationCount.value === 0) {
        return;
    }

    try {
        const response = await fetch("/notifications/read-all", {
            method: "POST",

            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",

                "X-Requested-With": "XMLHttpRequest",

                "X-CSRF-TOKEN": csrfToken(),
            },

            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error(`Mark all read failed: ${response.status}`);
        }

        notifications.value.forEach((notification) => {
            notification.read_at =
                notification.read_at ?? new Date().toISOString();
        });

        unreadNotificationCount.value = 0;
    } catch (error) {
        console.error("[Notifications] Failed to mark all:", error);
    }
};

const openNotification = async (notification) => {
    await markNotificationAsRead(notification);

    notificationOpen.value = false;

    if (notification.action_url) {
        router.visit(notification.action_url);
    }
};

onMounted(async () => {
    await loadNotifications();

    const authUserId = user.value?.id;

    if (!authUserId) {
        console.warn("[Notifications] No authenticated user.");

        return;
    }

    notificationChannel = listenForNotifications(
        authUserId,
        handleRealtimeNotification,
    );
});

onBeforeUnmount(() => {
    if (user.value?.id) {
        leaveUserChannel(user.value.id);
    }

    notificationChannel = null;
});

const formatNotificationTime = (date) => {
    if (!date) {
        return "";
    }

    const timestamp = new Date(date).getTime();

    const diff = Date.now() - timestamp;

    const seconds = Math.floor(diff / 1000);

    if (seconds < 10) {
        return "now";
    }

    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h`;
    }

    const days = Math.floor(hours / 24);

    if (days < 7) {
        return `${days}d`;
    }

    return new Date(date).toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
    });
};
</script>

<template>
    <div class="min-h-screen flex bg-gray-50">
        <!-- Mobile overlay -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 bg-black/40 z-30 lg:hidden"
            @click="mobileOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed lg:sticky top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 z-40 flex flex-col transition-transform lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="h-16 flex items-center justify-between px-4 border-b border-gray-100 shrink-0"
            >
                <Link
                    href="/dashboard"
                    class="flex items-center gap-2 font-bold text-gray-900"
                >
                    <ApplicationLogo :width="200" />
                </Link>
                <button
                    class="lg:hidden text-gray-700"
                    @click="mobileOpen = false"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                <template v-for="item in nav" :key="item.label">
                    <div v-if="item.children">
                        <button
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100"
                            @click="toggleSection(item.label)"
                        >
                            <span class="flex items-center gap-2">
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 text-gray-700"
                                />
                                {{ item.label }}
                            </span>
                            <component
                                :is="
                                    openSections.includes(item.label)
                                        ? ChevronDown
                                        : ChevronRight
                                "
                                class="h-3.5 w-3.5 text-gray-600"
                            />
                        </button>
                        <div
                            v-show="openSections.includes(item.label)"
                            class="ml-4 mt-0.5 space-y-0.5 border-l border-gray-100 pl-2"
                        >
                            <Link
                                v-for="child in item.children"
                                :key="child.path"
                                :href="child.path"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm"
                                :class="
                                    isActive(child.path)
                                        ? 'bg-blue-50 text-blue-700 font-medium'
                                        : 'text-gray-600 hover:bg-gray-100'
                                "
                            >
                                <component
                                    :is="child.icon"
                                    class="h-3.5 w-3.5"
                                />
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                    <Link
                        v-else
                        :href="item.path"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium"
                        :class="
                            isActive(item.path)
                                ? 'bg-blue-50 text-blue-700'
                                : 'text-gray-700 hover:bg-gray-100'
                        "
                    >
                        <component
                            :is="item.icon"
                            class="h-4 w-4"
                            :class="
                                isActive(item.path)
                                    ? 'text-blue-600'
                                    : 'text-gray-700'
                            "
                        />
                        {{ item.label }}
                    </Link>
                </template>
            </nav>

            <div class="border-t border-gray-100 p-2 shrink-0">
                <Link
                    href="/profile"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-100"
                >
                    <Settings class="h-4 w-4 text-gray-700" /> Settings
                </Link>
                <button
                    @click="logout"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50"
                >
                    <LogOut class="h-4 w-4" /> Logout
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <header
                class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20"
            >
                <button
                    class="lg:hidden text-gray-600"
                    @click="mobileOpen = true"
                >
                    <Menu class="h-5 w-5" />
                </button>
                <div class="hidden lg:block text-sm text-gray-700">
                    <slot name="header" />
                </div>
                <div class="relative ml-auto flex items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button
                            type="button"
                            class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition"
                            @click="notificationOpen = !notificationOpen"
                            aria-label="Notifications"
                        >
                            <Bell class="h-5 w-5" />

                            <!-- Unread badge -->
                            <span
                                v-if="unreadNotificationCount > 0"
                                class="absolute -right-0.5 -top-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center border-2 border-white"
                            >
                                {{
                                    unreadNotificationCount > 99
                                        ? "99+"
                                        : unreadNotificationCount
                                }}
                            </span>
                        </button>

                        <!-- Notification dropdown -->
                        <div
                            v-if="notificationOpen"
                            class="absolute right-0 mt-2 w-[380px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl z-50"
                        >
                            <!-- Header -->
                            <div
                                class="flex items-center justify-between border-b border-gray-100 px-4 py-3"
                            >
                                <div>
                                    <h3
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Notifications
                                    </h3>

                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ unreadNotificationCount }}
                                        unread
                                    </p>
                                </div>

                                <button
                                    v-if="unreadNotificationCount > 0"
                                    type="button"
                                    class="text-xs font-medium text-blue-600 hover:text-blue-700"
                                    @click="markAllNotificationsAsRead"
                                >
                                    Mark all as read
                                </button>
                            </div>

                            <!-- Loading -->
                            <div
                                v-if="notificationLoading"
                                class="px-4 py-8 text-center text-sm text-gray-500"
                            >
                                Loading notifications...
                            </div>

                            <!-- Empty -->
                            <div
                                v-else-if="notifications.length === 0"
                                class="px-4 py-10 text-center"
                            >
                                <Bell class="mx-auto h-8 w-8 text-gray-300" />

                                <p
                                    class="mt-2 text-sm font-medium text-gray-600"
                                >
                                    No notifications
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    You're all caught up.
                                </p>
                            </div>

                            <!-- Notification list -->
                            <div v-else class="max-h-[430px] overflow-y-auto">
                                <button
                                    v-for="notification in notifications"
                                    :key="notification.recipient_id"
                                    type="button"
                                    class="w-full text-left px-4 py-3 border-b border-gray-50 transition hover:bg-gray-50"
                                    :class="
                                        notification.read_at
                                            ? 'bg-white'
                                            : 'bg-blue-50/60'
                                    "
                                    @click="openNotification(notification)"
                                >
                                    <div class="flex gap-3">
                                        <!-- Indicator -->
                                        <div class="shrink-0 pt-0.5">
                                            <span
                                                v-if="!notification.read_at"
                                                class="block h-2.5 w-2.5 rounded-full bg-blue-600"
                                            ></span>

                                            <span
                                                v-else
                                                class="block h-2.5 w-2.5"
                                            ></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <p
                                                    class="text-sm font-semibold text-gray-900"
                                                >
                                                    {{ notification.title }}
                                                </p>

                                                <span
                                                    class="shrink-0 text-[10px] text-gray-400"
                                                >
                                                    {{
                                                        formatNotificationTime(
                                                            notification.created_at,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 text-xs leading-5 text-gray-600 line-clamp-2"
                                            >
                                                {{ notification.body }}
                                            </p>

                                            <p
                                                v-if="notification.action_label"
                                                class="mt-2 text-xs font-medium text-blue-600"
                                            >
                                                {{ notification.action_label }}
                                                →
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <!-- Footer -->
                            <div
                                v-if="notifications.length > 0"
                                class="border-t border-gray-100 px-4 py-2.5 text-center"
                            >
                                <button
                                    type="button"
                                    class="text-xs font-medium text-gray-600 hover:text-gray-900"
                                    @click="notificationOpen = false"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="relative">
                        <button
                            class="flex items-center gap-2 text-sm"
                            @click="userMenuOpen = !userMenuOpen"
                        >
                            <div
                                class="h-8 w-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold"
                            >
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <span
                                class="hidden sm:block font-medium text-gray-700"
                                >{{ user?.name }}</span
                            >
                            <ChevronDown class="h-3.5 w-3.5 text-gray-600" />
                        </button>
                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-30"
                            @click="userMenuOpen = false"
                        >
                            <Link
                                href="/profile"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <User class="h-4 w-4" /> Profile
                            </Link>
                            <button
                                @click="logout"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                            >
                                <LogOut class="h-4 w-4" /> Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="flashSuccess"
                        :key="'s-' + flashSuccess"
                        class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800"
                    >
                        <CheckCircle2 class="h-4 w-4 shrink-0" />
                        {{ flashSuccess }}
                    </div>
                </transition>
                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="flashError"
                        :key="'e-' + flashError"
                        class="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800"
                    >
                        <XCircle class="h-4 w-4 shrink-0" /> {{ flashError }}
                    </div>
                </transition>
                <slot />
            </main>
        </div>
    </div>
    <!--
|--------------------------------------------------------------------------
| Realtime notification toasts
|--------------------------------------------------------------------------
-->
    <div
        class="fixed top-20 right-4 sm:right-6 z-[100] w-[calc(100vw-2rem)] sm:w-[380px] pointer-events-none"
    >
        <TransitionGroup
            tag="div"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-8"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-200 ease-in absolute right-0"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-x-8"
            move-class="transition duration-300"
            class="flex flex-col gap-3"
        >
            <div
                v-for="toast in notificationToasts"
                :key="toast.toast_id"
                class="pointer-events-auto w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
            >
                <div class="flex gap-3 p-4">
                    <!-- Icon -->
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600"
                    >
                        <Bell class="h-4 w-4" />
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ toast.title }}
                            </p>

                            <button
                                type="button"
                                class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                @click="removeNotificationToast(toast.toast_id)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="mt-1 text-xs leading-5 text-gray-600">
                            {{ toast.body }}
                        </p>

                        <button
                            v-if="toast.action_url"
                            type="button"
                            class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
                            @click="
                                openNotification(toast);
                                removeNotificationToast(toast.toast_id);
                            "
                        >
                            {{ toast.action_label || "View" }}
                            →
                        </button>
                    </div>
                </div>

                <!-- 5 second progress indicator -->
                <div class="h-0.5 bg-blue-500 animate-toast-progress"></div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
@keyframes toast-progress {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}

.animate-toast-progress {
    transform-origin: left;
    animation: toast-progress 5s linear forwards;
}
</style>

<script setup>
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

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
    Menu,
    LogOut,
    Settings,
    User,
    Hotel,
    Layers,
    DoorOpen,
    ClipboardList,
    AlertTriangle,
    CalendarDays,
    MessageSquareWarning,
    GraduationCap,
    UserPlus,
    Ban,
    CircleDot,
    FileCheck2,
    X,
    Boxes,
    CheckCircle2,
    XCircle,
    Bike,
} from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions || {});
const currentPath = computed(() => window.location.pathname);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const canView = (moduleKey) =>
    !moduleKey || (permissions.value[moduleKey] || []).includes("view");

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
                    class="lg:hidden text-gray-500"
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
                                    class="h-4 w-4 text-gray-500"
                                />
                                {{ item.label }}
                            </span>
                            <component
                                :is="
                                    openSections.includes(item.label)
                                        ? ChevronDown
                                        : ChevronRight
                                "
                                class="h-3.5 w-3.5 text-gray-400"
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
                                    : 'text-gray-500'
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
                    <Settings class="h-4 w-4 text-gray-500" /> Settings
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
                <div class="hidden lg:block text-sm text-gray-500">
                    <slot name="header" />
                </div>
                <div class="relative ml-auto">
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
                        <ChevronDown class="h-3.5 w-3.5 text-gray-400" />
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
</template>

<script setup>
import ResidentLayout from "@/Layouts/ResidentLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import {
    AlertCircle,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Coffee,
    Cookie,
    Moon,
    Soup,
    Sparkles,
    Sun,
    UtensilsCrossed,
} from "lucide-vue-next";
import { computed } from "vue";

const props = defineProps({
    menus: {
        type: Array,
        default: () => [],
    },

    todayMenus: {
        type: Array,
        default: () => [],
    },

    weekStart: {
        type: String,
        required: true,
    },

    today: {
        type: String,
        required: true,
    },

    building: {
        type: Object,
        default: null,
    },
});

const meals = [
    {
        key: "breakfast",
        label: "Breakfast",
        icon: Coffee,
        time: "Morning",
        cardClass:
            "border-amber-200 bg-amber-50",
        iconClass:
            "bg-amber-100 text-amber-700",
        titleClass:
            "text-amber-900",
    },
    {
        key: "lunch",
        label: "Lunch",
        icon: Soup,
        time: "Afternoon",
        cardClass:
            "border-emerald-200 bg-emerald-50",
        iconClass:
            "bg-emerald-100 text-emerald-700",
        titleClass:
            "text-emerald-900",
    },
    {
        key: "snacks",
        label: "Snacks",
        icon: Cookie,
        time: "Evening",
        cardClass:
            "border-purple-200 bg-purple-50",
        iconClass:
            "bg-purple-100 text-purple-700",
        titleClass:
            "text-purple-900",
    },
    {
        key: "dinner",
        label: "Dinner",
        icon: Moon,
        time: "Night",
        cardClass:
            "border-blue-200 bg-blue-50",
        iconClass:
            "bg-blue-100 text-blue-700",
        titleClass:
            "text-blue-900",
    },
];

const parseLocalDate = (value) => {
    if (!value) {
        return null;
    }

    const [year, month, day] = String(value)
        .slice(0, 10)
        .split("-")
        .map(Number);

    return new Date(
        year,
        month - 1,
        day,
        12,
        0,
        0,
    );
};

const formatLocalDate = (date) => {
    const year = date.getFullYear();

    const month = String(
        date.getMonth() + 1,
    ).padStart(2, "0");

    const day = String(
        date.getDate(),
    ).padStart(2, "0");

    return `${year}-${month}-${day}`;
};

const days = computed(() => {
    const start = parseLocalDate(
        props.weekStart,
    );

    if (!start) {
        return [];
    }

    return Array.from(
        { length: 7 },
        (_, index) => {
            const date = new Date(start);

            date.setDate(
                start.getDate() + index,
            );

            return {
                date: formatLocalDate(date),

                weekday:
                    date.toLocaleDateString(
                        "en-IN",
                        {
                            weekday: "long",
                        },
                    ),

                shortWeekday:
                    date.toLocaleDateString(
                        "en-IN",
                        {
                            weekday: "short",
                        },
                    ),

                formatted:
                    date.toLocaleDateString(
                        "en-IN",
                        {
                            day: "2-digit",
                            month: "short",
                        },
                    ),

                fullDate:
                    date.toLocaleDateString(
                        "en-IN",
                        {
                            day: "2-digit",
                            month: "long",
                            year: "numeric",
                        },
                    ),

                isToday:
                    formatLocalDate(date) ===
                    props.today,
            };
        },
    );
});

const currentWeekLabel = computed(() => {
    const start = parseLocalDate(
        props.weekStart,
    );

    if (!start) {
        return "";
    }

    const end = new Date(start);

    end.setDate(
        start.getDate() + 6,
    );

    return `${start.toLocaleDateString(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
        },
    )} - ${end.toLocaleDateString(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
        },
    )}`;
});

const menuFor = (date, mealType) => {
    return props.menus.find(
        (menu) =>
            String(menu.menu_date).slice(
                0,
                10,
            ) === date &&
            menu.meal_type === mealType,
    );
};

const todayMenuFor = (mealType) => {
    return props.todayMenus.find(
        (menu) =>
            menu.meal_type === mealType,
    );
};

const todayHasMenu = computed(() => {
    return props.todayMenus.length > 0;
});

const totalMealsThisWeek = computed(() => {
    return props.menus.length;
});

const completeDays = computed(() => {
    return days.value.filter((day) => {
        return meals.every((meal) =>
            Boolean(
                menuFor(day.date, meal.key),
            ),
        );
    }).length;
});

const changeWeek = (weeks) => {
    const date = parseLocalDate(
        props.weekStart,
    );

    if (!date) {
        return;
    }

    date.setDate(
        date.getDate() + weeks * 7,
    );

    router.get(
        route(
            "resident.mess-menu.index",
        ),
        {
            week: formatLocalDate(date),
        },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const previousWeek = () => {
    changeWeek(-1);
};

const nextWeek = () => {
    changeWeek(1);
};

const currentWeek = () => {
    router.get(
        route(
            "resident.mess-menu.index",
        ),
        {},
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const splitItems = (items) => {
    if (!items) {
        return [];
    }

    return String(items)
        .split(/\n|,/)
        .map((item) => item.trim())
        .filter(Boolean);
};
</script>

<template>
    <Head title="Mess Menu" />

    <ResidentLayout title="Mess Menu">
        <div class="space-y-6">
            <!-- Hero -->
            <section
                class="overflow-hidden rounded-3xl border border-orange-200 bg-[linear-gradient(135deg,#7c2d12_0%,#ea580c_52%,#f59e0b_100%)] text-white shadow-xl"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8"
                >
                    <div>
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/10"
                            >
                                <UtensilsCrossed
                                    class="h-7 w-7"
                                />
                            </div>

                            <div>
                                <p
                                    class="text-xs font-bold uppercase tracking-[0.18em] text-white"
                                >
                                    Hostel Dining
                                </p>

                                <h1
                                    class="mt-1 text-2xl font-extrabold text-white md:text-3xl"
                                >
                                    Weekly Mess Menu
                                </h1>
                            </div>
                        </div>

                        <p
                            class="mt-5 max-w-2xl text-sm font-medium leading-6 text-white"
                        >
                            Check today's meals and the
                            complete weekly menu for your
                            hostel building.
                        </p>

                        <div
                            class="mt-5 flex flex-wrap items-center gap-2"
                        >
                            <span
                                class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-black/10 px-4 py-2.5 text-xs font-semibold text-white"
                            >
                                <CalendarDays
                                    class="h-4 w-4"
                                />

                                {{ currentWeekLabel }}
                            </span>

                            <span
                                v-if="building"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-black/10 px-4 py-2.5 text-xs font-semibold text-white"
                            >
                                <UtensilsCrossed
                                    class="h-4 w-4"
                                />

                                {{ building.name }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 rounded-2xl border border-white/20 bg-black/10 p-4"
                    >
                        <div class="text-center">
                            <p
                                class="text-2xl font-black text-white"
                            >
                                {{ totalMealsThisWeek }}
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-wide text-white"
                            >
                                Meals Listed
                            </p>
                        </div>

                        <div class="text-center">
                            <p
                                class="text-2xl font-black text-white"
                            >
                                {{ completeDays }}/7
                            </p>

                            <p
                                class="mt-1 text-[10px] font-bold uppercase tracking-wide text-white"
                            >
                                Complete Days
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- No building warning -->
            <section
                v-if="!building"
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5"
            >
                <AlertCircle
                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                />

                <div>
                    <p
                        class="text-sm font-bold text-amber-900"
                    >
                        Hostel building not assigned
                    </p>

                    <p
                        class="mt-1 text-xs leading-5 text-amber-700"
                    >
                        A building-specific menu cannot be
                        identified because you do not have
                        an active room stay. Common mess
                        menu entries are shown where
                        available.
                    </p>
                </div>
            </section>

            <!-- Today -->
            <section>
                <div
                    class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2
                            class="flex items-center gap-2 text-lg font-bold text-slate-900"
                        >
                            <Sun
                                class="h-5 w-5 text-amber-500"
                            />
                            Today's Menu
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            {{
                                parseLocalDate(
                                    today,
                                )?.toLocaleDateString(
                                    "en-IN",
                                    {
                                        weekday: "long",
                                        day: "2-digit",
                                        month: "long",
                                        year: "numeric",
                                    },
                                )
                            }}
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"
                    >
                        Today's meals
                    </span>
                </div>

                <div
                    v-if="todayHasMenu"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <article
                        v-for="meal in meals"
                        :key="meal.key"
                        class="rounded-2xl border p-5 shadow-sm"
                        :class="
                            meal.cardClass
                        "
                    >
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl"
                                :class="
                                    meal.iconClass
                                "
                            >
                                <component
                                    :is="meal.icon"
                                    class="h-5 w-5"
                                />
                            </div>

                            <span
                                class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-500"
                            >
                                <Clock3
                                    class="h-3 w-3"
                                />
                                {{ meal.time }}
                            </span>
                        </div>

                        <h3
                            class="mt-4 text-sm font-bold"
                            :class="
                                meal.titleClass
                            "
                        >
                            {{ meal.label }}
                        </h3>

                        <template
                            v-if="
                                todayMenuFor(
                                    meal.key,
                                )
                            "
                        >
                            <ul
                                class="mt-3 space-y-1.5"
                            >
                                <li
                                    v-for="item in splitItems(
                                        todayMenuFor(
                                            meal.key,
                                        ).items,
                                    )"
                                    :key="item"
                                    class="flex items-start gap-2 text-sm leading-5 text-slate-700"
                                >
                                    <span
                                        class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-current"
                                    ></span>

                                    <span>
                                        {{ item }}
                                    </span>
                                </li>
                            </ul>

                            <div
                                v-if="
                                    todayMenuFor(
                                        meal.key,
                                    ).special_notes
                                "
                                class="mt-4 rounded-xl border border-white/60 bg-white/60 p-3"
                            >
                                <p
                                    class="flex items-start gap-2 text-xs leading-5 text-slate-700"
                                >
                                    <Sparkles
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600"
                                    />

                                    {{
                                        todayMenuFor(
                                            meal.key,
                                        ).special_notes
                                    }}
                                </p>
                            </div>
                        </template>

                        <p
                            v-else
                            class="mt-3 text-sm text-slate-400"
                        >
                            Menu not added yet.
                        </p>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center"
                >
                    <UtensilsCrossed
                        class="mx-auto h-10 w-10 text-slate-300"
                    />

                    <h3
                        class="mt-3 text-sm font-bold text-slate-700"
                    >
                        Today's menu is not available
                    </h3>

                    <p
                        class="mt-1 text-xs text-slate-500"
                    >
                        Please check the weekly menu below
                        or contact hostel administration.
                    </p>
                </div>
            </section>

            <!-- Week navigation -->
            <section
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="previousWeek"
                >
                    <ChevronLeft
                        class="h-4 w-4"
                    />
                    Previous Week
                </button>

                <div class="text-center">
                    <p
                        class="text-sm font-bold text-slate-900"
                    >
                        {{ currentWeekLabel }}
                    </p>

                    <button
                        type="button"
                        class="mt-1 text-xs font-semibold text-indigo-600 hover:underline"
                        @click="currentWeek"
                    >
                        Go to current week
                    </button>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="nextWeek"
                >
                    Next Week
                    <ChevronRight
                        class="h-4 w-4"
                    />
                </button>
            </section>

            <!-- Weekly menu desktop -->
            <section
                class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block"
            >
                <div class="overflow-x-auto">
                    <table
                        class="min-w-[1100px] w-full text-sm"
                    >
                        <thead
                            class="bg-slate-50"
                        >
                            <tr>
                                <th
                                    class="w-32 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500"
                                >
                                    Meal
                                </th>

                                <th
                                    v-for="day in days"
                                    :key="day.date"
                                    class="min-w-36 px-4 py-4 text-left"
                                    :class="
                                        day.isToday
                                            ? 'bg-indigo-50'
                                            : ''
                                    "
                                >
                                    <p
                                        class="text-xs font-bold"
                                        :class="
                                            day.isToday
                                                ? 'text-indigo-700'
                                                : 'text-slate-700'
                                        "
                                    >
                                        {{
                                            day.shortWeekday
                                        }}
                                    </p>

                                    <p
                                        class="mt-1 text-[10px]"
                                        :class="
                                            day.isToday
                                                ? 'text-indigo-500'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{ day.formatted }}
                                    </p>

                                    <span
                                        v-if="
                                            day.isToday
                                        "
                                        class="mt-2 inline-flex rounded-full bg-indigo-600 px-2 py-0.5 text-[9px] font-bold text-white"
                                    >
                                        Today
                                    </span>
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100"
                        >
                            <tr
                                v-for="meal in meals"
                                :key="meal.key"
                            >
                                <td
                                    class="bg-slate-50/60 px-4 py-5 align-top"
                                >
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            :class="
                                                meal.iconClass
                                            "
                                        >
                                            <component
                                                :is="
                                                    meal.icon
                                                "
                                                class="h-4 w-4"
                                            />
                                        </div>

                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-900"
                                            >
                                                {{
                                                    meal.label
                                                }}
                                            </p>

                                            <p
                                                class="text-[10px] text-slate-400"
                                            >
                                                {{ meal.time }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td
                                    v-for="day in days"
                                    :key="`${day.date}-${meal.key}`"
                                    class="px-4 py-4 align-top"
                                    :class="
                                        day.isToday
                                            ? 'bg-indigo-50/40'
                                            : ''
                                    "
                                >
                                    <template
                                        v-if="
                                            menuFor(
                                                day.date,
                                                meal.key,
                                            )
                                        "
                                    >
                                        <ul
                                            class="space-y-1.5"
                                        >
                                            <li
                                                v-for="item in splitItems(
                                                    menuFor(
                                                        day.date,
                                                        meal.key,
                                                    )
                                                        .items,
                                                )"
                                                :key="item"
                                                class="flex items-start gap-2 text-xs leading-5 text-slate-700"
                                            >
                                                <span
                                                    class="mt-2 h-1 w-1 shrink-0 rounded-full bg-slate-400"
                                                ></span>

                                                <span>
                                                    {{ item }}
                                                </span>
                                            </li>
                                        </ul>

                                        <p
                                            v-if="
                                                menuFor(
                                                    day.date,
                                                    meal.key,
                                                )
                                                    .special_notes
                                            "
                                            class="mt-3 rounded-lg bg-amber-50 p-2 text-[10px] leading-4 text-amber-700"
                                        >
                                            {{
                                                menuFor(
                                                    day.date,
                                                    meal.key,
                                                )
                                                    .special_notes
                                            }}
                                        </p>
                                    </template>

                                    <span
                                        v-else
                                        class="text-xs text-slate-300"
                                    >
                                        Not added
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Weekly menu mobile -->
            <section
                class="space-y-4 lg:hidden"
            >
                <article
                    v-for="day in days"
                    :key="day.date"
                    class="overflow-hidden rounded-2xl border bg-white shadow-sm"
                    :class="
                        day.isToday
                            ? 'border-indigo-300 ring-2 ring-indigo-100'
                            : 'border-slate-200'
                    "
                >
                    <div
                        class="flex items-center justify-between border-b px-5 py-4"
                        :class="
                            day.isToday
                                ? 'border-indigo-100 bg-indigo-50'
                                : 'border-slate-100 bg-slate-50'
                        "
                    >
                        <div>
                            <p
                                class="text-sm font-bold"
                                :class="
                                    day.isToday
                                        ? 'text-indigo-900'
                                        : 'text-slate-900'
                                "
                            >
                                {{ day.weekday }}
                            </p>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                {{ day.fullDate }}
                            </p>
                        </div>

                        <span
                            v-if="day.isToday"
                            class="rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-bold text-white"
                        >
                            Today
                        </span>
                    </div>

                    <div
                        class="divide-y divide-slate-100"
                    >
                        <div
                            v-for="meal in meals"
                            :key="meal.key"
                            class="p-4"
                        >
                            <div
                                class="flex items-start gap-3"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                    :class="
                                        meal.iconClass
                                    "
                                >
                                    <component
                                        :is="
                                            meal.icon
                                        "
                                        class="h-4 w-4"
                                    />
                                </div>

                                <div
                                    class="min-w-0 flex-1"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <p
                                            class="text-sm font-bold text-slate-900"
                                        >
                                            {{
                                                meal.label
                                            }}
                                        </p>

                                        <span
                                            class="text-[10px] text-slate-400"
                                        >
                                            {{ meal.time }}
                                        </span>
                                    </div>

                                    <template
                                        v-if="
                                            menuFor(
                                                day.date,
                                                meal.key,
                                            )
                                        "
                                    >
                                        <ul
                                            class="mt-2 space-y-1.5"
                                        >
                                            <li
                                                v-for="item in splitItems(
                                                    menuFor(
                                                        day.date,
                                                        meal.key,
                                                    )
                                                        .items,
                                                )"
                                                :key="item"
                                                class="flex items-start gap-2 text-xs leading-5 text-slate-600"
                                            >
                                                <span
                                                    class="mt-2 h-1 w-1 shrink-0 rounded-full bg-slate-400"
                                                ></span>

                                                <span>
                                                    {{ item }}
                                                </span>
                                            </li>
                                        </ul>

                                        <p
                                            v-if="
                                                menuFor(
                                                    day.date,
                                                    meal.key,
                                                )
                                                    .special_notes
                                            "
                                            class="mt-3 rounded-lg bg-amber-50 p-2 text-[10px] leading-4 text-amber-700"
                                        >
                                            {{
                                                menuFor(
                                                    day.date,
                                                    meal.key,
                                                )
                                                    .special_notes
                                            }}
                                        </p>
                                    </template>

                                    <p
                                        v-else
                                        class="mt-2 text-xs text-slate-300"
                                    >
                                        Menu not added.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Note -->
            <section
                class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-5"
            >
                <AlertCircle
                    class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"
                />

                <div>
                    <p
                        class="text-sm font-bold text-blue-900"
                    >
                        Menu changes
                    </p>

                    <p
                        class="mt-1 text-xs leading-5 text-blue-700"
                    >
                        The administration may update meal
                        items or special notes based on
                        ingredient availability and hostel
                        requirements.
                    </p>
                </div>
            </section>
        </div>
    </ResidentLayout>
</template>
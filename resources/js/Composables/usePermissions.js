import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

export function usePermissions() {
    const page = usePage();

    const user = computed(
        () => page.props.auth?.user ?? null,
    );

    const permissions = computed(
        () => page.props.auth?.permissions ?? {},
    );

    const isSuperAdmin = computed(
        () => user.value?.role === "super_admin",
    );

    const can = (
        moduleKey,
        action = "view",
    ) => {
        if (isSuperAdmin.value) {
            return true;
        }

        return (
            permissions.value[moduleKey] ?? []
        ).includes(action);
    };

    const cannot = (
        moduleKey,
        action = "view",
    ) => {
        return !can(moduleKey, action);
    };

    const canAny = (
        moduleKey,
        actions = [],
    ) => {
        if (isSuperAdmin.value) {
            return true;
        }

        return actions.some((action) =>
            can(moduleKey, action),
        );
    };

    const canAll = (
        moduleKey,
        actions = [],
    ) => {
        if (isSuperAdmin.value) {
            return true;
        }

        return actions.every((action) =>
            can(moduleKey, action),
        );
    };

    const canView = (moduleKey) => {
        return can(moduleKey, "view");
    };

    return {
        user,
        permissions,
        isSuperAdmin,
        can,
        cannot,
        canAny,
        canAll,
        canView,
    };
}
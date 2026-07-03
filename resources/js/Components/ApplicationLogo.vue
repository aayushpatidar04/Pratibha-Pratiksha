<template>
    <img
        src="/assets/images/pratibha-pratiksha-logo-text.png"
        alt="Pratibha Pratiksha Logo"
        :style="{ width: width + 'px', height: 'auto' }"
    />
</template>

<script>
export default {
    name: "ApplicationLogo",
    props: {
        width: {
            type: Number,
            default: 200,
        },
    },
};
</script>

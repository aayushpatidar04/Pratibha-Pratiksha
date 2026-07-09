<script setup>
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from "chart.js";

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
);

defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    height: { type: Number, default: 260 },
    stacked: { type: Boolean, default: false },
});

const baseOptions = (stacked) => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: { boxWidth: 10, font: { size: 11 } },
        },
        tooltip: { mode: "index", intersect: false },
    },
    scales: {
        x: { stacked, grid: { display: false } },
        y: { stacked, beginAtZero: true, grid: { color: "#f3f4f6" } },
    },
});
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Bar :data="{ labels, datasets }" :options="baseOptions(stacked)" />
    </div>
</template>

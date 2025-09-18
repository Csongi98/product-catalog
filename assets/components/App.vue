<script setup>
import Menubar from "primevue/menubar";
import { useRouter } from "vue-router";

const router = useRouter();

const items = [
    {
        label: "Főoldal",
        icon: "pi pi-home",
        command: () => router.push({ name: "home" }),
    },
    {
        label: "Termékek",
        icon: "pi pi-shopping-cart",
        command: () => router.push({ name: "catalog" }),
    },
];
</script>

<template>
    <div>
        <Menubar :model="items" class="shadow-sm" />
        <main class="p-6">
            <RouterView />
        </main>
    </div>
</template>

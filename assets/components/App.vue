<script setup>
import Menubar from "primevue/menubar";
import Button from "primevue/button";
import Badge from "primevue/badge";
import { useRouter, RouterView, RouterLink } from "vue-router";
import { useCart } from "../composables/useCart";
import Toast from "primevue/toast";

const router = useRouter();
const { count } = useCart();

const items = [
    {
        label: "Főoldal",
        icon: "pi pi-home",
        command: () => router.push({ name: "home" }),
    },
    {
        label: "Termékek",
        icon: "pi pi-list",
        command: () => router.push({ name: "catalog" }),
    },
];
</script>

<template>
    <div>
        <Menubar :model="items" class="shadow-sm">
            <!-- Jobb oldal (kosár ikon) -->
            <template #end>
                <RouterLink to="/cart" class="relative">
                    <Button icon="pi pi-shopping-cart" text rounded />
                    <Badge
                        v-if="count"
                        :value="count"
                        severity="danger"
                        class="absolute -top-2 -right-2"
                    />
                </RouterLink>
            </template>
        </Menubar>

        <main class="lg:p-6">
            <RouterView />
        </main>
        <Toast />
    </div>
</template>

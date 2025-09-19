<template>
    <div class="max-w-6xl mx-auto">
        <Message v-if="error" severity="error" class="mb-4">
            {{ error }}
        </Message>

        <div v-else-if="loading" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-2">
                <Skeleton height="28rem" class="rounded-2xl" />
            </div>
            <div class="lg:col-span-3 space-y-3">
                <Skeleton width="60%" height="2rem" />
                <Skeleton width="30%" height="1.5rem" />
                <Skeleton height="6rem" />
                <div class="flex gap-2">
                    <Skeleton width="8rem" height="2.5rem" />
                    <Skeleton width="8rem" height="2.5rem" />
                </div>
            </div>
        </div>

        <div v-else-if="p" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <Card
                class="lg:col-span-2 rounded-2xl flex items-center justify-center w-full"
            >
                <template #content>
                    <div
                        class="w-full flex items-center justify-center bg-white"
                    >
                        <Image
                            v-if="p.imageUrl"
                            :src="p.imageUrl"
                            :alt="p.name"
                            preview
                            class="max-h-[28rem] object-contain mx-auto"
                            imageClass="max-h-[28rem] object-contain mx-auto"
                        />
                        <div
                            v-else
                            class="aspect-[4/3] w-full bg-gray-100 rounded-xl"
                        ></div>
                    </div>
                </template>
            </Card>

            <div class="lg:col-span-3 space-y-4 w-full">
                <Card class="rounded-2xl">
                    <template #title>
                        <div class="flex items-start justify-between gap-3">
                            <h1 class="text-2xl font-semibold leading-snug">
                                {{ p.name }}
                            </h1>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex items-end gap-4 mt-1">
                            <div class="text-2xl font-bold">
                                {{ toFt(p.price) }}
                            </div>
                            <div
                                v-if="p.netPrice != null"
                                class="text-sm text-slate-500"
                            >
                                Nettó: {{ toFt(p.netPrice) }}
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <Button
                                icon="pi pi-shopping-cart"
                                label="Kosárba"
                                @click="addToCart(p)"
                            />
                            <Button
                                icon="pi pi-heart"
                                label="Kedvencek"
                                severity="secondary"
                                outlined
                            />
                        </div>
                    </template>
                </Card>

                <Card class="rounded-2xl">
                    <template #title>
                        <span class="text-lg font-semibold">Leírás</span>
                    </template>
                    <template #content>
                        <p class="text-slate-700 whitespace-pre-line">
                            {{
                                p.description ||
                                "Ehhez a termékhez még nincs részletes leírás."
                            }}
                        </p>
                    </template>
                </Card>

                <div class="pt-2">
                    <RouterLink to="/catalog">
                        <Button
                            icon="pi pi-arrow-left"
                            label="Vissza a katalógushoz"
                            text
                        />
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useToast } from "primevue/usetoast";

import Card from "primevue/card";
import Button from "primevue/button";
import Image from "primevue/image";
import Skeleton from "primevue/skeleton";
import Message from "primevue/message";
import InputNumber from "primevue/inputnumber";
import { useCart } from "../composables/useCart";

const { add } = useCart();
const qty = ref(1);

const route = useRoute();
const toast = useToast();

const loading = ref(true);
const error = ref("");
const p = ref(null);

function toFt(cents) {
    if (cents == null) return "";
    return (
        new Intl.NumberFormat("hu-HU").format(Math.round(cents / 100)) + " Ft"
    );
}

function addToCart(product) {
    add(product, 1);
    toast.add({
        severity: "success",
        summary: "Kosár",
        detail: `"${product.name}" hozzáadva a kosárhoz.`,
        life: 3000,
    });
}

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const res = await fetch(`/api/products/${route.params.id}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        p.value = await res.json();
    } catch (e) {
        error.value = "Nem található a termék.";
        toast.add({
            severity: "error",
            summary: "Hiba",
            detail: "A termék nem tölthető be.",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
}

onMounted(load);
watch(() => route.params.id, load);
</script>

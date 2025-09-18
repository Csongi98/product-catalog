<script setup>
import { ref, onMounted } from "vue";
import { RouterLink } from "vue-router";

import Card from "primevue/card";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import Carousel from "primevue/carousel";
import Message from "primevue/message";

const items = ref([]);
const loading = ref(true);
const error = ref("");

function toFt(cents) {
    if (cents == null) return "";
    return (
        new Intl.NumberFormat("hu-HU").format(Math.round(cents / 100)) + " Ft"
    );
}

async function load() {
    loading.value = true;
    error.value = "";
    try {
        const res = await fetch("/api/products/random");
        if (!res.ok) throw new Error("API error");
        const data = await res.json();
        items.value = data.items ?? [];
    } catch (e) {
        console.error(e);
        error.value = "Nem sikerült betölteni az ajánlott termékeket.";
    } finally {
        loading.value = false;
    }
}

onMounted(load);

const responsiveOptions = [
    { breakpoint: "1280px", numVisible: 3, numScroll: 3 },
    { breakpoint: "1024px", numVisible: 2, numScroll: 2 },
    { breakpoint: "640px", numVisible: 1, numScroll: 1 },
];
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">Ajánlott termékek</h2>
            <Button icon="pi pi-refresh" text @click="load" />
        </div>

        <Message v-if="error" severity="error" class="mb-4">{{
            error
        }}</Message>
        <div
            v-else-if="loading"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
        >
            <Card v-for="i in 4" :key="i" class="rounded-2xl">
                <template #content>
                    <Skeleton class="w-full aspect-[4/3] mb-3 rounded-xl" />
                    <Skeleton width="80%" class="mb-2" />
                    <Skeleton width="40%" class="mb-3" />
                    <Skeleton width="60%" />
                </template>
            </Card>
        </div>

        <Carousel
            v-else
            :value="items"
            :numVisible="4"
            :numScroll="4"
            :responsiveOptions="responsiveOptions"
            circular
            class="recommend-carousel"
        >
            <template #item="{ data: p }">
                <Card class="rounded-2xl h-full hover:shadow transition">
                    <template #content>
                        <RouterLink
                            :to="{ name: 'product', params: { id: p.id } }"
                            class="block"
                        >
                            <div
                                class="aspect-[4/3] w-full overflow-hidden rounded-xl bg-gray-100"
                            >
                                <img
                                    v-if="p.imageUrl"
                                    :src="p.imageUrl"
                                    :alt="p.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>

                            <div class="mt-3">
                                <div
                                    class="font-semibold line-clamp-2 min-h-[3rem]"
                                >
                                    {{ p.name }}
                                </div>

                                <div
                                    class="mt-2 flex items-center justify-between"
                                >
                                    <div class="text-gray-800 font-bold">
                                        {{ toFt(p.price) }}
                                    </div>
                                    <Tag
                                        v-if="p.stock === 0"
                                        value="Elfogyott"
                                        severity="danger"
                                    />
                                </div>

                                <p
                                    class="mt-2 text-sm text-gray-500 line-clamp-2"
                                >
                                    {{ p.description }}
                                </p>
                            </div>
                        </RouterLink>

                        <div class="mt-3 flex gap-2">
                            <Button
                                icon="pi pi-shopping-cart"
                                label="Kosárba"
                                size="small"
                            />
                            <Button icon="pi pi-heart" outlined size="small" />
                        </div>
                    </template>
                </Card>
            </template>
        </Carousel>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.recommend-carousel :deep(.p-carousel-content) {
    padding-bottom: 0.25rem;
}
</style>

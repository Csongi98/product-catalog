<script setup>
import { useCart } from "../composables/useCart";
import { toFt } from "../utils/format";
import Button from "primevue/button";
import InputNumber from "primevue/inputnumber";
import Card from "primevue/card";
import Message from "primevue/message";
import { RouterLink } from "vue-router";

const { items, remove, setQty, clear, total } = useCart();
</script>

<template>
    <div class="max-w-5xl mx-auto space-y-4">
        <h1 class="text-2xl font-semibold">Kosár</h1>

        <Message v-if="!items.length" severity="info">
            A kosár üres.
            <RouterLink to="/catalog" class="text-blue-600 underline ml-1"
                >Vissza a katalógushoz</RouterLink
            >
        </Message>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="lg:col-span-2 space-y-3">
                <Card
                    v-for="it in items"
                    :key="it.id"
                    class="rounded-2xl flex justify-between gap-4"
                >
                    <template #content>
                        <div class="flex items-center justify-betweenm gap-4">
                            <img
                                v-if="it.imageUrl"
                                :src="it.imageUrl"
                                class="w-20 h-20 object-cover rounded"
                            />
                            <div class="flex-1 min-w-0">
                                <RouterLink
                                    :to="{
                                        name: 'product',
                                        params: { id: it.id },
                                    }"
                                    class="font-medium hover:underline line-clamp-2"
                                >
                                    {{ it.name }}
                                </RouterLink>
                                <div class="text-sm text-slate-600 mt-1">
                                    {{ toFt(it.price) }}
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 justify-between"
                            >
                                <InputNumber
                                    :modelValue="it.qty"
                                    @update:modelValue="(v) => setQty(it.id, v)"
                                    :min="1"
                                    showButtons
                                    buttonLayout="vertical"
                                    class="w-2"
                                />
                                <div class="w-28 text-right font-semibold">
                                    {{ toFt(it.price * it.qty) }}
                                </div>
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    @click="remove(it.id)"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <div class="flex items-center justify-between">
                    <RouterLink to="/catalog"
                        ><Button label="Vásárlás folytatása" text
                    /></RouterLink>
                    <Button
                        label="Kosár ürítése"
                        text
                        severity="secondary"
                        @click="clear"
                    />
                </div>
            </div>
            <Card class="lg:col-span-1 rounded-2xl h-fit w-full">
                <template #title
                    ><div class="text-lg font-semibold">
                        Összegzés
                    </div></template
                >
                <template #content>
                    <div class="flex items-center justify-between py-1">
                        <span>Termékek ({{ items.length }} fajta)</span>
                        <span class="font-medium">{{ toFt(total) }}</span>
                    </div>
                    <div class="text-sm text-slate-500 mb-4">
                        Szállítás a következő lépésben.
                    </div>
                    <RouterLink to="/checkout">
                        <Button
                            label="Tovább a pénztárhoz"
                            icon="pi pi-arrow-right"
                            class="w-full"
                        />
                    </RouterLink>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

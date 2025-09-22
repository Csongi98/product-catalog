<script setup>
import { ref } from "vue";
import { useCart } from "../composables/useCart";
import { toFt } from "../utils/format";

import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Message from "primevue/message";
import Card from "primevue/card";
import Divider from "primevue/divider";
import { useToast } from "primevue/usetoast";
import FloatLabel from "primevue/floatlabel";

const toast = useToast();
const { items, total, clear } = useCart();

const name = ref("");
const email = ref("");
const zip = ref("");
const city = ref("");
const address = ref("");
const phone = ref("");
const loading = ref(false);
const ok = ref(false);
const err = ref("");

async function submit() {
    err.value = "";
    ok.value = false;

    if (!items.value.length) {
        err.value = "A kosár üres.";
        return;
    }
    if (!name.value?.trim()) {
        err.value = "A név megadása kötelező.";
        return;
    }
    if (!email.value?.trim()) {
        err.value = "Az e-mail cím megadása kötelező.";
        return;
    }
    if (!zip.value?.trim()) {
        err.value = "Az irányítószám megadása kötelező.";
        return;
    }
    if (!city.value?.trim()) {
        err.value = "A város megadása kötelező.";
        return;
    }
    if (!address.value?.trim()) {
        err.value = "Az utca / házszám megadása kötelező.";
        return;
    }
    if (!phone.value?.trim()) {
        err.value = "A telefonszám megadása kötelező.";
        return;
    }

    const phoneRegex = /^\+?[0-9]{6,20}$/;
    if (!phoneRegex.test(phone.value)) {
        err.value = "Érvénytelen telefonszám.";
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
        err.value = "Érvénytelen e-mail cím.";
        return;
    }

    loading.value = true;
    try {
        const res = await fetch("/api/checkout", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/ld+json",
            },
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                zip: zip.value,
                city: city.value,
                address: address.value,
                phone: phone.value,
                items: items.value.map((x) => ({
                    id: x.id,
                    name: x.name,
                    price: x.price,
                    qty: x.qty,
                })),
                total: total.value,
            }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            if (res.status === 422 && data.errors) {
                err.value = data.errors
                    .map((e) => `${e.field}: ${e.msg}`)
                    .join(", ");
            } else {
                err.value = "Nem sikerült leadni a rendelést.";
            }
            return;
        }
        const data = await res.json();
        ok.value = true;
        toast.add({
            severity: "success",
            summary: "Rendelés leadva",
            detail: `Rendelésszám: ${data.orderNo}`,
            life: 4000,
        });
        clear();
        name.value = "";
        email.value = "";
        zip.value = "";
        city.value = "";
        address.value = "";
        phone.value = "";
    } catch {
        err.value = "Hálózati hiba.";
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="items-center justify-center flex flex-col">
        <div class="space-y-4 max:sm:w-full sm:w-2/3">
            <h1 class="text-2xl font-semibold">Pénztár</h1>

            <Message v-if="ok" severity="success"
                >Köszönjük! A visszaigazolást elküldtük e-mailben.</Message
            >
            <Message v-if="err" severity="error">{{ err }}</Message>

            <Card class="rounded-2xl w-full">
                <template #title>Vásárlói adatok</template>
                <template #content>
                    <form
                        @submit.prevent="submit"
                        class="w-full flex flex-col gap-5 pt-4"
                    >
                        <FloatLabel>
                            <InputText
                                id="name"
                                v-model="name"
                                class="w-full"
                            />
                            <label for="name">Név</label>
                        </FloatLabel>

                        <FloatLabel>
                            <InputText
                                id="email"
                                v-model="email"
                                class="w-full"
                            />
                            <label for="email">E-mail</label>
                        </FloatLabel>

                        <FloatLabel>
                            <InputText id="zip" v-model="zip" class="w-full" />
                            <label for="zip">Irányítószám</label>
                        </FloatLabel>

                        <FloatLabel>
                            <InputText
                                id="city"
                                v-model="city"
                                class="w-full"
                            />
                            <label for="city">Város</label>
                        </FloatLabel>

                        <FloatLabel class="sm:col-span-2">
                            <InputText
                                id="address"
                                v-model="address"
                                class="w-full"
                            />
                            <label for="address">Utca / házszám</label>
                        </FloatLabel>

                        <FloatLabel class="sm:col-span-2">
                            <InputText
                                id="phone"
                                v-model="phone"
                                class="w-full"
                            />
                            <label for="phone">Telefonszám</label>
                        </FloatLabel>

                        <Divider />
                        <Button
                            :loading="loading"
                            label="Rendelés leadása"
                            icon="pi pi-check"
                            type="submit"
                        />
                    </form>
                </template>
            </Card>
        </div>

        <div class="pt-6 sm:w-2/3">
            <Card class="rounded-2xl">
                <template #title>Kosár összegzés</template>
                <template #content>
                    <div v-if="!items.length" class="text-slate-600">
                        A kosár üres.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="it in items"
                            :key="it.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="line-clamp-2 mr-2"
                                >{{ it.name }} × {{ it.qty }}</span
                            >
                            <span class="font-medium">{{
                                toFt(it.price * it.qty)
                            }}</span>
                        </div>
                        <Divider />
                        <div
                            class="flex items-center justify-between text-lg font-semibold"
                        >
                            <span>Végösszeg</span>
                            <span>{{ toFt(total.value) }}</span>
                        </div>
                    </div>
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

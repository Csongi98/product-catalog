<template>
    <!-- 
      Termékek oldal felépítése:
      - Cím
      - Szűrőgombok (összes termék / kategóriák)
      - Oldalsáv (desktop) vagy Sidebar (mobil) kategóriaválasztáshoz
      - Terméklista rácsban
    -->
    <div class="">
        <div class="flex items-center gap-3 py-4">
            <h1 class="text-xl md:text-2xl font-semibold w-full text-center">
                Termékek
            </h1>
        </div>

        <div class="items-center gap-2 py-4 flex justify-center">
            <Button
                v-if="selectedCategory"
                icon="pi pi-times"
                label="Összes termék"
                severity="secondary"
                outlined
                @click="resetCategory"
            />
            <Button
                v-if="!isXL"
                icon="pi pi-filter"
                label="Kategóriák"
                @click="mobileOpen = true"
            />
        </div>

        <div class="flex gap-6" :class="isXL ? 'grid-cols-4' : ''">
            <aside v-if="isXL" class="col-span-1">
                <div
                    class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-auto w-96"
                >
                    <CategorySidebar
                        @select="onSelectCategory"
                        @path-change="() => {}"
                    />
                </div>
            </aside>

            <section :class="isXL ? 'col-span-3' : ''">
                <ProductGrid :categoryId="selectedCategory" />
            </section>
        </div>

        <Sidebar
            v-if="!isXL"
            v-model:visible="mobileOpen"
            position="left"
            class="w-11/12 sm:w-96"
            :dismissable="true"
            :modal="true"
            :showCloseIcon="true"
            header="Kategóriák"
        >
            <CategorySidebar
                v-if="mobileOpen"
                @select="onSelectCategory"
                @path-change="() => {}"
            />
        </Sidebar>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import CategorySidebar from "./CategorySidebar.vue";
import ProductGrid from "./ProductGrid.vue";
import Sidebar from "primevue/sidebar";
import Button from "primevue/button";

const selectedCategory = ref(null);
const mobileOpen = ref(false);
const isXL = ref(false);

let mql;
let onChange;

onMounted(() => {
    mql = window.matchMedia("(min-width: 1540px)");
    onChange = (e) => {
        isXL.value = e.matches;
        if (e.matches) mobileOpen.value = false;
    };
    isXL.value = mql.matches;
    if (mql.addEventListener) mql.addEventListener("change", onChange);
    else mql.addListener(onChange);
});

onBeforeUnmount(() => {
    if (!mql || !onChange) return;
    if (mql.removeEventListener) mql.removeEventListener("change", onChange);
    else mql.removeListener(onChange);
});

function onSelectCategory(id) {
    selectedCategory.value = id;
    mobileOpen.value = false;
}
function resetCategory() {
    selectedCategory.value = null;
}
</script>

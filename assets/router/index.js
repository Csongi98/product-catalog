import { createRouter, createWebHistory } from "vue-router";
import HomeApp from "../components/HomeApp.vue";
import CatalogApp from "../components/CatalogApp.vue";
import ProductDetail from "../components/ProductDetail.vue";
import CartView from "../components/CartView.vue";
import CheckoutView from "../components/CheckoutView.vue";

const routes = [
    { path: "/", name: "home", component: HomeApp },
    { path: "/catalog", name: "catalog", component: CatalogApp },
    {
        path: "/product/:id(\\d+)",
        name: "product",
        component: ProductDetail,
        props: true,
    },
    { path: "/cart", name: "cart", component: CartView },
    { path: "/checkout", name: "checkout", component: CheckoutView },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

export default router;

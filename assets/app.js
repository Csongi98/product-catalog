import "./styles/tailwind.css";
import { createApp } from "vue";
import App from "./components/App.vue";
import router from "./router";

import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import ToastService from "primevue/toastservice";
import MegaMenu from "primevue/megamenu";

import "primeicons/primeicons.css";
import "primeflex/primeflex.css";

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: { preset: Aura, options: { darkModeSelector: false } },
});
app.component("MegaMenu", MegaMenu);
app.use(ToastService);

app.mount("#app");

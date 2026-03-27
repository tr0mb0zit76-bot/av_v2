// resources/js/app.js
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import axios from 'axios';
import App from './App.vue';
import { useAiStore } from './stores/ai';

// Настройка axios
axios.defaults.baseURL = '/';
axios.defaults.withCredentials = true;

// Получаем CSRF токен и устанавливаем в заголовки
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);
app.use(router);

const aiStore = useAiStore();
aiStore.loadHistory();

app.mount('#app');
<!-- resources/js/App.vue -->
<template>
  <router-view />
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const route = useRoute();

// Публичные страницы, где не нужно проверять авторизацию
const publicPages = ['/', '/login', '/register', '/password/reset'];

const isPublicPage = () => {
  return publicPages.includes(route.path);
};

const checkAuth = async () => {
  // Только если страница не публичная и пользователь не авторизован
  if (!isPublicPage() && !authStore.isAuthenticated) {
    await authStore.fetchUser();
  }
};

onMounted(() => {
  checkAuth();
});

// Следим за изменением маршрута
watch(() => route.path, () => {
  checkAuth();
});
</script>
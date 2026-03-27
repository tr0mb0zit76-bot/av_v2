import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Импортируем MainLayout
import MainLayout from '@/layouts/MainLayout.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/views/Home.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/Login.vue')
    },
    // Все защищенные маршруты оборачиваем в MainLayout
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('@/views/Dashboard.vue'),
            },
            {
                path: 'orders',
                name: 'orders',
                component: () => import('@/views/Orders.vue'),
            },
            {
                path: 'cargos',
                name: 'cargos',
                component: () => import('@/views/Cargos.vue'),
            },
            {
                path: 'contractors',
                name: 'contractors',
                component: () => import('@/views/Contractors.vue'),
            },
            {
                path: 'reports',
                name: 'reports',
                component: () => import('@/views/Reports.vue'),
            },
            {
                path: 'users',
                name: 'users',
                component: () => import('@/views/Users.vue'),
                meta: { adminOnly: true }
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/views/Settings.vue'),
                meta: { adminOnly: true }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Если еще нет пользователя, пробуем получить (только для защищенных маршрутов)
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        await authStore.fetchUser();
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else if (to.path === '/login' && authStore.isAuthenticated) {
        next('/dashboard');
    } else if (to.meta.adminOnly && !authStore.isAdmin) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;
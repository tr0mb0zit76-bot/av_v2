import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.user,
        isAdmin: (state) => state.user?.role?.name === 'admin',
        userName: (state) => state.user?.name || 'Пользователь',
        userRole: (state) => state.user?.role?.display_name || state.user?.role?.name || 'Пользователь'
    },
    
    actions: {
        async login(email, password) {
            this.loading = true;
            try {
                const response = await axios.post('/api/login', { email, password });
                this.user = response.data.user;
                return { success: true };
            } catch (error) {
                return { 
                    success: false, 
                    message: error.response?.data?.message || 'Ошибка входа' 
                };
            } finally {
                this.loading = false;
            }
        },
        
        async logout() {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                window.location.href = '/login';
            }
        },
        
        async fetchUser() {
            if (this.user) return;
            
            try {
                const response = await axios.get('/api/user');
                this.user = response.data;
            } catch (error) {
                if (error.response?.status !== 401) {
                    console.error('Fetch user error:', error);
                }
                this.user = null;
            }
        }
    }
});
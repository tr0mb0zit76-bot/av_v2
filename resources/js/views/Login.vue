<template>
  <div class="login-page">
    <div class="login-container">
      <div class="logo">
        <h1>🚛 Автопартнер</h1>
        <p>Логистическая платформа</p>
      </div>
      
      <div v-if="error" class="error-message">
        {{ error }}
      </div>
      
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email</label>
          <input 
            type="email" 
            v-model="email" 
            placeholder="Введите email"
            required
            autofocus
          />
        </div>
        
        <div class="form-group">
          <label>Пароль</label>
          <input 
            type="password" 
            v-model="password" 
            placeholder="Введите пароль"
            required
          />
        </div>
        
        <button type="submit" :disabled="loading" class="login-btn">
          {{ loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>
      
      <div class="back-link">
        <router-link to="/">← На главную</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  
  const result = await authStore.login(email.value, password.value);
  
  if (result.success) {
    router.push('/dashboard');
  } else {
    error.value = result.message;
  }
  
  loading.value = false;
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: var(--bg-warm);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-container {
  background: var(--bg-card);
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  padding: 40px;
  width: 100%;
  max-width: 400px;
  border: 1px solid var(--border-light);
}

.logo {
  text-align: center;
  margin-bottom: 30px;
}

.logo h1 {
  color: var(--primary-dark);
  font-size: 28px;
}

.logo p {
  color: var(--text-soft);
  margin-top: 5px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  color: var(--text-soft);
  font-weight: 500;
}

input {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
  background: var(--bg-card);
  color: var(--text-dark);
}

input:focus {
  outline: none;
  border-color: var(--accent-main);
  box-shadow: 0 0 0 3px rgba(193,123,75,0.1);
}

.login-btn {
  width: 100%;
  padding: 12px;
  background: var(--accent-main);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.login-btn:hover:not(:disabled) {
  background: #b06a3d;
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  text-align: center;
}

.back-link {
  margin-top: 20px;
  text-align: center;
}

.back-link a {
  color: var(--accent-main);
  text-decoration: none;
  font-size: 14px;
}

.back-link a:hover {
  text-decoration: underline;
}
</style>
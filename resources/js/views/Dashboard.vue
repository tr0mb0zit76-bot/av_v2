<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h1>Дашборд</h1>
      <p>Добро пожаловать, {{ authStore.userName }}!</p>
    </div>
    
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Всего заказов</h3>
        <div class="stat-value">{{ stats.total_orders || 0 }}</div>
      </div>
      <div class="stat-card">
        <h3>Активных перевозок</h3>
        <div class="stat-value">{{ stats.active_orders || 0 }}</div>
      </div>
      <div class="stat-card">
        <h3>Средний KPI</h3>
        <div class="stat-value">{{ (stats.avg_kpi || 0).toFixed(1) }}%</div>
      </div>
      <div class="stat-card">
        <h3>Общая выручка</h3>
        <div class="stat-value">{{ formatNumber(stats.total_revenue) }} ₽</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const stats = ref({});

const formatNumber = (num) => {
  if (!num) return '0';
  return new Intl.NumberFormat('ru-RU').format(num);
};

onMounted(async () => {
  try {
    const response = await axios.get('/api/stats');
    stats.value = response.data;
  } catch (error) {
    console.error('Error loading stats:', error);
  }
});
</script>

<style scoped>
.dashboard-header {
  margin-bottom: 24px;
}

.dashboard-header h1 {
  font-size: 28px;
  color: var(--primary-dark);
  margin-bottom: 8px;
}

.dashboard-header p {
  color: var(--text-soft);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--bg-card);
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border: 1px solid var(--border-light);
}

.stat-card h3 {
  color: var(--text-soft);
  font-size: 14px;
  margin-bottom: 10px;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: var(--accent-main);
}
</style>
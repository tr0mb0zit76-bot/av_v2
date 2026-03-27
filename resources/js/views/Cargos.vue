<template>
  <div class="cargos-page">
    <div class="page-header">
      <h1>Грузы</h1>
      <button @click="createCargo" class="btn-primary">+ Добавить груз</button>
    </div>
    
    <div v-if="loading" class="loading">Загрузка...</div>
    
    <div v-else class="cargos-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Вес (кг)</th>
            <th>Объем (м³)</th>
            <th>Тип груза</th>
            <th>Опасный</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cargo in cargos" :key="cargo.id">
            <td>{{ cargo.id }}</td>
            <td>{{ cargo.title }}</td>
            <td>{{ cargo.weight || '—' }}</td>
            <td>{{ cargo.volume || '—' }}</td>
            <td>{{ cargo.cargo_type || '—' }}</td>
            <td>{{ cargo.is_hazardous ? 'Да' : 'Нет' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const cargos = ref([]);
const loading = ref(true);

const createCargo = () => {
  alert('Функция добавления груза в разработке');
};

const loadCargos = async () => {
  try {
    const response = await axios.get('/api/cargos');
    cargos.value = response.data;
  } catch (error) {
    console.error('Error loading cargos:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadCargos();
});
</script>

<style scoped>
.cargos-page {
  background: white;
  border-radius: 12px;
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.btn-primary {
  background: #c17b4b;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.cargos-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th {
  background: #f8f9fa;
  font-weight: 600;
  color: #666;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}
</style>
<template>
  <div class="contractors-page">
    <div class="page-header">
      <h1>Контрагенты</h1>
      <button @click="createContractor" class="btn-primary">+ Добавить</button>
    </div>
    
    <div v-if="loading" class="loading">Загрузка...</div>
    
    <div v-else class="contractors-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>ИНН</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Тип</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="contractor in contractors" :key="contractor.id">
            <td>{{ contractor.id }}</td>
            <td>{{ contractor.name }}</td>
            <td>{{ contractor.inn || '—' }}</td>
            <td>{{ contractor.phone || '—' }}</td>
            <td>{{ contractor.email || '—' }}</td>
            <td>{{ contractor.type }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const contractors = ref([]);
const loading = ref(true);

const createContractor = () => {
  alert('Функция добавления контрагента в разработке');
};

const loadContractors = async () => {
  try {
    const response = await axios.get('/api/contractors');
    contractors.value = response.data;
  } catch (error) {
    console.error('Error loading contractors:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadContractors();
});
</script>

<style scoped>
.contractors-page {
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

.contractors-table {
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
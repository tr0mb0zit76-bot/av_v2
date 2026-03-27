<!-- resources/js/components/orders/OrderFilters.vue -->
<template>
  <div class="order-filters">
    <div class="filters-row">
      <div class="filter-group">
        <label>Дата с</label>
        <input 
          type="date" 
          :value="localFilters.date_from" 
          @input="updateFilter('date_from', $event.target.value)"
          class="filter-input"
        >
      </div>
      
      <div class="filter-group">
        <label>Дата по</label>
        <input 
          type="date" 
          :value="localFilters.date_to" 
          @input="updateFilter('date_to', $event.target.value)"
          class="filter-input"
        >
      </div>
      
      <div class="filter-group">
        <label>Статус</label>
        <select 
          :value="localFilters.status" 
          @change="updateFilter('status', $event.target.value)"
          class="filter-select"
        >
          <option value="">Все статусы</option>
          <option value="new">Новый</option>
          <option value="in_transit">В пути</option>
          <option value="completed">Завершен</option>
          <option value="cancelled">Отменен</option>
        </select>
      </div>
      
      <div class="filter-group search-group">
        <label>Поиск</label>
        <input 
          type="text" 
          :value="localFilters.search" 
          @input="updateFilter('search', $event.target.value)"
          placeholder="Поиск по заказу/клиенту..." 
          class="filter-input search"
        >
      </div>
      
      <div class="filter-actions">
        <button @click="applyFilters" class="btn-apply">Применить</button>
        <button @click="resetFilters" class="btn-reset">Сбросить</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue', 'apply', 'reset']);

const localFilters = reactive({
  date_from: '',
  date_to: '',
  status: '',
  search: ''
});

// Синхронизация с props
watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    Object.assign(localFilters, newVal);
  }
}, { immediate: true, deep: true });

const updateFilter = (key, value) => {
  localFilters[key] = value;
  emit('update:modelValue', { ...localFilters });
};

const applyFilters = () => {
  emit('apply');
};

const resetFilters = () => {
  localFilters.date_from = '';
  localFilters.date_to = '';
  localFilters.status = '';
  localFilters.search = '';
  emit('update:modelValue', { ...localFilters });
  emit('reset');
};
</script>

<style scoped>
.order-filters {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  border: 1px solid #e9ecef;
}

.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 500;
  color: #666;
}

.filter-input, .filter-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
  background: white;
}

.filter-input.search {
  min-width: 250px;
}

.filter-input:focus, .filter-select:focus {
  outline: none;
  border-color: #c17b4b;
}

.filter-actions {
  display: flex;
  gap: 10px;
}

.btn-apply, .btn-reset {
  padding: 8px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  border: none;
  transition: all 0.2s;
}

.btn-apply {
  background: #c17b4b;
  color: white;
}

.btn-apply:hover {
  background: #b06a3d;
  transform: translateY(-1px);
}

.btn-reset {
  background: #6c757d;
  color: white;
}

.btn-reset:hover {
  background: #5a6268;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-input, .filter-select {
    width: 100%;
  }
  
  .filter-actions {
    margin-top: 10px;
  }
}
</style>
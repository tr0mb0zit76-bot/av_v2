<!-- resources/js/components/common/DataTable.vue -->
<template>
  <div class="data-table">
    <!-- Панель инструментов -->
    <div class="table-toolbar">
      <div class="toolbar-left">
        <slot name="toolbar-left"></slot>
      </div>
      
      <div class="toolbar-right">
        <button @click="$emit('refresh')" class="btn-icon" title="Обновить">
          🔄
        </button>
        <button @click="$emit('export')" class="btn-icon" title="Экспорт">
          📥
        </button>
      </div>
    </div>
    
    <!-- Таблица -->
    <div class="table-wrapper" :class="{ loading }">
      <table class="table">
        <thead>
          <tr>
            <th v-for="col in columns" :key="col.field" :style="{ width: col.width + 'px' }">
              {{ col.header }}
            </th>
            <th v-if="hasActions" class="actions-col">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in data" :key="row.id" @dblclick="$emit('row-dblclick', row)">
            <td v-for="col in columns" :key="col.field">
              <template v-if="col.type === 'currency'">
                {{ formatCurrency(row[col.field]) }}
              </template>
              <template v-else-if="col.type === 'percent'">
                {{ row[col.field] || 0 }}%
              </template>
              <template v-else-if="col.type === 'status'">
                <span :class="['status-badge', `status-${row[col.field]}`]">
                  {{ getStatusText(row[col.field]) }}
                </span>
              </template>
              <template v-else>
                {{ row[col.field] || '—' }}
              </template>
            </td>
            <td v-if="hasActions" class="actions-col">
              <slot name="actions" :row="row"></slot>
            </td>
          </tr>
          <tr v-if="!data.length && !loading">
            <td :colspan="columns.length + (hasActions ? 1 : 0)" class="empty-state">
              Нет данных
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>
    </div>
    
    <!-- Пагинация -->
    <div v-if="total > 0" class="pagination">
      <button 
        @click="$emit('update:currentPage', currentPage - 1)" 
        :disabled="currentPage === 1" 
        class="page-btn"
      >
        ←
      </button>
      <span class="page-info">
        Страница {{ currentPage }} из {{ totalPages }}
      </span>
      <button 
        @click="$emit('update:currentPage', currentPage + 1)" 
        :disabled="currentPage === totalPages" 
        class="page-btn"
      >
        →
      </button>
      <select 
        :value="perPage" 
        @change="onPerPageChange" 
        class="per-page-select"
      >
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
        <option :value="100">100</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  columns: {
    type: Array,
    required: true
  },
  hasActions: {
    type: Boolean,
    default: false
  },
  sortField: {
    type: String,
    default: 'id'
  },
  sortOrder: {
    type: String,
    default: 'desc'
  },
  currentPage: {
    type: Number,
    default: 1
  },
  perPage: {
    type: Number,
    default: 50
  },
  total: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits([
  'update:sortField',
  'update:sortOrder', 
  'update:currentPage',
  'update:perPage',
  'cell-save',
  'refresh',
  'export',
  'row-dblclick'
]);

const totalPages = computed(() => Math.ceil(props.total / props.perPage));

const formatCurrency = (value) => {
  if (!value && value !== 0) return '0';
  return new Intl.NumberFormat('ru-RU').format(value);
};

const getStatusText = (status) => {
  const statuses = {
    new: 'Новый',
    in_transit: 'В пути',
    completed: 'Завершен',
    cancelled: 'Отменен',
    documents: 'Документы',
    payment: 'Оплата'
  };
  return statuses[status] || status;
};

const onPerPageChange = (event) => {
  emit('update:perPage', parseInt(event.target.value));
  emit('update:currentPage', 1);
};

const sort = (field) => {
  if (props.sortField === field) {
    emit('update:sortOrder', props.sortOrder === 'asc' ? 'desc' : 'asc');
  } else {
    emit('update:sortField', field);
    emit('update:sortOrder', 'asc');
  }
};
</script>

<style scoped>
.data-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e9ecef;
}

.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
}

.toolbar-left, .toolbar-right {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e9ecef;
}

.table-wrapper {
  position: relative;
  overflow-x: auto;
  min-height: 200px;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

th {
  background: #f8f9fa;
  font-weight: 600;
  color: #495057;
  position: sticky;
  top: 0;
  border-bottom: 2px solid #e9ecef;
}

.actions-col {
  width: 80px;
  text-align: center;
}

tr:hover {
  background: #f8f9fa;
  cursor: pointer;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-new { background: #e3f2fd; color: #1976d2; }
.status-in_transit { background: #fff3e0; color: #f57c00; }
.status-completed { background: #e8f5e9; color: #388e3c; }
.status-cancelled { background: #ffebee; color: #d32f2f; }
.status-documents { background: #fce4ec; color: #c2185b; }
.status-payment { background: #e0f2fe; color: #0284c7; }

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #c17b4b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 48px;
  color: #6c757d;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
}

.page-btn {
  background: white;
  border: 1px solid #dee2e6;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: #e9ecef;
  border-color: #c17b4b;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #6c757d;
}

.per-page-select {
  padding: 6px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  cursor: pointer;
  background: white;
}

.per-page-select:hover {
  border-color: #c17b4b;
}
</style>
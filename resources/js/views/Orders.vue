<template>
  <div class="orders-page">
    <!-- Верхняя фиксированная часть -->
    <div class="orders-header">
      <div class="page-header">
        <h1>Заказы</h1>
        <div class="header-actions">
          <button @click="openCreateModal" class="btn-primary">
            + Новый заказ
          </button>
          <button @click="debugEditing" class="btn-secondary" style="background: #17a2b8; color: white;">
            🔍 EDIT DEBUG
          </button>
          <button @click="debugGridState" class="btn-secondary" style="background: #28a745; color: white;">
            📊 GRID DEBUG
          </button>
          <button @click="showSettings = true" class="btn-secondary">
            ⚙️
          </button>
          <button @click="exportData" class="btn-secondary">
            📥
          </button>
        </div>
      </div>
      
      <div class="filters-bar">
        <input type="date" v-model="filters.date_from" placeholder="Дата с" class="filter-input" />
        <input type="date" v-model="filters.date_to" placeholder="Дата по" class="filter-input" />
        <select v-model="filters.status" class="filter-select">
          <option value="">Все статусы</option>
          <option value="new">🆕 Новый</option>
          <option value="in_transit">🚛 В пути</option>
          <option value="completed">✅ Завершен</option>
          <option value="cancelled">❌ Отменен</option>
        </select>
        <input type="text" v-model="filters.search" placeholder="Поиск..." class="filter-input search" />
        <button @click="applyFilters" class="btn-filter">Применить</button>
        <button @click="resetFilters" class="btn-clear">Сбросить</button>
      </div>
    </div>
    
    <!-- Таблица -->
    <div class="grid-container">
      <OrdersGrid
        ref="gridRef"
        :data="orders"
        :loading="loading"
        :editable="canEdit"
        @cell-save="onCellSave"
        @row-dblclick="openEditModal"
      />
    </div>
    
    <!-- Модалки -->
    <ColumnSettings 
      v-if="showSettings" 
      :columns="allColumns" 
      :visible-columns="visibleColumns"
      :column-order="currentColumnOrder"
      @save="saveColumnSettings" 
      @close="showSettings = false" 
    />
    <OrderModal :show="showModal" :order-id="selectedOrderId" @saved="onOrderSaved" @close="closeModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { ordersColumns } from '@/config/ordersColumns';
import OrdersGrid from '@/components/orders/OrdersGrid.vue';
import ColumnSettings from '@/components/common/ColumnSettings.vue';
import OrderModal from '@/components/orders/OrderModal.vue';

const authStore = useAuthStore();

const currentColumnOrder = ref([]);
const orders = ref([]);
const loading = ref(false);
const showSettings = ref(false);
const showModal = ref(false);
const selectedOrderId = ref(null);
const gridRef = ref(null);
const visibleColumns = ref([]);
const filters = ref({
  date_from: '',
  date_to: '',
  status: '',
  search: ''
});

const userRole = computed(() => authStore.user?.role?.name || 'viewer');

// Исправленный canEdit
const canEdit = computed(() => {
  const roleConfig = ordersColumns.roles[userRole.value] || ordersColumns.roles.viewer;
  // Разрешаем редактирование, если editable не false и не null
  return roleConfig.editable !== false && roleConfig.editable !== null;
});

const allColumns = computed(() => ordersColumns.all);

const loadColumnSettings = () => {
  const saved = localStorage.getItem(`orders_visible_columns_${authStore.user?.id}`);
  if (saved) {
    visibleColumns.value = JSON.parse(saved);
  } else {
    const roleConfig = ordersColumns.roles[userRole.value] || ordersColumns.roles.viewer;
    visibleColumns.value = roleConfig.visible || ordersColumns.defaultVisible;
  }
};

const loadColumnOrder = () => {
  const savedOrder = localStorage.getItem(`orders_column_order_${authStore.user?.id}`);
  if (savedOrder) {
    try {
      currentColumnOrder.value = JSON.parse(savedOrder);
    } catch (e) {
      console.error('Error loading column order:', e);
    }
  } else {
    currentColumnOrder.value = allColumns.value.map(col => col.field);
  }
};

const saveColumnSettings = async (settings) => {
  try {
    if (settings.order) {
      currentColumnOrder.value = settings.order;
      localStorage.setItem(`orders_column_order_${authStore.user?.id}`, JSON.stringify(settings.order));
    }
    
    localStorage.setItem(`orders_visible_columns_${authStore.user?.id}`, JSON.stringify(settings.visible));
    
    if (gridRef.value && gridRef.value.applyColumnSettings) {
      const columnSettings = allColumns.value.map(col => ({
        colId: col.field,
        visible: settings.visible.includes(col.field),
        width: col.width || 150
      }));
      
      if (settings.order) {
        const orderedSettings = settings.order
          .map(field => columnSettings.find(s => s.colId === field))
          .filter(s => s);
        gridRef.value.applyColumnSettings(orderedSettings);
      } else {
        gridRef.value.applyColumnSettings(columnSettings);
      }
    }
    
    showSettings.value = false;
    await loadOrders();
  } catch (error) {
    console.error('Ошибка сохранения настроек:', error);
  }
};

const loadOrders = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.search) params.search = filters.value.search;
    
    const response = await axios.get('/api/orders', { params });
    orders.value = response.data;
  } catch (error) {
    console.error('Ошибка загрузки заказов:', error);
    alert('Ошибка загрузки заказов');
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  loadOrders();
};

const resetFilters = () => {
  filters.value = {
    date_from: '',
    date_to: '',
    status: '',
    search: ''
  };
  loadOrders();
};

const onCellSave = async ({ row, field, value }) => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    await axios.patch(`/api/orders/${row.id}/cell`, { field, value }, {
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    
    if (['customer_rate', 'carrier_rate', 'additional_expenses'].includes(field)) {
      await recalculateKpi(row.id);
    }
  } catch (error) {
    console.error('Ошибка сохранения:', error);
    alert('Ошибка сохранения');
    loadOrders();
  }
};

const recalculateKpi = async (orderId) => {
  try {
    await axios.post(`/api/orders/${orderId}/recalculate-kpi`);
    loadOrders();
  } catch (error) {
    console.error('Ошибка пересчета KPI:', error);
  }
};

const exportData = () => {
  if (gridRef.value) {
    gridRef.value.exportData();
  }
};

const openCreateModal = () => {
  selectedOrderId.value = null;
  showModal.value = true;
};

const openEditModal = (order) => {
  console.log('Opening edit modal for order:', order.id);
  selectedOrderId.value = order.id;
  showModal.value = true;
};

const onOrderSaved = () => {
  closeModal();
  loadOrders();
};

const closeModal = () => {
  showModal.value = false;
  selectedOrderId.value = null;
};

// Функции отладки
const debugEditing = () => {
  console.log('=== DEBUG: Editing Configuration ===');
  console.log('User role:', userRole.value);
  console.log('canEdit computed value:', canEdit.value);
  console.log('Role config from ordersColumns:', ordersColumns.roles[userRole.value]);
  console.log('All columns count:', allColumns.value.length);
  
  if (gridRef.value && gridRef.value.debugEditableConfig) {
    gridRef.value.debugEditableConfig();
  } else {
    console.log('Grid ref not ready or debugEditableConfig not available');
  }
};

const debugGridState = () => {
  console.log('=== DEBUG: Grid State ===');
  console.log('Orders count:', orders.value.length);
  console.log('Loading state:', loading.value);
  console.log('Grid ref exists:', !!gridRef.value);
  
  if (gridRef.value && gridRef.value.debugGrid) {
    gridRef.value.debugGrid();
  } else {
    console.log('Grid ref not ready or debugGrid not available');
  }
};

onMounted(() => {
  console.log('Orders.vue mounted, user:', authStore.user?.name, 'role:', userRole.value);
  loadColumnSettings();
  loadColumnOrder();
  loadOrders();
  
  // Даем время на инициализацию
  setTimeout(() => {
    debugEditing();
  }, 1000);
});
</script>

<style scoped>
.orders-page {
  background: white;
  border-radius: 12px;
  padding: 16px;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

.orders-header {
  flex-shrink: 0;
  background: white;
  z-index: 10;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.btn-primary, .btn-secondary {
  padding: 6px 12px;
  font-size: 13px;
  border-radius: 6px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #c17b4b;
  color: white;
}

.btn-primary:hover {
  background: #b06a3d;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #e9ecef;
  color: #495057;
}

.btn-secondary:hover {
  background: #dee2e6;
  transform: translateY(-1px);
}

.filters-bar {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
  align-items: center;
  padding: 8px 10px;
  background: #f8f9fa;
  border-radius: 8px;
}

.filter-input, .filter-select {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  min-width: 110px;
}

.filter-input.search {
  min-width: 180px;
  flex: 1;
}

.btn-filter, .btn-clear {
  padding: 6px 14px;
  font-size: 13px;
  border-radius: 6px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-filter {
  background: #c17b4b;
  color: white;
}

.btn-filter:hover {
  background: #b06a3d;
}

.btn-clear {
  background: #6c757d;
  color: white;
}

.btn-clear:hover {
  background: #5a6268;
}

.grid-container {
  flex: 1;
  min-height: 0;
  width: 100%;
  overflow: hidden;
  position: relative;
  margin-bottom: 8px;
}

@media (max-width: 768px) {
  .orders-page {
    padding: 12px;
  }
  
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-input, .filter-select {
    width: 100%;
  }
  
  .filter-input.search {
    min-width: auto;
  }
}
</style>
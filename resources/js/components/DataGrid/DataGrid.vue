<!-- resources/js/components/DataGrid/DataGrid.vue -->
<template>
  <div class="data-grid-container">
    <!-- Панель управления -->
    <div class="grid-toolbar">
      <div class="toolbar-left">
        <button @click="exportData" class="btn-sm">
          📥 Экспорт
        </button>
        <button @click="refreshData" class="btn-sm">
          🔄 Обновить
        </button>
      </div>
      
      <div class="toolbar-right">
        <!-- Фильтр по роли (только для админов) -->
        <select v-if="isAdmin" v-model="selectedRoleView" @change="applyRoleView" class="role-select">
          <option value="">Вид: По умолчанию</option>
          <option value="admin">Администратор</option>
          <option value="manager">Менеджер</option>
          <option value="dispatcher">Диспетчер</option>
          <option value="accountant">Бухгалтер</option>
        </select>
        
        <!-- Настройка колонок -->
        <button @click="showColumnSelector = !showColumnSelector" class="btn-sm">
          ⚙️ Настройка колонок
        </button>
      </div>
    </div>
    
    <!-- Селектор колонок -->
    <div v-if="showColumnSelector" class="column-selector">
      <h4>Видимые колонки</h4>
      <div class="column-list">
        <label v-for="col in allColumns" :key="col.field" class="column-item">
          <input 
            type="checkbox" 
            :value="col.field"
            v-model="visibleColumns"
            @change="saveColumnSettings"
          >
          <span>{{ col.headerName }}</span>
          <span class="column-group">{{ col.group }}</span>
        </label>
      </div>
    </div>
    
    <!-- Ag-Grid таблица -->
    <ag-grid-vue
      ref="agGrid"
      class="ag-theme-alpine"
      :style="{ height: 'calc(100vh - 200px)', width: '100%' }"
      :columnDefs="displayedColumns"
      :rowData="rowData"
      :defaultColDef="defaultColDef"
      :rowSelection="'multiple'"
      :pagination="true"
      :paginationPageSize="50"
      :sideBar="sideBar"
      :statusBar="statusBar"
      :components="components"
      @cell-double-clicked="onCellDoubleClick"
      @cell-value-changed="onCellValueChanged"
      @selection-changed="onSelectionChanged"
    />
    
    <!-- Модальное окно редактирования -->
    <Modal
      v-if="showModal"
      :title="modalTitle"
      :size="'large'"
      @close="closeModal"
    >
      <component 
        :is="currentModalComponent"
        :data="selectedRow"
        :type="modalType"
        @save="onModalSave"
        @close="closeModal"
      />
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { AgGridVue } from 'ag-grid-vue3';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';

// Регистрация всех модулей AG Grid (обязательно!)
ModuleRegistry.registerModules(AllModules);

// Стили AG Grid
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-alpine.css';

// Компоненты модальных окон
import OrderModal from './modals/OrderModal.vue';
import ContractorModal from './modals/ContractorModal.vue';
import CargoModal from './modals/CargoModal.vue';

const props = defineProps({
  endpoint: {
    type: String,
    required: true
  },
  entityType: {
    type: String,
    required: true // 'orders', 'contractors', 'cargos'
  },
  userRole: {
    type: String,
    required: true
  },
  userId: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(['data-changed', 'row-selected']);

// Состояние
const rowData = ref([]);
const agGrid = ref(null);
const showColumnSelector = ref(false);
const showModal = ref(false);
const modalType = ref(null);
const selectedRow = ref(null);
const selectedRoleView = ref('');
const visibleColumns = ref([]);

// Вычисляемое свойство для проверки прав администратора
const isAdmin = computed(() => props.userRole === 'admin');

// Конфигурация колонок для разных ролей
const roleColumnConfig = {
  admin: {
    visible: ['id', 'order_number', 'company_code', 'manager_name', 'order_date', 
              'loading_point', 'unloading_point', 'cargo_description', 'customer_rate', 
              'carrier_rate', 'kpi_percent', 'delta', 'customer_name', 'carrier_name', 
              'salary_accrued', 'salary_paid', 'track_status_customer', 'track_status_carrier'],
    editable: true
  },
  manager: {
    visible: ['order_number', 'company_code', 'order_date', 'loading_point', 
              'unloading_point', 'cargo_description', 'customer_rate', 'carrier_rate', 
              'kpi_percent', 'delta', 'customer_name', 'carrier_name', 'track_status_customer', 
              'track_status_carrier', 'invoice_number', 'upd_number'],
    editable: true
  },
  dispatcher: {
    visible: ['order_number', 'company_code', 'order_date', 'loading_point', 
              'unloading_point', 'cargo_description', 'driver_name', 'driver_phone',
              'track_status_customer', 'track_status_carrier'],
    editable: ['track_status_customer', 'track_status_carrier', 'driver_phone']
  },
  accountant: {
    visible: ['order_number', 'order_date', 'customer_rate', 'carrier_rate', 
              'kpi_percent', 'delta', 'salary_accrued', 'salary_paid',
              'prepayment_customer', 'prepayment_status', 'final_customer', 'final_customer_status',
              'prepayment_carrier', 'prepayment_carrier_status', 'final_carrier', 'final_carrier_status'],
    editable: ['salary_paid', 'prepayment_status', 'final_customer_status', 'final_carrier_status']
  }
};

// Все доступные колонки
const allColumns = ref([
  { field: 'id', headerName: 'ID', group: 'Основные', width: 80, type: 'number', pinned: 'left' },
  { field: 'order_number', headerName: '№ заказа', group: 'Основные', width: 120, pinned: 'left' },
  { field: 'company_code', headerName: 'Наша компания', group: 'Основные', width: 100 },
  { field: 'manager_name', headerName: 'Менеджер', group: 'Основные', width: 120 },
  { field: 'order_date', headerName: 'Дата заявки', group: 'Основные', width: 110, type: 'date' },
  
  { field: 'loading_point', headerName: 'Загрузка', group: 'Маршрут', width: 150 },
  { field: 'unloading_point', headerName: 'Выгрузка', group: 'Маршрут', width: 150 },
  { field: 'cargo_description', headerName: 'Груз', group: 'Маршрут', width: 200 },
  { field: 'loading_date', headerName: 'Дата погрузки', group: 'Маршрут', width: 110, type: 'date' },
  { field: 'unloading_date', headerName: 'Дата выгрузки', group: 'Маршрут', width: 110, type: 'date' },
  
  { field: 'customer_rate', headerName: 'Ставка заказчика', group: 'Финансы', width: 120, type: 'number' },
  { field: 'carrier_rate', headerName: 'Ставка перевозчика', group: 'Финансы', width: 120, type: 'number' },
  { field: 'additional_expenses', headerName: 'Доп расходы', group: 'Финансы', width: 100, type: 'number' },
  { field: 'kpi_percent', headerName: 'KPI %', group: 'KPI', width: 80, type: 'number' },
  { field: 'delta', headerName: 'Дельта', group: 'KPI', width: 100, type: 'number' },
  
  { field: 'customer_name', headerName: 'Заказчик', group: 'Контрагенты', width: 180 },
  { field: 'customer_contact', headerName: 'Контакт заказчика', group: 'Контрагенты', width: 150 },
  { field: 'carrier_name', headerName: 'Перевозчик', group: 'Контрагенты', width: 180 },
  { field: 'carrier_contact', headerName: 'Контакт перевозчика', group: 'Контрагенты', width: 150 },
  { field: 'driver_name', headerName: 'Водитель', group: 'Контрагенты', width: 150 },
  { field: 'driver_phone', headerName: 'Телефон водителя', group: 'Контрагенты', width: 120 },
  
  { field: 'prepayment_customer', headerName: 'Предоплата заказчик', group: 'Оплата', width: 130, type: 'number' },
  { field: 'prepayment_status', headerName: 'Статус предоплаты', group: 'Оплата', width: 120 },
  { field: 'final_customer', headerName: 'Постоплата заказчик', group: 'Оплата', width: 130, type: 'number' },
  { field: 'final_customer_status', headerName: 'Статус постоплаты', group: 'Оплата', width: 120 },
  { field: 'prepayment_carrier', headerName: 'Предоплата перевозчику', group: 'Оплата', width: 140, type: 'number' },
  { field: 'prepayment_carrier_status', headerName: 'Статус предоплаты', group: 'Оплата', width: 120 },
  { field: 'final_carrier', headerName: 'Постоплата перевозчику', group: 'Оплата', width: 140, type: 'number' },
  { field: 'final_carrier_status', headerName: 'Статус постоплаты', group: 'Оплата', width: 120 },
  
  { field: 'salary_accrued', headerName: 'Начислено', group: 'Зарплата', width: 100, type: 'number' },
  { field: 'salary_paid', headerName: 'Выплачено', group: 'Зарплата', width: 100, type: 'number' },
  
  { field: 'track_number_customer', headerName: 'Трэк-номер заказчика', group: 'Документы', width: 150 },
  { field: 'track_status_customer', headerName: 'Статус трэка', group: 'Документы', width: 100 },
  { field: 'track_number_carrier', headerName: 'Трэк-номер перевозчика', group: 'Документы', width: 150 },
  { field: 'track_status_carrier', headerName: 'Статус трэка', group: 'Документы', width: 100 },
  { field: 'invoice_number', headerName: '№ счёта', group: 'Документы', width: 120 },
  { field: 'upd_number', headerName: '№ УПД', group: 'Документы', width: 120 },
  { field: 'upd_customer_status', headerName: 'УПД заказчик', group: 'Документы', width: 100 },
  { field: 'order_customer_status', headerName: 'Заявка заказчик', group: 'Документы', width: 100 },
  { field: 'waybill_number', headerName: 'ТН', group: 'Документы', width: 100 },
  { field: 'upd_carrier_status', headerName: 'УПД перевозчик', group: 'Документы', width: 100 },
  { field: 'order_carrier_status', headerName: 'Заявка перевозчик', group: 'Документы', width: 100 }
]);

// Кастомные рендереры
const components = {
  currencyRenderer: {
    template: `<span>{{ params.value ? formatNumber(params.value) : '0' }} ₽</span>`,
    methods: {
      formatNumber(num) {
        return new Intl.NumberFormat('ru-RU').format(num);
      }
    }
  },
  percentRenderer: {
    template: `<span :class="percentClass">{{ params.value || 0 }}%</span>`,
    computed: {
      percentClass() {
        const value = this.params.value || 0;
        if (value >= 30) return 'text-success';
        if (value >= 15) return 'text-warning';
        return 'text-danger';
      }
    }
  },
  statusRenderer: {
    template: `<span :class="statusClass">{{ statusText }}</span>`,
    computed: {
      statusText() {
        const value = this.params.value;
        const statuses = {
          'оплачено': 'Оплачено',
          'ожидание': 'Ожидание',
          'просрочено': 'Просрочено'
        };
        return statuses[value] || value || '—';
      },
      statusClass() {
        const value = this.params.value;
        if (value === 'оплачено') return 'badge-success';
        if (value === 'просрочено') return 'badge-danger';
        return 'badge-warning';
      }
    }
  },
  trackStatusRenderer: {
    template: `<span :class="statusClass">{{ statusText }}</span>`,
    computed: {
      statusText() {
        return this.params.value ? '✅' : '⏳';
      },
      statusClass() {
        return this.params.value ? 'badge-success' : 'badge-warning';
      }
    }
  },
  documentStatusRenderer: {
    template: `<span :class="statusClass">{{ statusText }}</span>`,
    computed: {
      statusText() {
        const value = this.params.value;
        const statuses = {
          'получено': '✅',
          'отправлено': '📤',
          'ожидание': '⏳'
        };
        return statuses[value] || value || '—';
      },
      statusClass() {
        const value = this.params.value;
        if (value === 'получено') return 'badge-success';
        if (value === 'отправлено') return 'badge-info';
        return 'badge-warning';
      }
    }
  }
};

// Дефолтные настройки колонок
const defaultColDef = {
  sortable: true,
  filter: true,
  resizable: true,
  editable: false,
  floatingFilter: true
};

// Боковая панель для фильтров
const sideBar = {
  toolPanels: [
    {
      id: 'filters',
      labelDefault: 'Фильтры',
      labelKey: 'filters',
      iconKey: 'filter',
      toolPanel: 'agFiltersToolPanel',
    },
    {
      id: 'columns',
      labelDefault: 'Колонки',
      labelKey: 'columns',
      iconKey: 'columns',
      toolPanel: 'agColumnsToolPanel',
    }
  ],
  defaultToolPanel: 'filters'
};

// Статус-бар
const statusBar = {
  statusPanels: [
    { statusPanel: 'agTotalAndFilteredRowCountComponent', align: 'left' },
    { statusPanel: 'agSelectedRowCountComponent', align: 'left' },
    { statusPanel: 'agAggregationComponent', align: 'right' }
  ]
};

// Вычисляемые колонки с учетом прав
const displayedColumns = computed(() => {
  let columnsToShow = [];
  
  // Определяем, какие колонки показывать
  let visibleFields = [];
  if (selectedRoleView.value && roleColumnConfig[selectedRoleView.value]) {
    visibleFields = roleColumnConfig[selectedRoleView.value].visible;
  } else if (roleColumnConfig[props.userRole]) {
    visibleFields = roleColumnConfig[props.userRole].visible;
  }
  
  // Если пользователь настроил свои колонки, используем их
  if (visibleColumns.value.length > 0) {
    visibleFields = visibleColumns.value;
  }
  
  // Строим колонки
  allColumns.value.forEach(col => {
    if (visibleFields.includes(col.field)) {
      const colDef = { ...col };
      
      // Добавляем рендереры для определенных типов полей
      if (col.field.includes('rate') || col.field.includes('salary') || 
          col.field.includes('prepayment') || col.field.includes('final') ||
          col.field === 'delta') {
        colDef.cellRenderer = 'currencyRenderer';
      }
      
      if (col.field === 'kpi_percent') {
        colDef.cellRenderer = 'percentRenderer';
      }
      
      if (col.field.includes('status') && !col.field.includes('track')) {
        colDef.cellRenderer = 'statusRenderer';
      }
      
      if (col.field.includes('track_status')) {
        colDef.cellRenderer = 'trackStatusRenderer';
      }
      
      if (col.field.includes('upd_status') || col.field.includes('order_status')) {
        colDef.cellRenderer = 'documentStatusRenderer';
      }
      
      // Проверяем права на редактирование
      const editableFields = roleColumnConfig[props.userRole]?.editable;
      if (editableFields && (editableFields === true || editableFields.includes(col.field))) {
        colDef.editable = true;
        
        // Добавляем валидацию для числовых полей
        if (col.type === 'number') {
          colDef.valueParser = (params) => {
            const val = parseFloat(params.newValue);
            return isNaN(val) ? 0 : val;
          };
        }
      }
      
      columnsToShow.push(colDef);
    }
  });
  
  return columnsToShow;
});

// Загрузка данных
const loadData = async () => {
  try {
    const response = await fetch(`${props.endpoint}?role=${props.userRole}&user_id=${props.userId}`);
    const data = await response.json();
    rowData.value = data;
  } catch (error) {
    console.error('Error loading data:', error);
  }
};

// Сохранение изменений
const saveCellChange = async (rowIndex, field, value) => {
  const row = rowData.value[rowIndex];
  const saveData = {
    id: row.id,
    field: field,
    value: value,
    entity_type: props.entityType
  };
  
  try {
    const response = await fetch(`${props.endpoint}/save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(saveData)
    });
    
    const result = await response.json();
    if (result.success) {
      // Обновляем локальные данные
      row[field] = value;
      emit('data-changed', { row, field, value });
    } else {
      // Откатываем изменения
      if (agGrid.value && agGrid.value.api) {
        agGrid.value.api.refreshCells({ force: true });
      }
      alert(result.error || 'Ошибка сохранения');
    }
  } catch (error) {
    console.error('Save error:', error);
    if (agGrid.value && agGrid.value.api) {
      agGrid.value.api.refreshCells({ force: true });
    }
    alert('Ошибка соединения');
  }
};

// Обработчики событий
const onCellValueChanged = (params) => {
  const { data, colDef, newValue, oldValue, rowIndex } = params;
  if (newValue !== oldValue) {
    saveCellChange(rowIndex, colDef.field, newValue);
  }
};

const onCellDoubleClick = (params) => {
  if (params.data) {
    selectedRow.value = params.data;
    modalType.value = props.entityType;
    showModal.value = true;
  }
};

const onSelectionChanged = () => {
  if (agGrid.value && agGrid.value.api) {
    const selectedNodes = agGrid.value.api.getSelectedNodes();
    const selectedData = selectedNodes.map(node => node.data);
    emit('row-selected', selectedData);
  }
};

// Сохранение настроек колонок
const saveColumnSettings = () => {
  localStorage.setItem(`${props.entityType}_visible_columns_${props.userRole}`, JSON.stringify(visibleColumns.value));
};

// Загрузка настроек колонок
const loadColumnSettings = () => {
  const saved = localStorage.getItem(`${props.entityType}_visible_columns_${props.userRole}`);
  if (saved) {
    visibleColumns.value = JSON.parse(saved);
  }
};

// Применение вида по роли
const applyRoleView = () => {
  if (selectedRoleView.value) {
    const config = roleColumnConfig[selectedRoleView.value];
    if (config) {
      visibleColumns.value = [...config.visible];
      saveColumnSettings();
    }
  }
};

// Экспорт данных
const exportData = () => {
  if (agGrid.value && agGrid.value.api) {
    agGrid.value.api.exportDataAsCsv({
      fileName: `${props.entityType}_export_${new Date().toISOString().slice(0,10)}.csv`
    });
  }
};

// Обновление данных
const refreshData = () => {
  loadData();
};

// Модальное окно
const modalTitle = computed(() => {
  const titles = {
    orders: selectedRow.value ? `Редактирование заявки #${selectedRow.value.order_number}` : 'Новая заявка',
    contractors: selectedRow.value ? `Редактирование контрагента: ${selectedRow.value.name}` : 'Новый контрагент',
    cargos: selectedRow.value ? `Редактирование груза: ${selectedRow.value.title}` : 'Новый груз'
  };
  return titles[props.entityType] || 'Редактирование';
});

const currentModalComponent = computed(() => {
  const componentsMap = {
    orders: OrderModal,
    contractors: ContractorModal,
    cargos: CargoModal
  };
  return componentsMap[props.entityType] || OrderModal;
});

const onModalSave = async (data) => {
  try {
    const response = await fetch(`${props.endpoint}/${data.id || ''}`, {
      method: data.id ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if (result.success) {
      await loadData();
      closeModal();
      alert('Сохранено успешно');
    } else {
      alert(result.error || 'Ошибка сохранения');
    }
  } catch (error) {
    console.error('Save error:', error);
    alert('Ошибка соединения');
  }
};

const closeModal = () => {
  showModal.value = false;
  selectedRow.value = null;
  modalType.value = null;
};

// Инициализация
onMounted(() => {
  loadData();
  loadColumnSettings();
  
  // Настройка WebSocket для реального времени (опционально)
  if (window.Echo) {
    window.Echo.channel(`orders`)
      .listen('OrderUpdated', (e) => {
        const index = rowData.value.findIndex(row => row.id === e.order.id);
        if (index !== -1) {
          rowData.value[index] = { ...rowData.value[index], ...e.order };
          if (agGrid.value && agGrid.value.api) {
            agGrid.value.api.refreshCells({ force: true });
          }
        }
      });
  }
});

// Экспорт методов для родительского компонента
defineExpose({
  refreshData,
  exportData
});
</script>

<style scoped>
.data-grid-container {
  background: white;
  border-radius: 12px;
  padding: 20px;
  height: 100%;
}

.grid-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 15px;
  flex-wrap: wrap;
}

.toolbar-left, .toolbar-right {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-sm {
  padding: 6px 12px;
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s;
}

.btn-sm:hover {
  background: #e0e0e0;
}

.role-select {
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  font-size: 13px;
  cursor: pointer;
}

.column-selector {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
}

.column-selector h4 {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #333;
}

.column-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.column-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
}

.column-item:hover {
  background: #e9ecef;
}

.column-group {
  margin-left: auto;
  font-size: 11px;
  color: #6c757d;
}

/* Стили для статусов */
.badge-success {
  background: #d4edda;
  color: #155724;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  display: inline-block;
}

.badge-danger {
  background: #f8d7da;
  color: #721c24;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  display: inline-block;
}

.badge-warning {
  background: #fff3cd;
  color: #856404;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  display: inline-block;
}

.badge-info {
  background: #d1ecf1;
  color: #0c5460;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  display: inline-block;
}

.text-success {
  color: #28a745;
  font-weight: 600;
}

.text-warning {
  color: #ffc107;
  font-weight: 600;
}

.text-danger {
  color: #dc3545;
  font-weight: 600;
}
</style>
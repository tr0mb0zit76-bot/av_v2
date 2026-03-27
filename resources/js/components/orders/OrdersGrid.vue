<template>
  <div class="orders-grid">
    <ag-grid-vue
      ref="agGrid"
      class="ag-theme-alpine"
      :style="{ height: '100%', width: '100%' }"
      :columnDefs="dynamicColumnDefs"
      :rowData="rowData"
      :defaultColDef="defaultColDef"
      :rowSelection="rowSelectionConfig"
      :loading="loading"
      :stopEditingWhenCellsLoseFocus="true"
      :enableCellTextSelection="true"
      :ensureDomOrder="true"
      :suppressScrollOnNewData="false"
      :suppressHorizontalScroll="false"
      :domLayout="'normal'"
      :pagination="false"
      :alwaysShowVerticalScroll="true"
      @cell-double-clicked="onCellDoubleClicked"
      @cell-value-changed="onCellValueChanged"
      @grid-ready="onGridReady"
      @column-visible="onColumnVisible"
      @column-resized="onColumnResized"
      @column-moved="onColumnMoved"
      @column-pinned="onColumnPinned"
      @sort-changed="onSortChanged"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { AgGridVue } from 'ag-grid-vue3';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import { ordersColumns } from '@/config/ordersColumns';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

ModuleRegistry.registerModules([AllCommunityModule]);

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  editable: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['cell-save', 'row-dblclick', 'columns-changed']);

const authStore = useAuthStore();
const agGrid = ref(null);
const rowData = ref([]);
const isGridReady = ref(false);
const gridApi = ref(null);
let saveTimeout = null;
const dbConfig = ref(null);
const configLoaded = ref(false);
const currentEditableFields = ref([]);
const configInitialized = ref(false);

const rowSelectionConfig = {
  mode: 'multiRow',
  enableClickSelection: true,
  checkboxes: false,
  enableSelectionWithoutKeys: true
};

const defaultColDef = {
  sortable: true,
  filter: true,
  resizable: true,
  editable: false,
  floatingFilter: true,
  flex: 0,
  minWidth: 50,
  width: 150,
  suppressSizeToFit: true,
  autoHeight: false,
  wrapText: false
};

// Все доступные колонки
const getAllColumns = () => {
  const baseColumns = [...ordersColumns.all];
  const existingFields = baseColumns.map(col => col.field);
  const additionalColumns = [];
  
  if (!existingFields.includes('invoice_number')) {
    additionalColumns.push({ field: 'invoice_number', headerName: '№ счёта', width: 120, sortable: true, filter: true });
  }
  if (!existingFields.includes('upd_number')) {
    additionalColumns.push({ field: 'upd_number', headerName: '№ УПД', width: 120, sortable: true, filter: true });
  }
  if (!existingFields.includes('waybill_number')) {
    additionalColumns.push({ field: 'waybill_number', headerName: 'ТН', width: 100, sortable: true, filter: true });
  }
  if (!existingFields.includes('payment_statuses')) {
    additionalColumns.push({ field: 'payment_statuses', headerName: 'Статус оплаты', width: 130, sortable: true, filter: true });
  }
  
  return [...baseColumns, ...additionalColumns];
};

// Прямая функция получения редактируемых полей (без зависимостей от computed)
const getEditableFieldsDirect = () => {
  const userRole = authStore.user?.role?.name || 'viewer';
  
  // Если конфигурация из БД загружена и имеет данные - используем её
  if (dbConfig.value && configLoaded.value && dbConfig.value.editable) {
    return dbConfig.value.editable;
  }
  
  // Иначе используем конфигурацию из ordersColumns.js
  const roleConfig = ordersColumns.roles[userRole] || ordersColumns.roles.viewer;
  
  if (roleConfig.editable === true) {
    return getAllColumns().map(c => c.field);
  }
  
  if (Array.isArray(roleConfig.editable)) {
    return roleConfig.editable;
  }
  
  return [];
};

// Прямая функция получения видимых полей
const getVisibleFieldsDirect = () => {
  const userRole = authStore.user?.role?.name || 'viewer';
  
  if (dbConfig.value && configLoaded.value && dbConfig.value.visible) {
    return dbConfig.value.visible;
  }
  
  const roleConfig = ordersColumns.roles[userRole] || ordersColumns.roles.viewer;
  return roleConfig.visible || ordersColumns.defaultVisible;
};

// Загрузка конфигурации из БД
const loadDbConfig = async () => {
  try {
    const response = await axios.get('/api/user/columns-config/orders');
    dbConfig.value = response.data;
    console.log('DB Config loaded:', dbConfig.value);
    
    // Если конфигурация из БД пустая, игнорируем её
    if (!dbConfig.value || Object.keys(dbConfig.value).length === 0) {
      console.log('DB config is empty, will use role config');
      dbConfig.value = null;
    }
    
    configLoaded.value = true;
    configInitialized.value = true;
    
    // Принудительно обновляем колонки в гриде
    if (gridApi.value) {
      await nextTick();
      updateGridColumns();
    }
    
    return dbConfig.value;
  } catch (error) {
    if (error.response?.status === 401 || error.response?.status === 404) {
      console.log('No DB config found, using role config');
    } else {
      console.error('Error loading column config from DB:', error);
    }
    configLoaded.value = true;
    configInitialized.value = true;
    dbConfig.value = null;
    
    if (gridApi.value) {
      await nextTick();
      updateGridColumns();
    }
    
    return null;
  }
};

// Обновление колонок в гриде
const updateGridColumns = () => {
  if (!gridApi.value) return;
  
  const editableFields = getEditableFieldsDirect();
  currentEditableFields.value = editableFields;
  
  console.log('updateGridColumns - editableFields count:', editableFields.length);
  
  const columns = gridApi.value.getColumns();
  if (columns && columns.length > 0) {
    // Обновляем columnDefs для каждой колонки
    const newColumnState = columns.map(col => {
      const field = col.getColDef().field;
      const isEditable = editableFields.includes(field) && props.editable;
      return {
        colId: col.getColId(),
        editable: isEditable
      };
    });
    
    gridApi.value.applyColumnState({
      state: newColumnState,
      applyOrder: false
    });
    
    // Принудительно обновляем заголовки
    gridApi.value.refreshHeader();
    
    // Логируем результат
    setTimeout(() => {
      const updatedColumns = gridApi.value.getColumns();
      let editableCount = 0;
      updatedColumns.forEach(col => {
        if (col.getColDef().editable) editableCount++;
      });
      console.log(`Updated grid - Editable columns: ${editableCount} / ${updatedColumns.length}`);
    }, 100);
  }
};

// Динамические columnDefs
const dynamicColumnDefs = computed(() => {
  const visibleFields = getVisibleFieldsDirect();
  const editableFields = getEditableFieldsDirect();
  
  currentEditableFields.value = editableFields;
  
  console.log('dynamicColumnDefs - visible count:', visibleFields?.length);
  console.log('dynamicColumnDefs - editable count:', editableFields.length);
  
  let columns = getAllColumns();
  
  if (visibleFields && visibleFields !== null && Array.isArray(visibleFields)) {
    columns = columns.filter(col => visibleFields.includes(col.field));
  }
  
  return columns.map(col => {
    const isEditable = editableFields.includes(col.field) && props.editable;
    
    const colDef = {
      field: col.field,
      headerName: col.headerName,
      width: col.width,
      minWidth: col.minWidth || 80,
      sortable: col.sortable !== false,
      filter: col.filter !== false,
      resizable: true,
      suppressSizeToFit: true,
      flex: 0,
      pinned: col.pinned === 'left' ? 'left' : null,
      lockPinned: true,
      editable: isEditable
    };
    
    // Статус заказа
    if (col.field === 'status') {
      colDef.width = 60;
      colDef.minWidth = 60;
      colDef.cellRenderer = (params) => {
        if (!params.value) return '';
        const statuses = {
          new: '🆕',
          in_transit: '🚛',
          completed: '✅',
          cancelled: '❌'
        };
        const titles = {
          new: 'Новый',
          in_transit: 'В пути',
          completed: 'Завершен',
          cancelled: 'Отменен'
        };
        const icon = statuses[params.value] || '❓';
        const title = titles[params.value] || params.value;
        return `<span class="status-badge status-${params.value}" title="${title}">${icon}</span>`;
      };
    }
    // Числовые колонки
    else if (col.type === 'numericColumn' || 
             ['delta', 'salary_accrued', 'salary_paid', 'customer_rate', 'carrier_rate', 'kpi_percent', 
              'additional_expenses', 'insurance', 'bonus',
              'prepayment_customer_amount', 'final_customer_amount',
              'prepayment_carrier_amount', 'final_carrier_amount'].includes(col.field)) {
      colDef.valueFormatter = (params) => {
        if (params.value === null || params.value === undefined) return '0';
        return new Intl.NumberFormat('ru-RU').format(params.value);
      };
      colDef.valueParser = (params) => {
        const val = parseFloat(params.newValue);
        return isNaN(val) ? 0 : val;
      };
      colDef.filter = 'agNumberColumnFilter';
    }
    // Статусы оплаты
    else if (col.field === 'payment_statuses' || 
             col.field === 'prepayment_customer_status' || 
             col.field === 'final_customer_status' ||
             col.field === 'prepayment_carrier_status' || 
             col.field === 'final_carrier_status') {
      colDef.cellRenderer = (params) => {
        if (!params.value) return '—';
        const statuses = {
          paid: '✅ Оплачено',
          pending: '⏳ Ожидание',
          overdue: '⚠️ Просрочено',
          cancelled: '❌ Отменено'
        };
        const statusText = statuses[params.value] || params.value;
        const statusClass = {
          paid: 'status-paid',
          pending: 'status-pending',
          overdue: 'status-overdue',
          cancelled: 'status-cancelled'
        }[params.value] || '';
        return `<span class="payment-status ${statusClass}">${statusText}</span>`;
      };
    }
    // Поля с датами
    else if (col.type === 'date' || 
             col.field.includes('_date') || 
             col.field === 'order_date' || 
             col.field === 'loading_date' || 
             col.field === 'unloading_date') {
      colDef.valueFormatter = (params) => {
        if (!params.value) return '—';
        return params.value;
      };
    }
    // Поля с контактными данными и водителем
    else if (col.field === 'driver_name' || col.field === 'driver_phone' ||
             col.field === 'customer_contact_name' || col.field === 'customer_contact_phone' ||
             col.field === 'customer_contact_email' ||
             col.field === 'carrier_contact_name' || col.field === 'carrier_contact_phone' ||
             col.field === 'carrier_contact_email') {
      colDef.valueFormatter = (params) => {
        if (!params.value || params.value === 'null' || params.value === '—') return '—';
        return params.value;
      };
    }
    // Обычные текстовые колонки
    else {
      colDef.valueFormatter = (params) => {
        if (!params.value) return '—';
        return params.value;
      };
    }
    
    return colDef;
  });
});

watch(() => props.data, async (newData) => {
  if (newData && Array.isArray(newData)) {
    rowData.value = newData;
    
    if (isGridReady.value && gridApi.value) {
      await nextTick();
      setTimeout(() => {
        gridApi.value.resetRowHeights();
        loadColumnSettings();
      }, 100);
    }
  } else {
    rowData.value = [];
  }
}, { immediate: true, deep: true });

// Загрузка сохраненных настроек из localStorage
const loadColumnSettings = () => {
  const saved = localStorage.getItem(`orders_columns_${authStore.user?.id}`);
  if (saved && gridApi.value) {
    try {
      const columnState = JSON.parse(saved);
      gridApi.value.applyColumnState({
        state: columnState,
        applyOrder: true
      });
      return true;
    } catch (e) {
      console.error('Error loading column settings:', e);
    }
  }
  return false;
};

// Сохранение настроек колонок
const saveCurrentColumnSettings = () => {
  if (!gridApi.value) return;
  
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
  
  saveTimeout = setTimeout(() => {
    const columnState = gridApi.value.getColumnState();
    const currentState = columnState.map(col => ({
      colId: col.colId,
      hide: col.hide,
      width: col.width,
      pinned: col.pinned,
      sort: col.sort,
      sortIndex: col.sortIndex,
      order: col.order
    }));
    
    localStorage.setItem(`orders_columns_${authStore.user?.id}`, JSON.stringify(currentState));
    
    const visibleColumns = columnState
      .filter(col => !col.hide)
      .map(col => col.colId);
    localStorage.setItem(`orders_visible_columns_${authStore.user?.id}`, JSON.stringify(visibleColumns));
    
    const columnOrder = columnState.map(col => col.colId);
    localStorage.setItem(`orders_column_order_${authStore.user?.id}`, JSON.stringify(columnOrder));
    
    emit('columns-changed', columnState);
  }, 500);
};

const saveColumnSettingsImmediate = () => {
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
  
  if (gridApi.value) {
    const columnState = gridApi.value.getColumnState();
    localStorage.setItem(`orders_columns_${authStore.user?.id}`, JSON.stringify(columnState));
    
    const visibleColumns = columnState
      .filter(col => !col.hide)
      .map(col => col.colId);
    localStorage.setItem(`orders_visible_columns_${authStore.user?.id}`, JSON.stringify(visibleColumns));
    
    const columnOrder = columnState.map(col => col.colId);
    localStorage.setItem(`orders_column_order_${authStore.user?.id}`, JSON.stringify(columnOrder));
    
    emit('columns-changed', columnState);
  }
};

const onCellDoubleClicked = (params) => {
  if (params.data) {
    emit('row-dblclick', params.data);
  }
};

const onCellValueChanged = (params) => {
  if (params.newValue !== params.oldValue && props.editable) {
    emit('cell-save', {
      row: params.data,
      field: params.colDef.field,
      value: params.newValue
    });
  }
};

const onGridReady = async (params) => {
  console.log('Grid ready, setting up...');
  gridApi.value = params.api;
  isGridReady.value = true;
  
  await loadDbConfig();
  
  setTimeout(() => {
    const loaded = loadColumnSettings();
    
    if (!loaded) {
      const columnState = gridApi.value.getColumnState();
      const defaultVisible = getVisibleFieldsDirect();
      
      const newColumnState = columnState.map(col => ({
        ...col,
        hide: !defaultVisible.includes(col.colId),
        pinned: null,
        lockPinned: true
      }));
      gridApi.value.applyColumnState({ state: newColumnState });
    }
    
    gridApi.value.setGridOption('suppressHorizontalScroll', false);
    gridApi.value.setGridOption('alwaysShowVerticalScroll', true);
    
    // Обновляем редактируемость колонок
    updateGridColumns();
    
    setTimeout(() => {
      gridApi.value.resetRowHeights();
      gridApi.value.refreshHeader();
    }, 50);
    
  }, 100);
};

const onColumnVisible = () => {
  saveCurrentColumnSettings();
};

const onColumnResized = () => {
  saveCurrentColumnSettings();
};

const onColumnMoved = () => {
  saveCurrentColumnSettings();
};

const onColumnPinned = () => {
  saveCurrentColumnSettings();
};

const onSortChanged = () => {
  saveCurrentColumnSettings();
};

const exportData = () => {
  if (gridApi.value) {
    gridApi.value.exportDataAsCsv({
      fileName: `orders_export_${new Date().toISOString().slice(0, 10)}.csv`,
      allColumns: true
    });
  }
};

const saveColumnSettingsMethod = () => {
  saveColumnSettingsImmediate();
};

const applyColumnSettings = (settings) => {
  if (gridApi.value && settings) {
    const columnState = settings.map((setting, index) => ({
      colId: setting.colId,
      hide: !setting.visible,
      width: setting.width,
      pinned: null,
      lockPinned: true
    }));
    
    gridApi.value.applyColumnState({
      state: columnState,
      applyOrder: true
    });
    
    saveColumnSettingsImmediate();
    updateGridColumns();
  }
};

const getCurrentColumnState = () => {
  if (gridApi.value) {
    return gridApi.value.getColumnState();
  }
  return [];
};

const refreshGrid = () => {
  if (gridApi.value) {
    nextTick(() => {
      gridApi.value.resetRowHeights();
      gridApi.value.refreshCells({ force: true });
    });
  }
};

// Функции отладки
const debugGrid = () => {
  if (!gridApi.value) {
    console.log('Grid API not ready');
    return;
  }
  
  console.log('=== Grid Debug ===');
  console.log('props.editable:', props.editable);
  console.log('Current editable fields count:', currentEditableFields.value.length);
  console.log('First 10 editable fields:', currentEditableFields.value.slice(0, 10));
  
  const columns = gridApi.value.getColumns();
  if (columns) {
    let editableColumns = [];
    let nonEditableColumns = [];
    
    columns.forEach(col => {
      const colDef = col.getColDef();
      if (colDef.editable) {
        editableColumns.push(colDef.field);
      } else {
        nonEditableColumns.push(colDef.field);
      }
    });
    
    console.log('Editable columns count:', editableColumns.length);
    console.log('First 10 editable columns:', editableColumns.slice(0, 10));
    console.log('Total non-editable:', nonEditableColumns.length);
  }
};

const debugEditableConfig = () => {
  console.log('=== Editable Config Debug ===');
  console.log('props.editable:', props.editable);
  console.log('user role:', authStore.user?.role?.name);
  console.log('dbConfig:', dbConfig.value);
  console.log('configLoaded:', configLoaded.value);
  
  const editableFields = getEditableFieldsDirect();
  console.log('Editable fields count:', editableFields.length);
  console.log('First 10 editable fields:', editableFields.slice(0, 10));
  
  const roleConfig = ordersColumns.roles[authStore.user?.role?.name || 'viewer'];
  console.log('Role config:', roleConfig);
  console.log('All columns count:', getAllColumns().length);
};

onUnmounted(() => {
  if (saveTimeout) {
    clearTimeout(saveTimeout);
  }
});

defineExpose({
  exportData,
  saveColumnSettings: saveColumnSettingsMethod,
  refreshGrid,
  applyColumnSettings,
  getCurrentColumnState,
  debugGrid,
  debugEditableConfig,
  updateGridColumns
});
</script>

<style scoped>
.orders-grid {
  height: 100%;
  width: 100%;
  overflow: hidden;
  position: relative;
}

:deep(.ag-root-wrapper) {
  height: 100% !important;
  width: 100% !important;
  overflow: hidden !important;
}

:deep(.ag-root) {
  height: 100% !important;
  width: 100% !important;
}

:deep(.ag-body-viewport) {
  overflow-y: auto !important;
  overflow-x: auto !important;
}

:deep(.status-badge) {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

:deep(.status-new) {
  background: #e3f2fd;
  color: #1976d2;
}

:deep(.status-in_transit) {
  background: #fff3e0;
  color: #f57c00;
}

:deep(.status-completed) {
  background: #e8f5e9;
  color: #388e3c;
}

:deep(.status-cancelled) {
  background: #ffebee;
  color: #d32f2f;
}

:deep(.payment-status) {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

:deep(.status-paid) {
  background: #d4edda;
  color: #155724;
}

:deep(.status-pending) {
  background: #fff3cd;
  color: #856404;
}

:deep(.status-overdue) {
  background: #f8d7da;
  color: #721c24;
}

:deep(.status-cancelled) {
  background: #e2e3e5;
  color: #383d41;
}

:deep(.ag-header-cell) {
  transition: all 0.2s ease;
}

:deep(.ag-header-cell-moving) {
  opacity: 0.6;
  background-color: #f0f0f0;
}
</style>
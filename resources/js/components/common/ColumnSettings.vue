<!-- resources/js/components/common/ColumnSettings.vue -->
<template>
  <div class="modal-overlay" @click.self="close">
    <div class="modal">
      <div class="modal-header">
        <h3>Настройка колонок</h3>
        <button class="close-btn" @click="close">×</button>
      </div>
      
      <div class="modal-body">
        <div class="column-settings-info">
          <span class="info-text">💡 Перетащите колонки мышкой для изменения порядка</span>
          <button @click="resetToDefault" class="reset-btn">Сбросить к стандарту</button>
        </div>
        
        <div class="columns-list" ref="columnsList">
          <div 
            v-for="(column, index) in sortedColumns" 
            :key="column.field"
            class="column-item"
            :class="{ dragging: dragIndex === index }"
            draggable="true"
            @dragstart="onDragStart($event, index)"
            @dragend="onDragEnd"
            @dragover.prevent
            @dragenter="onDragEnter($event, index)"
          >
            <div class="drag-handle">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="12" r="1.5" />
                <circle cx="15" cy="12" r="1.5" />
                <circle cx="9" cy="16" r="1.5" />
                <circle cx="15" cy="16" r="1.5" />
                <circle cx="9" cy="8" r="1.5" />
                <circle cx="15" cy="8" r="1.5" />
              </svg>
            </div>
            
            <label class="column-checkbox">
              <input 
                type="checkbox" 
                v-model="columnVisibility[column.field]"
                @change="onColumnVisibilityChange(column.field)"
              >
              <span>{{ column.headerName || column.field }}</span>
            </label>
            
            <div class="column-group-tag">
              {{ column.group || 'Основные' }}
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button @click="close" class="btn-secondary">Отмена</button>
        <button @click="save" class="btn-primary">Сохранить</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  visibleColumns: {
    type: Array,
    default: () => []
  },
  columnOrder: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['save', 'close']);

const columnVisibility = ref({});
const columnOrderState = ref([]);
const dragIndex = ref(null);
const columnsList = ref(null);

onMounted(() => {
  // Устанавливаем видимость
  props.columns.forEach(column => {
    if (props.visibleColumns.length > 0) {
      columnVisibility.value[column.field] = props.visibleColumns.includes(column.field);
    } else {
      columnVisibility.value[column.field] = true;
    }
  });
  
  // Устанавливаем порядок
  if (props.columnOrder && props.columnOrder.length > 0) {
    columnOrderState.value = [...props.columnOrder];
  } else {
    columnOrderState.value = [...props.columns.map(c => c.field)];
  }
});

const sortedColumns = computed(() => {
  return columnOrderState.value
    .map(field => props.columns.find(c => c.field === field))
    .filter(c => c);
});

const onDragStart = (event, index) => {
  dragIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  event.target.classList.add('dragging');
};

const onDragEnd = (event) => {
  dragIndex.value = null;
  event.target.classList.remove('dragging');
};

const onDragEnter = (event, index) => {
  event.preventDefault();
  
  if (dragIndex.value === null || dragIndex.value === index) return;
  
  const newOrder = [...columnOrderState.value];
  const draggedItem = newOrder[dragIndex.value];
  
  newOrder.splice(dragIndex.value, 1);
  newOrder.splice(index, 0, draggedItem);
  
  columnOrderState.value = newOrder;
  dragIndex.value = index;
};

const onColumnVisibilityChange = (field) => {
  // Можно добавить логику
};

const save = () => {
  const visible = sortedColumns.value
    .filter(col => columnVisibility.value[col.field])
    .map(col => col.field);
  
  emit('save', { 
    visible, 
    order: columnOrderState.value,
    visibility: columnVisibility.value 
  });
  close();
};

const resetToDefault = () => {
  props.columns.forEach(column => {
    columnVisibility.value[column.field] = true;
  });
  columnOrderState.value = [...props.columns.map(c => c.field)];
};

const close = () => {
  emit('close');
};
</script>

<style scoped>
/* ... стили из предыдущей версии ... */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: fadeIn 0.2s ease;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 550px;
  max-width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s ease;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.close-btn:hover {
  background: #f8f9fa;
  color: #495057;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.column-settings-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e9ecef;
}

.info-text {
  font-size: 12px;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 6px;
}

.reset-btn {
  padding: 4px 12px;
  font-size: 12px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  color: #6c757d;
}

.reset-btn:hover {
  background: #e9ecef;
  border-color: #c17b4b;
  color: #c17b4b;
}

.columns-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.column-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.2s;
  cursor: move;
  user-select: none;
}

.column-item:hover {
  background: #e9ecef;
  border-color: #c17b4b;
  transform: translateX(2px);
}

.column-item.dragging {
  opacity: 0.5;
  background: #dee2e6;
}

.drag-handle {
  cursor: grab;
  color: #adb5bd;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.column-item:hover .drag-handle {
  color: #c17b4b;
}

.drag-handle:active {
  cursor: grabbing;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  flex: 1;
}

.column-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.column-checkbox span {
  font-size: 14px;
  color: #495057;
}

.column-group-tag {
  font-size: 11px;
  padding: 2px 8px;
  background: #e9ecef;
  border-radius: 12px;
  color: #6c757d;
  white-space: nowrap;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #e9ecef;
}

.btn-primary, .btn-secondary {
  padding: 8px 20px;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  font-weight: 500;
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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.columns-list::-webkit-scrollbar {
  width: 8px;
}

.columns-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.columns-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.columns-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
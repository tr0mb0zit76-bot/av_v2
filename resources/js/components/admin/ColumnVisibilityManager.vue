<template>
  <div class="column-visibility-manager">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Загрузка настроек...</p>
    </div>
    
    <div v-else-if="!isAdmin" class="no-access">
      <div class="no-access-icon">⛔</div>
      <h3>Доступ ограничен</h3>
      <p>Только администраторы могут управлять настройками видимости колонок</p>
    </div>
    
    <div v-else>
      <div class="manager-header">
        <h3>Настройка видимости колонок по ролям</h3>
        <div class="module-selector">
          <button 
            v-for="mod in modules" 
            :key="mod.key"
            :class="['module-btn', { active: currentModule === mod.key }]"
            @click="currentModule = mod.key"
          >
            {{ mod.label }}
          </button>
        </div>
      </div>
      
      <div class="roles-tabs">
        <button 
          v-for="role in roles" 
          :key="role.id"
          :class="['role-tab', { active: selectedRole?.id === role.id }]"
          @click="selectRole(role)"
        >
          <span class="role-name">{{ role.display_name || role.name }}</span>
          <span class="role-badge">{{ role.users_count || 0 }}</span>
        </button>
      </div>
      
      <div v-if="selectedRole" class="columns-config">
        <div class="config-header">
          <div class="config-title">
            <h4>{{ selectedRole.display_name || selectedRole.name }}</h4>
            <span class="role-description">{{ selectedRole.description || 'Настройка видимости колонок' }}</span>
          </div>
          <div class="config-actions">
            <button @click="resetToDefault" class="btn-reset">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              Сбросить
            </button>
            <button @click="saveConfig" class="btn-save" :disabled="saving">
              <svg v-if="!saving" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              <div v-else class="spinner-small"></div>
              {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
            </button>
          </div>
        </div>
        
        <div class="columns-list">
          <div class="columns-header">
            <div class="col-checkbox">
              <input 
                type="checkbox" 
                :checked="allColumnsSelected" 
                @change="toggleAllColumns"
                class="checkbox"
              >
              <span>Показывать</span>
            </div>
            <div class="col-name">Название колонки</div>
            <div class="col-group">Группа</div>
            <div class="col-editable">Разрешено редактирование</div>
          </div>
          
          <div 
            v-for="column in allColumns" 
            :key="column.field"
            class="column-row"
            :class="{ 'is-required': column.required }"
          >
            <div class="col-checkbox">
              <input 
                type="checkbox" 
                v-model="tempConfig.visible[column.field]"
                :disabled="column.required"
                class="checkbox"
              >
            </div>
            <div class="col-name">
              {{ column.headerName || column.field }}
              <span v-if="column.required" class="required-badge">обязательная</span>
            </div>
            <div class="col-group">
              <span class="group-tag">{{ column.group || 'Основные' }}</span>
            </div>
            <div class="col-editable">
              <label class="toggle-switch">
                <input 
                  type="checkbox" 
                  v-model="tempConfig.editable[column.field]"
                  :disabled="!tempConfig.visible[column.field]"
                >
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>
        
        <div class="config-footer">
          <div class="info-message">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="12" x2="12" y2="16"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Обязательные колонки (отмечены значком) нельзя скрыть</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { ordersColumns } from '@/config/ordersColumns';

const authStore = useAuthStore();
const modules = ref([
  { key: 'orders', label: 'Заказы', columns: ordersColumns.all },
  { key: 'contractors', label: 'Контрагенты', columns: [] },
  { key: 'cargos', label: 'Грузы', columns: [] }
]);

const currentModule = ref('orders');
const roles = ref([]);
const selectedRole = ref(null);
const tempConfig = ref({
  visible: {},
  editable: {}
});
const saving = ref(false);
const loading = ref(false);

const isAdmin = computed(() => authStore.isAdmin);

const allColumns = computed(() => {
  const module = modules.value.find(m => m.key === currentModule.value);
  if (currentModule.value === 'orders') {
    const additionalColumns = [
      { field: 'delta', headerName: 'Δ Дельта', group: 'Финансы', required: false },
      { field: 'salary_accrued', headerName: 'Начислено', group: 'Зарплата', required: false },
      { field: 'salary_paid', headerName: 'Выплачено', group: 'Зарплата', required: false },
      { field: 'invoice_number', headerName: '№ счёта', group: 'Документы', required: false },
      { field: 'upd_number', headerName: '№ УПД', group: 'Документы', required: false },
      { field: 'waybill_number', headerName: 'ТН', group: 'Документы', required: false },
      { field: 'payment_statuses', headerName: 'Статус оплаты', group: 'Оплата', required: false }
    ];
    return [...(module?.columns || []), ...additionalColumns];
  }
  return module?.columns || [];
});

const allColumnsSelected = computed(() => {
  const visibleColumns = allColumns.value.filter(col => !col.required);
  if (visibleColumns.length === 0) return false;
  return visibleColumns.every(col => tempConfig.value.visible[col.field]);
});

const loadRoles = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/roles');
    roles.value = response.data;
  } catch (error) {
    console.error('Error loading roles:', error);
    if (error.response?.status === 401) {
      alert('Недостаточно прав для просмотра настроек');
    }
  } finally {
    loading.value = false;
  }
};

const selectRole = (role) => {
  selectedRole.value = role;
  loadRoleConfig(role);
};

const loadRoleConfig = (role) => {
  const savedConfig = role.columns_config?.[currentModule.value];
  const defaultConfig = ordersColumns.roles[role.name] || ordersColumns.roles.viewer;
  
  const visible = {};
  const editable = {};
  
  allColumns.value.forEach(col => {
    if (savedConfig) {
      visible[col.field] = savedConfig.visible?.includes(col.field) ?? 
                          (defaultConfig.visible?.includes(col.field) ?? true);
      editable[col.field] = savedConfig.editable?.includes(col.field) ?? 
                           (defaultConfig.editable === true || defaultConfig.editable?.includes(col.field));
    } else {
      visible[col.field] = defaultConfig.visible?.includes(col.field) ?? true;
      editable[col.field] = defaultConfig.editable === true || defaultConfig.editable?.includes(col.field);
    }
  });
  
  tempConfig.value = { visible, editable };
};

const toggleAllColumns = () => {
  const shouldSelect = !allColumnsSelected.value;
  allColumns.value.forEach(col => {
    if (!col.required) {
      tempConfig.value.visible[col.field] = shouldSelect;
      if (!shouldSelect) {
        tempConfig.value.editable[col.field] = false;
      }
    }
  });
};

const saveConfig = async () => {
  if (!selectedRole.value || !isAdmin.value) return;
  
  saving.value = true;
  
  const visible = Object.entries(tempConfig.value.visible)
    .filter(([_, v]) => v === true)
    .map(([k]) => k);
    
  const editable = Object.entries(tempConfig.value.editable)
    .filter(([_, v]) => v === true)
    .map(([k]) => k);
  
  try {
    await axios.post(`/api/admin/roles/${selectedRole.value.id}/columns-config`, {
      module: currentModule.value,
      config: { visible, editable }
    });
    
    // Показываем временное уведомление
    const notification = document.createElement('div');
    notification.className = 'save-notification';
    notification.textContent = '✓ Настройки сохранены';
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 2000);
    
  } catch (error) {
    console.error('Error saving config:', error);
    alert('Ошибка сохранения настроек');
  } finally {
    saving.value = false;
  }
};

const resetToDefault = async () => {
  if (!selectedRole.value || !isAdmin.value) return;
  
  if (!confirm('Сбросить настройки для этой роли к стандартным?')) return;
  
  try {
    await axios.delete(`/api/admin/roles/${selectedRole.value.id}/columns-config/${currentModule.value}`);
    loadRoleConfig(selectedRole.value);
    alert('Настройки сброшены к стандартным');
  } catch (error) {
    console.error('Error resetting config:', error);
    alert('Ошибка сброса настроек');
  }
};

onMounted(() => {
  if (isAdmin.value) {
    loadRoles();
  }
});
</script>

<style scoped>
.column-visibility-manager {
  background: white;
  border-radius: 12px;
}

/* Загрузка */
.loading-state, .no-access {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: #6c757d;
  text-align: center;
}

.no-access-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.loading-state .spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #c17b4b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  margin-right: 8px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Заголовок */
.manager-header {
  margin-bottom: 24px;
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 16px;
}

.manager-header h3 {
  margin: 0 0 16px 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2a36;
}

.module-selector {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.module-btn {
  padding: 8px 20px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  color: #495057;
}

.module-btn:hover {
  background: #e9ecef;
  border-color: #c17b4b;
}

.module-btn.active {
  background: #c17b4b;
  color: white;
  border-color: #c17b4b;
}

/* Вкладки ролей */
.roles-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 12px;
}

.role-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  color: #6c757d;
}

.role-tab:hover {
  background: #f8f9fa;
  color: #c17b4b;
}

.role-tab.active {
  background: #c17b4b;
  color: white;
}

.role-name {
  font-weight: 500;
}

.role-badge {
  background: #e9ecef;
  color: #6c757d;
  padding: 2px 6px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 500;
}

.role-tab.active .role-badge {
  background: rgba(255, 255, 255, 0.3);
  color: white;
}

/* Конфигурация колонок */
.columns-config {
  background: #f8f9fa;
  border-radius: 12px;
  overflow: hidden;
}

.config-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 20px 24px;
  background: white;
  border-bottom: 1px solid #e9ecef;
}

.config-title h4 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2a36;
}

.role-description {
  font-size: 12px;
  color: #6c757d;
}

.config-actions {
  display: flex;
  gap: 12px;
}

.btn-reset, .btn-save {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-reset {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  color: #6c757d;
}

.btn-reset:hover {
  background: #e9ecef;
  border-color: #c17b4b;
  color: #c17b4b;
}

.btn-save {
  background: #c17b4b;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #b06a3d;
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Список колонок */
.columns-list {
  overflow-x: auto;
}

.columns-header {
  display: grid;
  grid-template-columns: 100px 1fr 120px 140px;
  background: #f8f9fa;
  padding: 12px 20px;
  font-size: 12px;
  font-weight: 600;
  color: #6c757d;
  border-bottom: 1px solid #e9ecef;
}

.column-row {
  display: grid;
  grid-template-columns: 100px 1fr 120px 140px;
  padding: 12px 20px;
  border-bottom: 1px solid #e9ecef;
  align-items: center;
  transition: background 0.2s;
}

.column-row:hover {
  background: #faf7f2;
}

.column-row.is-required {
  background: #fff8e7;
}

.col-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
}

.checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.col-name {
  font-size: 14px;
  color: #495057;
}

.required-badge {
  font-size: 10px;
  background: #ffc107;
  color: #856404;
  padding: 2px 6px;
  border-radius: 12px;
  margin-left: 8px;
}

.group-tag {
  font-size: 11px;
  padding: 4px 8px;
  background: #e9ecef;
  border-radius: 6px;
  color: #6c757d;
}

/* Toggle switch */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e9ecef;
  transition: 0.3s;
  border-radius: 34px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
  background-color: #c17b4b;
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.toggle-switch input:disabled + .toggle-slider {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Футер */
.config-footer {
  padding: 16px 24px;
  background: white;
  border-top: 1px solid #e9ecef;
}

.info-message {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #6c757d;
}

/* Уведомление о сохранении */
.save-notification {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #28a745;
  color: white;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  z-index: 10000;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Адаптивность */
@media (max-width: 768px) {
  .columns-header, .column-row {
    grid-template-columns: 80px 1fr 100px 80px;
    gap: 8px;
  }
  
  .config-header {
    flex-direction: column;
    gap: 12px;
  }
  
  .config-actions {
    width: 100%;
  }
  
  .btn-reset, .btn-save {
    flex: 1;
    justify-content: center;
  }
}
</style>
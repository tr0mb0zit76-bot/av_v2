<template>
  <div class="settings-page">
    <div class="settings-sidebar">
      <div class="sidebar-menu">
        <button 
          v-for="section in sections" 
          :key="section.key"
          :class="['menu-item', { active: currentSection === section.key }]"
          @click="currentSection = section.key"
        >
          <span class="menu-icon">{{ section.icon }}</span>
          <span class="menu-label">{{ section.label }}</span>
        </button>
      </div>
    </div>
    
    <div class="settings-content">
      <ColumnVisibilityManager v-if="currentSection === 'columns'" />
      <div v-else class="coming-soon">
        <div class="coming-soon-icon">🚧</div>
        <h3>В разработке</h3>
        <p>Этот раздел будет доступен в ближайшее время</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ColumnVisibilityManager from '@/components/admin/ColumnVisibilityManager.vue';

const sections = ref([
  { key: 'columns', label: 'Видимость колонок', icon: '📊' },
  { key: 'general', label: 'Общие', icon: '⚙️' },
  { key: 'notifications', label: 'Уведомления', icon: '🔔' },
  { key: 'backup', label: 'Резервное копирование', icon: '💾' }
]);

const currentSection = ref('columns');
</script>

<style scoped>
.settings-page {
  display: flex;
  gap: 24px;
  height: 100%;
  min-height: 500px;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Левая боковая панель */
.settings-sidebar {
  width: 280px;
  background: #f8f9fa;
  border-right: 1px solid #e9ecef;
  padding: 24px 0;
  flex-shrink: 0;
}

.sidebar-menu {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  margin: 0 12px;
  text-align: left;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  color: #495057;
}

.menu-item:hover {
  background: #e9ecef;
  color: #c17b4b;
}

.menu-item.active {
  background: #c17b4b;
  color: white;
}

.menu-icon {
  font-size: 18px;
  width: 24px;
  text-align: center;
}

.menu-label {
  flex: 1;
}

/* Правая область контента */
.settings-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  background: white;
}

/* Стили для заглушки "В разработке" */
.coming-soon {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 400px;
  text-align: center;
}

.coming-soon-icon {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.6;
}

.coming-soon h3 {
  font-size: 24px;
  color: #495057;
  margin-bottom: 12px;
}

.coming-soon p {
  font-size: 14px;
  color: #6c757d;
}

/* Скроллбар для контента */
.settings-content::-webkit-scrollbar {
  width: 8px;
}

.settings-content::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.settings-content::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.settings-content::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Адаптивность */
@media (max-width: 768px) {
  .settings-page {
    flex-direction: column;
    border-radius: 8px;
  }
  
  .settings-sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e9ecef;
    padding: 12px 0;
  }
  
  .sidebar-menu {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0 12px;
  }
  
  .menu-item {
    margin: 0;
    padding: 8px 16px;
  }
  
  .settings-content {
    padding: 20px;
  }
}
</style>
<template>
  <div class="main-layout">
    <!-- Верхнее меню -->
    <nav class="navbar">
      <div class="navbar-brand">
        <h2>🚛 Автопартнер</h2>
      </div>
      
      <div class="navbar-menu">
        <!-- Дашборд -->
        <router-link to="/dashboard" class="nav-item" :class="{ active: $route.path === '/dashboard' }">
          📊 Дашборд
        </router-link>

        <!-- CRM -->
        <div class="dropdown" @mouseenter="openDropdown('crm')" @mouseleave="closeDropdown('crm')">
          <button class="dropdown-btn">
            👥 CRM
            <span class="arrow">▼</span>
          </button>
          <div class="dropdown-content" v-show="activeDropdown === 'crm'">
            <router-link to="/contractors" class="dropdown-item">Контрагенты</router-link>
            <router-link to="#" class="dropdown-item">Задачи</router-link>
          </div>
        </div>

        <!-- Перевозки -->
        <div class="dropdown" @mouseenter="openDropdown('transport')" @mouseleave="closeDropdown('transport')">
          <button class="dropdown-btn">
            🚚 Перевозки
            <span class="arrow">▼</span>
          </button>
          <div class="dropdown-content" v-show="activeDropdown === 'transport'">
            <router-link to="/orders" class="dropdown-item">Заказы</router-link>
            <router-link to="#" class="dropdown-item">Маршруты</router-link>
            <router-link to="/cargos" class="dropdown-item">Грузы</router-link>
          </div>
        </div>

        <!-- Финансы -->
        <div class="dropdown" @mouseenter="openDropdown('finance')" @mouseleave="closeDropdown('finance')">
          <button class="dropdown-btn">
            💰 Финансы
            <span class="arrow">▼</span>
          </button>
          <div class="dropdown-content" v-show="activeDropdown === 'finance'">
            <router-link to="/reports" class="dropdown-item">Отчеты</router-link>
            <router-link to="#" class="dropdown-item">Оплаты</router-link>
            <router-link to="#" class="dropdown-item">Счета</router-link>
          </div>
        </div>

        <!-- Настройки (только для админа) -->
        <div v-if="authStore.isAdmin" class="dropdown" @mouseenter="openDropdown('settings')" @mouseleave="closeDropdown('settings')">
          <button class="dropdown-btn">
            ⚙️ Настройки
            <span class="arrow">▼</span>
          </button>
          <div class="dropdown-content" v-show="activeDropdown === 'settings'">
            <router-link to="/users" class="dropdown-item">🧑‍💻Пользователи</router-link>
            <router-link to="/settings" class="dropdown-item">⚒️​ Системные настройки</router-link>
          </div>
        </div>
      </div>
      
      <div class="navbar-user">
        <span class="user-name">{{ authStore.userName }}</span>
        <span class="user-role">{{ authStore.userRole }}</span>
        <button @click="logout" class="logout-btn">🚪 Выйти</button>
      </div>
    </nav>
    
    <!-- Основной контент -->
    <main class="main-content">
      <router-view />
    </main>
    
    <!-- Глобальная AI строка (всегда внизу) -->
    <div class="ai-footer">
      <div class="ai-input-wrapper">
        <input 
          type="text" 
          v-model="aiInput" 
          class="ai-input" 
          :placeholder="aiPlaceholder"
          @keyup.enter="sendAiMessage"
          @focus="onAiFocus"
          @blur="onAiBlur"
        />
        <button class="ai-send-btn" @click="sendAiMessage" :disabled="aiLoading">
          {{ aiLoading ? '⏳' : '→' }}
        </button>
      </div>
      <div class="ai-suggestions">
        <span 
          v-for="suggestion in aiSuggestions" 
          :key="suggestion.text"
          class="suggestion" 
          @click="quickSearch(suggestion.query || suggestion.text)"
        >
          {{ suggestion.icon }} {{ suggestion.text }}
        </span>
      </div>
      
      <!-- AI ответ (опционально) -->
      <div v-if="aiResponse" class="ai-response">
        <div class="ai-response-content">
          <span class="ai-icon">🤖</span>
          <span>{{ aiResponse }}</span>
          <button class="close-response" @click="aiResponse = null">×</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAiStore } from '@/stores/ai'; // Создадим отдельный store для AI

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const aiStore = useAiStore();

const activeDropdown = ref(null);
const aiInput = ref('');
const aiLoading = ref(false);
const aiResponse = ref(null);

// Динамический placeholder в зависимости от текущей страницы
const aiPlaceholder = computed(() => {
  const page = route.name;
  switch (page) {
    case 'dashboard':
      return '💬 Спросите AI о статистике, KPI, или попросите сделать отчет...';
    case 'orders':
      return '💬 Спросите AI о заказах, KPI, или попросите найти перевозку...';
    case 'contractors':
      return '💬 Спросите AI о контрагентах, поищите по ИНН или названию...';
    case 'cargos':
      return '💬 Спросите AI о грузах, подберите транспорт под характеристики...';
    case 'reports':
      return '💬 Спросите AI сформировать отчет по финансам или заказам...';
    default:
      return '💬 Спросите AI о работе с системой...';
  }
});

// Динамические подсказки в зависимости от текущей страницы
const aiSuggestions = computed(() => {
  const page = route.name;
  const suggestions = {
    dashboard: [
      { icon: '📊', text: 'Показать KPI за месяц', query: 'покажи KPI менеджеров за этот месяц' },
      { icon: '💰', text: 'Общая выручка', query: 'какая общая выручка за сегодня?' },
      { icon: '🚛', text: 'Активные заказы', query: 'сколько активных заказов в работе?' },
      { icon: '📈', text: 'Динамика', query: 'покажи динамику заказов по дням' }
    ],
    orders: [
      { icon: '📅', text: 'Заказы за сегодня', query: 'показать заказы за сегодня' },
      { icon: '🚛', text: 'В пути', query: 'заказы в пути' },
      { icon: '📊', text: 'KPI менеджеров', query: 'KPI менеджеров' },
      { icon: '🔍', text: 'Найти перевозку', query: 'найти перевозку Москва-Казань' }
    ],
    contractors: [
      { icon: '🔍', text: 'Поиск по ИНН', query: 'найди контрагента по ИНН 7701234567' },
      { icon: '➕', text: 'Добавить перевозчика', query: 'как добавить нового перевозчика?' },
      { icon: '📋', text: 'Активные контрагенты', query: 'покажи активных контрагентов' }
    ],
    cargos: [
      { icon: '📦', text: 'Типы грузов', query: 'какие типы грузов есть в системе?' },
      { icon: '⚖️', text: 'Поиск по весу', query: 'найди грузы до 20 тонн' },
      { icon: '🚛', text: 'Подобрать транспорт', query: 'подбери транспорт для груза 5 тонн Москва-СПб' }
    ],
    reports: [
      { icon: '💰', text: 'Финансовый отчет', query: 'сформируй финансовый отчет за месяц' },
      { icon: '📊', text: 'KPI отчет', query: 'отчет по KPI менеджеров' },
      { icon: '📥', text: 'Экспорт', query: 'экспортировать отчет в Excel' }
    ]
  };
  
  return suggestions[page] || [
    { icon: '❓', text: 'Помощь', query: 'что я могу сделать?' },
    { icon: '📊', text: 'Статистика', query: 'покажи статистику' }
  ];
});

// Отправка сообщения AI
const sendAiMessage = async () => {
  if (!aiInput.value.trim()) return;
  
  aiLoading.value = true;
  
  try {
    // Сохраняем запрос в историю (опционально)
    aiStore.addToHistory({
      query: aiInput.value,
      page: route.name,
      timestamp: new Date()
    });
    
    // Отправляем запрос к AI API
    const response = await fetch('/api/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        message: aiInput.value,
        context: {
          page: route.name,
          userId: authStore.user?.id,
          userRole: authStore.user?.role?.name
        }
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      aiResponse.value = data.reply;
      
      // Если AI вернул действие (например, применить фильтр)
      if (data.action) {
        executeAiAction(data.action);
      }
    } else {
      aiResponse.value = 'Извините, не удалось обработать запрос. Попробуйте переформулировать.';
    }
    
  } catch (error) {
    console.error('AI request error:', error);
    aiResponse.value = 'Ошибка соединения с AI. Попробуйте позже.';
  } finally {
    aiLoading.value = false;
    aiInput.value = '';
    
    // Автоматически скрываем ответ через 10 секунд
    setTimeout(() => {
      if (aiResponse.value) {
        aiResponse.value = null;
      }
    }, 10000);
  }
};

// Выполнение действий от AI (например, применение фильтров)
const executeAiAction = (action) => {
  const { type, data } = action;
  
  switch (type) {
    case 'filter':
      // Применяем фильтры на текущей странице
      if (route.name === 'orders' && window.ordersGridRef) {
        window.ordersGridRef.applyFilters(data);
      }
      break;
    case 'navigate':
      // Переходим на другую страницу
      router.push(data.path);
      break;
    case 'export':
      // Экспортируем данные
      if (window.currentGridRef?.exportData) {
        window.currentGridRef.exportData();
      }
      break;
    default:
      console.log('Unknown action:', action);
  }
};

// Быстрый поиск
const quickSearch = (query) => {
  aiInput.value = query;
  sendAiMessage();
};

// Фокус на AI строке (можно добавить аналитику)
const onAiFocus = () => {
  console.log('AI input focused');
};

const onAiBlur = () => {
  console.log('AI input blurred');
};

const openDropdown = (name) => {
  activeDropdown.value = name;
};

const closeDropdown = (name) => {
  if (activeDropdown.value === name) {
    activeDropdown.value = null;
  }
};

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};

// Закрывать меню при клике вне
const handleClickOutside = (event) => {
  if (!event.target.closest('.dropdown')) {
    activeDropdown.value = null;
  }
};

// Горячая клавиша Ctrl+K / Cmd+K для фокуса на AI строке
const handleKeyDown = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
    event.preventDefault();
    const aiInputElement = document.querySelector('.ai-input');
    if (aiInputElement) {
      aiInputElement.focus();
    }
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
.main-layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.navbar {
  background: linear-gradient(135deg, #1a3b5e 0%, #2c4b6e 100%);
  color: white;
  padding: 0 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
  min-height: 64px;
  flex-shrink: 0;
}

.navbar-brand h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.navbar-menu {
  display: flex;
  gap: 8px;
  flex: 1;
  margin-left: 40px;
}

.nav-item {
  padding: 12px 20px;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  transition: all 0.3s;
  font-weight: 500;
  display: inline-block;
}

.nav-item:hover,
.nav-item.active {
  background: rgba(255,255,255,0.2);
}

.dropdown {
  position: relative;
}

.dropdown-btn {
  background: transparent;
  border: none;
  color: white;
  padding: 12px 20px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 6px;
  transition: all 0.3s;
}

.dropdown-btn:hover {
  background: rgba(255,255,255,0.2);
}

.arrow {
  font-size: 10px;
  transition: transform 0.2s;
}

.dropdown:hover .arrow {
  transform: rotate(180deg);
}

.dropdown-content {
  position: absolute;
  top: 100%;
  left: 0;
  background: white;
  min-width: 220px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  overflow: hidden;
  z-index: 1000;
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-item {
  display: block;
  padding: 12px 20px;
  color: #333;
  text-decoration: none;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.dropdown-item:hover {
  background: #f5f7fa;
  border-left-color: #c17b4b;
}

.navbar-user {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-name {
  font-weight: 500;
}

.user-role {
  font-size: 12px;
  opacity: 0.8;
  background: rgba(255,255,255,0.2);
  padding: 4px 8px;
  border-radius: 4px;
}

.logout-btn {
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: rgba(255,255,255,0.3);
}

.main-content {
  flex: 1;
  overflow: auto;
  padding: 20px;
}

/* Глобальная AI строка */
.ai-footer {
  flex-shrink: 0;
  background: white;
  border-top: 1px solid #e9ecef;
  padding: 12px 24px;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
  z-index: 100;
}

.ai-input-wrapper {
  display: flex;
  gap: 12px;
  margin-bottom: 8px;
  width: 100%;
}

.ai-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 24px;
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
  background: #f8f9fa;
}

.ai-input:focus {
  border-color: #c17b4b;
  background: white;
  box-shadow: 0 0 0 3px rgba(193, 123, 75, 0.1);
}

.ai-send-btn {
  background: #c17b4b;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 24px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
  flex-shrink: 0;
  font-size: 18px;
}

.ai-send-btn:hover:not(:disabled) {
  background: #b06a3d;
  transform: translateY(-1px);
}

.ai-send-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.ai-suggestions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.suggestion {
  padding: 6px 12px;
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 20px;
  font-size: 12px;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.suggestion:hover {
  background: #c17b4b;
  color: white;
  border-color: #c17b4b;
  transform: translateY(-1px);
}

.ai-response {
  margin-top: 10px;
  padding: 10px 12px;
  background: #e8f5e9;
  border-left: 4px solid #4caf50;
  border-radius: 8px;
  animation: slideUp 0.3s ease;
}

.ai-response-content {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #2e7d32;
}

.ai-icon {
  font-size: 18px;
}

.close-response {
  margin-left: auto;
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: #999;
  padding: 0 4px;
}

.close-response:hover {
  color: #333;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .navbar {
    flex-direction: column;
    padding: 12px;
  }
  
  .navbar-menu {
    margin: 12px 0;
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .main-content {
    padding: 16px;
  }
  
  .ai-footer {
    padding: 12px 16px;
  }
  
  .ai-suggestions {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 4px;
  }
  
  .dropdown-content {
    position: fixed;
    top: auto;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    max-width: 280px;
  }
}
</style>
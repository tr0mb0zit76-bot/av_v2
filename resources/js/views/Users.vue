<template>
  <div class="users-page">
    <div class="page-header">
      <h1>Пользователи</h1>
      <button @click="openModal" class="btn-primary">+ Добавить пользователя</button>
    </div>
    
    <!-- Вкладки -->
    <div class="tabs">
      <button 
        :class="['tab-btn', { active: activeTab === 'active' }]"
        @click="activeTab = 'active'"
      >
        Активные ({{ activeUsers.length }})
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'inactive' }]"
        @click="activeTab = 'inactive'"
      >
        Неактивные ({{ inactiveUsers.length }})
      </button>
    </div>
    
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Загрузка пользователей...</p>
    </div>
    
    <div v-else class="users-table">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Имя</th>
              <th>Email</th>
              <th>Роль</th>
              <th>Активен</th>
              <th>Создан</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in displayedUsers" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span :class="['badge', getBadgeClass(user.role?.name)]">
                  {{ user.role?.display_name || user.role?.name || 'Без роли' }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', user.is_active ? 'status-active' : 'status-inactive']">
                  {{ user.is_active ? 'Активен' : 'Неактивен' }}
                </span>
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>
                <div class="actions">
                  <button 
                    @click="editUser(user)"
                    class="btn-icon"
                    title="Редактировать"
                  >
                    ✏️
                  </button>
                  <button 
                    v-if="activeTab === 'active' && user.id !== authStore.user?.id"
                    @click="deactivateUser(user.id)"
                    class="btn-icon deactivate"
                    title="Деактивировать"
                  >
                    🔒
                  </button>
                  <button 
                    v-if="activeTab === 'inactive'"
                    @click="activateUser(user.id)"
                    class="btn-icon activate"
                    title="Активировать"
                  >
                    🔓
                  </button>
                  <button 
                    v-if="user.id !== authStore.user?.id"
                    @click="deleteUser(user.id)"
                    class="btn-icon delete"
                    title="Удалить"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="displayedUsers.length === 0 && !loading" class="empty-state">
        <p>{{ activeTab === 'active' ? 'Нет активных пользователей' : 'Нет неактивных пользователей' }}</p>
      </div>
    </div>
    
    <!-- Модальное окно -->
    <div v-if="showModal" class="modal" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingUser ? 'Редактировать пользователя' : 'Добавить пользователя' }}</h3>
          <button @click="closeModal" class="close-btn">×</button>
        </div>
        
        <form @submit.prevent="saveUser">
          <div class="form-group">
            <label>Имя *</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="Введите полное имя"
            />
          </div>
          
          <div class="form-group">
            <label>Email *</label>
            <input 
              v-model="form.email" 
              type="email" 
              required
              placeholder="user@example.com"
            />
          </div>
          
          <div class="form-group" v-if="!editingUser">
            <label>Пароль *</label>
            <input 
              v-model="form.password" 
              type="password" 
              required
              placeholder="Минимум 8 символов"
              minlength="8"
            />
          </div>
          
          <div class="form-group" v-if="editingUser">
            <label>Новый пароль</label>
            <input 
              v-model="form.new_password" 
              type="password" 
              placeholder="Оставьте пустым, чтобы не менять"
              minlength="8"
            />
            <small class="form-hint">Оставьте пустым, чтобы не менять пароль</small>
          </div>
          
          <div class="form-group">
            <label>Роль *</label>
            <select v-model="form.role_id" required>
              <option value="">Выберите роль</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.display_name || role.name }}
              </option>
            </select>
          </div>
          
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-secondary">Отмена</button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Сохранение...' : editingUser ? 'Сохранить' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const users = ref([]);
const roles = ref([]);
const loading = ref(true);
const showModal = ref(false);
const submitting = ref(false);
const editingUser = ref(null);
const activeTab = ref('active');

const form = ref({
  name: '',
  email: '',
  password: '',
  new_password: '',
  role_id: '',
  is_active: true
});

// Вычисляемые свойства для фильтрации пользователей
const activeUsers = computed(() => {
  return users.value.filter(user => user.is_active === true || user.is_active === 1);
});

const inactiveUsers = computed(() => {
  return users.value.filter(user => user.is_active === false || user.is_active === 0);
});

const displayedUsers = computed(() => {
  return activeTab.value === 'active' ? activeUsers.value : inactiveUsers.value;
});

const formatDate = (date) => {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const getBadgeClass = (roleName) => {
  const classes = {
    admin: 'badge-admin',
    manager: 'badge-manager',
    dispatcher: 'badge-dispatcher',
    accountant: 'badge-accountant',
    viewer: 'badge-viewer',
    supervisor: 'badge-supervisor',
    default: 'badge-default'
  };
  return classes[roleName] || 'badge-default';
};

const loadUsers = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/users');
    users.value = response.data.sort((a, b) => {
      return a.name.localeCompare(b.name, 'ru');
    });
  } catch (error) {
    console.error('Ошибка загрузки пользователей:', error);
    alert('Ошибка загрузки пользователей');
  } finally {
    loading.value = false;
  }
};

const loadRoles = async () => {
  try {
    const response = await axios.get('/api/roles');
    roles.value = response.data.filter(role => role.name !== 'default');
  } catch (error) {
    console.error('Ошибка загрузки ролей:', error);
  }
};

const editUser = (user) => {
  editingUser.value = user;
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    new_password: '',
    role_id: user.role_id,
    is_active: user.is_active
  };
  showModal.value = true;
};

const saveUser = async () => {
  if (!form.value.name || !form.value.email || !form.value.role_id) {
    alert('Заполните все обязательные поля');
    return;
  }
  
  if (!editingUser.value && (!form.value.password || form.value.password.length < 8)) {
    alert('Пароль должен быть не менее 8 символов');
    return;
  }
  
  submitting.value = true;
  try {
    let response;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    if (editingUser.value) {
      const updateData = {
        name: form.value.name,
        email: form.value.email,
        role_id: form.value.role_id,
        is_active: form.value.is_active
      };
      
      if (form.value.new_password && form.value.new_password.length >= 8) {
        updateData.password = form.value.new_password;
      }
      
      response = await axios.put(`/api/users/${editingUser.value.id}`, updateData, {
        headers: { 'X-CSRF-TOKEN': csrfToken }
      });
      
      const index = users.value.findIndex(u => u.id === editingUser.value.id);
      if (index !== -1) {
        users.value[index] = response.data;
        users.value.sort((a, b) => a.name.localeCompare(b.name, 'ru'));
      }
    } else {
      response = await axios.post('/api/users', {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        role_id: form.value.role_id,
        is_active: form.value.is_active
      }, {
        headers: { 'X-CSRF-TOKEN': csrfToken }
      });
      users.value.unshift(response.data);
      users.value.sort((a, b) => a.name.localeCompare(b.name, 'ru'));
    }
    
    closeModal();
    alert(editingUser.value ? 'Пользователь обновлен' : 'Пользователь создан');
  } catch (error) {
    console.error('Ошибка:', error);
    alert(error.response?.data?.message || 'Ошибка сохранения');
  } finally {
    submitting.value = false;
  }
};

const deactivateUser = async (id) => {
  if (!confirm('Деактивировать пользователя? Он не сможет заходить в систему.')) return;
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    await axios.put(`/api/users/${id}`, { is_active: false }, {
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    
    const user = users.value.find(u => u.id === id);
    if (user) {
      user.is_active = false;
      users.value.sort((a, b) => a.name.localeCompare(b.name, 'ru'));
    }
    
    alert('Пользователь деактивирован');
  } catch (error) {
    console.error('Ошибка деактивации:', error);
    alert('Ошибка деактивации пользователя');
  }
};

const activateUser = async (id) => {
  if (!confirm('Активировать пользователя?')) return;
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    await axios.put(`/api/users/${id}`, { is_active: true }, {
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    
    const user = users.value.find(u => u.id === id);
    if (user) {
      user.is_active = true;
      users.value.sort((a, b) => a.name.localeCompare(b.name, 'ru'));
    }
    
    alert('Пользователь активирован');
  } catch (error) {
    console.error('Ошибка активации:', error);
    alert('Ошибка активации пользователя');
  }
};

const deleteUser = async (id) => {
  if (!confirm('Вы уверены, что хотите удалить этого пользователя?')) return;
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    await axios.delete(`/api/users/${id}`, {
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    
    users.value = users.value.filter(u => u.id !== id);
    alert('Пользователь удален');
  } catch (error) {
    console.error('Ошибка удаления:', error);
    alert('Ошибка удаления пользователя');
  }
};

const openModal = () => {
  editingUser.value = null;
  form.value = {
    name: '',
    email: '',
    password: '',
    new_password: '',
    role_id: '',
    is_active: true
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUser.value = null;
};

onMounted(() => {
  loadUsers();
  loadRoles();
});
</script>

<style scoped>
.users-page {
  background: white;
  border-radius: 12px;
  padding: 24px;
  min-height: 500px;
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
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #b06a3d;
}

/* Вкладки */
.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid #eee;
}

.tab-btn {
  padding: 10px 20px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  transition: all 0.2s;
  border-bottom: 2px solid transparent;
}

.tab-btn:hover {
  color: #c17b4b;
}

.tab-btn.active {
  color: #c17b4b;
  border-bottom-color: #c17b4b;
}

.users-table {
  overflow-x: auto;
}

.table-wrapper {
  max-height: calc(100vh - 300px);
  overflow-y: auto;
  border: 1px solid #eee;
  border-radius: 8px;
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
  position: sticky;
  top: 0;
  z-index: 10;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.badge-admin { background: #dc3545; color: white; }
.badge-manager { background: #28a745; color: white; }
.badge-dispatcher { background: #17a2b8; color: white; }
.badge-accountant { background: #ffc107; color: #333; }
.badge-viewer { background: #6c757d; color: white; }
.badge-supervisor { background: #fd7e14; color: white; }
.badge-default { background: #6c757d; color: white; }

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.status-active {
  background: #d4edda;
  color: #155724;
}

.status-inactive {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f0f0f0;
}

.btn-icon.deactivate:hover {
  background: #fff3cd;
  color: #856404;
}

.btn-icon.activate:hover {
  background: #d4edda;
  color: #155724;
}

.btn-icon.delete:hover {
  background: #fee;
  color: #dc3545;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: #666;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #c17b4b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 60px;
  color: #999;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 500px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #999;
}

.form-group {
  padding: 16px 24px;
  border-bottom: 1px solid #f0f0f0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #666;
  font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.form-group input[type="checkbox"] {
  width: auto;
  margin-right: 8px;
}

.form-hint {
  display: block;
  font-size: 12px;
  color: #999;
  margin-top: 5px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  background: #f8f9fa;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
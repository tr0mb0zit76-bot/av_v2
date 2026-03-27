<!-- resources/js/components/orders/OrderForm.vue -->
<template>
  <form @submit.prevent="save" class="order-form">
    <div class="form-grid">
      <div class="form-group">
        <label>№ заказа</label>
        <input 
          :value="form.order_number" 
          @input="updateForm('order_number', $event.target.value)"
          type="text" 
          class="form-control" 
          :readonly="!!form.id"
        >
      </div>
      
      <div class="form-group">
        <label>Наша компания *</label>
        <select 
          :value="form.company_code" 
          @change="updateForm('company_code', $event.target.value)"
          class="form-control" 
          required
        >
          <option value="">Выберите</option>
          <option value="ЛР">ЛР (Логистические Решения)</option>
          <option value="АП">АП (Автопартнер)</option>
          <option value="КВ">КВ (КВ)</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Дата заявки *</label>
        <input 
          :value="form.order_date" 
          @input="updateForm('order_date', $event.target.value)"
          type="date" 
          class="form-control" 
          required
        >
      </div>
      
      <div class="form-group">
        <label>Заказчик</label>
        <input 
          :value="form.customer_name" 
          @input="updateForm('customer_name', $event.target.value)"
          type="text" 
          class="form-control"
        >
      </div>
      
      <div class="form-group">
        <label>Перевозчик</label>
        <input 
          :value="form.carrier_name" 
          @input="updateForm('carrier_name', $event.target.value)"
          type="text" 
          class="form-control"
        >
      </div>
      
      <div class="form-group">
        <label>Ставка заказчика</label>
        <input 
          :value="form.customer_rate" 
          @input="updateForm('customer_rate', parseFloat($event.target.value))"
          type="number" 
          step="0.01" 
          class="form-control"
        >
      </div>
      
      <div class="form-group">
        <label>Ставка перевозчика</label>
        <input 
          :value="form.carrier_rate" 
          @input="updateForm('carrier_rate', parseFloat($event.target.value))"
          type="number" 
          step="0.01" 
          class="form-control"
        >
      </div>
      
      <div class="form-group" style="grid-column: span 2;">
        <label>Груз</label>
        <textarea 
          :value="form.cargo_description" 
          @input="updateForm('cargo_description', $event.target.value)"
          class="form-control" 
          rows="3"
        ></textarea>
      </div>
      
      <div class="form-group">
        <label>Загрузка</label>
        <input 
          :value="form.loading_point" 
          @input="updateForm('loading_point', $event.target.value)"
          type="text" 
          class="form-control"
        >
      </div>
      
      <div class="form-group">
        <label>Выгрузка</label>
        <input 
          :value="form.unloading_point" 
          @input="updateForm('unloading_point', $event.target.value)"
          type="text" 
          class="form-control"
        >
      </div>
    </div>
    
    <div class="form-actions">
      <button type="button" @click="$emit('close')" class="btn-secondary">Отмена</button>
      <button type="submit" class="btn-primary" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  orderId: {
    type: Number,
    default: null
  },
  initialData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['saved', 'close']);

const saving = ref(false);
const form = reactive({
  id: null,
  order_number: '',
  company_code: '',
  order_date: new Date().toISOString().slice(0, 10),
  customer_name: '',
  carrier_name: '',
  customer_rate: null,
  carrier_rate: null,
  cargo_description: '',
  loading_point: '',
  unloading_point: ''
});

const updateForm = (field, value) => {
  form[field] = value;
};

// Загрузка данных если редактируем
const loadOrder = async () => {
  if (props.orderId) {
    try {
      const response = await axios.get(`/api/orders/${props.orderId}`);
      Object.assign(form, response.data);
    } catch (error) {
      console.error('Failed to load order:', error);
      alert('Ошибка загрузки заказа');
    }
  }
};

// Сохранение
const save = async () => {
  saving.value = true;
  
  try {
    let response;
    if (form.id) {
      response = await axios.put(`/api/orders/${form.id}`, form);
    } else {
      response = await axios.post('/api/orders', form);
    }
    
    if (response.data) {
      emit('saved', response.data);
    }
  } catch (error) {
    console.error('Failed to save order:', error);
    alert(error.response?.data?.message || 'Ошибка сохранения');
  } finally {
    saving.value = false;
  }
};

// Инициализация
if (props.initialData) {
  Object.assign(form, props.initialData);
} else {
  loadOrder();
}
</script>

<style scoped>
.order-form {
  padding: 20px 0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-size: 13px;
  font-weight: 500;
  color: #666;
}

.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #c17b4b;
  box-shadow: 0 0 0 2px rgba(193, 123, 75, 0.1);
}

.form-actions {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-primary {
  background: #c17b4b;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #b06a3d;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #5a6268;
  transform: translateY(-1px);
}
</style>
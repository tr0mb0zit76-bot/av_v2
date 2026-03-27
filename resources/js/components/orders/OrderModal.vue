<template>
  <Modal :show="show" :title="modalTitle" size="large" @close="close">
    <!-- Вкладки -->
    <div class="modal-tabs">
      <button 
        :class="{ active: activeTab === 'main' }" 
        @click="activeTab = 'main'"
      >
        📋 Основное
      </button>
      <button 
        :class="{ active: activeTab === 'route' }" 
        @click="activeTab = 'route'"
      >
        📍 Маршрут и груз
      </button>
      <button 
        :class="{ active: activeTab === 'finance' }" 
        @click="activeTab = 'finance'"
      >
        💰 Финансы
      </button>
      <button 
        :class="{ active: activeTab === 'documents' }" 
        @click="activeTab = 'documents'"
      >
        📄 Документы
      </button>
    </div>

    <!-- Контент вкладок -->
    <div class="tab-content">
      <!-- Вкладка 1: Основное -->
      <div v-show="activeTab === 'main'" class="tab-pane">
        <div class="form-grid">
          <div class="form-group">
            <label>Номер заказа</label>
            <input v-model="form.order_number" type="text" class="form-control" readonly>
          </div>
          
          <div class="form-group">
            <label>Наша компания *</label>
            <select v-model="form.company_code" class="form-control" required>
              <option value="">-- Выберите --</option>
              <option value="ЛР">ЛР (Логистические Решения)</option>
              <option value="АП">АП (Автопартнер)</option>
              <option value="КВ">КВ (КВ)</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Дата заявки *</label>
            <input v-model="form.order_date" type="date" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label>Менеджер</label>
            <input :value="form.manager_name" type="text" class="form-control" readonly disabled>
          </div>
        </div>
        
        <!-- Контрагенты -->
        <h4>Контрагенты</h4>
        <div class="form-grid">
          <div class="form-group">
            <label>Заказчик</label>
            <input v-model="form.customer_name" type="text" class="form-control">
          </div>
          
          <div class="form-group">
            <label>Перевозчик</label>
            <input v-model="form.carrier_name" type="text" class="form-control">
          </div>
          
          <div class="form-group">
            <label>Водитель</label>
            <input v-model="form.driver_name" type="text" class="form-control">
          </div>
        </div>
      </div>

      <!-- Вкладка 2: Маршрут и груз -->
      <div v-show="activeTab === 'route'" class="tab-pane">
        <RouteBuilder v-model="form.legs" />
      </div>

      <!-- Вкладка 3: Финансы -->
      <div v-show="activeTab === 'finance'" class="tab-pane">
        <div class="finance-sections">
          <!-- Блок заказчика -->
          <div class="finance-section">
            <h4 class="section-title">Заказчик</h4>
            <div class="section-form-grid">
              <div class="form-group">
                <label>Ставка заказчика</label>
                <input v-model.number="form.customer_rate" type="number" step="0.01" class="form-control">
              </div>
              
              <div class="form-group">
                <label>Форма оплаты (заказчик)</label>
                <select v-model="form.customer_payment_form" class="form-control">
                  <option value="">-- Выберите --</option>
                  <option value="с НДС">с НДС</option>
                  <option value="без НДС">без НДС</option>
                  <option value="нал">нал</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Условия оплаты (заказчик)</label>
                <input v-model="form.customer_payment_term" type="text" class="form-control" placeholder="например: 5 бд по ОТТН">
              </div>
            </div>
          </div>

          <!-- Блок перевозчика -->
          <div class="finance-section">
            <h4 class="section-title">Перевозчик</h4>
            <div class="section-form-grid">
              <div class="form-group">
                <label>Ставка перевозчика</label>
                <input v-model.number="form.carrier_rate" type="number" step="0.01" class="form-control">
              </div>
              
              <div class="form-group">
                <label>Форма оплаты (перевозчик)</label>
                <select v-model="form.carrier_payment_form" class="form-control">
                  <option value="">-- Выберите --</option>
                  <option value="с НДС">с НДС</option>
                  <option value="без НДС">без НДС</option>
                  <option value="нал">нал</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Условия оплаты (перевозчик)</label>
                <input v-model="form.carrier_payment_term" type="text" class="form-control">
              </div>
            </div>
          </div>
        </div>

        <!-- Общие расходы -->
        <div class="common-expenses">
          <h4 class="section-title">Дополнительно</h4>
          <div class="section-form-grid section-form-grid-3">
            <div class="form-group">
              <label>Доп. расходы</label>
              <input v-model.number="form.additional_expenses" type="number" step="0.01" class="form-control">
            </div>
            
            <div class="form-group">
              <label>Страховка</label>
              <input v-model.number="form.insurance" type="number" step="0.01" class="form-control">
            </div>
            
            <div class="form-group">
              <label>Бонус</label>
              <input v-model.number="form.bonus" type="number" step="0.01" class="form-control">
            </div>
          </div>
        </div>
      </div>

      <!-- Вкладка 4: Документы -->
      <div v-show="activeTab === 'documents'" class="tab-pane">
        <!-- Документы заказа -->
        <div class="documents-section">
          <h4>📄 Документы заказа</h4>
          <div class="documents-grid">
            <!-- Трек-номера -->
            <div class="doc-card">
              <div class="doc-card-header">
                <span class="doc-icon">📮</span>
                <span class="doc-title">Трек-номера</span>
              </div>
              <div class="doc-card-body">
                <div class="doc-field">
                  <label>Заказчик</label>
                  <input v-model="form.track_number_customer" type="text" placeholder="Введите трек-номер" class="form-control">
                </div>
                <div class="doc-field">
                  <label>Перевозчик</label>
                  <input v-model="form.track_number_carrier" type="text" placeholder="Введите трек-номер" class="form-control">
                </div>
              </div>
            </div>

            <!-- Счет и УПД -->
            <div class="doc-card">
              <div class="doc-card-header">
                <span class="doc-icon">💰</span>
                <span class="doc-title">Финансовые документы</span>
              </div>
              <div class="doc-card-body">
                <div class="doc-field">
                  <label>№ счёта</label>
                  <input v-model="form.invoice_number" type="text" placeholder="Номер счета" class="form-control">
                </div>
                <div class="doc-field">
                  <label>№ УПД</label>
                  <input v-model="form.upd_number" type="text" placeholder="Номер УПД" class="form-control">
                </div>
                <div class="doc-field">
                  <label>Товарная накладная (ТН)</label>
                  <input v-model="form.waybill_number" type="text" placeholder="Номер ТН" class="form-control">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Загрузка файлов -->
        <div class="files-section">
          <div class="files-header">
            <h4>📎 Подписанные документы</h4>
            <button 
              class="btn-upload" 
              @click="triggerFileUpload"
              :disabled="!form.id"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 3v12m0 0-3-3m3 3 3-3M5 21h14"/>
              </svg>
              Загрузить файл
            </button>
            <input 
              ref="fileInput" 
              type="file" 
              @change="uploadDocument" 
              accept=".pdf,.doc,.docx,.jpg,.png" 
              style="display: none"
            >
          </div>
          
          <div v-if="documents.length === 0" class="empty-files">
            <div class="empty-icon">📭</div>
            <p>Нет загруженных документов</p>
            <span>Нажмите "Загрузить файл", чтобы добавить документ</span>
          </div>
          
          <div v-else class="files-list">
            <div v-for="doc in documents" :key="doc.id" class="file-card">
              <div class="file-icon" :class="getFileIconClass(doc.original_name)">
                {{ getFileIcon(doc.original_name) }}
              </div>
              <div class="file-info">
                <div class="file-name" :title="doc.original_name">
                  {{ truncateFileName(doc.original_name) }}
                </div>
                <div class="file-meta">
                  <span class="file-size">{{ formatFileSize(doc.file_size) }}</span>
                  <span class="file-date">{{ formatDate(doc.created_at) }}</span>
                </div>
              </div>
              <div class="file-actions">
                <button @click="downloadDocument(doc.id)" class="file-action-btn download" title="Скачать">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v12m0 0-3-3m3 3 3-3M5 21h14"/>
                  </svg>
                </button>
                <button @click="deleteDocument(doc.id)" class="file-action-btn delete" title="Удалить">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <div class="upload-hint" v-if="!form.id">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 8v4M12 16h.01"/>
            </svg>
            <span>Сначала сохраните заказ, чтобы загружать документы</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Кнопки -->
    <template #footer>
      <button @click="close" class="btn-secondary">Отмена</button>
      <button @click="save" class="btn-primary" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import Modal from '@/components/common/Modal.vue'
import { useAuthStore } from '@/stores/auth'
import RouteBuilder from './RouteBuilder.vue'

const props = defineProps({
  show: Boolean,
  orderId: Number
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()

// Состояние
const saving = ref(false)
const activeTab = ref('main')
const documents = ref([])
const fileInput = ref(null)

// Форма
const form = reactive({
  id: null,
  order_number: '',
  company_code: '',
  order_date: '',
  manager_name: '',
  manager_id: null,
  
  // Контрагенты
  customer_id: null,
  customer_name: '',
  carrier_id: null,
  carrier_name: '',
  driver_id: null,
  driver_name: '',
  
  // Маршрут
  legs: [],
  
  // Финансы
  customer_rate: null,
  customer_payment_form: '',
  customer_payment_term: '',
  carrier_rate: null,
  carrier_payment_form: '',
  carrier_payment_term: '',
  additional_expenses: 0,
  insurance: 0,
  bonus: 0,
  
  // Документы
  track_number_customer: '',
  track_number_carrier: '',
  invoice_number: '',
  upd_number: '',
  waybill_number: '',
})

// Сброс формы
const resetForm = () => {
  Object.assign(form, {
    id: null,
    order_number: '',
    company_code: '',
    order_date: new Date().toISOString().split('T')[0],
    manager_name: authStore.user?.name || '',
    manager_id: authStore.user?.id,
    customer_id: null,
    customer_name: '',
    carrier_id: null,
    carrier_name: '',
    driver_id: null,
    driver_name: '',
    legs: [],
    customer_rate: null,
    customer_payment_form: '',
    customer_payment_term: '',
    carrier_rate: null,
    carrier_payment_form: '',
    carrier_payment_term: '',
    additional_expenses: 0,
    insurance: 0,
    bonus: 0,
    track_number_customer: '',
    track_number_carrier: '',
    invoice_number: '',
    upd_number: '',
    waybill_number: '',
  })
  documents.value = []
  activeTab.value = 'main'
}

// Вспомогательные функции для файлов
const getFileIcon = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = {
    pdf: '📄',
    doc: '📝',
    docx: '📝',
    jpg: '🖼️',
    jpeg: '🖼️',
    png: '🖼️'
  }
  return icons[ext] || '📎'
}

const getFileIconClass = (filename) => {
  const ext = filename.split('.').pop().toLowerCase()
  const classes = {
    pdf: 'pdf',
    doc: 'word',
    docx: 'word',
    jpg: 'image',
    jpeg: 'image',
    png: 'image'
  }
  return classes[ext] || 'default'
}

const truncateFileName = (name) => {
  if (name.length > 40) {
    return name.substring(0, 37) + '...'
  }
  return name
}

const formatFileSize = (bytes) => {
  if (!bytes) return '—'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('ru-RU')
}

const triggerFileUpload = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

// Загрузка документов
const loadDocuments = async () => {
  if (!form.id) return
  
  try {
    const response = await axios.get(`/api/orders/${form.id}/documents`)
    documents.value = response.data || []
  } catch (error) {
    if (error.response?.status !== 500) {
      console.error('Ошибка загрузки документов:', error)
    }
    documents.value = []
  }
}

// Загрузка данных заказа
const loadOrder = async () => {
  if (!props.orderId) {
    resetForm()
    return
  }
  
  try {
    const response = await axios.get(`/api/orders/${props.orderId}`)
    const data = response.data
    
    // Форматируем даты
    const formatDate = (date) => {
      if (!date) return ''
      if (typeof date === 'string' && date.includes('T')) {
        return date.split('T')[0]
      }
      return date
    }
    
    // Функция для извлечения чистого названия города
    const extractCityName = (fullString) => {
      if (!fullString) return ''
      
      let city = fullString
      
      if (city.includes(',')) {
        const parts = city.split(',')
        for (let i = parts.length - 1; i >= 0; i--) {
          let part = parts[i].trim()
          
          part = part.replace(/^г\.?\s*/i, '')
          part = part.replace(/^город\s*/i, '')
          part = part.replace(/^пос\.?\s*/i, '')
          part = part.replace(/^поселок\s*/i, '')
          part = part.replace(/^с\.?\s*/i, '')
          part = part.replace(/^село\s*/i, '')
          part = part.replace(/^д\.?\s*/i, '')
          part = part.replace(/^деревня\s*/i, '')
          part = part.replace(/^пгт\.?\s*/i, '')
          
          if (part && !part.match(/область|район|край|республика/i)) {
            city = part
            break
          }
        }
      } else {
        city = city.replace(/^г\.?\s*/i, '')
        city = city.replace(/^город\s*/i, '')
        city = city.replace(/^пос\.?\s*/i, '')
        city = city.replace(/^поселок\s*/i, '')
        city = city.replace(/^с\.?\s*/i, '')
        city = city.replace(/^село\s*/i, '')
        city = city.replace(/^д\.?\s*/i, '')
        city = city.replace(/^деревня\s*/i, '')
        city = city.replace(/^пгт\.?\s*/i, '')
      }
      
      return city.trim()
    }
    
    // Функция для определения, является ли строка адресом или городом
    const isAddress = (str) => {
      if (!str) return false
      // Если есть номер дома, улица, проспект и т.д.
      return /ул\.|пр-кт|проспект|пер\.|переулок|ш\.|шоссе|бульвар|наб\.|набережная|д\.|дом/i.test(str)
    }
    
    // Очищаем и разделяем данные в точках маршрута
    if (data.legs && data.legs.length > 0) {
      data.legs.forEach(leg => {
        if (leg.points) {
          leg.points.forEach(point => {
            // Очищаем дату
            if (point.planned_date) {
              point.planned_date = formatDate(point.planned_date)
            }
            
            // Если city заполнен, но похож на адрес - перемещаем в address
            if (point.city && isAddress(point.city)) {
              if (!point.address) {
                point.address = point.city
              }
              point.city = ''
            }
            
            // Если address пуст, но city похож на адрес - исправляем
            if (!point.address && point.city && isAddress(point.city)) {
              point.address = point.city
              point.city = ''
            }
            
            // Если city заполнен, очищаем его от лишних деталей
            if (point.city) {
              point.city = extractCityName(point.city)
            }
            
            // Если address содержит город, извлекаем город и очищаем address
            if (point.address && !point.city && point.address.includes(',')) {
              const parts = point.address.split(',')
              if (parts.length > 1) {
                const possibleCity = extractCityName(parts[0])
                if (possibleCity && !isAddress(parts[0])) {
                  point.city = possibleCity
                  point.address = parts.slice(1).join(',').trim()
                }
              }
            }
          })
        }
      })
    }
    
    Object.assign(form, {
      id: data.id,
      order_number: data.order_number,
      company_code: data.company_code,
      order_date: formatDate(data.order_date),
      manager_name: data.manager_name || authStore.user?.name,
      manager_id: data.manager_id,
      customer_id: data.customer_id,
      customer_name: data.customer_name,
      carrier_id: data.carrier_id,
      carrier_name: data.carrier_name,
      driver_id: data.driver_id,
      driver_name: data.driver_name,
      legs: data.legs || [],
      customer_rate: data.customer_rate,
      customer_payment_form: data.customer_payment_form,
      customer_payment_term: data.customer_payment_term,
      carrier_rate: data.carrier_rate,
      carrier_payment_form: data.carrier_payment_form,
      carrier_payment_term: data.carrier_payment_term,
      additional_expenses: data.additional_expenses || 0,
      insurance: data.insurance || 0,
      bonus: data.bonus || 0,
      track_number_customer: data.track_number_customer,
      track_number_carrier: data.track_number_carrier,
      invoice_number: data.invoice_number,
      upd_number: data.upd_number,
      waybill_number: data.waybill_number,
    })
    
    // Временно закомментировано из-за отсутствия таблицы
    // await loadDocuments()
    
  } catch (error) {
    console.error('Ошибка загрузки заказа:', error)
    alert('Ошибка загрузки заказа')
  }
}

// Загрузка файла
const uploadDocument = async (event) => {
  const file = event.target.files[0]
  if (!file || !form.id) return
  
  const formData = new FormData()
  formData.append('file', file)
  formData.append('type', 'other')
  
  try {
    const response = await axios.post(`/api/orders/${form.id}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    documents.value.unshift(response.data)
    event.target.value = ''
  } catch (error) {
    console.error('Ошибка загрузки файла:', error)
    alert('Ошибка загрузки файла')
  }
}

// Скачивание файла
const downloadDocument = async (documentId) => {
  try {
    const response = await axios.get(`/api/orders/documents/${documentId}/download`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const contentDisposition = response.headers['content-disposition']
    let filename = 'document'
    if (contentDisposition) {
      const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (match && match[1]) {
        filename = match[1].replace(/['"]/g, '')
      }
    }
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Ошибка скачивания файла:', error)
    alert('Ошибка скачивания файла')
  }
}

// Удаление файла
const deleteDocument = async (documentId) => {
  if (!confirm('Удалить файл?')) return
  
  try {
    await axios.delete(`/api/orders/documents/${documentId}`)
    documents.value = documents.value.filter(doc => doc.id !== documentId)
  } catch (error) {
    console.error('Ошибка удаления файла:', error)
    alert('Ошибка удаления файла')
  }
}

// Сохранение
const save = async () => {
  saving.value = true
  
  try {
    const url = form.id ? `/api/orders/${form.id}` : '/api/orders'
    const method = form.id ? 'put' : 'post'
    
    const response = await axios[method](url, form)
    
    if (response.data) {
      emit('saved', response.data)
      emit('close')
    }
  } catch (error) {
    console.error('Ошибка сохранения:', error)
    alert(error.response?.data?.message || 'Ошибка сохранения')
  } finally {
    saving.value = false
  }
}

const close = () => {
  emit('close')
}

const modalTitle = computed(() => {
  return form.id ? `Редактирование заказа #${form.order_number}` : 'Новый заказ'
})

// Следим за изменениями show и orderId
watch(() => props.show, (newShow) => {
  if (newShow) {
    if (props.orderId) {
      loadOrder()
    } else {
      resetForm()
    }
  }
})

watch(() => props.orderId, (newId) => {
  if (props.show) {
    if (newId) {
      loadOrder()
    } else {
      resetForm()
    }
  }
})

onMounted(() => {
  if (props.show && props.orderId) {
    loadOrder()
  } else if (props.show && !props.orderId) {
    resetForm()
  }
})
</script>

<style scoped>
.modal-tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid #e9ecef;
  padding: 0 24px;
  background: #f8f9fa;
}

.modal-tabs button {
  padding: 12px 20px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #6c757d;
  transition: all 0.2s;
}

.modal-tabs button:hover {
  color: #c17b4b;
}

.modal-tabs button.active {
  color: #c17b4b;
  border-bottom: 2px solid #c17b4b;
  margin-bottom: -1px;
}

.tab-content {
  padding: 24px;
  min-height: 400px;
  max-height: 60vh;
  overflow-y: auto;
}

/* Общие стили для форм */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #495057;
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

.form-control:read-only,
.form-control:disabled {
  background: #e9ecef;
  cursor: not-allowed;
}

h4 {
  margin: 20px 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.btn-primary {
  background: #c17b4b;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary:hover:not(:disabled) {
  background: #b06a3d;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  cursor: pointer;
}

/* Стили для вкладки документов */
.documents-section {
  margin-bottom: 32px;
}

.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 20px;
  margin-top: 16px;
}

.doc-card {
  background: #f8f9fa;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e9ecef;
}

.doc-card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: white;
  border-bottom: 1px solid #e9ecef;
}

.doc-icon {
  font-size: 20px;
}

.doc-title {
  font-weight: 600;
  font-size: 14px;
  color: #495057;
}

.doc-card-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.doc-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.doc-field label {
  font-size: 12px;
  font-weight: 500;
  color: #6c757d;
}

/* Стили для файлов */
.files-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e9ecef;
}

.files-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.files-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.btn-upload {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #c17b4b 0%, #b06a3d 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-upload:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(193, 123, 75, 0.3);
}

.btn-upload:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-files {
  text-align: center;
  padding: 48px 20px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 2px dashed #dee2e6;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-files p {
  font-size: 14px;
  font-weight: 500;
  color: #6c757d;
  margin: 0 0 8px 0;
}

.empty-files span {
  font-size: 12px;
  color: #adb5bd;
}

.files-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
}

.file-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  transition: all 0.2s;
}

.file-card:hover {
  background: #f8f9fa;
  border-color: #c17b4b;
  transform: translateX(4px);
}

.file-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-size: 20px;
  flex-shrink: 0;
}

.file-icon.pdf {
  background: #ffebee;
}

.file-icon.word {
  background: #e3f2fd;
}

.file-icon.image {
  background: #e8f5e9;
}

.file-icon.default {
  background: #f3e5f5;
}

.file-info {
  flex: 1;
  min-width: 0;
}

.file-name {
  font-size: 13px;
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-meta {
  display: flex;
  gap: 12px;
  font-size: 11px;
  color: #6c757d;
}

.file-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.file-action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.file-action-btn.download {
  color: #c17b4b;
}

.file-action-btn.download:hover {
  background: #fff3e0;
  color: #b06a3d;
}

.file-action-btn.delete {
  color: #dc3545;
}

.file-action-btn.delete:hover {
  background: #ffebee;
  color: #c62828;
}

.upload-hint {
  margin-top: 16px;
  padding: 12px;
  background: #fff3cd;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #856404;
}

/* Стили для финансовой вкладки */
.finance-sections {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}

.finance-section {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.section-title {
  margin: 0 0 14px 0;
  font-size: 16px;
  font-weight: 600;
  color: #495057;
  padding-bottom: 8px;
  border-bottom: 2px solid #dee2e6;
}

.section-form-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-form-grid-3 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.common-expenses {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  margin-top: 0;
}

/* Адаптация для мобильных устройств */
@media (max-width: 768px) {
  .finance-sections {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .finance-section,
  .common-expenses {
    padding: 16px;
  }
  
  .section-title {
    font-size: 15px;
    margin-bottom: 12px;
  }
  
  .section-form-grid {
    gap: 10px;
  }
  
  .section-form-grid-3 {
    grid-template-columns: 1fr;
    gap: 10px;
  }
}
</style>
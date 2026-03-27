<template>
  <div class="route-point-card" :class="[point.type, { collapsed: isCollapsed }]">
    <div class="card-header">
      <div class="drag-handle" title="Перетащить для изменения порядка">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="12" r="1.5" />
          <circle cx="15" cy="12" r="1.5" />
          <circle cx="9" cy="16" r="1.5" />
          <circle cx="15" cy="16" r="1.5" />
          <circle cx="9" cy="8" r="1.5" />
          <circle cx="15" cy="8" r="1.5" />
        </svg>
      </div>
      
      <div class="point-badge" :class="point.type">
        <span class="point-number">{{ index + 1 }}</span>
        <span class="point-type">{{ typeLabel }}</span>
      </div>
      
      <div class="point-location" v-if="!isCollapsed && point.city">
        <span class="location-icon">📍</span>
        <span class="location-text">{{ point.city }}</span>
      </div>
      
      <button class="collapse-btn" @click="isCollapsed = !isCollapsed">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path v-if="isCollapsed" d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round"/>
          <path v-else d="M6 9l6 6 6-6" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      
      <button class="remove-btn" @click="$emit('remove', point.id)" title="Удалить точку">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <div class="card-content" :class="{ hidden: isCollapsed }">
      <div class="form-row">
        <div class="form-field full-width">
          <label>Город</label>
          <div class="dadata-wrapper">
            <input 
              ref="cityInput"
              v-model="point.city" 
              type="text" 
              placeholder="Начните вводить город..."
              class="form-input dadata-input"
              autocomplete="off"
              @input="onCityInput"
              @focus="onCityFocus"
              @blur="onCityBlur"
            />
            <div v-if="citySuggestions.length > 0 && showCitySuggestions" class="dadata-suggestions">
              <div
                v-for="(suggestion, idx) in citySuggestions"
                :key="idx"
                class="suggestion-item"
                @click="selectCity(suggestion)"
              >
                <span class="suggestion-icon">🏙️</span>
                <span class="suggestion-text">{{ suggestion.value }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-field full-width">
          <label>Адрес</label>
          <div class="dadata-wrapper">
            <input 
              ref="addressInput"
              v-model="point.address" 
              type="text" 
              placeholder="Улица, дом, строение"
              class="form-input dadata-input"
              :class="{ 'error-input': showAddressError }"
              autocomplete="off"
              @input="onAddressInput"
              @focus="onAddressFocus"
              @blur="onAddressBlur"
            />
            <div v-if="isLoadingAddress" class="loading-spinner">
              ⏳
            </div>
            <div v-if="addressSuggestions.length > 0 && showAddressSuggestions" class="dadata-suggestions">
              <div
                v-for="(suggestion, idx) in addressSuggestions"
                :key="idx"
                class="suggestion-item"
                @click="selectAddress(suggestion)"
              >
                <span class="suggestion-icon">📍</span>
                <span class="suggestion-text">{{ suggestion.value }}</span>
              </div>
            </div>
            <div v-if="showAddressError" class="address-error">
              <small>⚠️ Выберите город перед вводом адреса</small>
            </div>
            <div v-if="point.city && addressSuggestions.length === 0 && showAddressSuggestions && !isLoadingAddress && addressSearchQuery && addressSearchQuery.length > 2" class="address-hint">
              <small>⚠️ Не найдено улиц в городе "{{ getCityNameForSearch() }}"</small>
            </div>
          </div>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-field">
          <label>Дата</label>
          <input 
            v-model="point.planned_date" 
            type="date" 
            class="form-input date-input"
          />
        </div>
        
        <div class="form-field">
          <label>Время с</label>
          <input 
            v-model="point.planned_time_from" 
            type="time" 
            class="form-input time-input"
          />
        </div>
        
        <div class="form-field">
          <label>Время до</label>
          <input 
            v-model="point.planned_time_to" 
            type="time" 
            class="form-input time-input"
          />
        </div>
      </div>
      
      <div class="form-row" v-if="point.type !== 'transit'">
        <div class="form-field full-width">
          <label>Контактное лицо</label>
          <input 
            v-model="point.contact_person" 
            type="text" 
            placeholder="ФИО контактного лица"
            class="form-input"
          />
        </div>
      </div>
      
      <div class="form-row" v-if="point.type !== 'transit'">
        <div class="form-field full-width">
          <label>Телефон</label>
          <input 
            v-model="point.contact_phone" 
            type="tel" 
            placeholder="+7 (___) ___-__-__"
            class="form-input"
          />
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-field full-width">
          <label>Инструкции / Примечания</label>
          <textarea 
            v-model="point.instructions" 
            rows="2" 
            placeholder="Особые указания, пропуска, документы..."
            class="form-textarea"
          ></textarea>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  point: { type: Object, required: true },
  index: { type: Number, required: true },
  isFirst: { type: Boolean, default: false },
  isLast: { type: Boolean, default: false }
})

const emit = defineEmits(['update', 'remove'])

const isCollapsed = ref(false)
const isLoadingAddress = ref(false)
const addressSearchQuery = ref('')

const citySuggestions = ref([])
const addressSuggestions = ref([])
const showCitySuggestions = ref(false)
const showAddressSuggestions = ref(false)

let citySearchTimeout = null
let addressSearchTimeout = null

const typeLabel = computed(() => {
  const labels = {
    loading: 'Погрузка',
    transit: 'Транзит',
    unloading: 'Выгрузка'
  }
  return labels[props.point.type] || props.point.type
})

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

// Функция для извлечения только адреса (без города)
const extractAddressOnly = (fullAddress, cityName) => {
  if (!fullAddress) return ''
  
  let address = fullAddress
  
  // Если город известен, убираем его из адреса
  if (cityName) {
    // Убираем "г. Москва", "Москва,", "г. Москва," и т.д.
    const patterns = [
      new RegExp(`^г\\.?\\s*${cityName}\\s*,?\\s*`, 'i'),
      new RegExp(`^${cityName}\\s*,?\\s*`, 'i'),
      new RegExp(`,?\\s*${cityName}\\s*,?`, 'i'),
      new RegExp(`^город\\s*${cityName}\\s*,?\\s*`, 'i')
    ]
    
    for (const pattern of patterns) {
      address = address.replace(pattern, '')
    }
  }
  
  // Убираем "Самарская обл.," и подобные
  address = address.replace(/^[^,]*область\s*,?\s*/i, '')
  address = address.replace(/^[^,]*район\s*,?\s*/i, '')
  address = address.replace(/^[^,]*край\s*,?\s*/i, '')
  
  return address.trim()
}

// Функция для определения, является ли строка адресом
const isAddressString = (str) => {
  if (!str) return false
  return /ул\.|пр-кт|проспект|пер\.|переулок|ш\.|шоссе|бульвар|наб\.|набережная|д\.|дом/i.test(str)
}

// Функция для получения названия города для поиска
const getCityNameForSearch = () => {
  if (!props.point.city) return ''
  return extractCityName(props.point.city)
}

// Показывать ошибку, если вводят адрес без города
const showAddressError = computed(() => {
  return addressSearchQuery.value && addressSearchQuery.value.length > 2 && 
         !props.point.city && !isLoadingAddress.value
})

// Поиск городов
const searchCity = async (query) => {
  if (!query || query.length < 2) {
    citySuggestions.value = []
    showCitySuggestions.value = false
    return
  }

  try {
    const response = await axios.post('/api/suggest', {
      query: query,
      count: 10,
      type: 'city'
    })

    citySuggestions.value = response.data.suggestions || []
    showCitySuggestions.value = true
  } catch (error) {
    console.error('Ошибка поиска города:', error)
    citySuggestions.value = []
  }
}

// Поиск адресов с учетом города
const searchAddress = async (query) => {
  addressSearchQuery.value = query
  
  if (!query || query.length < 2) {
    addressSuggestions.value = []
    showAddressSuggestions.value = false
    return
  }

  isLoadingAddress.value = true
  
  try {
    const payload = {
      query: query,
      count: 10
    }
    
    const cityForSearch = getCityNameForSearch()
    if (cityForSearch && cityForSearch.length > 2) {
      payload.city = cityForSearch
    }
    
    const response = await axios.post('/api/suggest', payload)
    
    addressSuggestions.value = response.data.suggestions || []
    showAddressSuggestions.value = true
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    addressSuggestions.value = []
  } finally {
    isLoadingAddress.value = false
  }
}

// Обработчики для города
const onCityInput = (event) => {
  const value = event.target.value
  if (citySearchTimeout) clearTimeout(citySearchTimeout)
  citySearchTimeout = setTimeout(() => searchCity(value), 300)
}

const onCityFocus = () => {
  if (citySuggestions.value.length > 0) {
    showCitySuggestions.value = true
  }
}

const onCityBlur = () => {
  setTimeout(() => {
    showCitySuggestions.value = false
    if (props.point.city && (props.point.city.includes(',') || props.point.city.match(/область|район|край|республика/i))) {
      const cleaned = extractCityName(props.point.city)
      if (cleaned && cleaned !== props.point.city) {
        props.point.city = cleaned
        emit('update', { ...props.point })
      }
    }
  }, 200)
}

const selectCity = (suggestion) => {
  const cityName = extractCityName(suggestion.value)
  props.point.city = cityName
  emit('update', { ...props.point })
  showCitySuggestions.value = false
  if (props.point.address) {
    props.point.address = ''
    emit('update', { ...props.point })
  }
}

// Обработчики для адреса
const onAddressInput = (event) => {
  const value = event.target.value
  if (addressSearchTimeout) clearTimeout(addressSearchTimeout)
  addressSearchTimeout = setTimeout(() => searchAddress(value), 300)
}

const onAddressFocus = () => {
  if (addressSuggestions.value.length > 0) {
    showAddressSuggestions.value = true
  }
}

const onAddressBlur = () => {
  setTimeout(() => {
    showAddressSuggestions.value = false
  }, 200)
}

const selectAddress = (suggestion) => {
  // Извлекаем только адрес (без города)
  let addressOnly = extractAddressOnly(suggestion.value, props.point.city)
  
  // Если после очистки адрес пустой, берем полную подсказку
  if (!addressOnly && suggestion.value) {
    addressOnly = suggestion.value
  }
  
  props.point.address = addressOnly
  
  // Если город не заполнен, извлекаем его из подсказки
  if (!props.point.city && suggestion.data?.city) {
    props.point.city = extractCityName(suggestion.data.city)
  }
  
  emit('update', { ...props.point })
  showAddressSuggestions.value = false
  addressSearchQuery.value = ''
}

// Исправление данных при загрузке (для старых заказов)
const fixPointData = () => {
  let needUpdate = false
  const newPoint = { ...props.point }
  
  // Если адрес содержит город (например "Москва, ул. Тверская")
  if (newPoint.address && !newPoint.city) {
    // Проверяем, начинается ли адрес с названия города
    const parts = newPoint.address.split(',')
    if (parts.length > 1) {
      const firstPart = parts[0].trim()
      const possibleCity = extractCityName(firstPart)
      // Если первая часть похожа на город (не содержит признаков адреса)
      if (possibleCity && !isAddressString(firstPart) && firstPart.length > 2) {
        newPoint.city = possibleCity
        newPoint.address = parts.slice(1).join(',').trim()
        needUpdate = true
      }
    }
  }
  
  // Если город заполнен, но похож на адрес - перемещаем в address
  if (newPoint.city && isAddressString(newPoint.city)) {
    if (!newPoint.address) {
      newPoint.address = newPoint.city
    } else {
      newPoint.address = newPoint.city + ', ' + newPoint.address
    }
    newPoint.city = ''
    needUpdate = true
  }
  
  // Если город заполнен, очищаем от лишних деталей
  if (newPoint.city && (newPoint.city.includes(',') || newPoint.city.match(/область|район|край|республика/i))) {
    const cleaned = extractCityName(newPoint.city)
    if (cleaned && cleaned !== newPoint.city) {
      newPoint.city = cleaned
      needUpdate = true
    }
  }
  
  if (needUpdate) {
    emit('update', newPoint)
  }
}

// Следим за изменением города и очищаем адрес если нужно
watch(() => props.point.city, (newCity, oldCity) => {
  // Если город изменился и был адрес, очищаем адрес
  if (newCity !== oldCity && oldCity && props.point.address) {
    props.point.address = ''
    emit('update', { ...props.point })
  }
})

// Вызываем при монтировании
onMounted(() => {
  fixPointData()
})
</script>

<style scoped>
/* Все стили остаются без изменений */
.route-point-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  overflow: hidden;
  border-left: 4px solid;
}

.route-point-card.loading {
  border-left-color: #4caf50;
}

.route-point-card.transit {
  border-left-color: #ff9800;
}

.route-point-card.unloading {
  border-left-color: #2196f3;
}

.route-point-card.collapsed {
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #fafbfc;
  cursor: default;
}

.drag-handle {
  cursor: grab;
  color: #adb5bd;
  display: flex;
  align-items: center;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.drag-handle:hover {
  background: #e9ecef;
  color: #6c757d;
}

.drag-handle:active {
  cursor: grabbing;
}

.point-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.point-badge.loading {
  background: #e8f5e9;
  color: #2e7d32;
}

.point-badge.transit {
  background: #fff3e0;
  color: #ed6c02;
}

.point-badge.unloading {
  background: #e3f2fd;
  color: #1565c0;
}

.point-number {
  background: rgba(0, 0, 0, 0.1);
  padding: 2px 6px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.point-location {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #495057;
}

.location-icon {
  font-size: 14px;
}

.location-text {
  font-weight: 500;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.collapse-btn,
.remove-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.collapse-btn {
  color: #6c757d;
}

.collapse-btn:hover {
  background: #e9ecef;
  color: #495057;
}

.remove-btn {
  color: #dc3545;
}

.remove-btn:hover {
  background: #ffebee;
  color: #c62828;
}

.card-content {
  padding: 16px;
  border-top: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.card-content.hidden {
  display: none;
}

.form-row {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-field.full-width {
  flex: 100%;
}

.form-field label {
  font-size: 12px;
  font-weight: 500;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input,
.form-textarea {
  padding: 10px 12px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #c17b4b;
  box-shadow: 0 0 0 3px rgba(193, 123, 75, 0.1);
}

.form-input:hover,
.form-textarea:hover {
  border-color: #adb5bd;
}

.date-input,
.time-input {
  font-family: monospace;
}

.form-textarea {
  resize: vertical;
  min-height: 60px;
}

.dadata-wrapper {
  position: relative;
  width: 100%;
}

.dadata-input {
  width: 100%;
}

.loading-spinner {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
  opacity: 0.6;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}

.address-hint {
  position: absolute;
  bottom: -20px;
  left: 0;
  right: 0;
  font-size: 11px;
  color: #f59e0b;
  background: #fffbeb;
  padding: 4px 8px;
  border-radius: 4px;
  margin-top: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.address-error {
  position: absolute;
  bottom: -20px;
  left: 0;
  right: 0;
  font-size: 11px;
  color: #dc3545;
  background: #fff8f8;
  padding: 4px 8px;
  border-radius: 4px;
  margin-top: 4px;
}

.error-input {
  border-color: #dc3545 !important;
  background-color: #fff8f8;
}

.dadata-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 4px;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.suggestion-item:hover {
  background: #f8f9fa;
}

.suggestion-icon {
  font-size: 16px;
  flex-shrink: 0;
}

.suggestion-text {
  font-size: 13px;
  color: #333;
  flex: 1;
}
</style>
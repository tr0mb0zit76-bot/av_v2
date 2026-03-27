<template>
  <div class="autocomplete-container">
    <div class="autocomplete-wrapper">
      <input
        :value="modelValue"
        type="text"
        class="autocomplete-input"
        :placeholder="placeholder"
        :disabled="disabled"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown.down.prevent="onArrowDown"
        @keydown.up.prevent="onArrowUp"
        @keydown.enter.prevent="onEnter"
      />
      <div v-if="loading" class="autocomplete-spinner">⏳</div>
    </div>
    
    <div v-if="isOpen && filteredItems.length" class="autocomplete-dropdown">
      <div
        v-for="(item, index) in filteredItems"
        :key="index"
        class="autocomplete-item"
        :class="{ selected: selectedIndex === index }"
        @click="selectItem(item)"
        @mouseenter="selectedIndex = index"
      >
        <div class="item-name">{{ item.name || item.full_name || item.last_name }}</div>
        <div class="item-details">
          <span v-if="item.inn">ИНН: {{ item.inn }}</span>
          <span v-if="item.phone">{{ item.phone }}</span>
          <span v-if="item.first_name">{{ item.first_name }} {{ item.last_name }}</span>
        </div>
      </div>
    </div>
    
    <div v-if="isOpen && !loading && filteredItems.length === 0 && query.length >= 2" class="autocomplete-empty">
      Ничего не найдено
      <button v-if="showCreate" type="button" class="create-link" @click="createNew">
        + Создать
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  searchFn: {
    type: Function,
    required: true
  },
  params: {
    type: Object,
    default: () => ({})
  },
  placeholder: {
    type: String,
    default: 'Поиск...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  showCreate: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'select', 'create'])

const query = ref(props.modelValue)
const loading = ref(false)
const isOpen = ref(false)
const items = ref([])
const selectedIndex = ref(-1)

// Фильтрованные элементы
const filteredItems = computed(() => items.value)

// Обработка ввода
const onInput = async (e) => {
  const value = e.target.value
  query.value = value
  emit('update:modelValue', value)
  
  if (value.length >= 2) {
    await search(value)
  } else {
    items.value = []
    isOpen.value = false
  }
}

// Поиск
const search = async (value) => {
  loading.value = true
  try {
    const result = await props.searchFn(value, props.params)
    items.value = result || []
    isOpen.value = items.value.length > 0
  } catch (error) {
    console.error('Search error:', error)
    items.value = []
  } finally {
    loading.value = false
  }
}

// Выбор элемента
const selectItem = (item) => {
  const displayValue = item.name || item.full_name || `${item.last_name} ${item.first_name}`.trim()
  query.value = displayValue
  emit('update:modelValue', displayValue)
  emit('select', item)
  isOpen.value = false
  selectedIndex.value = -1
}

// Клавиатурная навигация
const onArrowDown = () => {
  if (selectedIndex.value < filteredItems.value.length - 1) {
    selectedIndex.value++
  }
}

const onArrowUp = () => {
  if (selectedIndex.value > 0) {
    selectedIndex.value--
  }
}

const onEnter = () => {
  if (selectedIndex.value >= 0 && filteredItems.value[selectedIndex.value]) {
    selectItem(filteredItems.value[selectedIndex.value])
  }
}

// Фокус
const onFocus = () => {
  if (query.value.length >= 2 && items.value.length > 0) {
    isOpen.value = true
  }
}

const onBlur = () => {
  setTimeout(() => {
    isOpen.value = false
  }, 200)
}

// Создание нового
const createNew = () => {
  emit('create', query.value)
  isOpen.value = false
}

// Синхронизация внешнего значения
watch(() => props.modelValue, (newVal) => {
  if (newVal !== query.value) {
    query.value = newVal
  }
})
</script>

<style scoped>
.autocomplete-container {
  position: relative;
  width: 100%;
}

.autocomplete-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.autocomplete-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.autocomplete-input:focus {
  outline: none;
  border-color: #c17b4b;
  box-shadow: 0 0 0 2px rgba(193, 123, 75, 0.1);
}

.autocomplete-input:disabled {
  background: #e9ecef;
  cursor: not-allowed;
}

.autocomplete-spinner {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 14px;
}

.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 300px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  margin-top: 4px;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.autocomplete-item {
  padding: 10px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.autocomplete-item:hover,
.autocomplete-item.selected {
  background: #f0f7ff;
}

.item-name {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.item-details {
  font-size: 12px;
  color: #6c757d;
  margin-top: 4px;
}

.item-details span {
  margin-right: 12px;
}

.autocomplete-empty {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  padding: 12px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  margin-top: 4px;
  text-align: center;
  color: #6c757d;
  font-size: 13px;
  z-index: 1000;
}

.create-link {
  background: none;
  border: none;
  color: #c17b4b;
  cursor: pointer;
  margin-left: 8px;
  font-size: 13px;
}

.create-link:hover {
  text-decoration: underline;
}
</style>
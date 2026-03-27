<template>
  <div class="autocomplete-container">
    <input
      :value="modelValue"
      @input="onInput"
      @focus="showDropdown"
      @blur="closeDropdown"
      :placeholder="placeholder"
      class="autocomplete-input"
    />
    <div v-if="isOpen && suggestions.length" class="autocomplete-dropdown">
      <div
        v-for="item in suggestions"
        :key="item.id"
        @click="selectItem(item)"
        class="autocomplete-item"
      >
        <div class="item-name">{{ item.name }}</div>
        <div class="item-details">
          <span v-if="item.inn">ИНН: {{ item.inn }}</span>
          <span v-if="item.phone">{{ item.phone }}</span>
        </div>
      </div>
    </div>
    <div v-if="loading" class="loading-spinner">⏳</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: String, default: '' },
  type: { type: String, required: true }, // 'customer' or 'carrier'
  placeholder: { type: String, default: 'Поиск...' }
})

const emit = defineEmits(['update:modelValue', 'select'])

const isOpen = ref(false)
const loading = ref(false)
const suggestions = ref([])
let debounceTimer = null

const onInput = (e) => {
  const value = e.target.value
  emit('update:modelValue', value)
  
  if (debounceTimer) clearTimeout(debounceTimer)
  
  if (value.length >= 2) {
    debounceTimer = setTimeout(() => search(value), 300)
  } else {
    suggestions.value = []
    isOpen.value = false
  }
}

const search = async (query) => {
  loading.value = true
  try {
    const response = await axios.get('/api/contractors/search', {
      params: { query, type: props.type }
    })
    suggestions.value = response.data
    isOpen.value = suggestions.value.length > 0
  } catch (error) {
    console.error('Search error:', error)
  } finally {
    loading.value = false
  }
}

const selectItem = (item) => {
  emit('update:modelValue', item.name)
  emit('select', item)
  isOpen.value = false
}

const showDropdown = () => {
  if (suggestions.value.length) isOpen.value = true
}

const closeDropdown = () => {
  setTimeout(() => { isOpen.value = false }, 200)
}
</script>
<template>
  <div class="route-builder">
    <!-- Кнопки добавления точек -->
    <div class="toolbar">
      <button type="button" @click="addPoint('loading')" class="btn-add loading">
        <span class="icon">📦</span>
        <span>Погрузка</span>
      </button>
      <button type="button" @click="addPoint('transit')" class="btn-add transit">
        <span class="icon">🚛</span>
        <span>Транзит</span>
      </button>
      <button type="button" @click="addPoint('unloading')" class="btn-add unloading">
        <span class="icon">🏁</span>
        <span>Выгрузка</span>
      </button>
    </div>

    <!-- Список точек маршрута -->
    <div v-if="points.length === 0" class="empty-state">
      <div class="empty-icon">🗺️</div>
      <p>Добавьте точки маршрута</p>
      <span class="empty-hint">Нажмите на кнопки выше, чтобы создать маршрут</span>
    </div>

    <draggable
      v-else
      v-model="points"
      item-key="id"
      handle=".drag-handle"
      @end="updateSequence"
      class="route-points-list"
    >
      <template #item="{ element, index }">
        <RoutePointItem
          :point="element"
          :index="index"
          :is-first="index === 0"
          :is-last="index === points.length - 1"
          @update="updatePoint"
          @remove="removePoint"
        />
      </template>
    </draggable>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import draggable from 'vuedraggable'
import RoutePointItem from './RoutePointItem.vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue'])

// Получаем текущий leg (обычно первый) и его точки
const currentLeg = computed({
  get: () => {
    if (props.modelValue && props.modelValue.length > 0) {
      return props.modelValue[0]
    }
    return null
  },
  set: (value) => {
    const newLegs = [...props.modelValue]
    if (newLegs.length > 0) {
      newLegs[0] = value
    } else {
      newLegs.push(value)
    }
    emit('update:modelValue', newLegs)
  }
})

// Точки маршрута (работаем напрямую с points текущего leg)
const points = computed({
  get: () => currentLeg.value?.points || [],
  set: (value) => {
    if (currentLeg.value) {
      // Обновляем sequence для всех точек
      const updatedPoints = value.map((point, idx) => ({
        ...point,
        sequence: idx
      }))
      
      currentLeg.value = {
        ...currentLeg.value,
        points: updatedPoints
      }
    } else if (value.length > 0) {
      // Если leg не существует, создаем его
      currentLeg.value = {
        id: null,
        order_id: null,
        sequence: 0,
        type: 'transport',
        description: 'Основной маршрут',
        points: value.map((point, idx) => ({
          ...point,
          sequence: idx
        })),
        cargos: []
      }
    }
  }
})

// Добавление точки
const addPoint = (type) => {
  const typeLabels = {
    loading: 'Погрузка',
    transit: 'Транзит',
    unloading: 'Выгрузка'
  }
  
  const newPoint = {
    id: `temp_${Date.now()}_${Math.random()}`,
    order_leg_id: currentLeg.value?.id || null,
    type: type,
    sequence: points.value.length,
    city: '',
    address: '',
    planned_date: '',
    planned_time_from: '',
    planned_time_to: '',
    contact_person: '',
    contact_phone: '',
    instructions: ''
  }
  
  points.value = [...points.value, newPoint]
}

// Обновление точки
const updatePoint = (updatedPoint) => {
  const index = points.value.findIndex(p => p.id === updatedPoint.id)
  if (index !== -1) {
    const newPoints = [...points.value]
    newPoints[index] = updatedPoint
    points.value = newPoints
  }
}

// Удаление точки
const removePoint = (id) => {
  points.value = points.value.filter(p => p.id !== id)
}

// Обновление последовательности после перетаскивания
const updateSequence = () => {
  // Обновляем sequence у всех точек
  const reorderedPoints = points.value.map((point, idx) => ({
    ...point,
    sequence: idx
  }))
  points.value = reorderedPoints
}
</script>

<style scoped>
.route-builder {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 20px;
}

.toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-add .icon {
  font-size: 18px;
}

.btn-add.loading {
  background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
}

.btn-add.loading:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(76, 175, 80, 0.4);
}

.btn-add.transit {
  background: linear-gradient(135deg, #ff9800 0%, #fb8c00 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
}

.btn-add.transit:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(255, 152, 0, 0.4);
}

.btn-add.unloading {
  background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
}

.btn-add.unloading:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  border: 2px dashed #dee2e6;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state p {
  font-size: 16px;
  font-weight: 500;
  color: #6c757d;
  margin: 0 0 8px 0;
}

.empty-hint {
  font-size: 13px;
  color: #adb5bd;
}

.route-points-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
</style>
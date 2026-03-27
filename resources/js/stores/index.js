// resources/js/stores/index.js - новый файл для объединения stores
import { createPinia } from 'pinia';
import { useAuthStore } from './auth';
import { useOrdersStore } from './modules/orders';
import { useContractorsStore } from './modules/contractors';
import { useUiStore } from './modules/ui';

const pinia = createPinia();

export { pinia, useAuthStore, useOrdersStore, useContractorsStore, useUiStore };
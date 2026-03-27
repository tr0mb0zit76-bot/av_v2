// resources/js/stores/modules/orders.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useOrdersStore = defineStore('orders', {
  state: () => ({
    items: [],
    loading: false,
    total: 0,
    currentOrder: null,
    error: null
  }),
  
  getters: {
    getOrderById: (state) => (id) => {
      return state.items.find(order => order.id === id);
    },
    
    getOrdersByStatus: (state) => (status) => {
      return state.items.filter(order => order.status === status);
    }
  },
  
  actions: {
    async fetchAll(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/orders', { params });
        this.items = response.data.data || response.data;
        this.total = response.data.total || this.items.length;
        return { success: true, data: this.items };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to fetch orders:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async fetchOne(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/api/orders/${id}`);
        this.currentOrder = response.data;
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to fetch order:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async create(data) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/orders', data);
        this.items.unshift(response.data);
        this.total++;
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to create order:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async update({ id, data }) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/api/orders/${id}`, data);
        const index = this.items.findIndex(item => item.id === id);
        if (index !== -1) {
          this.items[index] = { ...this.items[index], ...response.data };
        }
        if (this.currentOrder?.id === id) {
          this.currentOrder = { ...this.currentOrder, ...response.data };
        }
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to update order:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async saveCell({ id, field, value }) {
      this.error = null;
      
      try {
        const response = await axios.patch(`/api/orders/${id}/cell`, {
          field,
          value
        });
        
        const index = this.items.findIndex(item => item.id === id);
        if (index !== -1) {
          this.items[index][field] = value;
        }
        
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to save cell:', error);
        return { success: false, error: this.error };
      }
    },
    
    async delete(id) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.delete(`/api/orders/${id}`);
        this.items = this.items.filter(item => item.id !== id);
        this.total--;
        return { success: true };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to delete order:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async bulkDelete(ids) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.post('/api/orders/bulk-delete', { ids });
        this.items = this.items.filter(item => !ids.includes(item.id));
        this.total -= ids.length;
        return { success: true };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to bulk delete orders:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    clearError() {
      this.error = null;
    },
    
    reset() {
      this.items = [];
      this.loading = false;
      this.total = 0;
      this.currentOrder = null;
      this.error = null;
    }
  }
});
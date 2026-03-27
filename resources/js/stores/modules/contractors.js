// resources/js/stores/modules/contractors.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useContractorsStore = defineStore('contractors', {
  state: () => ({
    items: [],
    loading: false,
    total: 0,
    currentContractor: null,
    error: null
  }),
  
  getters: {
    getContractorById: (state) => (id) => {
      return state.items.find(contractor => contractor.id === id);
    },
    
    getCustomers: (state) => {
      return state.items.filter(c => c.type === 'customer' || c.type === 'both');
    },
    
    getCarriers: (state) => {
      return state.items.filter(c => c.type === 'carrier' || c.type === 'both');
    }
  },
  
  actions: {
    async fetchAll(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/api/contractors', { params });
        this.items = response.data.data || response.data;
        this.total = response.data.total || this.items.length;
        return { success: true, data: this.items };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        console.error('Failed to fetch contractors:', error);
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async search(query, type = null) {
      try {
        const params = { query };
        if (type) params.type = type;
        
        const response = await axios.get('/api/contractors/search', { params });
        return { success: true, data: response.data };
      } catch (error) {
        console.error('Failed to search contractors:', error);
        return { success: false, error: error.message };
      }
    },
    
    async create(data) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/contractors', data);
        this.items.unshift(response.data);
        this.total++;
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async update({ id, data }) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/api/contractors/${id}`, data);
        const index = this.items.findIndex(item => item.id === id);
        if (index !== -1) {
          this.items[index] = { ...this.items[index], ...response.data };
        }
        return { success: true, data: response.data };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    async delete(id) {
      this.loading = true;
      this.error = null;
      
      try {
        await axios.delete(`/api/contractors/${id}`);
        this.items = this.items.filter(item => item.id !== id);
        this.total--;
        return { success: true };
      } catch (error) {
        this.error = error.response?.data?.message || error.message;
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },
    
    reset() {
      this.items = [];
      this.loading = false;
      this.total = 0;
      this.currentContractor = null;
      this.error = null;
    }
  }
});
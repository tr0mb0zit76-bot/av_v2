// resources/js/stores/ai.js
import { defineStore } from 'pinia';

export const useAiStore = defineStore('ai', {
  state: () => ({
    history: [],
    settings: {
      enabled: true,
      contextLength: 10
    }
  }),
  
  getters: {
    lastQueries: (state) => {
      return state.history.slice(-5).reverse();
    }
  },
  
  actions: {
    addToHistory(item) {
      this.history.push({
        ...item,
        id: Date.now()
      });
      
      // Ограничиваем историю 50 записями
      if (this.history.length > 50) {
        this.history = this.history.slice(-50);
      }
      
      // Сохраняем в localStorage
      localStorage.setItem('ai_history', JSON.stringify(this.history));
    },
    
    loadHistory() {
      const saved = localStorage.getItem('ai_history');
      if (saved) {
        try {
          this.history = JSON.parse(saved);
        } catch (e) {
          console.error('Error loading AI history:', e);
        }
      }
    },
    
    clearHistory() {
      this.history = [];
      localStorage.removeItem('ai_history');
    }
  }
});
// resources/js/stores/modules/ui.js
import { defineStore } from 'pinia';

export const useUiStore = defineStore('ui', {
  state: () => ({
    sidebarOpen: true,
    currentModal: null,
    modalData: null,
    notifications: [],
    theme: localStorage.getItem('theme') || 'light'
  }),
  
  getters: {
    isModalOpen: (state) => !!state.currentModal,
    unreadNotifications: (state) => state.notifications.filter(n => !n.read).length
  },
  
  actions: {
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    
    openModal(name, data = null) {
      this.currentModal = name;
      this.modalData = data;
    },
    
    closeModal() {
      this.currentModal = null;
      this.modalData = null;
    },
    
    addNotification(notification) {
      const id = Date.now();
      this.notifications.push({
        id,
        read: false,
        timestamp: new Date(),
        ...notification
      });
      
      // Auto-remove after 5 seconds
      setTimeout(() => {
        this.removeNotification(id);
      }, 5000);
      
      return id;
    },
    
    removeNotification(id) {
      const index = this.notifications.findIndex(n => n.id === id);
      if (index !== -1) {
        this.notifications.splice(index, 1);
      }
    },
    
    markNotificationRead(id) {
      const notification = this.notifications.find(n => n.id === id);
      if (notification) {
        notification.read = true;
      }
    },
    
    clearNotifications() {
      this.notifications = [];
    },
    
    setTheme(theme) {
      this.theme = theme;
      localStorage.setItem('theme', theme);
      document.documentElement.setAttribute('data-theme', theme);
    },
    
    toggleTheme() {
      this.setTheme(this.theme === 'light' ? 'dark' : 'light');
    }
  }
});
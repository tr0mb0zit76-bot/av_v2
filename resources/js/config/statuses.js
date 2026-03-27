// resources/js/config/statuses.js
export const statuses = {
  orders: {
    new: { text: 'Новый', class: 'status-new', icon: '🆕' },
    in_transit: { text: 'В пути', class: 'status-in_transit', icon: '🚛' },
    completed: { text: 'Завершен', class: 'status-completed', icon: '✅' },
    cancelled: { text: 'Отменен', class: 'status-cancelled', icon: '❌' },
    documents: { text: 'Документы', class: 'status-documents', icon: '📄' },
    payment: { text: 'Оплата', class: 'status-payment', icon: '💰' }
  },
  
  payment: {
    paid: { text: 'Оплачено', class: 'payment-paid' },
    pending: { text: 'Ожидание', class: 'payment-pending' },
    overdue: { text: 'Просрочено', class: 'payment-overdue' },
    cancelled: { text: 'Отменен', class: 'payment-cancelled' }
  }
};
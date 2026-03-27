// services/kpiService.js
export class KpiService {
  constructor(periodService, salaryService) {
    this.periodService = periodService;
    this.salaryService = salaryService;
  }
  
  async calculateForOrder(order) {
    // Расчет KPI для конкретного заказа
    const delta = order.customer_rate - order.carrier_rate - order.additional_expenses;
    const kpiPercent = this.calculateKpiPercent(delta, order);
    const salary = this.salaryService.calculateSalary(kpiPercent, delta);
    
    return { delta, kpiPercent, salary };
  }
  
  calculateKpiPercent(delta, order) {
    // Логика расчета KPI
    if (delta <= 0) return 0;
    // ... сложная логика
  }
  
  async recalculatePeriod(managerId, startDate, endDate) {
    // Пересчет KPI за период для менеджера
    // Вызывается после изменения данных
  }
}
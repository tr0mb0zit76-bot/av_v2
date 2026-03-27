<template>
  <div class="reports-page">
    <div class="page-header">
      <h1>Отчеты</h1>
    </div>
    
    <div class="reports-container">
      <div class="report-card" @click="generateReport('orders')">
        <h3>📊 Отчет по заказам</h3>
        <p>Статистика по заказам за период</p>
      </div>
      
      <div class="report-card" @click="generateReport('kpi')">
        <h3>📈 KPI менеджеров</h3>
        <p>Эффективность работы менеджеров</p>
      </div>
      
      <div class="report-card" @click="generateReport('finance')">
        <h3>💰 Финансовый отчет</h3>
        <p>Доходы, расходы, прибыль</p>
      </div>
      
      <div class="report-card" @click="generateReport('salary')">
        <h3>👥 Зарплатный отчет</h3>
        <p>Начисленная зарплата по менеджерам</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const generateReport = (type) => {
  alert(`Генерация отчета "${type}" в разработке`);
};
</script>

<style scoped>
.reports-page {
  background: white;
  border-radius: 12px;
  padding: 24px;
}

.page-header h1 {
  margin: 0 0 24px 0;
  font-size: 24px;
  color: #333;
}

.reports-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.report-card {
  background: #f8f9fa;
  padding: 24px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #e9ecef;
}

.report-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-color: #c17b4b;
}

.report-card h3 {
  margin: 0 0 8px 0;
  color: #333;
}

.report-card p {
  margin: 0;
  color: #666;
  font-size: 14px;
}
</style>
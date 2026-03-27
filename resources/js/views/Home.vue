<template>
  <div class="site-container">
    <!-- Кнопка личного кабинета -->
    <div class="cabinet-button">
      <router-link to="/login" class="cabinet-btn">
        🚛 Личный кабинет
      </router-link>
    </div>

    <!-- Навигация по страницам -->
    <div class="section-nav">
      <div class="nav-dot" :class="{ active: activePage === 0 }" @click="scrollToPage(0)"></div>
      <div class="nav-dot" :class="{ active: activePage === 1 }" @click="scrollToPage(1)"></div>
      <div class="nav-dot" :class="{ active: activePage === 2 }" @click="scrollToPage(2)"></div>
    </div>

    <!-- Горизонтальные страницы -->
    <div ref="horizontalSections" class="horizontal-sections">
      <!-- Страница 1: О нас -->
      <div class="page">
        <div class="logo-container">
          <div class="logo-icon">АП</div>
          <div class="logo-text">
            <div class="logo-main">
              <h1>Автопартнер</h1>
              <span class="logo-region">Логистика</span>
            </div>
            <div class="logo-divider"></div>
            <div class="logo-tagline">Управляй перевозками с AI</div>
          </div>
        </div>
        
        <h2 class="section-title">О платформе</h2>
        <p class="description">
          Автопартнер — это интеллектуальная система управления логистикой, 
          которая объединяет AI-помощника, автоматический расчет KPI и полный 
          контроль перевозок в одном месте.
        </p>
      </div>

      <!-- Страница 2: Возможности -->
      <div class="page">
        <h2 class="section-title">Возможности платформы</h2>
        <div class="cards-grid">
          <div class="card" @click="goToDashboard">
            <h3>📊 Управление заказами</h3>
            <p>Создавайте и отслеживайте заказы, управляйте маршрутами и грузами</p>
          </div>
          <div class="card" @click="goToDashboard">
            <h3>💰 KPI и зарплата</h3>
            <p>Автоматический расчет KPI и зарплаты менеджеров</p>
          </div>
          <div class="card" @click="goToDashboard">
            <h3>🤖 AI помощник</h3>
            <p>Интеллектуальный парсер заказов и рекомендации</p>
          </div>
          <div class="card" @click="goToDashboard">
            <h3>📈 BI аналитика</h3>
            <p>Дашборды, отчеты и прогнозирование</p>
          </div>
        </div>
      </div>

      <!-- Страница 3: Контакты -->
      <div class="page">
        <h2 class="section-title">Контакты</h2>
        <div class="cards-grid contacts-grid">
          <div class="card">
            <h3>📞 Телефон</h3>
            <p>+7 (495) 123-45-67</p>
          </div>
          <div class="card">
            <h3>✉️ Email</h3>
            <p>support@avtopartner.ru</p>
          </div>
          <div class="card">
            <h3>📍 Адрес</h3>
            <p>г. Москва, ул. Логистическая, д. 15</p>
          </div>
          <div class="card">
            <h3>💬 Telegram</h3>
            <p>@avtopartner_support</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Поле ввода -->
    <div class="input-fixed">
      <div class="panel-header">
        <span class="panel-title">💬 AI-помощник</span>
      </div>
      <div class="input-wrapper">
        <input 
          type="text" 
          v-model="chatInput" 
          class="chat-input" 
          placeholder="Напишите, например: Найди перевозку из Москвы в Казань..." 
          @focus="showMessages"
          @keyup.enter="sendMessage"
        />
        <button class="send-btn" @click="sendMessage">Отправить →</button>
      </div>
      <div class="chips-wrapper">
        <div class="chip" @click="quickMessage('Найди перевозку из Москвы в Санкт-Петербург')">🚛 Москва → СПб</div>
        <div class="chip" @click="quickMessage('Сколько стоит доставка станка 2 тонны?')">⚙️ Стоимость станка</div>
        <div class="chip" @click="quickMessage('Покажи KPI за прошлый месяц')">📊 KPI за месяц</div>
        <div class="chip" @click="quickMessage('Создать заказ на перевозку')">➕ Новый заказ</div>
      </div>
    </div>

    <!-- Сообщения чата -->
    <div class="messages-container">
      <div class="chat-messages" :class="{ visible: messagesVisible }" ref="chatMessages">
        <div v-for="msg in messages" :key="msg.id" :class="['message', msg.type]">
          {{ msg.text }}
        </div>
      </div>
    </div>

    <!-- Затемнение -->
    <div class="chat-overlay" :class="{ active: messagesVisible }" @click="hideMessages"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const activePage = ref(0);
const horizontalSections = ref(null);
const chatInput = ref('');
const messagesVisible = ref(false);
const chatMessages = ref(null);
const messages = ref([
  { id: 1, type: 'bot', text: '👋 Привет! Я AI-помощник Автопартнера. Задай вопрос о перевозках, поиске груза или расчете стоимости.' }
]);

const scrollToPage = (index) => {
  if (horizontalSections.value) {
    horizontalSections.value.scrollTo({
      left: index * window.innerWidth,
      behavior: 'smooth'
    });
  }
};

const goToDashboard = () => {
  router.push('/login');
};

const showMessages = () => {
  messagesVisible.value = true;
};

const hideMessages = () => {
  messagesVisible.value = false;
};

const addMessage = (type, text) => {
  messages.value.push({ id: Date.now(), type, text });
  nextTick(() => {
    if (chatMessages.value) {
      chatMessages.value.scrollTop = chatMessages.value.scrollHeight;
    }
  });
};

const sendMessage = () => {
  const query = chatInput.value.trim();
  if (!query) return;

  addMessage('user', query);
  showMessages();
  
  setTimeout(() => {
    const responses = [
      "Из базы: перевозка станка Самара → Москва ~47 000₽, срок 2-3 дня. Эксперт — Иван.",
      "Сборный груз Казань: от 45₽/кг, отправка дважды в неделю. Связаться с Марией?",
      "Негабарит Москва-Владивосток: нужен спецтранспорт. Рекомендую Алексея.",
      "По вашему запросу нашёл 12 похожих перевозок. Лучший менеджер по этому направлению — Елена."
    ];
    addMessage('bot', responses[Math.floor(Math.random() * responses.length)]);
  }, 600);

  chatInput.value = '';
};

const quickMessage = (text) => {
  chatInput.value = text;
  sendMessage();
};

onMounted(() => {
  if (horizontalSections.value) {
    horizontalSections.value.addEventListener('scroll', () => {
      const scrollPos = horizontalSections.value.scrollLeft;
      const pageWidth = window.innerWidth;
      activePage.value = Math.round(scrollPos / pageWidth);
    });
  }
  
  document.addEventListener('click', (event) => {
    const isClickOnInput = event.target.closest('.input-fixed');
    const isClickOnMessages = event.target.closest('.messages-container');
    const isClickOnOverlay = event.target.closest('.chat-overlay');
    
    if (!isClickOnInput && !isClickOnMessages && !isClickOnOverlay) {
      hideMessages();
    }
  });
});
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.site-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  background-color: #faf7f2;
}

:root {
  --primary-dark: #1a3b5e;
  --primary-soft: #2c4b6e;
  --accent-main: #c17b4b;
  --accent-light: #d89464;
  --bg-warm: #faf7f2;
  --bg-card: #ffffff;
  --text-dark: #1f2a36;
  --text-soft: #4a5562;
  --border-light: #e5ddd2;
}

/* Кнопка кабинета - контрастная */
.cabinet-button {
  position: fixed;
  top: 30px;
  right: 30px;
  z-index: 20;
}

.cabinet-btn {
  background: var(--accent-main);
  color: white;
  border: none;
  border-radius: 60px;
  padding: 12px 28px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  position: fixed;
  top: 30px;
  right: 30px;
  z-index: 9999;
}

.cabinet-btn:hover {
  background: #b06a3d;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

/* Навигация */
.section-nav {
  position: fixed;
  top: 30px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 16px;
  z-index: 9999;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 12px 30px;
  border-radius: 60px;
  border: 1px solid var(--border-light);
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.nav-dot {
  width: 10px;
  height: 10px;
  border-radius: 10px;
  background: #ccc;
  cursor: pointer;
}

.nav-dot.active {
  width: 30px;
  background: var(--accent-main);
}

/* Горизонтальные страницы */
.horizontal-sections {
  flex: 1;
  overflow-x: auto;
  overflow-y: hidden;
  scroll-snap-type: x mandatory;
  display: flex;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  height: 100%;
  padding-bottom: 160px; /* Увеличиваем отступ, чтобы контент не перекрывался */
}

.page {
  flex: 0 0 100vw;
  height: 100%;
  scroll-snap-align: start;
  overflow-y: auto;
  padding: 80px 40px 100px 40px;
  background-color: var(--bg-warm);
  border-right: 1px solid var(--border-light);
}

/* Логотип */
.logo-container {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 40px;
}

.logo-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--primary-dark), var(--primary-soft));
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 28px;
  box-shadow: 0 10px 20px -5px rgba(26, 59, 94, 0.3);
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-main {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.logo-main h1 {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-dark);
}

.logo-region {
  font-size: 20px;
  font-weight: 500;
  color: var(--accent-main);
}

.logo-divider {
  width: 40px;
  height: 3px;
  background: var(--accent-main);
  margin: 8px 0 6px 0;
}

.logo-tagline {
  font-size: 14px;
  color: var(--text-soft);
}

.section-title {
  font-size: 28px;
  color: var(--primary-dark);
  margin-bottom: 20px;
}

.description {
  font-size: 16px;
  line-height: 1.6;
  color: var(--text-soft);
  max-width: 800px;
}

/* Карточки */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
  margin-top: 30px;
}

.contacts-grid {
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.card {
  background: var(--bg-card);
  padding: 28px;
  border-radius: 20px;
  border: 1px solid var(--border-light);
  transition: all 0.2s;
  cursor: pointer;
}

.card:hover {
  transform: translateY(-5px);
  border-color: var(--accent-main);
  box-shadow: 0 15px 30px -10px rgba(193, 123, 75, 0.15);
}

.card h3 {
  color: var(--primary-dark);
  margin-bottom: 12px;
  font-size: 20px;
}

.card p {
  color: var(--text-soft);
  line-height: 1.5;
}

/* Поле ввода */
.input-fixed {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: white;
  border-top: 2px solid var(--accent-main);
  padding: 16px 30px 20px 30px;
  box-shadow: 0 -5px 25px rgba(0,0,0,0.1);
  z-index: 40;
}

.input-wrapper {
  display: flex;
  background: #f5f5f5;
  border-radius: 60px;
  padding: 4px;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.input-wrapper:focus-within {
  border-color: var(--accent-main);
  background: white;
  box-shadow: 0 0 0 4px rgba(193, 123, 75, 0.15);
}

.chat-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 16px 24px;
  font-size: 16px;
  outline: none;
}

.send-btn {
  background: var(--accent-main);
  color: white;
  border: none;
  border-radius: 60px;
  padding: 12px 30px;
  font-weight: 600;
  cursor: pointer;
}

.send-btn:hover {
  background: #b06a3d;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.panel-title {
  font-weight: 600;
  color: var(--text-dark);
}

.chips-wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.chip {
  background: #f0f0f0;
  padding: 8px 20px;
  border-radius: 40px;
  font-size: 14px;
  color: var(--text-dark);
  cursor: pointer;
  border: 1px solid var(--border-light);
  transition: all 0.2s;
}

.chip:hover {
  background: var(--accent-main);
  color: white;
  border-color: var(--accent-main);
}

/* Сообщения */
.messages-container {
  position: fixed;
  bottom: 140px;
  left: 0;
  width: 100%;
  z-index: 35;
  display: flex;
  flex-direction: column;
  pointer-events: none;
}

.chat-messages {
  width: 100%;
  max-height: 40vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 24px 30px;
  background: white;
  border: 2px solid var(--accent-main);
  border-bottom: none;
  box-shadow: 0 -10px 30px rgba(0,0,0,0.15);
  pointer-events: auto;
  transition: opacity 0.3s, transform 0.3s;
  opacity: 0;
  transform: translateY(20px);
  visibility: hidden;
}

.chat-messages.visible {
  opacity: 1;
  transform: translateY(0);
  visibility: visible;
}

.message {
  padding: 14px 20px;
  border-radius: 20px;
  font-size: 14px;
  max-width: 80%;
}

.message.bot {
  background: #f0ede7;
  border: 1px solid var(--border-light);
  border-bottom-left-radius: 4px;
  align-self: flex-start;
}

.message.user {
  background: var(--accent-main);
  color: white;
  border-bottom-right-radius: 4px;
  align-self: flex-end;
}

/* Затемнение */
.chat-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(3px);
  z-index: 30;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s;
}

.chat-overlay.active {
  opacity: 1;
  pointer-events: auto;
}
</style>
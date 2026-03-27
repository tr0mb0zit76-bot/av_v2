// resources/js/config/ordersColumns.js
export const ordersColumns = {
  all: [
    // Основные
    { field: 'id', headerName: 'ID', width: 80, pinned: 'left' },
    { field: 'order_number', headerName: '№ заказа', width: 120, pinned: 'left' },
    { field: 'status', headerName: 'Статус', width: 60, sortable: true, filter: true },
    { field: 'company_code', headerName: 'Наша компания', width: 100 },
    { field: 'manager_name', headerName: 'Менеджер', width: 120 },
    { field: 'order_date', headerName: 'Дата заявки', width: 110, type: 'date' },
    
    // Маршрут
    { field: 'loading_point', headerName: 'Загрузка', width: 200 },
    { field: 'unloading_point', headerName: 'Выгрузка', width: 200 },
    { field: 'cargo_description', headerName: 'Груз', width: 200 },
    { field: 'loading_date', headerName: 'Дата погрузки', width: 110, type: 'date' },
    { field: 'unloading_date', headerName: 'Дата выгрузки', width: 110, type: 'date' },
    
    // Финансы - ставки и условия
    { field: 'customer_rate', headerName: 'Ставка заказчика', width: 130, type: 'numericColumn' },
    { field: 'customer_payment_form', headerName: 'Форма оплаты (заказчик)', width: 130 },
    { field: 'customer_payment_term', headerName: 'Условия оплаты (заказчик)', width: 150 },
    { field: 'carrier_rate', headerName: 'Ставка перевозчика', width: 130, type: 'numericColumn' },
    { field: 'carrier_payment_form', headerName: 'Форма оплаты (перевозчик)', width: 130 },
    { field: 'carrier_payment_term', headerName: 'Условия оплаты (перевозчик)', width: 150 },
    
    // Расходы и KPI
    { field: 'additional_expenses', headerName: 'Доп. расходы', width: 100, type: 'numericColumn' },
    { field: 'insurance', headerName: 'Страховка', width: 100, type: 'numericColumn' },
    { field: 'bonus', headerName: 'Бонус', width: 100, type: 'numericColumn' },
    { field: 'kpi_percent', headerName: 'KPI %', width: 80, type: 'numericColumn' },
    { field: 'delta', headerName: 'Δ Дельта', width: 100, type: 'numericColumn' },  // ✅ только один раз
    
    // Оплаты - заказчик
    { field: 'prepayment_customer_amount', headerName: 'Предоплата (заказчик)', width: 130, type: 'numericColumn' },
    { field: 'prepayment_customer_planned_date', headerName: 'План дата (заказчик)', width: 110, type: 'date' },
    { field: 'prepayment_customer_actual_date', headerName: 'Факт дата (заказчик)', width: 110, type: 'date' },
    { field: 'prepayment_customer_status', headerName: 'Статус (заказчик)', width: 100 },
    { field: 'final_customer_amount', headerName: 'Окончательный (заказчик)', width: 130, type: 'numericColumn' },
    { field: 'final_customer_planned_date', headerName: 'План дата (заказчик)', width: 110, type: 'date' },
    { field: 'final_customer_actual_date', headerName: 'Факт дата (заказчик)', width: 110, type: 'date' },
    { field: 'final_customer_status', headerName: 'Статус (заказчик)', width: 100 },
    
    // Оплаты - перевозчик
    { field: 'prepayment_carrier_amount', headerName: 'Предоплата (перевозчик)', width: 130, type: 'numericColumn' },
    { field: 'prepayment_carrier_planned_date', headerName: 'План дата (перевозчик)', width: 110, type: 'date' },
    { field: 'prepayment_carrier_actual_date', headerName: 'Факт дата (перевозчик)', width: 110, type: 'date' },
    { field: 'prepayment_carrier_status', headerName: 'Статус (перевозчик)', width: 100 },
    { field: 'final_carrier_amount', headerName: 'Окончательный (перевозчик)', width: 130, type: 'numericColumn' },
    { field: 'final_carrier_planned_date', headerName: 'План дата (перевозчик)', width: 110, type: 'date' },
    { field: 'final_carrier_actual_date', headerName: 'Факт дата (перевозчик)', width: 110, type: 'date' },
    { field: 'final_carrier_status', headerName: 'Статус (перевозчик)', width: 100 },
    
    // Контрагенты
    { field: 'customer_name', headerName: 'Заказчик', width: 180 },
    { field: 'customer_contact_name', headerName: 'Контакт заказчика', width: 150 },
    { field: 'customer_contact_phone', headerName: 'Телефон заказчика', width: 130 },
    { field: 'carrier_name', headerName: 'Перевозчик', width: 180 },
    { field: 'carrier_contact_name', headerName: 'Контакт перевозчика', width: 150 },
    { field: 'carrier_contact_phone', headerName: 'Телефон перевозчика', width: 130 },
    { field: 'driver_name', headerName: 'Водитель', width: 150 },
    { field: 'driver_phone', headerName: 'Телефон водителя', width: 130 },
    
    // Зарплата
    { field: 'salary_accrued', headerName: 'Начислено', width: 100, type: 'numericColumn' },  // ✅ только один раз
    { field: 'salary_paid', headerName: 'Выплачено', width: 100, type: 'numericColumn' },     // ✅ только один раз
    
    // Документы - трек-номера
    { field: 'track_number_customer', headerName: 'Трек-номер (заказчик)', width: 150 },
    { field: 'track_sent_date_customer', headerName: 'Дата отправки (заказчик)', width: 110, type: 'date' },
    { field: 'track_received_date_customer', headerName: 'Дата получения (заказчик)', width: 110, type: 'date' },
    { field: 'track_number_carrier', headerName: 'Трек-номер (перевозчик)', width: 150 },
    { field: 'track_sent_date_carrier', headerName: 'Дата отправки (перевозчик)', width: 110, type: 'date' },
    { field: 'track_received_date_carrier', headerName: 'Дата получения (перевозчик)', width: 110, type: 'date' },
    
    // Документы - счета и УПД
    { field: 'invoice_number', headerName: '№ счёта', width: 120 },
    { field: 'upd_number', headerName: '№ УПД', width: 120 },
    { field: 'upd_carrier_number', headerName: '№ УПД перевозчика', width: 130 },
    { field: 'upd_carrier_date', headerName: 'Дата УПД перевозчика', width: 110, type: 'date' },
    
    // Документы - заявки
    { field: 'order_customer_number', headerName: 'Заявка заказчика', width: 120 },
    { field: 'order_customer_date', headerName: 'Дата заявки заказчика', width: 110, type: 'date' },
    { field: 'order_carrier_number', headerName: 'Заявка перевозчика', width: 120 },
    { field: 'order_carrier_date', headerName: 'Дата заявки перевозчика', width: 110, type: 'date' },
    
    // Дополнительно
    { field: 'waybill_number', headerName: 'ТН', width: 100 }
  ],
  
  defaultVisible: [
    'id', 'order_number', 'status', 'company_code', 'order_date',
    'loading_point', 'unloading_point', 'cargo_description',
    'customer_rate', 'carrier_rate', 'kpi_percent', 'delta',
    'customer_name', 'carrier_name'
  ],
  
  roles: {
    admin: { visible: null, editable: true },
    manager: {
      visible: [
        'id', 'order_number', 'status', 'company_code', 'order_date',
        'loading_point', 'unloading_point', 'cargo_description',
        'customer_rate', 'customer_payment_form', 'customer_payment_term',
        'carrier_rate', 'carrier_payment_form', 'carrier_payment_term',
        'additional_expenses', 'kpi_percent', 'delta',
        'customer_name', 'carrier_name',
        'salary_accrued', 'salary_paid',
        'track_number_customer', 'track_number_carrier',
        'invoice_number', 'upd_number'
      ],
      editable: true
    },
    dispatcher: {
      visible: [
        'order_number', 'status', 'loading_point', 'unloading_point',
        'cargo_description', 'loading_date', 'unloading_date',
        'driver_name', 'driver_phone',
        'track_number_customer', 'track_number_carrier'
      ],
      editable: ['loading_date', 'unloading_date', 'driver_phone', 'track_number_customer', 'track_number_carrier']
    },
    accountant: {
      visible: [
        'order_number', 'order_date', 'customer_rate', 'carrier_rate',
        'kpi_percent', 'delta', 'salary_accrued', 'salary_paid',
        'prepayment_customer_status', 'final_customer_status',
        'prepayment_carrier_status', 'final_carrier_status',
        'invoice_number', 'upd_number'
      ],
      editable: ['salary_paid', 'prepayment_customer_status', 'final_customer_status', 'prepayment_carrier_status', 'final_carrier_status']
    },
    viewer: {
      visible: [
        'order_number', 'status', 'order_date', 'loading_point',
        'unloading_point', 'cargo_description', 'customer_name', 'carrier_name'
      ],
      editable: false
    }
  }
};
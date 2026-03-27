<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================
        // 1. Базовые таблицы
        // ========================================
        
        // Таблица сайтов
        Schema::create('sites', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('domain', 100)->unique();
            $table->string('name', 100);
            $table->string('theme', 50)->default('default');
            $table->string('home_url', 255);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        
        // Таблица ролей
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
        
        // Таблица пользователей
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('site_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('theme', 20)->default('light');
            $table->boolean('is_active')->default(true);
            $table->json('ai_preferences')->nullable();
            $table->boolean('ai_learning_enabled')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->index(['site_id']);
            $table->index(['role_id']);
        });
        
        // ========================================
        // 2. Справочники
        // ========================================
        
        // Таблица контрагентов
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['customer', 'carrier', 'both'])->default('both');
            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('inn', 20)->nullable()->index();
            $table->string('kpp', 20)->nullable();
            $table->string('ogrn', 20)->nullable();
            $table->string('okpo', 20)->nullable();
            $table->enum('legal_form', ['ooо', 'zao', 'ao', 'ip', 'samozanyaty', 'other'])->nullable();
            $table->string('legal_address')->nullable();
            $table->string('actual_address')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('website', 255)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_phone', 50)->nullable();
            $table->string('contact_person_email', 255)->nullable();
            $table->string('contact_person_position')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bik', 9)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('correspondent_account', 20)->nullable();
            $table->json('ati_profiles')->nullable();
            $table->string('ati_id', 50)->nullable();
            $table->json('transport_requirements')->nullable();
            $table->json('specializations')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('completed_orders')->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('name');
        });
        
        // Таблица водителей
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('patronymic')->nullable();
            $table->string('phone', 50)->nullable()->index();
            $table->string('email', 255)->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('contractor_id');
        });
        
        // ========================================
        // 3. Основные таблицы бизнес-логики
        // ========================================
        
        // Таблица грузов
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500)->index();
            $table->text('description')->nullable();
            $table->decimal('weight', 10, 2)->nullable()->index();
            $table->decimal('volume', 10, 2)->nullable();
            $table->string('cargo_type', 100)->nullable();
            $table->unsignedInteger('cargo_type_id')->nullable()->comment('ID из словаря АТИ');
            $table->string('packing_type', 100)->nullable();
            $table->unsignedInteger('pack_type_id')->nullable()->comment('ID из словаря АТИ');
            $table->integer('pallet_count')->nullable();
            $table->integer('belt_count')->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->boolean('is_hazardous')->default(false);
            $table->string('hazard_class', 10)->nullable();
            $table->boolean('needs_temperature')->default(false);
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->boolean('needs_hydraulic')->default(false);
            $table->boolean('needs_manipulator')->default(false);
            $table->text('special_instructions')->nullable();
            $table->json('photos')->nullable();
            $table->json('documents')->nullable();
            $table->unsignedBigInteger('ati_load_id')->nullable()->index();
            $table->timestamp('ati_published_at')->nullable();
            $table->json('ati_response')->nullable();
            $table->text('source_text')->nullable();
            $table->string('source_file', 500)->nullable();
            $table->boolean('parsed_by_ai')->default(false);
            $table->timestamp('parsed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        
        // Таблица заказов (основная)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable()->index();
            $table->string('company_code', 10)->nullable()->index();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('site_id')->nullable();
            $table->date('order_date')->nullable()->index();
            $table->date('loading_date')->nullable()->index();
            $table->date('unloading_date')->nullable()->index();
            
            // Финансы
            $table->decimal('customer_rate', 12, 2)->nullable();
            $table->string('customer_payment_form', 50)->nullable();
            $table->string('customer_payment_term', 50)->nullable();
            $table->decimal('carrier_rate', 12, 2)->nullable();
            $table->string('carrier_payment_form', 50)->nullable();
            $table->string('carrier_payment_term', 50)->nullable();
            $table->decimal('additional_expenses', 12, 2)->default(0);
            $table->decimal('insurance', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            
            // KPI
            $table->decimal('kpi_percent', 5, 2)->nullable();
            $table->decimal('delta', 12, 2)->nullable();
            $table->decimal('salary_accrued', 12, 2)->default(0);
            $table->decimal('salary_paid', 12, 2)->default(0);
            
            // Статусы
            $table->string('status', 50)->default('new')->index();
            $table->boolean('is_active')->default(true);
            
            // Связи
            $table->foreignId('customer_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            
            // AI
            $table->foreignId('ai_draft_id')->nullable()->index();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('ai_metadata')->nullable();
            $table->json('ati_response')->nullable();
            $table->string('ati_load_id')->nullable()->unique();
            $table->timestamp('ati_published_at')->nullable();
            
            // Документы
            $table->string('invoice_number')->nullable();
            $table->string('upd_number')->nullable();
            $table->string('waybill_number')->nullable();
            
            // Аудит
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->json('payment_statuses')->nullable();
            
            $table->timestamps();
            
            // Внешние ключи
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
            
            // Индексы
            $table->index(['manager_id', 'order_date']);
            $table->index(['status', 'is_active']);
        });
        
        // ========================================
        // 4. Таблицы для маршрутов
        // ========================================
        
        // Этапы заказа (Legs)
        Schema::create('order_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('sequence')->default(0);
            $table->enum('type', ['transport', 'storage', 'transshipment'])->default('transport');
            $table->string('description', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'sequence']);
        });
        
        // Точки маршрута
        Schema::create('route_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_leg_id')->constrained('order_legs')->onDelete('cascade');
            $table->enum('type', ['loading', 'unloading', 'transit', 'customs', 'warehouse'])->default('transit');
            $table->integer('sequence')->default(0);
            $table->string('city', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('kladr_id', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->date('planned_date')->nullable();
            $table->time('planned_time_from')->nullable();
            $table->time('planned_time_to')->nullable();
            $table->date('actual_date')->nullable();
            $table->time('actual_time')->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('instructions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['order_leg_id', 'sequence']);
            $table->index(['order_leg_id', 'type']);
        });
        
        // Связь грузов с этапами
        Schema::create('cargo_leg', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('cargos')->onDelete('cascade');
            $table->foreignId('order_leg_id')->constrained('order_legs')->onDelete('cascade');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->enum('status', ['planned', 'loaded', 'unloaded', 'damaged', 'lost'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['cargo_id', 'order_leg_id']);
            $table->index(['order_leg_id', 'status']);
        });
        
        // Выгрузка грузов по точкам (для частичной выгрузки)
        Schema::create('cargo_unloading_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_id')->constrained('cargos')->onDelete('cascade');
            $table->foreignId('route_point_id')->constrained('route_points')->onDelete('cascade');
            $table->decimal('quantity', 12, 4)->comment('Количество, выгружаемое в этой точке');
            $table->timestamp('unloaded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['cargo_id', 'route_point_id']);
            $table->index('route_point_id');
        });
        
        // ========================================
        // 5. Финансы и KPI
        // ========================================
        
        // График платежей
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('party', ['customer', 'carrier']);
            $table->enum('type', ['prepayment', 'final']);
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'party', 'type']);
            $table->index('status');
        });
        
        // Пороги KPI
        Schema::create('kpi_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('deal_type', 50);
            $table->decimal('threshold_from', 5, 2);
            $table->decimal('threshold_to', 5, 2);
            $table->integer('kpi_percent');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['deal_type', 'threshold_from', 'threshold_to'], 'unique_kpi_threshold_range');
            $table->index(['deal_type', 'is_active']);
            $table->index(['threshold_from', 'threshold_to']);
        });
        
        // Настройки KPI
        Schema::create('kpi_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        
        // Коэффициенты зарплаты
        Schema::create('salary_coefficients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->integer('base_salary')->default(0);
            $table->integer('bonus_percent')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['manager_id', 'effective_from'], 'unique_manager_active');
            $table->index(['manager_id', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });
        
        // ========================================
        // 6. AI и вспомогательные таблицы
        // ========================================
        
        // AI диалоги
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('type', 50)->default('chat');
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });
        
        // AI сообщения
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->enum('feedback', ['like', 'dislike'])->nullable();
            $table->text('feedback_comment')->nullable();
            $table->json('corrected_data')->nullable();
            $table->json('context_used')->nullable();
            $table->json('sources')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->float('processing_time')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            
            $table->index('created_at');
        });
        
        // AI вложения
        Schema::create('ai_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('ai_messages')->onDelete('cascade');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->integer('size')->nullable();
            $table->string('disk', 50)->default('local');
            $table->json('extracted_data')->nullable();
            $table->string('vector_id')->nullable();
            $table->string('status', 50)->default('pending')->index();
            $table->timestamps();
        });
        
        // AI черновики заказов
        Schema::create('ai_order_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->json('parsed_data');
            $table->json('edited_data')->nullable();
            $table->json('ai_suggestions')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'edited', 'cancelled'])->default('draft')->index();
            $table->enum('source', ['text', 'file', 'email', 'telegram'])->default('text');
            $table->string('source_file', 500)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
        
        // Индекс знаний AI
        Schema::create('ai_knowledge_index', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['order', 'document', 'contractor', 'driver', 'kpi_pattern', 'message']);
            $table->unsignedBigInteger('source_id');
            $table->string('vector_id')->index();
            $table->string('content_hash', 64);
            $table->json('metadata')->nullable();
            $table->timestamp('indexed_at')->nullable()->index();
            $table->timestamps();
            
            $table->unique(['source_type', 'source_id'], 'source_unique');
        });
        
        // ========================================
        // 7. Системные таблицы
        // ========================================
        
        // Логи парсера AI
        Schema::create('ai_parser_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('raw_text');
            $table->json('parsed_json')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('source', 255)->default('text');
            $table->string('processing_route', 255)->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->string('ip_address', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('user_feedback')->nullable();
            $table->timestamps();
        });
        
        // Логи обратной связи AI
        Schema::create('ai_feedback_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('message_id')->constrained('ai_messages')->onDelete('cascade');
            $table->enum('feedback_type', ['like', 'dislike']);
            $table->json('original_response');
            $table->json('corrected_data')->nullable();
            $table->text('comment')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            
            $table->index(['user_id']);
            $table->index(['message_id']);
        });
        
        // Виджеты пользователей
        Schema::create('user_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('widget_id');
            $table->boolean('enabled')->default(true);
            $table->integer('position')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'widget_id']);
            $table->index(['user_id', 'enabled', 'position']);
        });
        
        // Права доступа к виджетам
        Schema::create('widget_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id');
            $table->string('role');
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique(['widget_id', 'role']);
            $table->index(['widget_id', 'role', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем в обратном порядке (сначала те, у которых есть внешние ключи)
        Schema::dropIfExists('ai_feedback_log');
        Schema::dropIfExists('ai_parser_logs');
        Schema::dropIfExists('widget_role_permissions');
        Schema::dropIfExists('user_widgets');
        Schema::dropIfExists('ai_knowledge_index');
        Schema::dropIfExists('ai_order_drafts');
        Schema::dropIfExists('ai_attachments');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('salary_coefficients');
        Schema::dropIfExists('kpi_settings');
        Schema::dropIfExists('kpi_thresholds');
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('cargo_unloading_points');
        Schema::dropIfExists('cargo_leg');
        Schema::dropIfExists('route_points');
        Schema::dropIfExists('order_legs');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('contractors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sites');
    }
};
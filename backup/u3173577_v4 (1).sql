-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Время создания: Мар 27 2026 г., 11:46
-- Версия сервера: 8.0.44
-- Версия PHP: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `u3173577_v4`
--

-- --------------------------------------------------------

--
-- Структура таблицы `ai_attachments`
--

CREATE TABLE `ai_attachments` (
  `id` bigint UNSIGNED NOT NULL,
  `message_id` bigint UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` int DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `extracted_data` json DEFAULT NULL,
  `vector_id` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_conversations`
--

CREATE TABLE `ai_conversations` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'chat',
  `context` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_feedback_log`
--

CREATE TABLE `ai_feedback_log` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `message_id` bigint UNSIGNED NOT NULL,
  `feedback_type` enum('like','dislike') NOT NULL,
  `original_response` json NOT NULL,
  `corrected_data` json DEFAULT NULL,
  `comment` text,
  `processing_time_ms` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_knowledge_index`
--

CREATE TABLE `ai_knowledge_index` (
  `id` bigint UNSIGNED NOT NULL,
  `source_type` enum('order','document','contractor','driver','kpi_pattern','message') NOT NULL,
  `source_id` bigint UNSIGNED NOT NULL,
  `vector_id` varchar(255) NOT NULL,
  `content_hash` varchar(64) NOT NULL,
  `metadata` json DEFAULT NULL,
  `indexed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_messages`
--

CREATE TABLE `ai_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `conversation_id` bigint UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') NOT NULL,
  `content` text NOT NULL,
  `metadata` json DEFAULT NULL,
  `feedback` enum('like','dislike') DEFAULT NULL,
  `feedback_comment` text,
  `corrected_data` json DEFAULT NULL,
  `context_used` json DEFAULT NULL,
  `sources` json DEFAULT NULL,
  `tokens_used` int DEFAULT NULL,
  `processing_time` double DEFAULT NULL,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_order_drafts`
--

CREATE TABLE `ai_order_drafts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `conversation_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `parsed_data` json NOT NULL,
  `edited_data` json DEFAULT NULL,
  `ai_suggestions` json DEFAULT NULL,
  `status` enum('draft','confirmed','edited','cancelled') NOT NULL DEFAULT 'draft',
  `source` enum('text','file','email','telegram') NOT NULL DEFAULT 'text',
  `source_file` varchar(500) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ai_parser_logs`
--

CREATE TABLE `ai_parser_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `raw_text` text NOT NULL,
  `parsed_json` json DEFAULT NULL,
  `raw_response` json DEFAULT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'text',
  `processing_route` varchar(255) DEFAULT NULL,
  `processing_time_ms` int DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `user_feedback` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cargos`
--

CREATE TABLE `cargos` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text,
  `weight` decimal(10,2) DEFAULT NULL,
  `volume` decimal(10,2) DEFAULT NULL,
  `cargo_type` varchar(100) DEFAULT NULL,
  `cargo_type_id` int UNSIGNED DEFAULT NULL COMMENT 'ID из словаря АТИ',
  `packing_type` varchar(100) DEFAULT NULL,
  `pack_type_id` int UNSIGNED DEFAULT NULL COMMENT 'ID из словаря АТИ',
  `pallet_count` int DEFAULT NULL,
  `belt_count` int DEFAULT NULL,
  `length` decimal(10,2) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `is_hazardous` tinyint(1) NOT NULL DEFAULT '0',
  `hazard_class` varchar(10) DEFAULT NULL,
  `needs_temperature` tinyint(1) NOT NULL DEFAULT '0',
  `temp_min` decimal(5,2) DEFAULT NULL,
  `temp_max` decimal(5,2) DEFAULT NULL,
  `needs_hydraulic` tinyint(1) NOT NULL DEFAULT '0',
  `needs_manipulator` tinyint(1) NOT NULL DEFAULT '0',
  `special_instructions` text,
  `photos` json DEFAULT NULL,
  `documents` json DEFAULT NULL,
  `ati_load_id` bigint UNSIGNED DEFAULT NULL,
  `ati_published_at` timestamp NULL DEFAULT NULL,
  `ati_response` json DEFAULT NULL,
  `source_text` text,
  `source_file` varchar(500) DEFAULT NULL,
  `parsed_by_ai` tinyint(1) NOT NULL DEFAULT '0',
  `parsed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cargo_leg`
--

CREATE TABLE `cargo_leg` (
  `id` bigint UNSIGNED NOT NULL,
  `cargo_id` bigint UNSIGNED NOT NULL,
  `order_leg_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `status` enum('planned','loaded','unloaded','damaged','lost') NOT NULL DEFAULT 'planned',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `cargo_unloading_points`
--

CREATE TABLE `cargo_unloading_points` (
  `id` bigint UNSIGNED NOT NULL,
  `cargo_id` bigint UNSIGNED NOT NULL,
  `route_point_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL COMMENT 'Количество, выгружаемое в этой точке',
  `unloaded_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `contractors`
--

CREATE TABLE `contractors` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('customer','carrier','both') NOT NULL DEFAULT 'both',
  `name` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `inn` varchar(20) DEFAULT NULL,
  `kpp` varchar(20) DEFAULT NULL,
  `ogrn` varchar(20) DEFAULT NULL,
  `okpo` varchar(20) DEFAULT NULL,
  `legal_form` enum('ooо','zao','ao','ip','samozanyaty','other') DEFAULT NULL,
  `legal_address` varchar(255) DEFAULT NULL,
  `actual_address` varchar(255) DEFAULT NULL,
  `postal_address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_person_phone` varchar(50) DEFAULT NULL,
  `contact_person_email` varchar(255) DEFAULT NULL,
  `contact_person_position` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bik` varchar(9) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `correspondent_account` varchar(20) DEFAULT NULL,
  `ati_profiles` json DEFAULT NULL,
  `ati_id` varchar(50) DEFAULT NULL,
  `transport_requirements` json DEFAULT NULL,
  `specializations` json DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `completed_orders` int NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `patronymic` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `contractor_id` bigint UNSIGNED DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `kpi_settings`
--

CREATE TABLE `kpi_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `kpi_thresholds`
--

CREATE TABLE `kpi_thresholds` (
  `id` bigint UNSIGNED NOT NULL,
  `deal_type` varchar(50) NOT NULL,
  `threshold_from` decimal(5,2) NOT NULL,
  `threshold_to` decimal(5,2) NOT NULL,
  `kpi_percent` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `order_number` varchar(255) DEFAULT NULL,
  `company_code` varchar(10) DEFAULT NULL,
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `site_id` tinyint UNSIGNED DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `loading_date` date DEFAULT NULL,
  `unloading_date` date DEFAULT NULL,
  `customer_rate` decimal(12,2) DEFAULT NULL,
  `customer_payment_form` varchar(50) DEFAULT NULL,
  `customer_payment_term` varchar(50) DEFAULT NULL,
  `carrier_rate` decimal(12,2) DEFAULT NULL,
  `carrier_payment_form` varchar(50) DEFAULT NULL,
  `carrier_payment_term` varchar(50) DEFAULT NULL,
  `additional_expenses` decimal(12,2) NOT NULL DEFAULT '0.00',
  `insurance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bonus` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kpi_percent` decimal(5,2) DEFAULT NULL,
  `delta` decimal(12,2) DEFAULT NULL,
  `salary_accrued` decimal(12,2) NOT NULL DEFAULT '0.00',
  `salary_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(50) NOT NULL DEFAULT 'new',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `carrier_id` bigint UNSIGNED DEFAULT NULL,
  `driver_id` bigint UNSIGNED DEFAULT NULL,
  `ai_draft_id` bigint UNSIGNED DEFAULT NULL,
  `ai_confidence` decimal(5,2) DEFAULT NULL,
  `ai_metadata` json DEFAULT NULL,
  `ati_response` json DEFAULT NULL,
  `ati_load_id` varchar(255) DEFAULT NULL,
  `ati_published_at` timestamp NULL DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `upd_number` varchar(255) DEFAULT NULL,
  `waybill_number` varchar(255) DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `payment_statuses` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_legs`
--

CREATE TABLE `order_legs` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `sequence` int NOT NULL DEFAULT '0',
  `type` enum('transport','storage','transshipment') NOT NULL DEFAULT 'transport',
  `description` varchar(500) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payment_schedules`
--

CREATE TABLE `payment_schedules` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `party` enum('customer','carrier') NOT NULL,
  `type` enum('prepayment','final') NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `planned_date` date DEFAULT NULL,
  `actual_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` text,
  `permissions` json DEFAULT NULL,
  `columns_config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `route_points`
--

CREATE TABLE `route_points` (
  `id` bigint UNSIGNED NOT NULL,
  `order_leg_id` bigint UNSIGNED NOT NULL,
  `type` enum('loading','unloading','transit','customs','warehouse') NOT NULL DEFAULT 'transit',
  `sequence` int NOT NULL DEFAULT '0',
  `city` varchar(255) DEFAULT NULL,
  `address` text,
  `kladr_id` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `planned_date` date DEFAULT NULL,
  `planned_time_from` time DEFAULT NULL,
  `planned_time_to` time DEFAULT NULL,
  `actual_date` date DEFAULT NULL,
  `actual_time` time DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `instructions` text,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `salary_coefficients`
--

CREATE TABLE `salary_coefficients` (
  `id` bigint UNSIGNED NOT NULL,
  `manager_id` bigint UNSIGNED NOT NULL,
  `base_salary` int NOT NULL DEFAULT '0',
  `bonus_percent` int NOT NULL DEFAULT '0',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sites`
--

CREATE TABLE `sites` (
  `id` tinyint UNSIGNED NOT NULL,
  `domain` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `theme` varchar(50) NOT NULL DEFAULT 'default',
  `home_url` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `site_id` tinyint UNSIGNED DEFAULT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `theme` varchar(20) NOT NULL DEFAULT 'light',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `ai_preferences` json DEFAULT NULL,
  `ai_learning_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_widgets`
--

CREATE TABLE `user_widgets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `widget_id` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `position` int NOT NULL DEFAULT '0',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `widget_role_permissions`
--

CREATE TABLE `widget_role_permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `widget_id` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `ai_attachments`
--
ALTER TABLE `ai_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_attachments_message_id_foreign` (`message_id`),
  ADD KEY `ai_attachments_status_index` (`status`);

--
-- Индексы таблицы `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_conversations_user_id_foreign` (`user_id`),
  ADD KEY `ai_conversations_session_id_index` (`session_id`),
  ADD KEY `ai_conversations_last_activity_at_index` (`last_activity_at`);

--
-- Индексы таблицы `ai_feedback_log`
--
ALTER TABLE `ai_feedback_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_feedback_log_user_id_index` (`user_id`),
  ADD KEY `ai_feedback_log_message_id_index` (`message_id`),
  ADD KEY `ai_feedback_log_created_at_index` (`created_at`);

--
-- Индексы таблицы `ai_knowledge_index`
--
ALTER TABLE `ai_knowledge_index`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source_unique` (`source_type`,`source_id`),
  ADD KEY `ai_knowledge_index_vector_id_index` (`vector_id`),
  ADD KEY `ai_knowledge_index_indexed_at_index` (`indexed_at`);

--
-- Индексы таблицы `ai_messages`
--
ALTER TABLE `ai_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_messages_conversation_id_foreign` (`conversation_id`),
  ADD KEY `ai_messages_created_at_index` (`created_at`);

--
-- Индексы таблицы `ai_order_drafts`
--
ALTER TABLE `ai_order_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_order_drafts_user_id_foreign` (`user_id`),
  ADD KEY `ai_order_drafts_conversation_id_foreign` (`conversation_id`),
  ADD KEY `ai_order_drafts_order_id_foreign` (`order_id`),
  ADD KEY `ai_order_drafts_status_index` (`status`);

--
-- Индексы таблицы `ai_parser_logs`
--
ALTER TABLE `ai_parser_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_parser_logs_user_id_foreign` (`user_id`);

--
-- Индексы таблицы `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Индексы таблицы `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Индексы таблицы `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cargos_created_by_foreign` (`created_by`),
  ADD KEY `cargos_updated_by_foreign` (`updated_by`),
  ADD KEY `cargos_title_index` (`title`),
  ADD KEY `cargos_weight_index` (`weight`),
  ADD KEY `cargos_ati_load_id_index` (`ati_load_id`);

--
-- Индексы таблицы `cargo_leg`
--
ALTER TABLE `cargo_leg`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cargo_leg_cargo_id_order_leg_id_unique` (`cargo_id`,`order_leg_id`),
  ADD KEY `cargo_leg_order_leg_id_status_index` (`order_leg_id`,`status`);

--
-- Индексы таблицы `cargo_unloading_points`
--
ALTER TABLE `cargo_unloading_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cargo_unloading_points_cargo_id_route_point_id_index` (`cargo_id`,`route_point_id`),
  ADD KEY `cargo_unloading_points_route_point_id_index` (`route_point_id`);

--
-- Индексы таблицы `contractors`
--
ALTER TABLE `contractors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contractors_created_by_foreign` (`created_by`),
  ADD KEY `contractors_updated_by_foreign` (`updated_by`),
  ADD KEY `contractors_type_is_active_index` (`type`,`is_active`),
  ADD KEY `contractors_name_index` (`name`),
  ADD KEY `contractors_inn_index` (`inn`),
  ADD KEY `contractors_phone_index` (`phone`),
  ADD KEY `contractors_email_index` (`email`),
  ADD KEY `contractors_is_active_index` (`is_active`);

--
-- Индексы таблицы `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `drivers_contractor_id_index` (`contractor_id`),
  ADD KEY `drivers_phone_index` (`phone`);

--
-- Индексы таблицы `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Индексы таблицы `kpi_settings`
--
ALTER TABLE `kpi_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kpi_settings_key_unique` (`key`);

--
-- Индексы таблицы `kpi_thresholds`
--
ALTER TABLE `kpi_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_kpi_threshold_range` (`deal_type`,`threshold_from`,`threshold_to`),
  ADD KEY `kpi_thresholds_deal_type_is_active_index` (`deal_type`,`is_active`),
  ADD KEY `kpi_thresholds_threshold_from_threshold_to_index` (`threshold_from`,`threshold_to`);

--
-- Индексы таблицы `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_ati_load_id_unique` (`ati_load_id`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`),
  ADD KEY `orders_carrier_id_foreign` (`carrier_id`),
  ADD KEY `orders_driver_id_foreign` (`driver_id`),
  ADD KEY `orders_created_by_foreign` (`created_by`),
  ADD KEY `orders_updated_by_foreign` (`updated_by`),
  ADD KEY `orders_site_id_foreign` (`site_id`),
  ADD KEY `orders_manager_id_order_date_index` (`manager_id`,`order_date`),
  ADD KEY `orders_status_is_active_index` (`status`,`is_active`),
  ADD KEY `orders_order_number_index` (`order_number`),
  ADD KEY `orders_company_code_index` (`company_code`),
  ADD KEY `orders_order_date_index` (`order_date`),
  ADD KEY `orders_loading_date_index` (`loading_date`),
  ADD KEY `orders_unloading_date_index` (`unloading_date`),
  ADD KEY `orders_status_index` (`status`),
  ADD KEY `orders_ai_draft_id_index` (`ai_draft_id`);

--
-- Индексы таблицы `order_legs`
--
ALTER TABLE `order_legs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_legs_order_id_sequence_index` (`order_id`,`sequence`);

--
-- Индексы таблицы `payment_schedules`
--
ALTER TABLE `payment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_schedules_order_id_party_type_index` (`order_id`,`party`,`type`),
  ADD KEY `payment_schedules_status_index` (`status`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Индексы таблицы `route_points`
--
ALTER TABLE `route_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_points_order_leg_id_sequence_index` (`order_leg_id`,`sequence`),
  ADD KEY `route_points_order_leg_id_type_index` (`order_leg_id`,`type`);

--
-- Индексы таблицы `salary_coefficients`
--
ALTER TABLE `salary_coefficients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_manager_active` (`manager_id`,`effective_from`),
  ADD KEY `salary_coefficients_manager_id_is_active_index` (`manager_id`,`is_active`),
  ADD KEY `salary_coefficients_effective_from_effective_to_index` (`effective_from`,`effective_to`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Индексы таблицы `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sites_domain_unique` (`domain`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_site_id_index` (`site_id`),
  ADD KEY `users_role_id_index` (`role_id`);

--
-- Индексы таблицы `user_widgets`
--
ALTER TABLE `user_widgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_widgets_user_id_widget_id_unique` (`user_id`,`widget_id`),
  ADD KEY `user_widgets_user_id_enabled_position_index` (`user_id`,`enabled`,`position`);

--
-- Индексы таблицы `widget_role_permissions`
--
ALTER TABLE `widget_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `widget_role_permissions_widget_id_role_unique` (`widget_id`,`role`),
  ADD KEY `widget_role_permissions_widget_id_role_enabled_index` (`widget_id`,`role`,`enabled`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `ai_attachments`
--
ALTER TABLE `ai_attachments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_conversations`
--
ALTER TABLE `ai_conversations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_feedback_log`
--
ALTER TABLE `ai_feedback_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_knowledge_index`
--
ALTER TABLE `ai_knowledge_index`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_messages`
--
ALTER TABLE `ai_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_order_drafts`
--
ALTER TABLE `ai_order_drafts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ai_parser_logs`
--
ALTER TABLE `ai_parser_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cargo_leg`
--
ALTER TABLE `cargo_leg`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cargo_unloading_points`
--
ALTER TABLE `cargo_unloading_points`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `contractors`
--
ALTER TABLE `contractors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `kpi_settings`
--
ALTER TABLE `kpi_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `kpi_thresholds`
--
ALTER TABLE `kpi_thresholds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_legs`
--
ALTER TABLE `order_legs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `payment_schedules`
--
ALTER TABLE `payment_schedules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `route_points`
--
ALTER TABLE `route_points`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `salary_coefficients`
--
ALTER TABLE `salary_coefficients`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `sites`
--
ALTER TABLE `sites`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `user_widgets`
--
ALTER TABLE `user_widgets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `widget_role_permissions`
--
ALTER TABLE `widget_role_permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `ai_attachments`
--
ALTER TABLE `ai_attachments`
  ADD CONSTRAINT `ai_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `ai_messages` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `ai_conversations`
--
ALTER TABLE `ai_conversations`
  ADD CONSTRAINT `ai_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `ai_feedback_log`
--
ALTER TABLE `ai_feedback_log`
  ADD CONSTRAINT `ai_feedback_log_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `ai_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_feedback_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `ai_messages`
--
ALTER TABLE `ai_messages`
  ADD CONSTRAINT `ai_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `ai_order_drafts`
--
ALTER TABLE `ai_order_drafts`
  ADD CONSTRAINT `ai_order_drafts_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ai_order_drafts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ai_order_drafts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `ai_parser_logs`
--
ALTER TABLE `ai_parser_logs`
  ADD CONSTRAINT `ai_parser_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `cargos`
--
ALTER TABLE `cargos`
  ADD CONSTRAINT `cargos_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cargos_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `cargo_leg`
--
ALTER TABLE `cargo_leg`
  ADD CONSTRAINT `cargo_leg_cargo_id_foreign` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cargo_leg_order_leg_id_foreign` FOREIGN KEY (`order_leg_id`) REFERENCES `order_legs` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `cargo_unloading_points`
--
ALTER TABLE `cargo_unloading_points`
  ADD CONSTRAINT `cargo_unloading_points_cargo_id_foreign` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cargo_unloading_points_route_point_id_foreign` FOREIGN KEY (`route_point_id`) REFERENCES `route_points` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `contractors`
--
ALTER TABLE `contractors`
  ADD CONSTRAINT `contractors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contractors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `order_legs`
--
ALTER TABLE `order_legs`
  ADD CONSTRAINT `order_legs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payment_schedules`
--
ALTER TABLE `payment_schedules`
  ADD CONSTRAINT `payment_schedules_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `route_points`
--
ALTER TABLE `route_points`
  ADD CONSTRAINT `route_points_order_leg_id_foreign` FOREIGN KEY (`order_leg_id`) REFERENCES `order_legs` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `salary_coefficients`
--
ALTER TABLE `salary_coefficients`
  ADD CONSTRAINT `salary_coefficients_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_widgets`
--
ALTER TABLE `user_widgets`
  ADD CONSTRAINT `user_widgets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

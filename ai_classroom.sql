-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 06, 2026 at 11:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ai_classroom`
--

-- --------------------------------------------------------

--
-- Table structure for table `analysis_jobs`
--

CREATE TABLE `analysis_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `remote_job_id` varchar(36) DEFAULT NULL,
  `correlation_id` varchar(36) DEFAULT NULL,
  `exam_session_id` bigint(20) UNSIGNED NOT NULL,
  `source_type` enum('recorded_video','live_stream','webcam','test_source') NOT NULL,
  `video_asset_id` bigint(20) UNSIGNED DEFAULT NULL,
  `camera_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `model_version_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','queued','processing','paused','cancelled','failed','completed') NOT NULL DEFAULT 'pending',
  `remote_status` varchar(20) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `remote_output_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`remote_output_metadata`)),
  `progress_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `remote_progress` tinyint(3) UNSIGNED DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `correlation_id` varchar(36) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `result` enum('success','failure') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `camera_sources`
--

CREATE TABLE `camera_sources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `source_type` enum('webcam','rtsp','http','video_file','test_source') NOT NULL,
  `identifier` varchar(500) NOT NULL,
  `credentials_encrypted` text DEFAULT NULL,
  `status` enum('inactive','testing','connected','failed') NOT NULL DEFAULT 'inactive',
  `last_tested_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detection_events`
--

CREATE TABLE `detection_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_session_id` bigint(20) UNSIGNED NOT NULL,
  `analysis_job_id` bigint(20) UNSIGNED NOT NULL,
  `model_version_id` bigint(20) UNSIGNED NOT NULL,
  `source_type` enum('recorded_video','live_stream','webcam','test_source') NOT NULL,
  `temporary_track_id` int(11) NOT NULL,
  `event_type` enum('D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3') NOT NULL,
  `event_status` enum('active','ended') NOT NULL DEFAULT 'active',
  `started_at_frame` int(11) DEFAULT NULL,
  `ended_at_frame` int(11) DEFAULT NULL,
  `started_at_seconds` double DEFAULT NULL,
  `ended_at_seconds` double DEFAULT NULL,
  `confidence` double DEFAULT NULL,
  `rule_score` double DEFAULT NULL,
  `evidence_available` tinyint(1) NOT NULL DEFAULT 0,
  `review_status` enum('pending','confirmed_suspicious','dismissed_normal','needs_further_review') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `reviewer_note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_evidence`
--

CREATE TABLE `event_evidence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `detection_event_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('snapshot','clip') NOT NULL,
  `frame_number` int(11) DEFAULT NULL,
  `captured_at_seconds` double DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `checksum_sha256` varchar(64) DEFAULT NULL,
  `file_checksum` varchar(64) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_rooms`
--

CREATE TABLE `exam_rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `building` varchar(150) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `camera_position_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `status` enum('pending','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_30_132716_create_phase5_foundation_tables', 1),
(5, '2026_08_30_134430_add_remote_job_fields_to_analysis_jobs', 1),
(6, '2026_08_30_144216_add_is_active_to_model_versions', 1),
(7, '2026_08_30_150539_add_soft_deletes_to_analysis_jobs', 1),
(8, '2026_08_30_160000_add_deleted_at_to_video_assets_table', 1),
(9, '2026_09_06_000001_add_v2_event_taxonomy', 1),
(10, '2026_09_06_000002_add_soft_deletes_and_v2_management', 1),
(11, '2026_09_06_094126_add_soft_deletes_to_rooms_sessions_users', 2);

-- --------------------------------------------------------

--
-- Table structure for table `model_versions`
--

CREATE TABLE `model_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `version` varchar(50) NOT NULL,
  `weight_filename` varchar(255) NOT NULL,
  `checksum_sha256` varchar(64) NOT NULL,
  `class_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`class_list`)),
  `training_dataset_version` varchar(100) DEFAULT NULL,
  `image_size` int(11) DEFAULT NULL,
  `license` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `source_url` varchar(500) DEFAULT NULL,
  `framework_versions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`framework_versions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `group` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `group`, `created_at`, `updated_at`) VALUES
(1, 'manage_rooms', 'rooms', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(2, 'manage_sessions', 'sessions', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(3, 'manage_cameras', 'cameras', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(4, 'upload_recordings', 'recordings', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(5, 'view_evidence', 'evidence', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(6, 'review_events', 'events', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(7, 'export_reports', 'reports', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(8, 'view_audit_logs', 'audit', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(9, 'manage_users', 'users', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(10, 'manage_models', 'models', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(11, 'view_metrics', 'metrics', '2026-09-06 03:44:07', '2026-09-06 03:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 7),
(2, 11),
(3, 5),
(3, 11),
(4, 5),
(4, 6),
(5, 5),
(5, 8),
(5, 11);

-- --------------------------------------------------------

--
-- Table structure for table `processing_metrics`
--

CREATE TABLE `processing_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `analysis_job_id` bigint(20) UNSIGNED NOT NULL,
  `source_fps` double DEFAULT NULL,
  `processing_fps` double DEFAULT NULL,
  `detection_latency_ms` double DEFAULT NULL,
  `end_to_end_alert_latency_ms` double DEFAULT NULL,
  `cpu_percent` double DEFAULT NULL,
  `memory_mb` double DEFAULT NULL,
  `gpu_percent` double DEFAULT NULL,
  `dropped_frames` int(11) NOT NULL DEFAULT 0,
  `queue_size` int(11) DEFAULT NULL,
  `reconnect_count` int(11) NOT NULL DEFAULT 0,
  `job_duration_seconds` double DEFAULT NULL,
  `video_duration_to_processing_ratio` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retention_actions`
--

CREATE TABLE `retention_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('scheduled','executed','failed') NOT NULL,
  `target_type` varchar(100) NOT NULL,
  `target_id` varchar(100) NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `executed_at` datetime DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_decisions`
--

CREATE TABLE `review_decisions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `detection_event_id` bigint(20) UNSIGNED NOT NULL,
  `exam_session_id` bigint(20) UNSIGNED NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('confirmed_suspicious','dismissed_normal','needs_further_review') NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'system_admin', 'System Administrator', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(2, 'exam_admin', 'Exam Administrator', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(3, 'invigilator', 'Invigilator', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(4, 'reviewer', 'Reviewer', '2026-09-06 03:44:07', '2026-09-06 03:44:07'),
(5, 'auditor', 'Auditor', '2026-09-06 03:44:07', '2026-09-06 03:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`role_id`, `user_id`) VALUES
(1, 1),
(2, 2),
(2, 3),
(3, 4),
(4, 5),
(5, 6);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('e2GCF8vHiPmhRN8JbVLXTFW6gmy36okHTGVJvBCp', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRmVUSnY4NUpYODJMTWlwTFVWZFU3S0NLUWF4RFduY1p2eUpZZzUzTiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI5OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbWV0cmljcyI7czo1OiJyb3V0ZSI7czoxMzoibWV0cmljcy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1788688353);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'System Admin', 'admin@example.com', '2026-09-06 03:44:07', '$2y$12$tLVANEz05T//GnvnT9AVpuc8RnAsaqHBj1F6mi9xLOmRW8SKIW7O.', NULL, '2026-09-06 03:44:07', '2026-09-06 03:44:07', NULL),
(2, 'Exam Admin', 'examadmin@example.com', '2026-09-06 03:44:07', '$2y$12$g09uwuwVaDZs6QRYYqI9vOaO.9.E5uDloZUkOQenUOB0urJhdP3MK', NULL, '2026-09-06 03:44:07', '2026-09-06 03:44:07', NULL),
(3, 'Exam Admin (legacy)', 'exam@example.com', '2026-09-06 03:44:08', '$2y$12$AjpqC6mUKoWJcYwqc1PmGOFkQQlE8TXzgJGNx6vcacHqFMdU2qN6G', NULL, '2026-09-06 03:44:08', '2026-09-06 03:44:08', NULL),
(4, 'Invigilator', 'invigilator@example.com', '2026-09-06 03:44:08', '$2y$12$4NSZrV3go1nRLfm1QztKyeXdn/QYqXSIjg3sHKPzXpoC9.QAquhQW', NULL, '2026-09-06 03:44:08', '2026-09-06 03:44:08', NULL),
(5, 'Reviewer', 'reviewer@example.com', '2026-09-06 03:44:08', '$2y$12$sfoMLbgqHAulh83s7DLyDOIyjHt79MmCiZy3PjaM2jWAu/zXhcycK', NULL, '2026-09-06 03:44:08', '2026-09-06 03:44:08', NULL),
(6, 'Auditor', 'auditor@example.com', '2026-09-06 03:44:08', '$2y$12$WdaaPf2z/RteQgSHJfxabewyzROSoFY6nAgTeyOSxH/JgTvNz7iDC', NULL, '2026-09-06 03:44:08', '2026-09-06 03:44:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `video_assets`
--

CREATE TABLE `video_assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_session_id` bigint(20) UNSIGNED NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) NOT NULL,
  `duration_seconds` double DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `fps` double DEFAULT NULL,
  `codec` varchar(50) DEFAULT NULL,
  `checksum_sha256` varchar(64) DEFAULT NULL,
  `validation_status` enum('pending','valid','invalid') NOT NULL DEFAULT 'pending',
  `validation_error` text DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analysis_jobs`
--
ALTER TABLE `analysis_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `analysis_jobs_video_asset_id_foreign` (`video_asset_id`),
  ADD KEY `analysis_jobs_camera_source_id_foreign` (`camera_source_id`),
  ADD KEY `analysis_jobs_model_version_id_foreign` (`model_version_id`),
  ADD KEY `analysis_jobs_created_by_foreign` (`created_by`),
  ADD KEY `analysis_jobs_exam_session_id_status_index` (`exam_session_id`,`status`),
  ADD KEY `analysis_jobs_remote_job_id_index` (`remote_job_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_actor_id_action_index` (`actor_id`,`action`),
  ADD KEY `audit_logs_target_type_target_id_index` (`target_type`,`target_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `camera_sources`
--
ALTER TABLE `camera_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camera_sources_created_by_foreign` (`created_by`),
  ADD KEY `camera_sources_exam_session_id_source_type_index` (`exam_session_id`,`source_type`);

--
-- Indexes for table `detection_events`
--
ALTER TABLE `detection_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detection_events_model_version_id_foreign` (`model_version_id`),
  ADD KEY `detection_events_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `detection_events_exam_session_id_event_type_index` (`exam_session_id`,`event_type`),
  ADD KEY `detection_events_analysis_job_id_review_status_index` (`analysis_job_id`,`review_status`);

--
-- Indexes for table `event_evidence`
--
ALTER TABLE `event_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_evidence_detection_event_id_foreign` (`detection_event_id`);

--
-- Indexes for table `exam_rooms`
--
ALTER TABLE `exam_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_rooms_name_unique` (`name`),
  ADD KEY `exam_rooms_name_index` (`name`);

--
-- Indexes for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_sessions_created_by_foreign` (`created_by`),
  ADD KEY `exam_sessions_exam_room_id_status_index` (`exam_room_id`,`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_versions`
--
ALTER TABLE `model_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `model_versions_name_version_unique` (`name`,`version`),
  ADD UNIQUE KEY `model_versions_checksum_sha256_unique` (`checksum_sha256`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_role_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `processing_metrics`
--
ALTER TABLE `processing_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `processing_metrics_analysis_job_id_unique` (`analysis_job_id`);

--
-- Indexes for table `retention_actions`
--
ALTER TABLE `retention_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `retention_actions_actor_id_foreign` (`actor_id`);

--
-- Indexes for table `review_decisions`
--
ALTER TABLE `review_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_decisions_detection_event_id_foreign` (`detection_event_id`),
  ADD KEY `review_decisions_exam_session_id_foreign` (`exam_session_id`),
  ADD KEY `review_decisions_reviewed_by_foreign` (`reviewed_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`role_id`,`user_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `video_assets`
--
ALTER TABLE `video_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `video_assets_stored_filename_unique` (`stored_filename`),
  ADD KEY `video_assets_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `video_assets_exam_session_id_validation_status_index` (`exam_session_id`,`validation_status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analysis_jobs`
--
ALTER TABLE `analysis_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camera_sources`
--
ALTER TABLE `camera_sources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detection_events`
--
ALTER TABLE `detection_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_evidence`
--
ALTER TABLE `event_evidence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_rooms`
--
ALTER TABLE `exam_rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `model_versions`
--
ALTER TABLE `model_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `processing_metrics`
--
ALTER TABLE `processing_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retention_actions`
--
ALTER TABLE `retention_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_decisions`
--
ALTER TABLE `review_decisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `video_assets`
--
ALTER TABLE `video_assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analysis_jobs`
--
ALTER TABLE `analysis_jobs`
  ADD CONSTRAINT `analysis_jobs_camera_source_id_foreign` FOREIGN KEY (`camera_source_id`) REFERENCES `camera_sources` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `analysis_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analysis_jobs_exam_session_id_foreign` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analysis_jobs_model_version_id_foreign` FOREIGN KEY (`model_version_id`) REFERENCES `model_versions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `analysis_jobs_video_asset_id_foreign` FOREIGN KEY (`video_asset_id`) REFERENCES `video_assets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `camera_sources`
--
ALTER TABLE `camera_sources`
  ADD CONSTRAINT `camera_sources_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `camera_sources_exam_session_id_foreign` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `detection_events`
--
ALTER TABLE `detection_events`
  ADD CONSTRAINT `detection_events_analysis_job_id_foreign` FOREIGN KEY (`analysis_job_id`) REFERENCES `analysis_jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detection_events_exam_session_id_foreign` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detection_events_model_version_id_foreign` FOREIGN KEY (`model_version_id`) REFERENCES `model_versions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detection_events_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_evidence`
--
ALTER TABLE `event_evidence`
  ADD CONSTRAINT `event_evidence_detection_event_id_foreign` FOREIGN KEY (`detection_event_id`) REFERENCES `detection_events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD CONSTRAINT `exam_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_sessions_exam_room_id_foreign` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `processing_metrics`
--
ALTER TABLE `processing_metrics`
  ADD CONSTRAINT `processing_metrics_analysis_job_id_foreign` FOREIGN KEY (`analysis_job_id`) REFERENCES `analysis_jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retention_actions`
--
ALTER TABLE `retention_actions`
  ADD CONSTRAINT `retention_actions_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `review_decisions`
--
ALTER TABLE `review_decisions`
  ADD CONSTRAINT `review_decisions_detection_event_id_foreign` FOREIGN KEY (`detection_event_id`) REFERENCES `detection_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_decisions_exam_session_id_foreign` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_decisions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_assets`
--
ALTER TABLE `video_assets`
  ADD CONSTRAINT `video_assets_exam_session_id_foreign` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_assets_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

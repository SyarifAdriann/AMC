CREATE TABLE `aircraft_details` (
  `registration` varchar(10) NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `category` varchar(20) NOT NULL COMMENT 'Commercial, Cargo, Charter',
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `aircraft_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration` varchar(10) NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `on_block_time` varchar(50) DEFAULT NULL COMMENT 'Stores user input like "1430" or "EX RON". Parsed by backend.',
  `off_block_time` varchar(50) DEFAULT NULL COMMENT 'Stores user input like "1500". Parsed by backend.',
  `parking_stand` varchar(20) NOT NULL,
  `from_location` varchar(50) DEFAULT NULL,
  `to_location` varchar(50) DEFAULT NULL,
  `flight_no_arr` varchar(20) DEFAULT NULL,
  `flight_no_dep` varchar(20) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_ron` tinyint(1) NOT NULL DEFAULT 0,
  `ron_complete` tinyint(1) NOT NULL DEFAULT 0,
  `movement_date` date NOT NULL,
  `user_id_created` bigint(20) UNSIGNED NOT NULL,
  `user_id_updated` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `on_block_date` date DEFAULT NULL,
  `off_block_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'e.g., CREATE_MOVEMENT, UPDATE_USER',
  `target_table` varchar(50) NOT NULL,
  `target_id` bigint(20) UNSIGNED NOT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON-encoded old data',
  `new_values` text NOT NULL COMMENT 'JSON-encoded new data',
  `action_timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `daily_staff_roster` (
  `id` int(11) NOT NULL,
  `roster_date` date NOT NULL,
  `shift` varchar(50) NOT NULL,
  `updated_by_user_id` int(11) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `aerodrome_code` varchar(10) DEFAULT NULL,
  `day_shift_staff_1` varchar(100) DEFAULT NULL,
  `day_shift_staff_2` varchar(100) DEFAULT NULL,
  `day_shift_staff_3` varchar(100) DEFAULT NULL,
  `night_shift_staff_1` varchar(100) DEFAULT NULL,
  `night_shift_staff_2` varchar(100) DEFAULT NULL,
  `night_shift_staff_3` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `flight_references` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `flight_no` varchar(20) NOT NULL,
  `default_route` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username_attempted` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `narrative_logbook_amc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `shift` varchar(20) NOT NULL,
  `log_time` time NOT NULL,
  `activity_description` text NOT NULL,
  `entered_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stand_name` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL COMMENT 'e.g., Main Apron, South Apron, HGR',
  `x_coord` int(11) DEFAULT NULL COMMENT 'X coordinate for rendering. NULL for logical containers like Hangar.',
  `y_coord` int(11) DEFAULT NULL COMMENT 'Y coordinate for rendering. NULL for logical containers like Hangar.',
  `capacity` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of aircraft the stand can hold. >1 for Hangar.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT 'e.g., admin, operator, viewer',
  `status` enum('active','suspended') DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `aircraft_details`
  ADD PRIMARY KEY (`registration`);

ALTER TABLE `aircraft_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aircraft_movements_registration_index` (`registration`),
  ADD KEY `aircraft_movements_movement_date_index` (`movement_date`),
  ADD KEY `aircraft_movements_parking_stand_index` (`parking_stand`),
  ADD KEY `aircraft_movements_user_id_created_foreign` (`user_id_created`),
  ADD KEY `aircraft_movements_user_id_updated_foreign` (`user_id_updated`),
  ADD KEY `idx_on_block_date` (`on_block_date`),
  ADD KEY `idx_off_block_date` (`off_block_date`),
  ADD KEY `idx_ron_complete` (`ron_complete`),
  ADD KEY `idx_movement_date_ron` (`movement_date`,`is_ron`,`ron_complete`);

ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_log_user_id_foreign` (`user_id`);

ALTER TABLE `daily_staff_roster`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `flight_references`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flight_references_flight_no_index` (`flight_no`);

ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

ALTER TABLE `narrative_logbook_amc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `narrative_logbook_amc_entered_by_user_id_foreign` (`entered_by_user_id`);

ALTER TABLE `stands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stands_stand_name_unique` (`stand_name`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

ALTER TABLE `aircraft_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

ALTER TABLE `daily_staff_roster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `flight_references`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `narrative_logbook_amc`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `aircraft_movements`
  ADD CONSTRAINT `aircraft_movements_user_id_created_foreign` FOREIGN KEY (`user_id_created`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `aircraft_movements_user_id_updated_foreign` FOREIGN KEY (`user_id_updated`) REFERENCES `users` (`id`);

ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `narrative_logbook_amc`
  ADD CONSTRAINT `narrative_logbook_amc_entered_by_user_id_foreign` FOREIGN KEY (`entered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `packages` ADD `profile_viewers_view` INT NOT NULL AFTER `contact`;
ALTER TABLE `members` ADD `remaining_profile_viewer_view` INT NOT NULL DEFAULT '0' AFTER `remaining_contact_view`;

-- Who viewed profile
CREATE TABLE `profile_viewers` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `viewed_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `profile_viewers`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `profile_viewers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

-- Additional Attributes
CREATE TABLE `additional_attributes` (
  `id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `title` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `additional_attributes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `additional_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Additional Member Info
CREATE TABLE `additional_member_infos` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `additional_attribute_id` int(11) NOT NULL,
  `value` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `additional_member_infos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `additional_member_infos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

INSERT INTO `settings` (`id`, `type`, `value`, `created_at`, `updated_at`, `deleted_at`) 
VALUES
(NULL, 'additional_profile_section', NULL, current_timestamp(), current_timestamp(), NULL),
(NULL, 'additional_profile_section_name', 'Additional Profile Section', current_timestamp(), current_timestamp(), NULL),
(NULL, 'additional_profile_section_icon', NULL, current_timestamp(), current_timestamp(), NULL);

INSERT INTO `permissions` (`id`, `name`, `parent`, `guard_name`, `created_at`, `updated_at`)
VALUES
(NULL, 'show_additional_profile_attributes', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'add_additional_profile_attributes', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'edit_additional_profile_attributes', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'additional_profile_section_settings', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL);

ALTER TABLE `currencies` CHANGE `exchange_rate` `exchange_rate` DOUBLE(10,5) NULL DEFAULT '1';

UPDATE `settings` SET `value` = '5.1' WHERE `settings`.`type` = 'current_version';

COMMIT;
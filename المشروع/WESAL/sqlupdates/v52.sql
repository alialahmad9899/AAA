CREATE TABLE `annual_salary_ranges` (
  `id` int(11) NOT NULL,
  `min_salary` double(100,2) NOT NULL,
  `max_salary` double(100,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `annual_salary_ranges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `annual_salary_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `families` 
ADD `father_occupation` VARCHAR(100) NULL AFTER `father`,
ADD `mother_occupation` VARCHAR(100) NULL AFTER `mother`,
ADD `no_of_sisters` INT NULL AFTER `sibling`, 
ADD `no_of_brothers` INT NULL AFTER `no_of_sisters`,
ADD `about_parents` TEXT NULL AFTER `no_of_brothers`, 
ADD `about_siblings` TEXT NULL AFTER `about_parents`, 
ADD `about_relatives` TEXT NULL AFTER `about_siblings`;

ALTER TABLE `education` ADD `is_highest_degree` TINYINT(1) NOT NULL DEFAULT '0' AFTER `present`;
ALTER TABLE `members` ADD `annual_salary_range_id` INT NULL AFTER `on_behalves_id`;

INSERT INTO `permissions` (`id`, `name`, `parent`, `guard_name`, `created_at`, `updated_at`)
VALUES
(NULL, 'show_annual_salary_ranges', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'add_annual_salary_ranges', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'edit_annual_salary_ranges', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'delete_annual_salary_ranges', 'profile_attributes', 'web', '2022-07-27 11:28:21', NULL);


UPDATE `settings` SET `value` = '5.2' WHERE `settings`.`type` = 'current_version';
COMMIT;
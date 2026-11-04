ALTER TABLE `personal_access_tokens` ADD `expires_at` TIMESTAMP NULL AFTER `last_used_at`;
INSERT INTO `permissions` (`id`, `name`, `parent`, `guard_name`, `created_at`, `updated_at`)
VALUES
(NULL, 'pending_member_show', 'member', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'approved_member_show', 'member', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'blocked_member_show', 'member', 'web', '2022-07-27 11:28:21', NULL),
(NULL, 'deactvated_member_show', 'member', 'web', '2022-07-27 11:28:21', NULL);

INSERT INTO `settings` (`id`, `type`, `value`, `created_at`, `updated_at`, `deleted_at`)
VALUES
(NULL, 'disable_image_optimization', '0', current_timestamp(), current_timestamp(), NULL);
UPDATE `settings` SET `value` = '5.3' WHERE `settings`.`type` = 'current_version';
COMMIT;
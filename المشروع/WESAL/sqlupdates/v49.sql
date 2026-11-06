ALTER TABLE `currencies` CHANGE `exchange_rate` `exchange_rate` DOUBLE(10,5) NULL DEFAULT '0.00';

INSERT INTO `settings` (`id`, `type`, `value`, `created_at`, `updated_at`, `deleted_at`) 
    VALUES 
(NULL, 'verification_form', '[{\"type\":\"text\",\"label\":\"Your name\"}]', current_timestamp(), current_timestamp(), NULL);

ALTER TABLE `users` ADD `verification_info` LONGTEXT NULL DEFAULT NULL AFTER `approved`;

UPDATE `email_templates` SET `identifier` = 'member_verification_email', `subject` = 'Member Verification', `body` = '<p>Hi [[name]],\r\n</p><p>Your account verification has been [[status]]</p>\r\n</p><p>Please contact the&nbsp;administration&nbsp;team if you have any further questions. Best wishes.\r\n</p><p>Thanks,\r\n</p><p>[[from]]</p>' WHERE `email_templates`.`id` = 3;

UPDATE `settings` SET `type` = 'member_verification' WHERE `settings`.`type` = 'member_approval_by_admin';

INSERT INTO `permissions` (`id`, `name`, `parent`, `guard_name`, `created_at`, `updated_at`)
VALUES
(NULL, 'manage_member_verification_form', 'settings', 'web', '2022-07-27 11:28:21', NULL);

UPDATE `settings` SET `value` = '4.9' WHERE `settings`.`type` = 'current_version';

COMMIT;
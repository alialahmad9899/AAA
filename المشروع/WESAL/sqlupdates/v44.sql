ALTER TABLE `partner_expectations`
CHANGE `height` `height` DOUBLE(3,2) NULL DEFAULT NULL, 
CHANGE `weight` `weight` DOUBLE(5,2) NULL DEFAULT NULL;

INSERT INTO `settings` (`id`, `type`, `value`, `created_at`, `updated_at`, `deleted_at`) 
    VALUES (NULL, 'home_slider_images_small', NULL, current_timestamp(), current_timestamp(), NULL);

UPDATE `settings` SET `value` = '4.4' WHERE `settings`.`type` = 'current_version';

COMMIT;
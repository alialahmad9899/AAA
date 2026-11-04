
CREATE TABLE `registration_verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `code` text NOT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `email_templates` (`id`, `identifier`, `subject`, `body`, `status`, `created_at`, `updated_at`) 
VALUES 

(null, 'email_registration_verification', 'Email Verification for Registration on [[site_name]]', '<p>Thank you for choosing [[site_name]]! We are thrilled to welcome you to our community.</p> 

<p>Your email verification code is  <b>[[code]]</b></p>

<p>Please contact the administration team if you have any further questions.</p>

<p>Thanks,<br>
[[from]]</p>', 1, '2021-12-14 10:05:51', '2021-12-14 10:08:14');


UPDATE `settings` SET `value` = '5.5' WHERE `settings`.`type` = 'current_version';
COMMIT;
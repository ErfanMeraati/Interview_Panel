

CREATE TABLE `applications` (
  `id` int NOT NULL,
  `full_name` varchar(190) COLLATE utf8mb4_persian_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_persian_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_persian_ci NOT NULL,
  `national_id` varchar(20) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `birth_jalali` varchar(30) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `position` varchar(190) COLLATE utf8mb4_persian_ci NOT NULL,
  `experience` varchar(60) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_persian_ci,
  `photo_path` varchar(255) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `resume_original_name` varchar(255) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `status` enum('جدید','در حال بررسی','پذیرفته شد','رد شد') COLLATE utf8mb4_persian_ci NOT NULL DEFAULT 'جدید',
  `admin_note` text COLLATE utf8mb4_persian_ci,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at_jalali` varchar(30) COLLATE utf8mb4_persian_ci DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_time` time DEFAULT NULL,
  `interview_status` enum('none','scheduled','done','cancelled') COLLATE utf8mb4_persian_ci DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

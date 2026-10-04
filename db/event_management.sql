-- create database
CREATE DATABASE IF NOT EXISTS event_management_csc382 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE u741730784_EMCSC382;

-- users (optional: for organizers/admin)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','organizer','attendee') NOT NULL DEFAULT 'attendee',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- events
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME,
  capacity INT DEFAULT 0,
  image_filename VARCHAR(255),
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- registrations
CREATE TABLE registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_name VARCHAR(150) NOT NULL,
  user_email VARCHAR(255) NOT NULL,
  seats INT DEFAULT 1,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sample data
-- ملاحظة: يجب استبدال <hash-placeholder> بقيمة password_hash('yourpassword', PASSWORD_DEFAULT)
-- مثال: يمكنك تشغيل هذا في PHP: echo password_hash('admin123', PASSWORD_DEFAULT);
-- ثم نسخ النتيجة ولصقها في INSERT أدناه

INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- كلمة المرور الافتراضية: password (يجب تغييرها في بيئة الإنتاج)

-- بيانات تجريبية للفعاليات
INSERT INTO events (title, description, location, start_datetime, end_datetime, capacity, created_by) VALUES
('ورشة عمل تطوير الويب', 'ورشة عملية لتعلم أساسيات تطوير الويب باستخدام HTML, CSS, JavaScript', 'قاعة المؤتمرات - المبنى الرئيسي', '2024-12-20 10:00:00', '2024-12-20 14:00:00', 30, 1),
('محاضرة الذكاء الاصطناعي', 'محاضرة عن أحدث تطورات الذكاء الاصطناعي وتطبيقاته', 'المدرج الكبير', '2024-12-25 15:00:00', '2024-12-25 17:00:00', 100, 1),
('معرض المشاريع الطلابية', 'عرض للمشاريع المتميزة لطلاب الكلية', 'صالة المعارض', '2024-12-28 09:00:00', '2024-12-28 16:00:00', 200, 1);


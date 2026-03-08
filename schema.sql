-- Madrasa Management Portal SQL Schema (MySQL 8+ compatible)
CREATE TABLE IF NOT EXISTS madrasas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  location VARCHAR(120) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  madrasa_id INT DEFAULT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Admin','Teacher','Viewer') NOT NULL DEFAULT 'Viewer',
  full_name VARCHAR(120) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_uid VARCHAR(30) NOT NULL UNIQUE,
  register_no VARCHAR(120) DEFAULT NULL UNIQUE,
  madrasa_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  class_name VARCHAR(40) NOT NULL,
  gender ENUM('Boy','Girl') NOT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  attendance_percent DECIMAL(5,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  madrasa_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  subject_name VARCHAR(80) NOT NULL,
  class_name VARCHAR(40) NOT NULL,
  attendance_percent DECIMAL(5,2) DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  madrasa_id INT NOT NULL,
  exam_name VARCHAR(80) NOT NULL,
  exam_type ENUM('Midterm','Annual') NOT NULL,
  exam_date DATE DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  student_id INT NOT NULL,
  marks_math INT DEFAULT 0,
  marks_science INT DEFAULT 0,
  marks_english INT DEFAULT 0,
  total_marks INT DEFAULT 0,
  grade VARCHAR(4) DEFAULT 'F',
  rank_position INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mark_edit_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  result_id INT NOT NULL,
  edited_by INT DEFAULT NULL,
  old_total INT DEFAULT NULL,
  new_total INT DEFAULT NULL,
  edit_note VARCHAR(255) DEFAULT NULL,
  edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE CASCADE,
  FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  madrasa_id INT NOT NULL,
  title VARCHAR(160) NOT NULL,
  body TEXT NOT NULL,
  is_important TINYINT(1) DEFAULT 0,
  expiry_date DATE DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attendance_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  madrasa_id INT NOT NULL,
  student_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('Present','Absent') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_student_day (student_id, attendance_date),
  FOREIGN KEY (madrasa_id) REFERENCES madrasas(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Seed 13 madrasas
INSERT IGNORE INTO madrasas (id, name, location) VALUES
(1, 'Noorul Huda Madrasa', 'Malappuram'),
(2, 'Darul Uloom Central', 'Kozhikode'),
(3, 'Falah Islamic Academy', 'Kannur'),
(4, 'Rahmaniya Madrasa', 'Palakkad'),
(5, 'Sirajul Islam Madrasa', 'Thrissur'),
(6, 'Hidayathul Quran Center', 'Ernakulam'),
(7, 'Anwarul Islam Madrasa', 'Kottayam'),
(8, 'Nadwath Students Campus', 'Idukki'),
(9, 'Ameenul Uloom Madrasa', 'Pathanamthitta'),
(10, 'Thajul Huda School', 'Alappuzha'),
(11, 'Badria Dars', 'Kollam'),
(12, 'Misbahul Hudha', 'Wayanad'),
(13, 'Najathul Islam Madrasa', 'Kasaragod');


CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT
);

CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(120) NOT NULL UNIQUE,
  subject_name VARCHAR(180) NOT NULL,
  max_mark DECIMAL(8,2) DEFAULT 50,
  pass_mark DECIMAL(8,2) DEFAULT 18,
  display_order INT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS marks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT DEFAULT 0,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  mark DECIMAL(8,2) DEFAULT 0,
  UNIQUE KEY uk_exam_student_subject (exam_id, student_id, subject_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

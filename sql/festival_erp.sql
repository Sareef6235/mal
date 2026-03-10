-- Festival ERP Support Schema (MySQL/SQLite compatible reference)
CREATE TABLE IF NOT EXISTS festivals (
  id INTEGER PRIMARY KEY,
  festival_name VARCHAR(191) NOT NULL,
  festival_type VARCHAR(80) NOT NULL,
  year INTEGER NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status VARCHAR(30) DEFAULT 'Active',
  is_active INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_categories (
  id INTEGER PRIMARY KEY,
  category_name VARCHAR(80) UNIQUE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_events (
  id INTEGER PRIMARY KEY,
  event_name VARCHAR(191) NOT NULL,
  festival_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  max_score DECIMAL(8,2) DEFAULT 100,
  event_date DATE NULL,
  venue VARCHAR(191) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS houses (
  id INTEGER PRIMARY KEY,
  house_name VARCHAR(80) UNIQUE NOT NULL,
  color_code VARCHAR(20) DEFAULT '#2563eb',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_participants (
  id INTEGER PRIMARY KEY,
  participant_type VARCHAR(20) DEFAULT 'student',
  name VARCHAR(191) NOT NULL,
  register_no VARCHAR(120) NULL,
  class_name VARCHAR(80) NULL,
  gender VARCHAR(20) NULL,
  event_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  house_id INTEGER NULL,
  qr_token VARCHAR(80) UNIQUE,
  checked_in INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_judges (
  id INTEGER PRIMARY KEY,
  judge_name VARCHAR(191) NOT NULL,
  festival_id INTEGER NOT NULL,
  event_id INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_scores (
  id INTEGER PRIMARY KEY,
  participant_id INTEGER NOT NULL,
  event_id INTEGER NOT NULL,
  judge_id INTEGER NOT NULL,
  score DECIMAL(8,2) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS house_points (
  id INTEGER PRIMARY KEY,
  house_id INTEGER NOT NULL,
  participant_id INTEGER NOT NULL,
  event_id INTEGER NOT NULL,
  points INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO festival_categories (category_name) VALUES
('Boys'),('Girls'),('General'),('LP'),('UP'),('HS'),('Open');

INSERT INTO houses (house_name,color_code) VALUES
('Green House','#16a34a'),('Blue House','#2563eb'),('Red House','#dc2626'),('Yellow House','#ca8a04');

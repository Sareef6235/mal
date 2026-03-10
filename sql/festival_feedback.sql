-- Festival Feedback & Comment System
CREATE TABLE IF NOT EXISTS festival_comments (
  id INTEGER PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NULL,
  role VARCHAR(30) NOT NULL,
  festival_id INTEGER NOT NULL,
  event_id INTEGER NULL,
  rating INTEGER DEFAULT 0,
  comment TEXT NOT NULL,
  is_anonymous INTEGER DEFAULT 0,
  status VARCHAR(20) DEFAULT 'pending',
  admin_reply TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS festival_comment_reactions (
  id INTEGER PRIMARY KEY,
  comment_id INTEGER NOT NULL,
  reaction_type VARCHAR(20) NOT NULL,
  react_key VARCHAR(64) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

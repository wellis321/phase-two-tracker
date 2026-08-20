-- Phase 2 Delivery Tracker — schema
--
-- Run this against the SAME database sor-system uses (sor_management by
-- default). It only adds pm_* tables — it does NOT create `users` or
-- `login_attempts`; those already exist there and this app authenticates
-- against them directly. Run sor-system's own sql/schema.sql first if this
-- is a brand new database.

CREATE TABLE IF NOT EXISTS pm_tasks (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(255)  NOT NULL,
  description   TEXT,
  assignee_user_id INT UNSIGNED,
  status        ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
  due_date      DATE,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status),
  KEY idx_assignee (assignee_user_id),
  CONSTRAINT fk_pm_tasks_assignee FOREIGN KEY (assignee_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pm_tasks_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pm_risks_issues (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  type          ENUM('risk','issue') NOT NULL DEFAULT 'risk',
  title         VARCHAR(255)  NOT NULL,
  description   TEXT,
  severity      ENUM('red','amber','green') NOT NULL DEFAULT 'amber',
  status        ENUM('open','mitigated','closed') NOT NULL DEFAULT 'open',
  owner_user_id INT UNSIGNED,
  raised_date   DATE NOT NULL,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status),
  KEY idx_severity (severity),
  CONSTRAINT fk_pm_risks_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pm_risks_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pm_decisions (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(255)  NOT NULL,
  description   TEXT,
  needed_by_date DATE,
  decision_owner_user_id INT UNSIGNED,
  status        ENUM('open','decided') NOT NULL DEFAULT 'open',
  outcome       TEXT,
  decided_date  DATE,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status),
  CONSTRAINT fk_pm_decisions_owner FOREIGN KEY (decision_owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pm_decisions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pm_supplier_activities (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  supplier      VARCHAR(100)  NOT NULL DEFAULT 'ROCC',
  title         VARCHAR(255)  NOT NULL,
  description   TEXT,
  status        ENUM('planned','in_progress','complete','blocked') NOT NULL DEFAULT 'planned',
  due_date      DATE,
  owner_user_id INT UNSIGNED,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status),
  CONSTRAINT fk_pm_supplier_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pm_supplier_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pm_milestones (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(255)  NOT NULL,
  phase         VARCHAR(100),
  target_date   DATE,
  status        ENUM('upcoming','at_risk','complete') NOT NULL DEFAULT 'upcoming',
  notes         TEXT,
  created_by    INT UNSIGNED,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_target_date (target_date),
  KEY idx_status (status),
  CONSTRAINT fk_pm_milestones_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The weekly (or whatever cadence you choose) archive. One row per update —
-- the dashboard always shows the most recent row, so the cadence is a habit,
-- not something baked into the schema.
CREATE TABLE IF NOT EXISTS pm_weekly_snapshots (
  id                  INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  period_label        VARCHAR(150)  NOT NULL,
  overall_status      ENUM('red','amber','green') NOT NULL DEFAULT 'green',
  current_focus       TEXT,
  progress_narrative  TEXT,
  achievements        TEXT,
  key_decisions       TEXT,
  risks_raised        TEXT,
  lessons_learned     TEXT,
  looking_ahead_notes TEXT,
  created_by_user_id  INT UNSIGNED,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created_at (created_at),
  CONSTRAINT fk_pm_snapshots_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- task_id depends on depends_on_id (task_id is blocked until depends_on_id is done).
CREATE TABLE IF NOT EXISTS pm_task_dependencies (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id        INT UNSIGNED NOT NULL,
  depends_on_id  INT UNSIGNED NOT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_dependency (task_id, depends_on_id),
  KEY idx_task (task_id),
  KEY idx_depends_on (depends_on_id),
  CONSTRAINT fk_pm_task_deps_task FOREIGN KEY (task_id) REFERENCES pm_tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_task_deps_depends_on FOREIGN KEY (depends_on_id) REFERENCES pm_tasks(id) ON DELETE CASCADE,
  CONSTRAINT chk_pm_task_deps_not_self CHECK (task_id != depends_on_id)
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

-- Meeting agendas. Generated from live data (status, decisions, risks,
-- milestones) into `content` as one editable text block, then published —
-- an archived record of what was actually put in front of each meeting.
CREATE TABLE IF NOT EXISTS pm_agendas (
  id                  INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  title               VARCHAR(150)  NOT NULL,
  meeting_date        DATE,
  location            VARCHAR(150),
  content             TEXT NOT NULL,
  created_by_user_id  INT UNSIGNED,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created_at (created_at),
  CONSTRAINT fk_pm_agendas_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per person invited to a meeting. user_id links to a real account
-- when the attendee is internal (so attendance can be aggregated per person
-- over time); external attendees (suppliers etc.) just have a name. `name`
-- is always stored as a snapshot, even for linked users, so a later rename
-- or deactivation doesn't rewrite what a past agenda actually showed.
CREATE TABLE IF NOT EXISTS pm_agenda_attendees (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agenda_id   INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NULL,
  name        VARCHAR(150) NOT NULL,
  status      ENUM('attending','apologies') NOT NULL DEFAULT 'attending',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_agenda (agenda_id),
  KEY idx_user (user_id),
  CONSTRAINT fk_pm_agenda_attendees_agenda FOREIGN KEY (agenda_id) REFERENCES pm_agendas(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_agenda_attendees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-defined tags, freely nestable (parent_id NULL = top-level, e.g. a
-- "System" tag with "ROCC"/"NECH"/"APEX" nested underneath it). Uniqueness
-- of a tag's name among its siblings is enforced in application code (see
-- tag_name_taken() in includes/functions.php) rather than a DB constraint,
-- since MySQL unique indexes don't treat NULL parent_id values as equal.
-- taggable_type/taggable_id on pm_taggables is a generic pointer so tags can
-- attach to any pm_* record, not just tasks.
CREATE TABLE IF NOT EXISTS pm_tags (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  parent_id     INT UNSIGNED  NULL,
  name          VARCHAR(100)  NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_parent (parent_id),
  CONSTRAINT fk_pm_tags_parent FOREIGN KEY (parent_id) REFERENCES pm_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Free-form custom fields on a tag (e.g. "Address" -> "2 Spiersbridge Way").
-- Fields can themselves nest under a parent field (parent_field_id), so a
-- composite value like Address can be broken into Street/City/Postcode as
-- their own queryable rows instead of one flattened blob.
CREATE TABLE IF NOT EXISTS pm_tag_fields (
  id                INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  tag_id            INT UNSIGNED  NOT NULL,
  parent_field_id   INT UNSIGNED  NULL,
  field_name        VARCHAR(100)  NOT NULL,
  field_value       TEXT,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tag (tag_id),
  KEY idx_parent_field (parent_field_id),
  CONSTRAINT fk_pm_tag_fields_tag FOREIGN KEY (tag_id) REFERENCES pm_tags(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_tag_fields_parent FOREIGN KEY (parent_field_id) REFERENCES pm_tag_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pm_taggables (
  tag_id          INT UNSIGNED NOT NULL,
  taggable_type   VARCHAR(20)  NOT NULL,
  taggable_id     INT UNSIGNED NOT NULL,
  PRIMARY KEY (tag_id, taggable_type, taggable_id),
  KEY idx_taggable (taggable_type, taggable_id),
  CONSTRAINT fk_pm_taggables_tag FOREIGN KEY (tag_id) REFERENCES pm_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Anything a team member has flagged as worth discussing together. One row
-- per flagged record (flaggable_type/flaggable_id, same polymorphic pattern
-- as pm_taggables); who flagged it lives in pm_discussion_flags below, so
-- several people flagging the same thing stack onto one item rather than
-- creating duplicates. `note` is a shared, editable summary of what the
-- discussion is actually about — expected to change as understanding does.
-- Once the team's discussed it, `status` moves to added_to_agenda so it's
-- picked up by the next agenda draft; `agenda_id` is then set when that
-- agenda is actually published, recording which meeting it went to.
CREATE TABLE IF NOT EXISTS pm_discussion_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flaggable_type  VARCHAR(20)  NOT NULL,
  flaggable_id    INT UNSIGNED NOT NULL,
  note            TEXT NULL,
  status          ENUM('open', 'added_to_agenda') NOT NULL DEFAULT 'open',
  agenda_id       INT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_flaggable (flaggable_type, flaggable_id),
  KEY idx_status (status),
  CONSTRAINT fk_pm_discussion_items_agenda FOREIGN KEY (agenda_id) REFERENCES pm_agendas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Who flagged a discussion item — their initials show against it, and
-- unflagging just removes their row rather than the whole item.
CREATE TABLE IF NOT EXISTS pm_discussion_flags (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discussion_item_id   INT UNSIGNED NOT NULL,
  user_id              INT UNSIGNED NOT NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_flag (discussion_item_id, user_id),
  CONSTRAINT fk_pm_discussion_flags_item FOREIGN KEY (discussion_item_id) REFERENCES pm_discussion_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_discussion_flags_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Run once against the existing evs_drills database.
-- This preserves existing drill_history rows while enabling point details.
USE evs_drills;

ALTER TABLE drill_history
  ADD COLUMN detail_json JSON NULL;
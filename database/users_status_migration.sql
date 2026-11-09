-- =============================================================
-- USER STATUS (activate / deactivate)
-- Adds is_active to users. Existing accounts default to active.
-- Deactivated accounts must not be able to log in.
-- =============================================================

ALTER TABLE users
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;

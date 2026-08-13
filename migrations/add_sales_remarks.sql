-- SQL migration: add remarks column to sales
ALTER TABLE sales ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL;
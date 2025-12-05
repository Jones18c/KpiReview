-- Add status field to kpiReview table for draft/published functionality
-- Run this in the MayeshApps database

ALTER TABLE kpiReview 
ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'DRAFT' AFTER submitted_by,
ADD COLUMN published_at TIMESTAMP NULL AFTER updated_at,
ADD INDEX idx_status (status);

-- Update existing records to be PUBLISHED (since they were already submitted)
UPDATE kpiReview SET status = 'PUBLISHED' WHERE status = 'DRAFT';


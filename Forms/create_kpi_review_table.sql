-- Create table for KPI Review form submissions
-- Database: MayeshApps
-- Table: kpiReview

CREATE TABLE IF NOT EXISTS kpiReview (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Basic Information
    month VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    branch_manager VARCHAR(100) NULL,
    location_name VARCHAR(100) NOT NULL,
    location_number VARCHAR(10) NOT NULL,
    submitted_by VARCHAR(100) NOT NULL,
    
    -- Positive Results / Wins - Month
    positive_month_kpi_1 VARCHAR(255) NULL,
    positive_month_comments_1 TEXT NULL,
    positive_month_kpi_2 VARCHAR(255) NULL,
    positive_month_comments_2 TEXT NULL,
    positive_month_kpi_3 VARCHAR(255) NULL,
    positive_month_comments_3 TEXT NULL,
    positive_month_other TEXT NULL,
    
    -- Positive Results / Wins - Year to Date
    positive_ytd_kpi_1 VARCHAR(255) NULL,
    positive_ytd_comments_1 TEXT NULL,
    positive_ytd_kpi_2 VARCHAR(255) NULL,
    positive_ytd_comments_2 TEXT NULL,
    positive_ytd_kpi_3 VARCHAR(255) NULL,
    positive_ytd_comments_3 TEXT NULL,
    positive_ytd_other TEXT NULL,
    
    -- Challenges / Opportunities - Month
    challenge_month_kpi_1 VARCHAR(255) NULL,
    challenge_month_comments_1 TEXT NULL,
    challenge_month_kpi_2 VARCHAR(255) NULL,
    challenge_month_comments_2 TEXT NULL,
    challenge_month_kpi_3 VARCHAR(255) NULL,
    challenge_month_comments_3 TEXT NULL,
    challenge_month_other TEXT NULL,
    
    -- Challenges / Opportunities - Year to Date
    challenge_ytd_kpi_1 VARCHAR(255) NULL,
    challenge_ytd_comments_1 TEXT NULL,
    challenge_ytd_kpi_2 VARCHAR(255) NULL,
    challenge_ytd_comments_2 TEXT NULL,
    challenge_ytd_kpi_3 VARCHAR(255) NULL,
    challenge_ytd_comments_3 TEXT NULL,
    challenge_ytd_other TEXT NULL,
    
    -- Morale Meter
    morale_meter INT NULL CHECK (morale_meter IN (1, 2, 3, 4, 5)),
    morale_notes TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for common queries
    INDEX idx_location_number (location_number),
    INDEX idx_month_year (month, year),
    INDEX idx_submitted_by (submitted_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


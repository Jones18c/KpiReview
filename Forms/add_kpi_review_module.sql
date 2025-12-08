-- Add KPI Review module to userModules table
-- This will make it appear in the Admin > Edit User module selection list

INSERT INTO userModules (moduleName, moduleDescription, allUsers) 
VALUES ('form_kpi_review', 'Manager KPI Review', '0')
ON DUPLICATE KEY UPDATE 
    moduleDescription = 'Manager KPI Review',
    allUsers = '0';

-- Note: If you want ALL users to have access by default, change '0' to '1' above
-- Setting allUsers = '1' means everyone gets access automatically
-- Setting allUsers = '0' means access must be granted individually via Admin > Edit User

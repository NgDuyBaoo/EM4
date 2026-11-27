-- mysql -u root -p < scripts/create-db.sql
CREATE DATABASE IF NOT EXISTS ngoi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS ngoi_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ngoi'@'%' IDENTIFIED BY 'ngoi_pass';
GRANT ALL PRIVILEGES ON ngoi.* TO 'ngoi'@'%';
GRANT ALL PRIVILEGES ON ngoi_test.* TO 'ngoi'@'%';
FLUSH PRIVILEGES;

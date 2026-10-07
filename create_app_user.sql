CREATE USER IF NOT EXISTS 'college_app'@'localhost'
    IDENTIFIED BY 'CollegeApp@2026';
CREATE USER IF NOT EXISTS 'college_app'@'127.0.0.1'
    IDENTIFIED BY 'CollegeApp@2026';
ALTER USER 'college_app'@'localhost'
    IDENTIFIED BY 'CollegeApp@2026';
ALTER USER 'college_app'@'127.0.0.1'
    IDENTIFIED BY 'CollegeApp@2026';
GRANT ALL PRIVILEGES ON college.* TO 'college_app'@'localhost';
GRANT ALL PRIVILEGES ON college.* TO 'college_app'@'127.0.0.1';
FLUSH PRIVILEGES;

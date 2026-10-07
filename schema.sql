CREATE DATABASE IF NOT EXISTS college
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE college;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    register_no VARCHAR(80) NOT NULL DEFAULT '',
    program VARCHAR(100) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    profile_pic VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    category VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    evidence_file VARCHAR(255) NOT NULL DEFAULT '',
    status ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
    admin_remark TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_user_id (user_id),
    KEY idx_complaints_status (status),
    KEY idx_complaints_category (category),
    CONSTRAINT fk_complaints_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional proposal-based tables kept alongside the existing UI tables.
-- These do not replace or remove the current users/complaints structure.
CREATE TABLE IF NOT EXISTS departments (
    department_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_name VARCHAR(60) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (department_id),
    UNIQUE KEY uq_departments_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    student_name VARCHAR(60) NOT NULL,
    phone VARCHAR(15) NULL,
    email VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_user (user_id),
    UNIQUE KEY uq_students_email (email),
    KEY idx_students_department (department_id),
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_students_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    admin_name VARCHAR(60) NOT NULL,
    email VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admins_user (user_id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_department (department_id),
    CONSTRAINT fk_admins_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_admins_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    description VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaint_records (
    complaint_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NOT NULL,
    complaint_title VARCHAR(100) NOT NULL,
    complaint_description VARCHAR(300) NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    priority VARCHAR(15) NOT NULL DEFAULT 'Medium',
    complaint_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    PRIMARY KEY (complaint_id),
    KEY idx_complaint_records_user (user_id),
    KEY idx_complaint_records_department (department_id),
    KEY idx_complaint_records_category (category_id),
    KEY idx_complaint_records_status (complaint_status),
    CONSTRAINT fk_complaint_records_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_complaint_records_department
        FOREIGN KEY (department_id) REFERENCES departments (department_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_complaint_records_category
        FOREIGN KEY (category_id) REFERENCES categories (category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_complaint_records_priority
        CHECK (priority IN ('Low', 'Medium', 'High'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaint_assignments (
    assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (assignment_id),
    KEY idx_assignments_complaint (complaint_id),
    KEY idx_assignments_admin (admin_id),
    CONSTRAINT fk_assignment_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaint_records (complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_assignment_admin
        FOREIGN KEY (admin_id) REFERENCES admins (admin_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_updates (
    update_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(200) NULL,
    PRIMARY KEY (update_id),
    KEY idx_status_updates_complaint (complaint_id),
    CONSTRAINT fk_status_update_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaint_records (complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments (department_name)
SELECT 'Computer Science'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'Computer Science');

INSERT INTO departments (department_name)
SELECT 'Commerce'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'Commerce');

INSERT INTO departments (department_name)
SELECT 'Management'
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'Management');

INSERT INTO categories (category_name, description)
SELECT 'Academic', 'Academic and examination-related issues'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE category_name = 'Academic');

INSERT INTO categories (category_name, description)
SELECT 'Hostel', 'Hostel and residential facilities'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE category_name = 'Hostel');

INSERT INTO categories (category_name, description)
SELECT 'Transport', 'Transport and bus facility issues'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE category_name = 'Transport');

INSERT INTO categories (category_name, description)
SELECT 'Infrastructure', 'Campus and facilities maintenance'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE category_name = 'Infrastructure');

INSERT INTO categories (category_name, description)
SELECT 'Others', 'Miscellaneous complaints'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE category_name = 'Others');

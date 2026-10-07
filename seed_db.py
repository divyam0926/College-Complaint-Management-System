"""
Seed script to populate initial demo accounts in SQLite database (college.db).
Usage: python seed_db.py
"""
import os
import sqlite3
from pathlib import Path
from werkzeug.security import generate_password_hash

BASE_DIR = Path(__file__).resolve().parent
DB_PATH = BASE_DIR / "college.db"

def seed():
    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()

    # Create tables if they do not exist
    cur.executescript("""
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        register_no TEXT NOT NULL DEFAULT '',
        program TEXT NOT NULL DEFAULT '',
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student',
        profile_pic TEXT NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS complaints (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        category TEXT NOT NULL,
        description TEXT NOT NULL,
        evidence_file TEXT NOT NULL DEFAULT '',
        status TEXT NOT NULL DEFAULT 'Pending',
        admin_remark TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS departments (
        department_id INTEGER PRIMARY KEY AUTOINCREMENT,
        department_name TEXT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        category_id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    """)

    # Seed categories
    for cat, desc in [
        ("Academic", "Academic and examination-related issues"),
        ("Hostel", "Hostel and residential facilities"),
        ("Transport", "Transport and bus facility issues"),
        ("Infrastructure", "Campus and facilities maintenance"),
        ("Others", "Miscellaneous complaints"),
    ]:
        cur.execute("INSERT OR IGNORE INTO categories (category_name, description) VALUES (?, ?)", (cat, desc))

    for dep in ["Computer Science", "Commerce", "Management"]:
        cur.execute("INSERT OR IGNORE INTO departments (department_name) VALUES (?)", (dep,))

    # Seed demo admin
    admin_pw = generate_password_hash("Admin@123")
    cur.execute("""
        INSERT OR IGNORE INTO users (name, email, register_no, program, password, role)
        VALUES ('Administrator', 'admin@kristujayanti.com', '', '', ?, 'admin')
    """, (admin_pw,))

    # Seed demo student
    student_pw = generate_password_hash("Student@123")
    cur.execute("""
        INSERT OR IGNORE INTO users (name, email, register_no, program, password, role)
        VALUES ('Demo Student', 'student@kristujayanti.com', '23CS001', 'B.Tech CSE', ?, 'student')
    """, (student_pw,))

    conn.commit()
    conn.close()
    print("Database seeded successfully!")
    print("Admin: admin@kristujayanti.com / Admin@123")
    print("Student: student@kristujayanti.com / Student@123")

if __name__ == "__main__":
    seed()

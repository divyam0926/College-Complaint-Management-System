import os
import sqlite3
from datetime import datetime
from pathlib import Path
from dotenv import load_dotenv
import mysql.connector

load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
SQLITE_DB = BASE_DIR / "college.db"

# 1. Connect to SQLite and initialize schema
s_conn = sqlite3.connect(SQLITE_DB)
s_cur = s_conn.cursor()

s_cur.executescript("""
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

# 2. Try copying data from MySQL if available
try:
    m_conn = mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "college"),
    )
    m_cur = m_conn.cursor(dictionary=True)

    # Migrate users
    m_cur.execute("SELECT * FROM users")
    users = m_cur.fetchall()
    for u in users:
        created_at_str = u["created_at"].strftime("%Y-%m-%d %H:%M:%S") if isinstance(u["created_at"], datetime) else str(u["created_at"])
        s_cur.execute("""
            INSERT OR REPLACE INTO users (id, name, email, register_no, program, password, role, profile_pic, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (u["id"], u["name"], u["email"], u["register_no"], u["program"], u["password"], u["role"], u["profile_pic"], created_at_str))

    # Migrate complaints
    m_cur.execute("SELECT * FROM complaints")
    complaints = m_cur.fetchall()
    for c in complaints:
        created_at_str = c["created_at"].strftime("%Y-%m-%d %H:%M:%S") if isinstance(c["created_at"], datetime) else str(c["created_at"])
        s_cur.execute("""
            INSERT OR REPLACE INTO complaints (id, user_id, category, description, evidence_file, status, admin_remark, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        """, (c["id"], c["user_id"], c["category"], c["description"], c["evidence_file"], c["status"], c["admin_remark"], created_at_str))

    # Migrate categories & departments
    for cat, desc in [
        ('Academic', 'Academic and examination-related issues'),
        ('Hostel', 'Hostel and residential facilities'),
        ('Transport', 'Transport and bus facility issues'),
        ('Infrastructure', 'Campus and facilities maintenance'),
        ('Others', 'Miscellaneous complaints')
    ]:
        s_cur.execute("INSERT OR IGNORE INTO categories (category_name, description) VALUES (?, ?)", (cat, desc))

    for dep in ['Computer Science', 'Commerce', 'Management']:
        s_cur.execute("INSERT OR IGNORE INTO departments (department_name) VALUES (?)", (dep,))

    s_conn.commit()
    print(f"Migration completed! Migrated {len(users)} users and {len(complaints)} complaints to SQLite ({SQLITE_DB.name}).")
    m_cur.close()
    m_conn.close()
except Exception as e:
    print(f"Notice: Could not connect to MySQL ({e}). SQLite initialized with empty/default tables.")
    s_conn.commit()
finally:
    s_cur.close()
    s_conn.close()


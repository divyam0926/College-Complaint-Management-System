# 🎓 College Complaint Management System (CCMS)

A robust, full-stack institutional grievance management portal designed for colleges and universities. The portal enables students to lodge academic, hostel, transport, and infrastructure complaints with evidence attachments, while administrators can track, update, and resolve issues through a centralized dashboard.

Built with **Python**, **Flask**, **SQLite** (standalone, zero-setup) and **MySQL** (XAMPP-compatible).

---

## ✨ Features

### 👨‍🎓 Student Portal
- **Secure Authentication**: Institutional email validation (`@kristujayanti.com`) and hashed password security.
- **Lodge Grievance**: Categorized submissions (Academic, Hostel, Transport, Infrastructure, Others) with description and file/photo evidence attachments.
- **Real-Time Status Tracking**: Live dashboard showing status badges (`Pending`, `In Progress`, `Resolved`).
- **Grievance Detail & Remarks**: View administrative feedback and action remarks on submitted complaints.
- **Profile Management**: Profile picture upload and personal academic details management.

### 🛡️ Admin Center
- **Grievance Overview & Metrics**: Real-time summary counters for Total, Pending, and Resolved complaints.
- **Complaint Review**: Detailed view of student submissions, register numbers, programs, and attached evidence.
- **Status Lifecycle & Remarks**: Update complaint status (`In Progress` / `Resolved`) and attach official remarks visible to the student.

### ⚙️ System Architecture & Portability
- **Zero-Dependency Standalone Mode**: Runs completely out of the box using built-in **SQLite** (`college.db`). **No XAMPP or MySQL server required!**
- **Dual Database Architecture**: Switch between SQLite and MySQL anytime using `.env`.
- **One-Click Launch**: Double-click `run.bat` to launch the server and open your browser automatically.

---

## 🛠️ Tech Stack

- **Backend**: Python 3, Flask 3.x, Werkzeug
- **Database**: SQLite (default, self-contained) & MySQL
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, FontAwesome 6, SweetAlert2
- **Testing**: Pytest

---

## 📁 Project Structure

```text
college/
├── assets/                  # CSS stylesheets and client JavaScript
│   ├── css/
│   └── js/
├── student/                 # Student UI templates & partials
├── admin/                   # Admin UI templates
├── uploads/                 # User-uploaded evidence and profile images
├── tests/                   # Automated unit and route tests
│   └── test_routes.py
├── app.py                   # Main Flask application & API backend
├── run.bat                  # One-click Windows starter script
├── seed_db.py               # Database seeder for demo accounts
├── schema.sql               # MySQL database schema (optional)
├── migrate_to_sqlite.py     # Script to migrate MySQL data to SQLite
├── requirements.txt         # Python package dependencies
├── .env.example             # Template environment configuration
└── README.md                # Project documentation
```

---

## 🚀 Quick Start Guide

### 1. Clone the Repository
```bash
git clone https://github.com/divyam0926/College-Complaint-Management-System.git
cd College-Complaint-Management-System
```

### 2. Set Up Virtual Environment & Dependencies
```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

### 3. Configure Environment Variables
Copy `.env.example` to `.env`:
```powershell
Copy-Item .env.example .env
```

*(By default, `DB_TYPE=sqlite` is enabled so you can run immediately without configuring any database credentials!)*

### 4. (Optional) Seed Demo Accounts
To populate demo admin and student accounts:
```powershell
python seed_db.py
```
**Demo Credentials:**
- **Admin**: `admin@kristujayanti.com` / `Admin@123`
- **Student**: `student@kristujayanti.com` / `Student@123`

### 5. Run the Application

#### Option A: One-Click (Windows)
Double-click **`run.bat`**. It will automatically start the server and open your default browser to `http://127.0.0.1:5001`.

#### Option B: Terminal Command
```powershell
python app.py
```
Then navigate to **[http://127.0.0.1:5001](http://127.0.0.1:5001)** in your browser.

---

## 🧪 Running Tests

Run the test suite using `pytest`:
```powershell
pytest
```

---

## 🔌 API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/health` | Healthcheck and active database engine info |
| `POST` | `/api/register` | Student / Admin account registration |
| `POST` | `/api/login` | Session login |
| `POST` | `/api/logout` | Session logout |
| `GET` | `/api/student/complaints` | Get authenticated student's grievances and stats |
| `POST` | `/api/student/complaints` | Submit grievance with optional evidence upload |
| `GET` | `/api/admin/complaints` | Admin complaint listing (supports `?cat=` filter) |
| `PATCH` | `/api/admin/complaints/<id>` | Update complaint status and administrative remark |
| `GET` | `/uploads/<filename>` | Serve uploaded evidence files |

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).


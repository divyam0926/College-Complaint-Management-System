import os
import re
from html import escape
from functools import wraps
from pathlib import Path
import sqlite3
from datetime import datetime

try:
    import mysql.connector
except ImportError:
    mysql = None
from dotenv import load_dotenv
from flask import (
    Flask,
    jsonify,
    redirect,
    render_template_string,
    request,
    send_from_directory,
    session,
    url_for,
)
from flask_cors import CORS
from werkzeug.security import check_password_hash, generate_password_hash
from werkzeug.utils import secure_filename

load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
UPLOAD_DIR = BASE_DIR / "uploads"
UPLOAD_DIR.mkdir(exist_ok=True)

app = Flask(__name__)
app.secret_key = os.getenv("FLASK_SECRET_KEY", "change-this-secret-key")
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024
app.config["SESSION_COOKIE_SAMESITE"] = "Lax"
CORS(
    app,
    supports_credentials=True,
    origins=[
        origin.strip()
        for origin in os.getenv(
            "FRONTEND_ORIGIN", "http://localhost,http://127.0.0.1"
        ).split(",")
        if origin.strip()
    ],
)


DB_TYPE = os.getenv("DB_TYPE", "sqlite").lower()
SQLITE_DB = BASE_DIR / os.getenv("SQLITE_DB", "college.db")


def _sqlite_dict_factory(cursor, row):
    d = {}
    for idx, col in enumerate(cursor.description):
        val = row[idx]
        if col[0] == "created_at" and isinstance(val, str):
            try:
                val = datetime.strptime(val, "%Y-%m-%d %H:%M:%S")
            except ValueError:
                try:
                    val = datetime.fromisoformat(val)
                except ValueError:
                    pass
        d[col[0]] = val
    return d


def init_sqlite_db():
    conn = sqlite3.connect(SQLITE_DB)
    cur = conn.cursor()
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
    conn.commit()
    conn.close()


def get_db_connection():
    if DB_TYPE == "mysql" and mysql:
        return mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            port=int(os.getenv("DB_PORT", "3306")),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_NAME", "college"),
        )
    init_sqlite_db()
    conn = sqlite3.connect(SQLITE_DB)
    conn.row_factory = _sqlite_dict_factory
    conn.create_function("now", 0, lambda: datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    return conn


def _prepare_query(query):
    if DB_TYPE != "mysql" or not mysql:
        return query.replace("%s", "?")
    return query


def fetch_one(query, params=()):
    connection = get_db_connection()
    try:
        if DB_TYPE == "mysql" and mysql and hasattr(connection, "cursor"):
            cursor = connection.cursor(dictionary=True)
        else:
            cursor = connection.cursor()
        cursor.execute(_prepare_query(query), params)
        return cursor.fetchone()
    finally:
        cursor.close()
        connection.close()


def fetch_all(query, params=()):
    connection = get_db_connection()
    try:
        if DB_TYPE == "mysql" and mysql and hasattr(connection, "cursor"):
            cursor = connection.cursor(dictionary=True)
        else:
            cursor = connection.cursor()
        cursor.execute(_prepare_query(query), params)
        return cursor.fetchall()
    finally:
        cursor.close()
        connection.close()


def execute(query, params=()):
    connection = get_db_connection()
    try:
        cursor = connection.cursor()
        cursor.execute(_prepare_query(query), params)
        connection.commit()
        return cursor.lastrowid
    finally:
        cursor.close()
        connection.close()


def login_required(role=None):
    def decorator(view):
        @wraps(view)
        def wrapped(*args, **kwargs):
            if "user_id" not in session:
                return jsonify({"error": "Authentication required"}), 401
            if role and session.get("role") != role:
                return jsonify({"error": "Forbidden"}), 403
            return view(*args, **kwargs)

        return wrapped

    return decorator


def redirect_for_role(role):
    if role == "admin":
        return redirect(url_for("admin_dashboard"))
    return redirect(url_for("student_dashboard"))


def base_layout(title, body_html):
    return render_template_string(
        """
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{{ title }}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                body { background: linear-gradient(135deg, #0f172a, #1e293b 45%, #111827); color: white; font-family: Arial, sans-serif; }
                .glass { background: rgba(15,23,42,0.75); border: 1px solid rgba(148,163,184,0.25); border-radius: 22px; box-shadow: 0 24px 80px rgba(15,23,42,0.55); }
                .btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; }
                .btn-primary:hover { filter: brightness(1.08); }
                .muted { color: #cbd5e1; }
                .nav-link { color: #e2e8f0; }
                .nav-link:hover { color: white; }
                a { text-decoration: none; }
            </style>
        </head>
        <body class="min-vh-100 d-flex align-items-center justify-content-center p-4">
            {{ body_html | safe }}
        </body>
        </html>
        """,
        title=title,
        body_html=body_html,
    )


def legacy_ui(
    filename,
    form_action=None,
    link_replacements=None,
    value_replacements=None,
    body_replacements=None,
):
    """Reuse the original HTML/CSS while Flask owns all request handling."""
    page = (BASE_DIR / filename).read_text(encoding="utf-8")
    page = page[page.lower().index("<!doctype html>") :]
    for pattern, replacement in (body_replacements or []):
        page = re.sub(pattern, replacement, page, count=1, flags=re.DOTALL)
    for old_value, new_value in (value_replacements or {}).items():
        page = page.replace(old_value, new_value)
    page = re.sub(r"<\?php.*?\?>", "", page, flags=re.DOTALL)
    page = re.sub(
        r"<script\b[^>]*>.*?</script>",
        lambda match: "" if "swal.fire" in match.group(0).lower() else match.group(0),
        page,
        flags=re.DOTALL | re.IGNORECASE,
    )
    if form_action:
        page = re.sub(r'<form([^>]*?)action="[^"]*"', rf'<form\1action="{form_action}"', page, flags=re.IGNORECASE)
        page = re.sub(r"<form(?![^>]*action=)", f'<form action="{form_action}"', page, count=1, flags=re.IGNORECASE)
    for old_link, new_link in (link_replacements or {}).items():
        page = page.replace(old_link, new_link)
    return page


@app.get("/")
def login_page():
    if session.get("user_id"):
        return redirect_for_role(session.get("role", "student"))

    return legacy_ui(
        "index.php",
        "/login",
        {"register.php": "/register"},
    )


@app.post("/login")
def login_submit():
    email = request.form.get("email", "").strip().lower()
    password = request.form.get("password", "")

    if not email.endswith("@kristujayanti.com"):
        return redirect(url_for("login_page", error="Use your institutional email address"))

    user = fetch_one("SELECT * FROM users WHERE email = %s", (email,))
    if not user or not check_password_hash(user["password"], password):
        return redirect(url_for("login_page", error="Invalid email or password"))

    session.clear()
    session["user_id"] = user["id"]
    session["role"] = user["role"]
    session["user_name"] = user["name"]
    return redirect_for_role(user["role"])


@app.get("/register")
def register_page():
    if session.get("user_id"):
        return redirect_for_role(session.get("role", "student"))

    return legacy_ui(
        "register.php",
        "/register",
        {"index.php": "/", "login.php": "/"},
    )


@app.post("/register")
def register_submit():
    data = request.form
    email = data.get("email", "").strip().lower()
    role = data.get("role", "student")
    password = data.get("password", "")
    name = data.get("name", "").strip()

    if not email.endswith("@kristujayanti.com"):
        return redirect(url_for("register_page", error="Use your institutional email address"))
    if role not in {"student", "admin"}:
        return redirect(url_for("register_page", error="Invalid role selected"))
    if fetch_one("SELECT id FROM users WHERE email = %s", (email,)):
        return redirect(url_for("register_page", error="Email already registered"))
    if role == "admin":
        name = "Administrator"
        register_no = ""
        program = ""
    else:
        register_no = data.get("register_no", "").strip()
        program = data.get("program", "").strip()

    if not name or not password:
        return redirect(url_for("register_page", error="Required registration fields are missing"))

    execute(
        """INSERT INTO users (name, email, register_no, program, password, role)
           VALUES (%s, %s, %s, %s, %s, %s)""",
        (name, email, register_no, program, generate_password_hash(password), role),
    )

    return redirect(url_for("login_page", error="Registration successful. Please login."))


@app.get("/logout")
def logout_page():
    session.clear()
    return redirect(url_for("login_page"))


@app.get("/student/dashboard")
def student_dashboard():
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))

    user_id = session["user_id"]
    user = fetch_one("SELECT name, register_no, program, profile_pic FROM users WHERE id = %s", (user_id,))
    stats = fetch_one(
        """SELECT COUNT(*) total,
           SUM(status = 'Pending') pending,
           SUM(status = 'Resolved') resolved
           FROM complaints WHERE user_id = %s""",
        (user_id,),
    )
    complaints = fetch_all(
        "SELECT * FROM complaints WHERE user_id = %s ORDER BY created_at DESC",
        (user_id,),
    )

    name = escape(user["name"] or "")
    profile_pic = escape(user["profile_pic"] or "default_user.png")
    return legacy_ui(
        "student/dashboard.php",
        link_replacements={
            "../logout.php": "/logout",
            "profile.php": "/student/profile",
            "submit_complaint.php": "/student/submit-complaint",
            "view_status.php": "/student/status",
        },
        value_replacements={
            "<?php echo $user_data['register_no']; ?>": escape(user["register_no"] or ""),
            "<?php echo $profile_pic; ?>": profile_pic,
            "<?php echo $name; ?>": name,
            "<?php echo explode(' ', $name)[0]; ?>": name.split(" ")[0],
            "<?php echo $user_data['program']; ?>": escape(user["program"] or ""),
            "<?php echo $stats['total']; ?>": str(stats["total"] or 0),
            "<?php echo $stats['pending'] ?? 0; ?>": str(stats["pending"] or 0),
            "<?php echo $stats['resolved'] ?? 0; ?>": str(stats["resolved"] or 0),
        },
    )


@app.get("/student/submit-complaint")
def student_complaint_form():
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))
    return legacy_ui(
        "student/submit_complaint.php",
        "/student/submit-complaint",
        {
            "../logout.php": "/logout",
            "../index.php": "/",
            "dashboard.php": "/student/dashboard",
        },
    )


@app.route("/student/profile", methods=["GET", "POST"])
def student_profile():
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))

    user_id = session["user_id"]
    if request.method == "POST" and request.files.get("profile_img"):
        profile_image = request.files["profile_img"]
        if profile_image.filename:
            filename = secure_filename(profile_image.filename)
            filename = f"PROFILE_{user_id}_{filename}"
            profile_image.save(UPLOAD_DIR / filename)
            execute("UPDATE users SET profile_pic = %s WHERE id = %s", (filename, user_id))

    user = fetch_one("SELECT * FROM users WHERE id = %s", (user_id,))
    if not user:
        session.clear()
        return redirect(url_for("login_page", error="User account not found"))

    return legacy_ui(
        "student/profile.php",
        "/student/profile",
        {
            "dashboard.php": "/student/dashboard",
            "../logout.php": "/logout",
            "../uploads/": "/uploads/",
        },
        {
            "<?php echo $pic; ?>": escape(user["profile_pic"] or "default_user.png"),
            "<?php echo $user['name']; ?>": escape(user["name"] or ""),
            "<?php echo ucfirst($user['role']); ?>": escape((user["role"] or "").title()),
            "<?php echo $user['register_no']; ?>": escape(user["register_no"] or ""),
            "<?php echo $user['program']; ?>": escape(user["program"] or ""),
            "<?php echo $user['email']; ?>": escape(user["email"] or ""),
            '<?php if($msg == "success"): ?>': "if (false) {",
            "<?php endif; ?>": "}",
        },
    )


@app.post("/student/submit-complaint")
def student_complaint_submit():
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))

    user_id = session["user_id"]
    category = request.form.get("category", "").strip()
    description = request.form.get("description", "").strip()
    if not category or not description:
        return redirect(url_for("student_complaint_form"))

    filename = ""
    evidence = request.files.get("evidence")
    if evidence and evidence.filename:
        filename = secure_filename(evidence.filename)
        evidence.save(UPLOAD_DIR / filename)

    execute(
        """INSERT INTO complaints (user_id, category, description, evidence_file, status, created_at)
           VALUES (%s, %s, %s, %s, 'Pending', NOW())""",
        (user_id, category, description, filename),
    )
    return redirect(url_for("student_dashboard"))


@app.get("/student/complaint/<int:complaint_id>")
def student_complaint_detail(complaint_id):
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))

    complaint = fetch_one(
        "SELECT * FROM complaints WHERE id = %s AND user_id = %s",
        (complaint_id, session["user_id"]),
    )
    if not complaint:
        return redirect(url_for("student_status"))

    status = complaint["status"] or "Pending"
    status_class = {
        "Resolved": "status-resolved",
        "In Progress": "status-progress",
    }.get(status, "status-pending")
    category_icons = {
        "Academic": "fa-book",
        "Hostel": "fa-home",
        "Transport": "fa-bus",
        "Infrastructure": "fa-building",
    }
    icon = category_icons.get(complaint["category"], "fa-info-circle")
    header_colors = {
        "Resolved": "#22c55e, #16a34a",
        "In Progress": "#facc15, #fbbf24",
    }.get(status, "#6b7280, #9ca3af")
    created_at = complaint["created_at"].strftime("%d %b, %Y at %I:%M %p") if complaint["created_at"] else ""
    description = escape(complaint["description"] or "").replace("\n", "<br>")
    evidence = complaint["evidence_file"] or ""
    evidence_html = ""
    if evidence:
        evidence_html = f"""
            <div class="evidence-section">
                <label class="section-label"><i class="fas fa-paperclip"></i> Evidence Provided</label>
                <a href="/uploads/{escape(evidence)}" target="_blank">
                    <img src="/uploads/{escape(evidence)}" class="evidence-image" alt="Evidence">
                </a>
            </div>
        """
    remark_html = ""
    if complaint["admin_remark"]:
        remark_html = f"""
            <div class="admin-remark">
                <div class="remark-header"><i class="fas fa-comment-dots"></i> Admin Remark</div>
                <p class="remark-content">&quot;{escape(complaint['admin_remark']).replace(chr(10), '<br>')}&quot;</p>
            </div>
        """

    return legacy_ui(
        "student/view_complaint.php",
        link_replacements={
            "view_status.php": "/student/status",
            "../uploads/": "/uploads/",
        },
        value_replacements={
            "<?php echo $id; ?>": str(complaint_id),
            "<?php echo $complaint['status']; ?>": escape(status),
            "<?php echo $complaint['category']; ?>": escape(complaint["category"] or ""),
            "<?php echo nl2br($complaint['description']); ?>": description,
            "<?php echo date('d M, Y \\a\\t g:i A', strtotime($complaint['created_at'])); ?>": escape(created_at),
            "<?php echo $complaint['evidence_file']; ?>": escape(evidence),
            "<?php echo pathinfo($complaint['evidence_file'], PATHINFO_FILENAME); ?>": escape(Path(evidence).stem),
            "<?php echo nl2br($complaint['admin_remark']); ?>": escape(complaint["admin_remark"] or "").replace("\n", "<br>"),
            "background: linear-gradient(90deg, \n                <?php \n                    $status = $complaint['status'];\n                    if($status == 'Resolved') echo '#22c55e, #16a34a';\n                    elseif($status == 'In Progress') echo '#facc15, #fbbf24';\n                    else echo '#6b7280, #9ca3af';\n                ?>\n            );": f"background: linear-gradient(90deg, {header_colors});",
            "<?php if(!empty($complaint['evidence_file'])): ?>": "",
            "<?php endif; ?>": "",
            "<?php if(!empty($complaint['admin_remark'])): ?>": "",
        },
    ).replace("<div class=\"evidence-section\">", evidence_html + "<div class=\"evidence-section\">" if evidence else "<div class=\"evidence-section\">")


@app.get("/student/status")
def student_status():
    if "user_id" not in session or session.get("role") != "student":
        return redirect(url_for("login_page", error="Login required"))

    user_id = session["user_id"]
    stats = fetch_one(
        """SELECT COUNT(*) total,
           SUM(status = 'Pending') pending,
           SUM(status = 'Resolved') resolved
           FROM complaints WHERE user_id = %s""",
        (user_id,),
    )
    complaints = fetch_all(
        "SELECT * FROM complaints WHERE user_id = %s ORDER BY created_at DESC",
        (user_id,),
    )
    category_icons = {
        "Academic": "fa-book",
        "Hostel": "fa-home",
        "Transport": "fa-bus",
        "Infrastructure": "fa-building",
    }
    cards = []
    for complaint in complaints:
        status = complaint["status"] or "Pending"
        accent = {
            "Resolved": "accent-resolved",
            "In Progress": "accent-progress",
        }.get(status, "accent-pending")
        badge = {
            "Resolved": "bg-success text-white",
            "In Progress": "bg-warning text-dark",
        }.get(status, "bg-secondary text-white")
        created_at = complaint["created_at"].strftime("%d %b, %Y") if complaint["created_at"] else ""
        remark = ""
        if complaint["admin_remark"]:
            remark = f"""
                <div class="remark-area">
                    <div class="remark-label">ADMIN REMARK:</div>
                    <div class="remark-text">{escape(complaint['admin_remark'])}</div>
                </div>
            """
        cards.append(
            f"""
            <div class="col-12 col-md-6 col-lg-4 d-flex">
                <a href="{url_for('student_complaint_detail', complaint_id=complaint['id'])}" class="vibrant-card-link">
                    <div class="vibrant-card">
                        <div class="card-accent {accent}"></div>
                        <div class="card-body">
                            <div class="text-center mb-3"><span class="status-pill {badge}">{escape(status)}</span></div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-box me-3"><i class="fas {category_icons.get(complaint['category'], 'fa-info-circle')}"></i></div>
                                <div><div class="category-label">{escape(complaint['category'])}</div><h6 class="complaint-id">Complaint #{complaint['id']}</h6></div>
                            </div>
                            <div class="complaint-preview">{escape(complaint['description'])}</div>
                            {remark}
                            <div class="card-footer"><div class="date-label"><i class="far fa-clock me-1"></i>{created_at}</div><div class="view-detail">View Detail <i class="fas fa-arrow-right ms-1"></i></div></div>
                        </div>
                    </div>
                </a>
            </div>
            """
        )

    cards_html = "".join(cards) or '<div class="col-12 text-center py-5"><h4>No grievances found</h4></div>'
    return legacy_ui(
        "student/view_status.php",
        link_replacements={
            "dashboard.php": "/student/dashboard",
            "view_complaint.php?id=": "/student/complaint/",
            "../logout.php": "/logout",
        },
        value_replacements={
            "<?php echo $st['total']; ?>": str(stats["total"] or 0),
            "<?php echo $st['pending'] ?? 0; ?>": str(stats["pending"] or 0),
            "<?php echo $st['resolved'] ?? 0; ?>": str(stats["resolved"] or 0),
        },
        body_replacements=[
            (r'<div class="row g-4 pb-5">.*?</div>\s*</div>\s*</body>', f'<div class="row g-4 pb-5">{cards_html}</div>\n</div>\n\n</body>'),
        ],
    )


@app.get("/admin/dashboard")
def admin_dashboard():
    if "user_id" not in session or session.get("role") != "admin":
        return redirect(url_for("login_page", error="Login required"))

    complaints = fetch_all(
        """SELECT complaints.*, users.name, users.register_no, users.program
           FROM complaints JOIN users ON complaints.user_id = users.id
           ORDER BY complaints.created_at DESC"""
    )
    stats = fetch_one(
        """SELECT COUNT(*) total,
           SUM(status = 'Pending') pending,
           SUM(status = 'Resolved') resolved
           FROM complaints"""
    )
    rows = "".join(
        f"<tr><td>{item['id']}</td><td>{item['name']}</td><td>{item['category']}</td><td>{item['status']}</td><td><a href=\"{url_for('admin_reply', complaint_id=item['id'])}\" class=\"text-white\">Review</a></td></tr>"
        for item in complaints
    )

    return base_layout(
        "Admin Dashboard | CCMS",
        f"""
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <div class="text-uppercase small text-muted">Admin Dashboard</div>
                    <h2 class="fw-bold mb-0">Grievance Center</h2>
                </div>
                <a href="{url_for('logout_page')}" class="btn btn-outline-light">Logout</a>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="glass p-3"><div class="small text-muted">Total</div><div class="display-6 fw-bold">{stats['total'] or 0}</div></div></div>
                <div class="col-md-4"><div class="glass p-3"><div class="small text-muted">Pending</div><div class="display-6 fw-bold text-warning">{stats['pending'] or 0}</div></div></div>
                <div class="col-md-4"><div class="glass p-3"><div class="small text-muted">Resolved</div><div class="display-6 fw-bold text-success">{stats['resolved'] or 0}</div></div></div>
            </div>
            <div class="glass p-4">
                <h4 class="fw-bold mb-3">Complaints</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle">
                        <thead><tr><th>ID</th><th>Student</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>{rows}</tbody>
                    </table>
                </div>
            </div>
        </div>
        """,
    )


@app.get("/admin/reply/<int:complaint_id>")
def admin_reply(complaint_id):
    if "user_id" not in session or session.get("role") != "admin":
        return redirect(url_for("login_page", error="Login required"))

    complaint = fetch_one(
        """SELECT complaints.*, users.name, users.email, users.register_no, users.program
           FROM complaints JOIN users ON complaints.user_id = users.id
           WHERE complaints.id = %s""",
        (complaint_id,),
    )
    if not complaint:
        return redirect(url_for("admin_dashboard"))

    return base_layout(
        "Manage Complaint | Admin",
        f"""
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="fw-bold mb-0">Review Complaint</h3>
                            <a href="{url_for('admin_dashboard')}" class="btn btn-outline-light btn-sm">Back</a>
                        </div>
                        <div class="mb-4">
                            <p class="mb-1"><strong>Student:</strong> {complaint['name']}</p>
                            <p class="mb-1"><strong>Register No:</strong> {complaint['register_no']}</p>
                            <p class="mb-1"><strong>Category:</strong> {complaint['category']}</p>
                            <p class="mb-1"><strong>Status:</strong> {complaint['status']}</p>
                            <p class="mb-0"><strong>Issue:</strong> {complaint['description']}</p>
                        </div>
                        <form method="post" action="{url_for('admin_reply_submit', complaint_id=complaint_id)}">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Pending" selected>Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admin remark</label>
                                <textarea class="form-control" rows="4" name="admin_remark" placeholder="Add remark"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        """,
    )


@app.post("/admin/reply/<int:complaint_id>")
def admin_reply_submit(complaint_id):
    if "user_id" not in session or session.get("role") != "admin":
        return redirect(url_for("login_page", error="Login required"))

    status = request.form.get("status", "Pending")
    remark = request.form.get("admin_remark", "")
    if status not in {"Pending", "In Progress", "Resolved"}:
        return redirect(url_for("admin_dashboard"))

    execute(
        "UPDATE complaints SET status = %s, admin_remark = %s WHERE id = %s",
        (status, remark, complaint_id),
    )
    return redirect(url_for("admin_dashboard"))


@app.get("/api/health")
def health():
    try:
        connection = get_db_connection()
        connection.close()
        db_info = f"sqlite: {SQLITE_DB.name}" if DB_TYPE != "mysql" else os.getenv("DB_NAME", "college")
        return jsonify({"status": "ok", "database": db_info, "db_engine": DB_TYPE})
    except Exception as error:
        return jsonify({"status": "error", "database": str(error)}), 503


@app.post("/api/login")
def login():
    data = request.form or request.get_json(silent=True) or {}
    email = data.get("email", "").strip().lower()
    password = data.get("password", "")

    if not email.endswith("@kristujayanti.com"):
        return jsonify({"error": "Use your institutional email address"}), 400

    user = fetch_one("SELECT * FROM users WHERE email = %s", (email,))
    if not user or not check_password_hash(user["password"], password):
        return jsonify({"error": "Invalid email or password"}), 401

    session.update(user_id=user["id"], role=user["role"], user_name=user["name"])
    return jsonify({"message": "Login successful", "role": user["role"]})


@app.post("/api/register")
def register():
    data = request.form or request.get_json(silent=True) or {}
    email = data.get("email", "").strip().lower()
    role = data.get("role", "student")

    if not email.endswith("@kristujayanti.com"):
        return jsonify({"error": "Use your institutional email address"}), 400
    if role not in {"student", "admin"}:
        return jsonify({"error": "Invalid role"}), 400
    if fetch_one("SELECT id FROM users WHERE email = %s", (email,)):
        return jsonify({"error": "Email already registered"}), 409

    if role == "admin":
        name, register_no, program = "Administrator", "", ""
    else:
        name = data.get("name", "").strip()
        register_no = data.get("register_no", "").strip()
        program = data.get("program", "").strip()

    if not name or not data.get("password"):
        return jsonify({"error": "Required registration fields are missing"}), 400

    execute(
        """INSERT INTO users (name, email, register_no, program, password, role)
           VALUES (%s, %s, %s, %s, %s, %s)""",
        (name, email, register_no, program, generate_password_hash(data["password"]), role),
    )
    return jsonify({"message": "Registration successful"}), 201


@app.post("/api/logout")
def logout():
    session.clear()
    return jsonify({"message": "Logged out"})


@app.get("/uploads/<path:filename>")
def uploaded_file(filename):
    return send_from_directory(UPLOAD_DIR, filename)


@app.get("/api/student/complaints")
@login_required("student")
def student_complaints():
    user_id = session["user_id"]
    complaints = fetch_all(
        "SELECT * FROM complaints WHERE user_id = %s ORDER BY created_at DESC", (user_id,)
    )
    stats = fetch_one(
        """SELECT COUNT(*) total,
           SUM(status = 'Pending') pending,
           SUM(status = 'Resolved') resolved
           FROM complaints WHERE user_id = %s""",
        (user_id,),
    )
    return jsonify({"complaints": complaints, "stats": stats})


@app.post("/api/student/complaints")
@login_required("student")
def submit_complaint():
    category = request.form.get("category", "").strip()
    description = request.form.get("description", "").strip()
    if not category or not description:
        return jsonify({"error": "Category and description are required"}), 400

    filename = ""
    evidence = request.files.get("evidence")
    if evidence and evidence.filename:
        filename = secure_filename(evidence.filename)
        evidence.save(UPLOAD_DIR / filename)

    complaint_id = execute(
        """INSERT INTO complaints
           (user_id, category, description, evidence_file, status, created_at)
           VALUES (%s, %s, %s, %s, 'Pending', NOW())""",
        (session["user_id"], category, description, filename),
    )
    return jsonify({"message": "Complaint submitted", "id": complaint_id}), 201


@app.get("/api/admin/complaints")
@login_required("admin")
def admin_complaints():
    category = request.args.get("cat", "").strip()
    where = ""
    params = ()
    if category:
        where = "WHERE complaints.category = %s"
        params = (category,)

    complaints = fetch_all(
        f"""SELECT complaints.*, users.name, users.register_no, users.program
            FROM complaints JOIN users ON complaints.user_id = users.id
            {where} ORDER BY complaints.created_at DESC""",
        params,
    )
    stats = fetch_one(
        """SELECT COUNT(*) total,
           SUM(status = 'Pending') pending,
           SUM(status = 'Resolved') resolved
           FROM complaints"""
    )
    return jsonify({"complaints": complaints, "stats": stats})


@app.patch("/api/admin/complaints/<int:complaint_id>")
@login_required("admin")
def update_complaint(complaint_id):
    data = request.form or request.get_json(silent=True) or {}
    status = data.get("status", "")
    remark = data.get("admin_remark", "")
    if status not in {"Pending", "In Progress", "Resolved"}:
        return jsonify({"error": "Invalid status"}), 400

    execute(
        "UPDATE complaints SET status = %s, admin_remark = %s WHERE id = %s",
        (status, remark, complaint_id),
    )
    return jsonify({"message": "Complaint updated"})


if __name__ == "__main__":
    app.run(
        host=os.getenv("FLASK_HOST", "127.0.0.1"),
        port=int(os.getenv("FLASK_PORT", "5001")),
        debug=os.getenv("FLASK_DEBUG", "0") == "1",
    )

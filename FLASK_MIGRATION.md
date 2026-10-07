# Flask application

`app.py` is the application runtime and owns the page routes, sessions, database access, uploads, and API. The existing PHP files are retained as the source of the original HTML/CSS presentation while the migration is completed; PHP/Apache is not required to run the Flask application.

## Setup (Standalone - No XAMPP Required!)

The application now supports **SQLite** as its default database (`college.db`).
You **do not need XAMPP, Apache, or MySQL** installed or running.

Run the app by simply double-clicking `run.bat` or via PowerShell:

```powershell
python app.py
```

## Create the database

Import `schema.sql` from the XAMPP MySQL command line. It creates the `college` database, `users` table, and `complaints` table. Register the first account through the registration page; choose the `admin` role only for a trusted administrator account.

From PowerShell in this project folder, run:

```powershell
C:\xampp1\mysql\bin\mysql.exe -u root -p < schema.sql
```

Enter the MySQL password when prompted. If your `root` account has no password, press Enter at the prompt.

If XAMPP MySQL uses a password for `root`, use the connected MySQL Shell session to run `create_app_user.sql`. The Flask application then uses the separate `college_app` account configured in `.env`. Do not put the root password in `app.py` or commit `.env`.

Flask uses port `5001` by default so it does not conflict with your other project. Check the connection at `http://127.0.0.1:5001/api/health`. Change `FLASK_PORT` in `.env` if needed.

A successful response looks like:

```json
{"database":"college","status":"ok"}
```

## API endpoints

- `POST /api/login`
- `POST /api/register`
- `POST /api/logout`
- `GET /api/student/complaints`
- `POST /api/student/complaints` with multipart fields `category`, `description`, and optional `evidence`
- `GET /api/admin/complaints?cat=Academic`
- `PATCH /api/admin/complaints/<id>` with `status` and `admin_remark`
- `GET /uploads/<filename>`

The API uses the same password hashes, role values, complaint statuses, and table columns as the original application. Open `http://127.0.0.1:5001/` and use the Flask server directly. Do not start the PHP server or `unified_server.py` for the Flask application.

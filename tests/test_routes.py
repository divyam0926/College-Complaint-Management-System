from datetime import datetime

import pytest

from app import app


@pytest.fixture
def client():
    app.config["TESTING"] = True
    with app.test_client() as client:
        yield client


def test_login_page_loads(client):
    response = client.get("/")
    assert response.status_code == 200
    assert b"login" in response.data.lower()
    assert b'action="/login"' in response.data
    assert b"KJCMS" in response.data


def test_register_page_loads(client):
    response = client.get("/register")
    assert response.status_code == 200


def test_student_dashboard_requires_login(client):
    response = client.get("/student/dashboard")
    assert response.status_code in (302, 303)


def test_admin_dashboard_requires_login(client):
    response = client.get("/admin/dashboard")
    assert response.status_code in (302, 303)


def test_original_complaint_ui_is_served_by_flask(client):
    with client.session_transaction() as session:
        session["user_id"] = 1
        session["role"] = "student"

    response = client.get("/student/submit-complaint")
    assert response.status_code == 200
    assert b'id="complaintForm"' in response.data
    assert b'action="/student/submit-complaint"' in response.data


def test_original_student_dashboard_ui_is_served_by_flask(client, monkeypatch):
    with client.session_transaction() as session:
        session["user_id"] = 1
        session["role"] = "student"

    monkeypatch.setattr(
        "app.fetch_one",
        lambda query, params=(): {
            "name": "Test Student",
            "register_no": "KJC001",
            "program": "Computer Science",
            "profile_pic": "",
            "total": 2,
            "pending": 1,
            "resolved": 1,
        },
    )
    monkeypatch.setattr("app.fetch_all", lambda query, params=(): [])
    response = client.get("/student/dashboard")
    assert response.status_code == 200
    assert b"Hello, Test" in response.data
    assert b"KJC001" in response.data


def test_profile_page_requires_login(client):
    response = client.get("/student/profile")
    assert response.status_code in (302, 303)


def test_original_profile_ui_is_served_by_flask(client, monkeypatch):
    with client.session_transaction() as session:
        session["user_id"] = 1
        session["role"] = "student"

    monkeypatch.setattr(
        "app.fetch_one",
        lambda query, params=(): {
            "name": "Test Student",
            "role": "student",
            "register_no": "KJC001",
            "program": "Computer Science",
            "email": "student@kristujayanti.com",
            "profile_pic": "",
        },
    )
    response = client.get("/student/profile")
    assert response.status_code == 200
    assert b'id="profileForm"' in response.data
    assert b"Test Student" in response.data


def test_complaint_detail_requires_login(client):
    response = client.get("/student/complaint/1")
    assert response.status_code in (302, 303)


def test_original_complaint_detail_ui_is_served_by_flask(client, monkeypatch):
    with client.session_transaction() as session:
        session["user_id"] = 1
        session["role"] = "student"

    monkeypatch.setattr(
        "app.fetch_one",
        lambda query, params=(): {
            "id": 1,
            "category": "Academic",
            "description": "Exam timetable issue",
            "evidence_file": "",
            "status": "Pending",
            "admin_remark": "",
            "created_at": datetime(2026, 9, 3, 10, 30),
            "user_id": 1,
        },
    )
    response = client.get("/student/complaint/1")
    assert response.status_code == 200
    assert b"Complaint #1" in response.data
    assert b"Exam timetable issue" in response.data
    assert b"Pending" in response.data


def test_original_history_ui_is_served_by_flask(client, monkeypatch):
    with client.session_transaction() as session:
        session["user_id"] = 1
        session["role"] = "student"

    monkeypatch.setattr(
        "app.fetch_one",
        lambda query, params=(): {"total": 1, "pending": 1, "resolved": 0},
    )
    monkeypatch.setattr(
        "app.fetch_all",
        lambda query, params=(): [{
            "id": 7,
            "category": "Academic",
            "description": "Exam timetable issue",
            "status": "Pending",
            "admin_remark": "",
            "created_at": datetime(2026, 9, 3, 10, 30),
        }],
    )
    response = client.get("/student/status")
    assert response.status_code == 200
    assert b"COMPLAINTS HISTORY LOG" in response.data
    assert b"/student/complaint/7" in response.data

# St. Luke's School of San Rafael - School Management System

## Overview
This is a PHP-based school management system for St. Luke's School of San Rafael. The application provides features for managing students, faculty, staff, and administrators with role-based access control.

## Recent Changes
- **October 5, 2025 (Latest)**: Completed full feature implementation
  - **Staff Module**: Wired attendance persistence with daily record tracking and summaries
  - **Faculty Module**: Added grades API with full persistence (prelim/midterm/finals tracking)
  - **Faculty Module**: Connected attendance marking to persistence API
  - **Dashboard**: Replaced all placeholder metrics with live data from APIs
  - **Student Module**: Connected all views to real data (courses, grades, attendance)
  - **Security**: Fixed critical data isolation issue - students now see only their own data
  - **APIs Created**: grades_api.php, student_data.php with proper authorization
  - All CSV exports working with full data from APIs

- **October 5, 2025**: Initial Replit environment setup
  - Installed PHP 8.4 with required extensions (mysqli, pdo, pdo_pgsql)
  - Configured PHP built-in server workflow on port 5000
  - Set up deployment configuration for production (autoscale)
  - Added .gitignore for PHP-specific files

## Project Architecture

### Technology Stack
- **Backend**: PHP 8.4
- **Data Storage**: JSON files (located in `api/data/`)
- **Frontend**: HTML, CSS, JavaScript (Bootstrap 5)
- **Authentication**: Custom implementation with 2FA (TOTP)

### Directory Structure
```
dsa-school/
├── admin/          - Administrator dashboard and management
├── api/            - Backend API endpoints
│   └── data/       - JSON data files (users, students, settings, etc.)
├── assets/         - Static assets (CSS, JS, images)
├── faculty/        - Faculty-specific features
├── partials/       - Reusable PHP components
├── staff/          - Staff-specific features
├── student/        - Student portal features
└── index.php       - Login page (entry point)
```

### Key Features
- Role-based access control (Administrator, Staff, Faculty, Student)
- Two-factor authentication (TOTP)
- User management
- Student enrollment and attendance tracking
- Grade management
- Document requests
- Notifications system
- Audit logging
- System backups

### Data Files
All data is stored in JSON files located in `dsa-school/api/data/`:
- `users.json` - User accounts and authentication
- `students.json` - Student records
- `settings.json` - School settings
- `attendance.json` - Attendance records (per-student, per-day with status and remarks)
- `grades.json` - Grade records (by student, class, quarter with prelim/midterm/finals)
- `activities.json` - Activity logs
- `notifications.json` - System notifications
- `enrollment.json` - Enrollment data (year levels, sections, student assignments)
- `evaluation_responses.json` - Evaluation data
- `system_logs.json` - Audit logs

## Running the Application

### Development
The application runs on PHP's built-in server:
- Host: 0.0.0.0
- Port: 5000
- Document root: `dsa-school/`

### Test Accounts
The following test accounts are available:
- Administrator: espino.jamesbryant20+admin@gmail.com
- Staff: espino.jamesbryant20+staff@gmail.com
- Faculty: espino.jamesbryant20+faculty@gmail.com
- Student: espino.jamesbryant20+student@gmail.com

Note: All accounts require 2FA authentication using TOTP.

## Deployment
The application is configured for autoscale deployment, suitable for stateless web applications. The deployment uses PHP's built-in server.

## Configuration
- Timezone: Asia/Manila
- School levels: Kinder through Grade 10
- Academic quarters: Q1, Q2, Q3, Q4, Summer

## Security Features
- Password hashing using PHP's PASSWORD_DEFAULT
- Two-factor authentication (TOTP/RFC 6238)
- Session management
- Role-based permissions hierarchy
- File locking for concurrent write operations

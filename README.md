# Don Tum Repair System

A lightweight PHP + MySQL repair request management system for tracking maintenance requests for electrical equipment and items in the Don Tum area.

This project is designed for local deployment using XAMPP or a similar PHP/MySQL stack. It supports role-based access for admins, technicians, and general users.

## Overview

The system enables users to:

- submit repair requests with a title, category, location, and description
- include contact details and optional attachment images
- track ticket status throughout the repair workflow
- view assigned work and repair history by role
- manage maintenance operations from a single dashboard

## Roles

- Admin: manages the full system and assigns technicians to tickets
- Technician: handles assigned repair jobs and updates work status
- User: submits repair requests and tracks their own requests

## Features

- Secure login with PHP session handling
- Role-based access control
- Ticket creation and assignment workflow
- Status tracking for repair jobs
- Dashboard with task filtering based on user role
- Repair history and status logs
- Optional image upload support for repair reports
- Demo accounts for testing and demonstration

## Tech Stack

- PHP 8
- MySQL / MariaDB
- PDO database access
- HTML / CSS / PHP templates

## Project Structure

```text
.
├── README.md
├── dontum_repair_system-main/
│   └── dontum_repair_system-main/
│       └── dontum_repair_system/
│           ├── admin_assign.php
│           ├── assets/
│           ├── config.php
│           ├── dashboard.php
│           ├── database.sql
│           ├── index.php
│           ├── logout.php
│           ├── partials/
│           ├── seed.php
│           ├── tech_update.php
│           ├── ticket_create.php
│           ├── ticket_view.php
│           ├── uploads/
│           └── ...
```

Key files:

- `database.sql` — creates the `dontum_repair` database and required tables
- `config.php` — database connection settings and shared helpers
- `index.php` — login page
- `dashboard.php` — main system dashboard for all roles
- `ticket_create.php` — form for reporting a repair
- `ticket_view.php` — detailed ticket view and status history
- `admin_assign.php` — admin assignment logic
- `tech_update.php` — technician status updates
- `seed.php` — creates demo users and sample data

## Database Setup

Import the schema from `database.sql` before running the app.

Example using MySQL CLI:

```bash
mysql -u root -p < database.sql
```

Or import it via phpMyAdmin.

Default database connection values (in `config.php`):

- Host: `127.0.0.1`
- Database: `dontum_repair`
- User: `root`
- Password: empty string

## Run Locally

Using XAMPP:

1. Place the project in your `htdocs` folder.
2. Start Apache and MySQL.
3. Import `database.sql` to MySQL.
4. Open the app in your browser:

```text
http://localhost/dontum_repair_system/
```

If demo accounts are not created yet, open:

```text
http://localhost/dontum_repair_system/seed.php
```

## Demo Accounts

All demo accounts use the same password:

```text
password123
```

- Admin: `admin@dontum.local`
- Technician 1: `tech1@dontum.local`
- Technician 2: `tech2@dontum.local`
- Technician 3: `tech3@dontum.local`
- User: `user@dontum.local`

## Repair Workflow

1. A user logs in and submits a repair request.
2. The admin reviews the ticket and assigns a technician.
3. The technician updates the repair status and records progress notes.
4. Once work is complete, the status is marked as resolved and later closed.
5. Status changes are stored in the `status_logs` table.

## Notes

- This project is intended for local development and demonstration.
- Image uploads are stored in the `uploads/repairs/` directory.
- The system is a focused internal workflow tool rather than a production SaaS platform.
- There is no built-in LINE notification or external API integration in this version.

## License

No explicit license file is included in this repository yet. If you plan to share or distribute it publicly, consider adding an appropriate license.

## Contributing

This project is suitable for learning, local deployment, and internal operational use. Improvements in validation, UX, reporting, and security are welcome.

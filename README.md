# 🚨 Crime Complaints Reporting Management System (CRMS)

A full-featured digital crime and complaint reporting web platform. Enables citizens to submit incident reports, track case progression in real time, and dispatch responder teams from designated stations. Fully upgraded and containerized for **PHP 8.2 Apache**, **Aiven Cloud MySQL SSL**, and **Render Reverse Proxy / Port 10000 optimizations**.

---

## 🔐 Credentials & Access Portals (ID & Passwords)

### 1. Application Login Credentials
| Portal / Role | Username / Email | Password | Direct URL Link |
| :--- | :--- | :--- | :--- |
| 🛡️ **Super Administrator** | `admin` | `admin123` | [`/admin/login.php`](https://crime-complaint-management-system.onrender.com/admin/login.php) |
| 👤 **Sample Citizen / Complainant** | `cblake@sample.com` | `admin123` | [`/index.php`](https://crime-complaint-management-system.onrender.com/index.php) |
| ⚡ **1-Click Database Setup & Status** | *No Auth Required* | *Direct Access* | [`/db_setup.php`](https://crime-complaint-management-system.onrender.com/db_setup.php) |

### 2. Aiven Cloud MySQL Database Configuration
| Configuration Key | Value | Description |
| :--- | :--- | :--- |
| **DB_SERVER** | `mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com` | Aiven Cloud Hostname |
| **DB_PORT** | `12893` | 5-Digit Custom Port |
| **DB_USERNAME** | `avnadmin` | Master MySQL Admin User |
| **DB_PASSWORD** | *(Stored securely in `.env` / Aiven Console)* | Aiven Service Password |
| **DB_NAME** | `crms_db` | Dedicated Database Name |
| **DB_SSL** | `true` | Enforced TLS/SSL Encryption |

---

## 🌐 Live Application URLs

- **Render Live URL:** [https://crime-complaint-management-system.onrender.com](https://crime-complaint-management-system.onrender.com)
- **Admin Portal:** [https://crime-complaint-management-system.onrender.com/admin/login.php](https://crime-complaint-management-system.onrender.com/admin/login.php)
- **1-Click Database Setup & Health Check:** [https://crime-complaint-management-system.onrender.com/db_setup.php](https://crime-complaint-management-system.onrender.com/db_setup.php)
- **GitHub Repository:** [https://github.com/abadhbiharisd-98/crime-complaint_menegment-system](https://github.com/abadhbiharisd-98/crime-complaint_menegment-system)

---

## 🛠️ Technology Stack & Modernization

- **Backend Runtime:** PHP 8.2 on Apache (`php:8.2-apache`)
- **Database Engine:** Aiven Cloud MySQL 8.x (`require_secure_transport=ON`, TLS enforced)
- **Frontend:** Bootstrap 4, jQuery, DataTables, Select2, FontAwesome, Froala WYSIWYG
- **Containerization:** Docker with multi-stage Apache reverse proxy compatibility
- **Deployment Platform:** Render (Docker Web Service)

### Key Compatibility Fixes (PHP 8.x & Aiven Cloud):
1. **PHP 8.x Constructor Safety:** Class properties initialized safely in `__construct()` to prevent fatal deprecation errors.
2. **Aiven SSL Transport:** Configured `mysqli_init()` with `MYSQLI_CLIENT_SSL` and `MYSQLI_OPT_SSL_VERIFY_SERVER_CERT=false` to support Aiven's TLS encryption enforcement.
3. **5-Digit Custom Ports:** Support for Aiven custom ports (e.g., `12893`, `26284`).
4. **Resilient Query Handlers:** Defensive checks (`if ($qry && $qry instanceof mysqli_result)`) to avoid fatal `fetch_array() on bool` exceptions if tables are initializing.
5. **Session Header Safety:** `ob_start()` and `session_start()` placed at the very top of files before any HTML output, completely preventing "headers already sent" warnings.
6. **Render Port 10000 & Reverse Proxy Fixes:** Apache `UseCanonicalName Off`, `UseCanonicalPhysicalPort Off`, dynamic `$PORT` binding in `start.sh`, and smart port-stripping rewrite rules in `.htaccess`.

---

## ⚙️ Aiven Cloud Database Environment Variables for Render

Configure these variables in your **Render Dashboard &rarr; Environment**:

| Variable Key | Value / Description | Example Value |
| :--- | :--- | :--- |
| `PORT` | Container listen port (auto-set by Render) | `10000` |
| `DB_SERVER` | Aiven MySQL Hostname | `mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com` |
| `DB_PORT` | Aiven MySQL 5-digit port | `12893` |
| `DB_USERNAME` | Aiven MySQL Username | `avnadmin` |
| `DB_PASSWORD` | Aiven MySQL Password | *(Your Aiven Password)* |
| `DB_NAME` | Dedicated Database Name | `crms_db` |
| `DB_SSL` | Force SSL encrypted handshake | `true` |
| `BASE_URL` | Live production URL | `https://crime-complaint-management-system.onrender.com` |

---

## ⚡ 1-Click Database Setup Tool (`db_setup.php`)

Visit `https://<your-render-url>/db_setup.php` to access the visual diagnostics dashboard:

1. **Aiven SSL Health Check:** Live verification of TLS cipher, MySQL engine version, and connection latency.
2. **1-Click SQL Importer:** Automatically executes `crms_db.sql` with `SET SESSION sql_require_primary_key = 0`, sets foreign keys, seeds default stations, and ensures the default admin user exists.
3. **Clean Rebuild:** One-click option to drop and recreate the schema cleanly.
4. **Table Diagnostics:** Live record counters for all 7 tables:
   - `complainants`
   - `complaints`
   - `complaints_action`
   - `responders_team`
   - `stations`
   - `system_settings`
   - `users`

---

## 🚀 How to Deploy on Render

### Step 1: Push Code to GitHub
```bash
git remote add origin https://github.com/abadhbiharisd-98/crime-complaint_menegment-system.git
git branch -M main
git push -u origin main
```

### Step 2: Create Web Service on Render
1. Go to [Render Dashboard](https://dashboard.render.com/) and click **New + &rarr; Web Service**.
2. Select repository: `crime-complaint_menegment-system`.
3. Set **Runtime:** `Docker`.
4. Set **Branch:** `main`.
5. Under **Environment Variables**, add the Aiven DB keys from the table above.
6. Click **Deploy Web Service**.

### Step 3: Run 1-Click Setup & Verify
1. Once deployed, open: `https://<your-app>.onrender.com/db_setup.php`
2. Click **1-Click Import** (if not already populated).
3. Log in at `https://<your-app>.onrender.com/admin/login.php` with `admin` / `admin123`.

---

## 📁 Repository Structure

```text
├── admin/
│   ├── admin_class.php        # Core backend controller (PHP 8.x safe)
│   ├── ajax.php               # AJAX routing controller
│   ├── db_connect.php         # Aiven SSL MySQL connection handler
│   ├── header.php, navbar.php # Admin navigation and headers
│   ├── home.php, index.php    # Admin dashboard and master view
│   ├── login.php              # Direct-file Admin login portal
│   └── assets/                # Admin styling, DataTables, FontAwesome, WYSIWYG
├── database/
│   └── crms_db.sql            # Clean production database schema and seed data
├── .dockerignore              # Excludes build caches and backups
├── .env.example               # Template environment configuration
├── .gitignore                 # Excludes secrets, caches, and backups
├── .htaccess                  # Reverse proxy port-stripping & security rules
├── Dockerfile                 # PHP 8.2 Apache production container
├── db_setup.php               # 1-Click visual Aiven database setup tool
├── header.php, footer.php     # Public layout partials
├── home.php, index.php        # Public landing & incident report portal
├── login.php, signup.php      # Complainant modal endpoints
├── render.yaml                # Render Infrastructure-as-Code Blueprint
├── start.sh                   # Dynamic PORT Apache startup script
├── readme.txt                 # Quick text reference with credentials
└── README.md                  # Project documentation & credential guide
```

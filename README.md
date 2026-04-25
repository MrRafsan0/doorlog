# 🚪 DoorLog

A full-stack web application for logging and retrieving door codes for physical locations. Built as a personal utility and portfolio project.

**[Live Demo](https://rafisrough.xyz/doorlog)** — Public demo with a 50-entry cap.

---

## Screenshots

> _Add a screenshot of the main form and the search page here_

---

## Features

- **Add entries** — Log any address with its door code
- **Search** — Instantly search entries by address
- **Duplicate detection** — Alerts if an address already exists, with option to update the code
- **Admin portal** — Password-protected session login for managing entries
- **Delete entries** — Admin-only delete with smooth fade-out animation
- **50-entry cap** — Demo database limited to 50 entries to prevent abuse
- **Live slot counter** — Shows remaining capacity on the form page
- **Security** — Prepared statements throughout (SQL injection prevention), `password_hash()` for admin credentials, session-based authentication

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, Vanilla JavaScript |
| Backend | PHP 8.3 |
| Database | MySQL |
| Web Server | Caddy |
| Auth | PHP Sessions + `password_hash()` / `password_verify()` |

---

## Project Structure

```
doorlog/
├── index.html      # Public form — add a location
├── login.html      # Admin login page
├── search.php      # Public search page (delete visible to admin only)
├── api.php         # REST API — GET, POST, DELETE endpoints
├── auth.php        # Login endpoint — verifies credentials against DB
├── logout.php      # Destroys session
├── me.php          # Returns current session state as JSON
├── style.css       # All styles
├── script.js       # All frontend logic
└── .env.example    # Environment variable template
```

---

## Database Schema

```sql
CREATE DATABASE doorlog_db;

CREATE TABLE locations (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    address   VARCHAR(255) NOT NULL,
    door_code VARCHAR(50)  NOT NULL
);

CREATE TABLE admins (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL  -- stored as bcrypt hash
);

-- Create a dedicated DB user (don't use root)
CREATE USER 'doorlog_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON doorlog_db.* TO 'doorlog_user'@'localhost';
FLUSH PRIVILEGES;

-- Insert an admin (generate hash with PHP: password_hash('yourpassword', PASSWORD_DEFAULT))
INSERT INTO admins (username, password) VALUES ('admin', 'YOUR_BCRYPT_HASH');
```

---

## Local Setup

**Requirements:** PHP 8+, MySQL, a web server (Apache / Caddy / Nginx)

**1. Clone the repo**
```bash
git clone https://github.com/MrRafsan0/doorlog.git
cd doorlog
```

**2. Set up environment variables**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

**3. Set up the database**
```bash
mysql -u root -p < schema.sql
```

**4. Configure your web server**

Point the document root to the `doorlog/` folder. For Caddy:
```
localhost {
    root * /path/to/doorlog
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}
```

**5. Load the app**

Open `http://localhost` in your browser.

---

## API Reference

All endpoints are in `api.php`.

| Method | Auth | Description |
|---|---|---|
| `GET /api.php` | Public | Returns all entries + count + remaining slots |
| `GET /api.php?search=query` | Public | Search entries by address |
| `POST /api.php` | Public | Add or update an entry |
| `DELETE /api.php` | Admin only | Delete entry by `id` |

**POST body example:**
```json
{
  "address": "123 Main St",
  "door_code": "4521",
  "action": "check"
}
```

**GET response format:**
```json
{
  "entries":   [...],
  "count":     12,
  "remaining": 38
}
```

---

## Security Notes

- All database queries use **prepared statements** — no raw string interpolation
- Admin passwords are stored as **bcrypt hashes** using PHP's `password_hash()`
- Session tokens are validated server-side on every sensitive request
- Database credentials are loaded from **environment variables**, never hardcoded
- DELETE endpoint rejects all non-admin requests with `401 Unauthorized`

---

## What I Learned

- Structuring a REST API in vanilla PHP without a framework
- Session-based authentication flow (login → session → protected routes)
- The difference between authentication (who are you) and authorization (what can you do)
- Preventing SQL injection with prepared statements vs. raw queries
- Managing environment secrets properly — never commit credentials to version control

---

## License

MIT — feel free to use, modify, and learn from this project.

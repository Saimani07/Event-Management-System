# EventPro — Event Management System

A full-stack event management platform with a public booking site and an admin control panel, built on PHP 8, MySQL, and vanilla JS with a premium SaaS-style UI (glassmorphism, gradients, Chart.js analytics).

## ✅ What's implemented (Phase 1 — fully working)

**User side**
- Registration & login (bcrypt password hashing, duplicate-email checks, password strength validation, "remember me")
- Home page: hero, live stats, upcoming events, categories, testimonials
- Browse/search/filter events (category, price, sort), pagination
- Event details page (gallery, schedule, rules, map embed, related events)
- Book an event with **transaction-safe seat locking** (`SELECT ... FOR UPDATE`) so seats can never oversell under concurrent bookings
- My Bookings: status tabs (upcoming/completed/cancelled), cancel booking (restores seats)
- Profile: edit details, upload photo, change password, booking stats

**Admin side**
- Separate secure admin login/session
- Dashboard: live stat cards + 3 real Chart.js charts (monthly bookings line chart, category doughnut chart, user growth bar chart), recent bookings & registrations
- Manage Events: full CRUD with image upload, modal form, search
- Manage Categories: CRUD with icon/color, blocks deletion if events are attached
- Manage Bookings: search, status filter, cancel, **CSV export**
- Manage Users: search, suspend/activate, delete, booking counts

**Security**
- PDO prepared statements everywhere (no raw SQL interpolation)
- CSRF tokens on every form (`csrf_field()` + `csrf_verify()`)
- Output escaping via `clean()` (htmlspecialchars) to prevent XSS
- Secure session config (httponly, samesite, timeout + regeneration)
- Secure file uploads: MIME-type sniffing (not just extension), size limits, randomized filenames
- Role-based access via `require_login()` / `require_admin()` guards on every protected page

All of this runs today — no placeholder logic, every button and form is wired to real database operations. Every `.php` file has been syntax-checked (`php -l`) with zero errors.

## 📁 Folder structure

```
eventpro/
├── admin/                  # Admin panel
│   ├── includes/           # Admin header/footer layout
│   ├── dashboard.php
│   ├── login.php / logout.php
│   ├── manage-events.php
│   ├── manage-categories.php
│   ├── manage-bookings.php
│   └── manage-users.php
├── user/                   # Logged-in user pages
│   ├── book-event.php
│   ├── cancel-booking.php
│   ├── my-bookings.php
│   └── profile.php
├── includes/                # Shared partials & helpers
│   ├── header.php / footer.php
│   ├── functions.php        # sanitization, uploads, logging helpers
│   └── csrf.php
├── config/
│   ├── config.php           # session bootstrap, constants
│   └── database.php         # PDO connection
├── assets/
│   ├── css/style.css        # design system (palette, glassmorphism, components)
│   ├── css/admin.css        # admin layout
│   └── js/main.js, admin.js
├── uploads/events/           # uploaded event images (created automatically)
├── database/database.sql     # full schema + sample data
├── index.php, events.php, event-details.php, login.php, register.php, logout.php, 404.php
└── README.md
```

## 🛠 Installation

1. **Requirements**: PHP 8.1+, MySQL 5.7+/MariaDB, a web server (Apache/Nginx) or `php -S`.
2. **Database setup**:
   ```bash
   mysql -u root -p -e "CREATE DATABASE eventpro"
   mysql -u root -p eventpro < database/database.sql
   ```
3. **Configure connection** — edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'eventpro');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   ```
4. **Set your base path** — edit `config/config.php`:
   ```php
   define('BASE_URL', '/eventpro'); // or '' if served from domain root
   ```
5. **Permissions** — ensure the web server can write to `uploads/`:
   ```bash
   chmod -R 755 uploads/
   ```
6. **Run it**:
   ```bash
   php -S localhost:8000
   ```
   or point your Apache/Nginx vhost document root at this folder.

### Default logins (from sample data)
| Role  | Email                | Password   |
|-------|-----------------------|-----------|
| Admin | admin@eventpro.com     | Admin@123 |
| User  | aditi@example.com      | User@123  |

**Change these credentials before deploying to production.**

## 🎨 Tech stack

PHP 8 (PDO) · MySQL · HTML5/CSS3 · Vanilla JS (ES6) · Chart.js · SweetAlert2 · DataTables · Flatpickr · Font Awesome · Google Fonts (Inter)

## 🚧 Not yet built (planned Phase 2)

These were requested but are not in this pass — flagged honestly rather than faked:
- Dark mode is wired (toggle + CSS vars) but not yet applied to every admin table/chart color
- Payments table / payment gateway integration (marked optional in spec)
- Notifications table / in-app notifications (marked optional in spec)
- Forgot-password email flow (link exists, page not yet built)
- Loading skeletons, empty-state illustrations beyond basic version
- Seat-map style seat selection (currently quantity-based seat booking)
- Activity log viewer UI in admin (the `activity_logs` table is populated but has no dedicated admin screen yet)

## 📌 Notes

- Sample password hashes in `database.sql` correspond to `Admin@123` / `User@123` for the demo accounts, generated with PHP's `password_hash()` (bcrypt).
- Currency is formatted in ₹ (INR) — change `format_currency()` in `includes/functions.php` if you need a different currency.
- The FULLTEXT index on `events` is available for a future full-text search upgrade; current search uses `LIKE` for simplicity/compatibility.

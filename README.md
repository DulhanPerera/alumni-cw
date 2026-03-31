# Alumni Influencers API

Backend-only **CodeIgniter 3** API for the **Alumni Influencers** coursework. This project provides authentication, alumni profile management, blind bidding, featured alumnus selection, API key protection, and Swagger documentation for a client-agnostic AR/alumni platform.

## Features

### Authentication

* Alumni registration with university email validation
* Email verification flow
* Secure login/logout with sessions
* Forgot password and reset password flow
* Login activity logging

### Profile Management

* Create and update main alumni profile
* Manage:

  * Degrees
  * Certifications
  * Licenses
  * Short courses
  * Employment history
* Profile image upload
* Profile completion tracking

### Blind Bidding System

* Place blind bids for a feature date
* Update bids with **increase-only** validation
* View personal bid status without revealing competitor bid amounts
* Monthly featured slot tracking
* 3-win monthly limit, with event-based extension support
* Winner selection logic for featured alumnus

### API Key Security

* Create API keys
* Store only hashed API keys in the database
* Revoke API keys
* Track API key usage logs
* Protect public endpoints with Bearer token authentication

### Public API

* Get today’s featured alumnus
* Get featured alumnus by date

### Documentation

* Swagger / OpenAPI documentation included

---

## Tech Stack

* **Backend Framework:** CodeIgniter 3
* **Language:** PHP
* **Database:** MySQL
* **Server:** Apache (XAMPP/LAMPP)
* **API Documentation:** Swagger / OpenAPI

---

## Project Structure

```text
alumni-cw/
├── application/
│   ├── controllers/
│   │   ├── api/
│   │   └── Cron.php
│   ├── core/
│   ├── models/
│   ├── views/
│   └── config/
├── swagger/
├── uploads/
├── database/
├── system/
└── index.php
```

---

## Main Modules

### Auth API

* `POST /index.php/api/auth/register`
* `GET /index.php/api/auth/verify-email?token=...`
* `POST /index.php/api/auth/login`
* `POST /index.php/api/auth/logout`
* `POST /index.php/api/auth/forgot-password`
* `POST /index.php/api/auth/reset-password`
* `GET /index.php/api/auth/me`

### Profile API

* `GET /index.php/api/profile`
* `POST /index.php/api/profile`
* `POST /index.php/api/profile/degrees`
* `PUT /index.php/api/profile/degrees/{id}`
* `DELETE /index.php/api/profile/degrees/{id}`
* `POST /index.php/api/profile/certifications`
* `PUT /index.php/api/profile/certifications/{id}`
* `DELETE /index.php/api/profile/certifications/{id}`
* `POST /index.php/api/profile/licenses`
* `PUT /index.php/api/profile/licenses/{id}`
* `DELETE /index.php/api/profile/licenses/{id}`
* `POST /index.php/api/profile/short-courses`
* `PUT /index.php/api/profile/short-courses/{id}`
* `DELETE /index.php/api/profile/short-courses/{id}`
* `POST /index.php/api/profile/employment-history`
* `PUT /index.php/api/profile/employment-history/{id}`
* `DELETE /index.php/api/profile/employment-history/{id}`
* `POST /index.php/api/profile/upload-image`

### Bidding API

* `POST /index.php/api/bids`
* `PUT /index.php/api/bids/{id}`
* `GET /index.php/api/bids/status`
* `GET /index.php/api/bids/remaining-slots`
* `POST /index.php/api/bids/select-winner`

### API Key Management

* `GET /index.php/api/keys`
* `POST /index.php/api/keys`
* `POST /index.php/api/keys/{id}/revoke`
* `GET /index.php/api/keys/usage-logs`

### Public API

* `GET /index.php/api/public/featured-today`
* `GET /index.php/api/public/featured-by-date?date=YYYY-MM-DD`

---

## Setup Instructions

### 1. Clone the project

```bash
git clone <your-repo-url>
cd alumni-cw
```

### 2. Place the project in your web root

For XAMPP/LAMPP, place the project inside:

```text
/opt/lampp/htdocs/
```

### 3. Configure the database

Update:

```text
application/config/database.php
```

Set your database name, username, and password.

### 4. Import the database schema

Import your SQL file into MySQL using phpMyAdmin or the MySQL CLI.

### 5. Configure the base URL

Update:

```text
application/config/config.php
```

Example:

```php
$config['base_url'] = 'http://localhost/alumni-cw/';
$config['index_page'] = 'index.php';
```

### 6. Start Apache and MySQL

Make sure both services are running.

### 7. Access the API

Example:

```text
http://localhost/alumni-cw/index.php/api/auth/login
```

---

## Swagger Documentation

Open Swagger UI at:

```text
http://localhost/alumni-cw/swagger/
```

This provides interactive documentation and testing for the main API endpoints.

---

## Automated Winner Selection

The project includes winner selection logic for featured alumni.

Two trigger modes are supported:

1. **Manual trigger** for testing/demo via API endpoint
2. **Scheduled trigger** via `Cron.php` for automation

Example CLI command:

```bash
php index.php cron select_winner_today
```

Example for a specific date:

```bash
php index.php cron select_winner_for_date 2026-04-02
```

This structure allows manual testing during development and automatic execution in deployment using a scheduler/cron job.

---

## Authentication Notes

* Session-based authentication is used for internal authenticated endpoints
* Bearer token authentication is used for protected public client endpoints
* API keys are stored **hashed**, not in plain text

---

## Example Bearer Token Usage

```http
Authorization: Bearer YOUR_API_KEY_HERE
```

---

## Coursework Scope

This implementation is designed as a **backend-only API** version of the coursework. It focuses on the server-side functionality required for authentication, profile management, bidding, featured alumnus selection, token security, usage logging, and API documentation.

---

## Future Improvements

* Email sending integration for verification and password reset
* Notification system for bidding outcomes
* Full automated scheduler deployment
* Admin role restrictions for internal operations
* Expanded Swagger response schemas

---

## Author

**Dulhan**

Final-year Software Engineering coursework project.

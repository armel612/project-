# TICKETING/INVENTORY SYSTEM

This is a premium, open-source enterprise solution designed to bridge the gap between IT support and hardware logistics. Developed with a focus on visual excellence and dynamic scalability.

---
*© 2026 DIEUBOUEN DEUTOU DUCLAIR ARMEL - HND Defense Project*

## 🚀 Quick Start (XAMPP / WAMP)

1.  **Clone / Copy** the project into your `htdocs` or `www` directory.
2.  Ensure your **MySQL Server** is running.
3.  **Initialize Database**:
    Visit `http://localhost/[FOLDER_NAME]/Ticket_inventory-system/setup.php` in your browser.
    *This will automatically create the database, all tables, and seed default users.*
4.  **Login**:
    Visit `http://localhost/[FOLDER_NAME]/frontend/inx.html` and use:
    - **Admin**: `admin@enterprise.com` / `password123`
    - **Staff**: `alice@enterprise.com` / `password123`

## ✨ Core Features

-   **Dynamic Role Engine**: Create and manage custom organizational roles (HR, Finance, Security) on the fly.
-   **Granular Privilege Delegation**: Fine-tune administrative rights for specific users.
-   **IT Helpdesk**: Full ticket lifecycle (Open -> Assign -> Resolve) with real-time updates.
-   **Smart Inventory**: Automated stock tracking with health alerts and replenishment requests.
-   **Audit & Analytics**: Real-time platform usage metrics and security logs.

## 🛠️ Technology Stack

-   **Backend**: PHP 7.4+ (Pure, No-Framework)
-   **Frontend**: Vanilla HTML5, CSS3, JavaScript (ES6+)
-   **Security**: JWT (JSON Web Tokens) for Auth, PBKDF2 Hashing (Bcrypt), SQL Injection protection via PDO.
-   **Aesthetics**: Glassmorphism, CSS Variables, FontAwesome 6.

## 📁 Project Structure

-   `/frontend`: Dashboard UI and Client-side logic.
-   `/Ticket_inventory-system`: Backend Core.
    -   `/controllers`: API logic.
    -   `/models`: Database interaction.
    -   `/routes`: API Endpoint definitions.
    -   `/config`: System & DB environment.

---
*Developed for HND Defense - 2026*

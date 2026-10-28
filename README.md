# StudentHub Portal

A student portal web application developed as part of **ITUE203 – Web Development Frameworks** at CHARUSAT University, CSPIT.

## Project Overview

StudentHub Portal provides a centralized platform for students to access academic information and for administrators to manage students, campus events, registrations, and user accounts.

The project is developed progressively through 14 practicals, covering website planning, frontend development, JavaScript interactivity, backend programming, database integration, authentication, file handling, APIs, and security.

## Student Information

* **Student Name:** Purv Vipulkumar Patel
* **Student ID:** 25CE089
* **Programme:** B.Tech Computer Engineering
* **Semester:** 3
* **Academic Year:** 2026–27
* **University:** CHARUSAT University
* **College:** CSPIT
* **Subject:** ITUE203 – Web Development Frameworks

## Objectives

* Design and develop a functional student portal.
* Create responsive and interactive web pages.
* Implement forms with client-side and server-side validation.
* Use PHP for backend processing.
* Integrate a MySQL database.
* Implement secure registration and login functionality.
* Manage students, campus events, and registrations.
* Develop APIs and asynchronous data-loading features.
* Implement role-based access control and audit logging.
* Maintain project history using Git and GitHub.

## Practical List

### Practical 1: Project Planning and Setup

Plan the StudentHub Portal, identify its requirements and user roles, create the sitemap and wireframes, establish the project folder structure, and initialize the GitHub repository.

### Practical 2: HTML Fundamentals and Page Structure

Develop the basic website structure using HTML and organize the content into appropriate web pages.

### Practical 3: CSS Styling and Layout

Apply CSS styling, page layouts, typography, colors, and reusable design elements to improve the website's appearance.

### Practical 4: JavaScript Interactivity

Implement interactive components using JavaScript, including dynamic content, event handling, and UI interactions.

### Practical 5: Student Registration Form

Create a student registration form using HTML5 and JavaScript validation, including input validation with regular expressions.

### Practical 6: Dynamic Web Content and Interactive Features

Enhance the portal with JavaScript functionality, DOM manipulation, interactive components, and improved user experience.

### Practical 7: Backend Development with PHP

Introduce server-side programming using PHP to process requests and support dynamic web application functionality.

### Practical 8: Database Integration

Connect the application to a database and implement database-related operations for managing application data.

### Practical 9: Secure User Registration

Implement user registration with server-side validation, password hashing, and secure account creation.

### Practical 10: Authentication and Student Portal

Develop login functionality, session-based authentication, protected student pages, and a personalized student dashboard.

### Practical 11: Student Management and Data Export

Implement student management functionality and generate exports of student records.

### Practical 12: Event Management and Poster Upload

Develop event creation, editing, deletion, category and status filtering, poster uploads, file validation, and event-related audit logging.

### Practical 13: REST API and AJAX Event Management

Create a REST-style PHP API that exchanges JSON data and implement asynchronous event management using JavaScript Fetch/AJAX.

### Practical 14: Admin Dashboard, RBAC, and Audit Logging

Develop an administrative dashboard with statistical summaries, event analytics, user status management, role-based access control, and an audit log.

## Technologies Used

* **HTML5** – Web page structure
* **CSS3** – Styling and responsive layouts
* **JavaScript** – Interactivity and client-side validation
* **PHP** – Server-side programming
* **MySQL** – Database management
* **AJAX / Fetch API** – Asynchronous communication
* **JSON** – API data exchange
* **Git** – Version control
* **GitHub** – Source code hosting
* **Visual Studio Code** – Development environment
* **XAMPP** – Local PHP and MySQL environment

## Main Features

### Student Features

* Student registration and login
* Authenticated student dashboard
* Student profile management and information display
* Attendance information
* Results and academic information
* Fees information
* Timetable display
* Campus event information and registrations

### Administrator Features

* Administrative dashboard
* Student record management
* Event creation, editing, and deletion
* Event category and status filters
* Event poster uploads with file validation
* User account status management
* Role-based access control
* Audit logging for administrative actions

### Security Features

* Server-side input validation
* Password hashing
* Session-based authentication
* Role-based authorization
* CSRF token validation for protected form submissions
* Prepared statements for database queries
* File type and size validation for poster uploads
* Audit logging for administrative activities

## Project Structure

The project structure may evolve as additional practicals are completed.

```text
Student-Hub/
│
├── index.html
├── about.html
├── contact.html
├── register.html
├── login.html
├── dashboard.html
├── profile.html
├── attendance.html
├── fees.html
├── results.html
├── timetable.html
│
├── style.css
├── script.js
├── Logo.jpg
│
├── secure_register.php
├── secure_login.php
├── student_portal.php
├── manage_students.php
├── export_students.php
├── manage_events.php
├── ajax_events_manager.php
├── admin_dashboard.php
│
├── api/
│   └── events_api.php
│
├── includes/
│   ├── auth.php
│   ├── db.php
│   ├── header.php
│   ├── footer.php
│   └── audit.php
│
├── uploads/
│   └── posters/
│
└── README.md
```

## Installation and Setup

### 1. Install the Required Software

Install XAMPP and Visual Studio Code. Ensure that PHP and MySQL are available through XAMPP.

### 2. Clone the Repository

```bash
git clone https://github.com/Purv878/Student-Hub.git
```

### 3. Move the Project

Place the project folder inside the XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\Student-Hub\
```

### 4. Start XAMPP

Open the XAMPP Control Panel and start:

* Apache
* MySQL

### 5. Configure the Database

Open phpMyAdmin and create the database required by the application.

Import or execute the SQL schema and setup scripts provided with the project, if available.

Configure `includes/db.php` with the appropriate database name, username, password, and connection settings.

Ensure the required tables exist before running the PHP modules.

### 6. Configure File Uploads

Ensure that the `uploads/posters/` directory exists and is writable by PHP. Use appropriate permissions for your local environment.

### 7. Run the Project

Open the following address in your browser:

```text
http://localhost/Student-Hub/
```

Open the relevant PHP pages to test the backend practicals.

## Git and GitHub Workflow

The project is maintained using Git and GitHub.

After completing a practical, use the following commands:

```bash
git add .
git commit -m "Completed Practical 14"
git push origin main
```

Replace the commit message with the practical number and description when appropriate.

## Testing

The application can be tested by checking:

* Website navigation and page layout
* Registration form validation
* User login and session management
* Student dashboard access
* Database operations
* Student management and export functionality
* Event creation, editing, and deletion
* Poster upload validation
* API responses and asynchronous event loading
* Administrative permissions and user status changes
* Audit log entries

## Learning Outcomes

Through these practicals, the project demonstrates the development of a web application using frontend and backend technologies. It provides practical experience with HTML, CSS, JavaScript, PHP, MySQL, API integration, authentication, database operations, file uploads, security practices, and version control.

## GitHub Repository

**Repository:** https://github.com/Purv878/Student-Hub

## Conclusion

The StudentHub Portal demonstrates the progressive development of a student-focused web application through 14 practicals. It combines frontend design, backend processing, database management, authentication, event management, API integration, and administrative security features into a single project.

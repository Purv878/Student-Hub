-- ========================================================
-- StudentHub Portal Database Schema
-- Subject: ITUE203 - Web Development Frameworks
-- Student ID: 25CE089
-- ========================================================

CREATE DATABASE IF NOT EXISTS studenthub;
USE studenthub;

-- Disable foreign key checks for clean recreation if needed
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users (Authentication and Role-Based Access)
-- --------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    remember_token VARCHAR(64) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email),
    INDEX idx_user_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: students (Academic and Student Information)
-- --------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    department VARCHAR(50) NOT NULL,
    course VARCHAR(50) NOT NULL,
    semester INT NOT NULL DEFAULT 3,
    cgpa DECIMAL(3,2) DEFAULT 8.00,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_course (course),
    INDEX idx_student_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: events (Campus Events and Workshops)
-- --------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Technical',
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 100,
    poster_image VARCHAR(255) DEFAULT 'default_poster.png',
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_event_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: registrations (Event Enrollments)
-- --------------------------------------------------------
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'absent') NOT NULL DEFAULT 'registered',
    notes VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_event_student (event_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: audit_logs (Traceability and Administrative Actions)
-- --------------------------------------------------------
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Seed Data
-- Passwords:
-- Admin: Admin@123 ($2y$10$hxF.p2WNftadgXOGTzgf6uae49BuK/6d/Wp/x44gpRR8q7I3CP1va)
-- Student: Student@123 ($2y$10$XkdaKiUJudlWZDpytk7ZOep0IzVckB1vGsyxUxCkjtiIVNFQvmU22)
-- ========================================================

INSERT INTO users (username, email, password_hash, role, status) VALUES
('admin', 'admin@charusat.edu.in', '$2y$10$hxF.p2WNftadgXOGTzgf6uae49BuK/6d/Wp/x44gpRR8q7I3CP1va', 'admin', 'active'),
('purv_ce', 'purv@charusat.edu.in', '$2y$10$XkdaKiUJudlWZDpytk7ZOep0IzVckB1vGsyxUxCkjtiIVNFQvmU22', 'student', 'active'),
('aarav_patel', 'aarav@charusat.edu.in', '$2y$10$XkdaKiUJudlWZDpytk7ZOep0IzVckB1vGsyxUxCkjtiIVNFQvmU22', 'student', 'active'),
('diya_shah', 'diya@charusat.edu.in', '$2y$10$XkdaKiUJudlWZDpytk7ZOep0IzVckB1vGsyxUxCkjtiIVNFQvmU22', 'student', 'active');

-- Minimum 20 Seed Student Records for Practical 11 requirement
INSERT INTO students (user_id, student_id, full_name, email, mobile, department, course, semester, cgpa, gender, status) VALUES
(2, '25CE089', 'Purv Patel', 'purv@charusat.edu.in', '9876543210', 'Computer Engineering', 'B.Tech CE', 3, 9.45, 'Male', 'active'),
(3, '25CE001', 'Aarav Patel', 'aarav@charusat.edu.in', '9823456781', 'Computer Engineering', 'B.Tech CE', 3, 8.85, 'Male', 'active'),
(4, '25CE002', 'Diya Shah', 'diya@charusat.edu.in', '9823456782', 'Computer Engineering', 'B.Tech CE', 3, 9.10, 'Female', 'active'),
(NULL, '25IT003', 'Rohan Mehta', 'rohan.mehta@charusat.edu.in', '9823456783', 'Information Technology', 'B.Tech IT', 3, 8.40, 'Male', 'active'),
(NULL, '25IT004', 'Ananya Sharma', 'ananya.s@charusat.edu.in', '9823456784', 'Information Technology', 'B.Tech IT', 3, 9.25, 'Female', 'active'),
(NULL, '25CE005', 'Kavya Joshi', 'kavya.j@charusat.edu.in', '9823456785', 'Computer Engineering', 'B.Tech CE', 3, 8.60, 'Female', 'active'),
(NULL, '25EC006', 'Devanshu Dave', 'devanshu.d@charusat.edu.in', '9823456786', 'Electronics & Comm.', 'B.Tech EC', 3, 7.95, 'Male', 'active'),
(NULL, '25CE007', 'Pooja Trivedi', 'pooja.t@charusat.edu.in', '9823456787', 'Computer Engineering', 'B.Tech CE', 3, 8.75, 'Female', 'active'),
(NULL, '25IT008', 'Harshil Vora', 'harshil.v@charusat.edu.in', '9823456788', 'Information Technology', 'B.Tech IT', 3, 8.15, 'Male', 'active'),
(NULL, '25CE009', 'Nisha Prajapati', 'nisha.p@charusat.edu.in', '9823456789', 'Computer Engineering', 'B.Tech CE', 3, 9.05, 'Female', 'active'),
(NULL, '25ME010', 'Yash Parmar', 'yash.p@charusat.edu.in', '9823456790', 'Mechanical Engg.', 'B.Tech ME', 3, 7.80, 'Male', 'active'),
(NULL, '25CE011', 'Siddharth Desai', 'siddharth.d@charusat.edu.in', '9823456791', 'Computer Engineering', 'B.Tech CE', 3, 8.90, 'Male', 'active'),
(NULL, '25IT012', 'Khushi Soni', 'khushi.s@charusat.edu.in', '9823456792', 'Information Technology', 'B.Tech IT', 3, 9.30, 'Female', 'active'),
(NULL, '25CE013', 'Manan Bhatt', 'manan.b@charusat.edu.in', '9823456793', 'Computer Engineering', 'B.Tech CE', 3, 8.20, 'Male', 'active'),
(NULL, '25EC014', 'Ritu Panchal', 'ritu.p@charusat.edu.in', '9823456794', 'Electronics & Comm.', 'B.Tech EC', 3, 8.45, 'Female', 'active'),
(NULL, '25CE015', 'Tirth Patel', 'tirth.p@charusat.edu.in', '9823456795', 'Computer Engineering', 'B.Tech CE', 3, 8.95, 'Male', 'active'),
(NULL, '25IT016', 'Bhavya Rao', 'bhavya.r@charusat.edu.in', '9823456796', 'Information Technology', 'B.Tech IT', 3, 7.90, 'Female', 'active'),
(NULL, '25CE017', 'Kunal Solanki', 'kunal.s@charusat.edu.in', '9823456797', 'Computer Engineering', 'B.Tech CE', 3, 8.35, 'Male', 'active'),
(NULL, '25CL018', 'Jhanvi Barot', 'jhanvi.b@charusat.edu.in', '9823456798', 'Civil Engineering', 'B.Tech CL', 3, 8.05, 'Female', 'active'),
(NULL, '25CE019', 'Dhruv Pandya', 'dhruv.p@charusat.edu.in', '9823456799', 'Computer Engineering', 'B.Tech CE', 3, 8.70, 'Male', 'active'),
(NULL, '25IT020', 'Tanvi Makwana', 'tanvi.m@charusat.edu.in', '9823456800', 'Information Technology', 'B.Tech IT', 3, 9.15, 'Female', 'active');

-- Seed Events
INSERT INTO events (title, description, category, event_date, event_time, venue, capacity, poster_image, status, created_by) VALUES
('AI & Deep Learning Hackathon 2026', 'A 24-hour national hackathon focused on building intelligent solutions for healthcare and agriculture.', 'Technical', '2026-11-15', '09:00:00', 'Central Auditorium, CHARUSAT', 120, 'poster_ai.png', 'upcoming', 1),
('Web Development Masterclass', 'Hands-on workshop exploring modern full-stack web engineering, PHP APIs, and frontend integration.', 'Workshop', '2026-11-20', '10:30:00', 'Lab 304, CSPIT-CE', 60, 'poster_web.png', 'upcoming', 1),
('Spoural 2026 Sports Meet', 'Annual inter-department sports tournament including Cricket, Football, Volleyball, and Badminton.', 'Sports', '2026-12-05', '08:00:00', 'University Sports Complex', 300, 'poster_sports.png', 'upcoming', 1),
('Cloud Native DevOps Bootcamp', 'Practical exposure to Docker containers, Kubernetes orchestration, and CI/CD pipelines.', 'Technical', '2026-10-10', '14:00:00', 'Seminar Hall B', 80, 'poster_cloud.png', 'completed', 1),
('Cyber Security & Ethical Hacking', 'Interactive seminar on network defenses, web vulnerabilities, and secure coding practices.', 'Technical', '2026-10-18', '11:00:00', 'Auditorium 2', 150, 'poster_cyber.png', 'completed', 1);

-- Seed Registrations
INSERT INTO registrations (event_id, student_id, attendance_status, notes) VALUES
(1, 1, 'registered', 'Team Lead - AI Vision project'),
(1, 2, 'registered', 'Frontend Developer'),
(1, 3, 'registered', 'Data Specialist'),
(2, 1, 'registered', 'Fast-track learner'),
(2, 4, 'registered', 'Registered via StudentHub Portal'),
(3, 1, 'registered', 'Cricket Team Captain'),
(4, 1, 'attended', 'Completed certificate issued'),
(5, 1, 'attended', 'Active attendee');

-- Seed Initial Audit Logs
INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES
(1, 'DATABASE_INIT', 'system', 1, 'StudentHub database initialized successfully with baseline schema.', '127.0.0.1'),
(1, 'CREATE', 'events', 1, 'Admin created event AI & Deep Learning Hackathon 2026.', '127.0.0.1'),
(1, 'CREATE', 'events', 2, 'Admin created event Web Development Masterclass.', '127.0.0.1'),
(2, 'REGISTER', 'events', 1, 'Student 25CE089 registered for AI Hackathon.', '127.0.0.1');

CREATE DATABASE IF NOT EXISTS taytay_health
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE taytay_health;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contact_number VARCHAR(30),
    address VARCHAR(255),
    date_of_birth DATE,
    gender VARCHAR(30),
    role ENUM('patient','admin','doctor') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    email VARCHAR(100),
    contact_number VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doctor_department (department_id, doctor_name),
    FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_slots INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doctor_schedule
        (doctor_id, appointment_date, start_time, end_time),
    FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    schedule_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    slot_number INT NOT NULL,
    status ENUM(
        'Pending','Confirmed','Completed','Cancelled','No Show'
    ) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_schedule (user_id, schedule_id),
    UNIQUE KEY unique_schedule_slot (schedule_id, slot_number),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (schedule_id)
        REFERENCES schedules(schedule_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (appointment_id)
        REFERENCES appointments(appointment_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- Add the doctor role to an existing users table.
ALTER TABLE users
MODIFY role ENUM('patient','admin','doctor')
NOT NULL DEFAULT 'patient';

-- Add the required departments without duplicating existing ones.
INSERT IGNORE INTO departments (department_name, description)
VALUES
('Pediatrics', 'Medical care for infants, children, and adolescents.'),
('Obstetrics and Gynecology (OB-GYN)', 'Women''s health and pregnancy care.'),
('Cardiology', 'Heart and cardiovascular care.'),
('Dermatology', 'Skin, hair, and nail care.'),
('Internal Medicine', 'General medical care for adults.');

-- Add sample doctors if they do not already exist.
INSERT IGNORE INTO doctors (department_id, doctor_name, specialization)
SELECT department_id, 'Dr. Maria Santos', 'Pediatrics'
FROM departments WHERE department_name = 'Pediatrics';

INSERT IGNORE INTO doctors (department_id, doctor_name, specialization)
SELECT department_id, 'Dr. Ana Reyes', 'Obstetrics and Gynecology'
FROM departments
WHERE department_name = 'Obstetrics and Gynecology (OB-GYN)';

INSERT IGNORE INTO doctors (department_id, doctor_name, specialization)
SELECT department_id, 'Dr. Juan Cruz', 'Cardiology'
FROM departments WHERE department_name = 'Cardiology';

INSERT IGNORE INTO doctors (department_id, doctor_name, specialization)
SELECT department_id, 'Dr. Carla Garcia', 'Dermatology'
FROM departments WHERE department_name = 'Dermatology';

INSERT IGNORE INTO doctors (department_id, doctor_name, specialization)
SELECT department_id, 'Dr. Pedro Ramos', 'Internal Medicine'
FROM departments WHERE department_name = 'Internal Medicine';
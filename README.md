# Manatee Bank Web Application (Secured Version)

## 📌 Overview
This project is a secured version of the Manatee Bank Web Application.  
The original application contained multiple security vulnerabilities.  
This project focuses on identifying and fixing those vulnerabilities using secure coding practices.

## 🔗 Repository
https://github.com/lost2003times/manatee-bank-web-app-

## 🔗 Original Project
https://github.com/bocajspear1/manatee-bank-web-app

---

## ⚙️ Features

- User Registration and Login
- Account Creation and Management
- Check Upload System
- Session-based Authentication
- Role-based Access Control (Admin/User)

---

## 🔐 Security Improvements

### 1. SQL Injection Prevention
- Replaced raw SQL queries with prepared statements

### 2. Cross-Site Scripting (XSS) Protection
- Implemented `htmlspecialchars()` for output encoding

### 3. Secure File Upload
- MIME type validation
- Image validation using `getimagesize()`
- Restricted file types

### 4. Password Security
- Replaced MD5 with SHA256 + Salt
- Secure password storage

### 5. Session Security
- Implemented `session_regenerate_id()`

### 6. Authorization (NEW)
- Introduced role-based access control
- Admin-only access for account creation

# 📚 School Management API (Neo4j + Laravel)

![Laravel](https://img.shields.io/badge/Laravel-10-red)
![PHP](https://img.shields.io/badge/PHP-8+-blue)
![Neo4j](https://img.shields.io/badge/Neo4j-GraphDB-green)
![License](https://img.shields.io/badge/License-MIT-yellow)

A powerful RESTful API built with Laravel and Neo4j to manage students, schools, and subjects using a **graph database structure**.

---

## 🚀 Features

- 👨‍🎓 Manage Students  
- 🏫 Manage Schools  
- 📖 Manage Subjects  
- 🔗 Graph Relationships:
  - Student → School (**BELONGS_TO**)  
  - Student → Subjects (**TAKES**)  

---

## 🧱 Architecture

- Repository Pattern  
- Service Layer  
- Form Requests (Validation)  
- API Resources  
- Clean Separation of Concerns  

---

## 🧰 Tech Stack

- Laravel  
- Neo4j  
- Laudis Neo4j PHP Client  
- PHP 8+  
- REST API  

---

## ⚙️ Installation

```bash
git clone https://github.com/your-username/school-api.git
cd school-api
composer install
cp .env.example .env
php artisan key:generate

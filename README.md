<div align="center">

# 🐳 Universal Docker Web Developer Stack

*A comprehensive, modern, and containerized development environment.*

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-7.0-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docs.docker.com/compose/)

</div>

---

## 📖 Introduction

Tired of installing local servers like WAMP, XAMPP, or MAMP that clutter your system and break between updates? 

This project provides a **robust, containerized alternative** using Docker. It perfectly mirrors a production environment while keeping your local machine completely clean. With a single command, you get a fully networked stack of the most popular web technologies.

## ✨ What's Inside?

Our stack is pre-configured and ready to use out of the box:

- 🐘 **Web Server**: PHP 8.2 running on Apache (with PDO, MySQLi, PostgreSQL, and MongoDB extensions pre-installed).
- 🗄️ **Relational Databases**: MySQL 8.0 & PostgreSQL 16.
- 🍃 **NoSQL Database**: MongoDB 7.0.
- 🛠️ **Management UI**:
  - **phpMyAdmin** for MySQL.
  - **pgAdmin** for PostgreSQL.
  - **Mongo Express** for MongoDB.

---

## 🚀 Quick Start Guide

Follow these simple steps to get your environment up and running in minutes.

### 1. Prerequisites
Ensure you have the following installed on your machine:
- [Docker Engine](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

### 2. Setup
Clone the repository and set up your environment variables:

```bash
# Clone the repository
git clone https://github.com/dineshkummarc/Universal-Docker-Web-Developer-Stack.git
cd Universal-Docker-Web-Developer-Stack

# Copy the example environment file
cp .env.example .env
```
> 💡 **Tip:** Open the `.env` file to customize default ports and database passwords if needed.

### 3. Launch
Fire up the containers! On the first run, Docker will download the necessary images and build the PHP environment.

```bash
docker-compose up -d
```
> The `-d` flag runs the containers in the background (detached mode).

---

## 🌐 Default Access URLs

Once everything is running, you can access your tools via your browser. Place your HTML/PHP files inside the `./www` folder, and they will be served instantly!

| Service | Access Link | Default Credentials (from `.env.example`) |
| :--- | :--- | :--- |
| **🌍 Web Server** | [http://localhost:8081](http://localhost:8081) | *No authentication required* |
| **🐬 phpMyAdmin** | [http://localhost:8080](http://localhost:8080) | User: `root` <br> Pass: `root_secret` |
| **🐘 pgAdmin** | [http://localhost:8082](http://localhost:8082) | Email: `admin@admin.com` <br> Pass: `admin` |
| **🍃 Mongo Express** | [http://localhost:8083](http://localhost:8083) | User: `admin` <br> Pass: `admin` |

---

## 🔌 Connecting to Databases from PHP

Docker handles the networking for you. When connecting from your PHP application, **do not use `localhost`**. Instead, use the service names defined in `docker-compose.yml`.

### Example: MySQL Connection (PDO)
```php
<?php
$host = getenv('MYSQL_HOST'); // Resolves to 'mysql'
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✅ Connected to MySQL successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
```

---

## 🛑 Managing the Stack

Here are some handy commands for daily use:

**Stop the stack gracefully** (keeps containers intact):
```bash
docker-compose stop
```

**Stop and completely remove** containers, networks, and images:
```bash
docker-compose down
```
> ⚠️ **Note:** Your database data is safe! It is stored in Docker volumes and will persist even if you run `docker-compose down`.

**View live logs** to troubleshoot issues:
```bash
docker-compose logs -f
```

---

## 🤝 Contributing

Got an idea to make this stack even better? We love community contributions!
1. Fork the project.
2. Create your feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

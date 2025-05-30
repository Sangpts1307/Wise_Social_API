# Laravel API Project

This README provides instructions for setting up and running the Laravel API project.

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL or another compatible database
- Laravel CLI

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/vbh689/learning-php-laravel-wisesocial-api.git
   ```

2. Navigate to the project directory:
   ```
   cd learning-php-laravel-wisesocial-api
   ```

3. Install dependencies:
   ```
   composer install
   ```

4. Copy the `.env.example` file to `.env`:
   ```
   cp .env.example .env
   ```

5. Generate an application key:
   ```
   php artisan key:generate
   ```

6. Configure your database settings in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password
   ```

## Database Setup

1. Run migrations to set up the database schema:
   ```
   php artisan migrate
   ```

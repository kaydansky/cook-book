# Cook Book

A web-based application for managing and exploring recipes, ingredients, dishes, equipment, and more. Built with PHP, this app provides a comprehensive platform for culinary enthusiasts to organize, search, and discover a wide variety of cooking resources.

## Features

- Recipe catalog with detailed views
- Ingredient and equipment management
- Dietary restriction support
- User registration and authentication
- Image management for dishes and ingredients
- Pagination and filtering for large lists
- Email notifications (e.g., password reset)
- Mass and volume converters
- Modular, extensible architecture

## Project Structure

```
├── composer.json           # Composer dependencies
├── config/                 # Configuration files and SQL scripts
├── public/                 # Public assets and entry point (index.php)
├── src/                    # Application source code
│   ├── DB/                 # Database connection and instance
│   ├── DI/                 # Dependency injection
│   ├── Domain/             # Domain logic (Catalog, Categories, Dish, etc.)
│   ├── Emailer/            # Email sending logic
│   ├── Helpers/            # Utility functions
│   ├── Output/             # Output formatting
│   ├── Pagination/         # Pagination logic
│   ├── Picture/            # Image handling
│   ├── Router/             # Routing logic
├── templates/              # HTML templates for views
├── vendor/                 # Composer vendor libraries
```

## Requirements

- PHP 7.4 or higher
- Composer
- MySQL or compatible database
- Web server (e.g., Apache, Nginx, or Laragon)

## Setup Instructions

1. **Clone the repository**
   ```
   git clone <repository-url>
   cd cook-book
   ```
2. **Install dependencies**
   ```
   composer install
   ```
3. **Configure the application**
   - Copy `config/config.php.example` to `config/config.php` and update database credentials and other settings.
   - Import the SQL schema from `config/cookbook.sql` into your database.
4. **Set up the web server**
   - Point your web server's document root to the `public/` directory.
5. **Access the app**
   - Open your browser and navigate to your server's URL.

## Development

- Source code is organized by domain for easy extensibility.
- Templates are in the `templates/` directory and can be customized.
- Use Composer for dependency management.

## License

This project is licensed under the MIT License.

---

*Happy cooking!*

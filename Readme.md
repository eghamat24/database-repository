# Database Repository

## Installation
Use following command to add this package to composer development requirement.
```bash
composer require nanvaie/database-repository --dev
```

### Setup for Laravel
Navigate to `app.php` in `config` folder and add following line in 'provider' section:
```php
Nanvaie\DatabaseRepository\DatabaseRepositoryServiceProvider::class
```

### Setup for Lumen
Navigate to `app.php` in `bootstrap` folder and add following line after config registrations:
```php
$this->app->configure('repository')
```

Note: Make sure to run `composer dump-autoload` after these changes.

## Usage
List of artisan commands:

| Command                             | Inputs      | Options    | Description                       |
|-------------------------------------|-------------|------------|-----------------------------------|
| `command:make-entity`               | table_name  | -f, -d, -k | Create new Entity                 |
| `command:make-factory`              | table_name  | -f, -d     | Create new Factory                |
| `command:make-resource`             | table_name  | -f, -d, -k | Create new Resource               |
| `command:make-interface-repository` | table_name  | -f, -d, -k | Create new Repository Interface   |
| `command:make-repository`           | table_name  | -f, -d     | Create new Base Repository        |
| `command:make-mysql-repository`     | table_name  | -f, -d, -k | Create new MySql Repository class |
| `command:make-redis-repository`     | table_name  | -f, -d, -k | Create new Redis Repository class |
| `command:make-all-repository`       | table_names | -f, -d, -k | Run all of the above commands     |

Example 1. Create new Entity for a table named 'users'.
```bash
php artisan command:make-entity users
```

Example 2. Create all necessary classes for two tables named 'users' and 'customers' with enabled foreign key option.
```bash
php artisan command:make-all-repository users,customers
```
# Database Repository / PHP Repository / Laravel Repository

## Installation
Use following command to add this package to composer development requirement.
```bash
composer require nanvaie/database-repository --dev
```

### Setup Laravel Repository
Before publishing assets, add `REPOSITORY_PHP_VERSION` variable to `.env` and set it to your version of choosing. Supported values are: 8.0, 7.4 (Default is 8.0).

Then run following command in console to publish necessary assets in your project directory. 
```bash
php artisan vendor:publish --provider=Nanvaie\DatabaseRepository\DatabaseRepositoryServiceProvider
```

### Setup Lumen Repository
Navigate to `app.php` in `bootstrap` folder and add following line after service providers registrations:
```php
// snip
if ($app->environment('local', 'testing')) {
    $app->register(Nanvaie\DatabaseRepository\DatabaseRepositoryServiceProvider::class);
}
// snip
```
Copy [repository.php](config/repository.php) to project config folder located at project root.

Note: Make sure to run `composer dump-autoload` after these changes.

## Usage
List of artisan commands:

| Command                                | Inputs      | Options        | Description                       |
|----------------------------------------|-------------|----------------|-----------------------------------|
| `repository:make-entity`               | table_name  | -f, -d, -k, -g | Create new Entity                 |
| `repository:make-factory`              | table_name  | -f, -d, -g     | Create new Factory                |
| `repository:make-resource`             | table_name  | -f, -d, -k, -g | Create new Resource               |
| `repository:make-interface-repository` | table_name  | -f, -d, -k, -g | Create new Repository Interface   |
| `repository:make-repository`           | table_name  | -f, -d, -g     | Create new Base Repository        |
| `repository:make-mysql-repository`     | table_name  | -f, -d, -k, -g | Create new MySql Repository class |
| `repository:make-redis-repository`     | table_name  | -f, -d, -k, -g | Create new Redis Repository class |
| `repository:make-all`                  | table_names | -f, -d, -k, -g | Run all of the above commands     |

### Options Explanation
- `-f|--force`: Force commands to override existing files.
- `-d|--delete`: Delete already created files.
- `-k|--foreign-keys`: Try to detect foreign keys of table.
- `-g|--add-to-git`: Add created files to git repository.

Example 1. Create new Entity for a table named 'users'.
```bash
php artisan command:make-entity users
```

Example 2. Create all necessary classes for two tables named 'users' and 'customers' with enabled foreign key option.
```bash
php artisan command:make-all-repository users customers -k
```

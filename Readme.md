# Database Repository / PHP Repository / Laravel Repository

## Installation
Use following command to add this package to composer development requirement.
```bash
composer require nanvaie/database-repository --dev
```

### Setup Laravel Repository
Then run following command in console to publish necessary assets in your project directory. 
```bash
php artisan vendor:publish --tag=database-repository-config
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

| Command                                | Inputs                                                                    | Options            | Description                       |
|----------------------------------------|---------------------------------------------------------------------------|--------------------|-----------------------------------|
| `repository:make-entity`               | table_name                                                                | -f, -d, -k, -g     | Create new Entity                 |
| `repository:make-enum`                 | table_name                                                                | -f, -d, -g         | Create new Enum                   |
| `repository:make-factory`              | table_name                                                                | -f, -d, -g         | Create new Factory                |
| `repository:make-resource`             | table_name                                                                | -f, -d, -k, -g     | Create new Resource               |
| `repository:make-interface-repository` | table_name                                                                | -f, -d, -k, -g     | Create new Repository Interface   |
| `repository:make-repository`           | table_name, selected_db(optional)                                         | -f, -d, -k, -g     | Create new Base Repository        |
| `repository:make-mysql-repository`     | table_name                                                                | -f, -d, -k, -g     | Create new MySql Repository class |
| `repository:make-redis-repository`     | table_name                                                                | -f, -d, -k, -g     | Create new Redis Repository class |
| `repository:make-all`                  | --table_names=table_names(optional) <br/>--selected_db=database(optional) | -a, -f, -d, -k, -g | Run all of the above commands     |


### Options Explanation
- `-f|--force`: Force commands to override existing files.
- `-d|--delete`: Delete already created files.
- `-k|--foreign-keys`: Try to detect foreign keys of table.
- `-g|--add-to-git`: Add created files to git repository.
- `-a|--all-tables`: Use all existing tables.
- `--table_names=`: Add table names, separate names with comma.
- `--selected_db=` : Use between `Mysql`,`Redis`, If it does not send, the value will return from `config/repository.php`

Example 1. Create new Entity for a table named 'users'.
```bash
php artisan repository:make-entity users
```

Example 2. Create all necessary classes for two tables named 'users' and 'customers' with enabled foreign key option.
```bash
php artisan repository:make-all --table_names=users,customers -k
```

Example 3. Create all necessary classes for all tables with enabled foreign key option(this may be used for new projects).
```bash
php artisan repository:make-all -a -k
```

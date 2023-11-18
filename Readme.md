# Database Repository / PHP Repository / Laravel Repository

## Installation
Use the following command to add this package to the composer development requirement.
```bash
composer require eghamat24/database-repository --dev 
```

### Setup Laravel Repository
Then run the following command in the console to publish the necessary assets in your project directory. 
```bash
php artisan vendor:publish --tag=database-repository-config
```

### Setup Lumen Repository
Navigate to `app.php` in `bootstrap` folder and add the following line after service providers registrations:
```php
// snip
if ($app->environment('local', 'testing')) {
    $app->register(Eghamat24\DatabaseRepository\DatabaseRepositoryServiceProvider::class);
}
// snip
```
Copy [repository.php](config/repository.php) to the project config folder located at the project root.

Note: Make sure to run `composer dump-autoload` after these changes.

## Usage
To use this package easily, you can run the following command. It will create all the required components such as Entity, IRepository, Repository, MySqlRepository, RedisRepository, Resource, and Factory for the users table.
```bash
php artisan repository:make-all --table_names=users --strategy_name=QueryCacheStrategy
```
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
- `-k|--foreign-keys`: Try to detect foreign keys of a table.
- `-g|--add-to-git`: Add created files to the git repository.
- `-a|--all-tables`: Use all existing tables.
- `--table_names=`: Add table names, separate names with comma.
- `--selected_db=` : Use between `Mysql`,`Redis`, If it does not send, the value will return from `config/repository.php`
- `--strategy_name=` : add a trait to your Redis repository based on the strategy you choose

Example 1. Create a new Entity for a table named 'users'.
```bash
php artisan repository:make-entity users
```

Example 2. Create all necessary classes for two tables named 'users' and 'customers' with an enabled foreign key option.
```bash
php artisan repository:make-all --table_names=users,customers -k
```

Example 3. Create all necessary classes for all tables with an enabled foreign key option(this may be used for new projects).
```bash
php artisan repository:make-all -a -k
```

## Cache Strategy
We created some strategies for caching data, based on the number of records and change frequency

### SingleKeyCacheStrategy
SingleKeyCacheStrategy is a good choice for tables with very few rows, such as less than 50 records. This strategy creates one cache key and stores all the data on it. Then, when the app queries data, it loops through the data and returns the result.

This strategy has one cache key and clears all the cached data whenever there is a change. This ensures that the data is always updated and valid.

### QueryCacheStrategy
QueryCacheStrategy is suitable for tables that have low change frequency and not too many records. For example, if the table has less than 10,000 rows or if it has more than that but the queries are not very unique and there are similar queries, this strategy works well.

This strategy assigns a tag to each cache of the repository and clears all the cached data whenever there is a change. This ensures that the data is always updated and valid.

### TemporaryCacheStrategy
TemporaryCacheStrategy is useful when we have a large or diverse amount of data and the data user can tolerate some delay in the updates. We cache every request and delay the changes that are not critical to be reflected in real time. For example, if we have a post and we edit its content, we can cache the old version until the cache expires. We set the cache expiration time for each data item based on the maximum delay the user can accept. This way, we use this strategy to cache data temporarily.

### ClearableTemporaryCacheStrategy
Sometimes, we need to clear the data from a temporary cache when some critical changes occur. ClearableTemporaryCacheStrategy is suitable for these situations.

```bash
php artisan repository:make-all --table_names=users --strategy_name=QueryCacheStrategy
```

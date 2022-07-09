<?php

namespace Nanvaie\DatabaseRepository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait CustomMySqlQueries
{
    protected $dataTypes = [
        'bool' => 'bool',
        'boolean' => 'bool',
        'bit' => 'string',
        'int' => 'int',
        'integer' => 'int',
        'tinyint' => 'int',
        'smallint' => 'int',
        'mediumint' => 'int',
        'bigint' => 'int',
        'float' => 'float',
        'double' => 'float',
        'json' => 'string',
        'char' => 'string',
        'varchar' => 'string',
        'binary' => 'string',
        'varbinary' => 'string',
        'tinyblob' => 'string',
        'tinytext' => 'string',
        'text' => 'string',
        'blob' => 'string',
        'mediumtext' => 'string',
        'mediumblob' => 'string',
        'longtext' => 'string',
        'longblob' => 'string',
        'enum' => 'string',
        'date' => 'string',
        'time' => 'string',
        'datetime' => 'string',
        'timestamp' => 'string',
    ];

    /**
     * Extract all columns from a given table.
     * @param string $tableName
     * @return Collection
     */
    public function getAllColumnsInTable(string $tableName): Collection
    {
        return DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', $tableName)
            ->get();
    }

    /**
     * Extract all foreign keys from a given table. Foreign key's relations must define in MySql!
     * @param string $tableName
     * @return Collection
     */
    public function extractForeignKeys(string $tableName): Collection
    {
        $foreignKeys = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', $tableName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();

        $foreignKeys->each(function ($foreignKey) {
            $foreignKey->VARIABLE_NAME = camel_case(str_replace('_id', '', $foreignKey->COLUMN_NAME));
            $foreignKey->ENTITY_DATA_TYPE = ucfirst(camel_case(str_singular($foreignKey->REFERENCED_TABLE_NAME)));
        });

        return $foreignKeys;
    }
}
<?php

namespace Nanvaie\DatabaseRepository\Creators;

use App\Models\Repositories\User\IUserRepository;
use Illuminate\Support\Collection;
use Nanvaie\DatabaseRepository\CustomMySqlQueries;
use Nanvaie\DatabaseRepository\Commands;
use Nanvaie\DatabaseRepository\Commands\MakeRedisRepository;
use Illuminate\Support\Str;


class CreatorRepository implements IClassCreator
{
    public function __construct(
        public Collection $columns,
        public string     $sqlRepositoryVariable,
        public string     $sqlRepositoryName,
        public string     $repositoryStubsPath,
        public string     $detectForeignKeys,
        public string     $tableName,
        public string     $entityVariableName,
        public string     $entityName,
        public string     $entityNamespace,
        public string     $repositoryName,
        public string     $interfaceName,
        public string     $repositoryNamespace,
        public string     $selectedDb,
        public string     $redisRepositoryVariable,
        public string     $redisRepositoryName,
        public string     $strategyName
    )
    {
    }

    use CustomMySqlQueries;

    private function writeFunction(string $functionStub, string $functionName, string $columnName, string $attributeType): string
    {
        if ($functionName === 'getOneBy') {
            $functionReturnType = 'null|{{ EntityName }}';
            $functionName .= ucfirst(Str::camel($columnName));
            $columnName = Str::camel($columnName);
            $redisCashFunction = $this->getRedisCashFunctionGetOneBy($this->strategyName);


        } elseif ($functionName === 'getAllBy') {
            $functionReturnType = 'Collection';
            $functionName .= ucfirst(Str::plural(Str::camel($columnName)));
            $columnName = Str::plural(Str::camel($columnName));
            $redisCashFunction = $this->getRedisCashFunctionGetAllBy($this->strategyName);


        } elseif ($functionName === 'create') {
            $functionReturnType = $attributeType;
            $redisCashFunction = $this->getRedisCashFunctionCreate($this->strategyName);

        } elseif (in_array($functionName, ['update', 'remove', 'restore'])) {
            $functionReturnType = 'int';
            $redisCashFunction = $this->getRedisCashFunctionUpdate($this->strategyName);

        }
        return str_replace(['{{ FunctionName }}', '{{ AttributeType }}', '{{ AttributeName }}', '{{ FunctionReturnType }}','{{redisFunction}}'],
            [$functionName, $attributeType, Str::camel($columnName), $functionReturnType,$redisCashFunction],
            $functionStub);
    }
    private function writeSqlAttribute(string $attributeStub, string $sqlRepositoryVariable, string $sqlRepositoryName): string
    {
      return  str_replace(['{{ SqlRepositoryVariable }}', '{{ SqlRepositoryName }}'],
            [$sqlRepositoryVariable, $sqlRepositoryName],
            $attributeStub);
    }
    public function writeRedisAttribute(string $attributeStub,string $redisRepositoryVariable,string  $redisRepositoryName):string
    {
        return  str_replace(['{{ RedisRepositoryVariable }}', '{{ RedisRepositoryName }}'],
            [$redisRepositoryVariable, $redisRepositoryName],
            $attributeStub);
    }

    public function getNameSpace(): string
    {
        return $this->repositoryNamespace . '\\' . $this->entityName;
    }

    public function createUses(): array
    {
        return [
            "use $this->entityNamespace\\$this->entityName;",
            "use Illuminate\Support\Collection;"
        ];
    }

    public function getClassName(): string
    {
        return $this->repositoryName;
    }

    public function getExtendSection(): string
    {
        return 'implements ' . $this->interfaceName;
    }

    public function createAttributs(): array
    {
        $attributeSqlStub = file_get_contents($this->repositoryStubsPath . 'attribute.sql.stub');
        $attributes = [];
        $attributes['repository'] = 'private '.$this->interfaceName.' $repository;';
        $attributes['redisRepository'] = 'private '.$this->redisRepositoryName.' $redisRepository;';
        return $attributes;
    }

    public function createFunctions(): array
    {
        $constructStub = file_get_contents($this->repositoryStubsPath . 'construct.stub');
        $functionStub = file_get_contents($this->repositoryStubsPath . 'function.stub');
        $setterSqlStub = file_get_contents($this->repositoryStubsPath . 'setter.sql.stub');
        $functions = [];
        $functions['__construct'] = $this->getConstruct($setterSqlStub, $constructStub);
        $functions['__construct'] = $this->getConstructRedis($setterSqlStub, $constructStub);
        $functions['getOneById'] = $this->writeFunction($functionStub, 'getOneBy', 'id', 'int');
        $functions['getAllByIds'] = $this->writeFunction($functionStub, 'getAllBy', 'id', 'array');
        $indexes = $this->extractIndexes($this->tableName);
        foreach ($indexes as $index) {
            $fun_name = ucfirst(Str::plural(Str::camel($index->COLUMN_NAME)));
            $functions['getAllBy' . $fun_name] = $this->writeFunction($functionStub, 'getAllBy', $index->COLUMN_NAME, 'array');
            $fun_name = ucfirst(Str::camel($index->COLUMN_NAME));
            $functions['getOneBy' . $fun_name] = $this->writeFunction($functionStub, 'getOneBy', $index->COLUMN_NAME, 'int');
        }
        if ($this->detectForeignKeys) {
            $foreignKeys = $this->extractForeignKeys($this->tableName);

            foreach ($foreignKeys as $_foreignKey) {
                $fun_name = ucfirst(Str::camel($_foreignKey->COLUMN_NAME));
                $functions['getOneBy' . $fun_name] = $this->writeFunction($functionStub, 'getOneBy', $_foreignKey->COLUMN_NAME, 'int');
                $fun_name = ucfirst(Str::plural(Str::camel($_foreignKey->COLUMN_NAME)));
                $functions['getAllBy' . $fun_name] = $this->writeFunction($functionStub, 'getAllBy', $_foreignKey->COLUMN_NAME, 'array');
            }
        }
        $functions['create'] = $this->writeFunction($functionStub, 'create', $this->entityVariableName, $this->entityName);
        $functions['update'] = $this->writeFunction($functionStub, 'update', $this->entityVariableName, $this->entityName);
        if (in_array('deleted_at', $this->columns->pluck('COLUMN_NAME')->toArray(), true)) {
            $functions['remove'] = $this->writeFunction($functionStub, 'remove', $this->entityVariableName, $this->entityName);
            $functions['restore'] = $this->writeFunction($functionStub, 'restore', $this->entityVariableName, $this->entityName);
        }
        foreach ($functions as &$func) {
            $func = str_replace(["{{ SqlRepositoryVariable }}", '{{ SqlRepositoryName }}', '{{ EntityName }}'],
                [$this->sqlRepositoryVariable, $this->sqlRepositoryName, $this->entityName],
                $func
            );
        }
        return $functions;
    }
    public function getConstruct(string $setterSqlStub, string $constructStub)
    {
        return str_replace("{{ Setters }}", trim($this->writeSqlAttribute($setterSqlStub, $this->sqlRepositoryVariable, $this->sqlRepositoryName,$this->redisRepositoryVariable,$this->redisRepositoryName)), $constructStub);
    }
    public function getConstructRedis(string $setterSqlStub, string $constructStub)
    {
        return str_replace("{{ Setters }}", trim($this->writeRedisAttribute($setterSqlStub,$this->redisRepositoryVariable,$this->redisRepositoryName)), $constructStub);
    }
    private function getRedisCashFunctionGetOneBy($strategyName)
    {
        $repositoryRedisStubsPath=__DIR__ . '/../../'.'stubs/Repositories/Redis/getOneBy/base.';
        return match ($strategyName) {
            'QueryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'query_cache_strategy.stub'),
            'SingleKeyCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'single_key_cache_strategy.stub'),
            'ClearableTemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'clearable_temporary_cache_strategy.stub'),
            'TemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'temporary_cache_strategy.stub'),
         };
    }
    private function getRedisCashFunctionGetAllBy($strategyName)
    {
        $repositoryRedisStubsPath=__DIR__ . '/../../'.'stubs/Repositories/Redis/getAllBy/base.';
        return match ($strategyName) {
            'QueryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'query_cache_strategy.stub'),
            'SingleKeyCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'single_key_cache_strategy.stub'),
            'ClearableTemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'clearable_temporary_cache_strategy.stub'),
            'TemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'temporary_cache_strategy.stub'),
        };
    }
    private function getRedisCashFunctionCreate($strategyName)
    {
        $repositoryRedisStubsPath=__DIR__ . '/../../'.'stubs/Repositories/Redis/create/base.';
        return match ($strategyName) {
            'QueryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'query_cache_strategy.stub'),
            'SingleKeyCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'single_key_cache_strategy.stub'),
            'ClearableTemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'clearable_temporary_cache_strategy.stub'),
            'TemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'temporary_cache_strategy.stub'),
        };
    }
    private function getRedisCashFunctionUpdate($strategyName)
    {
        $repositoryRedisStubsPath=__DIR__ . '/../../'.'stubs/Repositories/Redis/update/base.';
        return match ($strategyName) {
            'QueryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'query_cache_strategy.stub'),
            'SingleKeyCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'single_key_cache_strategy.stub'),
            'ClearableTemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'clearable_temporary_cache_strategy.stub'),
            'TemporaryCacheStrategy' => file_get_contents($repositoryRedisStubsPath . 'temporary_cache_strategy.stub'),
        };
    }
}

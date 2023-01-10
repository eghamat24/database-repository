<?php

namespace Nanvaie\DatabaseRepository\Creators;

class CreatorRedisRepository implements IClassCreator
{
    public function __construct(
        public string $redisRepositoryName,
        public string $redisRepositoryNamespace,
        public string $entityName
    )
    {

    }
    public function getNameSpace(): string
    {
        return $this->redisRepositoryNamespace . '\\' . $this->entityName;
    }
    public function createUses(): array
    {
        return ["use Nanvaie\DatabaseRepository\Models\Repositories\RedisRepository;"];
    }
    public function getClassName(): string
    {
        return $this->redisRepositoryName;
    }
    public function getExtendSection(): string
    {
        return "extends RedisRepository";
    }
    public function createAttributs(): array
    {
        return [];
    }
    public function createFunctions(): array
    {
        return [];
    }
}

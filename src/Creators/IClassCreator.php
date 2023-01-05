<?php

namespace Nanvaie\DatabaseRepository\Creators;

use Illuminate\Support\Collection;

interface IClassCreator
{
//    public function getOneById(int $id): null|User;
//
//    public function getAllByIds(array $ids): Collection;
//
      public function createAttributs(Collection $columns, string $attributeStub,string $detectForeignKeys,string $tableName): array;

      public function createFunctions(Collection $columns, bool|string $accessorsStub,string $detectForeignKeys,string $tableName):array;


}

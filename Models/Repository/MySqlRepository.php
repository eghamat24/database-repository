<?php

namespace App\Models\Repositories;

use App\Models\Entities\Entity;
use App\Models\Factories\IFactory;
use App\Models\General\Polygon;
use App\Models\Griew\FilterOperator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class MySqlRepository
{
    private ?ConnectionInterface $alternativeDbConnection;

    protected string $primaryKey = 'id';

    protected string $table = '';

    protected bool $softDelete = false;

    private bool $withTrashed = false;

    protected IFactory $factory;

    public function __construct()
    {
        $this->alternativeDbConnection = null;
    }

    /**
     * Notice: this function cannot be used in async jobs because the connection is not serializable!
     * @param ConnectionInterface $connection
     */
    public function changeDatabaseConnection($connection)
    {
        $this->alternativeDbConnection = $connection;
    }

    public function newQuery(): Builder
    {
        if (is_null($this->alternativeDbConnection)) {
            $query = app('db')->table($this->table);
        } else {
            $query = $this->alternativeDbConnection->table($this->table);
        }

        if ($this->softDelete) {
            if (!$this->withTrashed) {
                $query = $query->whereNull('deleted_at');
            } else {
                $this->withTrashed = false;
            }
        }

        return $query;
    }

    public function withTrashed(): MySqlRepository
    {
        $this->withTrashed = true;

        return $this;
    }

    /**
     * @param int|null $total
     * @param int $offset
     * @param int $count
     * @param array $orders
     * @param array $filters
     * @return Collection
     */
    public function getAllForGridView(?int &$total, int $offset = 0, int $count = 0, array $orders = [], array $filters = []): Collection
    {
        $query = $this->newQuery();

        $result = $this->processGridViewQuery($query, $total, $offset, $count, $orders, $filters)->get();

        return $this->factory->makeCollectionOfEntities($result);
    }

    public function raw($str)
    {
        if (is_null($this->alternativeDbConnection)) {
            return app('db')->raw($str);
        }
        return $this->alternativeDbConnection->raw($str);
    }

    public function exists($columnValue, $columnName = null)
    {
        if (is_null($columnName)) {
            $columnName = $this->primaryKey;
        }
        return $this->newQuery()->where($columnName, $columnValue)->exists();
    }

    /**
     * this is for validation purpose look at AppServiceProvider
     * @param $attribute
     * @param $value
     * @param null $ignoredPrimaryKey
     * @return bool
     */
    public function valueExists($attribute, $value, $ignoredPrimaryKey = null)
    {
        $query = $this->newQuery();

        if ($this->softDelete) {
            $query->whereNull('deleted_at');
        }

        $query->where($attribute, $value);

        if (!is_null($ignoredPrimaryKey)) {
            $query->where($this->primaryKey, '<>', $ignoredPrimaryKey);
        }

        return $query->exists();
    }

    /**
     * @param Entity $model
     */
    public function updateOrCreate($model)
    {
        if ($this->exists($model->getPrimaryKey())) {
            $this->update($model);
        } else {
            $this->create($model);
        }
    }

    /**
     * @param Entity $model
     */
    public function createIfNotExists($model)
    {
        if (!$this->exists($model->getPrimaryKey())) {
            $this->create($model);
        }
    }

    /**
     * It returns maximum row Id
     * @return int|mixed
     */
    public function getMaxId()
    {
        $entity = $this->newQuery()->orderByDesc($this->primaryKey)->first();
        if ($entity) {
            return $entity->id;
        } else {
            return 0;
        }
    }

    /**
     * @param Builder $query
     * @param int $offset
     * @param int $count
     * @param int|null $total
     * @param array $orders
     * @param array $filters
     * @return Builder
     */
    protected function processGridViewQuery(Builder $query, ?int &$total, int $offset = 0, int $count = 0, array $orders = [], array $filters = []): Builder
    {
        if ($orders) {
            $query = $this->processOrder($query, $orders);
        }

        if ($filters) {
            $query = $this->processFilter($query, $filters);
        }

        $total = $query->count();

        if ($count) {
            $query->offset($offset);
            $query->limit($count);
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param array $orders
     * @return Builder
     */
    protected function processOrder(Builder $query, array $orders): Builder
    {
        foreach ($orders as $order) {
            $query->orderBy($order->name, $order->type);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    protected function processFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            switch (strtolower(snake_case($filter->operator))) {
                case FilterOperator::IS_NULL:
                    $query->whereNull($filter->name);
                    break;
                case FilterOperator::IS_NOT_NULL:
                    $query->whereNotNull($filter->name);
                    break;
                case FilterOperator::IS_EQUAL_TO:
                    if (is_string($filter->operand1) && Str::contains($filter->operand1, '|')) {
                        // create in functionality with equal string
                        $arr = array_filter(explode('|', $filter->operand1));
                        $query->whereIn($filter->name, $arr);
                    } else {
                        $query->where($filter->name, '=', $filter->operand1);
                    }
                    break;
                case FilterOperator::IS_NOT_EQUAL_TO:
                    if (is_string($filter->operand1) && Str::contains($filter->operand1, '|')) {
                        // create in functionality with equal string
                        $arr = array_filter(explode('|', $filter->operand1));
                        $query->whereNotIn($filter->name, $arr);
                    } else {
                        $query->where($filter->name, '<>', $filter->operand1);
                    }
                    break;
                case FilterOperator::START_WITH:
                    $query->where($filter->name, 'LIKE', $filter->operand1 . '%');
                    break;
                case FilterOperator::DOES_NOT_CONTAINS:
                    $query->where($filter->name, 'NOT LIKE', '%' . $filter->operand1 . '%');
                    break;
                case FilterOperator::CONTAINS:
                    $query->where($filter->name, 'LIKE', '%' . $filter->operand1 . '%');
                    break;
                case FilterOperator::ENDS_WITH:
                    $query->where($filter->name, 'LIKE', '%' . $filter->operand1);
                    break;
                case FilterOperator::IN:
                    $query->whereIn($filter->name, $filter->operand1);
                    break;
                case FilterOperator::NOT_IN:
                    $query->whereNotIn($filter->name, $filter->operand1);
                    break;
                case FilterOperator::BETWEEN:
                    $query->whereBetween($filter->name, array($filter->operand1, $filter->operand2));
                    break;
                case FilterOperator::IS_AFTER_THAN_OR_EQUAL_TO:
                case FilterOperator::IS_GREATER_THAN_OR_EQUAL_TO:
                    $query->where($filter->name, '>=', $filter->operand1);
                    break;
                case FilterOperator::IS_AFTER_THAN:
                case FilterOperator::IS_GREATER_THAN:
                    $query->where($filter->name, '>', $filter->operand1);
                    break;
                case FilterOperator::IS_LESS_THAN_OR_EQUAL_TO:
                case FilterOperator::IS_BEFORE_THAN_OR_EQUAL_TO:
                    $query->where($filter->name, '<=', $filter->operand1);
                    break;
                case FilterOperator::IS_LESS_THAN:
                case FilterOperator::IS_BEFORE_THAN:
                    $query->where($filter->name, '<', $filter->operand1);
                    break;
                case FilterOperator::IS_INSIDE_POLYGON:
                    $name = $filter->name;
                    /** @var Polygon $polygon */
                    $polygon = $filter->operand1;
                    $query->whereRaw("Contains(GeomFromText('{$polygon->toRaw()}'),{$name})");
                    break;
            }
        }

        return $query;
    }
}

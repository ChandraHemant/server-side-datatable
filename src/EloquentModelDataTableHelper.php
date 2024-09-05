<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EloquentModelDataTableHelper
{
    public static function getServerSideDataTable(Model $eloquentModel, array $column)
    {
        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        self::applyCustomFunction($query, $column);

        $searchValue = request()->input('search.value');
        if ($searchValue) {
            self::applySearchFilter($query, $column, $searchValue);
            self::applyCustomSearch($query, $column, $searchValue);
        }

        self::applySelectColumns($query, $column);

        self::applyOrderBy($query, $column);

        self::applyPagination($query);

        return $query->get();
    }

    public static function countFilteredServerSideDataTable(Model $eloquentModel, array $column)
    {
        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        self::applyCustomFunction($query, $column);

        $searchValue = request()->input('search.value');
        if ($searchValue) {
            self::applySearchFilter($query, $column, $searchValue);
            self::applyCustomSearch($query, $column, $searchValue);
        }

        self::applySelectColumns($query, $column);

        return $query->count();
    }

    public static function getDataWithJoinTables(Model $eloquentModel, array $column)
    {
        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        self::applyCustomFunction($query, $column);

        self::applySelectColumns($query, $column);

        self::applyOrderBy($query, $column);

        return $query->get();
    }

    private static function applyCustomFunction($query, array $columns)
    {
        foreach ($columns['customFunction'] ?? [] as $column) {
            if (isset($column['relation']) && isset($column['function'])) {
                if (is_array($column['relation'])) {
                    foreach ($column['relation'] as $relation) {
                        self::applyFunctionToQuery($query, $relation, $column['function'], $column);
                    }
                } else {
                    self::applyFunctionToQuery($query, $column['relation'], $column['function'], $column);
                }
            }
        }
    }

    private static function applyFunctionToQuery($query, $relation, $function, $column)
    {
        $query->{$function}($relation, function ($query) use ($column) {
            self::applyNestedCustomFunction($query, $column);
        });
    }

    private static function applyNestedCustomFunction($query, $column)
    {
        foreach ($column['conditionList'] ?? [] as $condition) {
            if (isset($condition['function'])) {
                if (is_array($condition['function'])) {
                    foreach ($condition['function'] as $func) {
                        self::applyFunctionToQuery($query, $condition['column'], $func, $condition);
                    }
                } else {
                    self::applyFunctionToQuery($query, $condition['column'], $condition['function'], $condition);
                }
            }
        }
        if (isset($column['nested'])) {
            self::applyWithConditions($query, $column['nested']);
        }
    }

    private static function applyWithConditions($query, $columns)
    {
        foreach ($columns['with'] ?? [] as $column) {
            if (isset($column['relation'])) {
                if (isset($column['conditionList'])) {
                    $query->with([$column['relation'] => function ($subQuery) use ($column) {
                        self::applyConditionList($subQuery, $column);
                    }]);
                } else {
                    $query->with($column['relation']);
                }
            }
        }
    }

    private static function applyConditionList($query, $column)
    {
        foreach ($column['conditionList'] ?? [] as $condition) {
            if (isset($condition['function'])) {
                if (is_array($condition['function'])) {
                    foreach ($condition['function'] as $func) {
                        self::applyFunction($query, $condition, $func);
                    }
                } else {
                    self::applyFunction($query, $condition, $condition['function']);
                }
            }
        }
        if (isset($column['nested'])) {
            self::applyWithConditions($query, $column['nested']);
        }
    }

    private static function applyFunction($query, $condition, $function)
    {
        if (isset($condition['column']) && is_array($condition['column'])) {
            foreach ($condition['column'] as $col) {
                if (isset($condition['value'])) {
                    $query->{$function}($col, $condition['operator'], $condition['value']);
                } else {
                    $query->{$function}($col);
                }
            }
        } else {
            if (isset($condition['value'])) {
                $query->{$function}($condition['column'], $condition['operator'], $condition['value']);
            } else {
                $query->{$function}($condition['column']);
            }
        }
    }

    private static function applyWhereConditions($query, array $column)
    {
        foreach ($column['where'] ?? [] as $where) {
            $isRaw = $where['isRaw'] ?? false;
            $isArray = $where['isArray'] ?? false;
            $column = $where['column'];

            if ($isRaw) {
                $query->whereRaw($column, $where['operator'], $where['value']);
            } elseif ($isArray) {
                $query->whereIn($column, $where['value']);
            } else {
                $query->where($column, $where['operator'], $where['value']);
            }
        }
    }

    private static function applySearchFilter($query, array $column, $searchValue)
    {
        foreach ($column['select'] as $item) {
            self::applySearchFilterForItem($query, $item, $searchValue);
        }
    }

    private static function applySearchFilterForItem($query, $item, $searchValue)
    {
        $query->orWhere($item[0], 'like', "%{$searchValue}%");
    }

    private static function applyCustomSearch($query, array $column, $searchValue)
    {
        foreach ($column['search'] ?? [] as $column) {
            if (isset($column['relation'])) {
                if (isset($column['function'])) {
                    $query->$column['function']($column['relation'], function ($query) use ($column, $searchValue) {
                        self::applyNestedFilter($query, $column, $searchValue);
                    });
                } else {
                    $query->whereHas($column['relation'], function ($query) use ($column, $searchValue) {
                        self::applyNestedFilter($query, $column, $searchValue);
                    });
                }
            } else {
                self::applyNestedFilter($query, $column, $searchValue);
            }
        }
    }

    private static function applyNestedFilter($query, $column, $searchValue)
    {
        if (is_array($column['column'])) {
            foreach ($column['column'] ?? [] as $col) {
                $query->orWhere($col, 'like', "%{$searchValue}%");
            }
        } else {
            $query->orWhere($column['column'], 'like', "%{$searchValue}%");
        }
        if (isset($column['nested'])) {
            self::applyCustomSearch($query, $column['nested'], $searchValue);
        }
    }

    private static function applySelectColumns($query, array $column)
    {
        foreach ($column['select'] ?? [] as $item) {
            self::applySelectColumn($query, $item);
        }
    }

    private static function applyOrderBy($query, array $column)
    {
        if (request()->input('order')) {
            $orderColumnIndex = request()->input('order.0.column');
            $orderDirection = request()->input('order.0.dir');
            $orderItem = $column['order'][$orderColumnIndex] ?? null;

            if ($orderItem) {
                self::applyOrderByItem($query, $orderItem[0], $orderDirection);
            }
        } elseif (isset($column['orderBy'])) {
            foreach ($column['orderBy'] as $order) {
                self::applyOrderByItem($query, $order['column'], $order['direction']);
            }
        }
    }

    private static function applyOrderByItem($query, $column, $direction)
    {
        $query->orderBy($column, $direction);
    }

    private static function applySelectColumn($query, $item)
    {
        if (is_array($item) && count($item) > 1) {
            $query->addSelect(["{$item[0]} as {$item[1]}"]);
        } else {
            $query->addSelect([$item[0]]);
        }
    }

    private static function applyPagination($query)
    {
        if (filled(request()->input('length')) && filled(request()->input('start'))) {
            $query->offset(request()->input('start'))->limit(request()->input('length'));
        }
    }
}

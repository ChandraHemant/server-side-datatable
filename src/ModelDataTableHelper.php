<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class: ModelDataTableHelper
 * Author: Hemant Kumar Chandra
 * Category: Helpers
 *
 * This class provides helper methods for retrieving data from a database table with the option to join multiple tables.
 */

class ModelDataTableHelper
{

    /**
     * Retrieve server-side DataTables data from an Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $eloquentModel
     *   The Eloquent model to query for data.
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *   Example:
     *   $column = [
     *       'orderBy' => [
     *           ['column' => 'model_column_1', 'direction' => 'DESC'],
     *           ['column' => 'model_relation_function.relation_model_column_1', 'direction' => 'ASC'],
     *       ],
     *       'order' => [
     *           ['model_column_1'],
     *           ['model_column_2'],
     *           ['model_column_3'],
     *           ['model_relation_function.relation_model_column_1'],
     *       ],
     *       'select' => [
     *           ['model_column_1', 'alias_name_1'],
     *           ['model_column_2', 'alias_name_2'],
     *           ['model_column_3', 'alias_name_3'],
     *           ['model_relation_function.relation_model_column_1', 'alias_name_4'],
     *       ],
     *       'where' => [
     *           ['column' => 'model_column_4', 'operator' => '=', 'value' => '1'],
     *       ],
     *   ];
     *
     * @return \Illuminate\Support\Collection
     *   The result of the server-side DataTables query.
     */

    public static function getServerSideDataTable(Model $eloquentModel, array $column)
    {
        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        $searchValue = request()->input('search.value');
        if ($searchValue) {
            self::applySearchFilter($query, $column, $searchValue);
        }

        self::applySelectColumns($query, $column);

        self::applyOrderBy($query, $column);

        self::applyPagination($query);

        return $query->get();
    }

    /**
     * Count the number of filtered records for server-side DataTables.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $eloquentModel
     *   The Eloquent model to query for data.
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *
     * @return int
     *   The count of filtered records.
     */
    public static function countFilteredServerSideDataTable(Model $eloquentModel, array $column)
    {

        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        $searchValue = request()->input('search.value');
        if ($searchValue) {
            self::applySearchFilter($query, $column, $searchValue);
        }

        self::applySelectColumns($query, $column);

        return $query->count();
    }

    /**
     * Retrieve data with join tables based on provided column array.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $eloquentModel
     *   The Eloquent model to query for data.
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *
     * @return \Illuminate\Support\Collection
     *   The result of the query with join tables.
     */
    public static function getDataWithJoinTables(Model $eloquentModel, array $column)
    {

        $query = $eloquentModel->query();

        self::applyWhereConditions($query, $column);

        self::applyWithConditions($query, $column);

        self::applySelectColumns($query, $column);

        self::applyOrderBy($query, $column);

        return $query->get();
    }


    /**
     * Apply WHERE conditions to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying WHERE conditions.
     */
    private static function applyWhereConditions($query, array $column)
    {
        foreach ($column['where'] ?? [] as $where) {
            $isEncrypted = $where['encrypted'] ?? false;
            $isRaw = $where['isRaw'] ?? false;
            $isArray = $where['isArray'] ?? false;
            $column = $where['column'];

            list($relation, $column) = self::getColumnDetails($column);

            // Define column expression based on encryption
            $columnExpression = $isEncrypted ? DB::raw("md5({$column})") : $column;

            // Handle raw condition for dynamic expressions
            if ($isRaw && strpos($column, '?') !== false) {
                // Replace the placeholder dynamically
                $columnExpression = str_replace('?', $where['value'], $columnExpression);
                $query->whereRaw($columnExpression);
            } else {
                $query->when($relation !== null, function ($query) use ($relation, $columnExpression, $where, $isRaw, $isArray) {
                    $query->whereHas($relation, function ($query) use ($columnExpression, $where, $isRaw, $isArray) {
                        if ($isRaw) {
                            $query->whereRaw($columnExpression, $where['operator'], $where['value']);
                        } elseif ($isArray) {
                            $query->whereIn($columnExpression, $where['value']);
                        } else {
                            $query->where($columnExpression, $where['operator'], $where['value']);
                        }
                    });
                }, function ($query) use ($columnExpression, $where, $isRaw, $isArray) {
                    if ($isRaw) {
                        $query->whereRaw("{$query->getModel()->getTable()}.{$columnExpression}", $where['operator'], $where['value']);
                    } elseif ($isArray) {
                        $query->whereIn("{$query->getModel()->getTable()}.{$columnExpression}", $where['value']);
                    } else {
                        $query->where("{$query->getModel()->getTable()}.{$columnExpression}", $where['operator'], $where['value']);
                    }
                });
            }
        }
    }



    /**
     * Apply search filters to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying search filters.
     *
     * @param  string  $searchValue
     *   The search value entered by the user.
     */
    private static function applySearchFilter($query, array $column, $searchValue)
    {
        $query->where(function ($query) use ($column, $searchValue) {
                foreach ($column['search'] ?? [] as $search) {
                    $isColumn = $search['isColumn'] ?? false;
                    if ($isColumn && $search['condition'] == strtolower($searchValue)) {
                        self::applySearchFilterForColumn($query, $search);
                    }
                }
                foreach ($column['select'] as $item) {
                    self::applySearchFilterForItem($query, $item, $searchValue);
                }
        });
    }

    /**
     * Apply search filters for a specific item in the column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $item
     *   The item specifying the column and alias for search filtering.
     *
     * @param  string  $searchValue
     *   The search value entered by the user.
     */
    private static function applySearchFilterForItem($query, $item, $searchValue)
    {
        list($relation, $column) = self::getColumnDetails($item[0]);

        if($relation){
            $relatedTable = $query->getModel()->{$relation}()->getRelated();

            if (!self::isJoinApplied($query, $relatedTable->getTable())) {
                $query->join(
                    $relatedTable->getTable(),
                    "{$relatedTable->getTable()}.{$relatedTable->getKeyName()}",
                    '=',
                    "{$query->getModel()->getTable()}.{$query->getModel()->{$relation}()->getForeignKeyName()}"
                );
            }
            $query->orWhere("{$relatedTable->getTable()}.{$column}", 'like', "%{$searchValue}%");
        } else {
            $query->orWhere("{$query->getModel()->getTable()}.{$column}", 'like', "%{$searchValue}%");
        }

    }


    /**
     * Apply SELECT columns to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying SELECT columns.
     */
    private static function applySearchFilterForColumn($query, $item)
    {
        list($relation, $column) = self::getColumnDetails($item['column']);
        list($relation2, $column2) = self::getColumnDetails($item['value']);

        if($relation){

            $relatedTable = $query->getModel()->{$relation}()->getRelated();

            if (!self::isJoinApplied($query, $relatedTable->getTable())) {
                $query->join(
                    $relatedTable->getTable(),
                    "{$relatedTable->getTable()}.{$relatedTable->getKeyName()}",
                    '=',
                    "{$query->getModel()->getTable()}.{$query->getModel()->{$relation}()->getForeignKeyName()}"
                );
            }

            $relatedTable2 = $query->getModel()->{$relation2}()->getRelated();

            if (!self::isJoinApplied($query, $relatedTable2->getTable())) {
                $query->join(
                    $relatedTable2->getTable(),
                    "{$relatedTable2->getTable()}.{$relatedTable2->getKeyName()}",
                    '=',
                    "{$query->getModel()->getTable()}.{$query->getModel()->{$relation2}()->getForeignKeyName()}"
                );
            }
            $query->whereColumn("{$relatedTable->getTable()}.{$column}", $item['operator'], "{$relatedTable2->getTable()}.{$column2}");
        } else {
            $query->whereColumn("{$query->getModel()->getTable()}.{$column}", $item['operator'], "{$query->getModel()->getTable()}.{$column2}");
        }
    }

    /**
     * Apply SELECT columns to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying SELECT columns.
     */
    private static function applyWithConditions($query, array $columns)
    {
        foreach ($columns as $column) {
            if (isset($column['relation'])) {
                $query->with([$column['relation'] => function ($query) use ($column) {
                    self::applyNestedWithConditions($query, $column);
                }]);
            }
        }
    }

    private static function applyNestedWithConditions($query, $column)
    {
        if (isset($column['selectColumn'])) {
            foreach ($column['selectColumn'] as $select) {
                $query->addSelect($select);
            }
        }

        if (isset($column['nested'])) {
            self::applyWithConditions($query, [$column['nested']]);
        }
    }

    /**
     * Apply SELECT columns to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying SELECT columns.
     */
    private static function applySelectColumns($query, array $column)
    {
        foreach ($column['select'] ?? [] as $item) {
            self::applySelectColumn($query, $item);
        }
    }

    /**
     * Apply ORDER BY clauses to the query based on the provided column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $column
     *   The array specifying ORDER BY clauses.
     */
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

    /**
     * Apply ORDER BY clause for a specific item in the column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  mixed  $column
     *   The column to order by.
     *
     * @param  string  $direction
     *   The direction of the ordering (ASC or DESC).
     */
    private static function applyOrderByItem($query, $column, $direction)
    {
        list($relation, $column) = self::getColumnDetails($column);

        if ($relation) {
            $relatedTable = $query->getModel()->{$relation}()->getRelated();

            if (!self::isJoinApplied($query, $relatedTable->getTable())) {
                $query->join(
                    $relatedTable->getTable(),
                    "{$relatedTable->getTable()}.{$relatedTable->getKeyName()}",
                    '=',
                    "{$query->getModel()->getTable()}.{$query->getModel()->{$relation}()->getForeignKeyName()}"
                );
            }

            $query->orderBy("{$relatedTable->getTable()}.{$column}", $direction);
        } else {
            $query->orderBy("{$query->getModel()->getTable()}.{$column}", $direction);
        }
    }

    /**
     * Apply SELECT column to the query for a specific item in the column array.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  array  $item
     *   The item specifying the column and alias for SELECT.
     */
    private static function applySelectColumn($query, $item)
    {
        list($relation, $column) = self::getColumnDetails($item[0]);

        if ($relation) {
            $relatedTable = $query->getModel()->{$relation}()->getRelated();

            if (!self::isJoinApplied($query, $relatedTable->getTable())) {
                $query->join(
                    $relatedTable->getTable(),
                    "{$relatedTable->getTable()}.{$relatedTable->getKeyName()}",
                    '=',
                    "{$query->getModel()->getTable()}.{$query->getModel()->{$relation}()->getForeignKeyName()}"
                );
            }

            if (is_array($item) && count($item) > 1) {
                $query->addSelect(["{$relatedTable->getTable()}.{$column} as {$item[1]}"]);
            }else{
                $query->addSelect(["{$relatedTable->getTable()}.{$column}"]);
            }

        } else {
            if (is_array($item) && count($item) > 1) {
                $query->addSelect("{$query->getModel()->getTable()}.{$item[0]} as {$item[1]}");
            }else{
                $query->addSelect("{$query->getModel()->getTable()}.{$item[0]}");
            }
        }
    }

    /**
     * Get details about a column, including any related table information.
     *
     * @param  string  $column
     *   The column name to get details for.
     *
     * @return array
     *   An array containing the relation name (if any) and the actual column name.
     */
    private static function getColumnDetails($column)
    {
        if (strpos($column, '.') !== false) {
            return explode('.', $column);
        }

        return [null, $column];
    }

    /**
     * Apply pagination to the query based on the request parameters.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     */
    private static function applyPagination($query)
    {
        if (filled(request()->input('length')) && filled(request()->input('start'))) {
            $query->offset(request()->input('start'))->limit(request()->input('length'));
        }
    }

    /**
     * Check if a join has already been applied to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *   The query builder instance.
     *
     * @param  string  $table
     *   The table name to check for in the existing joins.
     *
     * @return bool
     *   TRUE if the join has already been applied; otherwise, FALSE.
     */
    private static function isJoinApplied($query, $table)
    {
        return collect($query->getQuery()->joins ?? [])
            ->contains('table', $table);
    }
}

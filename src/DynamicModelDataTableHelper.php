<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Database\Eloquent\Model;

class DynamicModelDataTableHelper
{
    /**
     * Retrieve server-side DataTables data from an Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $eloquentModel
     *   The Eloquent model to query for data.
     *
     * @param  array  $dynamicConditions
     *   An array specifying the dynamic conditions for the query.
     *   Example:
     *   $dynamicConditions = [
     *       ['method' => 'orderBy', 'args' => [function() { return ['column' => 'model_column_1', 'direction' => 'DESC']; }]],
     *       ['method' => 'orderBy', 'args' => [function() { return ['column' => 'model_relation_function.relation_model_column_1', 'direction' => 'ASC']; }]],
     *       ['method' => 'select', 'args' => [['model_column_1', 'alias_name_1']]],
     *       ['method' => 'whereHas', 'args' => ['relationName', function($query) { $query->where('column', 'value'); }]],
     *       ['method' => 'whereColumn', 'args' => ['column1', '=', 'column2', 'condition' => 'searchValue']],
     *   ];
     *
     * @param  array  $searchColumns
     *   An array specifying columns to search in.
     *
     * @param  array  $searchRelationships
     *   An array specifying relationships to search in.
     *
     * @return \Illuminate\Support\Collection
     *   The result of the server-side DataTables query.
     */
    public static function getServerSideDataTable(Model $eloquentModel, $dynamicConditions, $searchColumns = [], $searchRelationships = [])
    {
        $model = $eloquentModel->query();

        // Apply dynamic conditions to the query
        self::applyDynamicConditions($model, $dynamicConditions, $searchColumns, $searchRelationships);

        // Apply pagination to the query
        self::applyPagination($model);

        // Retrieve the data
        return $model->get();
    }

    /**
     * Count the filtered records for server-side DataTables.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $eloquentModel
     *   The Eloquent model to query for data.
     *
     * @param  array  $dynamicConditions
     *   An array specifying the dynamic conditions for the query.
     *
     * @param  array  $searchColumns
     *   An array specifying columns to search in.
     *
     * @param  array  $searchRelationships
     *   An array specifying relationships to search in.
     *
     * @return int
     *   The count of filtered records.
     */
    public static function countFilteredServerSideDataTable(Model $eloquentModel, $dynamicConditions, $searchColumns = [], $searchRelationships = [])
    {
        $model = $eloquentModel->query();

        // Apply dynamic conditions to the query
        self::applyDynamicConditions($model, $dynamicConditions, $searchColumns, $searchRelationships);

        // Count the filtered records
        return $model->count();
    }

    /**
     * Apply dynamic conditions to the query.
     *
     * @param  mixed  $model
     *   The query builder instance.
     *
     * @param  array  $dynamicConditions
     *   An array specifying the dynamic conditions for the query.
     *
     * @param  array  $searchColumns
     *   An array specifying columns to search in.
     *
     * @param  array  $searchRelationships
     *   An array specifying relationships to search in.
     *
     * @return mixed
     *   The modified query builder instance.
     */
    public static function applyDynamicConditions($model, $dynamicConditions, $searchColumns = [], $searchRelationships = [])
    {
        $searchValue = request('search.value');

        // Iterate over dynamic conditions
        foreach ($dynamicConditions as $condition) {
            $method = $condition['method'];
            $args = $condition['args'];

            // Replace callable arguments with their return values
            if (is_callable($args[count($args) - 1])) {
                $args[count($args) - 1] = $args[count($args) - 1]($model);
            }

            // Apply different methods based on conditions
            if ($method === 'select') {
                if ($condition['relation']) {
                    // Load specified relationships
                    $withRelations = [];
                    foreach ($condition['relation'] as $relation) {
                        $withRelations[] = $relation;
                    }
                    $model->with($withRelations);
                }
                // Select specific columns
                $model->select(...$args);
            } elseif ($method === 'orderBy') {
                // Apply ordering
                $orderByArgs = $args[0]();
                $model->orderBy($orderByArgs[0], $orderByArgs[1]);
            } elseif ($method === 'whereHas') {
                // Apply relationship constraint
                $relationship = $args[0];
                $constraint = $args[1];
                $model->whereHas($relationship, $constraint);
            } elseif ($method === 'whereColumn') {
                // Apply column comparison constraint
                $column1 = $args[0];
                $operator = $args[1];
                $column2 = $args[2];
                if (strtolower($searchValue) == $condition['condition']) {
                    return $model->whereColumn($column1, $operator, $column2);
                } else {
                    continue;
                }
            } else {
                // Apply other dynamic methods
                $model = $model->$method(...$args);
            }

            // Apply search criteria if provided
            if ($searchValue !== null) {
                $model = $model->where(function ($query) use ($searchValue, $searchColumns, $searchRelationships) {
                    if (!empty($searchColumns)) {
                        // Search in specified columns
                        $query->where(function ($query) use ($searchValue, $searchColumns) {
                            foreach ($searchColumns as $column) {
                                $query->orWhere($column, 'like', '%' . $searchValue . '%');
                            }
                        });
                    }
                    if (!empty($searchRelationships)) {
                        // Search in specified relationships
                        foreach ($searchRelationships as $relationship => $columns) {
                            $query->orWhereHas($relationship, function ($query) use ($searchValue, $columns) {
                                $query->where(function ($query) use ($searchValue, $columns) {
                                    foreach ($columns as $column) {
                                        $query->orWhere($column, 'like', '%' . $searchValue . '%');
                                    }
                                });
                            });
                        }
                    }
                });
            }
        }

        return $model;
    }


    /**
     * Apply pagination to the query based on the request parameters.
     *
     * @param  mixed  $query
     *   The query builder instance.
     */
    private static function applyPagination($query)
    {
        if (filled(request()->input('length')) && filled(request()->input('start'))) {
            $query->offset(request()->input('start'))->limit(request()->input('length'));
        }
    }
}

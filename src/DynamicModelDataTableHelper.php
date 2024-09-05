<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Database\Eloquent\Model;

/**
 * Class: DynamicModelDataTableHelper
 * Author: Hemant Kumar Chandra
 * Category: Helpers
 *
 * This class provides helper methods for retrieving data from a database table with the option to join multiple tables.
 */
class DynamicModelDataTableHelper
{
    private $eloquentModel;
    private $dynamicConditions;
    private $searchColumns;
    private $searchRelationships;

    /**
     * Constructor method to initialize the class with required parameters.
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
     */
    public function __construct(Model $eloquentModel, array $dynamicConditions = [], array $searchColumns = [], array $searchRelationships = [])
    {
        $this->eloquentModel = $eloquentModel;
        $this->dynamicConditions = $dynamicConditions;
        $this->searchColumns = $searchColumns;
        $this->searchRelationships = $searchRelationships;
    }

    /**
     * Retrieve server-side DataTables data from the provided Eloquent model.
     *
     * @return \Illuminate\Support\Collection
     *   The result of the server-side DataTables query.
     */

    public function getServerSideDataTable(bool $query = false)
    {
        $model = $this->eloquentModel->query();

        // Apply dynamic conditions to the query
        $this->applyDynamicConditions($model, $this->dynamicConditions, $this->searchColumns, $this->searchRelationships);

        // Apply pagination to the query
        $this->applyPagination($model);

        // Retrieve the data
        if($query){
            return $model;
        }else{
            return $model->get();
        }
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
    public function countFilteredServerSideDataTable()
    {
        $model = $this->eloquentModel->query();

        // Apply dynamic conditions to the query
        $this->applyDynamicConditions($model, $this->dynamicConditions, $this->searchColumns, $this->searchRelationships);

        // Count the filtered records
        return $model->count();
    }

    /**
     * Apply dynamic conditions to the query.
     *
     * @param  mixed  $model
     *   The query builder instance.
     */
    private function applyDynamicConditions($model, $dynamicConditions, $searchColumns = [], $searchRelationships = [])
    {
        // Iterate over dynamic conditions
        $searchValue = request('search.value');
        foreach ($dynamicConditions as $condition) {
            $method = $condition['method'];

            // Handle arguments, including callable resolution
            $args = $this->resolveArguments($condition['args'] ?? []);

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
            } elseif ($method === 'sortBy') {
                $orderColumnIndex = request('order.0.column');
                $orderDirection = request('order.0.dir');

                // Make sure $orderDirection is a string
                $orderDirection = is_string($orderDirection) ? strtolower($orderDirection) : 'asc';
                $orderDirection = $orderDirection === 'desc' ? 'desc' : 'asc';

                // Apply ordering
                $model->orderBy($args[$orderColumnIndex], $orderDirection);
            } elseif ($method === 'nestedCondition') {
                // Apply nested conditions
                $model->{$condition['parentMethod']}(function ($query) use ($condition) {
                    foreach ($condition['nestedMethod'] as $nestedConditions) {
                        $childMethod = $nestedConditions['childMethod'];
                        foreach ($nestedConditions as $nestedCondition) {
                            if ($nestedCondition !== $nestedConditions['childMethod']) {
                                $nestedArgs = $this->resolveArguments($nestedCondition['args'] ?? []);
                                $query->{$childMethod}(...$nestedArgs);
                            }
                        }
                    }
                });
            } elseif ($method === 'whereRelation') {
                // Apply relationship constraint
                $model->{$condition['parentMethod']}($condition['relation'], function ($query1) use ($args, $condition){
                    return $query1->{$condition['childMethod']}(...$args);
                });
            } elseif ($method === 'whereHas') {
                // Apply relationship constraint
                $model->whereHas($condition['relation'], function ($query1) use ($args){
                    return $query1->where(...$args);
                });
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
            } elseif ($method === 'nestedRelationCondition') {
                $model->{$condition['parentMethod']}($condition['relation'], function ($query) use ($condition) {
                    if (isset($condition['args'])) {
                        $args = $this->resolveArguments($condition['args']);
                        $query->where(...$args);
                    }
                    foreach ($condition['nestedMethod'] as $nestedCondition) {
                        $query->{$nestedCondition['childMethod']}($nestedCondition['relation'], function ($subQuery) use ($nestedCondition) {
                            foreach ($nestedCondition['nestedConditions'] as $condition) {
                                $args = isset($condition['args']) ? $this->resolveArguments($condition['args']) : [];
                                $subQuery->{$condition['method']}(...$args);
                            }
                        });
                    }
                });
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
     * Resolve arguments array, replacing callable arguments with their return values.
     *
     * @param array $args
     * @return array
     */
    private function resolveArguments(array $args)
    {
        if (empty($args)) {
            return [];
        }

        foreach ($args as &$arg) {
            if (is_callable($arg)) {
                $arg = $arg(); // Execute the callable and replace with its return value
            }
        }

        return $args;
    }


    /**
     * Apply pagination to the query based on the request parameters.
     *
     * @param  mixed  $query
     *   The query builder instance.
     */
    private function applyPagination($query)
    {
        if (filled(request()->input('length')) && filled(request()->input('start'))) {
            $query->offset(request()->input('start'))->limit(request()->input('length'));
        }
    }


    /*
    * Example Usage:
    */

    /*
    $dynamicConditions = [
        [
            'method' => 'whereColumn',
            'args' => ['column1', '>=', 'column2'],
            'condition' => 'loss'
        ],
        [
            'method' => 'whereColumn',
            'args' => ['column1', '<', 'column2'],
            'condition' => 'profit'
        ],
        [
            'method' => 'whereRaw',
            'args' => ['YEAR(column3) = ?', session()->get('financialYear')]
        ],
        [
            'method' => 'whereIn',
            'args' => ['column4', session()->get('values')]
        ],
        [
            'method' => 'where',
            'args' => ['column6', 0]
        ],
        [
            'method' => 'whereHas',
            'args' => ['column7', 'LIKE', $request->input('status')],
            'relation' => 'o_status'
        ],
        [
            'method' => 'whereRelation',
            'parentMethod' => 'whereHas',
            'childMethod' => 'whereIn',
            'args' => ['column', 'value'],
            'relation' => 'relationship_method'
        ],
        [
            'method' => 'nestedCondition',
            'parentMethod' => 'where',
            'nestedMethod' => [
                [
                    'childMethod' => 'where',
                    [
                        'method' => 'where',
                        'args' => ['column1', '=', 5]
                    ],
                    [
                        'method' => 'whereIn',
                        'args' => ['column2', [1, 4, 7]]
                    ],
                ],
                [
                    'childMethod' => 'orWhere',
                    [
                        'method' => 'where',
                        'args' => ['column1', '!=', 5]
                    ],
                    [
                        'method' => 'whereIn',
                        'args' => ['column3', [1, 4, 7]]
                    ],
                ],
            ],
        ],
        [
            'method' => 'nestedRelationCondition',
            'parentMethod' => 'whereHas',
            'relation' => 'relationship_method',
            'args' => [['column1', $user->id], ['column2', $statusId]],
            'nestedMethod' => [
                [
                    'childMethod' => 'whereDoesntHave',
                    'relation' => 'relationship_method1',
                    'nestedConditions' => [
                        [
                            'method' => 'where',
                            'args' => ['log_request_id', $requestId]
                        ],
                        [
                            'method' => 'where',
                            'args' => [['column1', $user->id], ['column2', $statusId]]
                        ]
                    ]
                ]
            ]
        ],
        [
            'method' => 'select',
            'args' => ['column1','column2','column3','column4','column5','column6','column7','column8','column9'],
            'relation' => ['relation1','relation2','relation3','relation4','relation5','relation6','relation7','relation8','relation9'],
        ],
        [
            'method' => 'orderBy',
            'args' => ['column1','column2','column3','column4','column5','column6','column7','column8','column9']
        ]
    ];

    // Define search value, columns, and relationships
    $searchColumns = ['column1','column2','column3','column4','column5','column6','column7','column8','column9'];

    $searchRelationships = [
        'relation1' => ['column1'],
        'relation2' => ['column2'],
        'relation3' => ['column3'],
        'relation4' => ['column4'],
        'relation5' => ['column5'],
        'relation6' => ['column6'],
        'relation7' => ['column7'],
        'relation8' => ['column8'],
        'relation9' => ['column9'],
    ];
    $helper = new DynamicModelDataTableHelper(
        eloquentModel: $modelInstance,
        dynamicConditions: $dynamicConditions,
        searchColumns: $searchColumns,
        searchRelationships: $searchRelationships
    );

    $result = $helper->getServerSideDataTable();
    */
}

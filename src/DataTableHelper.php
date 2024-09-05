<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Support\Facades\DB;

/**
 * Class: DataTableHelper
 * Author: Hemant Kumar Chandra
 * Category: Helpers
 *
 * This class provides helper methods for retrieving data from a database table with the option to join multiple tables.
 */

class DataTableHelper
{
    /**
     * Retrieve data from a database table with the option to join multiple tables.
     *
     * @param  array|string|null  $table
     *   The name of the database table to retrieve data from, or an array of table names if joining multiple tables.
     *   If not specified, the default table name will be used.
     *
     * @param  array|string|null  $join
     *   An array defining the join conditions and table aliases for each table to join.
     *   The array should be structured as follows:
     *     - 'tables': An array of table names to join in the query, in the order that they should be joined.
     *     - 'fields': An array of field names to select from each table in the query, in the same order as the 'tables' array.
     *     - 'joinType': An array of join types to use for each join, in the same order as the 'tables' array.
     *   If not specified, no join will be performed.
     *   Example:
    *       $join = array(
    *           'tables'=> array(
    *               array(
    *                   'table_1', 'table_2'
    *               ),
    *           ),
    *           'fields'=>array(
    *               array(
    *                   'column_table_1', 'column_table_2'
    *               ),
    *           ),
    *           'join_type'=>array(
    *               'inner',
    *           ),
    *       );
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *   Example:
     *
     *       $column = array(
     *           'table'=> 'table_1',
     *           'order'=> array(
     *               array('table_1', 'column_1_table_1'),
     *               array('table_1', 'column_2_table_1'),
     *               array('table_1', 'column_3_table_1'),
     *               array('table_2', 'column_1_table_2')
     *           ),
     *           'select'=>array(
     *               array('table_1', 'column_1_table_1'),
     *               array('table_1', 'column_2_table_1'),
     *               array('table_1', 'column_3_table_1'),
     *               array('table_2', 'column_1_table_2')
     *           ),
     *           'where'=>array(
     *               array('column' => 'table_1.column_name', 'operator' => '=', 'value' => '1')
     *           ),
     *           'orderBy'=>array(
     *               array('column' => 'column_.column_name', 'direction' => 'DESC')
     *           ),
     *       );
     *
     * @return \Illuminate\Support\Collection
     *   The result of the database query.
     */

    public function getServerSideDataTable($column, $join = array())
    {
        $query = $this->buildQuery($column, $join);

        $this->applySearchFilter($query, $column['select']);

        $this->applyOrdering($query, $column);

        $this->applyPagination($query);

        return $query->get();
    }

    /**
     * Count the number of filtered records for server-side DataTables.
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *
     * @param  array  $join
     *   An array defining the join conditions and table aliases for each table to join.
     *   Example:
     *   $join = [
     *       'tables' => ['table1', 'table2'],
     *       'fields' => [['field1', 'field2'], ['field3', 'field4']],
     *       'joinType' => ['inner', 'left'],
     *   ];
     *
     * @return int
     *   The count of filtered records.
     */
    public function countFilteredServerSideDataTable($column, $join = array())
    {
        $query = $this->buildQuery($column, $join);

        $this->applySearchFilter($query, $column['select']);

        return $query->count();
    }

    /**
     * Retrieve data with join tables based on the provided column configuration and join information.
     *
     * @param  array  $column
     *   An array specifying the columns, ordering, and filtering conditions for the query.
     *
     * @param  array  $join
     *   An array defining the join conditions and table aliases for each table to join.
     *   Example:
     *   $join = [
     *       'tables' => ['table1', 'table2'],
     *       'fields' => [['field1', 'field2'], ['field3', 'field4']],
     *       'joinType' => ['inner', 'left'],
     *   ];
     *
     * @return \Illuminate\Support\Collection
     *   The result of the query with join tables.
     */
    public function getDataWithJoinTables($column, $join = array())
    {
        $query = $this->buildQuery($column, $join);

        $this->applyOrdering($query, $column);

        return $query->get();
    }

    private function buildQuery($column, $join)
    {
        $query = DB::table($column['table']);

        $this->applyWhereConditions($query, $column);
        $this->applySelectColumns($query, $column['select']);
        $this->applyJoinTables($query, $join);

        return $query;
    }

    private function applyWhereConditions($query, $column)
    {
        if (isset($column['where'])) {
            foreach ($column['where'] as $where) {
                $query->where($where['column'], $where['operator'], $where['value']);
            }
        }
    }

    private function applySelectColumns($query, $selectColumns)
    {
        if (isset($selectColumns)) {
            foreach ($selectColumns as $select) {
                if(count($select)>2){
                    $query->addSelect("{$select[0]}.{$select[1]} as {$select[2]}");
                }else{
                    $query->addSelect("{$select[0]}.{$select[1]}");
                }
            }
        }
    }

    private function applyJoinTables($query, $join)
    {
        if (!empty($join) && isset($join['tables'])) {
            foreach ($join['tables'] as $i => $joinTable) {
                $type = $join['joinType'][$i] ?? 'inner';
                $queryMethod = $type == 'left' ? 'leftJoin' : ($type == 'right' ? 'rightJoin' : 'join');

                $query->$queryMethod(
                    $joinTable[0],
                    "{$joinTable[0]}.{$join['fields'][$i][0]}",
                    '=',
                    "{$joinTable[1]}.{$join['fields'][$i][1]}"
                );
            }
        }
    }

    private function applySearchFilter($query, $selectColumns)
    {
        $i = 0;
        foreach ($selectColumns as $item) {
            if (request()->input('search.value')) {
                $query->orWhere(
                    function ($query) use ($item) {
                        $query->where("{$item[0]}.{$item[1]}", 'like', '%' . request()->input('search.value') . '%');
                    }
                );
            }
            $i++;
        }
    }

    private function applyOrdering($query, $column)
    {
        if (request()->input('order') && isset($column['order'])) {
            $query->orderBy(
                "{$column['order'][request()->input('order.0.column')][0]}.{$column['order'][request()->input('order.0.column')][1]}",
                request()->input('order.0.dir')
            );
        } elseif (isset($column['orderBy'])) {
            foreach ($column['orderBy'] as $order) {
                $query->orderBy($order['column'], $order['direction']);
            }
        }
    }

    private function applyPagination($query)
    {
        if (filled(request()->input('length')) && filled(request()->input('start'))) {
            $query->offset(request()->input('start'))->limit(request()->input('length'));
        }
    }
}


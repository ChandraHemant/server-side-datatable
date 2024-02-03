<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Support\Facades\DB;

class TableDataHelper{



    /**
        * @author		Hemant Kumar Chandra
        * @category	    Table Data Helpers
        * @method	    getTableData(), countFilteredTableData(), 
        *
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
        *     - 'join_type': An array of join types to use for each join, in the same order as the 'tables' array.
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
        * @param  array|string|null  $where
        *         An array of key-value pairs or MD5 hashes specifying the where clause for the query.
        *         Example: ['status' => 'approved', 'md5(id)' => '63b431791c7a3f3e8b7a9fb81d63f7c2']
        *         Default: null (returns all rows from the table)
    */


    public function getServerSideDataTable($table, $select, $column_order, $column_search, $order, $where = '', $join = array())
    {
        $query = DB::table($table)->select($select);

        if (!empty($join) || $join != null) {
            foreach ($join['tables'] as $i => $joinTable) {
                $query->join(
                    $joinTable[0],
                    $joinTable[0] . '.' . $join['fields'][$i][0] . ' = ' . $joinTable[1] . '.' . $join['fields'][$i][1],
                    $join['join_type'][$i]
                );
            }
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $i = 0;

        foreach ($column_search as $item) {
            if (request()->input('search.value')) {
                if ($i === 0) {
                    $query->where(function ($query) use ($item) {
                        $query->where($item, 'like', '%' . request()->input('search.value') . '%');
                    });
                } else {
                    $query->orWhere($item, 'like', '%' . request()->input('search.value') . '%');
                }

                if (count($column_search) - 1 == $i) {
                    $query->orWhere(function ($query) {
                        $query->whereNotNull('id'); // Dummy condition to close the group
                    });
                }
            }
            $i++;
        }

        if (request()->input('order')) {
            $query->orderBy($column_order[request()->input('order.0.column')], request()->input('order.0.dir'));
        } elseif (isset($order)) {
            $query->orderBy(key($order), $order[key($order)]);
        }

        if (request()->input('length') != -1) {
            $query->skip(request()->input('start'))->take(request()->input('length'));
        }

        return $query->get()->toArray();
    }

    public function countFilteredServerSideDataTable($table, $select, $column_order, $column_search, $order, $where = '', $join = array())
    {
        $query = DB::table($table)->select($select);

        if (!empty($join) || $join != null) {
            foreach ($join['tables'] as $i => $joinTable) {
                $query->join(
                    $joinTable[0],
                    $joinTable[0] . '.' . $join['fields'][$i][0] . ' = ' . $joinTable[1] . '.' . $join['fields'][$i][1],
                    $join['join_type'][$i]
                );
            }
        }

        if (!empty($where)) {
            $query->where($where);
        }

        $i = 0;

        foreach ($column_search as $item) {
            if (request()->input('search.value')) {
                if ($i === 0) {
                    $query->where(function ($query) use ($item) {
                        $query->where($item, 'like', '%' . request()->input('search.value') . '%');
                    });
                } else {
                    $query->orWhere($item, 'like', '%' . request()->input('search.value') . '%');
                }

                if (count($column_search) - 1 == $i) {
                    $query->orWhere(function ($query) {
                        $query->whereNotNull('id'); // Dummy condition to close the group
                    });
                }
            }
            $i++;
        }

        if (request()->input('order')) {
            $query->orderBy($column_order[request()->input('order.0.column')], request()->input('order.0.dir'));
        } elseif (isset($order)) {
            $query->orderBy(key($order), $order[key($order)]);
        }

        return $query->count();
    }

    public function getTableDataWithJoinParam($table, $join = array(), $param = '')
    {
        $query = DB::table($table);

        if (!empty($join) || $join != null) {
            foreach ($join['tables'] as $i => $joinTable) {
                $query->join(
                    $joinTable[0],
                    $joinTable[0] . '.' . $join['fields'][$i][0] . ' = ' . $joinTable[1] . '.' . $join['fields'][$i][1],
                    $join['join_type'][$i]
                );
            }
        }

        if (!empty($param)) {
            $query->where($param);
        }

        return $query->get();
    }


}


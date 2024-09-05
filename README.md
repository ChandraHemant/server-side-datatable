# Server Side Data Table Packages

This package provides a convenient method to fetch data for server-side DataTables in Laravel applications.

## Installation

You can install this package via Composer:

```bash
composer require chandra-hemant/server-side-datatable
```
## Overview

The ChandraHemant/ServerSideDatatable package provides helper methods for retrieving data from a database table with the option to join multiple tables. This package is designed to facilitate server-side processing for DataTables in Laravel applications.

## Class Overview

##### Namespace: ChandraHemant\ServerSideDatatable
##### Author: Hemant Kumar Chandra

# DynamicModelDataTableHelper Class

The `DynamicModelDataTableHelper` class provides a set of methods for retrieving and manipulating data from a database table, especially tailored for use with Laravel's Eloquent ORM. This guide outlines how to effectively utilize these methods to implement server-side data tables with dynamic conditions, pagination, and search functionality.

## Usage

### Retrieving Data

You can retrieve data from your database table using the `getServerSideDataTable` method.

```php
use App\Models\YourModel; // Import your Eloquent model

$dynamicConditions = [
    // Specify your dynamic conditions here
];
$searchColumns = [
    // Specify your searchable columns here
];
$searchRelationships = [
    // Specify your dynamic relationships here
];
$helper = new DynamicModelDataTableHelper(
    eloquentModel: new YourModel(),
    dynamicConditions: $dynamicConditions,
    searchColumns: $searchColumns,
    searchRelationships: $searchRelationships
);

$result = $helper->getServerSideDataTable();
```

### Counting Filtered Records

To count the filtered records without fetching all data, you can use the `countFilteredServerSideDataTable` method.

```php
use App\Models\YourModel; // Import your Eloquent model

$dynamicConditions = [
    // Specify your dynamic conditions here
];
$searchColumns = [
    // Specify your searchable columns here
];
$searchRelationships = [
    // Specify your dynamic relationships here
];
$helper = new DynamicModelDataTableHelper(
    eloquentModel: new YourModel(),
    dynamicConditions: $dynamicConditions,
    searchColumns: $searchColumns,
    searchRelationships: $searchRelationships
);

$result = $helper->countFilteredServerSideDataTable();
```

### Dynamic Conditions

The 'dynamicConditions' parameter allows you to customize your database query based on various conditions. These conditions can include ordering, selecting specific columns, filtering based on relationships, and more.

```php

 $dynamicConditions = [
    [
        'method' => 'whereColumn',
        'args' => ['column1', '>=', 'column2'],
        'condition' => 'loss'
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
        'args' => ['relation1', function ($query1) {
            $query1->where('column1', '=', 4);
        }]
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

```

### Search Functionality

You can enable search functionality by providing columns and relationships to search in.

```php

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
```

### Pagination

Pagination is applied automatically based on the request parameters.


## Constructor Parameters

* `$eloquentModel` (Illuminate\Database\Eloquent\Model): The Eloquent model to query for data.
* `$dynamicConditions` (array): An array specifying the dynamic conditions for the query.
* `$searchColumns` (array): An array specifying columns to search in.
* `$searchRelationships` (array): An array specifying relationships to search in.

## Methods

`getServerSideDataTable(bool $query = false): Illuminate\Support\Collection`
Retrieve server-side DataTables data from the provided Eloquent model.

### Parameters:
* `$query` (bool, optional): Whether to return the query builder instead of executing the query. Default is `false`.


### Example

Here's an example of how you can utilize these methods in your controller:

```php
use App\Models\YourModel;
use ChandraHemant\ServerSideDatatable\DynamicModelDataTableHelper;

class YourController extends Controller
{
    public function index()
    {
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
                'args' => ['relation1', function ($query1) {
                    $query1->where('column1', '=', 4);
                }]
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

        $data = $helper->getServerSideDataTable();

        return view('your_view', compact('data'));
    }
}
```

## Conclusion

This guide provides a basic overview of how to use the `DynamicModelDataTableHelper` class in your Laravel application. By following these instructions, you can easily implement server-side data tables with dynamic conditions, pagination, and search functionality.



# DataTableHelper Class

The DataTableHelper class is a PHP utility designed to enhance server-side data retrieval for DataTables within Laravel applications. DataTables is a popular JavaScript library used for creating interactive and dynamic tables in web applications. This class provides a set of methods to build and execute flexible database queries, supporting features such as table joins, column selection, search filtering, ordering, and pagination. 

## Methods

```bash
getServerSideDataTable($column, $join = array())
```
Retrieves data from a specified database table, with the option to join multiple tables. This method applies search filtering, ordering, and pagination based on the provided parameters.

```bash
countFilteredServerSideDataTable($column, $join = array())
```
Counts the number of filtered rows based on the specified criteria. Useful for determining the total count before pagination.

```bash
getDataWithJoinTables($column, $join = array())
```
Retrieves data with the option to join multiple tables. This method applies ordering based on the provided parameters.

## Private Methods

```bash
buildQuery($column, $join)
```
Builds the initial query using the specified table and applies where conditions, select columns, and join tables.

```bash
applyWhereConditions($query, $column)
```
Applies WHERE conditions to the query based on the provided parameters.

```bash
applySelectColumns($query, $selectColumns)
```
Applies SELECT columns to the query based on the provided parameters.

```bash
applyJoinTables($query, $join)
```
Applies JOIN clauses to the query based on the provided join conditions and types.

```bash
applySearchFilter($query, $selectColumns)
```
Applies search filtering to the query based on the user-provided search value.

```bash
applyOrdering($query, $column)
```
Applies ordering to the query based on the user-provided order parameters or predefined order.

```bash
applyPagination($query)
```
Applies pagination to the query based on the user-provided start and length parameters.

## Usage Example

```php
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

// Specify columns, ordering, and filtering conditions
$column = array(
    'table'=> 'products',
    'order'=> array(
        array('products', 'prod_id'),
        array('products', 'prod_name'),
        array('products', 'prod_type'),
        array('products', 'prod_descr'),
        array('products', 'prod_total_price'),
        array('products', 'prod_nsv'),
        array('productCategory', 'cat_name')
    ),
    'select'=>array(
        array('products', 'prod_id'),
        array('products', 'prod_name'),
        array('products', 'prod_type'),
        array('products', 'prod_descr'),
        array('products', 'prod_total_price'),
        array('products', 'prod_nsv'),
        array('productCategory', 'cat_name')
    ),
    'where'=>array(
        array('column' => 'products.mf_id', 'operator' => '=', 'value' => '1')
    ),
    'orderBy'=>array(
        array('column' => 'products.prod_id', 'direction' => 'DESC')
    ),
);
// Specify join conditions and types
$join = array(
    'tables'=> array(
        array('productCategory', 'products'),
    ),
    'fields'=>array(
        array('cat_id', 'cat_id'),   
    ),
    'joinType'=>array(
        'left', 
    ),
);

$list = DataTableHelper::getServerSideDataTable($column, $join);
$count = DataTableHelper::countFilteredServerSideDataTable($column, $join);

/**
 * Construct the output array for a server-side DataTable response.
 *
 * @var array $output
 *   An associative array containing the following keys:
 *     - 'draw': The DataTable draw counter to control asynchronous requests.
 *                Example: 'draw' => request()->input('draw')
 *     - 'recordsTotal': The total number of records in the entire dataset, regardless of filtering.
 *                Example: 'recordsTotal' => sizeof($list)
 *     - 'recordsFiltered': The total number of records after filtering, considering the provided column and join configurations.
 *                Example: 'recordsFiltered' => $count
 *     - 'data': The actual data to be displayed in the DataTable.
 *                Example: 'data' => $data
 */

$data = array();
foreach ($list as $val) {
    $row = array();
                    
    $row[] = '#'.$val->prod_id;			
    $row[] = $val->prod_name;			
    $row[] = $val->prod_type;						
    $row[] = $val->prod_descr;						
    $row[] = $val->prod_total_price;							
    $row[] = $val->prod_nsv;							
    $row[] = $val->cat_name;	
        
    $data[] = $row;
}

$output = array(
    "draw"            => request()->input('draw'),
    "recordsTotal"    => sizeof($list),
    "recordsFiltered" => $count,
    "data"            => $data,
);

echo json_encode($output);
```

# Eloquent Model DataTable Helper
This PHP class, `ModelDataTableHelper`, provides helper methods for retrieving server-side DataTables data from an Eloquent model. It offers options for ordering, filtering, and joining multiple tables, making it a versatile tool for interacting with database tables in Laravel applications.

### `getServerSideDataTable`

Retrieve server-side DataTables data from an Eloquent model.

## Usage Example of Eloquent Model DataTable Helper

```php

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
 *           ['column' => 'model_relation_function.relation_model_column_1', 'operator' => '=', 'value' => '1', 'encrypted' => true],
 *           ['column' => 'YEAR(model_column_5) = ?', 'operator' => '=', 'value' => '2024', 'isRaw'=>true],
 *           ['column' => 'model_relation_function_1.relation_model_column_1', 'operator' => '!=', 'value' => '["5,"6"]', 'isArray'=>true],
 *           ['column' => 'model_column_6', 'operator' => '>=', 'value' => 'model_column_7', 'isColumn' => true],
 *       ],
 *   ];
 *
 * @return \Illuminate\Support\Collection
 *   The result of the server-side DataTables query.
 */

$column = [
    'orderBy' => [
        ['column' => 'prod_id', 'direction' => 'DESC'],
        ['column' => 'productCategory.cat_name', 'direction' => 'ASC'],
    ],
    'order' => [
        ['prod_id'],
        ['prod_name'],
        ['prod_type'],
        ['prod_descr'],
        ['prod_total_price'],
        ['prod_nsv'],
        ['productCategory.cat_name'],
    ],
    'select' => [
        ['prod_id', 'id'],
        ['prod_name', 'name'],
        ['prod_type', 'type'],
        ['prod_descr', 'description'],
        ['prod_total_price', 'total_price'],
        ['prod_nsv', 'nsv'],
        ['basic_price', 'price'],
        ['productCategory.cat_name', 'category_name'],
    ],
    'with' => [
        ['relation'=>'audit_employee'],
        ['relation'=>'audits', 'nested' => [ 'relation' => 'user_detail', 'selectColumn' => ['emp_id', 'emp_name']]],
    ],
    'where' => [
        ['column' => 'productCategory.mf_id', 'operator' => '=', 'value' => 'c4ca4238a0b923820dcc509a6f75849b', 'encrypted' => true],
        ['column' => 'YEAR(we_from_pre) = ?', 'operator' => '=', 'value' => session()->get('financialYear'), 'isRaw'=>true],
        ['column' => 'product_category.cat_type', 'operator' => '!=', 'value' => '["5,"6"]', 'isArray'=>true],
        ['column' => 'prod_nsv', 'operator' => '=', 'value' => 'basic_price', 'isColumn' => true],
    ],
];

$list = ModelDataTableHelper::getServerSideDataTable(new Product(), $column);
```

## How to Use

1. *Installation*: Ensure that the DataTableHelper class is included in your Laravel project.
2. *Initialization*: Create an instance of the DataTableHelper class.
3. *Data Retrieval*: Call the `getServerSideDataTable` method with specified columns, join conditions, and other parameters to retrieve paginated and filtered data.
4. *Counting Filtered Rows*: Optionally, use the `countFilteredServerSideDataTable` method to determine the total count of filtered rows before pagination.
5. *Customization*: Adjust the class according to your specific use case by modifying the methods or extending its functionality.

By leveraging the `DataTableHelper` and `ModelDataTableHelper` class, you can seamlessly integrate server-side DataTables functionality into your Laravel application, providing a user-friendly and efficient way to handle large datasets in tabular form.

#### Note: 
Maintaining consistency in the order and length between the `$column['order']` array and the `Table Headers` is crucial for ensuring accurate and expected column sorting in the DataTable.

This connection relies on the assumption that both the `$column['order']` array and the headers of the associated table share the same order and length. The `$column['order']` array is used to specify the default ordering of columns in the DataTable. Each element in this array represents a column and includes information about the table and column name, as well as the desired ordering direction (e.g., ascending or descending).

For this connection to work seamlessly, it is essential that the order and length of elements in the `$column['order']` array align with the headers of the corresponding table. The DataTable interprets the order of elements in the `$column['order']` array to determine the initial sorting of columns when the page is loaded. If the order and length of elements in `$column['order']` do not match the headers of the table, unexpected sorting behavior may occur.

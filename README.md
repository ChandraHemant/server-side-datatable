# Server Side Data Table Package

This package provides a convenient method `getServerSideDataTable` to fetch data for server-side DataTables in Laravel applications.

## Installation

You can install this package via Composer:

```bash
composer require chandra-hemant/server-side-datatable
```

## DataTableHelper Class

The DataTableHelper class is a PHP utility designed to enhance server-side data retrieval for DataTables within Laravel applications. DataTables is a popular JavaScript library used for creating interactive and dynamic tables in web applications. This class provides a set of methods to build and execute flexible database queries, supporting features such as table joins, column selection, search filtering, ordering, and pagination. 

## Class Overview

##### Namespace: ChandraHemant\ServerSideDatatable
##### Author: Hemant Kumar Chandra

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
$helper = new DataTableHelper();
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
        array('product_category', 'cat_name')
    ),
    'select'=>array(
        array('products', 'prod_id'),
        array('products', 'prod_name'),
        array('products', 'prod_type'),
        array('products', 'prod_descr'),
        array('products', 'prod_total_price'),
        array('products', 'prod_nsv'),
        array('product_category', 'cat_name')
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
        array('product_category', 'products'),
    ),
    'fields'=>array(
        array('cat_id', 'cat_id'),   
    ),
    'joinType'=>array(
        'left', 
    ),
);

$list = $helper->getServerSideDataTable($column, $join);
$count = $helper->countFilteredServerSideDataTable($column, $join);

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

## How to Use

1. *Installation*: Ensure that the DataTableHelper class is included in your Laravel project.
2. *Initialization*: Create an instance of the DataTableHelper class.
3. *Data Retrieval*: Call the `getServerSideDataTable` method with specified columns, join conditions, and other parameters to retrieve paginated and filtered data.
4. *Counting Filtered Rows*: Optionally, use the `countFilteredServerSideDataTable` method to determine the total count of filtered rows before pagination.
5. *Customization*: Adjust the class according to your specific use case by modifying the methods or extending its functionality.


By leveraging the `DataTableHelper` class, you can seamlessly integrate server-side DataTables functionality into your Laravel application, providing a user-friendly and efficient way to handle large datasets in tabular form.

#### Note: 
Maintaining consistency in the order and length between the `$column['order']` array and the `Table Headers` is crucial for ensuring accurate and expected column sorting in the DataTable.

This connection relies on the assumption that both the `$column['order']` array and the headers of the associated table share the same order and length. The `$column['order']` array is used to specify the default ordering of columns in the DataTable. Each element in this array represents a column and includes information about the table and column name, as well as the desired ordering direction (e.g., ascending or descending).

For this connection to work seamlessly, it is essential that the order and length of elements in the `$column['order']` array align with the headers of the corresponding table. The DataTable interprets the order of elements in the `$column['order']` array to determine the initial sorting of columns when the page is loaded. If the order and length of elements in `$column['order']` do not match the headers of the table, unexpected sorting behavior may occur.

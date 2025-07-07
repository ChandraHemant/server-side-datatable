# Server Side Data Table Packages

This package provides convenient methods to fetch data for server-side DataTables in Laravel applications with multiple approaches ranging from simple to advanced usage.

## Installation

You can install this package via Composer:

```bash
composer require chandra-hemant/server-side-datatable
```

## Overview

The ChandraHemant/ServerSideDatatable package provides helper methods for retrieving data from database tables with options to join multiple tables, apply complex conditions, and handle server-side processing for DataTables in Laravel applications.

##### Namespace: ChandraHemant\ServerSideDatatable
##### Author: Hemant Kumar Chandra

---

# 🚀 FlexibleDataTable Class (Recommended)

**NEW:** The most developer-friendly approach with Laravel-style fluent interface!

## Features

✅ **Laravel-Style Fluent Interface** - Method chaining just like Eloquent  
✅ **All Laravel Query Builder Methods** - where, whereIn, whereRaw, join, etc.  
✅ **Zero Learning Curve** - If you know Laravel, you know this!  
✅ **Case-Insensitive Search** - Works with both English and Hindi text  
✅ **Auto DataTable Formatting** - Returns proper DataTable JSON response  
✅ **Multiple Output Formats** - DataTable, Collection, or Query Builder  
✅ **IntelliSense Support** - Full IDE auto-completion  
✅ **Magic Method Support** - Automatically supports current and future Laravel functions  

## Quick Start

### Basic Usage

```php
use ChandraHemant\ServerSideDatatable\FlexibleDataTable;

// Simple DataTable response
return FlexibleDataTable::of(new User())
    ->searchable(['name', 'email', 'phone'])
    ->orderable(['name', 'email', 'created_at'])
    ->make();
```

### Advanced Usage

```php
// Complex query with all Laravel functions
return FlexibleDataTable::of(new Order())
    // Basic Laravel query methods
    ->where('status', 'completed')
    ->whereIn('type', ['online', 'offline'])
    ->whereNotNull('payment_date')
    ->whereBetween('total_amount', [100, 5000])
    ->whereDate('created_at', '>=', '2024-01-01')
    ->whereYear('created_at', 2024)
    ->whereRaw('total_amount > (SELECT AVG(total_amount) FROM orders)')
    
    // Relationships
    ->whereHas('customer', function($query) {
        $query->where('is_verified', true);
    })
    ->with(['customer', 'products', 'payments'])
    ->withCount(['products', 'payments'])
    ->withSum('products', 'price')
    
    // Joins and grouping
    ->join('customers', 'orders.customer_id', '=', 'customers.id')
    ->groupBy('customer_id')
    ->having('total_orders', '>', 5)
    
    // Search configuration
    ->searchable(['order_number', 'total_amount'])
    ->searchableRelation('customer', ['name', 'email'])
    ->searchableRelation('products', ['name', 'sku'])
    
    // Ordering configuration
    ->orderable(['order_number', 'total_amount', 'created_at'])
    ->orderBy('created_at', 'desc')
    
    // Return DataTable response
    ->make();
```

### Different Output Formats

```php
// 1. DataTable JSON response (for AJAX)
$dataTableResponse = FlexibleDataTable::of(new Product())
    ->where('is_active', 1)
    ->searchable(['name', 'sku'])
    ->make();

// 2. Collection of models (for other uses)
$products = FlexibleDataTable::of(new Product())
    ->where('is_active', 1)
    ->searchable(['name', 'sku'])
    ->get();

// 3. Query builder (for further customization)
$query = FlexibleDataTable::of(new Product())
    ->where('is_active', 1)
    ->searchable(['name', 'sku'])
    ->getQuery();

// Add more conditions to the query
$finalResults = $query->where('category_id', 5)->paginate(15);
```

### Helper Methods

```php
// Conditional logic
FlexibleDataTable::of(new Transaction())
    ->when(request('status'), function($query, $status) {
        $query->where('status', $status);
    })
    ->unless(request('include_cancelled'), function($query) {
        $query->where('status', '!=', 'cancelled');
    })
    ->make();

// Profit/Loss helper
FlexibleDataTable::of(new Transaction())
    ->profitLoss('credit_amount', 'debit_amount', request('filter'))
    ->make();

// Year filtering helper
FlexibleDataTable::of(new Sale())
    ->filterByYear('sale_date', 2024)
    ->make();
```

## Controller Example

```php
use ChandraHemant\ServerSideDatatable\FlexibleDataTable;

class ProductController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return FlexibleDataTable::of(new Product())
                ->select(['id', 'name', 'price', 'category_id'])
                ->where('is_active', true)
                ->whereIn('category_id', [1, 2, 3])
                ->when(request('min_price'), function($query) {
                    $query->where('price', '>=', request('min_price'));
                })
                ->searchable(['name', 'sku', 'description'])
                ->searchableRelation('category', ['name'])
                ->with(['category', 'images'])
                ->withCount(['reviews'])
                ->withAvg('reviews', 'rating')
                ->orderable(['name', 'price', 'created_at'])
                ->orderBy('name')
                ->make();
        }
        
        return view('products.index');
    }
}
```

## Available Methods

### Core Methods
- `of(Model $model)` - Create new instance
- `make()` - Return DataTable JSON response
- `get()` - Return Collection of models
- `getQuery()` - Return Query Builder instance

### Search Configuration
- `searchable(array $columns)` - Set searchable columns
- `searchableRelation(string $relation, array $columns)` - Add searchable relationship
- `orderable(array $columns)` - Set orderable columns

### Laravel Query Builder Methods (All Supported!)
- **Basic:** `where`, `whereIn`, `whereNotIn`, `whereNull`, `whereNotNull`
- **Date/Time:** `whereDate`, `whereMonth`, `whereYear`, `whereTime`, `whereBetween`
- **Raw Queries:** `whereRaw`, `selectRaw`, `orderByRaw`, `havingRaw`
- **Relationships:** `whereHas`, `whereDoesntHave`, `whereRelation`, `whereBelongsTo`
- **Joins:** `join`, `leftJoin`, `rightJoin`, `crossJoin`
- **Aggregates:** `groupBy`, `having`, `withCount`, `withSum`, `withAvg`
- **Advanced:** `when`, `unless`, `tap`, `distinct`, `limit`, `offset`
- **JSON:** `whereJsonContains`, `whereJsonLength`, `whereJsonPath`
- **Subqueries:** `whereExists`, `whereIn` with closures
- **Model Scopes:** Custom scopes from your models

### Helper Methods
- `when(condition, callback)` - Conditional query building
- `profitLoss(profitColumn, lossColumn, searchValue)` - Profit/loss filtering
- `filterByYear(column, year)` - Year-based filtering

---

# DynamicModelDataTableHelper Class

The `DynamicModelDataTableHelper` class provides advanced server-side DataTable functionality with complex array-based configuration. This is the most powerful but complex approach.

## Usage

### Retrieving Data

```php
use ChandraHemant\ServerSideDatatable\DynamicModelDataTableHelper;

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
        'args' => ['column7', 'LIKE', $request->input('status')],
        'relation' => 'o_status'
    ],
    [
        'method' => 'select',
        'args' => ['column1','column2','column3','column4','column5'],
        'relation' => ['relation1','relation2','relation3','relation4','relation5'],
    ],
    [
        'method' => 'orderBy',
        'args' => ['column1', 'desc']
    ]
];

$searchColumns = ['column1','column2','column3','column4','column5'];

$searchRelationships = [
    'relation1' => ['column1'],
    'relation2' => ['column2'],
    'relation3' => ['column3'],
];

$helper = new DynamicModelDataTableHelper(
    eloquentModel: new YourModel(),
    dynamicConditions: $dynamicConditions,
    searchColumns: $searchColumns,
    searchRelationships: $searchRelationships
);

$result = $helper->getServerSideDataTable();
```

### Complex Nested Conditions

```php
$dynamicConditions = [
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
                    ]
                ]
            ]
        ]
    ]
];
```

### Constructor Parameters

- `$eloquentModel` (Illuminate\Database\Eloquent\Model): The Eloquent model to query for data.
- `$dynamicConditions` (array): An array specifying the dynamic conditions for the query.
- `$searchColumns` (array): An array specifying columns to search in.
- `$searchRelationships` (array): An array specifying relationships to search in.

### Methods

- `getServerSideDataTable(bool $query = false)` - Retrieve server-side DataTables data
- `countFilteredServerSideDataTable()` - Count filtered records

---

# DataTableHelper Class

The DataTableHelper class provides traditional SQL-based server-side data retrieval for DataTables with support for complex joins and raw SQL queries.

## Methods

- `getServerSideDataTable($column, $join = array())` - Retrieves data with joins
- `countFilteredServerSideDataTable($column, $join = array())` - Counts filtered rows
- `getDataWithJoinTables($column, $join = array())` - Retrieves data with joins (no pagination)

## Usage Example

```php
use ChandraHemant\ServerSideDatatable\DataTableHelper;

// Specify columns, ordering, and filtering conditions
$column = array(
    'table'=> 'products',
    'order'=> array(
        array('products', 'prod_id'),
        array('products', 'prod_name'),
        array('products', 'prod_type'),
        array('productCategory', 'cat_name')
    ),
    'select'=>array(
        array('products', 'prod_id'),
        array('products', 'prod_name'),
        array('products', 'prod_type'),
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

// Process data for DataTable response
$data = array();
foreach ($list as $val) {
    $row = array();
    $row[] = '#'.$val->prod_id;			
    $row[] = $val->prod_name;			
    $row[] = $val->prod_type;						
    $row[] = $val->cat_name;	
    $data[] = $row;
}

$output = array(
    "draw" => request()->input('draw'),
    "recordsTotal" => sizeof($list),
    "recordsFiltered" => $count,
    "data" => $data,
);

return response()->json($output);
```

---

# ModelDataTableHelper Class

This class provides Eloquent model-based server-side DataTables functionality with advanced column configuration and relationship handling.

## Usage Example

```php
use ChandraHemant\ServerSideDatatable\ModelDataTableHelper;

$column = [
    'orderBy' => [
        ['column' => 'prod_id', 'direction' => 'DESC'],
        ['column' => 'productCategory.cat_name', 'direction' => 'ASC'],
    ],
    'order' => [
        ['prod_id'],
        ['prod_name'],
        ['prod_type'],
        ['productCategory.cat_name'],
    ],
    'select' => [
        ['prod_id', 'id'],
        ['prod_name', 'name'],
        ['prod_type', 'type'],
        ['productCategory.cat_name', 'category_name'],
    ],
    'with' => [
        ['relation'=>'audit_employee'],
        ['relation'=>'audits', 'nested' => [
            'relation' => 'user_detail', 
            'selectColumn' => ['emp_id', 'emp_name']
        ]],
    ],
    'where' => [
        ['column' => 'productCategory.mf_id', 'operator' => '=', 'value' => 'encrypted_value', 'encrypted' => true],
        ['column' => 'YEAR(created_at) = ?', 'operator' => '=', 'value' => '2024', 'isRaw'=>true],
        ['column' => 'category.type', 'operator' => '!=', 'value' => '["5","6"]', 'isArray'=>true],
        ['column' => 'price', 'operator' => '=', 'value' => 'cost', 'isColumn' => true],
    ],
];

$list = ModelDataTableHelper::getServerSideDataTable(new Product(), $column);
```

---

## Comparison of Approaches

| Feature | FlexibleDataTable | DynamicModelDataTableHelper |
|---------|----------------|----------------------------|
| **Ease of Use** | ⭐⭐⭐⭐⭐ Very Easy | ⭐⭐ Complex |
| **Learning Curve** | ⭐⭐⭐⭐⭐ Zero (Laravel-like) | ⭐⭐ High |
| **Flexibility** | ⭐⭐⭐⭐⭐ Very High | ⭐⭐⭐⭐⭐ Very High |
| **Laravel Integration** | ⭐⭐⭐⭐⭐ Perfect | ⭐⭐⭐⭐ Good |
| **Code Readability** | ⭐⭐⭐⭐⭐ Excellent | ⭐⭐ Poor |
| **Performance** | ⭐⭐⭐⭐⭐ Excellent | ⭐⭐⭐⭐ Good |

## Recommendations

- **🚀 For New Projects:** Use **FlexibleDataTable** - Laravel-style, easy to learn and use
- **🔧 For Complex Legacy Systems:** Use **DynamicModelDataTableHelper** - Maximum flexibility with array configuration
- **📊 For Raw SQL Needs:** Use **DataTableHelper** - Direct SQL control with joins
- **⚡ For Eloquent-Heavy Apps:** Use **ModelDataTableHelper** - Good balance of features and complexity

## Important Notes

### Column Order Consistency
Maintaining consistency in the order and length between the `orderable` array and `Table Headers` is crucial for ensuring accurate column sorting in DataTables. The order of elements in the `orderable` array must align with the headers of the corresponding table.

### Case-Sensitive Search
For case-insensitive search functionality, ensure your database collation supports it, or use the built-in LOWER() function implementation in FlexibleDataTable.

### Performance Optimization
- Add database indexes for searchable and orderable columns
- Use `select()` to limit retrieved columns
- Consider caching for frequently accessed data
- Monitor query performance in production

## License

This package is open-sourced software licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please open an issue on GitHub.
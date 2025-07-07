<?php

namespace ChandraHemant\ServerSideDatatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Flexible DataTable Helper - Laravel Style
 * Author: Hemant Kumar Chandra
 *
 * ✅ SUPPORTS ALL LARAVEL QUERY BUILDER FUNCTIONS!
 * ✅ where, whereIn, whereRaw, whereHas, whereDate, etc.
 * ✅ join, leftJoin, rightJoin, crossJoin
 * ✅ groupBy, having, havingRaw, orderBy, orderByRaw
 * ✅ with, withCount, withSum, withAvg, etc.
 * ✅ select, selectRaw, addSelect, distinct
 * ✅ when, unless, tap, and many more!
 *
 * Easy to use, Laravel-like fluent interface for DataTables
 */

/*
===========================================
🎉 KEY FEATURES SUMMARY
===========================================

✅ ALL Laravel Query Builder Functions Work!
   - where, whereIn, whereRaw, whereHas, whereDate
   - join, leftJoin, rightJoin, crossJoin
   - groupBy, having, havingRaw
   - orderBy, orderByRaw, orderByDesc
   - with, withCount, withSum, withAvg
   - select, selectRaw, addSelect, distinct
   - when, unless, tap
   - And 100+ more Laravel functions!

✅ DataTable Features Auto-Handled
   - Search in columns and relationships
   - Sorting/Ordering
   - Pagination
   - Filtering

✅ Multiple Output Formats
   - make() → DataTable JSON format
   - get() → Collection of models
   - getQuery() → Query Builder for custom use

✅ Laravel-Style Fluent Interface
   - Method chaining just like Eloquent
   - No complex arrays to remember
   - IntelliSense/Auto-complete support

✅ Zero Learning Curve
   - If you know Laravel, you know this!
   - Same functions, same syntax
   - No documentation needed

===========================================
💡 MAGIC METHOD POWER
===========================================

The __call() magic method automatically forwards
ANY Laravel Query Builder method to the underlying
query, so you get 100% Laravel compatibility!

This means you can use:
- All current Laravel functions
- Future Laravel functions (automatically supported)
- Custom scopes defined in your models
- Package-added query methods

It's literally like using Laravel Query Builder
with added DataTable superpowers! 🚀
*/
class FlexibleDataTable
{
    private $model;
    private $query;
    private $searchableColumns = [];
    private $searchableRelations = [];
    private $orderableColumns = [];
    private $textColumns = []; // For PostgreSQL optimization
    private $numericColumns = []; // For PostgreSQL optimization

    /**
     * Create new DataTable instance
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->query = $model->newQuery();
    }

    /**
     * Static method to create new instance - Laravel style
     */
    public static function of(Model $model)
    {
        return new self($model);
    }

    /**
     * Add searchable columns
     */
    public function searchable(array $columns)
    {
        $this->searchableColumns = array_merge($this->searchableColumns, $columns);
        return $this;
    }

    /**
     * Add searchable relations
     */
    public function searchableRelation(string $relation, array $columns)
    {
        $this->searchableRelations[$relation] = $columns;
        return $this;
    }

    /**
     * Set orderable columns (for sorting)
     * FIXED: Now properly maps column indices to column names
     */
    public function orderable(array $columns)
    {
        // Reset array to ensure proper indexing (0, 1, 2, ...)
        $this->orderableColumns = array_values($columns);
        return $this;
    }

    /**
     * Specify which columns are text/varchar (PostgreSQL optimization)
     */
    public function textColumns(array $columns)
    {
        $this->textColumns = $columns;
        return $this;
    }

    /**
     * Specify which columns are numeric (PostgreSQL optimization)
     */
    public function numericColumns(array $columns)
    {
        $this->numericColumns = $columns;
        return $this;
    }

    /**
     * Magic method to support ALL Laravel Query Builder methods
     */
    public function __call($method, $arguments)
    {
        $result = $this->query->$method(...$arguments);

        if ($result instanceof Builder) {
            return $this;
        }

        return $result;
    }

    /**
     * Handle profit/loss conditions easily
     */
    public function profitLoss($profitColumn, $lossColumn, $searchValue)
    {
        return $this->when($searchValue === 'profit', function($query) use ($profitColumn, $lossColumn) {
            $query->whereColumn($profitColumn, '>', $lossColumn);
        })->when($searchValue === 'loss', function($query) use ($profitColumn, $lossColumn) {
            $query->whereColumn($profitColumn, '<', $lossColumn);
        });
    }

    /**
     * Handle year filtering easily
     */
    public function filterByYear($column, $year = null)
    {
        $year = $year ?? session('financialYear', date('Y'));
        $this->query->whereYear($column, $year);
        return $this;
    }

    /**
     * Get DataTable response (main method)
     */
    public function make()
    {
        // Apply search if present
        $this->applySearch();

        // Apply ordering if present
        $this->applyOrdering();

        // Get total count before filtering
        $totalRecords = $this->model->count();

        // Get filtered count
        $filteredRecords = $this->query->count();

        // Apply pagination
        $this->applyPagination();

        // Get data
        $data = $this->query->get();

        return [
            'draw' => request('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ];
    }

    /**
     * Get only data (without DataTable format)
     */
    public function get()
    {
        $this->applySearch();
        $this->applyOrdering();
        $this->applyPagination();

        return $this->query->get();
    }

    /**
     * Get query builder (for further customization)
     */
    public function getQuery()
    {
        $this->applySearch();
        $this->applyOrdering();

        return $this->query;
    }

    /**
     * Apply search functionality
     * FIXED: PostgreSQL compatible case-insensitive search
     */
    private function applySearch()
    {
        $searchValue = request('search.value');

        if (!empty($searchValue)) {
            $this->query->where(function($query) use ($searchValue) {
                $searchValue = strtolower($searchValue); // Convert search to lowercase

                // Search in columns (PostgreSQL compatible)
                foreach ($this->searchableColumns as $column) {
                    $query->orWhereRaw($this->buildSearchCondition($column), ["%{$searchValue}%"]);
                }

                // Search in relations (PostgreSQL compatible)
                foreach ($this->searchableRelations as $relation => $columns) {
                    $query->orWhereHas($relation, function($subQuery) use ($searchValue, $columns) {
                        $subQuery->where(function($q) use ($searchValue, $columns) {
                            foreach ($columns as $column) {
                                $q->orWhereRaw($this->buildSearchCondition($column), ["%{$searchValue}%"]);
                            }
                        });
                    });
                }
            });
        }

        // Individual column search (if implemented in frontend)
        $this->applyIndividualColumnSearch();
    }

    /**
     * Apply individual column search (optional feature)
     * FIXED: PostgreSQL compatible individual column search
     */
    private function applyIndividualColumnSearch()
    {
        $columns = request('columns', []);

        foreach ($columns as $index => $column) {
            if (!empty($column['search']['value'])) {
                $searchValue = strtolower($column['search']['value']);

                // Get the actual column name from orderable columns
                if (isset($this->orderableColumns[$index])) {
                    $columnName = $this->orderableColumns[$index];
                    $this->query->whereRaw($this->buildSearchCondition($columnName), ["%{$searchValue}%"]);
                }
            }
        }
    }

    /**
     * Build PostgreSQL compatible search condition
     * Handles both text and numeric columns
     */
    private function buildSearchCondition($column)
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        // If column is explicitly marked as text, use simple LOWER
        if (in_array($column, $this->textColumns)) {
            if ($connection === 'pgsql') {
                return "LOWER({$column}) LIKE ?";
            }
            return "LOWER({$column}) LIKE ?";
        }

        // If column is explicitly marked as numeric, cast to text
        if (in_array($column, $this->numericColumns)) {
            if ($connection === 'pgsql') {
                return "LOWER(CAST({$column} AS TEXT)) LIKE ?";
            } elseif ($connection === 'sqlsrv') {
                return "LOWER(CAST({$column} AS NVARCHAR(MAX))) LIKE ?";
            }
            return "LOWER(CAST({$column} AS CHAR)) LIKE ?";
        }

        // Auto-detect based on database type
        if ($connection === 'pgsql') {
            // PostgreSQL: Convert to text first, then apply LOWER
            return "LOWER(CAST({$column} AS TEXT)) LIKE ?";
        } elseif ($connection === 'mysql') {
            // MySQL: Simple LOWER function
            return "LOWER({$column}) LIKE ?";
        } elseif ($connection === 'sqlite') {
            // SQLite: Simple LOWER function
            return "LOWER({$column}) LIKE ?";
        } elseif ($connection === 'sqlsrv') {
            // SQL Server: Simple LOWER function
            return "LOWER(CAST({$column} AS NVARCHAR(MAX))) LIKE ?";
        }

        // Default fallback
        return "LOWER(CAST({$column} AS TEXT)) LIKE ?";
    }

    /**
     * Apply ordering
     * FIXED: Proper column index mapping
     */
    private function applyOrdering()
    {
        $orderColumnIndex = (int) request('order.0.column');
        $orderDirection = request('order.0.dir', 'asc');

        // Validate column index and direction
        if (isset($this->orderableColumns[$orderColumnIndex])) {
            $column = $this->orderableColumns[$orderColumnIndex];
            $direction = in_array($orderDirection, ['asc', 'desc']) ? $orderDirection : 'asc';

            // Add table prefix if column doesn't contain dot
            if (!str_contains($column, '.')) {
                $tableName = $this->model->getTable();
                $column = "{$tableName}.{$column}";
            }

            $this->query->orderBy($column, $direction);
        }
    }

    /**
     * Apply pagination
     */
    private function applyPagination()
    {
        $start = (int) request('start', 0);
        $length = (int) request('length', 10);

        if ($length > 0) {
            $this->query->offset($start)->limit($length);
        }
    }

    /**
     * Add debugging method to check what's being sent
     */
    public function debug()
    {
        return [
            'request_data' => request()->all(),
            'searchable_columns' => $this->searchableColumns,
            'orderable_columns' => $this->orderableColumns,
            'searchable_relations' => $this->searchableRelations,
            'text_columns' => $this->textColumns,
            'numeric_columns' => $this->numericColumns,
            'database_driver' => config('database.connections.' . config('database.default') . '.driver'),
            'query' => $this->query->toSql(),
            'bindings' => $this->query->getBindings()
        ];
    }
}

/*
===========================================
USAGE EXAMPLES - ALL LARAVEL FUNCTIONS SUPPORTED!
===========================================

// Basic Usage
$dataTable = FlexibleDataTable::of(new User())
    ->searchable(['name', 'email', 'phone'])
    ->orderable(['name', 'email', 'created_at'])
    ->make();

// ALL Laravel Query Builder Methods Work!
$dataTable = FlexibleDataTable::of(new Order())
    ->searchable(['order_number', 'total_amount'])
    ->searchableRelation('customer', ['name', 'email'])
    ->where('status', 'completed')
    ->whereIn('type', ['online', 'offline'])
    ->whereNotNull('payment_date')
    ->whereBetween('total_amount', [100, 5000])
    ->whereDate('created_at', '>=', '2024-01-01')
    ->whereMonth('created_at', 12)
    ->whereYear('created_at', 2024)
    ->whereTime('created_at', '>=', '09:00:00')
    ->whereRaw('total_amount > (SELECT AVG(total_amount) FROM orders)')
    ->whereExists(function($query) {
        $query->select(DB::raw(1))
              ->from('payments')
              ->whereColumn('payments.order_id', 'orders.id');
    })
    ->whereJsonContains('meta->tags', 'priority')
    ->whereJsonLength('meta->items', 3)
    ->with(['customer', 'products', 'payments'])
    ->withCount(['products', 'payments'])
    ->withSum('products', 'price')
    ->withAvg('ratings', 'score')
    ->join('customers', 'orders.customer_id', '=', 'customers.id')
    ->leftJoin('discounts', 'orders.discount_id', '=', 'discounts.id')
    ->groupBy('customer_id')
    ->having('total_orders', '>', 5)
    ->havingRaw('SUM(total_amount) > ?', [10000])
    ->orderBy('total_amount', 'desc')
    ->orderByRaw('FIELD(status, "pending", "processing", "completed")')
    ->limit(100)
    ->offset(50)
    ->distinct()
    ->orderable(['order_number', 'total_amount', 'created_at'])
    ->make();

// Relationship Queries
$dataTable = FlexibleDataTable::of(new User())
    ->searchable(['name', 'email'])
    ->whereHas('orders', function($query) {
        $query->where('status', 'completed')
              ->where('total_amount', '>', 1000);
    })
    ->whereDoesntHave('suspensions')
    ->withWhereHas('profile', function($query) {
        $query->where('is_verified', true);
    })
    ->whereRelation('orders', 'status', 'completed')
    ->whereMorphedTo('commentable', $post)
    ->whereBelongsTo($team)
    ->make();

// Subqueries and Advanced
$dataTable = FlexibleDataTable::of(new Product())
    ->searchable(['name', 'sku'])
    ->whereIn('category_id', function($query) {
        $query->select('id')
              ->from('categories')
              ->where('is_active', true);
    })
    ->addSelect([
        'latest_order_date' => Order::select('created_at')
            ->whereColumn('product_id', 'products.id')
            ->orderBy('created_at', 'desc')
            ->limit(1)
    ])
    ->selectRaw('products.*, (price * discount / 100) as discounted_price')
    ->when(request('category'), function($query, $category) {
        $query->where('category_id', $category);
    })
    ->unless(request('include_inactive'), function($query) {
        $query->where('is_active', true);
    })
    ->tap(function($query) {
        if (auth()->user()->isAdmin()) {
            $query->withTrashed();
        }
    })
    ->make();

// Aggregates and Statistics
$dataTable = FlexibleDataTable::of(new Sale())
    ->searchable(['invoice_number', 'customer_name'])
    ->selectRaw('
        DATE(created_at) as sale_date,
        COUNT(*) as total_sales,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount,
        MAX(amount) as max_amount,
        MIN(amount) as min_amount
    ')
    ->groupBy(DB::raw('DATE(created_at)'))
    ->havingRaw('SUM(amount) > ?', [5000])
    ->orderBy('sale_date', 'desc')
    ->make();

// Complex Conditions with Scopes
$dataTable = FlexibleDataTable::of(new User())
    ->searchable(['name', 'email'])
    ->active()  // Model scope
    ->verified() // Model scope
    ->whereIn('role', ['admin', 'manager'])
    ->orWhere(function($query) {
        $query->where('is_premium', true)
              ->where('subscription_expires_at', '>', now());
    })
    ->make();

// JSON Queries (Laravel 5.3+)
$dataTable = FlexibleDataTable::of(new User())
    ->searchable(['name', 'email'])
    ->whereJsonContains('preferences->notifications', 'email')
    ->whereJsonLength('preferences->themes', '>', 0)
    ->whereJsonPath('meta', '$.user.type', '=', 'premium')
    ->make();

// Database Functions
$dataTable = FlexibleDataTable::of(new Transaction())
    ->searchable(['reference_number'])
    ->whereRaw('YEAR(created_at) = ?', [date('Y')])
    ->whereRaw('MONTH(created_at) = ?', [date('m')])
    ->whereRaw('DAYOFWEEK(created_at) NOT IN (1, 7)') // Exclude weekends
    ->whereRaw('TIME(created_at) BETWEEN ? AND ?', ['09:00:00', '17:00:00'])
    ->orderByRaw('FIELD(status, "pending", "processing", "completed", "failed")')
    ->make();

// Just get data (not DataTable format)
$products = FlexibleDataTable::of(new Product())
    ->searchable(['name', 'sku'])
    ->where('is_active', 1)
    ->whereHas('category', function($query) {
        $query->where('is_featured', true);
    })
    ->with(['category', 'images'])
    ->orderBy('name')
    ->get();

// Get query for further customization
$query = FlexibleDataTable::of(new User())
    ->searchable(['name', 'email'])
    ->where('is_active', 1)
    ->whereNotNull('email_verified_at')
    ->getQuery();

// Add more conditions to query
$users = $query->where('role', 'admin')
              ->orWhereHas('permissions', function($q) {
                  $q->where('name', 'manage_users');
              })
              ->paginate(15);

===========================================
CONTROLLER EXAMPLE - ALL LARAVEL FUNCTIONS
===========================================

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return FlexibleDataTable::of(new User())
                ->searchable(['name', 'email', 'phone'])
                ->searchableRelation('profile', ['address', 'city'])
                ->where('is_active', 1)
                ->whereNotNull('email_verified_at')
                ->whereHas('roles', function($query) {
                    $query->whereIn('name', ['user', 'moderator']);
                })
                ->when(request('verified_only'), function($query) {
                    $query->whereNotNull('email_verified_at');
                })
                ->with(['profile', 'roles'])
                ->withCount(['orders', 'posts'])
                ->orderable(['name', 'email', 'created_at'])
                ->make();
        }

        return view('users.index');
    }

    public function transactions()
    {
        if (request()->ajax()) {
            return FlexibleDataTable::of(new Transaction())
                ->searchable(['reference_number', 'description'])
                ->whereIn('user_id', auth()->user()->accessible_users)
                ->whereYear('transaction_date', request('year', date('Y')))
                ->whereMonth('transaction_date', request('month', date('m')))
                ->whereBetween('amount', [
                    request('min_amount', 0),
                    request('max_amount', 999999)
                ])
                ->when(request('type'), function($query, $type) {
                    if ($type === 'profit') {
                        $query->whereColumn('credit_amount', '>', 'debit_amount');
                    } elseif ($type === 'loss') {
                        $query->whereColumn('credit_amount', '<', 'debit_amount');
                    }
                })
                ->when(request('status'), function($query) {
                    $query->where('status', request('status'));
                })
                ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')
                ->orderByRaw('FIELD(status, "pending", "processing", "completed")')
                ->orderBy('transaction_date', 'desc')
                ->orderable(['reference_number', 'amount', 'transaction_date'])
                ->make();
        }

        return view('transactions.index');
    }

    public function salesReport()
    {
        if (request()->ajax()) {
            return FlexibleDataTable::of(new Sale())
                ->searchable(['invoice_number', 'customer_name'])
                ->selectRaw('
                    DATE(created_at) as sale_date,
                    COUNT(*) as total_sales,
                    SUM(amount) as total_revenue,
                    AVG(amount) as avg_sale_amount
                ')
                ->whereDate('created_at', '>=', request('start_date', now()->startOfMonth()))
                ->whereDate('created_at', '<=', request('end_date', now()->endOfMonth()))
                ->whereHas('customer', function($query) {
                    $query->where('is_active', true);
                })
                ->groupBy(DB::raw('DATE(created_at)'))
                ->havingRaw('SUM(amount) > ?', [1000])
                ->orderBy('sale_date', 'desc')
                ->make();
        }

        return view('sales.report');
    }

    public function products()
    {
        if (request()->ajax()) {
            return FlexibleDataTable::of(new Product())
                ->searchable(['name', 'sku', 'description'])
                ->searchableRelation('category', ['name'])
                ->searchableRelation('brand', ['name'])
                ->where('is_active', true)
                ->whereNotNull('price')
                ->whereIn('category_id', function($query) {
                    $query->select('id')
                          ->from('categories')
                          ->where('is_featured', true);
                })
                ->when(request('price_range'), function($query, $range) {
                    [$min, $max] = explode('-', $range);
                    $query->whereBetween('price', [$min, $max]);
                })
                ->when(request('has_discount'), function($query) {
                    $query->where('discount_percentage', '>', 0);
                })
                ->addSelect([
                    'total_sold' => Order::selectRaw('SUM(quantity)')
                        ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                        ->whereColumn('order_items.product_id', 'products.id')
                        ->where('orders.status', 'completed')
                ])
                ->with(['category', 'brand', 'images'])
                ->withCount(['reviews', 'favorites'])
                ->withAvg('reviews', 'rating')
                ->orderBy('name')
                ->orderable(['name', 'price', 'created_at', 'total_sold'])
                ->make();
        }

        return view('products.index');
    }
}

===========================================
REAL WORLD EXAMPLES
===========================================

// E-commerce Orders with Complex Filters
FlexibleDataTable::of(new Order())
    ->searchable(['order_number', 'tracking_number'])
    ->searchableRelation('customer', ['name', 'email', 'phone'])
    ->searchableRelation('shipping_address', ['street', 'city', 'postal_code'])
    ->where('status', '!=', 'cancelled')
    ->whereDate('created_at', '>=', request('date_from', now()->startOfMonth()))
    ->whereDate('created_at', '<=', request('date_to', now()->endOfMonth()))
    ->whereBetween('total_amount', [
        request('amount_min', 0),
        request('amount_max', 999999)
    ])
    ->when(request('payment_status'), function($query, $status) {
        $query->whereHas('payments', function($q) use ($status) {
            $q->where('status', $status);
        });
    })
    ->when(request('shipping_method'), function($query, $method) {
        $query->where('shipping_method', $method);
    })
    ->whereJsonContains('meta->tags', request('tag'))
    ->with([
        'customer:id,name,email',
        'items.product:id,name,sku',
        'payments:id,order_id,status,amount',
        'shipping_address:id,street,city,state,country'
    ])
    ->withCount(['items', 'payments'])
    ->withSum('items', 'quantity')
    ->orderByRaw('FIELD(status, "pending", "processing", "shipped", "delivered")')
    ->orderBy('created_at', 'desc')
    ->make();

// Financial Reports with Aggregations
FlexibleDataTable::of(new Transaction())
    ->selectRaw('
        DATE_FORMAT(created_at, "%Y-%m") as month,
        transaction_type,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credits,
        SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debits,
        SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as net_amount
    ')
    ->where('status', 'completed')
    ->whereYear('created_at', request('year', date('Y')))
    ->whereHas('user', function($query) {
        $query->where('is_verified', true);
    })
    ->groupBy('month', 'transaction_type')
    ->havingRaw('COUNT(*) > ?', [10])
    ->orderBy('month', 'desc')
    ->orderBy('transaction_type')
    ->make();
*/

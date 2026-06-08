<?php
$secret = getenv('IMPORT_SECRET') ?: 'changeme';
if (php_sapi_name() !== 'cli') {
    $provided = $_GET['secret'] ?? '';
    if ($provided !== $secret) {
        http_response_code(403);
        die('Forbidden');
    }
}

require_once 'db.php';
function insert_batch(PDO $pdo, string $table, array $columns, array $rows): void {
    if (empty($rows)) return;
    $cols = implode(', ', $columns);
    $placeholders = [];
    $params = [];
    $idx = 1;
    foreach ($rows as $row) {
        $row_ph = [];
        foreach ($columns as $i => $col) {
            $val = $row[$i] ?? null;
            $params[":p{$idx}"] = ($val === '' || $val === null) ? null : $val;
            $row_ph[] = ":p{$idx}";
            $idx++;
        }
        $placeholders[] = '(' . implode(',', $row_ph) . ')';
    }
    $sql = "INSERT INTO $table ($cols) VALUES " . implode(',', $placeholders) . " ON CONFLICT DO NOTHING";
    $pdo->prepare($sql)->execute($params);
}
function import_csv(PDO $pdo, string $file, string $table, array $columns, ?callable $transform = null): int {
    $handle = fopen($file, 'r');
    if (!$handle) throw new Exception("Cannot open $file");
    fgetcsv($handle);
    $count = 0;
    $batch = [];
    $batch_size = 500;
    while (($row = fgetcsv($handle)) !== false) {
        if ($transform) $row = $transform($row);
        if (!$row) continue;
        $batch[] = $row;
        $count++;
        if (count($batch) >= $batch_size) {
            insert_batch($pdo, $table, $columns, $batch);
            echo "  $table: $count rows\n";
            flush();
            $batch = [];
        }
    }
    if (!empty($batch)) insert_batch($pdo, $table, $columns, $batch);
    fclose($handle);
    return $count;
}

$csv_dir = __DIR__ . '/csv';

try {
    $n = import_csv($pdo, "$csv_dir/Regions.csv", 'regions', ['region_id', 'region_description']);
    echo "Regions: $n\n";

    $n = import_csv($pdo, "$csv_dir/Territories.csv", 'territories', ['territory_id', 'territory_description', 'region_id']);
    echo "Territories: $n\n";

    $n = import_csv($pdo, "$csv_dir/Categories.csv", 'categories', ['category_id', 'category_name', 'description']);
    echo "Categories: $n\n";

    $n = import_csv($pdo, "$csv_dir/Suppliers.csv", 'suppliers',
        ['supplier_id','company_name','contact_name','contact_title','address','city','region','postal_code','country','phone','fax','home_page']);
    echo "Suppliers: $n\n";

    $n = import_csv($pdo, "$csv_dir/Customers.csv", 'customers',
        ['customer_id','company_name','contact_name','contact_title','address','city','region','postal_code','country','phone','fax']);
    echo "Customers: $n\n";

    $n = import_csv($pdo, "$csv_dir/Employees.csv", 'employees',
        ['employee_id','last_name','first_name','title','title_of_courtesy','birth_date','hire_date',
         'address','city','region','postal_code','country','home_phone','extension','notes','photo_path'],
        function($row) {
            return array_merge(array_slice($row, 0, 15), [array_slice($row, 16)[0] ?? null]);
        }
    );
    $handle = fopen("$csv_dir/Employees.csv", 'r');
    fgetcsv($handle);
    $upd = $pdo->prepare("UPDATE employees SET reports_to = :rt WHERE employee_id = :id");
    while (($row = fgetcsv($handle)) !== false) {
        $rt = (isset($row[15]) && $row[15] !== '') ? (int)$row[15] : null;
        $upd->execute([':rt' => $rt, ':id' => (int)$row[0]]);
    }
    fclose($handle);
    echo "Employees: $n\n";

    $n = import_csv($pdo, "$csv_dir/EmployeeTerritories.csv", 'employee_territories', ['employee_id', 'territory_id']);
    echo "EmployeeTerritories: $n\n";

    $n = import_csv($pdo, "$csv_dir/Shippers.csv", 'shippers', ['shipper_id', 'company_name', 'phone']);
    echo "Shippers: $n\n";

    $n = import_csv($pdo, "$csv_dir/Products.csv", 'products',
        ['product_id','product_name','supplier_id','category_id','quantity_per_unit','unit_price',
         'units_in_stock','units_on_order','reorder_level','discontinued']);
    echo "Products: $n\n";

    $n = import_csv($pdo, "$csv_dir/Orders.csv", 'orders',
        ['order_id','customer_id','employee_id','order_date','required_date','shipped_date',
         'ship_via','freight','ship_name','ship_address','ship_city','ship_region','ship_postal_code','ship_country']);
    echo "Orders: $n\n";

    $n = import_csv($pdo, "$csv_dir/Order Details.csv", 'order_details',
        ['order_id','product_id','unit_price','quantity','discount']);
    echo "OrderDetails: $n\n";

    echo "\nImport complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

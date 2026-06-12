<?php
require_once 'db.php';

$page = $_GET['page'] ?? 'dashboard';
$search = trim($_GET['q'] ?? '');
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = 25;
$sort = $_GET['sort'] ?? '';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

$country_filter  = $_GET['country']  ?? '';
$category_filter = $_GET['category'] ?? '';
$supplier_filter = $_GET['supplier'] ?? '';
$status_filter   = $_GET['status']   ?? '';
$city_filter     = $_GET['city']     ?? '';
$title_filter    = $_GET['title']    ?? '';

$insert_success = '';
$insert_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['insert_table'])) {
    $t = $_POST['insert_table'];
    try {
        switch ($t) {
            case 'customers':
                $pdo->prepare("INSERT INTO customers
                    (customer_id,company_name,contact_name,contact_title,address,city,region,postal_code,country,phone,fax)
                    VALUES (:a,:b,:c,:d,:e,:f,:g,:h,:i,:j,:k)")->execute([
                    ':a' => strtoupper(substr($_POST['customer_id'] ?? '', 0, 5)),
                    ':b' => $_POST['company_name']   ?: null, ':c' => $_POST['contact_name']   ?: null,
                    ':d' => $_POST['contact_title']  ?: null, ':e' => $_POST['address']         ?: null,
                    ':f' => $_POST['city']           ?: null, ':g' => $_POST['region']          ?: null,
                    ':h' => $_POST['postal_code']    ?: null, ':i' => $_POST['country']         ?: null,
                    ':j' => $_POST['phone']          ?: null, ':k' => $_POST['fax']             ?: null,
                ]);
                break;
            case 'products':
                $pdo->prepare("INSERT INTO products
                    (product_name,supplier_id,category_id,quantity_per_unit,unit_price,units_in_stock,units_on_order,reorder_level,discontinued)
                    VALUES (:a,:b,:c,:d,:e,:f,:g,:h,:i)")->execute([
                    ':a' => $_POST['product_name']      ?: null,
                    ':b' => $_POST['supplier_id']       ?: null, ':c' => $_POST['category_id']      ?: null,
                    ':d' => $_POST['quantity_per_unit'] ?: null, ':e' => $_POST['unit_price']        ?: 0,
                    ':f' => $_POST['units_in_stock']    ?: 0,    ':g' => $_POST['units_on_order']    ?: 0,
                    ':h' => $_POST['reorder_level']     ?: 0,    ':i' => isset($_POST['discontinued']) ? 1 : 0,
                ]);
                break;
            case 'suppliers':
                $pdo->prepare("INSERT INTO suppliers
                    (company_name,contact_name,contact_title,address,city,region,postal_code,country,phone,fax,home_page)
                    VALUES (:a,:b,:c,:d,:e,:f,:g,:h,:i,:j,:k)")->execute([
                    ':a' => $_POST['company_name']  ?: null, ':b' => $_POST['contact_name']  ?: null,
                    ':c' => $_POST['contact_title'] ?: null, ':d' => $_POST['address']        ?: null,
                    ':e' => $_POST['city']          ?: null, ':f' => $_POST['region']         ?: null,
                    ':g' => $_POST['postal_code']   ?: null, ':h' => $_POST['country']        ?: null,
                    ':i' => $_POST['phone']         ?: null, ':j' => $_POST['fax']            ?: null,
                    ':k' => $_POST['home_page']     ?: null,
                ]);
                break;
            case 'employees':
                $pdo->prepare("INSERT INTO employees
                    (last_name,first_name,title,title_of_courtesy,birth_date,hire_date,address,city,region,postal_code,country,home_phone,extension,notes,reports_to)
                    VALUES (:a,:b,:c,:d,:e,:f,:g,:h,:i,:j,:k,:l,:m,:n,:o)")->execute([
                    ':a' => $_POST['last_name']         ?: null, ':b' => $_POST['first_name']        ?: null,
                    ':c' => $_POST['title']             ?: null, ':d' => $_POST['title_of_courtesy'] ?: null,
                    ':e' => $_POST['birth_date']        ?: null, ':f' => $_POST['hire_date']         ?: null,
                    ':g' => $_POST['address']           ?: null, ':h' => $_POST['city']              ?: null,
                    ':i' => $_POST['region']            ?: null, ':j' => $_POST['postal_code']       ?: null,
                    ':k' => $_POST['country']           ?: null, ':l' => $_POST['home_phone']        ?: null,
                    ':m' => $_POST['extension']         ?: null, ':n' => $_POST['notes']             ?: null,
                    ':o' => $_POST['reports_to']        ?: null,
                ]);
                break;
            case 'orders':
                $pdo->prepare("INSERT INTO orders
                    (customer_id,employee_id,order_date,required_date,ship_via,freight,ship_name,ship_address,ship_city,ship_region,ship_postal_code,ship_country)
                    VALUES (:a,:b,:c,:d,:e,:f,:g,:h,:i,:j,:k,:l)")->execute([
                    ':a' => $_POST['customer_id']    ?: null, ':b' => $_POST['employee_id']    ?: null,
                    ':c' => $_POST['order_date']     ?: null, ':d' => $_POST['required_date']  ?: null,
                    ':e' => $_POST['ship_via']       ?: null, ':f' => $_POST['freight']        ?: 0,
                    ':g' => $_POST['ship_name']      ?: null, ':h' => $_POST['ship_address']   ?: null,
                    ':i' => $_POST['ship_city']      ?: null, ':j' => $_POST['ship_region']    ?: null,
                    ':k' => $_POST['ship_postal_code'] ?: null, ':l' => $_POST['ship_country'] ?: null,
                ]);
                break;
        }
        $insert_success = "Record added to $t.";
    } catch (PDOException $e) {
        $insert_error = $e->getMessage();
    }
}

function paginate_links(string $page, int $offset, int $limit, int $total, array $extra = []): string {
    $params = array_merge(['page' => $page, 'offset' => $offset - $limit], $extra);
    $prev = $offset > 0
        ? '<a href="?'.http_build_query($params).'">&#8592; Prev</a>'
        : '<span class="disabled">&#8592; Prev</span>';
    $params['offset'] = $offset + $limit;
    $next = ($offset + $limit < $total)
        ? '<a href="?'.http_build_query($params).'">Next &#8594;</a>'
        : '<span class="disabled">Next &#8594;</span>';
    $from = $total ? $offset + 1 : 0;
    $to   = min($offset + $limit, $total);
    return "<div class='pagination'>$prev <span>$from&ndash;$to of $total</span> $next</div>";
}

function build_filter_params(array $filters = []): array {
    $params = [];
    foreach ($filters as $key => $value) {
        if ($value !== '' && $value !== null) $params[$key] = $value;
    }
    return $params;
}

function th_sort(string $col, string $label, string $cur_sort, string $cur_dir, array $extra = []): string {
    $new_dir = ($cur_sort === $col && $cur_dir === 'ASC') ? 'desc' : 'asc';
    $arrow   = '';
    if ($cur_sort === $col) $arrow = $cur_dir === 'ASC' ? ' ▲' : ' ▼';
    $p = array_merge($extra, ['sort' => $col, 'dir' => $new_dir]);
    return '<th><a class="sort-link" href="?'.http_build_query($p).'">'.$label.$arrow.'</a></th>';
}

$allowed_sorts = [
    'customers' => ['customer_id','company_name','contact_name','city','country'],
    'products'  => ['product_id','product_name','unit_price','units_in_stock'],
    'orders'    => ['order_id','customer_id','order_date','shipped_date','ship_country','freight'],
    'employees' => ['employee_id','last_name','city','country'],
    'suppliers' => ['supplier_id','company_name','city','country'],
];

function safe_sort(string $page, string $sort, array $allowed, string $default): string {
    $list = $allowed[$page] ?? [];
    return in_array($sort, $list, true) ? $sort : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Northwind</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, sans-serif; background: #f4f5f7; color: #1a1a2e; }
nav { background: #1a1a2e; padding: 12px 24px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
nav a { color: #a0aec0; text-decoration: none; font-size: 14px; padding: 6px 12px; border-radius: 4px; }
nav a:hover, nav a.active { background: #2d3748; color: #fff; }
nav .brand { color: #fff; font-weight: 700; font-size: 16px; margin-right: 16px; }
.container { max-width: 1200px; margin: 24px auto; padding: 0 24px; }
.cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
.card { background: #fff; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.card .num { font-size: 2rem; font-weight: 700; color: #4f46e5; }
.card .label { font-size: 13px; color: #6b7280; margin-top: 4px; }
h2 { font-size: 18px; margin-bottom: 16px; color: #1a1a2e; }
.search-bar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; align-items: center; }
.search-bar input, .search-bar select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background: #fff; }
.search-bar input { flex: 1; min-width: 180px; }
.search-bar button { padding: 8px 16px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
.search-bar button:hover { background: #4338ca; }
.search-bar a.reset { padding: 8px 16px; background: #9ca3af; color: #fff; border-radius: 6px; font-size: 14px; text-decoration: none; }
.search-bar a.reset:hover { background: #6b7280; }
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
th { background: #f9fafb; text-align: left; padding: 10px 14px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
th a.sort-link { color: #6b7280; text-decoration: none; }
th a.sort-link:hover { color: #4f46e5; }
td { padding: 10px 14px; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f9fafb; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.badge-green { background: #d1fae5; color: #065f46; }
.badge-red { background: #fee2e2; color: #991b1b; }
.pagination { display: flex; gap: 12px; align-items: center; margin-top: 16px; font-size: 14px; color: #6b7280; }
.pagination a { color: #4f46e5; text-decoration: none; }
.pagination .disabled { color: #d1d5db; }
.section { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 24px; }
.alert-success { background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-error   { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.insert-form { background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.insert-form select, .insert-form input, .insert-form textarea {
    width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 12px; background: #fff;
}
.insert-form label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.insert-form .fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0 16px; }
.insert-form .field-group { display: none; }
.insert-form .field-group.active { display: contents; }
.insert-form button[type=submit] { padding: 10px 24px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-top: 4px; }
.insert-form button[type=submit]:hover { background: #4338ca; }
.checkbox-wrap { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.checkbox-wrap input { width: auto; margin-bottom: 0; }
</style>
</head>
<body>
<nav>
  <span class="brand">Northwind</span>
  <a href="?page=dashboard"  class="<?= $page==='dashboard' ?'active':'' ?>">Dashboard</a>
  <a href="?page=customers"  class="<?= $page==='customers'  ?'active':'' ?>">Customers</a>
  <a href="?page=products"   class="<?= $page==='products'   ?'active':'' ?>">Products</a>
  <a href="?page=orders"     class="<?= $page==='orders'     ?'active':'' ?>">Orders</a>
  <a href="?page=employees"  class="<?= $page==='employees'  ?'active':'' ?>">Employees</a>
  <a href="?page=suppliers"  class="<?= $page==='suppliers'  ?'active':'' ?>">Suppliers</a>
  <a href="?page=reports" class="<?= $page==='reports' ?'active':'' ?>">Reports</a>
  
  <a href="?page=insert"     class="<?= $page==='insert'     ?'active':'' ?>">+ Insert</a>
</nav>
<div class="container">

<?php if ($page === 'dashboard'): ?>
  <h2>Dashboard</h2>
  <?php $counts = [
    'Customers'=>'SELECT COUNT(*) FROM customers','Products'=>'SELECT COUNT(*) FROM products',
    'Orders'=>'SELECT COUNT(*) FROM orders','Employees'=>'SELECT COUNT(*) FROM employees',
    'Suppliers'=>'SELECT COUNT(*) FROM suppliers','Categories'=>'SELECT COUNT(*) FROM categories',
  ]; ?>
  <div class="cards">
  <?php foreach ($counts as $label => $sql): ?>
    <div class="card"><div class="num"><?= number_format($pdo->query($sql)->fetchColumn()) ?></div><div class="label"><?= $label ?></div></div>
  <?php endforeach; ?>
  </div>
  <div class="section">
    <h2>Top 10 Products by Revenue</h2>
    <table>
      <tr><th>Product</th><th>Category</th><th>Revenue</th></tr>
      <?php $rows = $pdo->query("
        SELECT p.product_name, c.category_name,
               SUM(od.unit_price * od.quantity * (1 - od.discount))::numeric(12,2) AS revenue
        FROM order_details od
        JOIN products p ON p.product_id = od.product_id
        JOIN categories c ON c.category_id = p.category_id
        GROUP BY p.product_name, c.category_name
        ORDER BY revenue DESC LIMIT 10")->fetchAll();
      foreach ($rows as $r): ?>
      <tr><td><?= htmlspecialchars($r['product_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['category_name'] ?? '') ?></td>
          <td>$<?= number_format($r['revenue'], 2) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="section">
    <h2>Top 10 Customers by Orders</h2>
    <table>
      <tr><th>Company</th><th>Country</th><th>Orders</th></tr>
      <?php $rows = $pdo->query("
        SELECT c.company_name, c.country, COUNT(o.order_id) AS order_count
        FROM customers c JOIN orders o ON o.customer_id = c.customer_id
        GROUP BY c.company_name, c.country ORDER BY order_count DESC LIMIT 10")->fetchAll();
      foreach ($rows as $r): ?>
      <tr><td><?= htmlspecialchars($r['company_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['country'] ?? '') ?></td>
          <td><?= $r['order_count'] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif ($page === 'customers'): ?>
  <h2>Customers</h2>
  <?php
    $countries = $pdo->query("SELECT DISTINCT country FROM customers WHERE country IS NOT NULL ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
    $cities    = $pdo->query("SELECT DISTINCT city    FROM customers WHERE city    IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
  ?>
  <form class="search-bar" method="get">
    <input type="hidden" name="page" value="customers">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by company...">
    <select name="country">
      <option value="">All Countries</option>
      <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $country_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <select name="city">
      <option value="">All Cities</option>
      <?php foreach ($cities as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $city_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
    <a class="reset" href="?page=customers">Reset</a>
  </form>
  <?php
    $s = safe_sort('customers', $sort, $allowed_sorts, 'company_name');
    $conditions = []; $params = [];
    if ($search)         { $conditions[] = "company_name ILIKE :q"; $params[':q'] = "%$search%"; }
    if ($country_filter) { $conditions[] = "country = :country";    $params[':country'] = $country_filter; }
    if ($city_filter)    { $conditions[] = "city = :city";          $params[':city'] = $city_filter; }
    $where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $stmt = $pdo->prepare("SELECT customer_id,company_name,contact_name,city,country,phone FROM customers $where ORDER BY $s $dir LIMIT $limit OFFSET $offset");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $extra = build_filter_params(['q'=>$search,'country'=>$country_filter,'city'=>$city_filter,'sort'=>$sort,'dir'=>strtolower($dir)]);
  ?>
  <table>
    <tr>
      <?= th_sort('customer_id',  'ID',      $s, $dir, array_merge(['page'=>'customers'], $extra)) ?>
      <?= th_sort('company_name', 'Company', $s, $dir, array_merge(['page'=>'customers'], $extra)) ?>
      <?= th_sort('contact_name', 'Contact', $s, $dir, array_merge(['page'=>'customers'], $extra)) ?>
      <?= th_sort('city',         'City',    $s, $dir, array_merge(['page'=>'customers'], $extra)) ?>
      <?= th_sort('country',      'Country', $s, $dir, array_merge(['page'=>'customers'], $extra)) ?>
      <th>Phone</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= htmlspecialchars($r['customer_id']  ?? '') ?></td>
        <td><?= htmlspecialchars($r['company_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['contact_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['city']         ?? '') ?></td>
        <td><?= htmlspecialchars($r['country']      ?? '') ?></td>
        <td><?= htmlspecialchars($r['phone']        ?? '') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?= paginate_links('customers', $offset, $limit, $total, $extra) ?>

<?php elseif ($page === 'products'): ?>
  <h2>Products</h2>
  <?php
    $categories = $pdo->query("SELECT category_id,category_name FROM categories ORDER BY category_name")->fetchAll();
    $suppliers  = $pdo->query("SELECT supplier_id,company_name  FROM suppliers  ORDER BY company_name")->fetchAll();
  ?>
  <form class="search-bar" method="get">
    <input type="hidden" name="page" value="products">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by product name...">
    <select name="category">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= $category_filter===(string)$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?>
    </select>
    <select name="supplier">
      <option value="">All Suppliers</option>
      <?php foreach ($suppliers as $s): ?><option value="<?= $s['supplier_id'] ?>" <?= $supplier_filter===(string)$s['supplier_id']?'selected':'' ?>><?= htmlspecialchars($s['company_name']) ?></option><?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">All Status</option>
      <option value="active"       <?= $status_filter==='active'?'selected':'' ?>>Active</option>
      <option value="discontinued" <?= $status_filter==='discontinued'?'selected':'' ?>>Discontinued</option>
    </select>
    <button type="submit">Search</button>
    <a class="reset" href="?page=products">Reset</a>
  </form>
  <?php
    $s = safe_sort('products', $sort, $allowed_sorts, 'product_name');
    $conditions = []; $params = [];
    if ($search)          { $conditions[] = "p.product_name ILIKE :q"; $params[':q'] = "%$search%"; }
    if ($category_filter) { $conditions[] = "p.category_id = :cat";   $params[':cat'] = (int)$category_filter; }
    if ($supplier_filter) { $conditions[] = "p.supplier_id = :sup";   $params[':sup'] = (int)$supplier_filter; }
    if ($status_filter)   { $conditions[] = "p.discontinued = :disc"; $params[':disc'] = $status_filter==='discontinued'?1:0; }
    $where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM products p $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $sort_col = in_array($s, ['product_id','product_name','unit_price','units_in_stock']) ? "p.$s" : "p.product_name";
    $stmt = $pdo->prepare("SELECT p.product_id,p.product_name,c.category_name,s.company_name AS supplier,p.unit_price,p.units_in_stock,p.discontinued
      FROM products p LEFT JOIN categories c ON c.category_id=p.category_id LEFT JOIN suppliers s ON s.supplier_id=p.supplier_id
      $where ORDER BY $sort_col $dir LIMIT $limit OFFSET $offset");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $extra = build_filter_params(['q'=>$search,'category'=>$category_filter,'supplier'=>$supplier_filter,'status'=>$status_filter,'sort'=>$sort,'dir'=>strtolower($dir)]);
  ?>
  <table>
    <tr>
      <?= th_sort('product_id',    '#',        $s, $dir, array_merge(['page'=>'products'], $extra)) ?>
      <?= th_sort('product_name',  'Name',     $s, $dir, array_merge(['page'=>'products'], $extra)) ?>
      <th>Category</th><th>Supplier</th>
      <?= th_sort('unit_price',    'Price',    $s, $dir, array_merge(['page'=>'products'], $extra)) ?>
      <?= th_sort('units_in_stock','In Stock', $s, $dir, array_merge(['page'=>'products'], $extra)) ?>
      <th>Status</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['product_id'] ?></td>
        <td><?= htmlspecialchars($r['product_name']  ?? '') ?></td>
        <td><?= htmlspecialchars($r['category_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['supplier']      ?? '') ?></td>
        <td>$<?= number_format($r['unit_price'], 2) ?></td>
        <td><?= $r['units_in_stock'] ?></td>
        <td><?= $r['discontinued'] ? '<span class="badge badge-red">Discontinued</span>' : '<span class="badge badge-green">Active</span>' ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?= paginate_links('products', $offset, $limit, $total, $extra) ?>

<?php elseif ($page === 'orders'): ?>
  <h2>Orders</h2>
  <?php $countries = $pdo->query("SELECT DISTINCT ship_country FROM orders WHERE ship_country IS NOT NULL ORDER BY ship_country")->fetchAll(PDO::FETCH_COLUMN); ?>
  <form class="search-bar" method="get">
    <input type="hidden" name="page" value="orders">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by customer ID...">
    <select name="country">
      <option value="">All Countries</option>
      <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $country_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">All Status</option>
      <option value="shipped" <?= $status_filter==='shipped'?'selected':'' ?>>Shipped</option>
      <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
    </select>
    <button type="submit">Search</button>
    <a class="reset" href="?page=orders">Reset</a>
  </form>
  <?php
    $s = safe_sort('orders', $sort, $allowed_sorts, 'order_date');
    $conditions = []; $params = [];
    if ($search)         { $conditions[] = "o.customer_id ILIKE :q"; $params[':q'] = "%$search%"; }
    if ($country_filter) { $conditions[] = "o.ship_country = :country"; $params[':country'] = $country_filter; }
    if ($status_filter === 'shipped') { $conditions[] = "o.shipped_date IS NOT NULL"; }
    if ($status_filter === 'pending') { $conditions[] = "o.shipped_date IS NULL"; }
    $where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $sort_col = in_array($s, ['order_id','customer_id','order_date','shipped_date','ship_country','freight']) ? "o.$s" : "o.order_date";
    $stmt = $pdo->prepare("SELECT o.order_id,o.customer_id,o.order_date,o.shipped_date,o.ship_country,o.freight,
      CONCAT(e.first_name,' ',e.last_name) AS employee
      FROM orders o LEFT JOIN employees e ON e.employee_id=o.employee_id
      $where ORDER BY $sort_col $dir LIMIT $limit OFFSET $offset");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $extra = build_filter_params(['q'=>$search,'country'=>$country_filter,'status'=>$status_filter,'sort'=>$sort,'dir'=>strtolower($dir)]);
  ?>
  <table>
    <tr>
      <?= th_sort('order_id',    'Order #',    $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
      <?= th_sort('customer_id', 'Customer',   $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
      <th>Employee</th>
      <?= th_sort('order_date',   'Order Date', $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
      <?= th_sort('shipped_date', 'Shipped',    $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
      <?= th_sort('ship_country', 'Country',    $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
      <?= th_sort('freight',      'Freight',    $s, $dir, array_merge(['page'=>'orders'], $extra)) ?>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['order_id'] ?></td>
        <td><?= htmlspecialchars($r['customer_id']  ?? '') ?></td>
        <td><?= htmlspecialchars($r['employee']     ?? '') ?></td>
        <td><?= $r['order_date'] ?></td>
        <td><?= $r['shipped_date'] ?: '<span class="badge badge-red">Pending</span>' ?></td>
        <td><?= htmlspecialchars($r['ship_country'] ?? '') ?></td>
        <td>$<?= number_format($r['freight'], 2) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?= paginate_links('orders', $offset, $limit, $total, $extra) ?>

<?php elseif ($page === 'employees'): ?>
  <h2>Employees</h2>
  <?php
    $titles    = $pdo->query("SELECT DISTINCT title   FROM employees WHERE title   IS NOT NULL ORDER BY title")->fetchAll(PDO::FETCH_COLUMN);
    $countries = $pdo->query("SELECT DISTINCT country FROM employees WHERE country IS NOT NULL ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
  ?>
  <form class="search-bar" method="get">
    <input type="hidden" name="page" value="employees">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name...">
    <select name="title">
      <option value="">All Titles</option>
      <?php foreach ($titles as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= $title_filter===$t?'selected':'' ?>><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
    </select>
    <select name="country">
      <option value="">All Countries</option>
      <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $country_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
    <a class="reset" href="?page=employees">Reset</a>
  </form>
  <?php
    $s = safe_sort('employees', $sort, $allowed_sorts, 'last_name');
    $conditions = []; $params = [];
    if ($search)        { $conditions[] = "(e.first_name ILIKE :q OR e.last_name ILIKE :q)"; $params[':q'] = "%$search%"; }
    if ($title_filter)  { $conditions[] = "e.title = :title";     $params[':title'] = $title_filter; }
    if ($country_filter){ $conditions[] = "e.country = :country"; $params[':country'] = $country_filter; }
    $where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";
    $sort_col = in_array($s, ['employee_id','last_name','city','country']) ? "e.$s" : "e.last_name";
    $stmt = $pdo->prepare("SELECT e.employee_id,e.first_name,e.last_name,e.title,e.city,e.country,
      CONCAT(m.first_name,' ',m.last_name) AS manager
      FROM employees e LEFT JOIN employees m ON m.employee_id=e.reports_to
      $where ORDER BY $sort_col $dir");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $extra = build_filter_params(['q'=>$search,'title'=>$title_filter,'country'=>$country_filter,'sort'=>$sort,'dir'=>strtolower($dir)]);
  ?>
  <table>
    <tr>
      <?= th_sort('employee_id', '#',         $s, $dir, array_merge(['page'=>'employees'], $extra)) ?>
      <?= th_sort('last_name',   'Name',      $s, $dir, array_merge(['page'=>'employees'], $extra)) ?>
      <th>Title</th>
      <?= th_sort('city',    'City',    $s, $dir, array_merge(['page'=>'employees'], $extra)) ?>
      <?= th_sort('country', 'Country', $s, $dir, array_merge(['page'=>'employees'], $extra)) ?>
      <th>Reports To</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['employee_id'] ?></td>
        <td><?= htmlspecialchars(($r['first_name'] ?? '').' '.($r['last_name'] ?? '')) ?></td>
        <td><?= htmlspecialchars($r['title']   ?? '') ?></td>
        <td><?= htmlspecialchars($r['city']    ?? '') ?></td>
        <td><?= htmlspecialchars($r['country'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['manager'] ?? 'N/A') ?></td></tr>
    <?php endforeach; ?>
  </table>

<?php elseif ($page === 'suppliers'): ?>
  <h2>Suppliers</h2>
  <?php
    $countries = $pdo->query("SELECT DISTINCT country FROM suppliers WHERE country IS NOT NULL ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
    $cities    = $pdo->query("SELECT DISTINCT city    FROM suppliers WHERE city    IS NOT NULL ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
  ?>
  <form class="search-bar" method="get">
    <input type="hidden" name="page" value="suppliers">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by company...">
    <select name="country">
      <option value="">All Countries</option>
      <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $country_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <select name="city">
      <option value="">All Cities</option>
      <?php foreach ($cities as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $city_filter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
    <a class="reset" href="?page=suppliers">Reset</a>
  </form>
  <?php
    $s = safe_sort('suppliers', $sort, $allowed_sorts, 'company_name');
    $conditions = []; $params = [];
    if ($search)         { $conditions[] = "company_name ILIKE :q"; $params[':q'] = "%$search%"; }
    if ($country_filter) { $conditions[] = "country = :country";    $params[':country'] = $country_filter; }
    if ($city_filter)    { $conditions[] = "city = :city";          $params[':city'] = $city_filter; }
    $where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $where"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $stmt = $pdo->prepare("SELECT supplier_id,company_name,contact_name,city,country,phone FROM suppliers $where ORDER BY $s $dir LIMIT $limit OFFSET $offset");
    $stmt->execute($params); $rows = $stmt->fetchAll();
    $extra = build_filter_params(['q'=>$search,'country'=>$country_filter,'city'=>$city_filter,'sort'=>$sort,'dir'=>strtolower($dir)]);
  ?>
  <table>
    <tr>
      <?= th_sort('supplier_id',  '#',        $s, $dir, array_merge(['page'=>'suppliers'], $extra)) ?>
      <?= th_sort('company_name', 'Company',  $s, $dir, array_merge(['page'=>'suppliers'], $extra)) ?>
      <th>Contact</th>
      <?= th_sort('city',    'City',    $s, $dir, array_merge(['page'=>'suppliers'], $extra)) ?>
      <?= th_sort('country', 'Country', $s, $dir, array_merge(['page'=>'suppliers'], $extra)) ?>
      <th>Phone</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['supplier_id'] ?></td>
        <td><?= htmlspecialchars($r['company_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['contact_name'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['city']         ?? '') ?></td>
        <td><?= htmlspecialchars($r['country']      ?? '') ?></td>
        <td><?= htmlspecialchars($r['phone']        ?? '') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?= paginate_links('suppliers', $offset, $limit, $total, $extra) ?>

  <?php elseif ($page === 'reports'): ?>
    <h2>Reports</h2>
    <div class="section">
        <h2>Monthly Orders by Customer</h2>
        <table>
            <tr>
                <th>Customer</th>
                <th>Month</th>
                <th>Order Count</th>
                <th>Total Amount</th>
            </tr>

        <?php
            $rows = $pdo->query("
                SELECT c.company_name,
                    TO_CHAR(DATE_TRUNC('month', o.order_date), 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.order_id) AS order_count,
                    ROUND(SUM(od.unit_price * od.quantity * (1 - od.discount))::numeric, 2) AS total_amount
                FROM orders o
                JOIN customers c ON c.customer_id = o.customer_id
                JOIN order_details od ON od.order_id = o.order_id
                GROUP BY c.company_name, DATE_TRUNC('month', o.order_date)
                ORDER BY month DESC, c.company_name
            ")->fetchAll();

            foreach ($rows as $r):
            ?>
            <tr>
                <td><?= htmlspecialchars($r['company_name']) ?></td>
                <td><?= htmlspecialchars($r['month']) ?></td>
                <td><?= $r['order_count'] ?></td>
                <td>$<?= number_format($r['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <div class="section">
        <h2>Monthly Orders by Region</h2>

        <table>
            <tr>
                <th>Region</th>
                <th>Month</th>
                <th>Order Count</th>
                <th>Total Amount</th>
            </tr>

            <?php
            $rows = $pdo->query("
                SELECT
                    COALESCE(o.ship_region, 'Unknown') AS region,
                    TO_CHAR(DATE_TRUNC('month', o.order_date), 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.order_id) AS order_count,
                    ROUND(SUM(od.unit_price * od.quantity * (1 - od.discount))::numeric, 2) AS total_amount
                FROM orders o
                JOIN order_details od ON od.order_id = o.order_id
                GROUP BY region, DATE_TRUNC('month', o.order_date)
                ORDER BY month DESC, region
            ")->fetchAll();

            foreach ($rows as $r):
            ?>
            <tr>
                <td><?= htmlspecialchars($r['region']) ?></td>
                <td><?= htmlspecialchars($r['month']) ?></td>
                <td><?= $r['order_count'] ?></td>
                <td>$<?= number_format($r['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Monthly Orders by Employee</h2>

        <table>
            <tr>
                <th>Employee</th>
                <th>Month</th>
                <th>Order Count</th>
                <th>Total Amount</th>
            </tr>

            <?php
            $rows = $pdo->query("
                SELECT
                    CONCAT(e.first_name, ' ', e.last_name) AS employee,
                    TO_CHAR(DATE_TRUNC('month', o.order_date), 'YYYY-MM') AS month,
                    COUNT(DISTINCT o.order_id) AS order_count,
                    ROUND(SUM(od.unit_price * od.quantity * (1 - od.discount))::numeric, 2) AS total_amount
                FROM orders o
                JOIN employees e ON e.employee_id = o.employee_id
                JOIN order_details od ON od.order_id = o.order_id
                GROUP BY employee, DATE_TRUNC('month', o.order_date)
                ORDER BY month DESC, employee
            ")->fetchAll();

            foreach ($rows as $r):
            ?>
            <tr>
                <td><?= htmlspecialchars($r['employee']) ?></td>
                <td><?= htmlspecialchars($r['month']) ?></td>
                <td><?= $r['order_count'] ?></td>
                <td>$<?= number_format($r['total_amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

<?php elseif ($page === 'insert'): ?>
  <h2>Insert Record</h2>
  <?php if ($insert_success): ?><div class="alert-success"><?= htmlspecialchars($insert_success) ?></div><?php endif; ?>
  <?php if ($insert_error):   ?><div class="alert-error"><?= htmlspecialchars($insert_error) ?></div><?php endif; ?>
  <?php
    $emp_list  = $pdo->query("SELECT employee_id, CONCAT(first_name,' ',last_name) AS name FROM employees ORDER BY last_name")->fetchAll();
    $cat_list  = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name")->fetchAll();
    $sup_list  = $pdo->query("SELECT supplier_id, company_name  FROM suppliers  ORDER BY company_name")->fetchAll();
    $cus_list  = $pdo->query("SELECT customer_id, company_name  FROM customers  ORDER BY company_name")->fetchAll();
    $shi_list  = $pdo->query("SELECT shipper_id,  company_name  FROM shippers   ORDER BY company_name")->fetchAll();
  ?>
  <div class="insert-form">
    <form method="post" action="?page=insert">
      <label>Table</label>
      <select name="insert_table" id="tableSelect" onchange="showFields(this.value)" style="max-width:300px; margin-bottom:24px;">
        <option value="">Select a table...</option>
        <option value="customers" <?= ($_POST['insert_table']??'')==='customers'?'selected':'' ?>>Customers</option>
        <option value="products"  <?= ($_POST['insert_table']??'')==='products' ?'selected':'' ?>>Products</option>
        <option value="suppliers" <?= ($_POST['insert_table']??'')==='suppliers'?'selected':'' ?>>Suppliers</option>
        <option value="employees" <?= ($_POST['insert_table']??'')==='employees'?'selected':'' ?>>Employees</option>
        <option value="orders"    <?= ($_POST['insert_table']??'')==='orders'   ?'selected':'' ?>>Orders</option>
      </select>

      <div class="fields">


        <div class="field-group" id="fields-customers">
          <div><label>Customer ID (5 chars)*</label><input type="text" name="customer_id" maxlength="5" placeholder="ALFKI"></div>
          <div><label>Company Name*</label><input type="text" name="company_name"></div>
          <div><label>Contact Name</label><input type="text" name="contact_name"></div>
          <div><label>Contact Title</label><input type="text" name="contact_title"></div>
          <div><label>Address</label><input type="text" name="address"></div>
          <div><label>City</label><input type="text" name="city"></div>
          <div><label>Region</label><input type="text" name="region"></div>
          <div><label>Postal Code</label><input type="text" name="postal_code"></div>
          <div><label>Country</label><input type="text" name="country"></div>
          <div><label>Phone</label><input type="text" name="phone"></div>
          <div><label>Fax</label><input type="text" name="fax"></div>
        </div>

        <div class="field-group" id="fields-products">
          <div><label>Product Name*</label><input type="text" name="product_name"></div>
          <div><label>Category</label>
            <select name="category_id"><option value="">None</option><?php foreach ($cat_list as $c): ?><option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select>
          </div>
          <div><label>Supplier</label>
            <select name="supplier_id"><option value="">None</option><?php foreach ($sup_list as $s): ?><option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['company_name']) ?></option><?php endforeach; ?></select>
          </div>
          <div><label>Qty Per Unit</label><input type="text" name="quantity_per_unit"></div>
          <div><label>Unit Price</label><input type="number" name="unit_price" step="0.01" min="0" value="0"></div>
          <div><label>Units in Stock</label><input type="number" name="units_in_stock" min="0" value="0"></div>
          <div><label>Units on Order</label><input type="number" name="units_on_order" min="0" value="0"></div>
          <div><label>Reorder Level</label><input type="number" name="reorder_level" min="0" value="0"></div>
          <div class="checkbox-wrap"><input type="checkbox" name="discontinued" id="disc"><label for="disc" style="text-transform:none;font-size:14px;">Discontinued</label></div>
        </div>

        <div class="field-group" id="fields-suppliers">
          <div><label>Company Name*</label><input type="text" name="company_name"></div>
          <div><label>Contact Name</label><input type="text" name="contact_name"></div>
          <div><label>Contact Title</label><input type="text" name="contact_title"></div>
          <div><label>Address</label><input type="text" name="address"></div>
          <div><label>City</label><input type="text" name="city"></div>
          <div><label>Region</label><input type="text" name="region"></div>
          <div><label>Postal Code</label><input type="text" name="postal_code"></div>
          <div><label>Country</label><input type="text" name="country"></div>
          <div><label>Phone</label><input type="text" name="phone"></div>
          <div><label>Fax</label><input type="text" name="fax"></div>
          <div><label>Home Page</label><input type="text" name="home_page"></div>
        </div>

        <div class="field-group" id="fields-employees">
          <div><label>First Name*</label><input type="text" name="first_name"></div>
          <div><label>Last Name*</label><input type="text" name="last_name"></div>
          <div><label>Title</label><input type="text" name="title"></div>
          <div><label>Title of Courtesy</label><input type="text" name="title_of_courtesy" placeholder="Mr. / Ms. / Dr."></div>
          <div><label>Birth Date</label><input type="date" name="birth_date"></div>
          <div><label>Hire Date</label><input type="date" name="hire_date"></div>
          <div><label>Address</label><input type="text" name="address"></div>
          <div><label>City</label><input type="text" name="city"></div>
          <div><label>Region</label><input type="text" name="region"></div>
          <div><label>Postal Code</label><input type="text" name="postal_code"></div>
          <div><label>Country</label><input type="text" name="country"></div>
          <div><label>Home Phone</label><input type="text" name="home_phone"></div>
          <div><label>Extension</label><input type="text" name="extension"></div>
          <div><label>Reports To</label>
            <select name="reports_to"><option value="">None</option><?php foreach ($emp_list as $e): ?><option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?></select>
          </div>
          <div style="grid-column:1/-1"><label>Notes</label><textarea name="notes" rows="3"></textarea></div>
        </div>

        <div class="field-group" id="fields-orders">
          <div><label>Customer*</label>
            <select name="customer_id"><option value="">Select...</option><?php foreach ($cus_list as $c): ?><option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option><?php endforeach; ?></select>
          </div>
          <div><label>Employee</label>
            <select name="employee_id"><option value="">None</option><?php foreach ($emp_list as $e): ?><option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['name']) ?></option><?php endforeach; ?></select>
          </div>
          <div><label>Order Date</label><input type="date" name="order_date"></div>
          <div><label>Required Date</label><input type="date" name="required_date"></div>
          <div><label>Ship Via</label>
            <select name="ship_via"><option value="">None</option><?php foreach ($shi_list as $s): ?><option value="<?= $s['shipper_id'] ?>"><?= htmlspecialchars($s['company_name']) ?></option><?php endforeach; ?></select>
          </div>
          <div><label>Freight</label><input type="number" name="freight" step="0.01" min="0" value="0"></div>
          <div><label>Ship Name</label><input type="text" name="ship_name"></div>
          <div><label>Ship Address</label><input type="text" name="ship_address"></div>
          <div><label>Ship City</label><input type="text" name="ship_city"></div>
          <div><label>Ship Region</label><input type="text" name="ship_region"></div>
          <div><label>Ship Postal Code</label><input type="text" name="ship_postal_code"></div>
          <div><label>Ship Country</label><input type="text" name="ship_country"></div>
        </div>

      </div>
      <button type="submit" id="submitBtn" style="display:none;">Insert Record</button>
    </form>
  </div>
  <script>
    function showFields(table) {
      document.querySelectorAll('.field-group').forEach(el => {
        el.classList.remove('active');
        el.querySelectorAll('input, select, textarea').forEach(field => field.disabled = true);
      });
      document.getElementById('submitBtn').style.display = 'none';
      if (table) {
        var el = document.getElementById('fields-' + table);
        if (el) {
          el.classList.add('active');
          el.querySelectorAll('input, select, textarea').forEach(field => field.disabled = false);
          document.getElementById('submitBtn').style.display = 'inline-block';
        }
      }
    }
    showFields(document.getElementById('tableSelect').value);
  </script>

<?php endif; ?>
</div>
</body>
</html>
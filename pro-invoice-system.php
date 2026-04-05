<?php
/*
Plugin Name: Pro Invoice System Arabic
Version: 13.1
*/

if (!defined('ABSPATH')) exit;

function pis_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'pis_invoices';

    $sql = "CREATE TABLE $table (
        id INT AUTO_INCREMENT,
        invoice_number VARCHAR(50),
        invoice_date DATE,
        supplier VARCHAR(255),
        total DECIMAL(10,2),
        category VARCHAR(100),
        image TEXT,
        PRIMARY KEY (id)
    )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'pis_create_table');

/* ======= التعديل الوحيد (Excel احترافي + إجمالي) ======= */
function pis_export_excel($category = null){
    if (ob_get_length()) ob_end_clean();

    global $wpdb;
    $table = $wpdb->prefix . 'pis_invoices';

    if($category){
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE category=%s", $category));
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table");
    }

    $total_sum = 0;

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=invoices-" . date("Y-m-d") . ".xls");

    echo "\xEF\xBB\xBF";

    echo "رقم الفاتورة	التاريخ	المورد	الإجمالي	التصنيف
";
    echo "----------------------------------------------------
";

    foreach($results as $r){
        $total_sum += $r->total;
        echo "{$r->invoice_number}	{$r->invoice_date}	{$r->supplier}	{$r->total}	{$r->category}
";
    }

    echo "----------------------------------------------------
";
    echo "الإجمالي الكلي			{$total_sum}	
";

    exit;
}

add_action('admin_init', function(){
    if(isset($_GET['pis_export'])){
        $cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : null;
        pis_export_excel($cat);
    }
});

/* ===== باقي الكود كما هو بدون تغيير ===== */

add_action('admin_menu', function(){
    add_menu_page('الفواتير','الفواتير','manage_options','pis-list','pis_list');
    add_submenu_page('pis-list','إضافة فاتورة','إضافة فاتورة','manage_options','pis-add','pis_add');
});

function pis_add(){
    global $wpdb;
    $table = $wpdb->prefix . 'pis_invoices';

    if(isset($_POST['save'])){
        $image='';
        if(!empty($_FILES['image']['name'])){
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
            if(isset($upload['url'])) $image=$upload['url'];
        }

        $wpdb->insert($table,[
            'invoice_number'=>$_POST['invoice_number'],
            'invoice_date'=>$_POST['invoice_date'],
            'supplier'=>$_POST['supplier'],
            'total'=>$_POST['total'],
            'category'=>$_POST['category'],
            'image'=>$image
        ]);

        echo "<div class='notice notice-success'><p>تم الحفظ</p></div>";
        echo "<a href='?page=pis-list' class='button button-primary'>عرض الفواتير</a>";
    }
?>
<div class="wrap pis-card">
<h1>إضافة فاتورة</h1>

<form method="post" enctype="multipart/form-data" class="pis-form">

<label>رقم الفاتورة</label>
<input type="text" name="invoice_number" required>

<label>التاريخ</label>
<input type="date" name="invoice_date" required>

<label>المورد</label>
<input type="text" name="supplier">

<label>الإجمالي</label>
<input type="number" step="0.01" name="total">

<label>التصنيف</label>
<select name="category">
<option>مبيعات</option>
<option>مشتريات</option>
<option>مصروفات</option>
</select>

<label>صورة الفاتورة</label>
<input type="file" name="image">

<button name="save" class="pis-btn">حفظ</button>
</form>
</div>
<?php }

function pis_list(){
    global $wpdb;
    $table = $wpdb->prefix . 'pis_invoices';

    if(isset($_GET['delete'])){
        $wpdb->delete($table,['id'=>intval($_GET['delete'])]);
    }

    if(isset($_GET['view'])){
        $row=$wpdb->get_row("SELECT * FROM $table WHERE id=".intval($_GET['view']));
?>
<div class="wrap pis-card">
<h1>تفاصيل الفاتورة</h1>
<p><strong>رقم:</strong> <?= $row->invoice_number ?></p>
<p><strong>التاريخ:</strong> <?= $row->invoice_date ?></p>
<p><strong>المورد:</strong> <?= $row->supplier ?></p>
<p><strong>الإجمالي:</strong> <?= $row->total ?> ر.س</p>
<p><strong>التصنيف:</strong> <?= $row->category ?></p>
<?php if($row->image): ?>
<img src="<?= $row->image ?>" style="max-width:300px">
<?php endif; ?>
<br><br>
<a href="?page=pis-list" class="button">⬅️ رجوع</a>
</div>
<?php return; }

    if(isset($_GET['edit'])){
        $id=intval($_GET['edit']);
        $row=$wpdb->get_row("SELECT * FROM $table WHERE id=$id");

        if(isset($_POST['update'])){
            $image=$row->image;
            if(!empty($_FILES['image']['name'])){
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
                if(isset($upload['url'])) $image=$upload['url'];
            }

            $wpdb->update($table,[
                'invoice_number'=>$_POST['invoice_number'],
                'invoice_date'=>$_POST['invoice_date'],
                'supplier'=>$_POST['supplier'],
                'total'=>$_POST['total'],
                'category'=>$_POST['category'],
                'image'=>$image
            ],['id'=>$id]);

            echo "<script>location.href='?page=pis-list'</script>";
        }
?>
<div class="wrap pis-card">
<h1>تعديل الفاتورة</h1>

<form method="post" enctype="multipart/form-data" class="pis-form">
<input type="text" name="invoice_number" value="<?= $row->invoice_number ?>">
<input type="date" name="invoice_date" value="<?= $row->invoice_date ?>">
<input type="text" name="supplier" value="<?= $row->supplier ?>">
<input type="number" name="total" value="<?= $row->total ?>">

<select name="category">
<option <?= $row->category=='مبيعات'?'selected':'' ?>>مبيعات</option>
<option <?= $row->category=='مشتريات'?'selected':'' ?>>مشتريات</option>
<option <?= $row->category=='مصروفات'?'selected':'' ?>>مصروفات</option>
</select>

<label>تغيير الصورة</label>
<input type="file" name="image">

<?php if($row->image): ?>
<img src="<?= $row->image ?>" style="max-width:100px">
<?php endif; ?>

<button name="update" class="pis-btn">تحديث</button>
</form>
</div>
<?php return; }

    $selected = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';

    if($selected){
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE category=%s",$selected));
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table");
    }

    $total = $wpdb->get_var("SELECT SUM(total) FROM $table");
?>

<div class="wrap">
<h1>كل الفواتير</h1>

<a href="?page=pis-add" class="pis-btn">➕ إضافة فاتورة</a>

<form method="get" style="margin:15px 0">
<input type="hidden" name="page" value="pis-list">

<select name="cat">
<option value="">كل التصنيفات</option>
<option value="مبيعات" <?= $selected=='مبيعات'?'selected':'' ?>>مبيعات</option>
<option value="مشتريات" <?= $selected=='مشتريات'?'selected':'' ?>>مشتريات</option>
<option value="مصروفات" <?= $selected=='مصروفات'?'selected':'' ?>>مصروفات</option>
</select>

<button class="button">فلترة</button>

<a href="?pis_export=1" class="button button-primary">تصدير الكل</a>

<?php if($selected): ?>
<a href="?pis_export=1&cat=<?= $selected ?>" class="button">تصدير (<?= $selected ?>)</a>
<?php endif; ?>

</form>

<div>عدد الفواتير: <?= count($results) ?> | الإجمالي: <?= $total ?> ر.س</div>

<table class="widefat">
<tr>
<th>#</th>
<th>رقم</th>
<th>المورد</th>
<th>الإجمالي</th>
<th>التصنيف</th>
<th>الصورة</th>
<th>إجراءات</th>
</tr>

<?php foreach($results as $r): ?>
<tr>
<td><?= $r->id ?></td>
<td><?= $r->invoice_number ?></td>
<td><?= $r->supplier ?></td>
<td><?= $r->total ?></td>
<td><?= $r->category ?></td>
<td><?php if($r->image): ?><img src="<?= $r->image ?>" style="max-width:50px"><?php endif; ?></td>
<td>
<a class="button" href="?page=pis-list&view=<?= $r->id ?>">عرض</a>
<a class="button" href="?page=pis-list&edit=<?= $r->id ?>">تعديل</a>
<a class="button" href="?page=pis-list&delete=<?= $r->id ?>" onclick="return confirm('حذف؟')">حذف</a>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

<style>
.wrap{direction:rtl;font-family:Cairo}
.pis-btn{background:#2271b1;color:#fff;padding:10px;border-radius:6px;text-decoration:none}
.pis-card{background:#fff;padding:25px;border-radius:12px;max-width:600px}
</style>

<?php }

/* =========================
   Frontend (إضافة فقط)
========================= */
add_shortcode('pis_frontend', function(){

if(!is_user_logged_in()){
    return "<p>يجب تسجيل الدخول</p>";
}

if(!current_user_can('administrator')){
    return "<p>❌ غير مصرح لك</p>";
}

ob_start();
global $wpdb;
$table = $wpdb->prefix . 'pis_invoices';

if(isset($_POST['add_invoice'])){
    $image='';
    if(!empty($_FILES['image']['name'])){
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
        if(isset($upload['url'])) $image=$upload['url'];
    }

    $wpdb->insert($table,[
        'invoice_number'=>$_POST['invoice_number'],
        'invoice_date'=>$_POST['invoice_date'],
        'supplier'=>$_POST['supplier'],
        'total'=>$_POST['total'],
        'category'=>$_POST['category'],
        'image'=>$image
    ]);

    echo "<p style='color:green'>تمت الإضافة</p>";
}

$results = $wpdb->get_results("SELECT * FROM $table");
?>

<div style="max-width:1000px;margin:auto;background:#fff;padding:20px;border-radius:10px">

<h2>📊 نظام الفواتير</h2>

<form method="post" enctype="multipart/form-data">
<input type="text" name="invoice_number" placeholder="رقم الفاتورة" required>
<input type="date" name="invoice_date" required>
<input type="text" name="supplier" placeholder="المورد">
<input type="number" name="total" placeholder="الإجمالي">

<select name="category">
<option>مبيعات</option>
<option>مشتريات</option>
<option>مصروفات</option>
</select>

<input type="file" name="image">

<button name="add_invoice">➕ إضافة</button>
</form>

<hr>

<table style="width:100%;margin-top:20px;border-collapse:collapse">
<tr>
<th>رقم</th>
<th>المورد</th>
<th>الإجمالي</th>
<th>التصنيف</th>
<th>الصورة</th>
</tr>

<?php foreach($results as $r): ?>
<tr>
<td><?= $r->invoice_number ?></td>
<td><?= $r->supplier ?></td>
<td><?= $r->total ?></td>
<td><?= $r->category ?></td>
<td><?php if($r->image): ?><img src="<?= $r->image ?>" width="50"><?php endif; ?></td>
</tr>
<?php endforeach; ?>

</table>

</div>

<?php
return ob_get_clean();
});

/* =========================
   حماية المدير فقط (إضافة)
========================= */
add_action('template_redirect', function(){

    if(is_page('invoices')){

        if(!is_user_logged_in()){
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        if(!current_user_can('administrator')){
            wp_die('❌ غير مصرح لك بالدخول');
        }

    }

});


/* =========================
   Frontend Advanced Actions (إضافة فقط)
========================= */
add_shortcode('pis_frontend_pro', function(){

if(!is_user_logged_in()){
    return "<p>يجب تسجيل الدخول</p>";
}

if(!current_user_can('administrator')){
    return "<p>❌ غير مصرح لك</p>";
}

global $wpdb;
$table = $wpdb->prefix . 'pis_invoices';

ob_start();

/* DELETE */
if(isset($_GET['del'])){
    $wpdb->delete($table, ['id'=>intval($_GET['del'])]);
}

/* ADD */
if(isset($_POST['add_invoice'])){
    $image='';
    if(!empty($_FILES['image']['name'])){
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
        if(isset($upload['url'])) $image=$upload['url'];
    }

    $wpdb->insert($table,[
        'invoice_number'=>$_POST['invoice_number'],
        'invoice_date'=>$_POST['invoice_date'],
        'supplier'=>$_POST['supplier'],
        'total'=>$_POST['total'],
        'category'=>$_POST['category'],
        'image'=>$image
    ]);
}

/* UPDATE */
if(isset($_POST['update_invoice'])){
    $id = intval($_POST['id']);
    $row = $wpdb->get_row("SELECT * FROM $table WHERE id=$id");

    $image = $row->image;
    if(!empty($_FILES['image']['name'])){
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
        if(isset($upload['url'])) $image=$upload['url'];
    }

    $wpdb->update($table,[
        'invoice_number'=>$_POST['invoice_number'],
        'invoice_date'=>$_POST['invoice_date'],
        'supplier'=>$_POST['supplier'],
        'total'=>$_POST['total'],
        'category'=>$_POST['category'],
        'image'=>$image
    ],['id'=>$id]);
}

/* FILTER */
$where = "";
if(isset($_GET['cat']) && $_GET['cat']){
    $cat = sanitize_text_field($_GET['cat']);
    $where = $wpdb->prepare("WHERE category=%s",$cat);
}

/* EXPORT */
if(isset($_GET['export_front'])){
    if (ob_get_length()) ob_end_clean();

    $results = $wpdb->get_results("SELECT * FROM $table $where");

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=frontend-invoices.xls");

    echo "\\xEF\\xBB\\xBF";
    echo "رقم\tالتاريخ\tالمورد\tالإجمالي\tالتصنيف\n";

    $sum = 0;
    foreach($results as $r){
        $sum += $r->total;
        echo "{$r->invoice_number}\t{$r->invoice_date}\t{$r->supplier}\t{$r->total}\t{$r->category}\n";
    }
    echo "الإجمالي\t\t\t{$sum}\n";
    exit;
}

/* VIEW */
if(isset($_GET['view'])){
    $row = $wpdb->get_row("SELECT * FROM $table WHERE id=".intval($_GET['view']));
    ?>
    <div style="background:#fff;padding:20px">
    <h3>تفاصيل الفاتورة</h3>
    <p>رقم: <?= $row->invoice_number ?></p>
    <p>التاريخ: <?= $row->invoice_date ?></p>
    <p>المورد: <?= $row->supplier ?></p>
    <p>الإجمالي: <?= $row->total ?></p>
    <p>التصنيف: <?= $row->category ?></p>
    <?php if($row->image): ?><img src="<?= $row->image ?>" width="200"><?php endif; ?>
    <br><a href="?">رجوع</a>
    </div>
    <?php
    return ob_get_clean();
}

/* EDIT */
if(isset($_GET['edit'])){
    $row = $wpdb->get_row("SELECT * FROM $table WHERE id=".intval($_GET['edit']));
    ?>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $row->id ?>">
    <input type="text" name="invoice_number" value="<?= $row->invoice_number ?>">
    <input type="date" name="invoice_date" value="<?= $row->invoice_date ?>">
    <input type="text" name="supplier" value="<?= $row->supplier ?>">
    <input type="number" name="total" value="<?= $row->total ?>">
    <select name="category">
    <option <?= $row->category=='مبيعات'?'selected':'' ?>>مبيعات</option>
    <option <?= $row->category=='مشتريات'?'selected':'' ?>>مشتريات</option>
    <option <?= $row->category=='مصروفات'?'selected':'' ?>>مصروفات</option>
    </select>
    <input type="file" name="image">
    <button name="update_invoice">تحديث</button>
    </form>
    <?php
    return ob_get_clean();
}

$results = $wpdb->get_results("SELECT * FROM $table $where");
?>

<div style="max-width:1000px;margin:auto">

<h2>نظام الفواتير</h2>

<form method="get">
<select name="cat">
<option value="">كل التصنيفات</option>
<option value="مبيعات">مبيعات</option>
<option value="مشتريات">مشتريات</option>
<option value="مصروفات">مصروفات</option>
</select>
<button>فلترة</button>
<a href="?export_front=1">📥 Excel</a>
</form>

<table style="width:100%;border-collapse:collapse">
<tr>
<th>رقم</th><th>المورد</th><th>الإجمالي</th><th>التصنيف</th><th>إجراءات</th>
</tr>

<?php foreach($results as $r): ?>
<tr>
<td><?= $r->invoice_number ?></td>
<td><?= $r->supplier ?></td>
<td><?= $r->total ?></td>
<td><?= $r->category ?></td>
<td>
<a href="?view=<?= $r->id ?>">عرض</a>
<a href="?edit=<?= $r->id ?>">تعديل</a>
<a href="?del=<?= $r->id ?>">حذف</a>
</td>
</tr>
<?php endforeach; ?>

</table>

</div>

<?php
return ob_get_clean();
});


/* =========================
   UI PRO FRONTEND (إضافة فقط)
========================= */
add_shortcode('pis_ui_pro', function(){

if(!is_user_logged_in()){
    return "<p style='text-align:center'>يجب تسجيل الدخول</p>";
}

if(!current_user_can('administrator')){
    return "<p style='text-align:center;color:red'>غير مصرح</p>";
}

global $wpdb;
$table = $wpdb->prefix . 'pis_invoices';

ob_start();

/* ADD */
if(isset($_POST['ui_add'])){
    $image='';
    if(!empty($_FILES['image']['name'])){
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($_FILES['image'], ['test_form'=>false]);
        if(isset($upload['url'])) $image=$upload['url'];
    }

    $wpdb->insert($table,[
        'invoice_number'=>$_POST['invoice_number'],
        'invoice_date'=>$_POST['invoice_date'],
        'supplier'=>$_POST['supplier'],
        'total'=>$_POST['total'],
        'category'=>$_POST['category'],
        'image'=>$image
    ]);
}

/* DELETE */
if(isset($_GET['ui_delete'])){
    $wpdb->delete($table,['id'=>intval($_GET['ui_delete'])]);
}

/* VIEW */
if(isset($_GET['ui_view'])){
    $r = $wpdb->get_row("SELECT * FROM $table WHERE id=".intval($_GET['ui_view']));
    ?>
    <div class="pis-card">
        <h2>تفاصيل الفاتورة</h2>
        <p>رقم: <?= $r->invoice_number ?></p>
        <p>التاريخ: <?= $r->invoice_date ?></p>
        <p>المورد: <?= $r->supplier ?></p>
        <p>الإجمالي: <?= $r->total ?> ر.س</p>
        <p>التصنيف: <?= $r->category ?></p>
        <?php if($r->image): ?><img src="<?= $r->image ?>" class="pis-img"><?php endif; ?>
        <a href="?" class="pis-btn">رجوع</a>
    </div>
    <?php
    return ob_get_clean();
}

$results = $wpdb->get_results("SELECT * FROM $table");

?>

<div class="pis-container">

<h2>📊 نظام الفواتير</h2>

<div class="pis-grid">

<form method="post" enctype="multipart/form-data" class="pis-form">
<h3>➕ إضافة فاتورة</h3>

<input type="text" name="invoice_number" placeholder="رقم الفاتورة" required>
<input type="date" name="invoice_date" required>
<input type="text" name="supplier" placeholder="المورد">
<input type="number" name="total" placeholder="الإجمالي">

<select name="category">
<option>مبيعات</option>
<option>مشتريات</option>
<option>مصروفات</option>
</select>

<input type="file" name="image">

<button name="ui_add">حفظ</button>
</form>

<div class="pis-table">

<h3>📄 الفواتير</h3>

<table>
<tr>
<th>رقم</th>
<th>المورد</th>
<th>الإجمالي</th>
<th>إجراءات</th>
</tr>

<?php foreach($results as $r): ?>
<tr>
<td><?= $r->invoice_number ?></td>
<td><?= $r->supplier ?></td>
<td><?= $r->total ?></td>
<td>
<a href="?ui_view=<?= $r->id ?>" class="view">عرض</a>
<a href="?ui_delete=<?= $r->id ?>" class="del">حذف</a>
</td>
</tr>
<?php endforeach; ?>

</table>

</div>

</div>

</div>

<style>
.pis-container{
max-width:1200px;
margin:auto;
padding:20px;
font-family:Cairo;
}
.pis-grid{
display:grid;
grid-template-columns:1fr 2fr;
gap:20px;
}
.pis-form, .pis-table{
background:#fff;
padding:20px;
border-radius:12px;
box-shadow:0 4px 15px rgba(0,0,0,.05);
}
input,select{
width:100%;
margin-bottom:10px;
padding:10px;
border-radius:6px;
border:1px solid #ddd;
}
button{
background:#2271b1;
color:#fff;
padding:10px;
border:none;
border-radius:6px;
width:100%;
}
table{
width:100%;
border-collapse:collapse;
}
td,th{
padding:10px;
border-bottom:1px solid #eee;
text-align:center;
}
.view{
background:#2271b1;
color:#fff;
padding:5px 10px;
border-radius:5px;
text-decoration:none;
}
.del{
background:#d63638;
color:#fff;
padding:5px 10px;
border-radius:5px;
text-decoration:none;
}
.pis-card{
background:#fff;
padding:20px;
border-radius:12px;
max-width:600px;
margin:auto;
text-align:center;
}
.pis-img{
max-width:250px;
margin-top:15px;
border-radius:10px;
}
.pis-btn{
display:inline-block;
margin-top:15px;
background:#2271b1;
color:#fff;
padding:10px 15px;
border-radius:6px;
text-decoration:none;
}
@media(max-width:768px){
.pis-grid{
grid-template-columns:1fr;
}
}
</style>

<?php
return ob_get_clean();
});

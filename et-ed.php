<?php
// Open the database

include 'config.php';
include 'db.php';


$i_name   = $_POST['item_name'];
$i_desc   = $_POST['item_description'];
$b_name   = $_POST['borrower_name'];
$b_netid  = $_POST['borrower_netid'];
$b_time   = new DateTime('@'.strtotime($_POST['pickup_datetime']));
// See below for setting repetition
$d_time   = new DateTime('@'.strtotime($_POST['due_datetime']));
$erow     = substr($_POST['editrow'], 7);

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    $fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);
    $entry = array($i_name, $i_desc, $b_name, $b_netid, $b_time->format($dt_fmt), $d_time->format($dt_fmt));
    foreach ($entry as $key => $val) {
        $entry[$key] = htmlspecialchars($val);
    }
    $fdb->editRow($loans_table, $erow, $entry);
    echo header('Location: et-lo.php');
}

?>


<?php
// Open the database

include 'config.php';

$fdb = fopen($db_filename, 'a') or die('ERROR: Unable to open database for appending!');

$i_name   = $_POST['item_name'];
$i_desc   = $_POST['item_description'];
$b_name   = $_POST['borrower_name'];
$b_netid  = $_POST['borrower_netid'];
$b_time   = date('o-m-d\TH:i', strtotime($_POST['pickup_datetime']));
$d_time   = date('o-m-d\TH:i', strtotime($_POST['due_datetime']));

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    $entry = $i_name . $sep . $i_desc . $sep . $b_name . $sep . $b_netid . $sep . $b_time . $sep . $d_time . "\n";
    $escaped_entry = htmlspecialchars($entry);
    fwrite($fdb, $escaped_entry);
    fclose($fdb);
    echo header('Location: et-lo.php');
}

?>

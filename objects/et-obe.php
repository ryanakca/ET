<?php
// Open the database

include '../config.php';
include '../db.php';


$i_name   = $_POST['item_name'];

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    $fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);
    $entry = array(htmlspecialchars($i_name));
    $erow = substr($_POST['editrow'], 7);
    $fdb->editRow($objects_table, $erow, $entry);
    echo header('Location: view.html');
}

?>


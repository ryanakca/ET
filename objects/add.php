<?php
// Open the database

include '../config.php';
include '../db.php';

$fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    $i_name = $_POST['item_name'];
    $entry = array(htmltospecialchars($i_name));
    $fdb->newRow($objects_table, $entry);
    echo header('Location: view.php');
}

?>

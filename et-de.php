<?php

include 'config.php';
include 'db.php';

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    echo header('Location: et-lo.php');
} else {
    // $_POST['row'] is in the format: delrowX
    // where X is the row we want to delete.
    $row = (int)substr($_POST['row'], 6);

    $fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);

    $fdb->deleteRow($loans_table, $row);
}

?>

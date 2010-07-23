<?php
/*
 * Copyright (C) 2010 Ryan Kavanagh <ryanakca@kubuntu.org>
 *
 * See file COPYING for details
 *
 */

include '../config.php';
include '../db.php';

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    echo header('Location: view.php');
} else {
    // $_POST['row'] is in the format: delrowX
    // where X is the row we want to delete.
    $row = (int)substr($_POST['row'], 6);

    $fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);

    $fdb->deleteRow($objects_table, $row);
}

?>

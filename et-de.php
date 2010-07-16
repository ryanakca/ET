<?php

//$empty_check = implode('', $_POST);
$empty_check = 'b';
$_POST['row'] = 'delrow2';
if (empty($empty_check)) {
    echo header('Location: et-lo.php');
} else {
    // $_POST['row'] is in the format: delrowX
    // where X is the row we want to delete.
    $drow = (int)substr($_POST['row'], 6);

    $db_lines = file('et_db.txt');

    unset($db_lines[$drow]);
    $fdb = fopen('et_db.txt', 'w'); 
    fwrite($fdb, join('',$db_lines));
}

?>

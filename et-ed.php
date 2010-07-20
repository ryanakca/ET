<?php
// Open the database

include 'config.php';


$i_name   = $_POST['item_name'];
$i_desc   = $_POST['item_description'];
$b_name   = $_POST['borrower_name'];
$b_netid  = $_POST['borrower_netid'];
$b_time   = new DateTime('@'.strtotime($_POST['pickup_datetime']));
// See below for setting repetition
$d_time   = new DateTime('@'.strtotime($_POST['due_datetime']));
$erow     = (int)substr($_POST['row'], 6); 

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    $db_lines = file($db_filename);
    $fdb = fopen($db_filename, 'w') or die('ERROR: Unable to open database for editing!');
    foreach ($db_lines as $lineno => $line) {
        if ($lineno == $erow) {
            $entry = $i_name . $sep . $i_desc . $sep . $b_name . $sep . $b_netid . $sep . $b_time->format($dt_fmt) . $sep . $d_time->format($dt_fmt) . "\n";
            $escaped_entry = htmlspecialchars($entry);
            fwrite($fdb, $escaped_entry);
        } else {
            fwrite($fdb, $line);
        }
    }
    fclose($fdb);
    echo header('Location: et-lo.php');
}

?>


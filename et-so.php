<?php
// Open the database

include 'config.php';
include 'db.php';

$fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);

$i_name   = $_POST['item_name'];
$i_desc   = $_POST['item_description'];
$b_name   = $_POST['borrower_name'];
$b_netid  = $_POST['borrower_netid'];
$b_time   = new DateTime('@'.strtotime($_POST['pickup_datetime']));
// See below for setting repetition
$d_time   = new DateTime('@'.strtotime($_POST['due_datetime']));

$empty_check = implode('', $_POST);
if (empty($empty_check)) {
    die('ERROR: Submitted an empty form!');
} else {
    // Set $b_rep to 1 if not null;
    $b_rep = (isset($_POST['repetition']) && !empty($_POST['repetition'])) ? $_POST['repetition'] : 1;
    for ($rep = 0; $rep < $b_rep; $rep++) {
        $entry = $i_name . $cell_sep . $i_desc . $cell_sep . $b_name . $cell_sep . $b_netid . $cell_sep . $b_time->format($dt_fmt) . $cell_sep . $d_time->format($dt_fmt) . "\n";
        $escaped_entry = htmlspecialchars($entry);
        $fdb->newRow($loans_table, $entry);
        // Increment time by one week for next pass/repetition. P1W -> Period of
        // 1 Week. See ISO8601
        $b_time->add(new DateInterval('P1W'));
        $d_time->add(new DateInterval('P1W'));
    }
    echo header('Location: et-lo.php');
}

?>

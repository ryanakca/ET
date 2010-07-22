  <thead>
    <tr>
      <th>Item Name</th>
      <th>Borrower Name</th>
      <th>Borrower NetID</th>
      <th>Pickup Time</th>
      <th>Due Time</th>
    </tr>
  </thead>
  <tbody>
<?php
include '../config.php';
include '../db.php';

$fdb = new FlatFileDB($db_filename, $table_sep, $cell_sep);

foreach ($fdb->getTable($loans_table) as $rownum => $row) {
    $class = '';

    if (date($row[5]) < date($dt_fmt)) {
        $class = ' class="late"';
    } elseif ($rownum % 2 == 0) {
        $class = ' class="alt"';
    }

    echo '<tr' . $class . '>';
    echo '<td class="item_name">' . $row[0] . '</td>';
    echo '<td class="borrower_name">' . $row[1] . '</td>';
    echo '<td class="borrower_netid">' . $row[2] . '</td>';
    echo '<td class="pickup_datetime">' . $row[3] . '</td>';
    echo '<td class="due_datetime">' . $row[4] . '</td>';
    echo '</tr>';
    $row++;
}

?>
  </tbody>

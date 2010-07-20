  <thead>
    <tr>
      <th>Item Name</th>
      <th>Item Description</th>
      <th>Borrower Name</th>
      <th>Borrower NetID</th>
      <th>Pickup Time</th>
      <th>Due Time</th>
    </tr>
  </thead>
  <tbody>
<?php
include 'config.php';

$fdb = fopen($db_filename, 'r') or die('ERROR: Unable to open database for reading!');

$row = 0;

while ($line = fgets($fdb)) {
$cells[$row] = explode($sep, $line);

$class = '';

if (date($cells[$row][5]) < date($dt_fmt)) {
    $class = ' class="late"';
} elseif ($row % 2 == 0) {
    $class = ' class="alt"';
}

echo '<tr' . $class . '>';
echo '<td class="item_name">' . $cells[$row][0] . '</td>';
echo '<td class="item_description">' . $cells[$row][1] . '</td>';
echo '<td class="borrower_name">' . $cells[$row][2] . '</td>';
echo '<td class="borrower_netid">' . $cells[$row][3] . '</td>';
echo '<td class="pickup_datetime">' . $cells[$row][4] . '</td>';
echo '<td class="due_datetime">' . $cells[$row][5] . '</td>';
echo '</tr>';
$row++;
}
fclose($fdb);

?>
  </tbody>

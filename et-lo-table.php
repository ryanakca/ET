  <thead>
    <tr>
      <th>Item Name</th>
      <th>Item Description</th>
      <th>Borrower Name</th>
      <th>Borrower NetID</th>
      <th>Pickup Time</th>
      <th>Due Time</th>
      <th>Delete</th>
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
foreach ($cells[$row] as $cell) {
    echo '<td>' . $cell . '</td>';
}
echo '<td class="delete" id="delrow'.$row.'">Yes</td>';
echo '</tr>';
$row++;
}
fclose($fdb);

?>
  </tbody>

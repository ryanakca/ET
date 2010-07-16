<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Currently Loaned Out Items</title>
    <link type="text/css" rel="stylesheet" href="css/et.css" />
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script type="text/JavaScript" src="js/jquery-1.4.2.min.js"></script>
    <script type="text/JavaScript" src="js/jquery-ui-1.8.2.custom.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $('td.delete').click(function(){
            var $row = $(this).attr('id');
            if (confirm("Really delete this entry?")) {
                $.post("et-de.php", { row: $row });
                $(this).parent().remove();
            }
        });
    });
    </script>
  </head>
  <body>
    <h1>Currently Loaned Out Items</h1>
    <table id="loanedout">
      <thead>
        <tr>
          <th>Item Name</th>
          <th>Item Description</th>
          <th>Borrower Name</th>
          <th>Borrower NetID</th>
          <th>Borrowed Time</th>
          <th>Due Date</th>
          <th>Due Time</th>
          <th>Delete</th>
        </tr>
      </thead>
      <tbody>
<?php
$fdb = fopen('et_db.txt', 'r') or die('ERROR: Unable to open database for appending!');

$row = 0;

while ($line = fgets($fdb)) {
    $cells[$row] = explode('', $line);

    $class = '';

    if (date($cells[$row][4]) < date('o-m-d')) {
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
    </table>
  <p><a href="et-so.html">Sign out an item</a></p>
  </body>
</html>

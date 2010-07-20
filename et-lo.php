<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Currently Loaned Out Items</title>
    <link type="text/css" rel="stylesheet" href="css/et.css" />
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <?php include 'header.php' ?>
    <script type="text/javascript">
    function addED() {
            var $curTdPerRow = $('#loanedout tbody tr:first td').length;
            $('#loanedout thead tr').append('<th>Delete</th>');
            $rows = $('#loanedout tbody tr').length;
            for (i = 0; i < $rows; i++) {
                $('#loanedout tbody tr:eq('+i+')').append('<td id="delrow'+i+'" class="delete">Yes</td>');
            }
    };
    $(document).ready(function(){
                $('#loanedout').load('et-lo-table.php', addED);
    });
    $('td.delete').live('click', function(){
            var $row = $(this).attr('id');
            if (confirm("Really delete this entry?")) {
                $.post("et-de.php", { row: $row });
                $('#loanedout').load('et-lo-table.php', addED);
            }
    });
    </script>
  </head>
  <body>
    <h1>Currently Loaned Out Items</h1>
    <table id="loanedout">
    </table>
  <p><a href="et-so.html">Sign out an item</a></p>
  </body>
</html>

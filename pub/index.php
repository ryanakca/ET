<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
  Copyright (C) 2010 Ryan Kavanagh <ryanakca@kubuntu.org>

  See file COPYING for details.
-->
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Currently Loaned Out Items</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <?php include '../header.php' ?>
    <script type="text/javascript">
    $(document).ready(function(){
        $('#loanedout').load('et-lo-table.php', function(){
            $('#loanedout').tablesorter();
        });
        $('#objects').load('objects-table.html', function(){
            $('#objects').tablesorter();
        });
    });
    </script>
  </head>
  <body>
    <h1>Currently Loaned Out Items</h1>
    <p><em>Click on a column header to sort that column.</em></p>
    <table id="loanedout">
    </table>
    <h1>Items available for loan</h1>
    <table id="objects">
    </table>
  </body>
</html>

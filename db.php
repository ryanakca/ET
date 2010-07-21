<?php
class FlatFileDB {

    private $db_filename = NULL;
    private $table_seperator = NULL;
    private $cell_seperator = NULL;
    private $fdb = NULL;

    function __construct($db_filename, $table_seperator="=.=.=", $cell_seperator="")
    {
        // Creates a FlatFileDB for $db_filename
        $this->db_filename = $db_filename;
        $this->table_seperator = $table_seperator;
        $this->cell_seperator = $cell_seperator;
        $this->fdb = new SplFileObject($this->db_filename);
    }

    private function openDB($mode='r')
    {
        $this->fdb = new SplFileObject($this->db_filename, $mode);
    }

    private function lockDB_r()
    {
        // Locks the DB for reading
        $this->fdb->flock(LOCK_SH) or die('Error: Unable to lock DB for reading.');
    }

    private function lockDB_w()
    {
        // Locks the DB for writing
        $this->fdb->flock(LOCK_EX) or die('Error: Unable to lock DB for writing.');
    }

    private function unlockDB()
    {
        // Unlocks the DB
        $this->fdb->flock(LOCK_UN);
    }

    private function getTableRange($table)
    {
        // Returns the first and last line number of a table, excluding the
        // header.
        //
        // Caveat: We don't lock the table, you should do that in the function
        // you call this one from. Otherwise, someone might edit the database
        // and invalidate this table range.

        $limits = array();

        // Return to the top of file
        $this->fdb->rewind();

        // Marks whether or not we are in our table
        $in_table = false;
        foreach ($this->fdb as $lineno => $row) {
            if (!$in_table) {
                if (preg_match('/^'.$this->table_seperator . ' ' . $table . ' ' . $this->table_seperator .'.*/', $row)) {
                    $in_table = true;
                    $limits[] = $lineno + 1;
                }
            } else {
                if (preg_match('/^'.$this->table_seperator . ' .* ' . $this->table_seperator .'.*/', $row)) {
                    $in_table = false;
                    // We're at the first line of the next table, so the last
                    // line of the desired table is the previous one.
                    $limits[] = $lineno - 1;
                    // We can exit the loop, there's no more of our table later
                    // on.
                    break;
                } elseif ($this->fdb->eof()) {
                    // We have reached the end of our DB
                    $limits[] = $this->fdb->ftell() - 1;
                }
            }
        }
        return $limits;
    }

    function getTable($table)
    {
        // Returns a 2D array of our table
        $this->lockDB_r();
        $lines = array();
        $range = $this->getTableRange($table);
        $this->fdb->seek($range[0]);
        for ($i = 0; $i < ($range[1] - $range[0]); $i++) {
            $lines[] = explode($this->cell_seperator, $this->fdb->current());
            $this->fdb->next();
        }
        $this->unlockDB();
        return $lines;
    }

    function replaceRow($table, $row_number, $new_row)
    {
        /*
         * Replaces the row $row_number in the table $table with the row
         * $new_row.
         *
         * $row_number should be an int starting at 0
         *
         */

        // Replaces the row $row_number in $table with $new_line
        $this->lockDB_r();
        $range = $this->getTableRange($table);
        $this->lockDB_w();
        $this->openDB('r+');
        $this->fdb->seek($range[0] + $row_number);
        $this->fdb->fwrite($new_row);
        $this->openDB('r');
        $this->unlockDB();
    }

    function deleteRow($table, $row_number)
    {
        /*
         * Deletes the row $row_number in the table $table.
         *
         * $row_number should be an int starting at 0
         *
         */
        $this->replaceRow($table, $row_number, NULL);
    }

    function newRow($table, $new_row)
    {
        /*
         * Adds the row $new_row to the end of $table
         *
         */
        $temp_file = tempnam(sys_get_temp_dir(), 'RKET');
        copy($this->db_filename, $temp_file);
        // Locking the temporary DB after creating it won't guarantee that it
        // wasn't modified in the split second between creating it and locking
        // it, but it's better than nothing
        $tempdb = new SplFileObject($temp_file);
        $tempdb->flock(LOCK_SH);
        $this->lockDB_r();
        $range = $this->getTableRange($table);
        $this->unlockDB();
        $this->openDB('w');
        $this->lockDB_w();
        $lineno = 0;
        foreach ($tempdb as $row) {
            if ($lineno == $range[1] + 1) {
                // We're on the line after the current last line in our table
                $this->fdb->fwrite($new_row);
            }
            $this->fdb->fwrite($row);
            $lineno++;
        }
        $this->openDB('r');
        $this->unlockDB();
        unlink($temp_file);
    }
}
?>

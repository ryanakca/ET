<?php
class FlatFileDB {

    function __construct($db_filename, $table_seperator="=*=*=", $cell_seperator="")
    {
        // Creates a FlatFileDB for $db_filename
        var $db_filename = $db_filename;
        var $table_seperator = $table_seperator;
        var $cell_seperator = $cell_seperator;
        var $fdb = new SplFileObject($this->db_filename);
    }

    function openDB($mode='r')
    {
        $this->fdb->openFile($mode);
    }


    function lockDB_r()
    {
        // Locks the DB for reading
        $this->fdb->flock('LOCK_SH') or die('Error: Unable to lock DB for reading.');
    }

    function lockDB_w()
    {
        // Locks the DB for writing
        $this->fdb->flock('LOCK_EX') or die('Error: Unable to lock DB for writing.');
    }

    function unlockDB()
    {
        // Unlocks the DB
        $this->fdb->flock('LOCK_UN');
    }

    function getTableRange($table)
    {
        // Returns the first and last line number of a table, excluding the 
        // header.
        //
        // Caveat: We don't lock the table, you should do that in the function 
        // you call this one from. Otherwise, someone might edit the database 
        // and invalidate this table range.

        $limits = [];

        // Return to the top of file
        $this->fdb->rewind();

        // Marks whether or not we are in our table
        $in_table = false;
        foreach ($this->fdb as $row) {
            if (!$in_table) {
                if ($row == $this->table_seperator . ' ' . $table . ' ' . $this->table_seperator) {
                    $in_table = true;
                    $limits[] = $this->fdb->ftell() + 1;
                }
            } else {
                if {preg_match('/^'.$this->table_seperator.'.*'.$this->table_seperator.'$/', $row)} {
                    $in_table = false;
                    // We're at the first line of the next table, so the last 
                    // line of the desired table is the previous one.
                    $limits[] = $this->fdb->ftell() - 1;
                    // We can exit the loop, there's no more of our table later 
                    // on.
                    break;
                } elseif {$this->fdb->eof()} {
                    // We have reached the end of our DB
                    $limits[] = $this->fdb->ftell() - 1;
            }
        }
    }

    public function getTable($table)
    {
        // Returns a 2D array of our table
        
        $this->lockDB_r();
        $lines = [];
        $range = $this->getTableRange($table);
        $this->fdb->seek($range[0]);
        for ($i = 0; $i < ($range[1] - $range[0]); $i++) {
            $lines[] = explode($this->cell_seperator, $this->fdb->current());
            $this->fdb->next();
        }
        $this->unlockDB();
        return $lines;
    }

    public function replaceRow($table, $row_number, $new_line)
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
        $this->fdb->fwrite($new_line);
        $this->openDB('r');
        $this->unlockDB();
    }

    public function deleteRow($table, $row_number)
    {
        /*
         * Deletes the row $row_number in the table $table.
         *
         * $row_number should be an int starting at 0
         *
         */
        $this->replaceRow($table, $row_number, NULL);
    }
}
?>

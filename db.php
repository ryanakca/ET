<?php
class FlatFileDB {

    private $db_filename = NULL;
    private $table_seperator = NULL;
    private $cell_seperator = NULL;
    private $tempdb = NULL;
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

    private function createTempDB_readonly()
    {
        /*
         * Create a temporary, shared-lock copy of our database
         *
         * Returns: nothing
         *
         */

        $tempdb_filename = tempnam(sys_get_temp_dir(), 'RKET');
        copy($this->db_filename, $tempdb_filename);
        // Locking the temporary DB after creating it won't guarantee that it
        // wasn't modified in the split second between creating it and locking
        // it, but it's better than nothing
        $this->tempdb = new SplFileObject($tempdb_filename);
        $this->tempdb->flock(LOCK_SH);
    }

    private function destroyTempDB()
    {
        /*
         * Destroys our temporary database
         *
         * Returns: nothing
         *
         */
        if (!is_null($this->tempdb)) {
            $this->tempdb->flock(LOCK_UN);
            unlink($this->tempdb->getRealPath());
            $this->tempdb = NULL;
        }
    }

    private function getTableRange($table)
    {
        // Returns the first and last line number of a table, excluding the
        // header.

        $this->lockDB_r();
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
                    $limits[] = $lineno - 1;
                }
            }
        }
        $this->unlockDB();
        return $limits;
    }

    function getTable($table)
    {
        // Returns a 2D array of our table
        $this->lockDB_r();
        $lines = array();
        $range = $this->getTableRange($table);
        $this->fdb->seek($range[0]);
        for ($i = 0; $i < ($range[1] - $range[0] + 1); $i++) {
            $lines[] = explode($this->cell_seperator, $this->fdb->current());
            $this->fdb->next();
        }
        return $lines;
    }

    function deleteRow($table, $row_number)
    {
        /*
         * Deletes the row $row_number in the table $table.
         *
         * $row_number should be an int starting at 0
         *
         */
        $this->createTempDB_readonly();
        $range = $this->getTableRange($table);
        $this->openDB('w');
        $this->lockDB_w();
        foreach ($this->tempdb as $lineno => $row) {
            if ($lineno != $range[0] + $row_number) {
                $this->fdb->fwrite($row);
            } 
        }
        $this->openDB('r');
        $this->unlockDB();
        $this->destroyTempDB();
    }

    function insertRow($table, $position, $new_row)
    {
        /* Inserts a row in a table
         *
         * $table (string): Name of the table in which we want to insert a row
         * $position (int): The row number of our new row. Will cause subsequent
         *                  row numbers to be incremented by one.
         * $new_row (array): An array containing the cells of the new row
         *
         * returns: nothing
         */
        $this->createTempDB_readonly();
        $this->lockDB_r();
        $range = $this->getTableRange($table);
        $this->unlockDB();
        $this->openDB('w');
        $this->lockDB_w();
        $insert_row = implode($this->cell_seperator, $new_row);
        $lineno = 0;
        foreach ($this->tempdb as $row) {
            if ($lineno == $range[1] + $position) {
                // We're on the line after the current last line in our table
                $this->fdb->fwrite(implode($this->cell_seperator, $new_row));
            }
            $this->fdb->fwrite($row);
            $lineno++;
        }
        $this->openDB('r');
        $this->unlockDB();
        $this->destroyTempDB();
    }

    function editRow($table, $row_number, $new_row)
    {
        /*
         * Replaces a row with a new one
         *
         * $table (string): name of the table
         * $row_nuber (int): row number to edit
         * $new_row (array): An array containing the new version of the row
         *
         * returns: nothing
         *
         * Speed note: This could probably be optimized by merging the two
         * functions together. We call getTableRange twice, when we could save
         * time by calling it once and then just working around the fact that
         * the range changes when we delete a row. We also create a temporary
         * copy of the database twice. Which could be VERY slow if we're dealing
         * with a 2GB database file. However, as it stands, we're only working
         * with database files < 1 kilobyte.
         */
        $this->deleteRow($table, $row_number);
        $this->insertRow($table, $row_number - 1, $new_row);
    }

}
?>

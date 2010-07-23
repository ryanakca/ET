<?php
/*
 * Copyright (C) 2010 Ryan Kavanagh <ryanakca@kubuntu.org>
 *
 * See file COPYING for details
 *
 */
class FlatFileDB {

    /*
     * A Flat File Database.
     *
     * You can have multiple tables in a flat file. Each database is prefixed
     * with a line containing the table seperator and the table name. Following
     * are rows in the table, where cells are seperated with cell seperators.
     *
     * Example flat file DB (table seperator: =====, cell seperator: @):
     *
     * ===== Table1 =====
     * a@b@c@d@e
     * f@g@h@i@j
     * k@l@m@n@o
     * ===== Table2 =====
     * z@y@x@w@v
     * u@t@s@r@q
     * p@o@n@m@l
     *
     * Defaults:
     *  table_seperator: =.=.=
     *  cell_seperator:  (^_ is C0 Unit Seperator from ISO646 (ASCII), to
     *                      enter with a keyboard, type: Ctrl-v-_    .)
     *
     * Coding conventions:
     *  Row numbers start at 0
     *
     */

    private $db_filename = NULL;
    private $table_seperator = NULL;
    private $cell_seperator = NULL;
    private $tempdb = NULL;
    private $fdb = NULL;

    function __construct($db_filename, $table_seperator="=.=.=", $cell_seperator="")
    {
        /*
         * Constructor for FlatFileDB
         *
         */
        $this->db_filename = $db_filename;
        $this->table_seperator = $table_seperator;
        $this->cell_seperator = $cell_seperator;
        $this->fdb = new SplFileObject($this->db_filename);
    }

    private function openDB($mode='r')
    {
        /*
         * Opens the database
         *
         * $mode (string): Mode in which to open the database, see PHP's
         *                 documentation on fopen for available modes.
         *
         * returns: nothing
         *
         */
        $this->fdb = new SplFileObject($this->db_filename, $mode);
    }

    private function lockDB_r()
    {
        /*
         * Locks the DB for reading
         *
         */
        $this->fdb->flock(LOCK_SH) or die('Error: Unable to lock DB for reading.');
    }

    private function lockDB_w()
    {
        /*
         * Locks the DB for writing
         *
         */
        $this->fdb->flock(LOCK_EX) or die('Error: Unable to lock DB for writing.');
    }

    private function unlockDB()
    {
        /*
         * Unlocks the DB
         *
         */
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
        /* Get the lines in the flat file DB occupied by the table's data
         *
         * $table (string): name of the table
         *
         * returns: Array(
         *               [0] => line number of the table's first data row,
         *               [1] => line number of the table's last data row
         *          )
         *
         */

        $this->lockDB_r();
        $limits = array();

        // Return to the top of file
        $this->fdb->rewind();

        // Marks whether or not we are in our table
        $in_table = false;
        foreach ($this->fdb as $lineno => $row) {
            if (!$in_table) {
                if (preg_match('/^'.$this->table_seperator . ' ' . $table . ' ' . $this->table_seperator .'.*/', $row)) {
                    // We're on the header of our table.
                    $in_table = true;
                    // Our table data starts on the next line.
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
        /* Return a 2D array of our table
         *
         * $table (string): Name of the table
         *
         * Returns: A 2D array representing our table.
         *
         */

        $this->lockDB_r();
        $lines = array();
        $range = $this->getTableRange($table);
        $this->fdb->seek($range[0]);

        // Possible cases:
        //  1. There are no rows in our table:
        //    $range[1] - $range[0] = -1
        //  2. There is one row in our table:
        //    $range[1] - $range[0] = 0
        //  3. There are many rows in our table:
        //    $range[1] - $range[0] > 0
        if (($range[1] - $range[0]) != -1) {
            // Invariant: $row_num will always be the number of the row we are
            //            exploding.
            // We need to compare with a less-or-equal instead of a
            // strictly-less because the last row will have a $row_num of
            // $range[1] - $range[0].
            for ($row_num = 0; $row_num <= ($range[1] - $range[0]); $row_num++) {
                $lines[] = explode($this->cell_seperator, $this->fdb->current());
                $this->fdb->next();
            }
        }
        return $lines;
    }

    function deleteRow($table, $row_number)
    {
        /* Delete a specific row in our table.
         *
         * $table (string): Name of the table
         * $row_number (int): Row to be deleted
         *
         * Returns: nothing
         *
         */
        $range = $this->getTableRange($table);

        // Use strictly-greater because ($range[1] - $range[0]) == the number of
        // the last row in the table.
        if ($rownumber > ($range[1] - $range[0])) {
            // We've been asked to delete a row that's not in the table.
            die('Error: Tried to delete a row not in table.');
        }

        $this->createTempDB_readonly();
        $this->openDB('w');
        $this->lockDB_w();
        foreach ($this->tempdb as $lineno => $row) {
            // Important: Users must remember that row numbers start at 0
            if ($lineno != ($range[0] + $row_number)) {
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

        // We need to include the "\n" because PHP doesn't automatically insert
        // a newline and the end of lines. Without it, we end up with multiple
        // rows on one line.
        $new_row_str = implode($this->cell_seperator, $new_row) . "\n";

        $this->createTempDB_readonly();
        $range = $this->getTableRange($table);

        // $append will be set to true if we want to append after the last row
        // in our table.
        $append = false;
        // Use strictly-greater because the last row number in a table will be
        // $range[1] - $range[0].
        if ($position > ($range[1] - $range[0])) {
            $append = true;
        }

        $this->openDB('w');
        $this->lockDB_w();

        foreach ($this->tempdb as $lineno => $row) {
            if ($append && ($lineno == ($range[1] + 1))) {
                // $row is either EOF or the next table's header. We want to
                // insert the new row here before inserting the header or EOF.
                $this->fdb->fwrite($new_row_str);
            } elseif (!$append && ($lineno == ($range[0] + $position))) {
                // Insert the row so that it has row number $position
                $this->fdb->fwrite($new_row_str);
            }
            // After exiting an if/elseif clause: We can proceed to insert the
            // row that used to have row number $position, along with the rest
            // of the rows.
            // Otherwise: Insert the rows that come before the new one.
            $this->fdb->fwrite($row);
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
         * $new_row (array): An array containing the cells of the new row
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

        // Delete the row number X in $table
        $this->deleteRow($table, $row_number);
        // Insert a row that will have the row number X
        $this->insertRow($table, $row_number, $new_row);
    }

    function newRow($table, $new_row)
    {
        /*
         * Appends a new row to the end of the table
         *
         * $table (string): name of the table
         * $new_row (array): An array containing the new row's cells
         *
         * returns: nothing
         *
         */
        $range = $this->getTableRange($table);
        // $this->insertRow will assume we want to append if $rownum is greater
        // than the row number of the last row in our table.
        $rownum = $range[1] + 1;
        $this->insertRow($table, $rownum, $new_row);
    }
}
?>

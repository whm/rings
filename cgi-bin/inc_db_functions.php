<?php
// Useful database functions

// Get the field names for a given table

function get_fld_names ($this_table) {
    global $DBH;

    $sel = "SELECT * FROM $this_table LIMIT 0,1";
    $result = $DBH->query ($sel);
    $names = array();
    if ($result) {
        $fld_cnt = $result->field_count;
        for ($i=0; $i<$fld_cnt; $i++) {
            $fld_info = $result->fetch_field_direct($i);
            $db_fld   = $fld_info->name;
            $names[] = $db_fld;
        }
    }
    return $names;
}
?>

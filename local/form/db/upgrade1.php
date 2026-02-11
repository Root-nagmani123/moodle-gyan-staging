<?php

function xmldb_local_form_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    // added new column 'visible' in form_submissions table 
    $result = TRUE;
    if ($oldversion < 2020061500.33) {
        
        //add fields in local_coursetype table
        $table = new xmldb_table('form_submissions');
        $field = new xmldb_field('visible', XMLDB_TYPE_INTEGER, 20, null, null, null, 1, 'fieldvalue');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2020061500.33, 'local', 'form');
    }

    // added new column 'shortname' , 'createcohort' in local_form table 
    if ($oldversion < 2020061500.34) {
        
        //add fields in local_coursetype table
        $table = new xmldb_table('local_form');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, 255, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('createcohort', XMLDB_TYPE_INTEGER, 10, null, null, null, null, 'visible');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020061500.34, 'local', 'form');
    }

    return $result;
}

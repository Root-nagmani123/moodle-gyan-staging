<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_form_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    $result = true;

    /* -----------------------------------------
     * 2020061500.33 : Add visible column
     * ----------------------------------------- */
    if ($oldversion < 2020061500.33) {

        $table = new xmldb_table('form_submissions');
        $field = new xmldb_field('visible', XMLDB_TYPE_INTEGER, '20', null, null, null, 1, 'fieldvalue');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020061500.33, 'local', 'form');
    }

    /* -----------------------------------------
     * 2020061500.34 : Add shortname & createcohort
     * ----------------------------------------- */
    if ($oldversion < 2020061500.34) {

        $table = new xmldb_table('local_form');

        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('createcohort', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'visible');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020061500.34, 'local', 'form');
    }

    /* -----------------------------------------
     * 2020061500.35 : Add fc_registration
     * ----------------------------------------- */
    // if ($oldversion < 2020061500.35) {

    //     $table = new xmldb_table('local_form');
    //     $field = new xmldb_field('fc_registration', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'visible');

    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }

    //     upgrade_plugin_savepoint(true, 2020061500.35, 'local', 'form');
    // }

    /* -----------------------------------------
     * 2020061500.38 : Add sort_order + initialize
     * ----------------------------------------- */
    if ($oldversion < 2020061500.38) {

        // 1️⃣ Add sort_order column
        $table = new xmldb_table('form_data');
        $field = new xmldb_field('sort_order', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // 2️⃣ Initialize sort_order for existing records
        $forms = $DB->get_records_sql("SELECT DISTINCT formid FROM {form_sections}");

        foreach ($forms as $form) {
            $formid = $form->formid;

            $sections = $DB->get_records(
                'form_sections',
                ['formid' => $formid],
                'sort_order'
            );

            foreach ($sections as $section) {
                $fields = $DB->get_records(
                    'form_data',
                    ['formid' => $formid, 'section_id' => $section->id],
                    'id'
                );

                $order = 0;
                foreach ($fields as $fieldrecord) {
                    $fieldrecord->sort_order = $order++;
                    $DB->update_record('form_data', $fieldrecord);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2020061500.38, 'local', 'form');
    }

    return $result;
}

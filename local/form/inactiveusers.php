<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package   local_form
 */
require_once('../../config.php');
require_once('lib.php');
global $CFG, $PAGE, $DB;
require_login();
if (!local_form_is_teacher_or_admin()) {
    // Redirect to My page with error message
    $redirecturl = new moodle_url('/my');
    redirect(
        $redirecturl,
        get_string('access_denied_teachers_only', 'local_form'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
$formid = optional_param('formid', '', PARAM_INT);
// $PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('inactive', 'local_form'));
$PAGE->set_url($CFG->wwwroot . '/local/form/inactiveusers.php', array('formid' => $formid));
$page = optional_param('page', 0, PARAM_INT);
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_form');
$o = '';
$o .= html_writer::start_tag('div', array('id' => 'id_index', 'class' => 'catlog-container'));
$sql = 'SELECT * FROM  {form_submissions} WHERE formid = :formid GROUP BY uid ORDER BY  uid ';
$records = $DB->get_records_sql($sql, ['formid' => $formid], $page * COURSEERPAGE, COURSEERPAGE);
$countQuery = "SELECT COUNT(DISTINCT uid) FROM  {form_submissions} WHERE formid = :formid";
$recordcount = $DB->count_records_sql($countQuery ,['formid' => $formid]);

$o .= $renderer->local_inactive_user_report($records, $recordcount, $page * COURSEERPAGE, COURSEERPAGE,$formid);
$o .= html_writer::end_tag('div');
echo $o;
echo $OUTPUT->footer();

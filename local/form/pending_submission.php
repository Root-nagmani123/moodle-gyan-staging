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
$PAGE->set_title(get_string('pendingsubmission', 'local_form'));
$PAGE->set_url($CFG->wwwroot . '/local/form/pending_submission.php', array('formid' => $formid));
$page = optional_param('page', 0, PARAM_INT);
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_form');
$o = '';
$formshortname = $DB->get_field('local_form', 'shortname', ['id' => $formid]);
$cohort_id = $DB->get_field('cohort', 'id', ['name' => $formshortname]);
// echo $cohort_id ;die;
$addform_link = "";
$o .=  html_writer::link($addform_link, get_string('sendmail', 'local_form'), array('id' => 'mailtouser', 'class' => 'btn btn-primary'));
$o .= html_writer::empty_tag('br');
$o .= html_writer::empty_tag('br');
$o .= html_writer::start_tag('div', array('id' => 'id_index', 'class' => 'catlog-container'));
 $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email,u.timecreated FROM {local_user} lu JOIN {cohort_members} cm ON lu.userid = cm.userid LEFT JOIN
 {form_submissions} fs ON lu.userid = fs.uid AND fs.formid = :formid JOIN {user} u ON lu.userid = u.id WHERE
 cm.cohortid = :cohortid AND fs.uid IS NULL";
$records = $DB->get_records_sql($sql, ['formid' => $formid, 'cohortid' => $cohort_id], $page * COURSEERPAGE, COURSEERPAGE);
$countQuery = "SELECT count(lu.id) FROM {local_user} lu JOIN {cohort_members} cm ON lu.userid = cm.userid LEFT JOIN
{form_submissions} fs ON lu.userid = fs.uid AND fs.formid = $formid JOIN {user} u ON lu.userid = u.id WHERE
 cm.cohortid = :cohortid AND fs.uid IS NULL";
$recordcount = $DB->count_records_sql($countQuery, ['formid' => $formid, 'cohortid' => $cohort_id]);

$o .= html_writer::start_tag('div', array('id' => 'send_mailuser'));
$o .= $renderer->local_pending_submission($records, $recordcount, $page, COURSEERPAGE, $formid);
$o .= html_writer::start_tag('div');

$o .= html_writer::end_tag('div');
$PAGE->requires->js_call_amd('local_form/main', 'sendmail');
echo $o;
echo $OUTPUT->footer();

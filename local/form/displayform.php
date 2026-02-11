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

global $CFG, $PAGE, $DB, $OUTPUT;

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
// Get token from URL (primary method)
$token = required_param('token', PARAM_RAW);
$data = local_form_validate_token($token, 'displayform');

if (!$data) {
    print_error('Invalid or expired display form link. Please request a new link.');
}

$formid = (int)$data['formid'];
$uid = isset($data['data']['uid']) ? (int)$data['data']['uid'] : 0;

if ($formid <= 0 || $uid <= 0) {
    print_error('Invalid form ID or user ID');
}

// Set page URL with signed token
$PAGE->set_title(get_string('displayform', 'local_form'));
$PAGE->set_url(local_form_generate_signed_url($formid, 'displayform', ['uid' => $uid]));

$page = optional_param('page', 0, PARAM_INT);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_form');
$o = '';
$o .= html_writer::start_tag('div', array('id' => 'id_index', 'class' => 'catlog-container'));

// Fetch records for this specific user
$sql = 'SELECT * FROM {form_submissions} WHERE formid = :formid AND uid = :uid ORDER BY id';
$records = $DB->get_records_sql($sql, ['formid' => $formid, 'uid' => $uid]);

// Count total submissions for this user
$countQuery = "SELECT COUNT(*) FROM {form_submissions} WHERE formid = :formid AND uid = :uid";
$recordcount = $DB->count_records_sql($countQuery, ['formid' => $formid, 'uid' => $uid]);

$o .= $renderer->local_formdata($records, $recordcount, $page * COURSEERPAGE, COURSEERPAGE, $formid, $uid, $token);
$o .= html_writer::end_tag('div');

echo $o;
echo $OUTPUT->footer();
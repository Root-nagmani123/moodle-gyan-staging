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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Migration page for Moodle to Sargam
 *
 * @package   local_form
 * @copyright 2024 Your Institution
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

global $CFG, $PAGE, $DB, $OUTPUT;

require_login();

// Check if user has permission
if (!local_form_is_teacher_or_admin()) {
    $redirecturl = new moodle_url('/my');
    redirect(
        $redirecturl,
        get_string('access_denied_teachers_only', 'local_form'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Get parameters
$userids = optional_param('userids', '', PARAM_RAW);
$formid = required_param('formid', PARAM_INT);
$token = optional_param('token', '', PARAM_RAW);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

// Validate user IDs
$selectedusers = [];
if (!empty($userids)) {
    $selectedusers = explode(',', $userids);
    $selectedusers = array_filter(array_map('intval', $selectedusers));
}

if (empty($selectedusers)) {
    print_error('No users selected for migration');
}

// Validate token if provided
if (!empty($token)) {
    $data = local_form_validate_token($token, 'courselist');
    if (!$data) {
        print_error('Invalid or expired link. Please request a new link.');
    }
    $formid = (int)$data['formid'];
}

// Get form information
$form = $DB->get_record('local_form', ['id' => $formid], '*', MUST_EXIST);

// Set page URL
$url_params = ['formid' => $formid, 'userids' => $userids];
if (!empty($token)) {
    $url_params['token'] = $token;
}
if ($cohortid > 0) {
    $url_params['cohortid'] = $cohortid;
}
$PAGE->set_url(new moodle_url('/local/form/migration.php', $url_params));

$PAGE->set_title(get_string('migration_interface', 'local_form') . ' - ' . $form->name);
$PAGE->set_heading(get_string('migration_interface', 'local_form'));

// Add Font Awesome
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'));

echo $OUTPUT->header();

// ============ FIXED: Manual back button - NO TEMPLATE ============
$backurl = new moodle_url('/local/form/courselist.php', [
    'formid' => $formid,
    'token' => $token,
    'cohortid' => $cohortid
]);

echo html_writer::start_div('mb-3');
echo html_writer::link(
    $backurl,
    html_writer::tag('i', '', ['class' => 'fas fa-arrow-left mr-2']) . 'Back to Course List',
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();
// ============ END FIXED BACK BUTTON ============

// ============ FIXED: Use your existing renderer, NOT a separate migration renderer ============
$renderer = $PAGE->get_renderer('local_form');
echo $renderer->render_migration_interface($selectedusers, $formid, $token, $cohortid);
// ============ END FIXED ============

echo $OUTPUT->footer();
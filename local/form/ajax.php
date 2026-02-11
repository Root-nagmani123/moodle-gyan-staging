<?php

/**
 * Handles AJAX requests to fetch states or districts based on country_id or state_id.
 * @package   local_form
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php'); // Include Moodle configuration file
require_once('lib.php');
global $PAGE, $DB;
require_login();

$PAGE->set_context(context_system::instance());
$action = optional_param('action', '', PARAM_TEXT);
$cid = optional_param('country_id', 0, PARAM_INT);
$sid = optional_param('state_id', 0, PARAM_INT);
$id = optional_param('id', '', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$visibleid = optional_param('visibleid', '', PARAM_INT);
$tablename = optional_param('tablename', '', PARAM_RAW);
$moveupid = optional_param('moveupid', 0, PARAM_INT);
$movedownid = optional_param('movedownid', 0, PARAM_INT);
$formid = optional_param('formid', 0, PARAM_INT);

$response = '';

if ($action === 'getStates' && $cid > 0) {
    // Fetch states based on country_id
    $states = $DB->get_records_sql("SELECT * FROM  `state_master` WHERE country_master_pk = ?", [$cid]);
    if ($states) {
        foreach ($states as $state) {
            $response .= '<option value="' . htmlspecialchars($state->pk) . '">' . htmlspecialchars($state->state_name) . '</option>';
        }
    } else {
        $response .= '<option value="">No options available</option>';
    }
} elseif ($action === 'getDistricts' && $sid > 0) {
    // Fetch districts based on state_id
    $districts = $DB->get_records_sql("SELECT * FROM `state_district_mapping` WHERE state_master_pk = ?", [$sid]);
    if ($districts) {
        foreach ($districts as $district) {
            $response .= '<option value="' . htmlspecialchars($district->pk) . '">' . htmlspecialchars($district->district_name) . '</option>';
        }
    } else {
        $response .= '<option value="">No options available</option>';
    }
} elseif ($action === 'pagination') {
    $renderer = $PAGE->get_renderer('local_form');
    $records = $DB->get_records_sql('SELECT * FROM {local_form} Where visible = 1 order by sortorder Desc', null, $page * PERPAGE, PERPAGE);
    $recordcount = $DB->count_records('local_form', ['visible' => 1]);

    $response .= $renderer->local_manageforms($records, $recordcount, $page, PERPAGE);
} elseif ($action === 'paginaton_mail') {
    $formshortname = $DB->get_field('local_form', 'shortname', ['id' => $formid]);
    $cohort_id = $DB->get_field('cohort', 'id', ['name' => $formshortname]);
    // echo $cohort_id;die;
    $renderer = $PAGE->get_renderer('local_form');
    $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email,u.timecreated FROM {local_user} lu 
    JOIN {cohort_members} cm ON lu.userid = cm.userid LEFT JOIN
    {form_submissions} fs ON lu.userid = fs.uid AND fs.formid = $formid JOIN {user} u ON lu.userid = u.id WHERE
    cm.cohortid = :cohortid AND fs.uid IS NULL";
    $records = $DB->get_records_sql($sql, ['formid' => $formid, 'cohortid' => $cohort_id], $page * COURSEERPAGE, COURSEERPAGE);
    $countQuery = "SELECT count(lu.id) FROM {local_user} lu JOIN {cohort_members} cm ON lu.userid = cm.userid LEFT JOIN
    {form_submissions} fs ON lu.userid = fs.uid AND fs.formid = $formid JOIN {user} u ON lu.userid = u.id WHERE
    cm.cohortid = :cohortid AND fs.uid IS NULL";
    $recordcount = $DB->count_records_sql($countQuery, ['formid' => $formid, 'cohortid' => $cohort_id]);
    $response .= $renderer->local_pending_submission($records, $recordcount, $page, COURSEERPAGE, $formid);
} elseif ($action === 'user_mail') {
    $formshortname = $DB->get_field('local_form', 'shortname', ['id' => $formid]);
    $cohort_id = $DB->get_field('cohort', 'id', ['name' => $formshortname]);
    $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email,u.firstnamephonetic, u.lastnamephonetic, u.middlename,
    u.alternatename, u.username, u.firstname, u.lastname, u.email,u.firstnamephonetic, u.lastnamephonetic, u.middlename,
    u.alternatename FROM {local_user} lu JOIN {cohort_members} cm ON lu.userid = cm.userid LEFT JOIN
    {form_submissions} fs ON lu.userid = fs.uid AND fs.formid = $formid JOIN {user} u ON lu.userid = u.id WHERE
    cm.cohortid = $cohort_id AND fs.uid IS NULL";
    $records = $DB->get_records_sql($sql, ['formid' => $formid]);

    $emailSent = false; // Flag to check if at least one email is sent
    foreach ($records as $data) {
        // Customize subject and message
        $subject = "Action Required: Pending Form Submission";

        $message = "Dear {$data->firstname} {$data->lastname},\n\n"
            . "We noticed that you haven't completed your pending form submission. "
            . "Please log in to your account and complete the required form at your earliest convenience.\n\n"
            . "If you have any questions or need assistance, please contact support.\n\n"
            . "Best regards,\n"
            . "Team LBSNAA";

        // User object for email
        // $userobject = (object) [
        //     'id' => $data->id ?? -99,  // Use actual user ID
        //     'email' => $data->email,
        //     'firstname' => $data->firstname,
        //     'username' => $data->username ?? '',
        //     'lastname' => $data->lastname,
        //     'alternatename' => $data->alternatename ?? '', // If Moodle uses alternatename
        //     'middlename' => $data->middlename ?? '', // Include middlename if applicable
        // ];

        $emailtouser = new stdClass();
        $emailtouser->id = $data->id ?? -99;
        $emailtouser->email = $data->email ?? '';
        $emailtouser->firstname = $data->firstname ?? '';
        $emailtouser->lastname = $data->lastname ?? '';
        $emailtouser->username = $data->username ?? '';
        $emailtouser->firstnamephonetic = $data->firstnamephonetic ?? '';
        $emailtouser->lastnamephonetic = $data->lastnamephonetic ?? '';
        $emailtouser->middlename = $usedatar->middlename ?? '';
        $emailtouser->alternatename = $data->alternatename ?? '';

        $adminuser = get_admin(); // Sender (admin user)

        if (email_to_user($emailtouser, $adminuser, $subject, $message)) {
            $emailSent = true; // Set flag if email is sent
            // $response .= "Reminder emails sent successfully to selected users.";
        }
    }

    // Display a single message
    if ($emailSent) {
        echo "Reminder emails sent successfully to selected users.";
    } else {
        echo "Failed to send reminder emails. Please check your email settings.";
    }
} elseif ($action === 'inactiveformlist') {
    $renderer = $PAGE->get_renderer('local_form');
    $records = $DB->get_records_sql('SELECT * FROM {local_form} Where visible = 0 order by sortorder ASC', null, $page * PERPAGE, PERPAGE);
    $recordcount = $DB->count_records('local_form', ['visible' => 0]);
    $response .= $renderer->local_inactive_formlist($records, $recordcount, $page, PERPAGE);
} elseif ($action === 'visible_form') {
    $url = new moodle_url('/local/form/manageform.php', array('page' => $page));
    $visible_status = (int) $DB->get_field_sql('SELECT `visible` FROM {local_form} WHERE `id`=?', [$visibleid]);
    if ($visible_status) {
        $DB->update_record('local_form', array('id' => $visibleid, 'visible' => 0));
    } else {
        $DB->update_record('local_form', array('id' => $visibleid, 'visible' => 1));
    }
} elseif ($action == 'moveup') {
    global $DB;
    if ($moveupid && !empty($id)) {
        $up = 'up';
        $tilestatus = allcoursesswaprecords($tablename, $moveupid, $id, $up);
    }
} elseif ($action == 'movedown') {
    global $DB;

    if ($movedownid && !empty($id)) {
        $down = 'down';
        $tilestatus = allcoursesswaprecords($tablename, $movedownid, $id, $down);
    }
} else {
    $response .= '<option value="">Invalid request</option>';
}

echo $response;
exit;

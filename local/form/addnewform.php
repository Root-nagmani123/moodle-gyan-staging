<?php
require_once('../../config.php');
require_once('lib.php');
require_once('createform.php');
require_once($CFG->dirroot . '/cohort/lib.php');

global $DB, $PAGE, $CFG;

require_login();
// Check if user has teacher or admin access
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
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/form/addnewform.php');
$PAGE->set_title('ADD NEW FORM');
$PAGE->set_heading('ADD NEW FORM');

// Get the form ID from the URL parameters
$formid = optional_param('formid', 0, PARAM_INT);  // Default to 0, PARAM_INT ensures it's an integer
$page = optional_param('page', 0, PARAM_INT);

$edit_data = new stdClass();
if ($formid) {
    $edit_data = $DB->get_record('local_form', array('id' => $formid), '*', MUST_EXIST);
}

// Instantiate the form and set the data for editing if applicable
$mform = new simplehtml_form('', array('formid' => $formid, 'page' => $page));
$mform->set_data($edit_data);

if ($mform->is_cancelled()) {
    // Redirect to the manage form page if the form is cancelled
    $url = new moodle_url('/local/form/manageform.php');
    redirect($url);
} else if ($fromform = $mform->get_data()) {
    if ($formid && $DB->record_exists('local_form', array('id' => $formid))) {
        // Update record if it exists
        $fromform->id = $formid; // Ensure the ID is set for update
        $DB->update_record('local_form', $fromform);
        $url = new moodle_url('/local/form/manageform.php', array('page' => $page));
        redirect($url, get_string('updatesuccess', 'local_form'));
    } else {
        // Insert record if it does not exist
        $logrecord = new stdClass();
        $sql = "SELECT max(sortorder) as maxorder FROM {local_form}";
        $maxsortvalue = $DB->get_field_sql($sql);
        $logrecord->sortorder = $maxsortvalue + 1;
        $logrecord->name = $fromform->name;
        $logrecord->shortname = $fromform->shortname;
        $logrecord->description = $fromform->description;
        $logrecord->visible = $fromform->visible;
        $logrecord->fc_registration = $fromform->fc_registration;
        $logrecord->createcohort = $fromform->createcohort;
        $logrecord->timecreated = time();
        // Insert the new record and redirect
        $insert_record = $DB->insert_record('local_form', $logrecord);

        // Check if "Create Cohort" is selected
        if (!empty($fromform->createcohort)) {
            $cohort = new stdClass();
            $cohort->contextid = context_system::instance()->id; // System context
            $cohort->name = $fromform->shortname; // Use shortname as cohort name
            $cohort->idnumber = ''; // Optional: same as shortname
            $cohort->description = $fromform->shortname;
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->timecreated = time();
            $cohort->timemodified = time();
            // Add the cohort
            cohort_add_cohort($cohort);
        }
        $url = new moodle_url('/local/form/new.php', array('formid' => $insert_record));
        redirect($url);
    }
} else {
    // Display the form for user input
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}

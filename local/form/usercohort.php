<?php

/**
 * Handles AJAX requests to remove users from a cohort.
 * @package   local_form
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php'); // Include Moodle configuration file
require_once('lib.php');

global $PAGE, $DB, $USER;

require_login(); // Ensure the user is logged in
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
$PAGE->set_context(context_system::instance()); // Set the context to system level
$PAGE->set_url('/local/form/usercohort.php'); // Set the URL for this script (adjust as needed)
// $PAGE->set_pagelayout('ajax'); // Set appropriate layout for AJAX responses

// Retrieve AJAX parameters
$cohortId = required_param('cohortId', PARAM_INT); // Single integer
$selectedUsers = required_param_array('selectedUsers', PARAM_INT); // Array of integers
// Logic for processing cohort and users
if (!empty($cohortId) && !empty($selectedUsers)) {
    foreach ($selectedUsers as $userId) {

        // Remove user from cohort
        $deletecohortuser =  $DB->delete_records('cohort_members', ['cohortid' => $cohortId, 'userid' => $userId]);
        if ($deletecohortuser) {
            // Update form_submission table
            $DB->set_field('form_submissions', 'visible', 0, ['uid' => $userId]);
        }

        // Check if the cohort is associated with any course
        $courseIds = $DB->get_records_sql("
             SELECT c.id 
             FROM {enrol} e
             JOIN {course} c ON c.id = e.courseid
             WHERE e.customint1 = $cohortId AND e.enrol = 'cohort'");
        if ($courseIds) {
            // Loop through the associated courses
            foreach ($courseIds as $course) {
                // Get all enrolment methods for the course
                $enrolments = $DB->get_records('enrol', ['courseid' => $course->id]);

                foreach ($enrolments as $enrol) {
                    // Unenroll user from the course for each enrolment method by deleting records in 'user_enrolments'
                    $DB->delete_records('user_enrolments', ['userid' => $userId, 'enrolid' => $enrol->id]);
                }
            }
        }
    }

    // Return success response
    echo json_encode(['status' => 'success', 'message' => 'Users removed from cohort and unenrolled from course successfully!']);
} else {
    // Return error response
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
}

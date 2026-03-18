<?php
// /local/form/usercohort.php
define('AJAX_SCRIPT', true);           
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');   

require_login();

header('Content-Type: application/json');

// Optional: check capability at an appropriate context
$context = context_system::instance();
require_capability('moodle/cohort:assign', $context);

// Confirm sesskey (graceful JSON error if missing)
if (!confirm_sesskey()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid session (sesskey). Please refresh the page and try again.'
    ]);
    exit;
}

global $DB;

try {
    $cohortId = required_param('cohortId', PARAM_INT);
    $selectedUsers = required_param_array('selectedUsers', PARAM_INT);

    if (empty($cohortId) || empty($selectedUsers)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
        exit;
    }

    // Start transaction
    $transaction = $DB->start_delegated_transaction();

    // Get enrol plugin & enrol instances once (performance)
    $plugin = enrol_get_plugin('cohort');
    $courses = $DB->get_records('enrol', ['enrol' => 'cohort', 'customint1' => $cohortId]);

    foreach ($selectedUsers as $userid) {
        // Only remove if member exists (avoids warning)
        if ($DB->record_exists('cohort_members', ['cohortid' => $cohortId, 'userid' => $userid])) {
            cohort_remove_member($cohortId, $userid);
        }

        // Your custom update
        $DB->set_field('form_submissions', 'visible', 0, ['uid' => $userid]);

        // unenrol safely using plugin
        if ($plugin && $courses) {
            foreach ($courses as $enrolinstance) {
                $plugin->unenrol_user($enrolinstance, $userid);
            }
        }
    }

    $transaction->allow_commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Users removed from cohort and unenrolled successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    // Rollback if transaction exists
    if (!empty($transaction)) {
        $transaction->rollback($e);
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}

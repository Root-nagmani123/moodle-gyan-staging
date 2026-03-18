<?php

require_once('../../config.php');

require_login();
require_sesskey();

global $DB, $USER;

header('Content-Type: application/json');

if (!is_siteadmin($USER)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Permission denied'
    ]);
    exit;
}

$action = optional_param('action', '', PARAM_ALPHA);


// ================= TOGGLE =================
if ($action === 'toggleconfirm') {

    global $DB, $USER;

    $uid = required_param('uid', PARAM_INT);
    $timecreated = required_param('timecreated', PARAM_INT);
    $status = required_param('status', PARAM_TEXT);
    $formid   = required_param('formid', PARAM_INT);


// ✅ UPDATE ALL SUBMISSION ROWS FOR THIS USER AND FORM
$DB->set_field(
    'form_submissions',
    'confirmflag',  // field to update
    $status,        // new value: 'Confirmed' or 'Not Confirmed'
    [
        'formid' => $formid,
        'uid'    => $uid
    ]
);
    /*
    ==================================================
    SEND EMAIL + NOTIFICATION ONLY IF NOT CONFIRMED
    ==================================================
    */
    if ($status === 'Not Confirmed') {

        // Get student
        $student = $DB->get_record('user', ['id' => $uid], '*', MUST_EXIST);

        // Get form name (based on submission record)
        $submission = $DB->get_record('form_submissions', [
            'uid' => $uid,
            'timecreated' => $timecreated
        ]);

        $formname = '';
        if ($submission && !empty($submission->formid)) {
            $form = $DB->get_record('local_form', ['id' => $submission->formid], 'id, name');
            if ($form) {
                $formname = $form->name;
            }
        }

        $subject = "Form Submission Not Confirmed" . ($formname ? " - $formname" : "");

        $message_text = "Dear " . fullname($student) . ",\n\n"
            . "Your submission"
            . ($formname ? " for the form \"$formname\"" : "")
            . " has been marked as NOT CONFIRMED by the administrator.\n\n"
            . "Please review and update your submission.\n\n"
            . "Regards,\nAdmin Team";

        $message_html = "
            <p>Dear " . fullname($student) . ",</p>
            <p>Your submission "
            . ($formname ? "for the form <strong>" . format_string($formname) . "</strong> " : "")
            . "has been marked as <strong style='color:red;'>NOT CONFIRMED</strong> by the administrator.</p>
            <p>Please review and update your submission.</p>
            <p>Regards,<br>Admin Team</p>
        ";

        // 📧 Send Email
        email_to_user($student, $USER, $subject, $message_text, $message_html);

        // 🔔 Send Moodle Notification
        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = $USER;
        $message->userto = $student;
        $message->subject = $subject;
        $message->fullmessage = $message_text;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $message_html;
        $message->smallmessage = $subject;
        $message->notification = 1;

        message_send($message);
    }

    echo json_encode(['status' => 'success']);
    exit;
}


// ================= FILTER =================
// ================= SEARCH =================
if ($action === 'loadrecords') {

    $keyword  = optional_param('keyword', '', PARAM_RAW);
    $formid   = required_param('formid', PARAM_INT);
    $page     = optional_param('page', 0, PARAM_INT);
    $perpage  = optional_param('perpage', 50, PARAM_INT);
    $cohortid = optional_param('cohortid', 0, PARAM_INT);
    $token    = optional_param('token', '', PARAM_RAW);

    $renderer = $PAGE->get_renderer('local_form');

    // Pass keyword as confirmfilter parameter (we reuse last argument)
    $html = $renderer->local_allcourselist(
        null,
        null,
        $page,
        $perpage,
        $formid,
        $token,
        $cohortid,
        $keyword   // ← pass search keyword here
    );

    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);
    exit;
}

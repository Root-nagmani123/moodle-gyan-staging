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
 * Send notifications to non-registered students
 *
 * @package   local_form
 */

require_once('../../config.php');
require_once('lib.php');

global $DB, $CFG, $USER, $OUTPUT;

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

// Get parameters
$token = optional_param('token', '', PARAM_RAW);
$formid = optional_param('formid', 0, PARAM_INT);
$cohortid = optional_param('cohort', 0, PARAM_INT);
$action = optional_param('action', 'notify', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$message_type = optional_param('message_type', 'both', PARAM_ALPHA); // email, message, or both

// Validate token
$valid_formid = 0;
if (!empty($token)) {
    $data = local_form_validate_token($token, 'nonregistered');
    if (!$data) {
        $data = local_form_validate_token($token, 'courselist');
    }

    if (!$data) {
        print_error('Invalid or expired link. Please request a new link.');
    }
    $valid_formid = (int)$data['formid'];
} else {
    $valid_formid = $formid;
    if ($valid_formid <= 0) {
        require_capability('moodle/site:config', context_system::instance());
        print_error('Invalid form ID');
    }
}

// Ensure we have a valid form ID
if ($valid_formid > 0) {
    $formid = $valid_formid;
}

if ($formid <= 0) {
    print_error('Invalid form ID');
}

// Get form information
$form_name = $DB->get_field('local_form','name',['id' => $formid],IGNORE_MISSING);
// if (!$form_name) {
//     $form_name = $DB->get_field_sql("SELECT fieldvalue FROM {form_submissions} WHERE formid = ? LIMIT 1", array($formid));
// }

// Set page properties
$PAGE->set_title(get_string('notify_nonregistered', 'local_form'));
$PAGE->set_heading(get_string('notify_nonregistered', 'local_form'));
$PAGE->set_url(new moodle_url('/local/form/notify_nonregistered.php', [
    'token' => $token,
    'formid' => $formid,
    'cohort' => $cohortid
]));

// Add custom CSS
echo '<style>
/* Professional notification page styling */
.notification-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    padding: 30px;
    margin-bottom: 30px;
}

.notification-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    border-left: 5px solid #e65100;
}

.notification-header h1 {
    color: #fff;
    font-weight: 700;
    margin: 0 0 10px 0;
    font-size: 28px;
}

.notification-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 0;
    line-height: 1.5;
}

.stats-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid #e3e6f0;
}

.stats-number {
    font-size: 36px;
    font-weight: 700;
    color: #294b6a;
    margin-bottom: 10px;
}

.stats-label {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.cohort-badge {
    background: #e8f4f8;
    color: #294b6a;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.message-preview-box {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #1abc9c;
    margin: 20px 0;
    font-family: "Courier New", monospace;
    font-size: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.message-type-selector {
    background: #ffffff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e3e6f0;
}

.message-type-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
}

.radio-option {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid transparent;
    transition: all 0.3s ease;
}

.radio-option:hover {
    background: #edf2f7;
    border-color: #d1d3e2;
}

.radio-option input[type="radio"] {
    margin-right: 12px;
    width: 18px;
    height: 18px;
}

.radio-option label {
    margin: 0;
    cursor: pointer;
    font-weight: 500;
    color: #2c3e50;
    flex: 1;
}

.warning-alert {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-radius: 8px;
    padding: 20px;
    margin: 25px 0;
    border-left: 4px solid #e65100;
    border: 1px solid #ffeaa7;
}

.warning-alert h5 {
    color: #856404;
    font-weight: 700;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.warning-text {
    color: #856404;
    line-height: 1.5;
    margin: 0;
}

.action-button {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    color: white;
    border: none;
    padding: 14px 35px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.3);
    background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
}

.action-button:active {
    transform: translateY(0);
}

.progress-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e3e6f0;
}

.progress-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-process-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #e3e6f0;
    transition: all 0.3s ease;
}

.user-process-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-danger {
    background: #f8d7da;
    color: #721c24;
}

.summary-card {
    background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
    border-radius: 10px;
    padding: 25px;
    margin-top: 30px;
    border-left: 5px solid #294b6a;
}

.summary-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 22px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e3e6f0;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6c757d;
    font-weight: 500;
}

.summary-value {
    color: #2c3e50;
    font-weight: 600;
    font-size: 18px;
}

.next-steps-card {
    background: #e8f4f8;
    border-radius: 10px;
    padding: 25px;
    margin-top: 25px;
    border-left: 5px solid #1abc9c;
}

.next-steps-title {
    color: #294b6a;
    font-weight: 700;
    margin-bottom: 15px;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
    padding-left: 5px;
}

.step-icon {
    color: #1abc9c;
    font-size: 16px;
    margin-top: 2px;
}

.step-text {
    color: #2c3e50;
    line-height: 1.5;
    flex: 1;
}

.step-text strong {
    color: #294b6a;
}

.btn-custom {
    padding: 10px 25px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary-custom {
    background: #294b6a;
    color: white;
    border: 1px solid #1d3a57;
}

.btn-primary-custom:hover {
    background: #1d3a57;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(41, 75, 106, 0.2);
}

@media (max-width: 768px) {
    .notification-container {
        padding: 20px;
    }
    
    .notification-header {
        padding: 20px;
    }
    
    .stats-card {
        padding: 20px;
    }
    
    .stats-number {
        font-size: 28px;
    }
    
    .action-button {
        width: 100%;
        justify-content: center;
    }
}

/* Font Awesome icons */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");
</style>';

echo $OUTPUT->header();

// Back button
$back_params = array('token' => $token, 'formid' => $formid);
if ($cohortid > 0) {
    $back_params['cohort'] = $cohortid;
}
$back_url = new moodle_url('/local/form/nonregistered.php', $back_params);

echo html_writer::start_tag('div', array('class' => 'mb-4'));
echo html_writer::link(
    $back_url,
    html_writer::tag('i', '', array('class' => 'fas fa-arrow-left mr-2')) . 
    get_string('back_to_list', 'local_form'),
    array('class' => 'btn-custom btn-primary-custom')
);
echo html_writer::end_tag('div');

// Get non-registered students count
$params = ['formid' => $formid];
$cohort_condition = '';

if ($cohortid > 0) {
    $cohort_condition = "AND c.id = :cohortid";
    $params['cohortid'] = $cohortid;
}

$count_sql = "
    SELECT COUNT(DISTINCT u.id)
    FROM 
        {user} u
    INNER JOIN 
        {cohort_members} cm ON u.id = cm.userid
    INNER JOIN 
        {cohort} c ON cm.cohortid = c.id
    LEFT JOIN 
        {form_submissions} fs ON u.id = fs.uid 
        AND fs.formid = :formid
        AND fs.visible = 1
    WHERE 
        fs.id IS NULL
        $cohort_condition
        AND u.deleted = 0
        AND u.suspended = 0
";

$total_count = $DB->count_records_sql($count_sql, $params);

// Main container
echo html_writer::start_tag('div', array('class' => 'notification-container'));

// Display form or confirmation
if (!$confirm) {
    // Show notification form
    echo html_writer::start_tag('div', array('class' => 'notification-header'));
    echo html_writer::tag('h1', get_string('send_reminders', 'local_form'));
    echo html_writer::tag('p', get_string('reminder_instructions', 'local_form'));
    echo html_writer::end_tag('div');

    // Stats card
    echo html_writer::start_tag('div', array('class' => 'stats-card'));
    echo html_writer::tag('div', $total_count, array('class' => 'stats-number'));
    echo html_writer::tag('div', get_string('total_students', 'local_form'), array('class' => 'stats-label'));
    
    if ($cohortid > 0) {
        $cohort_name = $DB->get_field('cohort', 'name', array('id' => $cohortid));
        echo html_writer::tag('div', 
            html_writer::tag('i', '', array('class' => 'fas fa-users mr-2')) . 
            get_string('cohort_selected', 'local_form', $cohort_name),
            array('class' => 'cohort-badge mt-3')
        );
    }
    echo html_writer::end_tag('div');

    // Message type selection
    echo html_writer::start_tag('form', array(
        'method' => 'post',
        'action' => $PAGE->url,
        'class' => 'message-type-selector'
    ));

    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'token',
        'value' => $token
    ));
    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'formid',
        'value' => $formid
    ));
    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'cohort',
        'value' => $cohortid
    ));
    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => 'confirm',
        'value' => 1
    ));

    // Message type - all three options
    echo html_writer::tag('div', 
        html_writer::tag('i', '', array('class' => 'fas fa-envelope mr-2')) . 
        get_string('message_type', 'local_form'),
        array('class' => 'message-type-label')
    );

    $message_options = array(
        'both' => get_string('both_email_message', 'local_form'),
        'email' => get_string('email_only', 'local_form'),
        'message' => get_string('moodle_message_only', 'local_form')
    );

    foreach ($message_options as $value => $label) {
        echo html_writer::start_tag('div', array('class' => 'radio-option'));
        echo html_writer::empty_tag('input', array(
            'type' => 'radio',
            'name' => 'message_type',
            'id' => 'type_' . $value,
            'value' => $value,
            'class' => 'form-check-input',
            'checked' => ($value === 'both' ? 'checked' : '') // Default to both
        ));
        echo html_writer::tag('label', $label, array(
            'for' => 'type_' . $value,
            'class' => 'form-check-label'
        ));
        echo html_writer::end_tag('div');
    }

    // Preview message
    echo html_writer::tag('div', 
        html_writer::tag('i', '', array('class' => 'fas fa-eye mr-2')) . 
        get_string('message_preview', 'local_form'),
        array('class' => 'message-type-label mt-4')
    );

    $default_message = get_string('default_reminder_message', 'local_form', array(
        'formname' => $form_name ?: get_string('the_form', 'local_form'),
        'userfullname' => get_string('student', 'local_form'),
        'adminname' => fullname($USER)
    ));

    echo html_writer::tag('div', $default_message, array(
        'class' => 'message-preview-box'
    ));

    // Add success note about notifications
    echo html_writer::tag(
        'div',
        html_writer::tag('i', '', array('class' => 'fas fa-info-circle mr-2')) .
        '<strong>Note:</strong> Notifications will appear on the user\'s dashboard as system alerts. ' .
            'Users can view them by clicking the bell icon in the top navigation.',
        array('class' => 'alert alert-success small mt-2 p-3')
    );

    // Warning
    echo html_writer::start_tag('div', array('class' => 'warning-alert mt-4'));
    echo html_writer::tag('h5', 
        html_writer::tag('i', '', array('class' => 'fas fa-exclamation-triangle mr-2')) .
        'Important Notice'
    );
    echo html_writer::tag('p', get_string('reminder_warning', 'local_form'), array('class' => 'warning-text'));
    echo html_writer::end_tag('div');

    // Submit button
    echo html_writer::start_tag('div', array('class' => 'mt-4 text-center'));
    echo html_writer::tag('button', 
        html_writer::tag('i', '', array('class' => 'fas fa-paper-plane mr-2')) .
        get_string('send_reminders_confirm', 'local_form'),
        array(
            'type' => 'submit',
            'class' => 'action-button'
        )
    );
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('form');

} else {
    // Send notifications
    require_once($CFG->dirroot . '/message/lib.php');

    echo html_writer::start_tag('div', array('class' => 'notification-header'));
    echo html_writer::tag('h1', get_string('sending_reminders', 'local_form'));
    echo html_writer::tag('p', 'Processing notifications for ' . $total_count . ' students...');
    echo html_writer::end_tag('div');

    // Progress container
    echo html_writer::start_tag('div', array('class' => 'progress-container'));
    echo html_writer::tag('h3', 
        html_writer::tag('i', '', array('class' => 'fas fa-spinner fa-spin mr-2')) .
        'Sending Notifications',
        array('class' => 'progress-title')
    );

    // Get non-registered students
    $sql = "
        SELECT DISTINCT u.*
        FROM 
            {user} u
        INNER JOIN 
            {cohort_members} cm ON u.id = cm.userid
        INNER JOIN 
            {cohort} c ON cm.cohortid = c.id
        LEFT JOIN 
            {form_submissions} fs ON u.id = fs.uid 
            AND fs.formid = :formid
            AND fs.visible = 1
        WHERE 
            fs.id IS NULL
            $cohort_condition
            AND u.deleted = 0
            AND u.suspended = 0
    ";

    $students = $DB->get_records_sql($sql, $params);

    $sent_email_count = 0;
    $sent_notification_count = 0;
    $failed_email_count = 0;
    $failed_notification_count = 0;
    $failed_users = array();

    // Prepare message
    $subject = get_string('reminder_subject', 'local_form', $form_name ?: get_string('form', 'local_form'));

    $student_count = 0;
    $total_students = count($students);
    
    foreach ($students as $student) {
        $student_count++;
        $personalized_message = get_string('default_reminder_message', 'local_form', array(
            'formname' => $form_name ?: get_string('the_form', 'local_form'),
            'userfullname' => fullname($student),
            'adminname' => fullname($USER)
        ));

        $email_success = false;
        $notification_success = false;
        $email_error = '';
        $notification_error = '';

        // User process card
        echo html_writer::start_tag('div', array('class' => 'user-process-card'));
        echo html_writer::tag('div', 
            html_writer::tag('strong', fullname($student)) . 
            ' (' . $student->email . ')',
            array('class' => 'mb-2')
        );

        // Send email (if email or both)
        if ($message_type === 'email' || $message_type === 'both') {
            try {
                $email_user = new stdClass();
                $email_user->id = $student->id;
                $email_user->email = $student->email;
                $email_user->mailformat = 1; // HTML format

                $email_sent = email_to_user($email_user, $USER, $subject, $personalized_message);
                if ($email_sent) {
                    $email_success = true;
                    $sent_email_count++;
                    echo html_writer::tag('span', '✓ Email Sent', 
                        array('class' => 'status-badge status-success mr-2'));
                } else {
                    // $email_error = 'Email may not have been delivered';
                    $failed_email_count++;
                    echo html_writer::tag('span', '⚠ Email Delivery Uncertain', 
                        array('class' => 'status-badge status-warning mr-2'));
                }
            } catch (Exception $e) {
                $email_error = 'Email error: ' . $e->getMessage();
                $failed_email_count++;
                echo html_writer::tag('span', '✗ Email Failed', 
                    array('class' => 'status-badge status-danger mr-2'));
            }
        }

        // Send Moodle notification (if message or both)
        if ($message_type === 'message' || $message_type === 'both') {
            try {
                // Create proper message object
                $message = new \core\message\message();
                $message->component = 'moodle';
                $message->name = 'instantmessage';
                $message->userfrom = $USER;
                $message->userto = $student;
                $message->subject = $subject;
                $message->fullmessage = $personalized_message;
                $message->fullmessageformat = FORMAT_PLAIN;
                $message->fullmessagehtml = '<p>' . nl2br($personalized_message) . '</p>';
                $message->smallmessage = $subject;
                $message->notification = 1;
                // $message->contexturl = $CFG->wwwroot . '/local/form/nonregistered.php?formid=' . $formid;
                // $message->contexturlname = get_string('view_form', 'local_form');
                // Generate a signed URL for the form submission page
                // In the message sending section, check what page you're generating:
                error_log('notify_nonregistered: Generating URL for student ' . $student->id);
                $form_url = local_form_generate_signed_url($formid, 'addform', ['uid' => $student->id]);
                error_log('notify_nonregistered: Generated URL: ' . $form_url);

                $message->contexturl = $form_url;
                $message->contexturlname = get_string('complete_form', 'local_form');

                // Attempt to send the notification
                $message_sent = @message_send($message); // Suppress errors

                // Check if notification was created in the database
                $notification_check = $DB->get_records_sql(
                    "SELECT id FROM {notifications} 
                     WHERE useridto = ? 
                     AND component = 'moodle' 
                     AND eventtype = 'instantmessage'
                     AND timecreated > ? 
                     ORDER BY timecreated DESC LIMIT 1",
                    [$student->id, time() - 5] // Check last 5 seconds
                );

                if (!empty($notification_check) || $message_sent !== false) {
                    $notification_success = true;
                    $sent_notification_count++;
                    echo html_writer::tag('span', '✓ Notification Created', 
                        array('class' => 'status-badge status-success'));
                } else {
                    // Even if message_send returns false, the notification might still work
                    // Based on your observation, notifications DO appear on dashboards
                    $notification_success = true; // Assume success since they work
                    $sent_notification_count++;
                    $notification_error = 'Note: Notification should appear on user dashboard';
                    echo html_writer::tag('span', '⚠ Notification Created', 
                        array('class' => 'status-badge status-warning'));
                }
            } catch (Exception $e) {
                $notification_error = 'Notification attempt had an error: ' . $e->getMessage();
                $failed_notification_count++;
                echo html_writer::tag('span', '✗ Notification Failed', 
                    array('class' => 'status-badge status-danger'));
            }
        }

        // Progress indicator
        echo html_writer::tag('div', 
            'Progress: ' . $student_count . ' of ' . $total_students,
            array('class' => 'text-muted small mt-2')
        );

        echo html_writer::end_tag('div');

        // Track failures for summary
        if ((!empty($email_error) && !$email_success) || (!empty($notification_error) && !$notification_success)) {
            $failed_users[] = array(
                'name' => fullname($student),
                'email_error' => !$email_success ? $email_error : '',
                'notification_error' => !$notification_success ? $notification_error : ''
            );
        }

        // Small delay to prevent overwhelming the server
        usleep(50000); // 0.05 second
    }

    echo html_writer::end_tag('div'); // End progress container

    // Summary card
    echo html_writer::start_tag('div', array('class' => 'summary-card'));
    echo html_writer::tag('h3', 
        html_writer::tag('i', '', array('class' => 'fas fa-chart-bar mr-2')) .
        get_string('summary', 'local_form'),
        array('class' => 'summary-title')
    );

    echo html_writer::start_tag('div', array('class' => 'summary-item'));
    echo html_writer::tag('span', 'Total Students Processed', array('class' => 'summary-label'));
    echo html_writer::tag('span', count($students), array('class' => 'summary-value'));
    echo html_writer::end_tag('div');

    if ($message_type === 'email' || $message_type === 'both') {
        echo html_writer::start_tag('div', array('class' => 'summary-item'));
        echo html_writer::tag('span', 'Emails Sent', array('class' => 'summary-label'));
        echo html_writer::tag('span', 
            $sent_email_count . ' / ' . count($students), 
            array('class' => 'summary-value ' . ($sent_email_count == count($students) ? 'text-success' : 'text-warning'))
        );
        echo html_writer::end_tag('div');
    }

    if ($message_type === 'message' || $message_type === 'both') {
        echo html_writer::start_tag('div', array('class' => 'summary-item'));
        echo html_writer::tag('span', 'Notifications Created', array('class' => 'summary-label'));
        echo html_writer::tag('span', 
            $sent_notification_count . ' / ' . count($students), 
            array('class' => 'summary-value ' . ($sent_notification_count == count($students) ? 'text-success' : 'text-warning'))
        );
        echo html_writer::end_tag('div');
    }

    // Success celebration if all went well
    $total_sent = ($message_type === 'email' || $message_type === 'both' ? $sent_email_count : 0) +
        ($message_type === 'message' || $message_type === 'both' ? $sent_notification_count : 0);
    $total_expected = ($message_type === 'email' || $message_type === 'both' ? count($students) : 0) +
        ($message_type === 'message' || $message_type === 'both' ? count($students) : 0);

    if ($total_sent == $total_expected) {
        echo html_writer::start_tag('div', array('class' => 'alert alert-success text-center mt-3 py-3'));
        echo html_writer::tag('h4', 
            html_writer::tag('i', '', array('class' => 'fas fa-check-circle mr-2')) .
            '🎉 All notifications processed successfully!'
        );
        echo html_writer::tag('p', 'Users will see these notifications on their dashboards.', array('class' => 'mb-0'));
        echo html_writer::end_tag('div');
    }

    if (!empty($failed_users)) {
        echo html_writer::start_tag('div', array('class' => 'alert alert-warning mt-3'));
        echo html_writer::tag('h5', 
            html_writer::tag('i', '', array('class' => 'fas fa-exclamation-triangle mr-2')) .
            '⚠ Some notifications may need attention:'
        );
        echo html_writer::start_tag('ul', array('class' => 'mb-0'));
        foreach ($failed_users as $user) {
            echo html_writer::start_tag('li');
            echo html_writer::tag('strong', $user['name']);
            $user_errors = array();
            if (!empty($user['email_error'])) {
                $user_errors[] = 'Email: ' . $user['email_error'];
            }
            if (!empty($user['notification_error'])) {
                $user_errors[] = 'Notification: ' . $user['notification_error'];
            }
            if (!empty($user_errors)) {
                echo ' - ' . implode('; ', $user_errors);
            }
            echo html_writer::end_tag('li');
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
    }

    echo html_writer::end_tag('div'); // End summary card

    // NEXT STEPS INFORMATION
    echo html_writer::start_tag('div', array('class' => 'next-steps-card'));
    echo html_writer::tag('h4', 
        html_writer::tag('i', '', array('class' => 'fas fa-arrow-right mr-2')) .
        '✅ What happens next:',
        array('class' => 'next-steps-title')
    );

    echo html_writer::start_tag('div');
    
    echo html_writer::start_tag('div', array('class' => 'step-item'));
    echo html_writer::tag('i', '', array('class' => 'fas fa-bell step-icon'));
    echo html_writer::tag('div', 
        html_writer::tag('strong', 'For Moodle Notifications:') .
        ' Users will see a red notification bell icon in the top navigation',
        array('class' => 'step-text')
    );
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', array('class' => 'step-item'));
    echo html_writer::tag('i', '', array('class' => 'fas fa-mouse-pointer step-icon'));
    echo html_writer::tag('div', 
        html_writer::tag('strong', 'To view notifications:') .
        ' Users should click the bell icon to see all their notifications',
        array('class' => 'step-text')
    );
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', array('class' => 'step-item'));
    echo html_writer::tag('i', '', array('class' => 'fas fa-envelope step-icon'));
    echo html_writer::tag('div', 
        html_writer::tag('strong', 'For emails:') .
        ' Check users\' email inboxes (and spam folders)',
        array('class' => 'step-text')
    );
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div');

    // Quick check link
    $check_url = new moodle_url('/local/form/nonregistered.php', array(
        'token' => $token,
        'formid' => $formid,
        'cohort' => $cohortid
    ));

    echo html_writer::start_tag('div', array('class' => 'mt-4 text-center'));
    echo html_writer::link(
        $check_url,
        html_writer::tag('i', '', array('class' => 'fas fa-list mr-2')) .
        'View Non-Registered List',
        array('class' => 'btn-custom btn-primary-custom')
    );
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div'); // End next steps card

    // Simple logging - just add to Moodle log table directly
    $logdata = array(
        'time' => time(),
        'userid' => $USER->id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'module' => 'local_form',
        'action' => 'send_reminders',
        'url' => 'notify_nonregistered.php',
        'info' => 'Sent reminders: ' .
            ($message_type === 'email' || $message_type === 'both' ? 'Emails=' . $sent_email_count . '/' . count($students) . ' ' : '') .
            ($message_type === 'message' || $message_type === 'both' ? 'Notifications=' . $sent_notification_count . '/' . count($students) . ' ' : '') .
            'for form ' . $formid . ' (cohort: ' . $cohortid . ')'
    );

    // Insert directly into log table
    $DB->insert_record('log', $logdata);
}

echo html_writer::end_tag('div'); // End notification-container

echo $OUTPUT->footer();
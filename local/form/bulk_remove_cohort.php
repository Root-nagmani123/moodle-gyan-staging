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
 * Bulk remove users from cohort
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

// Get parameters - UPDATED: Better handling of userids
$token = optional_param('token', '', PARAM_RAW);
$formid = optional_param('formid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);

// Get userids from POST or GET - FIXED: Handle comma-separated string
$userids_param = optional_param('userids', '', PARAM_RAW);

// Convert comma-separated string to array of integers
$userids = array();
if (!empty($userids_param)) {
    // If it's already an array (from GET/POST array), use it directly
    if (is_array($userids_param)) {
        $userids = array_map('intval', $userids_param);
    } else {
        // Split comma-separated string
        $userids = array_map('intval', explode(',', $userids_param));
    }
}

// Remove empty values
$userids = array_filter($userids);

// Verify token if provided
if (!empty($token)) {
    $data = local_form_validate_token($token, 'nonregistered');
    if (!$data) {
        throw new moodle_exception('Invalid or expired link. Please request a new link.');
    }
}

if ($formid <= 0 || $cohortid <= 0) {
    throw new moodle_exception('Invalid parameters');
}

// Check if we have users to remove
if (empty($userids)) {
    $redirect_params = ['formid' => $formid, 'cohortid' => $cohortid];
    if (!empty($token)) {
        $redirect_params['token'] = $token;
    }
    $redirect_url = new moodle_url('/local/form/nonregistered.php', $redirect_params);
    redirect(
        $redirect_url,
        get_string('no_users_selected', 'local_form'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Require confirmation
require_sesskey();

// Get cohort info for messages
$cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name, idnumber');
$form = $DB->get_record('local_form', ['id' => $formid], 'name');

// Remove users from cohort
$removed_count = 0;
$failed_count = 0;

foreach ($userids as $userid) {
    try {
        // Check if user is actually in the cohort
        if ($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
            // Remove from cohort
            $DB->delete_records('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid]);
            
            // Log the action
            $event = \core\event\cohort_member_removed::create([
                'context' => context_system::instance(),
                'objectid' => $cohortid,
                'relateduserid' => $userid,
                'other' => [
                    'cohortid' => $cohortid,
                    'cohortname' => $cohort->name ?? '',
                    'formid' => $formid,
                    'formname' => $form->name ?? ''
                ]
            ]);
            $event->trigger();
            
            $removed_count++;
        } else {
            $failed_count++;
        }
    } catch (Exception $e) {
        $failed_count++;
        debugging("Error removing user {$userid} from cohort {$cohortid}: " . $e->getMessage());
    }
}

// Prepare redirect URL
$redirect_params = ['formid' => $formid, 'cohortid' => $cohortid];
if (!empty($token)) {
    $redirect_params['token'] = $token;
}
$redirect_url = new moodle_url('/local/form/nonregistered.php', $redirect_params);

// Prepare messages
$messages = [];

if ($removed_count > 0) {
    $message = get_string('users_removed_from_cohort', 'local_form');
    $message = str_replace('{count}', $removed_count, $message);
    $message = str_replace('{cohort}', format_string($cohort->name ?? ''), $message);
    $messages[] = $message;
    
    // Notification type
    $notification_type = \core\output\notification::NOTIFY_SUCCESS;
}

if ($failed_count > 0) {
    $error_message = get_string('users_failed_removal', 'local_form');
    $error_message = str_replace('{count}', $failed_count, $error_message);
    $messages[] = $error_message;
    
    // If we have both successes and failures, show warning
    if ($removed_count > 0) {
        $notification_type = \core\output\notification::NOTIFY_WARNING;
    } else {
        $notification_type = \core\output\notification::NOTIFY_ERROR;
    }
}

// Combine messages
$final_message = implode('<br>', $messages);

// Redirect with message
redirect($redirect_url, $final_message, null, $notification_type);
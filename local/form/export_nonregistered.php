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
 * Export non-registered students data
 *
 * @package   local_form
 */

require_once('../../config.php');
require_once('lib.php');

global $DB, $CFG;

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
$dataformat = optional_param('dataformat', '', PARAM_ALPHA);
$token = optional_param('token', '', PARAM_RAW);
$formid = optional_param('formid', 0, PARAM_INT);
$cohortid = optional_param('cohort', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$exportall = optional_param('exportall', 0, PARAM_INT);

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

// Get non-registered students data
$params = ['formid' => $formid];
$cohort_condition = '';

if ($cohortid > 0) {
    $cohort_condition = "AND c.id = :cohortid";
    $params['cohortid'] = $cohortid;
}

// SQL to get non-registered students
$sql = "
    SELECT 
        u.id AS user_id,
        u.username,
        u.firstname,
        u.lastname,
        u.email,
        u.idnumber,
        u.institution,
        u.department,
        u.city,
        u.country,
        u.lang,
        u.timezone,
        FROM_UNIXTIME(u.lastaccess) AS last_access,
        c.name AS cohort_name,
        c.idnumber AS cohort_idnumber,
        c.description AS cohort_description
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
    ORDER BY 
        u.lastname, u.firstname
";

// Apply pagination if not exporting all
// if (!$exportall) {
//     $sql .= " LIMIT " . ($page * $perpage) . ", " . $perpage;
// }

$students = $DB->get_records_sql($sql, $params);

// Prepare data for export
$data = [];
$columns = [
    'user_id' => get_string('user_id', 'local_form'),
    'username' => get_string('username', 'local_form'),
    'firstname' => get_string('firstname', 'local_form'),
    'lastname' => get_string('lastname', 'local_form'),
    'email' => get_string('email', 'local_form'),
    'cohort_name' => get_string('cohort_name', 'local_form'),
    'status' => get_string('status', 'local_form'),
    'last_access' => get_string('last_access', 'local_form')
];

foreach ($students as $student) {
    $data[] = [
        'user_id' => $student->user_id,
        'username' => $student->username,
        'firstname' => $student->firstname,
        'lastname' => $student->lastname,
        'email' => $student->email,
        'cohort_name' => $student->cohort_name,
        'status' => 'Not Registered',
        'last_access' => $student->last_access
    ];
}

// Generate filename
$filename = 'nonregistered_students_form_' . $formid;
if ($cohortid > 0) {
    $cohortname = $DB->get_field('cohort', 'idnumber', ['id' => $cohortid]);
    if ($cohortname) {
        $filename .= '_cohort_' . $cohortname;
    } else {
        $filename .= '_cohort_' . $cohortid;
    }
}
$filename .= '_' . date('Y-m-d_H-i');

// Download data
\core\dataformat::download_data($filename, $dataformat, array_values($columns), $data);
exit;
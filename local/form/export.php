<?php
require_once('../../config.php');
require_once('lib.php');
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
$dataformat = optional_param('dataformat', '', PARAM_ALPHA);
// die($dataformat);

$page = optional_param('page', '', PARAM_INT);
$formid = optional_param('formid', '', PARAM_INT);
$visible = optional_param('visible', '', PARAM_INT);

$perpage = COURSEERPAGE;

$columns = get_dynamic_columns('form_submissions',$formid);
// $filename = !empty($columns) ? $columns[2] . '_submissions' : 'form_submissions_' . $formid;
$rs = printcourseData($formid, $columns, $page, $perpage,$visible,$dataformat);
// Extract the first column's first value for the filename
$firstColumn = !empty($columns) ? $columns[0] : 'form_submissions'; // Default column if no columns exist
$firstValue = !empty($rs) && isset($rs[0][$firstColumn]) ? $rs[0][$firstColumn] : 'default_value';

// Sanitize the value for use in the filename
$firstValue = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $firstValue); // Replace non-alphanumeric characters with underscores

// Set the filename
$filename = 'courseregisteration';
\core\dataformat::download_data($filename, $dataformat, $columns, $rs, null);

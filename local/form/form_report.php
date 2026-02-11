<?php
require_once('../../config.php');
require_once('lib.php');

global $CFG, $PAGE, $OUTPUT, $DB;

require_login();
// if (!is_siteadmin()) {
//     throw new moodle_exception('accessdenied', 'admin');
// }

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/form/form_report.php');
$PAGE->set_title('Forms Report');
$PAGE->set_heading('Forms Report');

$page   = optional_param('page', 0, PARAM_INT);
$status = optional_param('status', 'active', PARAM_ALPHA); // active | inactive
$perpage = PERPAGE;

echo $OUTPUT->header();


$visible = ($status === 'inactive') ? 0 : 1;
$recordcount = $DB->count_records('local_form', ['visible' => $visible]);

$statuslabel = ($status === 'inactive') ? 'Inactive Forms' : 'Active Forms';
$statusbadge = ($status === 'inactive') ? 'badge-secondary' : 'badge-success';

$activeurl   = new moodle_url('/local/form/form_report.php', ['status' => 'active']);
$inactiveurl = new moodle_url('/local/form/form_report.php', ['status' => 'inactive']);

$btnactiveclass   = ($status === 'active')
    ? 'btn btn-primary'
    : 'btn btn-outline-primary';

$btninactiveclass = ($status === 'inactive')
    ? 'btn btn-primary'
    : 'btn btn-outline-primary';
$manageformsurl = new moodle_url('/local/form/manageform.php');
echo html_writer::start_div('card mb-4 shadow-sm');
echo html_writer::start_div('card-body');

if (is_siteadmin()) {
    echo html_writer::div(
        html_writer::link(
            $manageformsurl,
            html_writer::tag('i', '', ['class' => 'fas fa-arrow-left mr-2']) . 'Back to Manage Forms',
            [
                'class' => 'btn btn-outline-primary btn-sm mb-3',
                'title' => 'Return to Manage Forms'
            ]
        ),
        'd-flex justify-content-start'
    );
}



/* Header Row */
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');

echo html_writer::tag(
    'h3',
    'Forms Status & Submission Report' .
        html_writer::tag(
            'span',
            $statuslabel,
            ['class' => "badge $statusbadge ml-2"]
        ),
    ['class' => 'mb-0']
);

echo html_writer::start_div('btn-group');
echo html_writer::link(
    $activeurl,
    html_writer::tag('i', '', ['class' => 'fas fa-check-circle mr-1']) . 'Active',
    ['class' => $btnactiveclass]
);
echo html_writer::link(
    $inactiveurl,
    html_writer::tag('i', '', ['class' => 'fas fa-ban mr-1']) . 'Inactive',
    ['class' => $btninactiveclass]
);
echo html_writer::end_div();


echo html_writer::end_div();
echo html_writer::div(
    '<strong>About this report</strong><br>
     This report provides an overview of all forms created in the system. You can view active and inactive forms, check linked cohorts, and access form submissions for reporting and monitoring purposes.',
    'bg-light p-3 rounded mb-4'
);


/* Count Info */
echo html_writer::tag(
    'p',
    "Total records: <strong>{$recordcount}</strong>",
    ['class' => 'text-muted mb-3']
);


$records = $DB->get_records_sql(
    "SELECT *
       FROM {local_form}
      WHERE visible = ?
   ORDER BY sortorder DESC",
    [$visible],
    $page * $perpage,
    $perpage
);

$renderer = $PAGE->get_renderer('local_form');
echo $renderer->local_manageforms_report($records, $recordcount, $page, $perpage, $status);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo $OUTPUT->footer();

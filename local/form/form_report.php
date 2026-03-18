<?php
require_once('../../config.php');
require_once('lib.php');

global $CFG, $PAGE, $OUTPUT, $DB;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/form/form_report.php');
$PAGE->set_title('Forms Report');
$PAGE->set_heading('Forms Report');

$page    = optional_param('page', 0, PARAM_INT);
$status  = optional_param('status', 'active', PARAM_ALPHA);
$search  = optional_param('search', '', PARAM_TEXT);
$perpage = PERPAGE;

echo $OUTPUT->header();

$visible = ($status === 'inactive') ? 0 : 1;


$params = [$visible];
$sqlwhere = "visible = ?";

if (!empty($search)) {
    $sqlwhere .= " AND " . $DB->sql_like('name', '?', false);
    $params[] = '%' . $search . '%';
}


$recordcount = $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {local_form}
      WHERE $sqlwhere",
    $params
);


$statuslabel = ($status === 'inactive') ? 'Inactive Forms' : 'Active Forms';
$statusbadge = ($status === 'inactive') ? 'badge-secondary' : 'badge-success';

$activeurl   = new moodle_url('/local/form/form_report.php', ['status' => 'active']);
$inactiveurl = new moodle_url('/local/form/form_report.php', ['status' => 'inactive']);

$btnactiveclass   = ($status === 'active') ? 'btn btn-primary' : 'btn btn-outline-primary';
$btninactiveclass = ($status === 'inactive') ? 'btn btn-primary' : 'btn btn-outline-primary';

$manageformsurl = new moodle_url('/local/form/manageform.php');

echo html_writer::start_div('card mb-4 shadow-sm');
echo html_writer::start_div('card-body');

if (is_siteadmin()) {
    echo html_writer::div(
        html_writer::link(
            $manageformsurl,
            html_writer::tag('i', '', ['class' => 'fas fa-arrow-left mr-2']) . 'Back to Manage Forms',
            ['class' => 'btn btn-outline-primary btn-sm mb-3']
        )
    );
}


echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');

echo html_writer::tag(
    'h3',
    'Forms Status & Submission Report ' .
        html_writer::tag('span', $statuslabel, ['class' => "badge $statusbadge ml-2"]),
    ['class' => 'mb-0']
);

echo html_writer::start_div('btn-group');
echo html_writer::link($activeurl, 'Active', ['class' => $btnactiveclass]);
echo html_writer::link($inactiveurl, 'Inactive', ['class' => $btninactiveclass]);
echo html_writer::end_div();

echo html_writer::end_div();


echo html_writer::start_tag('form', [
    'method' => 'get',
    'class'  => 'form-inline mb-3'
]);

echo html_writer::empty_tag('input', [
    'type'        => 'text',
    'name'        => 'search',
    'value'       => s($search),
    'placeholder' => 'Search by form name',
    'class'       => 'form-control mr-2'
]);

echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'status',
    'value' => $status
]);

echo html_writer::tag('button', 'Search', [
    'type'  => 'submit',
    'class' => 'btn btn-primary'
]);

if (!empty($search)) {
    echo html_writer::link(
        new moodle_url('/local/form/form_report.php', ['status' => $status]),
        'Reset',
        ['class' => 'btn btn-outline-secondary ml-2']
    );
}

echo html_writer::end_tag('form');


$sql = "SELECT *
          FROM {local_form}
         WHERE $sqlwhere
      ORDER BY sortorder DESC";

$records = $DB->get_records_sql(
    $sql,
    $params,
    $page * $perpage,
    $perpage
);


$renderer = $PAGE->get_renderer('local_form');
echo $renderer->local_manageforms_report(
    $records,
    $recordcount,
    $page,
    $perpage,
    $status,
    $search
);

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

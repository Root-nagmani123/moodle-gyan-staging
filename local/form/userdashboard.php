<?php 
require_once('../../config.php');
require_once('lib.php');
global $CFG, $USER, $PAGE;
require_login();
$context = context_system::instance();
$PAGE->set_url('/local/form/userdashboard.php');
$PAGE->set_context($context);
// $PAGE->set_pagelayout('course');
$PAGE->set_title('userdashboard');
$PAGE->set_heading('User Dashboard');
$page = optional_param('page', 0, PARAM_INT);
$PAGE->requires->js_call_amd('local_form/main', 'form');
echo $OUTPUT->header();
$o = '';
$o .= "<h2>Pick Your Registration Form and Complete the Process</h2>";
$ajaxurl = new moodle_url('/local/form/ajax.php');
$renderer = $PAGE->get_renderer('local_form');
$o .= "<input type='hidden' value='" . $ajaxurl . "' class='ajaxurl'>"; //for ajax call
$o .= html_writer::start_tag('div', array('class' => 'd-flex float-right'));
$o .= html_writer::end_tag('div');
// $records = $DB->get_records_sql('SELECT * FROM {local_form} order by sortorder ASC',null, $page * PERPAGE, PERPAGE);
// $recordcount = $DB->count_records('local_form', null);

$records = $DB->get_records_sql(
    'SELECT * FROM {local_form} WHERE visible = 1 ORDER BY sortorder ASC',
    null,
    $page * PERPAGE,
    PERPAGE
);

// Total record count for visible records
$recordcount = $DB->count_records_select('local_form', 'visible = 1');
$o .= html_writer::start_tag('div', array('id' => 'localformlist'));
$o .= $renderer->local_userdashboard($records, $recordcount,$page, PERPAGE);
$o .= html_writer::end_tag('div');
echo $o;
echo $OUTPUT->footer();

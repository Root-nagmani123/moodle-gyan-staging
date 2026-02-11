<?php
require_once('../../config.php');
require_once('lib.php');
global $CFG, $USER, $PAGE;
require_login();
if (!is_siteadmin()) {
    throw new moodle_exception('Access denied');
}
$context = context_system::instance();
$PAGE->set_url('/local/form/manageform.php');
$PAGE->set_context($context);
$PAGE->set_title('Manageforms');
$PAGE->set_heading('Manage Forms');
$page = optional_param('page', 0, PARAM_INT);
echo $OUTPUT->header();
$o = '';
$ajaxurl = new moodle_url('/local/form/ajax.php');
$addform_link = new moodle_url('/local/form/addnewform.php');
$inactiveform_link = new moodle_url('/local/form/inactive_forms.php');
$generate_encrypt_url = new moodle_url('/local/form/generate_encrypt_url.php');
$formreport_link = new moodle_url('/local/form/form_report.php');

$renderer = $PAGE->get_renderer('local_form');
$o .= "<input type='hidden' value='" . $ajaxurl . "' class='ajaxurl'>"; //for ajax call
$o .= html_writer::start_tag('div', array('class' => 'd-flex float-right'));
//$o .= html_writer::tag('h2', get_string('category', 'local_form'), array('class' => 'page-title pb-2 d-none'));
$o .= html_writer::end_tag('div');
$o .= html_writer::link($addform_link,html_writer::tag('i', '', ['class' => 'fas fa-plus-circle mr-1']) . get_string('addformlink', 'local_form'),
    ['class' => 'btn btn-primary']);

$o .= html_writer::link($inactiveform_link,html_writer::tag('i', '', ['class' => 'fas fa-ban mr-1']) . get_string('inactiveform', 'local_form'),
    ['class' => 'btn btn-primary ml-2']);

$o .= html_writer::link($generate_encrypt_url,html_writer::tag('i', '', ['class' => 'fas fa-key mr-1']) . 'Generate Encrypted URL',
['class' => 'btn btn-secondary ml-2']);

$o .= html_writer::link($formreport_link,html_writer::tag('i', '', ['class' => 'fas fa-chart-bar mr-1']) . 'Forms Report',
    ['class' => 'btn btn-info ml-2']);

// Fetch forms
$records = $DB->get_records_sql('SELECT * FROM {local_form} Where visible = 1 order by sortorder Desc', null, $page * PERPAGE, PERPAGE);
// Find where you have other buttons and add this:
$copyform_link = new moodle_url('/local/form/copyform.php');
$o .=  html_writer::link($copyform_link, html_writer::tag('i', '', ['class' => 'fas fa-copy mr-1']) . 'Copy Existing Form', array('class' => 'btn btn-primary ml-2'));
// Add this line with your other buttons
$advancedcopy_link = new moodle_url('/local/form/copyform_advanced.php');
$o .=  html_writer::link($advancedcopy_link, html_writer::tag('i', '', ['class' => 'fas fa-copy mr-1']) . 'Advanced Copy', array('class' => 'btn btn-info ml-2'));
// print_object($records);
$recordcount = $DB->count_records('local_form', ['visible' => 1]);

$o .= html_writer::start_tag('div', array('id' => 'localformlist'));
$o .= $renderer->local_manageforms($records, $recordcount, $page, PERPAGE);
$o .= html_writer::end_tag('div');
$PAGE->requires->js_call_amd('local_form/main', 'formoperation');
echo $o;
echo $OUTPUT->footer();

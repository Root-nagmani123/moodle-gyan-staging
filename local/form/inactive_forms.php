<?php 
require_once('../../config.php');
require_once('lib.php');
global $CFG, $USER, $PAGE;
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
$context = context_system::instance();
$PAGE->set_url('/local/form/inactive_forms.php');
$PAGE->set_context($context);
// $PAGE->set_pagelayout('course');
$PAGE->set_title('inactive_forms');
$PAGE->set_heading('Manage Inactive Forms');
//$PAGE->navbar->add(get_string('category', 'local_form'), new moodle_url('/local/allcourses/configuration/category.php'));
$page = optional_param('page', 0, PARAM_INT);
echo $OUTPUT->header();
$o = '';
$ajaxurl = new moodle_url('/local/form/ajax.php');
$renderer = $PAGE->get_renderer('local_form');
$o .= "<input type='hidden' value='" . $ajaxurl . "' class='ajaxurl'>"; //for ajax call
$o .= html_writer::start_tag('div', array('class' => 'd-flex float-right'));
//$o .= html_writer::tag('h2', get_string('category', 'local_form'), array('class' => 'page-title pb-2 d-none'));
$o .= html_writer::end_tag('div');
$records = $DB->get_records_sql('SELECT * FROM {local_form} Where visible = 0 order by sortorder ASC',null, $page * PERPAGE, PERPAGE);
$recordcount = $DB->count_records('local_form', ['visible' => 0]);

$o .= html_writer::start_tag('div', array('id' => 'inactiveform_list'));
$backlink = new moodle_url('/local/form/manageform.php');
$o .=  html_writer::link($backlink, 'Back', array('class' => 'btn btn-primary ml-2'));
$o .= '<br><br>';
$o .= $renderer->local_inactive_formlist($records, $recordcount,$page, PERPAGE);
$o .= html_writer::end_tag('div');
$PAGE->requires->js_call_amd('local_form/main', 'formoperation');
$PAGE->requires->js_call_amd('local_form/main', 'inactiveformlist');
echo $o;
echo $OUTPUT->footer();

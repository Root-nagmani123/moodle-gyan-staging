<?php
require_once('../../config.php');

$PAGE->set_url(new moodle_url('/local/form/home.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('homepage', 'local_form'));
// $PAGE->set_heading(get_string('welcometomoodle', 'local_form'));
$PAGE->set_pagelayout('standard'); // Use the login layout for pre-login pages

// Set the background image URL
$faqimage = $CFG->wwwroot . '/local/form/pix/diroffice.jpg';

// Inline CSS for the background image
$custom_style = "
    body {
        background: url('$faqimage') no-repeat center center fixed;
        background-size: cover;
        color: #fff; /* Optional: Set text color to contrast with the background */
    }
    .homepage-links {
        text-align: center;
        margin-top: 20%;
    }
    .btn {
        margin: 10px;
        padding: 10px 20px;
        font-size: 18px;
    }
";

// Add the custom style to the page
echo html_writer::tag('style', $custom_style);

echo $OUTPUT->header();

// Add your links here
echo html_writer::start_div('homepage-links');
echo html_writer::tag('h1', get_string('welcomemessage', 'local_form'));
echo html_writer::link(new moodle_url('/local/form/register.php'), get_string('register', 'local_form'), ['class' => 'btn btn-primary']);
echo '  ';
echo html_writer::link(new moodle_url('/login/index.php'), get_string('login', 'local_form'), ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo $OUTPUT->footer(); 

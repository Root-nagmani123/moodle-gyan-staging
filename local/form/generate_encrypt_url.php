<?php
// generate_encrypt_url.php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php'); // Use the existing token functions

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
$PAGE->set_url('/local/form/generate_encrypt_url.php');
$PAGE->set_context($context);
$PAGE->set_title('Generate Registration URL');
$PAGE->navbar->add('Generate Registration URL');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Generate Registration URL', ['class' => 'mb-4']);

// Check if formid is provided
$formid = optional_param('formid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

global $DB;

if ($formid && $action == 'generate') {
    // Verify form exists
    if ($DB->record_exists('local_form', ['id' => $formid, 'visible' => 1])) {
        // Generate token-based URL (no expiry - set to 0)
        $register_url = local_form_generate_signed_url($formid, 'register', [], 0);
        
        // Get form details
        $form = $DB->get_record('local_form', ['id' => $formid]);
        
        echo html_writer::start_tag('div', ['class' => 'alert alert-success']);
        echo html_writer::tag('h4', 'Registration URL Generated!', ['class' => 'alert-heading']);
        echo html_writer::tag('p', 'For form: ' . html_writer::tag('strong', $form->name));
        echo html_writer::end_tag('div');
        
        // Display the URL in a box
        echo html_writer::start_tag('div', ['class' => 'card mb-4']);
        echo html_writer::start_tag('div', ['class' => 'card-header bg-primary text-white']);
        echo html_writer::tag('h5', 'Registration URL', ['class' => 'mb-0']);
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        
        // Display URL
        echo html_writer::tag('p', html_writer::tag('strong', 'Full URL:'));
        echo html_writer::start_tag('div', ['class' => 'input-group mb-3']);
        echo html_writer::tag('input', '', [
            'type' => 'text',
            'value' => $register_url,
            'id' => 'encryptedUrl',
            'class' => 'form-control',
            'readonly' => 'readonly'
        ]);
        echo html_writer::start_tag('div', ['class' => 'input-group-append']);
        echo html_writer::tag('button', 'Copy', [
            'class' => 'btn btn-primary',
            'onclick' => 'copyToClipboard()',
            'type' => 'button'
        ]);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        
        // Short explanation
        echo html_writer::start_tag('div', ['class' => 'alert alert-info']);
        echo html_writer::tag('p', html_writer::tag('strong', 'How to use:'));
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', 'Copy the URL above');
        echo html_writer::tag('li', 'Use it on your website, in emails, or anywhere you want to share');
        echo html_writer::tag('li', 'Example: <code>&lt;a href="' . htmlspecialchars($register_url) . '"&gt;Register Here&lt;/a&gt;</code>');
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
        
        echo html_writer::end_tag('div'); // card-body
        echo html_writer::end_tag('div'); // card
        
        // Show back button
        echo html_writer::start_tag('div', ['class' => 'text-center mt-4']);
        echo html_writer::link(
            new moodle_url('/local/form/generate_encrypt_url.php'),
            'Generate Another URL',
            ['class' => 'btn btn-secondary']
        );
        echo ' ';
        echo html_writer::link(
            new moodle_url('/local/form/manageform.php'),
            'Back to Forms',
            ['class' => 'btn btn-outline-secondary']
        );
        echo html_writer::end_tag('div');
        
    } else {
        echo $OUTPUT->notification('Invalid form ID or form is not visible', 'error');
        echo html_writer::link(
            new moodle_url('/local/form/generate_encrypt_url.php'),
            'Try Again',
            ['class' => 'btn btn-primary']
        );
    }
    
} else {
    // Show form selection
    
    // Get all visible forms
    $forms = $DB->get_records('local_form', ['visible' => 1], 'name ASC');
    
    if (empty($forms)) {
        echo $OUTPUT->notification('No forms available', 'warning');
        echo $OUTPUT->continue_button(new moodle_url('/local/form/manageform.php'));
    } else {
        echo html_writer::tag('p', 'Select a form to generate a registration URL:', ['class' => 'mb-4']);
        
        // Simple selection form
        echo html_writer::start_tag('form', [
            'method' => 'get',
            'action' => '',
            'class' => 'form-horizontal'
        ]);
        
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'generate'
        ]);
        
        echo html_writer::start_tag('div', ['class' => 'form-group row']);
        echo html_writer::tag('label', 'Select Form:', [
            'for' => 'formid',
            'class' => 'col-md-3 col-form-label font-weight-bold'
        ]);
        echo html_writer::start_tag('div', ['class' => 'col-md-9']);
        echo html_writer::start_tag('select', [
            'name' => 'formid',
            'id' => 'formid',
            'class' => 'form-control',
            'required' => 'required'
        ]);
        echo html_writer::tag('option', '-- Select a Form --', ['value' => '']);
        
        foreach ($forms as $form) {
            echo html_writer::tag('option', $form->name, ['value' => $form->id]);
        }
        
        echo html_writer::end_tag('select');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div', ['class' => 'form-group row']);
        echo html_writer::start_tag('div', ['class' => 'col-md-9 offset-md-3']);
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => 'Generate Registration URL',
            'class' => 'btn btn-primary'
        ]);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        
        echo html_writer::end_tag('form');
        
        // Quick links for common forms
        echo html_writer::start_tag('div', ['class' => 'card mt-4']);
        echo html_writer::start_tag('div', ['class' => 'card-header bg-light']);
        echo html_writer::tag('h5', 'Quick Generate', ['class' => 'mb-0']);
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('p', 'Click on a form to quickly generate its URL:', ['class' => 'mb-2']);
        
        echo html_writer::start_tag('div', ['class' => 'list-group']);
        foreach ($forms as $form) {
            $url = new moodle_url('/local/form/generate_encrypt_url.php', [
                'formid' => $form->id,
                'action' => 'generate'
            ]);
            echo html_writer::link($url, $form->name, ['class' => 'list-group-item list-group-item-action']);
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
}

// Add JavaScript for copy functionality
echo '
<script>
function copyToClipboard() {
    var copyText = document.getElementById("encryptedUrl");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(copyText.value).then(function() {
        // Change button text temporarily
        var btn = document.querySelector("button[onclick=\'copyToClipboard()\']");
        var originalText = btn.textContent;
        btn.textContent = "Copied!";
        btn.classList.remove("btn-primary");
        btn.classList.add("btn-success");
        
        setTimeout(function() {
            btn.textContent = originalText;
            btn.classList.remove("btn-success");
            btn.classList.add("btn-primary");
        }, 2000);
    }).catch(function(err) {
        // Fallback for older browsers
        document.execCommand("copy");
        alert("URL copied to clipboard!");
    });
}
</script>';

echo $OUTPUT->footer();
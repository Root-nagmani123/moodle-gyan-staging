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
 * Advanced copy form with selective field copying
 *
 * @package    local_form
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir . '/formslib.php');

global $CFG, $USER, $PAGE, $DB;

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

$step = optional_param('step', 1, PARAM_INT);
$formid = optional_param('formid', 0, PARAM_INT);
$sesskey = optional_param('sesskey', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_url('/local/form/copyform_advanced.php');
$PAGE->set_context($context);
$PAGE->set_title('Advanced Form Copy');
$PAGE->set_heading('Copy Form with Selected Fields');
// $PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Advanced Copy');

// Add custom CSS
$PAGE->requires->css('/local/form/styles/copyform.css');

echo $OUTPUT->header();

if ($step == 1) {
    display_form_selection();
} else if ($step == 2 && $formid) {
    display_field_selection($formid);
} else if ($step == 3 && confirm_sesskey()) {
    process_form_copy();
} else {
    redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]));
}

echo $OUTPUT->footer();

/**
 * Display form selection (Step 1)
 */
function display_form_selection()
{
    global $DB, $OUTPUT;

    $forms = $DB->get_records('local_form', ['visible' => 1], 'name ASC');

    if (empty($forms)) {
        echo $OUTPUT->notification('No forms available to copy.', 'warning');
        echo $OUTPUT->continue_button(new moodle_url('/local/form/manageform.php'));
        return;
    }

    echo html_writer::tag('h3', 'Step 1: Select Form to Copy', ['class' => 'mb-4']);
    echo html_writer::tag('p', 'Choose a form to copy from the list below.', ['class' => 'text-muted mb-4']);

    // Create a responsive table similar to manageform.php
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'width' => '100%']);
    
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Form Name', ['width' => '30%']);
    echo html_writer::tag('th', 'Description', ['width' => '40%']);
    echo html_writer::tag('th', 'Sections', ['class' => 'text-center', 'width' => '10%']);
    echo html_writer::tag('th', 'Fields', ['class' => 'text-center', 'width' => '10%']);
    echo html_writer::tag('th', 'Action', ['class' => 'text-center', 'width' => '10%']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    
    foreach ($forms as $form) {
        $fieldcount = $DB->count_records('form_data', ['formid' => $form->id]);
        $sectioncount = $DB->count_records('form_sections', ['formid' => $form->id]);

        $actionurl = new moodle_url('/local/form/copyform_advanced.php', [
            'step' => 2,
            'formid' => $form->id
        ]);
        
        echo html_writer::start_tag('tr');
        
        // Form Name
        echo html_writer::start_tag('td');
        echo html_writer::tag('strong', $form->name);
        if (!empty($form->shortname)) {
            echo html_writer::tag('div', html_writer::tag('small', 'Shortname: ' . $form->shortname, ['class' => 'text-muted']));
        }
        echo html_writer::end_tag('td');
        
        // Description
        echo html_writer::start_tag('td');
        echo shorten_text($form->description, 80);
        echo html_writer::end_tag('td');
        
        // Sections count
        echo html_writer::start_tag('td', ['class' => 'text-center']);
        echo html_writer::tag('span', $sectioncount, ['class' => 'badge badge-info']);
        echo html_writer::end_tag('td');
        
        // Fields count
        echo html_writer::start_tag('td', ['class' => 'text-center']);
        echo html_writer::tag('span', $fieldcount, ['class' => 'badge badge-secondary']);
        echo html_writer::end_tag('td');
        
        // Action
        echo html_writer::start_tag('td', ['class' => 'text-center']);
        echo html_writer::link($actionurl, 'Select', [
            'class' => 'btn btn-primary btn-sm',
            'title' => 'Select this form for copying'
        ]);
        echo html_writer::end_tag('td');
        
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_tag('div'); // table-responsive

    // Simple copy option
    echo html_writer::start_tag('div', ['class' => 'mt-4 pt-3 border-top']);
    echo html_writer::tag('h5', 'Quick Copy Option', ['class' => 'mb-3']);
    echo html_writer::tag('p', 'To copy entire form without selecting fields:', ['class' => 'text-muted mb-3']);
    $simplecopyurl = new moodle_url('/local/form/copyform.php');
    echo html_writer::link($simplecopyurl, 'Use Simple Copy', ['class' => 'btn btn-secondary']);
    echo html_writer::end_tag('div');
}


/**
 * Display field selection (Step 2)
 */
function display_field_selection($formid)
{
    global $DB, $OUTPUT, $PAGE;

    $form = $DB->get_record('local_form', ['id' => $formid], '*', MUST_EXIST);
    $sections = $DB->get_records('form_sections', ['formid' => $formid], 'sort_order ASC');

    if (empty($sections)) {
        echo $OUTPUT->notification('The selected form has no sections.', 'warning');
        echo $OUTPUT->continue_button(new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]));
        return;
    }

    echo html_writer::start_tag('div', ['class' => 'copy-form-container']);
    echo html_writer::tag('h3', 'Step 2: Select Fields to Copy');
    echo html_writer::tag('div', 'Copying from: ' . html_writer::tag('strong', $form->name), ['class' => 'mb-4']);
    
    // Field selection summary
    echo html_writer::start_tag('div', ['class' => 'alert alert-info mb-4']);
    echo html_writer::tag('span', '0 / 0 fields selected', ['id' => 'fieldCount', 'class' => 'font-weight-bold']);
    echo html_writer::end_tag('div');

    // Start form
    $actionurl = new moodle_url('/local/form/copyform_advanced.php', [
        'step' => 3,
        'formid' => $formid,
        'sesskey' => sesskey()
    ]);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $actionurl,
        'id' => 'copyFormAdvanced',
        'class' => 'copy-form-advanced'
    ]);

    // New form details card
    echo html_writer::start_tag('div', ['class' => 'card mb-4']);
    echo html_writer::start_tag('div', ['class' => 'card-header bg-primary text-white']);
    echo html_writer::tag('h5', 'New Form Details', ['class' => 'mb-0']);
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', ['class' => 'card-body']);

    // New form name
    echo html_writer::start_tag('div', ['class' => 'form-group row']);
    echo html_writer::tag('label', 'New Form Name*', [
        'class' => 'col-md-3 col-form-label font-weight-bold',
        'for' => 'new_form_name'
    ]);
    echo html_writer::start_tag('div', ['class' => 'col-md-9']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'new_form_name',
        'id' => 'new_form_name',
        'class' => 'form-control',
        'required' => 'required',
        'placeholder' => 'Enter unique form name',
        'value' => $form->name . ' (Copy)'
    ]);
    echo html_writer::tag('small', 'Maximum 255 characters. Must be unique.', ['class' => 'form-text text-muted']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    // New form shortname - NEW FIELD ADDED
    echo html_writer::start_tag('div', ['class' => 'form-group row']);
    echo html_writer::tag('label', 'New Form Shortname*', [
        'class' => 'col-md-3 col-form-label font-weight-bold',
        'for' => 'new_form_shortname'
    ]);
    echo html_writer::start_tag('div', ['class' => 'col-md-9']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'new_form_shortname',
        'id' => 'new_form_shortname',
        'class' => 'form-control',
        'required' => 'required',
        'placeholder' => 'Enter unique shortname (alphanumeric only)',
        'value' => !empty($form->shortname) ? $form->shortname . '_copy' : 'form_' . time()
    ]);
    echo html_writer::tag('small', 'Alphanumeric characters, hyphens, and underscores only. Must be unique. This will also be used as cohort name if cohort is created.', ['class' => 'form-text text-muted']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    // New form description
    echo html_writer::start_tag('div', ['class' => 'form-group row']);
    echo html_writer::tag('label', 'New Form Description', [
        'class' => 'col-md-3 col-form-label font-weight-bold',
        'for' => 'new_form_description'
    ]);
    echo html_writer::start_tag('div', ['class' => 'col-md-9']);
    echo html_writer::tag('textarea', $form->description, [
        'name' => 'new_form_description',
        'id' => 'new_form_description',
        'class' => 'form-control',
        'rows' => '3',
        'placeholder' => 'Enter form description'
    ]);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo html_writer::end_tag('div'); // card-body
    echo html_writer::end_tag('div'); // card

    // Bulk actions
    echo html_writer::start_tag('div', ['class' => 'card mb-4']);
    echo html_writer::start_tag('div', ['class' => 'card-header bg-info text-white d-flex justify-content-between align-items-center']);
    echo html_writer::tag('h5', 'Select Sections and Fields', ['class' => 'mb-0']);
    
    echo html_writer::start_tag('div', ['class' => 'btn-group btn-group-sm']);
    echo html_writer::tag('button', 'Select All', [
        'type' => 'button',
        'class' => 'btn btn-light',
        'id' => 'selectAll'
    ]);
    echo html_writer::tag('button', 'Deselect All', [
        'type' => 'button',
        'class' => 'btn btn-light',
        'id' => 'deselectAll'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div'); // card-header
    echo html_writer::end_tag('div'); // card

    // Sections container
    echo html_writer::start_tag('div', ['class' => 'sections-container']);

    $sectionindex = 0;
    foreach ($sections as $section) {
        $sectionindex++;
        $fields = $DB->get_records('form_data', [
            'formid' => $formid,
            'section_id' => $section->id
        ], 'sort_order ASC');

        echo html_writer::start_tag('div', ['class' => 'section-card card mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card-header d-flex align-items-center']);

        // Section checkbox
        echo html_writer::start_tag('div', ['class' => 'form-check mr-3']);
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'section-checkbox form-check-input',
            'id' => 'section_' . $section->id,
            'data-section' => $section->id,
            'checked' => 'checked'
        ]);
        echo html_writer::tag('label', '', [
            'class' => 'form-check-label',
            'for' => 'section_' . $section->id
        ]);
        echo html_writer::end_tag('div');
        
        // Section title
        echo html_writer::tag('h6', "Section {$sectionindex}: {$section->section_title}", [
            'class' => 'mb-0 flex-grow-1'
        ]);
        
        // Section field count
        if ($fields) {
            echo html_writer::tag('span', count($fields) . ' fields', [
                'class' => 'badge badge-primary ml-2'
            ]);
        }
        
        echo html_writer::end_tag('div'); // card-header

        echo html_writer::start_tag('div', ['class' => 'card-body p-3', 'id' => 'section_body_' . $section->id]);

        if ($fields) {
            echo html_writer::start_tag('div', ['class' => 'row g-3']); // Using g-3 for gutter spacing
            
            foreach ($fields as $field) {
                echo html_writer::start_tag('div', ['class' => 'col-12 col-md-6 col-lg-4']); // Responsive columns
                echo html_writer::start_tag('div', ['class' => 'field-item']);
                echo html_writer::start_tag('div', ['class' => 'card h-100 border']);
                
                // Field checkbox and details
                echo html_writer::start_tag('div', ['class' => 'card-body p-3']);
                echo html_writer::start_tag('div', ['class' => 'form-check d-flex align-items-start']);
                
                echo html_writer::empty_tag('input', [
                    'type' => 'checkbox',
                    'class' => 'field-checkbox form-check-input mt-1',
                    'name' => 'selected_fields[]',
                    'id' => 'field_' . $field->id,
                    'value' => $field->id,
                    'data-section' => $section->id,
                    'checked' => 'checked'
                ]);
                
                // Field details
                echo html_writer::start_tag('div', ['class' => 'field-details ml-2 flex-grow-1']);
                
                // Field label
                echo html_writer::tag('label', 
                    html_writer::tag('strong', $field->formlabel), [
                        'class' => 'form-check-label field-label d-block',
                        'for' => 'field_' . $field->id,
                        'style' => 'cursor: pointer;'
                    ]);
                
                // Field metadata
                echo html_writer::start_tag('div', ['class' => 'field-meta small text-muted mt-1']);
                
                // Field type
                echo html_writer::tag('span', "Type: " . html_writer::tag('span', $field->formtype, ['class' => 'badge badge-light']), 
                    ['class' => 'd-block mb-1']);
                
                // Badges for field properties
                echo html_writer::start_tag('div', ['class' => 'field-badges']);
                if ($field->required) {
                    echo html_writer::tag('span', 'Required', ['class' => 'badge badge-warning mr-1 mb-1']);
                }
                
                if ($field->formtype === 'select' || $field->formtype === 'radio' || $field->formtype === 'checkbox') {
                    if (!empty($field->fieldoption)) {
                        $optionscount = substr_count($field->fieldoption, ',') + 1;
                        echo html_writer::tag('span', "{$optionscount} options", ['class' => 'badge badge-info mr-1 mb-1']);
                    }
                }
                echo html_writer::end_tag('div'); // field-badges
                
                echo html_writer::end_tag('div'); // field-meta
                echo html_writer::end_tag('div'); // field-details
                echo html_writer::end_tag('div'); // form-check
                echo html_writer::end_tag('div'); // card-body
                echo html_writer::end_tag('div'); // card
                echo html_writer::end_tag('div'); // field-item
                echo html_writer::end_tag('div'); // col
            }
            
            echo html_writer::end_tag('div'); // row
        } else {
            echo html_writer::tag('p', 'No fields in this section.', ['class' => 'text-muted text-center py-3']);
        }

        echo html_writer::end_tag('div'); // card-body
        echo html_writer::end_tag('div'); // section-card
    }

    echo html_writer::end_tag('div'); // sections-container

    // Copy options
    echo html_writer::start_tag('div', ['class' => 'card mt-4']);
    echo html_writer::start_tag('div', ['class' => 'card-header bg-light']);
    echo html_writer::tag('h6', 'Copy Options', ['class' => 'mb-0']);
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    
    echo html_writer::start_tag('div', ['class' => 'row']);
    echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    
    echo html_writer::start_tag('div', ['class' => 'form-check mb-3']);
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'copy_settings',
        'id' => 'copy_settings',
        'class' => 'form-check-input',
        'checked' => 'checked',
        'value' => '1'
    ]);
    echo html_writer::tag('label', 'Copy form settings (dates, registration settings)', [
        'class' => 'form-check-label',
        'for' => 'copy_settings'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'form-check mb-3']);
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'create_cohort',
        'id' => 'create_cohort',
        'class' => 'form-check-input',
        'checked' => 'checked',
        'value' => '1'
    ]);
    echo html_writer::tag('label', 'Create cohort with same name as shortname', [
        'class' => 'form-check-label',
        'for' => 'create_cohort'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div'); // col-md-6
    
    echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    
    echo html_writer::start_tag('div', ['class' => 'form-check mb-3']);
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'preserve_order',
        'id' => 'preserve_order',
        'class' => 'form-check-input',
        'checked' => 'checked',
        'value' => '1'
    ]);
    echo html_writer::tag('label', 'Preserve section and field order', [
        'class' => 'form-check-label',
        'for' => 'preserve_order'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div'); // col-md-6
    echo html_writer::end_tag('div'); // row
    
    echo html_writer::end_tag('div'); // card-body
    echo html_writer::end_tag('div'); // card

    // Action buttons - Fixed to bottom
    echo html_writer::start_tag('div', ['class' => 'action-buttons mt-4 pt-4 border-top']);
    
    echo html_writer::start_tag('div', ['class' => 'd-flex justify-content-between align-items-center']);
    
    echo html_writer::start_tag('div');
    echo html_writer::tag('small', 'Note: Only selected fields will be copied to the new form.', [
        'class' => 'form-text text-muted'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'btn-group']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'name' => 'submitbutton',
        'value' => 'Create Copy',
        'class' => 'btn btn-primary',
        'id' => 'submitButton'
    ]);
    
    echo html_writer::tag('a', 'Back', [
        'href' => new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]),
        'class' => 'btn btn-secondary ml-2'
    ]);
    
    echo html_writer::tag('a', 'Cancel', [
        'href' => new moodle_url('/local/form/manageform.php'),
        'class' => 'btn btn-outline-secondary ml-2'
    ]);
    echo html_writer::end_tag('div');
    
    echo html_writer::end_tag('div'); // d-flex
    
    echo html_writer::end_tag('div'); // action-buttons

    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div'); // copy-form-container

    // Add custom CSS inline
    echo html_writer::tag('style', '
        .copy-form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .sections-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
            margin-bottom: 20px;
        }
        
        .section-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .field-item .card {
            transition: all 0.2s ease;
            border: 1px solid #dee2e6;
        }
        
        .field-item .card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .field-checkbox:checked + .field-details .field-label {
            color: #007bff;
        }
        
        .field-meta .badge {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
        
        .action-buttons {
            position: sticky;
            bottom: 0;
            background-color: white;
            z-index: 100;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .col-md-6, .col-lg-4 {
                margin-bottom: 10px;
            }
            
            .field-item .card {
                margin-bottom: 10px;
            }
            
            .btn-group .btn {
                margin-bottom: 5px;
            }
        }
    ');

    // Add JavaScript with shortname validation
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $(document).ready(function() {
                // Toggle all checkboxes
                $('#selectAll').click(function() {
                    $('.section-checkbox, .field-checkbox').prop('checked', true).trigger('change');
                });
                
                $('#deselectAll').click(function() {
                    $('.section-checkbox, .field-checkbox').prop('checked', false).trigger('change');
                });
                
                // Section checkbox controls fields
                $('.section-checkbox').change(function() {
                    var sectionId = $(this).data('section');
                    var isChecked = $(this).is(':checked');
                    $('.field-checkbox[data-section=\"' + sectionId + '\"]').prop('checked', isChecked);
                    updateFieldCount();
                });
                
                // Update section checkbox based on field selection
                $('.field-checkbox').change(function() {
                    var sectionId = $(this).data('section');
                    var totalFields = $('.field-checkbox[data-section=\"' + sectionId + '\"]').length;
                    var checkedFields = $('.field-checkbox[data-section=\"' + sectionId + '\"]:checked').length;
                    $('#section_' + sectionId).prop('checked', totalFields === checkedFields);
                    updateFieldCount();
                });
                
                // Form submission validation
                $('#copyFormAdvanced').submit(function(e) {
                    var selectedFields = $('.field-checkbox:checked').length;
                    var newFormName = $('#new_form_name').val().trim();
                    var newFormShortname = $('#new_form_shortname').val().trim();
                    
                    if (!newFormName) {
                        e.preventDefault();
                        alert('Please enter a form name.');
                        $('#new_form_name').focus();
                        return false;
                    }
                    
                    if (!newFormShortname) {
                        e.preventDefault();
                        alert('Please enter a form shortname.');
                        $('#new_form_shortname').focus();
                        return false;
                    }
                    
                    // Validate shortname format
                    var shortnameRegex = /^[a-zA-Z0-9_-]+$/;
                    if (!shortnameRegex.test(newFormShortname)) {
                        e.preventDefault();
                        alert('Shortname can only contain letters, numbers, hyphens, and underscores.');
                        $('#new_form_shortname').focus();
                        return false;
                    }
                    
                    if (selectedFields === 0) {
                        e.preventDefault();
                        alert('Please select at least one field to copy.');
                        return false;
                    }
                    
                    // Disable submit button to prevent double submission
                    $('#submitButton').prop('disabled', true).val('Creating...');
                });
                
                // Real-time field count
                function updateFieldCount() {
                    var selected = $('.field-checkbox:checked').length;
                    var total = $('.field-checkbox').length;
                    $('#fieldCount').text(selected + ' / ' + total + ' fields selected');
                    
                    // Update button text if no fields selected
                    if (selected === 0) {
                        $('#submitButton').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                    } else {
                        $('#submitButton').prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
                    }
                }
                
                // Highlight sections with selected fields
                function highlightSections() {
                    $('.section-card').each(function() {
                        var sectionId = $(this).find('.section-checkbox').data('section');
                        var selectedCount = $('.field-checkbox[data-section=\"' + sectionId + '\"]:checked').length;
                        var totalCount = $('.field-checkbox[data-section=\"' + sectionId + '\"]').length;
                        
                        if (selectedCount === totalCount && totalCount > 0) {
                            $(this).find('.card-header').css('background-color', '#d4edda');
                        } else if (selectedCount > 0) {
                            $(this).find('.card-header').css('background-color', '#fff3cd');
                        } else {
                            $(this).find('.card-header').css('background-color', '#f8f9fa');
                        }
                    });
                }
                
                $('.field-checkbox, .section-checkbox').change(function() {
                    updateFieldCount();
                    highlightSections();
                });
                
                // Initial calls
                updateFieldCount();
                highlightSections();
            });
        });
    ");
}

/**
 * Process form copy (Step 3)
 */
function process_form_copy()
{
    global $DB, $OUTPUT;

    // Get form data
    $existingformid = required_param('formid', PARAM_INT);
    $newname = required_param('new_form_name', PARAM_TEXT);
    $newshortname = required_param('new_form_shortname', PARAM_ALPHANUMEXT); // NEW PARAMETER
    $newdescription = optional_param('new_form_description', '', PARAM_TEXT);
    $selectedfields = optional_param_array('selected_fields', [], PARAM_INT);
    $copysettings = optional_param('copy_settings', 0, PARAM_INT);
    $createcohort = optional_param('create_cohort', 0, PARAM_INT); // NEW PARAMETER
    $preserveorder = optional_param('preserve_order', 0, PARAM_INT);

    // Validate
    if (empty($newname)) {
        \core\notification::error('Form name is required.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    if (empty($newshortname)) {
        \core\notification::error('Form shortname is required.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    // Validate shortname format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $newshortname)) {
        \core\notification::error('Shortname can only contain letters, numbers, hyphens, and underscores.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    if ($DB->record_exists('local_form', ['name' => $newname])) {
        \core\notification::error('A form with this name already exists.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    if ($DB->record_exists('local_form', ['shortname' => $newshortname])) {
        \core\notification::error('A form with this shortname already exists.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    // Check if cohort with this name already exists (if cohort creation is checked)
    if ($createcohort && $DB->record_exists('cohort', ['name' => $newshortname])) {
        \core\notification::error('A cohort with this name already exists. Please choose a different shortname.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    if (empty($selectedfields)) {
        \core\notification::error('Please select at least one field to copy.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }

    // Process the copy
    $newformid = duplicate_form_selected_fields(
        $existingformid,
        $newname,
        $newshortname, // NEW PARAMETER
        $newdescription,
        $selectedfields,
        $copysettings,
        $createcohort, // NEW PARAMETER
        $preserveorder
    );

    if ($newformid) {
        \core\notification::success('Form copied successfully! ' . 
            html_writer::link(
                new moodle_url('/local/form/editform.php', ['id' => $newformid]),
                'Edit the new form',
                ['class' => 'alert-link']
            )
        );
        redirect(new moodle_url('/local/form/manageform.php'));
    } else {
        \core\notification::error('Error copying form. Please try again.');
        redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
    }
}

/**
 * Duplicate form with selected fields
 */
function duplicate_form_selected_fields($existingformid, $newname, $newshortname, $newdescription, $selectedfields, $copysettings, $createcohort, $preserveorder)
{
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    try {
        // 1. Get the existing form
        $existingform = $DB->get_record('local_form', ['id' => $existingformid], '*', MUST_EXIST);

        // 2. Create new form record with shortname
        $newform = new stdClass();
        $newform->name = $newname;
        $newform->shortname = $newshortname; // NEW: Store shortname
        $newform->description = $newdescription;
        $newform->visible = 1;
        $newform->fc_registration = $copysettings ? $existingform->fc_registration : 0;
        $newform->sortorder = get_next_sort_order();
        $newform->course_edate = $copysettings ? $existingform->course_edate : null;
        $newform->course_sdate = $copysettings ? $existingform->course_sdate : null;
        $newform->timecreated = time();

        $newformid = $DB->insert_record('local_form', $newform);

        if (!$newformid) {
            throw new Exception('Failed to create new form record');
        }

        // 3. Create cohort if requested
        $cohortid = null;
        if ($createcohort && !empty($newshortname)) {
            $cohortid = create_cohort_from_form($newshortname, $newname);
            if ($cohortid) {
                \core\notification::info("Cohort '{$newshortname}' created successfully.");
            } else {
                \core\notification::warning("Form was created but cohort creation failed.");
            }
        }

        // 4. Get selected fields with their sections
        list($fieldinsql, $fieldparams) = $DB->get_in_or_equal($selectedfields);
        $sql = "SELECT fd.*, fs.section_title, fs.sort_order as section_order
                FROM {form_data} fd
                LEFT JOIN {form_sections} fs ON fd.section_id = fs.id
                WHERE fd.id {$fieldinsql} AND fd.formid = ?
                ORDER BY " . ($preserveorder ? "fs.sort_order, fd.sort_order" : "fd.id");
        
        $params = array_merge($fieldparams, [$existingformid]);
        $selectedfieldrecords = $DB->get_records_sql($sql, $params);

        // Group fields by section
        $sections = [];
        $sectionmapping = [];

        foreach ($selectedfieldrecords as $field) {
            if ($field->section_id && !isset($sections[$field->section_id])) {
                $sections[$field->section_id] = [
                    'title' => $field->section_title,
                    'order' => $field->section_order,
                    'fields' => []
                ];
            }
            $sections[$field->section_id]['fields'][] = $field;
        }

        // 5. Create new sections
        foreach ($sections as $oldsectionid => $sectiondata) {
            $newsection = new stdClass();
            $newsection->formid = $newformid;
            $newsection->section_title = $sectiondata['title'];
            $newsection->sort_order = $preserveorder ? $sectiondata['order'] : count($sectionmapping) + 1;

            $newsectionid = $DB->insert_record('form_sections', $newsection);
            $sectionmapping[$oldsectionid] = $newsectionid;
        }

        // 6. Create new fields
        foreach ($selectedfieldrecords as $oldfield) {
            $newfield = new stdClass();
            $newfield->formid = $newformid;
            $newfield->section_id = isset($sectionmapping[$oldfield->section_id]) ? $sectionmapping[$oldfield->section_id] : null;
            $newfield->formname = $oldfield->formname;
            $newfield->formtype = $oldfield->formtype;
            $newfield->formlabel = $oldfield->formlabel;
            $newfield->fieldoption = $oldfield->fieldoption;
            $newfield->required = $oldfield->required;
            $newfield->layout = $oldfield->layout;
            $newfield->table_index = $oldfield->table_index;
            $newfield->format = $oldfield->format;
            $newfield->row_index = $oldfield->row_index;
            $newfield->col_index = $oldfield->col_index;
            $newfield->header = $oldfield->header;
            $newfield->field_type = $oldfield->field_type;
            $newfield->field_title = $oldfield->field_title;
            $newfield->field_url = $oldfield->field_url;
            $newfield->field_options = $oldfield->field_options;
            $newfield->field_checkbox_options = $oldfield->field_checkbox_options;
            $newfield->field_radio_options = $oldfield->field_radio_options;
            $newfield->sort_order = $preserveorder ? $oldfield->sort_order : 0;

            if (!$DB->insert_record('form_data', $newfield)) {
                throw new Exception('Failed to create field: ' . $oldfield->formlabel);
            }
        }

        $transaction->allow_commit();

        // Log the action
        $eventdata = [
            'context' => context_system::instance(),
            'objectid' => $newformid,
            'other' => [
                'sourceformid' => $existingformid,
                'sourceformname' => $existingform->name,
                'newformname' => $newname,
                'newformshortname' => $newshortname,
                'fieldscopied' => count($selectedfields),
                'cohortcreated' => $createcohort ? 'yes' : 'no'
            ]
        ];

        return $newformid;

    } catch (Exception $e) {
        $transaction->rollback($e);
        error_log('Error copying form: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a cohort with the same name as form shortname
 * 
 * @param string $shortname Form shortname (will be used as cohort name)
 * @param string $formname Form name (will be used as cohort description)
 * @return int|bool Cohort ID or false on failure
 */
function create_cohort_from_form($shortname, $formname)
{
    global $DB, $CFG;
    
    require_once($CFG->dirroot . '/cohort/lib.php');
    
    // Check if cohort already exists
    if ($DB->record_exists('cohort', ['name' => $shortname])) {
        \core\notification::warning("Cohort '{$shortname}' already exists. Using existing cohort.");
        return $DB->get_field('cohort', 'id', ['name' => $shortname]);
    }
    
    try {
        $context = context_system::instance();
        
        $cohort = new stdClass();
        $cohort->name = $shortname;
        $cohort->idnumber = 'form_' . $shortname;
        $cohort->description = $shortname;
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->contextid = $context->id;
        $cohort->component = 'local_form';
        $cohort->timecreated = time();
        $cohort->timemodified = time();
        
        $cohortid = cohort_add_cohort($cohort);
        
        if ($cohortid) {
            return $cohortid;
        } else {
            return false;
        }
    } catch (Exception $e) {
        error_log('Error creating cohort: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get next sort order value for forms
 */
function get_next_sort_order()
{
    global $DB;
    $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_form} WHERE visible = 1');
    return $maxsort ? $maxsort + 1 : 1;
}
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
 * Advanced copy form with selective field copying
 *
 * @package    local_form
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// require_once('../../config.php');
// require_once('lib.php');
// require_once($CFG->libdir . '/formslib.php');

// global $CFG, $USER, $PAGE, $DB;

// require_login();
// if (!is_siteadmin()) {
//     throw new moodle_exception('Access denied');
// }

// $step = optional_param('step', 1, PARAM_INT);
// $formid = optional_param('formid', 0, PARAM_INT);
// $sesskey = optional_param('sesskey', '', PARAM_TEXT);

// $context = context_system::instance();
// $PAGE->set_url('/local/form/copyform_advanced.php');
// $PAGE->set_context($context);
// $PAGE->set_title('Advanced Form Copy');
// $PAGE->set_heading('Copy Form with Selected Fields');
// // $PAGE->set_pagelayout('standard');
// $PAGE->navbar->add('Advanced Copy');

// // Add custom CSS
// $PAGE->requires->css('/local/form/styles/copyform.css');

// echo $OUTPUT->header();

// if ($step == 1) {
//     display_form_selection();
// } else if ($step == 2 && $formid) {
//     display_field_selection($formid);
// } else if ($step == 3 && confirm_sesskey()) {
//     process_form_copy();
// } else {
//     redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]));
// }

// echo $OUTPUT->footer();

// /**
//  * Display form selection (Step 1)
//  */
// function display_form_selection()
// {
//     global $DB, $OUTPUT;

//     $forms = $DB->get_records('local_form', ['visible' => 1], 'name ASC');

//     if (empty($forms)) {
//         echo $OUTPUT->notification('No forms available to copy.', 'warning');
//         echo $OUTPUT->continue_button(new moodle_url('/local/form/manageform.php'));
//         return;
//     }

//     echo html_writer::tag('h3', 'Step 1: Select Form to Copy', ['class' => 'mb-4']);
//     echo html_writer::tag('p', 'Choose a form to copy from the list below.', ['class' => 'text-muted mb-4']);

//     // Create a responsive table similar to manageform.php
//     echo html_writer::start_tag('div', ['class' => 'table-responsive']);
//     echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'width' => '100%']);
    
//     echo html_writer::start_tag('thead');
//     echo html_writer::start_tag('tr');
//     echo html_writer::tag('th', 'Form Name', ['width' => '30%']);
//     echo html_writer::tag('th', 'Description', ['width' => '40%']);
//     echo html_writer::tag('th', 'Sections', ['class' => 'text-center', 'width' => '10%']);
//     echo html_writer::tag('th', 'Fields', ['class' => 'text-center', 'width' => '10%']);
//     echo html_writer::tag('th', 'Action', ['class' => 'text-center', 'width' => '10%']);
//     echo html_writer::end_tag('tr');
//     echo html_writer::end_tag('thead');
    
//     echo html_writer::start_tag('tbody');
    
//     foreach ($forms as $form) {
//         $fieldcount = $DB->count_records('form_data', ['formid' => $form->id]);
//         $sectioncount = $DB->count_records('form_sections', ['formid' => $form->id]);

//         $actionurl = new moodle_url('/local/form/copyform_advanced.php', [
//             'step' => 2,
//             'formid' => $form->id
//         ]);
        
//         echo html_writer::start_tag('tr');
        
//         // Form Name
//         echo html_writer::start_tag('td');
//         echo html_writer::tag('strong', $form->name);
//         echo html_writer::end_tag('td');
        
//         // Description
//         echo html_writer::start_tag('td');
//         echo shorten_text($form->description, 80);
//         echo html_writer::end_tag('td');
        
//         // Sections count
//         echo html_writer::start_tag('td', ['class' => 'text-center']);
//         echo html_writer::tag('span', $sectioncount, ['class' => 'badge badge-info']);
//         echo html_writer::end_tag('td');
        
//         // Fields count
//         echo html_writer::start_tag('td', ['class' => 'text-center']);
//         echo html_writer::tag('span', $fieldcount, ['class' => 'badge badge-secondary']);
//         echo html_writer::end_tag('td');
        
//         // Action
//         echo html_writer::start_tag('td', ['class' => 'text-center']);
//         echo html_writer::link($actionurl, 'Select', [
//             'class' => 'btn btn-primary btn-sm',
//             'title' => 'Select this form for copying'
//         ]);
//         echo html_writer::end_tag('td');
        
//         echo html_writer::end_tag('tr');
//     }
    
//     echo html_writer::end_tag('tbody');
//     echo html_writer::end_tag('table');
//     echo html_writer::end_tag('div'); // table-responsive

//     // Simple copy option
//     echo html_writer::start_tag('div', ['class' => 'mt-4 pt-3 border-top']);
//     echo html_writer::tag('h5', 'Quick Copy Option', ['class' => 'mb-3']);
//     echo html_writer::tag('p', 'To copy entire form without selecting fields:', ['class' => 'text-muted mb-3']);
//     $simplecopyurl = new moodle_url('/local/form/copyform.php');
//     echo html_writer::link($simplecopyurl, 'Use Simple Copy', ['class' => 'btn btn-secondary']);
//     echo html_writer::end_tag('div');
// }


// /**
//  * Display field selection (Step 2)
//  */
// function display_field_selection($formid)
// {
//     global $DB, $OUTPUT, $PAGE;

//     $form = $DB->get_record('local_form', ['id' => $formid], '*', MUST_EXIST);
//     $sections = $DB->get_records('form_sections', ['formid' => $formid], 'sort_order ASC');

//     if (empty($sections)) {
//         echo $OUTPUT->notification('The selected form has no sections.', 'warning');
//         echo $OUTPUT->continue_button(new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]));
//         return;
//     }

//     echo html_writer::start_tag('div', ['class' => 'copy-form-container']);
//     echo html_writer::tag('h3', 'Step 2: Select Fields to Copy');
//     echo html_writer::tag('div', 'Copying from: ' . html_writer::tag('strong', $form->name), ['class' => 'mb-4']);
    
//     // Field selection summary
//     echo html_writer::start_tag('div', ['class' => 'alert alert-info mb-4']);
//     echo html_writer::tag('span', '0 / 0 fields selected', ['id' => 'fieldCount', 'class' => 'font-weight-bold']);
//     echo html_writer::end_tag('div');

//     // Start form
//     $actionurl = new moodle_url('/local/form/copyform_advanced.php', [
//         'step' => 3,
//         'formid' => $formid,
//         'sesskey' => sesskey()
//     ]);

//     echo html_writer::start_tag('form', [
//         'method' => 'post',
//         'action' => $actionurl,
//         'id' => 'copyFormAdvanced',
//         'class' => 'copy-form-advanced'
//     ]);

//     // New form details card
//     echo html_writer::start_tag('div', ['class' => 'card mb-4']);
//     echo html_writer::start_tag('div', ['class' => 'card-header bg-primary text-white']);
//     echo html_writer::tag('h5', 'New Form Details', ['class' => 'mb-0']);
//     echo html_writer::end_tag('div');

//     echo html_writer::start_tag('div', ['class' => 'card-body']);

//     // New form name
//     echo html_writer::start_tag('div', ['class' => 'form-group row']);
//     echo html_writer::tag('label', 'New Form Name*', [
//         'class' => 'col-md-3 col-form-label font-weight-bold',
//         'for' => 'new_form_name'
//     ]);
//     echo html_writer::start_tag('div', ['class' => 'col-md-9']);
//     echo html_writer::empty_tag('input', [
//         'type' => 'text',
//         'name' => 'new_form_name',
//         'id' => 'new_form_name',
//         'class' => 'form-control',
//         'required' => 'required',
//         'placeholder' => 'Enter unique form name',
//         'value' => $form->name . ' (Copy)'
//     ]);
//     echo html_writer::tag('small', 'Maximum 255 characters. Must be unique.', ['class' => 'form-text text-muted']);
//     echo html_writer::end_tag('div');
//     echo html_writer::end_tag('div');

//     // New form description
//     echo html_writer::start_tag('div', ['class' => 'form-group row']);
//     echo html_writer::tag('label', 'New Form Description', [
//         'class' => 'col-md-3 col-form-label font-weight-bold',
//         'for' => 'new_form_description'
//     ]);
//     echo html_writer::start_tag('div', ['class' => 'col-md-9']);
//     echo html_writer::tag('textarea', $form->description, [
//         'name' => 'new_form_description',
//         'id' => 'new_form_description',
//         'class' => 'form-control',
//         'rows' => '3',
//         'placeholder' => 'Enter form description'
//     ]);
//     echo html_writer::end_tag('div');
//     echo html_writer::end_tag('div');

//     echo html_writer::end_tag('div'); // card-body
//     echo html_writer::end_tag('div'); // card

//     // Bulk actions
//     echo html_writer::start_tag('div', ['class' => 'card mb-4']);
//     echo html_writer::start_tag('div', ['class' => 'card-header bg-info text-white d-flex justify-content-between align-items-center']);
//     echo html_writer::tag('h5', 'Select Sections and Fields', ['class' => 'mb-0']);
    
//     echo html_writer::start_tag('div', ['class' => 'btn-group btn-group-sm']);
//     echo html_writer::tag('button', 'Select All', [
//         'type' => 'button',
//         'class' => 'btn btn-light',
//         'id' => 'selectAll'
//     ]);
//     echo html_writer::tag('button', 'Deselect All', [
//         'type' => 'button',
//         'class' => 'btn btn-light',
//         'id' => 'deselectAll'
//     ]);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::end_tag('div'); // card-header
//     echo html_writer::end_tag('div'); // card

//     // Sections container
//     echo html_writer::start_tag('div', ['class' => 'sections-container']);

//     $sectionindex = 0;
//     foreach ($sections as $section) {
//         $sectionindex++;
//         $fields = $DB->get_records('form_data', [
//             'formid' => $formid,
//             'section_id' => $section->id
//         ], 'sort_order ASC');

//         echo html_writer::start_tag('div', ['class' => 'section-card card mb-3']);
//         echo html_writer::start_tag('div', ['class' => 'card-header d-flex align-items-center']);

//         // Section checkbox
//         echo html_writer::start_tag('div', ['class' => 'form-check mr-3']);
//         echo html_writer::empty_tag('input', [
//             'type' => 'checkbox',
//             'class' => 'section-checkbox form-check-input',
//             'id' => 'section_' . $section->id,
//             'data-section' => $section->id,
//             'checked' => 'checked'
//         ]);
//         echo html_writer::tag('label', '', [
//             'class' => 'form-check-label',
//             'for' => 'section_' . $section->id
//         ]);
//         echo html_writer::end_tag('div');
        
//         // Section title
//         echo html_writer::tag('h6', "Section {$sectionindex}: {$section->section_title}", [
//             'class' => 'mb-0 flex-grow-1'
//         ]);
        
//         // Section field count
//         if ($fields) {
//             echo html_writer::tag('span', count($fields) . ' fields', [
//                 'class' => 'badge badge-primary ml-2'
//             ]);
//         }
        
//         echo html_writer::end_tag('div'); // card-header

//         echo html_writer::start_tag('div', ['class' => 'card-body p-3', 'id' => 'section_body_' . $section->id]);

//         if ($fields) {
//             echo html_writer::start_tag('div', ['class' => 'row g-3']); // Using g-3 for gutter spacing
            
//             foreach ($fields as $field) {
//                 echo html_writer::start_tag('div', ['class' => 'col-12 col-md-6 col-lg-4']); // Responsive columns
//                 echo html_writer::start_tag('div', ['class' => 'field-item']);
//                 echo html_writer::start_tag('div', ['class' => 'card h-100 border']);
                
//                 // Field checkbox and details
//                 echo html_writer::start_tag('div', ['class' => 'card-body p-3']);
//                 echo html_writer::start_tag('div', ['class' => 'form-check d-flex align-items-start']);
                
//                 echo html_writer::empty_tag('input', [
//                     'type' => 'checkbox',
//                     'class' => 'field-checkbox form-check-input mt-1',
//                     'name' => 'selected_fields[]',
//                     'id' => 'field_' . $field->id,
//                     'value' => $field->id,
//                     'data-section' => $section->id,
//                     'checked' => 'checked'
//                 ]);
                
//                 // Field details
//                 echo html_writer::start_tag('div', ['class' => 'field-details ml-2 flex-grow-1']);
                
//                 // Field label
//                 echo html_writer::tag('label', 
//                     html_writer::tag('strong', $field->formlabel), [
//                         'class' => 'form-check-label field-label d-block',
//                         'for' => 'field_' . $field->id,
//                         'style' => 'cursor: pointer;'
//                     ]);
                
//                 // Field metadata
//                 echo html_writer::start_tag('div', ['class' => 'field-meta small text-muted mt-1']);
                
//                 // Field type
//                 echo html_writer::tag('span', "Type: " . html_writer::tag('span', $field->formtype, ['class' => 'badge badge-light']), 
//                     ['class' => 'd-block mb-1']);
                
//                 // Badges for field properties
//                 echo html_writer::start_tag('div', ['class' => 'field-badges']);
//                 if ($field->required) {
//                     echo html_writer::tag('span', 'Required', ['class' => 'badge badge-warning mr-1 mb-1']);
//                 }
                
//                 if ($field->formtype === 'select' || $field->formtype === 'radio' || $field->formtype === 'checkbox') {
//                     if (!empty($field->fieldoption)) {
//                         $optionscount = substr_count($field->fieldoption, ',') + 1;
//                         echo html_writer::tag('span', "{$optionscount} options", ['class' => 'badge badge-info mr-1 mb-1']);
//                     }
//                 }
//                 echo html_writer::end_tag('div'); // field-badges
                
//                 echo html_writer::end_tag('div'); // field-meta
//                 echo html_writer::end_tag('div'); // field-details
//                 echo html_writer::end_tag('div'); // form-check
//                 echo html_writer::end_tag('div'); // card-body
//                 echo html_writer::end_tag('div'); // card
//                 echo html_writer::end_tag('div'); // field-item
//                 echo html_writer::end_tag('div'); // col
//             }
            
//             echo html_writer::end_tag('div'); // row
//         } else {
//             echo html_writer::tag('p', 'No fields in this section.', ['class' => 'text-muted text-center py-3']);
//         }

//         echo html_writer::end_tag('div'); // card-body
//         echo html_writer::end_tag('div'); // section-card
//     }

//     echo html_writer::end_tag('div'); // sections-container

//     // Copy options
//     echo html_writer::start_tag('div', ['class' => 'card mt-4']);
//     echo html_writer::start_tag('div', ['class' => 'card-header bg-light']);
//     echo html_writer::tag('h6', 'Copy Options', ['class' => 'mb-0']);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::start_tag('div', ['class' => 'card-body']);
    
//     echo html_writer::start_tag('div', ['class' => 'row']);
//     echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    
//     echo html_writer::start_tag('div', ['class' => 'form-check mb-3']);
//     echo html_writer::empty_tag('input', [
//         'type' => 'checkbox',
//         'name' => 'copy_settings',
//         'id' => 'copy_settings',
//         'class' => 'form-check-input',
//         'checked' => 'checked',
//         'value' => '1'
//     ]);
//     echo html_writer::tag('label', 'Copy form settings (dates, registration settings)', [
//         'class' => 'form-check-label',
//         'for' => 'copy_settings'
//     ]);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::end_tag('div'); // col-md-6
    
//     echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    
//     echo html_writer::start_tag('div', ['class' => 'form-check mb-3']);
//     echo html_writer::empty_tag('input', [
//         'type' => 'checkbox',
//         'name' => 'preserve_order',
//         'id' => 'preserve_order',
//         'class' => 'form-check-input',
//         'checked' => 'checked',
//         'value' => '1'
//     ]);
//     echo html_writer::tag('label', 'Preserve section and field order', [
//         'class' => 'form-check-label',
//         'for' => 'preserve_order'
//     ]);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::end_tag('div'); // col-md-6
//     echo html_writer::end_tag('div'); // row
    
//     echo html_writer::end_tag('div'); // card-body
//     echo html_writer::end_tag('div'); // card

//     // Action buttons - Fixed to bottom
//     echo html_writer::start_tag('div', ['class' => 'action-buttons mt-4 pt-4 border-top']);
    
//     echo html_writer::start_tag('div', ['class' => 'd-flex justify-content-between align-items-center']);
    
//     echo html_writer::start_tag('div');
//     echo html_writer::tag('small', 'Note: Only selected fields will be copied to the new form.', [
//         'class' => 'form-text text-muted'
//     ]);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::start_tag('div', ['class' => 'btn-group']);
//     echo html_writer::empty_tag('input', [
//         'type' => 'submit',
//         'name' => 'submitbutton',
//         'value' => 'Create Copy',
//         'class' => 'btn btn-primary',
//         'id' => 'submitButton'
//     ]);
    
//     echo html_writer::tag('a', 'Back', [
//         'href' => new moodle_url('/local/form/copyform_advanced.php', ['step' => 1]),
//         'class' => 'btn btn-secondary ml-2'
//     ]);
    
//     echo html_writer::tag('a', 'Cancel', [
//         'href' => new moodle_url('/local/form/manageform.php'),
//         'class' => 'btn btn-outline-secondary ml-2'
//     ]);
//     echo html_writer::end_tag('div');
    
//     echo html_writer::end_tag('div'); // d-flex
    
//     echo html_writer::end_tag('div'); // action-buttons

//     echo html_writer::end_tag('form');
//     echo html_writer::end_tag('div'); // copy-form-container

//     // Add custom CSS inline
//     echo html_writer::tag('style', '
//         .copy-form-container {
//             max-width: 1200px;
//             margin: 0 auto;
//             padding: 0 15px;
//         }
        
//         .sections-container {
//             max-height: 500px;
//             overflow-y: auto;
//             padding-right: 10px;
//             margin-bottom: 20px;
//         }
        
//         .section-card .card-header {
//             background-color: #f8f9fa;
//             border-bottom: 2px solid #dee2e6;
//         }
        
//         .field-item .card {
//             transition: all 0.2s ease;
//             border: 1px solid #dee2e6;
//         }
        
//         .field-item .card:hover {
//             border-color: #007bff;
//             box-shadow: 0 2px 4px rgba(0,0,0,0.1);
//         }
        
//         .field-checkbox:checked + .field-details .field-label {
//             color: #007bff;
//         }
        
//         .field-meta .badge {
//             font-size: 0.75em;
//             padding: 0.25em 0.5em;
//         }
        
//         .action-buttons {
//             position: sticky;
//             bottom: 0;
//             background-color: white;
//             z-index: 100;
//         }
        
//         /* Responsive adjustments */
//         @media (max-width: 768px) {
//             .col-md-6, .col-lg-4 {
//                 margin-bottom: 10px;
//             }
            
//             .field-item .card {
//                 margin-bottom: 10px;
//             }
            
//             .btn-group .btn {
//                 margin-bottom: 5px;
//             }
//         }
//     ');

//     // Add JavaScript
//     $PAGE->requires->js_amd_inline("
//         require(['jquery'], function($) {
//             $(document).ready(function() {
//                 // Toggle all checkboxes
//                 $('#selectAll').click(function() {
//                     $('.section-checkbox, .field-checkbox').prop('checked', true).trigger('change');
//                 });
                
//                 $('#deselectAll').click(function() {
//                     $('.section-checkbox, .field-checkbox').prop('checked', false).trigger('change');
//                 });
                
//                 // Section checkbox controls fields
//                 $('.section-checkbox').change(function() {
//                     var sectionId = $(this).data('section');
//                     var isChecked = $(this).is(':checked');
//                     $('.field-checkbox[data-section=\"' + sectionId + '\"]').prop('checked', isChecked);
//                     updateFieldCount();
//                 });
                
//                 // Update section checkbox based on field selection
//                 $('.field-checkbox').change(function() {
//                     var sectionId = $(this).data('section');
//                     var totalFields = $('.field-checkbox[data-section=\"' + sectionId + '\"]').length;
//                     var checkedFields = $('.field-checkbox[data-section=\"' + sectionId + '\"]:checked').length;
//                     $('#section_' + sectionId).prop('checked', totalFields === checkedFields);
//                     updateFieldCount();
//                 });
                
//                 // Form submission validation
//                 $('#copyFormAdvanced').submit(function(e) {
//                     var selectedFields = $('.field-checkbox:checked').length;
//                     var newFormName = $('#new_form_name').val().trim();
                    
//                     if (!newFormName) {
//                         e.preventDefault();
//                         alert('Please enter a form name.');
//                         $('#new_form_name').focus();
//                         return false;
//                     }
                    
//                     if (selectedFields === 0) {
//                         e.preventDefault();
//                         alert('Please select at least one field to copy.');
//                         return false;
//                     }
                    
//                     // Disable submit button to prevent double submission
//                     $('#submitButton').prop('disabled', true).val('Creating...');
//                 });
                
//                 // Real-time field count
//                 function updateFieldCount() {
//                     var selected = $('.field-checkbox:checked').length;
//                     var total = $('.field-checkbox').length;
//                     $('#fieldCount').text(selected + ' / ' + total + ' fields selected');
                    
//                     // Update button text if no fields selected
//                     if (selected === 0) {
//                         $('#submitButton').prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
//                     } else {
//                         $('#submitButton').prop('disabled', false).addClass('btn-primary').removeClass('btn-secondary');
//                     }
//                 }
                
//                 // Highlight sections with selected fields
//                 function highlightSections() {
//                     $('.section-card').each(function() {
//                         var sectionId = $(this).find('.section-checkbox').data('section');
//                         var selectedCount = $('.field-checkbox[data-section=\"' + sectionId + '\"]:checked').length;
//                         var totalCount = $('.field-checkbox[data-section=\"' + sectionId + '\"]').length;
                        
//                         if (selectedCount === totalCount && totalCount > 0) {
//                             $(this).find('.card-header').css('background-color', '#d4edda');
//                         } else if (selectedCount > 0) {
//                             $(this).find('.card-header').css('background-color', '#fff3cd');
//                         } else {
//                             $(this).find('.card-header').css('background-color', '#f8f9fa');
//                         }
//                     });
//                 }
                
//                 $('.field-checkbox, .section-checkbox').change(function() {
//                     updateFieldCount();
//                     highlightSections();
//                 });
                
//                 // Initial calls
//                 updateFieldCount();
//                 highlightSections();
//             });
//         });
//     ");
// }

// /**
//  * Process form copy (Step 3)
//  */
// function process_form_copy()
// {
//     global $DB, $OUTPUT;

//     // Get form data
//     $existingformid = required_param('formid', PARAM_INT);
//     $newname = required_param('new_form_name', PARAM_TEXT);
//     $newdescription = optional_param('new_form_description', '', PARAM_TEXT);
//     $selectedfields = optional_param_array('selected_fields', [], PARAM_INT);
//     $copysettings = optional_param('copy_settings', 0, PARAM_INT);
//     $preserveorder = optional_param('preserve_order', 0, PARAM_INT);

//     // Validate
//     if (empty($newname)) {
//         \core\notification::error('Form name is required.');
//         redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
//     }

//     if ($DB->record_exists('local_form', ['name' => $newname])) {
//         \core\notification::error('A form with this name already exists.');
//         redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
//     }

//     if (empty($selectedfields)) {
//         \core\notification::error('Please select at least one field to copy.');
//         redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
//     }

//     // Process the copy
//     $newformid = duplicate_form_selected_fields(
//         $existingformid,
//         $newname,
//         $newdescription,
//         $selectedfields,
//         $copysettings,
//         $preserveorder
//     );

//     if ($newformid) {
//         \core\notification::success('Form copied successfully! ' . 
//             html_writer::link(
//                 new moodle_url('/local/form/editform.php', ['id' => $newformid]),
//                 'Edit the new form',
//                 ['class' => 'alert-link']
//             )
//         );
//         redirect(new moodle_url('/local/form/manageform.php'));
//     } else {
//         \core\notification::error('Error copying form. Please try again.');
//         redirect(new moodle_url('/local/form/copyform_advanced.php', ['step' => 2, 'formid' => $existingformid]));
//     }
// }

// /**
//  * Duplicate form with selected fields
//  */
// function duplicate_form_selected_fields($existingformid, $newname, $newdescription, $selectedfields, $copysettings, $preserveorder)
// {
//     global $DB;

//     $transaction = $DB->start_delegated_transaction();

//     try {
//         // 1. Get the existing form
//         $existingform = $DB->get_record('local_form', ['id' => $existingformid], '*', MUST_EXIST);

//         // 2. Create new form record
//         $newform = new stdClass();
//         $newform->name = $newname;
//         $newform->description = $newdescription;
//         $newform->visible = 1;
//         $newform->fc_registration = $copysettings ? $existingform->fc_registration : 0;
//         $newform->sortorder = get_next_sort_order();
//         $newform->course_edate = $copysettings ? $existingform->course_edate : null;
//         $newform->course_sdate = $copysettings ? $existingform->course_sdate : null;
//         $newform->timecreated = time();

//         $newformid = $DB->insert_record('local_form', $newform);

//         if (!$newformid) {
//             throw new Exception('Failed to create new form record');
//         }

//         // 3. Get selected fields with their sections
//         list($fieldinsql, $fieldparams) = $DB->get_in_or_equal($selectedfields);
//         $sql = "SELECT fd.*, fs.section_title, fs.sort_order as section_order
//                 FROM {form_data} fd
//                 LEFT JOIN {form_sections} fs ON fd.section_id = fs.id
//                 WHERE fd.id {$fieldinsql} AND fd.formid = ?
//                 ORDER BY " . ($preserveorder ? "fs.sort_order, fd.sort_order" : "fd.id");
        
//         $params = array_merge($fieldparams, [$existingformid]);
//         $selectedfieldrecords = $DB->get_records_sql($sql, $params);

//         // Group fields by section
//         $sections = [];
//         $sectionmapping = [];

//         foreach ($selectedfieldrecords as $field) {
//             if ($field->section_id && !isset($sections[$field->section_id])) {
//                 $sections[$field->section_id] = [
//                     'title' => $field->section_title,
//                     'order' => $field->section_order,
//                     'fields' => []
//                 ];
//             }
//             $sections[$field->section_id]['fields'][] = $field;
//         }

//         // 4. Create new sections
//         foreach ($sections as $oldsectionid => $sectiondata) {
//             $newsection = new stdClass();
//             $newsection->formid = $newformid;
//             $newsection->section_title = $sectiondata['title'];
//             $newsection->sort_order = $preserveorder ? $sectiondata['order'] : count($sectionmapping) + 1;

//             $newsectionid = $DB->insert_record('form_sections', $newsection);
//             $sectionmapping[$oldsectionid] = $newsectionid;
//         }

//         // 5. Create new fields
//         foreach ($selectedfieldrecords as $oldfield) {
//             $newfield = new stdClass();
//             $newfield->formid = $newformid;
//             $newfield->section_id = isset($sectionmapping[$oldfield->section_id]) ? $sectionmapping[$oldfield->section_id] : null;
//             $newfield->formname = $oldfield->formname;
//             $newfield->formtype = $oldfield->formtype;
//             $newfield->formlabel = $oldfield->formlabel;
//             $newfield->fieldoption = $oldfield->fieldoption;
//             $newfield->required = $oldfield->required;
//             $newfield->layout = $oldfield->layout;
//             $newfield->table_index = $oldfield->table_index;
//             $newfield->format = $oldfield->format;
//             $newfield->row_index = $oldfield->row_index;
//             $newfield->col_index = $oldfield->col_index;
//             $newfield->header = $oldfield->header;
//             $newfield->field_type = $oldfield->field_type;
//             $newfield->field_title = $oldfield->field_title;
//             $newfield->field_url = $oldfield->field_url;
//             $newfield->field_options = $oldfield->field_options;
//             $newfield->field_checkbox_options = $oldfield->field_checkbox_options;
//             $newfield->field_radio_options = $oldfield->field_radio_options;
//             $newfield->sort_order = $preserveorder ? $oldfield->sort_order : 0;

//             if (!$DB->insert_record('form_data', $newfield)) {
//                 throw new Exception('Failed to create field: ' . $oldfield->formlabel);
//             }
//         }

//         $transaction->allow_commit();

//         // Log the action
//         $eventdata = [
//             'context' => context_system::instance(),
//             'objectid' => $newformid,
//             'other' => [
//                 'sourceformid' => $existingformid,
//                 'sourceformname' => $existingform->name,
//                 'newformname' => $newname,
//                 'fieldscopied' => count($selectedfields)
//             ]
//         ];

//         return $newformid;

//     } catch (Exception $e) {
//         $transaction->rollback($e);
//         error_log('Error copying form: ' . $e->getMessage());
//         return false;
//     }
// }

// /**
//  * Get next sort order value for forms
//  */
// function get_next_sort_order()
// {
//     global $DB;
//     $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_form} WHERE visible = 1');
//     return $maxsort ? $maxsort + 1 : 1;
// }
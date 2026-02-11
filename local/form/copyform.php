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
 * Copy existing form to create a new form
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

/**
 * Form class for copying existing form
 */
class local_form_copy_form extends moodleform
{

    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Form name
        $mform->addElement('text', 'new_form_name', 'New Form Name');
        $mform->setType('new_form_name', PARAM_TEXT);
        $mform->addRule('new_form_name', 'Required', 'required', null, 'client');
        $mform->addRule('new_form_name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('new_form_name', 'newformname', 'local_form');

        // Form shortname - NEW FIELD
        $mform->addElement('text', 'new_form_shortname', 'New Form Shortname');
        $mform->setType('new_form_shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('new_form_shortname', 'Required', 'required', null, 'client');
        $mform->addRule('new_form_shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('new_form_shortname', 'newformshortname', 'local_form');

        // Form description
        $mform->addElement('textarea', 'new_form_description', 'New Form Description', 'rows="3"');
        $mform->setType('new_form_description', PARAM_TEXT);
        $mform->addHelpButton('new_form_description', 'newformdescription', 'local_form');

        // Create cohort option - NEW FIELD
        $mform->addElement('advcheckbox', 'create_cohort', 'Create Cohort', 
            'Create a cohort with the same name as form shortname');
        $mform->setType('create_cohort', PARAM_BOOL);
        $mform->setDefault('create_cohort', 1); // Default to checked
        $mform->addHelpButton('create_cohort', 'createcohort', 'local_form');

        // Select existing form
        $forms = $DB->get_records('local_form', ['visible' => 1], 'name ASC');
        $formoptions = ['' => '-- Select a Form --'];

        foreach ($forms as $form) {
            $formoptions[$form->id] = $form->name;
        }

        $mform->addElement('select', 'existing_form', 'Select Existing Form to Copy', $formoptions);
        $mform->setType('existing_form', PARAM_INT);
        $mform->addRule('existing_form', 'Required', 'required', null, 'client');
        $mform->addHelpButton('existing_form', 'selectexistingform', 'local_form');

        // Add help text
        $mform->addElement(
            'static',
            'help',
            '',
            '<div class="alert alert-info">' .
                'This will create a new form with all sections and fields copied from the selected existing form.' .
                '<br><strong>Note:</strong> Cohort will be created with the same name as the form shortname.' .
                '</div>'
        );

        // Action buttons
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Copy Form');
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        // Check if form name already exists
        global $DB;
        if (!empty($data['new_form_name']) && $DB->record_exists('local_form', ['name' => $data['new_form_name']])) {
            $errors['new_form_name'] = 'A form with this name already exists. Please choose a different name.';
        }

        // Check if form shortname already exists
        if (!empty($data['new_form_shortname']) && $DB->record_exists('local_form', ['shortname' => $data['new_form_shortname']])) {
            $errors['new_form_shortname'] = 'A form with this shortname already exists. Please choose a different shortname.';
        }

        // Check if cohort with this name already exists (if cohort creation is checked)
        if (!empty($data['create_cohort']) && !empty($data['new_form_shortname'])) {
            if ($DB->record_exists('cohort', ['name' => $data['new_form_shortname']])) {
                $errors['new_form_shortname'] = 'A cohort with this name already exists. Please choose a different shortname.';
            }
        }

        // Validate shortname format
        if (!empty($data['new_form_shortname']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $data['new_form_shortname'])) {
            $errors['new_form_shortname'] = 'Shortname can only contain letters, numbers, hyphens, and underscores.';
        }

        return $errors;
    }
}

// Page setup
$context = context_system::instance();
$PAGE->set_url('/local/form/copyform.php');
$PAGE->set_context($context);
$PAGE->set_title('Copy Existing Form');
$PAGE->set_heading('Copy Existing Form to New Form');
$PAGE->navbar->add('Manage Forms', new moodle_url('/local/form/manageform.php'));
$PAGE->navbar->add('Copy Form');

// Output header
echo $OUTPUT->header();

// Initialize form
$mform = new local_form_copy_form();

// Form processing
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/form/manageform.php'));
} else if ($data = $mform->get_data()) {
    // Process form data
    $newformid = duplicate_form_with_fields(
        $data->existing_form, 
        $data->new_form_name, 
        $data->new_form_shortname, // Added shortname
        $data->new_form_description,
        $data->create_cohort // Added cohort creation flag
    );

    if ($newformid) {
        \core\notification::success('Form duplicated successfully!');
        redirect(new moodle_url('/local/form/manageform.php'));
    } else {
        \core\notification::error('Error duplicating form. Please try again.');
        // Redisplay the form
        $mform->display();
    }
} else {
    // Display the form for the first time
    $mform->display();
}

echo $OUTPUT->footer();

/**
 * Duplicate form and all its sections and fields
 * 
 * @param int $existingformid The ID of the form to copy
 * @param string $newname Name for the new form
 * @param string $newshortname Shortname for the new form - NEW PARAMETER
 * @param string $newdescription Description for the new form
 * @param bool $createcohort Whether to create a cohort with the same name - NEW PARAMETER
 * @return int|bool New form ID or false on failure
 */
function duplicate_form_with_fields($existingformid, $newname, $newshortname, $newdescription, $createcohort = true)
{
    global $DB;

    // Start transaction for data integrity
    $transaction = $DB->start_delegated_transaction();

    try {
        // 1. Get the existing form
        $existingform = $DB->get_record('local_form', ['id' => $existingformid], '*', MUST_EXIST);

        // 2. Create new form record with shortname
        $newform = new stdClass();
        $newform->name = $newname;
        $newform->shortname = $newshortname; // Added shortname
        $newform->description = $newdescription;
        $newform->visible = 1;
        $newform->fc_registration = $existingform->fc_registration;
        $newform->sortorder = get_next_sort_order();
        $newform->course_edate = $existingform->course_edate;
        $newform->course_sdate = $existingform->course_sdate;
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
                // Optionally, you can store the cohort ID in the form record if needed
                // $newform->cohortid = $cohortid;
                // $DB->update_record('local_form', $newform);
            }
        }

        // 4. Get sections from existing form
        $existingsections = $DB->get_records('form_sections', ['formid' => $existingformid], 'sort_order ASC');
        $sectionmapping = []; // Map old section IDs to new section IDs

        foreach ($existingsections as $oldsection) {
            // Create new section
            $newsection = new stdClass();
            $newsection->formid = $newformid;
            $newsection->section_title = $oldsection->section_title;
            $newsection->sort_order = $oldsection->sort_order;

            $newsectionid = $DB->insert_record('form_sections', $newsection);

            if (!$newsectionid) {
                throw new Exception('Failed to create section: ' . $oldsection->section_title);
            }

            $sectionmapping[$oldsection->id] = $newsectionid;
        }

        // 5. Get form fields from existing form
        $existingfields = $DB->get_records('form_data', ['formid' => $existingformid], 'sort_order ASC');

        foreach ($existingfields as $oldfield) {
            // Create new field
            $newfield = new stdClass();
            $newfield->formid = $newformid;
            $newfield->section_id = ($oldfield->section_id && isset($sectionmapping[$oldfield->section_id]))
                ? $sectionmapping[$oldfield->section_id]
                : $oldfield->section_id;
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
            $newfield->sort_order = $oldfield->sort_order;

            if (!$DB->insert_record('form_data', $newfield)) {
                throw new Exception('Failed to create field: ' . $oldfield->formlabel);
            }
        }

        // Commit transaction
        $transaction->allow_commit();

        return $newformid;
    } catch (Exception $e) {
        $transaction->rollback($e);
        error_log('Error duplicating form: ' . $e->getMessage());
        \core\notification::error('Error: ' . $e->getMessage());
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
            \core\notification::success("Cohort '{$shortname}' created successfully.");
            return $cohortid;
        } else {
            \core\notification::error("Failed to create cohort '{$shortname}'.");
            return false;
        }
    } catch (Exception $e) {
        error_log('Error creating cohort: ' . $e->getMessage());
        \core\notification::error('Error creating cohort: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get next sort order value for forms
 * 
 * @return int Next sort order
 */
function get_next_sort_order()
{
    global $DB;
    $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_form}');
    return $maxsort ? $maxsort + 1 : 1;
}
<?php
require_once('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $CFG, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/form/formsubmit.php');
$PAGE->set_title('Form Submission');
$PAGE->set_heading('Form Submission!');

echo $OUTPUT->header();

$formid = optional_param('formid', 0, PARAM_INT);

// Process form data submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Initialize arrays
    $uploaded_files = [];
    $upload_dir = $CFG->dirroot . '/local/form/pix/';

    // Allowed file types and max size
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 5 * 1024 * 1024;

    // Get old submitted values first
    $oldrecords = $DB->get_records('form_submissions', [
        'formid' => $formid,
        'uid' => $USER->id
    ]);

    $oldfiles = [];
    foreach ($oldrecords as $rec) {
        $oldfiles[$rec->fieldname] = $rec->fieldvalue;
    }

    // Delete old submission
    $DB->delete_records('form_submissions', [
        'formid' => $formid,
        'uid'    => $USER->id
    ]);

    $fields = $_POST;

    $insert_sql = "INSERT INTO {form_submissions}
    (formid, uid, fieldname, fieldvalue, timecreated)
    VALUES (?, ?, ?, ?, ?)";

    // Handle file uploads
    foreach ($_FILES as $field_name => $file_info) {

        if (isset($file_info['error']) && $file_info['error'] === UPLOAD_ERR_OK) {

            $originalname = pathinfo($file_info['name'], PATHINFO_FILENAME);
            $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
            $timestamp = time();

            $unique_filename = $originalname . '_' . $timestamp . '.' . $extension;

            $tempname = $file_info['tmp_name'];
            $target_file = $upload_dir . $unique_filename;

            // Validate file type
            if (!in_array($file_info['type'], $allowed_types)) {
                echo get_string('invalidfiletype', 'local_form') . " for field: $field_name<br>";
                continue;
            }

            // Validate file size
            if ($file_info['size'] > $max_file_size) {
                echo get_string('filesizeexceeded', 'local_form') . " for field: $field_name<br>";
                continue;
            }

            // Create directory if not exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move file
            if (move_uploaded_file($tempname, $target_file)) {

                $uploaded_files[$field_name] = $unique_filename;
            } else {
                echo get_string('erroruploadingfile', 'local_form') . " for field: $field_name<br>";
            }
        }
    }

    // Prepare table arrays
    $table_data = [];
    $table_header_data = [];

    // Table file uploads
    foreach ($_FILES as $key => $file) {

        if (strpos($key, 'table_') === 0 && $file['error'] === UPLOAD_ERR_OK) {

            $filename = basename($file['name']);
            $tempname = $file['tmp_name'];

            $folder = $CFG->dirroot . '/local/form/pix/';
            $target_file = $folder . $filename;

            if (move_uploaded_file($tempname, $target_file)) {

                $uploaded_files[$key] = $filename;
            } else {

                echo get_string('erroruploadingfile', 'local_form');
                exit;
            }
        }
    }

    // Update fields with uploaded files
    foreach ($uploaded_files as $fieldname => $filename) {

        if (strpos($fieldname, 'table_') === false) {

            $fields[$fieldname] = $filename;
        } else {

            $table_field_index = str_replace('table_file_', '', $fieldname);
            $table_data[$table_field_index] = $filename;
        }
    }

    // Preserve old files if no new upload
    if (!empty($oldfiles)) {

        foreach ($oldfiles as $fieldname => $oldvalue) {

            $inputname = 'field_' . str_replace(' ', '_', trim($fieldname));

            if (!isset($fields[$inputname]) && !empty($oldvalue)) {

                $fields[$inputname] = $oldvalue;
            }
        }
    }

    // Insert fields
   foreach ($fields as $fieldname => $fieldvalue) {

    // Convert arrays to comma-separated string (for checkboxes)
    if (is_array($fieldvalue)) {
        $fieldvalue = implode(', ', $fieldvalue);
    }

    // Table fields handling
    if (strpos($fieldname, 'table_') === 0) {
        $index = str_replace('table_', '', $fieldname);
        $table_data[$index] = $fieldvalue;
        continue;
    }

    // Header fields handling
    if (strpos($fieldname, 'header_') === 0) {
        $index = str_replace('header_', '', $fieldname);
        $table_header_data[$index] = $fieldvalue;
        continue;
    }

    $fieldname = trim($fieldname);
    $fieldvalue = trim($fieldvalue);

    // Skip formid field
    if ($fieldname === 'formid') {
        continue;
    }

    // Normalize field name
    $fieldname_db = str_replace('field_', '', $fieldname);
    $fieldname_db = str_replace('_', ' ', $fieldname_db);
    $fieldname_db = preg_replace('/\s+/', ' ', $fieldname_db);

    $current_time = time();

    try {
        // Check if record already exists
        $existing = $DB->get_record('form_submissions', [
            'formid' => $formid,
            'uid' => $USER->id,
            'fieldname' => $fieldname_db
        ]);

        if ($existing) {
            // ✅ Update existing record
            $existing->fieldvalue = $fieldvalue;
            $existing->timecreated = $current_time;
            $DB->update_record('form_submissions', $existing);
        } else {
            // ✅ Insert new record
            $DB->insert_record('form_submissions', [
                'formid' => $formid,
                'uid' => $USER->id,
                'fieldname' => $fieldname_db,
                'fieldvalue' => $fieldvalue,
                'visible' => 1,
                'timecreated' => $current_time
            ]);
        }
    } catch (Exception $e) {
        echo "Error saving field '" . htmlspecialchars($fieldname_db) . "': " . htmlspecialchars($e->getMessage()) . "<br>";
        continue; // Continue processing other fields
    }
}

    // Insert table JSON
    if (!empty($table_data) || !empty($table_header_data)) {

        $alltable_data = [
            'header' => $table_header_data,
            'table_value' => $table_data
        ];

        $json_table_data = json_encode($alltable_data);

        try {

            $DB->execute($insert_sql, [
                $formid,
                $USER->id,
                'table',
                $json_table_data,
                time()
            ]);
        } catch (Exception $e) {

            echo "Error inserting table data: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    redirect(new moodle_url('/my/courses.php', []), 'Form has been submitted successfully!');
}

echo $OUTPUT->footer();

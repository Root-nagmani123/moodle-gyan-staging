<?php
require_once('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/form/formsubmit.php');
$PAGE->set_title('Form Submission');
$PAGE->set_heading('Form Submission!');

echo $OUTPUT->header();
$formid = optional_param('formid', 0, PARAM_INT);
// Handle file uploads for regular fields
// Initialize an array to store the uploaded files
$uploaded_files = [];
$upload_dir = $CFG->dirroot . '/local/form/pix/';

// Define allowed file types and maximum file size (5MB)
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf', 'pdf'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Iterate over the $_FILES array to handle multiple file uploads dynamically
foreach ($_FILES as $field_name => $file_info) {
    // Check if the file has been uploaded and doesn't have any errors
    if (isset($file_info['error']) && $file_info['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file_info['name']);
        $timestamp = time(); // Get the current timestamp
        $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $unique_filename = $filename . '_' . $timestamp . '.' . $extension; // Concatenate filename with timestamp
        $tempname = $file_info['tmp_name'];
        $target_file = $upload_dir . $unique_filename;

        // Validate file type
        if (!in_array($file_info['type'], $allowed_types)) {
            echo get_string('invalidfiletype', 'local_form') . " for field: $field_name<br>";
            continue; // Skip this file and move to the next
        }

        // Validate file size
        if ($file_info['size'] > $max_file_size) {
            echo get_string('filesizeexceeded', 'local_form') . " for field: $field_name<br>";
            continue; // Skip this file and move to the next
        }

        // Create the upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Move the uploaded file to the target directory
        if (move_uploaded_file($tempname, $target_file)) {
            // Store the file name in the uploaded_files array
            $uploaded_files[$field_name] = $unique_filename;
            // echo "File uploaded successfully for field: $field_name. File name: " . htmlspecialchars($filename) . "<br>";
        } else {
            echo get_string('erroruploadingfile', 'local_form') . " for field: $field_name<br>";
        }
    } else {
        // Handle cases where no file was uploaded or an error occurred
        echo "No file uploaded or an error occurred for field: $field_name<br>";
    }
}





// Process form data submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = $_POST;
    $insert_sql = "INSERT INTO  {form_submissions} (formid, uid, fieldname, fieldvalue, timecreated) VALUES (?, ?, ?, ?, ?)";

    try {
        $max_uid_query = "SELECT COALESCE(MAX(uid), 0) AS max_uid FROM {form_submissions}";
        $result = $DB->get_record_sql($max_uid_query);
        $new_uid = $result->max_uid + 1; // Start from 1 if the table is empty
    } catch (Exception $e) {
        echo "Error retrieving max uid: " . htmlspecialchars($e->getMessage());
        exit;
    }


    // Prepare to collect table data
    $table_data = [];
    $table_header_data = [];

    // Check for files uploaded with table fields
    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'table_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
            $filename = basename($file['name']);
            $tempname = $file['tmp_name'];
            $folder = $CFG->dirroot . '/local/form/pix/';
            $table_field_index = str_replace('table_file_', '', $key);
            $target_file = $folder . $filename;

            // Validate and move file
            if (move_uploaded_file($tempname, $target_file)) {
                $uploaded_files[$key] = $filename; // Store the file name for table fields
            } else {
                echo get_string('erroruploadingfile', 'local_form');
                exit;
            }
        }
    }

    // Update fields with uploaded file names
    foreach ($uploaded_files as $fieldname => $filename) {
        if (strpos($fieldname, 'table_') === false) {
            $fields[$fieldname] = $filename; // Regular file fields
        } else {
            // For table fields, store in a structured way
            $table_field_index = str_replace('table_file_', '', $fieldname);
            $table_data[$table_field_index] = $filename;
        }
    }

    foreach ($fields as $fieldname => $fieldvalue) {
        if (is_array($fieldvalue)) {
            $fieldvalue = implode(', ', $fieldvalue);
        }

        if (strpos($fieldname, 'table_') === 0) {
            $index = str_replace('table_', '', $fieldname);
            $table_data[$index] = $fieldvalue;
            continue; // Skip further processing for table fields
        }

        if (strpos($fieldname, 'header_') === 0) {
            $index = str_replace('header_', '', $fieldname);
            $table_header_data[$index] = $fieldvalue;
            continue; // Skip further processing for header fields
        }

        // Sanitize input
        $fieldname = trim($fieldname);
        $fieldvalue = trim($fieldvalue);

        if ($fieldname === 'formid') {
            continue; // Skip inserting the formid itself
        }

        $fieldname = str_replace('field_', '', $fieldname);
        $fieldname = str_replace('_', ' ', $fieldname); // Remove underscores from field names
        // print_object($fieldname);die("ffff");
        $time = time();

        try {
            $DB->execute($insert_sql, [$formid, $USER->id, $fieldname, $fieldvalue, $time]);
        } catch (Exception $e) {
            echo "Error inserting data: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    // Insert table data as JSON
    if (!empty($table_data) || !empty($table_header_data)) {
        $alltable_data = [
            'header' => $table_header_data,
            'table_value' => $table_data,
        ];
        $json_table_data = json_encode($alltable_data);

        try {
            $DB->execute($insert_sql, [$formid, $USER->id, 'table', $json_table_data, time()]);
        } catch (Exception $e) {
            echo "Error inserting table data: " . htmlspecialchars($e->getMessage());
            exit;
        }
    }
    // Redirect after successful submission
    // redirect(new moodle_url('/my/courses.php', ['formid' => $formid]), 'Form has been submitted successfully!');
    redirect(new moodle_url('/my/courses.php', []), 'Form has been submitted successfully!');
}
echo $OUTPUT->footer();

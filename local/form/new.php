<?php
global $PAGE;
require_once('../../config.php');
require_once('lib.php');
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
global $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/form/new.php');
$PAGE->set_title('NEW FORM');
$PAGE->set_heading('CREATE NEW FORM');

echo $OUTPUT->header();

$form_id = optional_param('formid', 0, PARAM_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_titles = optional_param_array('section_title', [], PARAM_TEXT);
    $field_sections = $_POST['field_section'] ?? [];
    $field_names = $_POST['field_name'] ?? [];
    $field_types = $_POST['field_type'] ?? [];
    $field_labels = $_POST['field_label'] ?? [];
    $field_options = $_POST['field_options'] ?? [];
    $is_requireds = $_POST['is_required'] ?? [];
    $field_layouts = $_POST['field_layout'] ?? [];

    $table_rows = optional_param_array('table_rows', [], PARAM_INT);
    $table_columns = optional_param_array('table_columns', [], PARAM_INT);
    $table_sections = optional_param_array('table_section', [], PARAM_INT);
    $table_headers = optional_param_array('table_column_heading', [], PARAM_TEXT);

    // Start a transaction
    $transaction = $DB->start_delegated_transaction();

    try {
        $section_ids = [];

        // Insert sections
        foreach ($section_titles as $section_index => $section_title) {
            $section_title = trim($section_title);
            if (empty($section_title)) continue;

            $section_data = new stdClass();
            $section_data->formid = $form_id;
            $section_data->section_title = $section_title;

            $section_id = $DB->insert_record('form_sections', $section_data, true);
            $section_ids[$section_index] = $section_id;
        }
        // Prepare and insert fields
        if (!empty($field_names)) {
            foreach ($field_names as $index => $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $section_index = $field_sections[$index] ?? 0;
                if (!isset($section_ids[$section_index])) {
                    throw new Exception("Invalid section index: $section_index");
                }
                $section_id = $section_ids[$section_index];
                $field_data = new stdClass();
                $field_data->formid = $form_id;
                $field_data->section_id = $section_id;
                $field_data->formname = $name;
                $field_data->formtype = $field_types[$index] ?? 'text';
                $field_data->formlabel = $field_labels[$index] ?? '';
                $field_data->fieldoption = $field_options[$index] ?? '';
                $field_data->required = isset($is_requireds[$index]) ? 1 : 0;
                $field_data->layout = $field_layouts[$index] ?? 'vertical';
                $DB->insert_record('form_data', $field_data);
                // $url = new moodle_url('/local/form/manageform.php', array('formid' => $form_id));
                // redirect($url);

            }
        }

        // Check if table sections are present before processing table data
        if (!empty($table_sections) && !empty($table_rows) && !empty($table_columns)) {
            foreach ($table_sections as $section_index => $val) {
                if (!isset($section_ids[$val])) {
                    continue; // Skip if section index is invalid
                }
                $section_id = $section_ids[$val];
                $rows = $table_rows[$section_index] ?? 0;
                $columns = $table_columns[$section_index] ?? 0;

                for ($colIndex = 0; $colIndex < $columns; $colIndex++) {
                    for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
                        // Ensure unique identifiers for table data
                        $headerTitle = $_POST["table_column_heading_{$val}_{$rowIndex}"][$colIndex] ?? '';

                        $keyType = "table_row{$rowIndex}_{$val}_0";
                        $keyTitle = "table_title{$rowIndex}_{$val}_0";
                        $keyUrl = "table_url{$rowIndex}_{$val}_0";
                        $keyOptions = "table_options{$rowIndex}_{$val}_0";
                        $keyCheckbox = "checkbox_options{$rowIndex}_{$val}_0";
                        $keyRadio = "radio_options{$rowIndex}_{$val}_0";

                        $fieldType = $_POST[$keyType][$colIndex] ?? null;
                        $fieldTitle = $_POST[$keyTitle][$colIndex] ?? null;
                        $fieldUrl = $_POST[$keyUrl][$colIndex] ?? null;
                        $fieldOptions = $_POST[$keyOptions][$colIndex] ?? null;
                        $checkboxOptions = $_POST[$keyCheckbox][$colIndex] ?? null;
                        $radioOptions = $_POST[$keyRadio][$colIndex] ?? null;

                        $table_data = new stdClass();
                        $table_data->formid = $form_id;
                        $table_data->section_id = $section_id;
                        $table_data->formname = null;
                        $table_data->formtype = null;
                        $table_data->formlabel = null;
                        $table_data->fieldoption = null;
                        $table_data->required = 0;
                        $table_data->layout = null;
                        $table_data->table_index = 0;
                        $table_data->format = 'table';
                        $table_data->row_index = $rowIndex;
                        $table_data->col_index = $colIndex;
                        $table_data->header = $headerTitle;
                        $table_data->field_type = trim($fieldType);
                        $table_data->field_title = trim($fieldTitle);
                        $table_data->field_url = trim($fieldUrl);
                        $table_data->field_options = trim($fieldOptions);
                        $table_data->field_checkbox_options = trim($checkboxOptions);
                        $table_data->field_radio_options = trim($radioOptions);
                        // print_object($table_data);
                        $DB->insert_record('form_data', $table_data);
                    }
                }
            }
        } else {
            echo '<div class="alert alert-warning" role="alert">No valid table data provided.</div>';
        }

        $transaction->allow_commit();
        echo '<div class="alert alert-success fade show" role="alert" id="success-alert">Fields and tables added successfully!</div>';
         // Redirect to manageform.php after 3 seconds
          // Use JavaScript to redirect after 3 seconds
    echo '<script>
    setTimeout(function() {
        var url = "' . new moodle_url('/local/form/manageform.php') . '";
        window.location.href = url;
    }, 3000); // Redirect after 3 seconds
  </script>';
    } catch (Exception $e) {
        $transaction->rollback($e);
        error_log('Database Error: ' . $e->getMessage());
        echo '<div class="alert alert-danger fade show" role="alert" id="error-alert">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Form</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .section-entry,
        .field-entry,
        .table-entry {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .field-options {
            margin-top: 10px;
        }

        table {
            margin-top: 10px;
            width: 100%;
        }

        table th,
        table td {
            text-align: center;
        }
    </style>
    <script>
        function addSection() {
            const container = document.getElementById("sections-container");
            const index = container.children.length;

            const sectionHtml = `
                <div class="section-entry" id="section_${index}">
                    <h3>Section ${index + 1}</h3>
                    <div class="form-group">
                        <label for="section_title_${index}">Section Title:</label>
                        <input type="text" class="form-control" id="section_title_${index}" name="section_title[]" required>
                    </div>
                    <div id="fields-container_${index}">
                        <!-- Fields for this section will be added here -->
                    </div>
                    <div id="tables-container_${index}">
                        <!-- Tables for this section will be added here -->
                    </div>
                    <button type="button" class="btn btn-primary" onclick="addField(${index})">Add Field</button>
                    <button type="button" class="btn btn-info" onclick="addTable(${index})">Add Table</button>
                    <button type="button" class="btn btn-danger" onclick="removeSection(${index})">Remove Section</button>
                    <hr>
                </div>
            `;

            container.insertAdjacentHTML("beforeend", sectionHtml);
        }

        function addField(sectionIndex) {
            const container = document.getElementById(`fields-container_${sectionIndex}`);
            const index = container.children.length;

            const fieldHtml = `
                <div class="field-entry" id="field_${sectionIndex}_${index}">
                    <h4>Field ${index + 1}</h4>
                    <input type="hidden" name="field_section[]" value="${sectionIndex}">
                    <div class="form-group">
                        <label for="field_name_${sectionIndex}_${index}">Field Name:</label>
                        <input type="text" class="form-control" id="field_name_${sectionIndex}_${index}" name="field_name[]" required>
                    </div>

                    <div class="form-group">
                        <label for="field_type_${sectionIndex}_${index}">Field Type:</label>
                        <select class="form-control" id="field_type_${sectionIndex}_${index}" name="field_type[]">
                            <option value="text">Text</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="email">Email</option>
                            <option value="textarea">Textarea</option>
                            <option value="radio">Radio</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="date">Date</option>
                            <option value="file">File</option>
                            <option value="view_download">View/Download</option>
                            <option value="number">Number</option> <!-- New field type for numbers -->
                            <option value="time">Time</option> <!-- New field type for time -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="field_label_${sectionIndex}_${index}">Field Label:</label>
                        <input type="text" class="form-control" id="field_label_${sectionIndex}_${index}" name="field_label[]" required>
                    </div>

                    <div class="form-group">
                        <label for="field_options_${sectionIndex}_${index}">Options (comma separated):</label>
                        <input type="text" class="form-control" id="field_options_${sectionIndex}_${index}" name="field_options[]">
                    </div>

                    <div class="form-group">
                        <label>Required:</label>
                        <input type="checkbox" name="is_required[]" value="1">
                    </div>

                    <div class="form-group">
                        <label for="field_layout_${sectionIndex}_${index}">Layout:</label>
                        <select class="form-control" id="field_layout_${sectionIndex}_${index}" name="field_layout[]">
                            <option value="vertical">Vertical</option>
                            <option value="horizontal">Horizontal</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-danger" onclick="removeField(${sectionIndex}, ${index})">Remove Field</button>
                    <hr>
                </div>
            `;

            container.insertAdjacentHTML("beforeend", fieldHtml);
        }

        function addTable(sectionIndex) {
            const container = document.getElementById(`tables-container_${sectionIndex}`);
            const index = container.children.length;

            const tableHtml = `
                <div class="table-entry" id="table_${sectionIndex}_${index}">
                    <h4>Table ${index + 1}</h4>
                    <input type="hidden" name="table_section[]" value="${sectionIndex}">
                    <div class="form-group">
                        <label for="table_rows_${sectionIndex}_${index}">Number of Rows:</label>
                        <input type="number" class="form-control" id="table_rows_${sectionIndex}_${index}" name="table_rows[]" required>
                    </div>

                    <div class="form-group">
                        <label for="table_columns_${sectionIndex}_${index}">Number of Columns:</label>
                        <input type="number" class="form-control" id="table_columns_${sectionIndex}_${index}" name="table_columns[]" required>
                    </div>

                   
                    <div id="table-container${sectionIndex}_${index}"></div>

                    <button type="button" class="btn btn-primary" onclick="generateTable(${sectionIndex}, ${index})">Generate Table</button>
                    <button type="button" class="btn btn-danger" onclick="removeTable(${sectionIndex}, ${index})">Remove Table</button>
                    <hr>
                </div>
            `;

            container.insertAdjacentHTML("beforeend", tableHtml);
        }

        function generateTable(sectionIndex, tableIndex) {
            const rows = document.getElementById(`table_rows_${sectionIndex}_${tableIndex}`).value;
            const cols = document.getElementById(`table_columns_${sectionIndex}_${tableIndex}`).value;
            const tableContainer = document.getElementById(`table-container${sectionIndex}_${tableIndex}`);

            if (!rows || !cols) {
                tableContainer.innerHTML = '<div class="alert alert-warning" role="alert">Please specify both number of rows and columns.</div>';
                return;
            }

            let table = '<table class="table table-bordered table-striped">';

            // Generate column headings
            table += '<thead class="table-secondary"><tr>';
            for (let j = 0; j < cols; j++) {
                table += `<th><input type='text' placeholder='Column ${j + 1} Heading' class='form-control' name="table_column_heading_${sectionIndex}_${tableIndex}[]" required></th>`;
            }
            table += '</tr></thead>';

            // Generate table body
            for (let i = 0; i < rows; i++) {
                table += '<tr>';
                for (let j = 0; j < cols; j++) {
                    table += `<td>
                <select class="form-control" onchange="fieldtype(this.value, ${j + 1}, ${i + 1}, ${sectionIndex}, ${tableIndex})" name="table_row${i}_${sectionIndex}_${tableIndex}[]">
                    <option value="">Select Type</option>
                    <option value="Label">Label</option>
                    <option value="Text">Text</option>
                    <option value="Date">Date</option>
                    <option value="Email">Email</option>
                    <option value="Textarea">Textarea</option>
                    <option value="Checkbox">Checkbox</option>
                    <option value="Radio Button">Radio Button</option>
                    <option value="Select Box">Select Box</option>
                    <option value="File Upload">File Upload</option>
                    <option value="View/Download">View/Download</option>
                </select>
                <div id="type_label${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_title${i}_${sectionIndex}_${tableIndex}[]" placeholder='Title'>
                </div>
                <div id="type_url${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_url${i}_${sectionIndex}_${tableIndex}[]" placeholder='URL'>
                </div>
                <div id="option${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Options (comma separated)'>
                </div>
                <div id="checkbox_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="checkbox_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Checkbox Options (comma separated)'>
                </div>
                <div id="radio_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="radio_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Radio Options (comma separated)'>
                </div>
            </td>`;
                }
                table += '</tr>';
            }
            table += '</table>';

            tableContainer.innerHTML = table;
        }


        function fieldtype(value, col, row, sectionIndex, tableIndex) {
            const typeLabel = document.getElementById(`type_label${col}_${row}_${sectionIndex}_${tableIndex}`);
            const typeUrl = document.getElementById(`type_url${col}_${row}_${sectionIndex}_${tableIndex}`);
            const option = document.getElementById(`option${col}_${row}_${sectionIndex}_${tableIndex}`);
            const checkboxOptions = document.getElementById(`checkbox_options${col}_${row}_${sectionIndex}_${tableIndex}`);
            const radioOptions = document.getElementById(`radio_options${col}_${row}_${sectionIndex}_${tableIndex}`);

            typeLabel.style.display = value === 'Label' || value === 'View/Download' ? 'block' : 'none';
            typeUrl.style.display = value === 'View/Download' ? 'block' : 'none';
            option.style.display = value === 'Select Box' || value === 'Radio Button' || value === 'Checkbox' ? 'block' : 'none';
            checkboxOptions.style.display = value === 'Checkbox' ? 'block' : 'none';
            radioOptions.style.display = value === 'Radio Button' ? 'block' : 'none';
        }

        function removeSection(sectionIndex) {
            const section = document.getElementById(`section_${sectionIndex}`);
            if (section) {
                section.remove();
            }
        }

        function removeField(sectionIndex, fieldIndex) {
            const field = document.getElementById(`field_${sectionIndex}_${fieldIndex}`);
            if (field) {
                field.remove();
            }
        }

        function removeTable(sectionIndex, tableIndex) {
            const table = document.getElementById(`table_${sectionIndex}_${tableIndex}`);
            if (table) {
                table.remove();
            }
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>Create New Form</h1>
        <form method="POST">
            <div id="sections-container">
                <!-- Sections will be added here -->
            </div>
            <button type="button" class="btn btn-success" onclick="addSection()">Add Section</button>
            <hr>
            <button type="submit" class="btn btn-primary">Save Form</button>
              <?php
        //     $url = new moodle_url('/local/form/new.php', array('formid' => $form_id));
        // redirect($url);
        ?>
        </form>
    </div>
</body>

</html>

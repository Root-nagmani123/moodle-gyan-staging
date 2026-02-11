<?php

global $PAGE, $DB;

require_once('../../config.php');

require_login();

// Initialize page
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/form/editform.php');
$PAGE->set_title('Edit Form Fields');
$PAGE->set_heading('Edit Form Fields');

echo $OUTPUT->header();

// Check if a form ID is provided
$form_id = optional_param('formid', 0, PARAM_INT);
// $section_id_param = optional_param('section_id', 0, PARAM_INT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $section_ids = $_POST['section_id'] ?? [];
    $section_titles = $_POST['section_title'] ?? [];
    $sort_order = $_POST['sort_order'] ?? [];
    $field_ids = $_POST['field_id'] ?? [];
    $field_names = $_POST['field_name'] ?? [];
    $field_types = $_POST['field_type'] ?? [];
    $field_labels = $_POST['field_label'] ?? [];
    $field_options = $_POST['field_options'] ?? [];
    $is_requireds = $_POST['is_required'] ?? [];
    $field_sections = $_POST['field_section'] ?? [];
    // Determine if any fields require table format
  
    $tableformat = false;
    foreach ($field_types as $field) {
        if (in_array($field, ['Label', 'View/Download', 'Radio Button'])) {
            $tableformat = true;
            break; // Exit the loop if a match is found
        }
    }

    // Prepare the SQL update statement based on the table format
    if ($tableformat) {
        echo "Table format is present.";
        $sql_update_field = "
        UPDATE {form_data} 
        SET section_id = ?, field_type = ?, field_title = ?, field_url = ?, field_options = ?, field_checkbox_options = ?, field_radio_options = ? 
        WHERE id = ?";
    } else {
        echo "Table format is not present.";
        $sql_update_field = "
        UPDATE {form_data} 
        SET section_id = ?, formname = ?, formtype = ?, formlabel = ?, fieldoption = ?, required = ? 
        WHERE id = ?";
    }
    // Prepare SQL statements
    $sql_insert_section = "INSERT INTO {form_sections} (formid, section_title, sort_order) VALUES (?, ?, ?)";
    $sql_update_section = "UPDATE {form_sections} SET section_title = ?, sort_order = ? WHERE id = ?";
    $sql_delete_section = "DELETE FROM {form_sections} WHERE id = ?";
    $sql_insert_field = "INSERT INTO {form_data} (formid, section_id, formname, formtype, formlabel, fieldoption, required) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $sql_update_field = "UPDATE {form_data} SET section_id = ?, formname = ?, formtype = ?, formlabel = ?, fieldoption = ?, required = ? WHERE id = ?";
    $sql_delete_field = "DELETE FROM {form_data} WHERE id = ?";

    $transaction = $DB->start_delegated_transaction();

    try {
        // Handle sections
        foreach ($section_titles as $index => $title) {
            $title = trim($title);
            $section_id = $section_ids[$index] ?? 'new';
            $order = $sort_order[$index] ?? 0;

            if ($section_id !== 'new') {
                // Update existing section
                $DB->execute($sql_update_section, [$title, $order, $section_id]);
            } else {
                // Insert new section
                $DB->execute($sql_insert_section, [$form_id, $title, $order]);
                // $query = "SELECT MAX(id) AS max_id FROM form_sections";

                // Execute the query
                $section_id = $DB->get_record_sql('SELECT MAX(id) AS max_id FROM {form_sections}');
                $section_ids[$index] = $section_id; // Update $section_ids with the new ID
            }
        }

        // Handle fields
        $tableformat = false;
       
// Example of executing the SQL (assuming you have necessary data to bind)
foreach ($field_ids as $index => $field_id) {
    $is_required = isset($is_requireds[$index]) && $is_requireds[$index] === 'on' ? 1 : 0; 

    // Prepare the parameters based on the current field's type
    if ($tableformat) {
        $params = [
            $field_sections[$index],
            $field_types[$index],
            $field_names[$index],
            $field_urls[$index] ?? null, // Assuming you have a field for URLs
            $field_options[$index],
            $field_checkbox_options[$index] ?? null, // Placeholder for checkbox options
            $field_radio_options[$index] ?? null, // Placeholder for radio options
            $new_id++,
        ];
    } else {
        $params = [
            $field_sections[$index],
            $field_names[$index],
            $field_types[$index],
            $field_labels[$index],
            $field_options[$index],
            $is_requireds = $is_required,
            $field_id
        ];
    }
    echo $is_requireds;
// print_object($params);
    // Execute the prepared statement with the parameters
    $DB->execute($sql_update_field, $params);
}

        // Remove fields and sections marked for deletion
        if (!empty($_POST['field_id']['delete'])) {
            foreach ($_POST['field_id']['delete'] as $id) {
                $DB->execute($sql_delete_field, [$id]);
            }
        }

        if (!empty($_POST['section_id']['delete'])) {
            foreach ($_POST['section_id']['delete'] as $id) {
                $DB->execute($sql_delete_section, [$id]);
            }
        }

        // Commit the transaction
        $transaction->allow_commit();

        // Redirect to the manage form page with a success message
        // redirect(new moodle_url('/local/form/manageform.php', ['formid' => $form_id]), 'Form fields updated successfully!');
    } catch (Exception $e) {
        // Rollback the transaction and handle the exception
        $transaction->rollback($e);
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    }
}

// Retrieve form sections and fields from the database
$sections = $DB->get_records('form_sections', ['formid' => $form_id], 'sort_order');
$fields = $DB->get_records('form_data', ['formid' => $form_id]);

// Display the form
echo '<form method="post" action="">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
echo '<input type="hidden" name="formid" value="' . $form_id . '" />';
echo '<div id="sections-container">';

// Existing sections
if ($sections) {
    $i = 0;
    foreach ($sections as $section) {
        $section_title = htmlspecialchars($section->section_title);
        $section_id = $section->id;
        $is_active = 'active';

        echo "<div class='section-group $is_active' id='section_$i'>";
        echo "<input type='hidden' name='section_id[]' value='{$section_id}' />";
        echo "<input type='hidden' name='sort_order[]' value='{$i}' />";
        echo "<label>Section Title:</label>";
        echo "<input type='text' name='section_title[]' value='{$section_title}' />";
        echo "<div id='fields-container_$i'>";


        $has_table_format = false;

        // Check if any field has format 'table'
        foreach ($fields as $field) {
            if ($field->section_id == $section_id && $field->format === 'table') {
                $has_table_format = true;
                break;
            }
        }

        if ($has_table_format) {
            echo "<table border='1'>"; // Start table with a border
            echo "<tr>
            <th>Label</th>
            <th>Name</th>
            <th>Type</th>
            <th>Options</th>
            <th>Required</th>
            <th>Delete</th>
          </tr>"; // Table header

            foreach ($fields as $field) {

                if ($field->section_id == $section_id && $field->format === 'table') {
                    $is_required = $field->required ? 'checked' : '';
                    if ($field->field_type == 'Label') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                    }

                    if ($field->field_type == 'Select Box') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                        $field_option = htmlspecialchars($field->field_options);
                    }
                    if ($field->field_type == 'Radio Button') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_options);
                        $field_option = htmlspecialchars($field->field_radio_options);
                    }
                    if ($field->field_type == 'Checkbox') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                        $field_option = htmlspecialchars($field->field_checkbox_options);
                    }

                    if ($field->field_type == 'View/Download') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                        $field_option = htmlspecialchars($field->field_url);
                    }
                    if ($field->field_type == 'Date') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                    }

                    if ($field->field_type == 'File Upload') {
                        $field_label = htmlspecialchars($field->field_type);
                        $field_name = htmlspecialchars($field->field_title);
                    }


                    echo "<tr id='field_$i'>"; // Start a new row
                    echo "<td><input type='text' name='field_label[]' value='{$field_label}' /></td>";
                    echo "<td><input type='text' name='field_name[]' value='{$field_name}' /></td>";
                    echo "<td>
                    <select name='field_type[]'>";
                    foreach (['Label', 'Text', 'Date', 'Email', 'Textarea', 'Checkbox', 'Radio Button', 'Select Box', 'File Upload', 'View/Download'] as $type) {
                        $selected = $field->field_type === $type ? ' selected' : '';
                        echo "<option value='{$type}'{$selected}>" . ucfirst($type) . "</option>";
                    }
                    echo "</select>
                  </td>";
                    echo "<td><input type='text' name='field_options[]' value='{$field_option}' /></td>";
                    echo "<td>
                    <label><input type='checkbox' name='is_required[]' {$is_required} /> Required</label>
                  </td>";
                    echo "<td><input type='checkbox' name='field_id[delete][]' value='{$field->id}' /> Delete</td>";
                    echo "</tr>"; // End row
                }
            }

            echo "</table>"; // End table
        } else {
            // Original layout for fields without 'table' format
            foreach ($fields as $field) {
                if ($field->section_id == $section_id) {
                    $field_label = htmlspecialchars($field->formlabel);
                    $field_name = htmlspecialchars($field->formname);
                    $field_type = htmlspecialchars($field->formtype);
                    $field_options = htmlspecialchars($field->fieldoption);
                    $is_required = $field->required ? 'checked' : '';

                    echo "<div class='form-group' id='field_$i'>";
                    echo "<input type='hidden' name='field_id[]' value='{$field->id}' />";
                    echo "<input type='hidden' name='field_section[]' value='{$section_id}' />";

                    echo "<label>Label:</label>";
                    echo "<input type='text' name='field_label[]' value='{$field_label}' />";

                    echo "<label>Name:</label>";
                    echo "<input type='text' name='field_name[]' value='{$field_name}' />";

                    echo "<label>Type:</label>";
                    echo "<select name='field_type[]'>";
                    foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file'] as $type) {
                        $selected = $field->formtype === $type ? ' selected' : '';
                        echo "<option value='{$type}'{$selected}>" . ucfirst($type) . "</option>";
                    }
                    echo "</select>";

                    echo "<label>Options (comma separated):</label>";
                    echo "<input type='text' name='field_options[]' value='{$field_options}' />";

                    echo "<div class='checkbox-container'>";
                    echo "<label><input type='checkbox' name='is_required[]' {$is_required} /> Required</label>";
                    echo "<label><input type='checkbox' name='field_id[delete][]' value='{$field->id}' /> Delete</label>";
                    echo "</div>";

                    echo "</div><br>";
                }
            }
        }
        echo '</div>'; // End of fields-container
        echo "<button type='button' class='btn-add-field' onclick='addField($i, $section_id)'>Add New Field</button>";
        echo "<button type='button' class='btn-remove-section' onclick='removeSection(this)'>Remove Section</button>";
        echo "<button type='button' class='btn-move-up' onclick='moveSection($i, -1)'>Move Up</button>";
        echo "<button type='button' class='btn-move-down' onclick='moveSection($i, 1)'>Move Down</button>";
        echo '</div>'; // End of section-group

        $i++;
    }
}

// Add a new section button
echo '<button type="button" class="btn-add-section" onclick="addSection()">Add New Section</button>';
echo '</div>'; // End of sections-container
echo '<input type="submit" value="Save Changes" />';
echo '</form>';

?>

<script>
    let sectionCounter = <?php echo isset($i) ? $i : 0; ?>;

    function addField(sectionIndex, sectionId) {
        const fieldsContainer = document.querySelector(`#fields-container_${sectionIndex}`);
        const newFieldIndex = fieldsContainer.children.length;

        const fieldHtml = `
        <div class='form-group' id='field_${sectionCounter}_${newFieldIndex}'>
            <input type='hidden' name='field_id[]' value='new' />
            <input type='hidden' name='field_section[]' value='${sectionId}' />
            <label>Label:</label>
            <input type='text' name='field_label[]' />
            <label>Name:</label>
            <input type='text' name='field_name[]' />
            <label>Type:</label>
            <select name='field_type[]'>
                <option value='text'>Text</option>
                <option value='dropdown'>Dropdown</option>
                <option value='radio'>Radio</option>
                <option value='checkbox'>Checkbox</option>
                <option value='date'>Date</option>
                <option value='file'>File</option>
            </select>
            <label>Options (comma separated):</label>
            <input type='text' name='field_options[]' />
            <div class='checkbox-container'>
                <label><input type='checkbox' name='is_required[]' /> Required</label>
                <label><input type='checkbox' name='field_id[delete][]' value='new' /> Delete</label>
                <button type="button" class="btn-remove" onclick="removeField(this)">Remove Field</button>
            </div>
        </div>
        <br>
    `;

        fieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);

        // Update the URL without reloading the page
        updateURLWithSection(sectionId);
    }

    function removeSection(button) {
        const sectionGroup = button.closest('.section-group');
        sectionGroup.remove();

        // Check if the removed section was active and handle accordingly
        const activeSectionId = document.querySelector('.section-group.active')?.querySelector('input[name="section_id[]"]').value;
        if (!activeSectionId) {
            updateURLWithSection(0); // or some default section ID
        } else {
            updateURLWithSection(activeSectionId);
        }
    }

    function addSection() {
        const sectionsContainer = document.getElementById('sections-container');
        const newSectionIndex = sectionCounter++;

        const sectionHtml = `
        <div class='section-group' id='section_${newSectionIndex}'>
            <input type='hidden' name='section_id[]' value='new' />
            <input type='hidden' name='sort_order[]' value='${newSectionIndex}' />
            <label>Section Title:</label>
            <input type='text' name='section_title[]' />
            <div id='fields-container_${newSectionIndex}'></div>
            <button type='button' class='btn-add-field' onclick='addField(${newSectionIndex}, "new")'>Add New Field</button>
            <button type='button' class='btn-remove-section' onclick='removeSection(this)'>Remove Section</button>
            <button type='button' class='btn-move-up' onclick='moveSection(${newSectionIndex}, -1)'>Move Up</button>
            <button type='button' class='btn-move-down' onclick='moveSection(${newSectionIndex}, 1)'>Move Down</button>
        </div>
        <br>
    `;

        sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml);
    }

    function moveSection(index, direction) {
        const section = document.getElementById(`section_${index}`);
        const container = section.parentNode;

        if (direction === 1 && section.nextElementSibling) {
            container.insertBefore(section.nextElementSibling, section);
        } else if (direction === -1 && section.previousElementSibling) {
            container.insertBefore(section, section.previousElementSibling);
        }

        // Update sort_order
        const sections = container.querySelectorAll('.section-group');
        sections.forEach((section, i) => {
            section.querySelector('input[name="sort_order[]"]').value = i;
        });
    }

    function updateURLWithSection(sectionId) {
        const url = new URL(window.location.href);
        url.searchParams.set('section_id', sectionId);
        window.history.pushState({}, '', url.toString());
    }

    function removeField(button) {
        button.parentElement.parentElement.remove();
    }

    window.addEventListener('popstate', function(event) {
        const sectionId = new URL(window.location.href).searchParams.get('section_id');
        if (sectionId) {
            // Handle UI update based on the sectionId if needed
            console.log(`Section ID from URL: ${sectionId}`);
        }
    });
</script>

<?php
// Include CSS for styling the form
echo '<style>
    form {
        width: 80%;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .section-group, .form-group {
        margin-bottom: 20px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    input[type="text"], select {
        width: calc(100% - 22px);
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    input[type="checkbox"] {
        margin-right: 5px;
    }
    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .button-container {
        text-align: right;
        margin-top: 20px;
    }
    .btn-add, .btn-update, .btn-remove, .btn-add-field, .btn-remove-section, .btn-move-up, .btn-move-down {
        background-color: #4CAF50; /* Green */
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        margin-right: 10px;
        cursor: pointer;
        border-radius: 5px;
    }
    .btn-update {
        background-color: #2196F3; /* Blue */
    }
    .btn-remove {
        background-color: #f44336; /* Red */
    }
    .btn-move-up, .btn-move-down {
        background-color: #FF9800; /* Orange */
    }
    .btn-remove:hover, .btn-update:hover, .btn-move-up:hover, .btn-move-down:hover {
        opacity: 0.8;
    }
    .btn-remove:hover {
        background-color: #d32f2f; /* Darker red */
    }
    .btn-move-up:hover, .btn-move-down:hover {
        background-color: #F57C00; /* Darker orange */
    }
</style>';

echo $OUTPUT->footer();
?>
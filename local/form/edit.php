<?php
// File: /var/www/hardeep/public_html/Testing_moodle/local/form/edit.php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

global $PAGE, $DB, $OUTPUT;

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
// Initialize page
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Edit Form Fields');
$PAGE->set_heading('Edit Form Fields');

// Get token from URL (primary method)
$token = optional_param('token', '', PARAM_RAW);
$form_id = 0;

if (!empty($token)) {
    $data = local_form_validate_token($token, 'edit');
    if (!$data) {
        print_error('Invalid or expired edit form link. Please request a new link.');
    }
    $form_id = (int)$data['formid'];
} else {
    $form_id = optional_param('formid', 0, PARAM_INT);
    if ($form_id > 0) {
        $signed_url = local_form_generate_signed_url($form_id, 'edit');
        redirect($signed_url);
    }
}

if ($form_id <= 0) {
    print_error('Invalid form ID');
}

// Set page URL with signed token
$current_url = local_form_generate_signed_url($form_id, 'edit');
$PAGE->set_url($current_url);

echo $OUTPUT->header();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    
    $posted_form_id = optional_param('formid', 0, PARAM_INT);
    $posted_token = optional_param('form_token', '', PARAM_RAW);

    if (!empty($posted_token)) {
        $post_data = local_form_validate_token($posted_token, 'edit');
        if (!$post_data || (int)$post_data['formid'] !== $posted_form_id) {
            print_error('Invalid form submission token.');
        }
    } else if (!empty($token)) {
        $post_data = local_form_validate_token($token, 'edit');
        if (!$post_data || (int)$post_data['formid'] !== $posted_form_id) {
            print_error('Invalid form submission token.');
        }
    }

    // Get all POST data
    $section_ids = $_POST['section_id'] ?? [];
    $section_titles = $_POST['section_title'] ?? [];
    $section_orders = $_POST['sort_order'] ?? [];
    
    // Field data - will be organized by section
    $field_ids_by_section = [];
    $field_names_by_section = [];
    $field_types_by_section = [];
    $field_labels_by_section = [];
    $field_options_by_section = [];
    $field_required_by_section = [];
    $field_orders_by_section = [];
    
    // Parse field data by section
    foreach ($_POST as $key => $value) {
        // Field IDs
        if (strpos($key, 'field_id_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_ids_by_section[$section_index][$field_index] = $value;
            }
        }
        // Field names
        elseif (strpos($key, 'field_name_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_names_by_section[$section_index][$field_index] = $value;
            }
        }
        // Field types
        elseif (strpos($key, 'field_type_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_types_by_section[$section_index][$field_index] = $value;
            }
        }
        // Field labels
        elseif (strpos($key, 'field_label_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_labels_by_section[$section_index][$field_index] = $value;
            }
        }
        // Field options
        elseif (strpos($key, 'field_options_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_options_by_section[$section_index][$field_index] = $value;
            }
        }
        // Field required
        elseif (strpos($key, 'field_required_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_required_by_section[$section_index][$field_index] = ($value === 'on') ? 1 : 0;
            }
        }
        // Field orders
        elseif (strpos($key, 'field_order_') === 0) {
            $parts = explode('_', $key);
            if (count($parts) >= 3) {
                $section_index = $parts[2];
                $field_index = $parts[3] ?? 0;
                $field_orders_by_section[$section_index][$field_index] = (int)$value;
            }
        }
    }
    
    // Get delete arrays
    $delete_field_ids = $_POST['field_delete'] ?? [];
    $delete_section_ids = $_POST['section_delete'] ?? [];

    // Prepare SQL statements
    $sql_insert_section = "INSERT INTO {form_sections} (formid, section_title, sort_order) VALUES (?, ?, ?)";
    $sql_update_section = "UPDATE {form_sections} SET section_title = ?, sort_order = ? WHERE id = ?";
    $sql_delete_section = "DELETE FROM {form_sections} WHERE id = ?";
    $sql_insert_field = "INSERT INTO {form_data} (formid, section_id, formname, formtype, formlabel, fieldoption, required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $sql_update_field = "UPDATE {form_data} SET section_id = ?, formname = ?, formtype = ?, formlabel = ?, fieldoption = ?, required = ?, sort_order = ? WHERE id = ?";
    $sql_delete_field = "DELETE FROM {form_data} WHERE id = ?";

    $transaction = $DB->start_delegated_transaction();

    try {
        // Handle sections
        $section_id_mapping = [];
        foreach ($section_ids as $index => $section_id) {
            $title = trim($section_titles[$index] ?? '');
            $order = $section_orders[$index] ?? $index;
            
            // Skip if this section is marked for deletion
            if (in_array($section_id, $delete_section_ids)) {
                continue;
            }
            
            if ($section_id !== 'new' && $section_id > 0) {
                // Update existing section
                $DB->execute($sql_update_section, [$title, $order, $section_id]);
                $section_id_mapping[$index] = $section_id;
            } else {
                // Insert new section
                $new_section = new stdClass();
                $new_section->formid = $form_id;
                $new_section->section_title = $title;
                $new_section->sort_order = $order;
                $new_section_id = $DB->insert_record('form_sections', $new_section);
                $section_id_mapping[$index] = $new_section_id;
            }
        }
        
        // Handle deletions
        // Delete sections
        if (!empty($delete_section_ids)) {
            foreach ($delete_section_ids as $section_id) {
                if ($section_id !== 'new' && $section_id > 0) {
                    // Delete all fields in this section first
                    $DB->delete_records('form_data', ['section_id' => $section_id]);
                    // Then delete the section
                    $DB->execute($sql_delete_section, [$section_id]);
                }
            }
        }
        
        // Delete fields
        if (!empty($delete_field_ids)) {
            foreach ($delete_field_ids as $field_id) {
                if ($field_id !== 'new' && $field_id > 0) {
                    $DB->execute($sql_delete_field, [$field_id]);
                }
            }
        }
        
        // Handle fields by section
        foreach ($section_id_mapping as $section_index => $actual_section_id) {
            // Get fields for this section
            $field_ids = $field_ids_by_section[$section_index] ?? [];
            $field_names = $field_names_by_section[$section_index] ?? [];
            $field_types = $field_types_by_section[$section_index] ?? [];
            $field_labels = $field_labels_by_section[$section_index] ?? [];
            $field_options = $field_options_by_section[$section_index] ?? [];
            $field_required = $field_required_by_section[$section_index] ?? [];
            $field_orders = $field_orders_by_section[$section_index] ?? [];
            
            foreach ($field_ids as $field_index => $field_id) {
                // Skip if this field is marked for deletion
                if (in_array($field_id, $delete_field_ids)) {
                    continue;
                }
                
                $name = $field_names[$field_index] ?? '';
                $type = $field_types[$field_index] ?? 'text';
                $label = $field_labels[$field_index] ?? '';
                $options = $field_options[$field_index] ?? '';
                $required = $field_required[$field_index] ?? 0;
                $order = $field_orders[$field_index] ?? $field_index;
                
                if ($field_id === 'new') {
                    // Insert new field
                    $params = [
                        $form_id,
                        $actual_section_id,
                        $name,
                        $type,
                        $label,
                        $options,
                        $required,
                        $order
                    ];
                    $DB->execute($sql_insert_field, $params);
                } else if ($field_id > 0) {
                    // Update existing field
                    $params = [
                        $actual_section_id,
                        $name,
                        $type,
                        $label,
                        $options,
                        $required,
                        $order,
                        $field_id
                    ];
                    $DB->execute($sql_update_field, $params);
                }
            }
        }

        // Commit the transaction
        $transaction->allow_commit();
        
        // Redirect with token for security
        if (!empty($token)) {
            $redirect_url = local_form_generate_signed_url($form_id, 'edit');
        } else {
            $redirect_url = new moodle_url('/local/form/manageform.php');
        }
                    $redirect_url = new moodle_url('/local/form/manageform.php');


        redirect($redirect_url, 'Form fields updated successfully!');
    } catch (Exception $e) {
        // Rollback the transaction and handle the exception
        $transaction->rollback($e);
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    }
}

// Retrieve form sections and fields from the database
$sections = $DB->get_records('form_sections', ['formid' => $form_id], 'sort_order');
$fields = $DB->get_records('form_data', ['formid' => $form_id], 'section_id, sort_order, id');

// Display the form with token in action
echo '<div class="form-builder-container">';
echo '<div class="form-header">';
echo '<h2><i class="fa fa-edit"></i> Form Builder</h2>';
echo '<p class="subtitle">Drag and drop to reorder sections and fields</p>';
echo '</div>';

echo '<form method="post" action="">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
echo '<input type="hidden" name="formid" value="' . $form_id . '" />';
if (!empty($token)) {
    echo '<input type="hidden" name="form_token" value="' . htmlspecialchars($token) . '" />';
}

echo '<div id="form-wrapper">';
echo '<div id="sections-container">';

// Existing sections
if ($sections) {
    $section_counter = 0;
    foreach ($sections as $section) {
        $section_title = htmlspecialchars($section->section_title);
        $section_id = $section->id;

        echo "<div class='section-card' id='section_{$section_counter}'>";
        echo "<div class='section-header'>";
        echo "<div class='section-title'>";
        echo "<span class='section-number'>Section " . ($section_counter + 1) . "</span>";
        echo "<input type='hidden' name='section_id[]' value='{$section_id}' />";
        echo "<input type='hidden' name='sort_order[]' value='{$section_counter}' />";
        echo "<input type='text' name='section_title[]' value='{$section_title}' class='section-title-input' placeholder='Enter section title...' />";
        echo "</div>";
        echo "<div class='section-controls'>";
        echo "<button type='button' class='btn-control btn-move-up' onclick='moveSection({$section_counter}, -1)' title='Move Up'><i class='fa fa-arrow-up'></i></button>";
        echo "<button type='button' class='btn-control btn-move-down' onclick='moveSection({$section_counter}, 1)' title='Move Down'><i class='fa fa-arrow-down'></i></button>";
        echo "<button type='button' class='btn-control btn-remove-section' onclick='removeSection(this)' title='Remove Section'><i class='fa fa-trash'></i></button>";
        echo "</div>";
        echo "</div>"; // End section-header
        
        echo "<div id='fields-container_{$section_counter}' class='fields-container'>";
        
        // Get fields for this section
        $section_fields = array_filter($fields, function($field) use ($section_id) {
            return $field->section_id == $section_id;
        });
        
        // Sort fields by sort_order
        usort($section_fields, function($a, $b) {
            return ($a->sort_order ?? 0) <=> ($b->sort_order ?? 0);
        });
        
        $field_counter = 0;
        foreach ($section_fields as $field) {
            $field_label = htmlspecialchars($field->formlabel);
            $field_name = htmlspecialchars($field->formname);
            $field_type = htmlspecialchars($field->formtype);
            $field_options = htmlspecialchars($field->fieldoption);
            $is_required = $field->required ? 'checked' : '';
            $field_sort_order = $field->sort_order ?? $field_counter;

            echo "<div class='field-card' data-field-index='{$field_counter}'>";
            echo "<div class='field-header'>";
            echo "<span class='field-icon'><i class='fa fa-grip-vertical'></i></span>";
            echo "<span class='field-number'>Field " . ($field_counter + 1) . "</span>";
            echo "</div>";
            
            echo "<div class='field-content'>";
            echo "<input type='hidden' name='field_id_{$section_counter}_{$field_counter}' value='{$field->id}' />";
            echo "<input type='hidden' name='field_order_{$section_counter}_{$field_counter}' value='{$field_sort_order}' class='field-order-input' />";

            echo "<div class='form-row'>";
            echo "<div class='form-group'>";
            echo "<label>Label</label>";
            echo "<input type='text' name='field_label_{$section_counter}_{$field_counter}' value='{$field_label}' class='form-control' placeholder='Enter field label' />";
            echo "</div>";
            
            echo "<div class='form-group'>";
            echo "<label>Name</label>";
            echo "<input type='text' name='field_name_{$section_counter}_{$field_counter}' value='{$field_name}' class='form-control' placeholder='Enter field name' />";
            echo "</div>";
            echo "</div>";

            echo "<div class='form-row'>";
            echo "<div class='form-group'>";
            echo "<label>Type</label>";
            echo "<select name='field_type_{$section_counter}_{$field_counter}' class='form-control'>";
            foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file', 'time', 'number', 'email'] as $type) {
                $selected = $field->formtype === $type ? ' selected' : '';
                echo "<option value='{$type}'{$selected}>" . ucfirst($type) . "</option>";
            }
            echo "</select>";
            echo "</div>";
            
            echo "<div class='form-group'>";
            echo "<label>Options (comma separated)</label>";
            echo "<input type='text' name='field_options_{$section_counter}_{$field_counter}' value='{$field_options}' class='form-control' placeholder='Option 1, Option 2, Option 3' />";
            echo "</div>";
            echo "</div>";

            echo "<div class='field-actions'>";
            echo "<label class='checkbox-container'>";
            echo "<input type='checkbox' name='field_required_{$section_counter}_{$field_counter}' {$is_required} />";
            echo "<span class='checkmark'></span>";
            echo " Required Field";
            echo "</label>";
            
            echo "<label class='checkbox-container delete-checkbox'>";
            echo "<input type='checkbox' class='field-delete-checkbox' name='field_delete[]' value='{$field->id}' />";
            echo "<span class='checkmark'></span>";
            echo " Delete Field";
            echo "</label>";
            
            echo "<div class='move-buttons'>";
            echo "<button type='button' class='btn-move btn-move-up' onclick='moveField(this, -1)' title='Move Up'><i class='fa fa-arrow-up'></i></button>";
            echo "<button type='button' class='btn-move btn-move-down' onclick='moveField(this, 1)' title='Move Down'><i class='fa fa-arrow-down'></i></button>";
            echo "<button type='button' class='btn-move btn-remove-field' onclick='removeFieldByButton(this)' title='Remove Field'><i class='fa fa-trash'></i></button>";
            echo "</div>";
            echo "</div>";
            
            echo "</div>";
            echo "</div>";
            
            $field_counter++;
        }
        echo '</div>'; // End of fields-container

        echo '<div class="section-footer">';
        echo '<button type="button" class="btn-add-field" onclick="addField(' . $section_counter . ')"><i class="fa fa-plus-circle"></i> Add New Field</button>';
        echo '</div>';

        echo '</div>'; // End of section-card
        $section_counter++;
    }
}

echo '</div>'; // Close sections-container

// Save button section - ALWAYS at the bottom
echo '<div class="form-footer">';
echo '<button type="button" class="btn-add-section" onclick="addSection()"><i class="fa fa-plus-square"></i> Add New Section</button>';
echo '<input type="submit" class="btn-save" value="Save Changes" />';
echo '</div>';

echo '</div>'; // Close form-wrapper
echo '</form>';
echo '</div>'; // Close form-builder-container


?>

<script>
    let sectionCounter = <?php echo isset($section_counter) ? $section_counter : 0; ?>;
    let fieldCounters = {};

    // Initialize field counters for existing sections
    <?php if ($sections): ?>
        <?php $section_index = 0; ?>
        <?php foreach ($sections as $section): ?>
            fieldCounters[<?php echo $section_index; ?>] = <?php 
                $section_fields = array_filter($fields, function($field) use ($section) {
                    return $field->section_id == $section->id;
                });
                echo count($section_fields);
            ?>;
            <?php $section_index++; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    /* ================= ADD FIELD ================= */
    function addField(sectionIndex) {
        const fieldsContainer = document.getElementById(`fields-container_${sectionIndex}`);
        
        // Initialize counter for this section if not exists
        if (!fieldCounters[sectionIndex]) {
            fieldCounters[sectionIndex] = 0;
        }
        
        const fieldIndex = fieldCounters[sectionIndex]++;
        const newFieldOrder = fieldsContainer.querySelectorAll('.field-card:not([style*="display: none"])').length;

        const fieldHtml = `
    <div class="field-card" data-field-index="${fieldIndex}">
        <div class="field-header">
            <span class="field-icon"><i class="fa fa-grip-vertical"></i></span>
            <span class="field-number">New Field</span>
        </div>
        <div class="field-content">
            <input type="hidden" name="field_id_${sectionIndex}_${fieldIndex}" value="new">
            <input type="hidden" name="field_order_${sectionIndex}_${fieldIndex}" value="${newFieldOrder}" class="field-order-input">

            <div class="form-row">
                <div class="form-group">
                    <label>Label</label>
                    <input type="text" name="field_label_${sectionIndex}_${fieldIndex}" class="form-control" placeholder="Enter field label">
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="field_name_${sectionIndex}_${fieldIndex}" class="form-control" placeholder="Enter field name">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="field_type_${sectionIndex}_${fieldIndex}" class="form-control">
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                        <option value="file">File</option>
                        <option value="time">Time</option>
                        <option value="number">Number</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Options (comma separated)</label>
                    <input type="text" name="field_options_${sectionIndex}_${fieldIndex}" class="form-control" placeholder="Option 1, Option 2, Option 3">
                </div>
            </div>

            <div class="field-actions">
                <label class="checkbox-container">
                    <input type="checkbox" name="field_required_${sectionIndex}_${fieldIndex}">
                    <span class="checkmark"></span>
                    Required Field
                </label>
                <label class="checkbox-container delete-checkbox">
                    <input type="checkbox" class="field-delete-checkbox" name="field_delete[]" value="new">
                    <span class="checkmark"></span>
                    Delete Field
                </label>
                <div class="move-buttons">
                    <button type="button" class="btn-move btn-move-up" onclick="moveField(this, -1)" title="Move Up"><i class="fa fa-arrow-up"></i></button>
                    <button type="button" class="btn-move btn-move-down" onclick="moveField(this, 1)" title="Move Down"><i class="fa fa-arrow-down"></i></button>
                    <button type="button" class="btn-move btn-remove-field" onclick="removeFieldByButton(this)" title="Remove Field"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
    `;

        fieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
        
        // Scroll to the new field
        const newField = fieldsContainer.lastElementChild;
        newField.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Update field numbers
        updateFieldNumbers(fieldsContainer);
    }

    /* ================= ADD SECTION ================= */
    function addSection() {
        const sectionsContainer = document.getElementById('sections-container');
        const formFooter = document.querySelector('.form-footer');
        const sectionIndex = sectionCounter++;

        // Initialize field counter for new section
        fieldCounters[sectionIndex] = 0;

        const sectionHtml = `
    <div class="section-card" id="section_${sectionIndex}">
        <div class="section-header">
            <div class="section-title">
                <span class="section-number">New Section</span>
                <input type="hidden" name="section_id[]" value="new">
                <input type="hidden" name="sort_order[]" value="${sectionIndex}">
                <input type="text" name="section_title[]" class="section-title-input" placeholder="Enter section title...">
            </div>
            <div class="section-controls">
                <button type="button" class="btn-control btn-move-up" onclick="moveSection(${sectionIndex}, -1)" title="Move Up"><i class="fa fa-arrow-up"></i></button>
                <button type="button" class="btn-control btn-move-down" onclick="moveSection(${sectionIndex}, 1)" title="Move Down"><i class="fa fa-arrow-down"></i></button>
                <button type="button" class="btn-control btn-remove-section" onclick="removeSection(this)" title="Remove Section"><i class="fa fa-trash"></i></button>
            </div>
        </div>
        <div id="fields-container_${sectionIndex}" class="fields-container"></div>
        <div class="section-footer">
            <button type="button" class="btn-add-field" onclick="addField(${sectionIndex})"><i class="fa fa-plus-circle"></i> Add New Field</button>
        </div>
    </div>
    `;

        // Insert the new section right before the footer
        formFooter.insertAdjacentHTML('beforebegin', sectionHtml);

        // Focus on the new section title input
        const newSection = document.getElementById(`section_${sectionIndex}`);
        const titleInput = newSection.querySelector('.section-title-input');
        titleInput.focus();
        
        // Scroll to the new section
        newSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Update section numbers
        updateSectionNumbers();
    }

    /* ================= MOVE SECTION ================= */
    function moveSection(sectionIndex, direction) {
        const sectionElement = document.getElementById(`section_${sectionIndex}`);
        const sectionsContainer = document.getElementById('sections-container');
        const formFooter = document.querySelector('.form-footer');
        
        if (!sectionElement || !sectionsContainer) return;
        
        // Get all visible sections (excluding the form-footer)
        const sections = Array.from(sectionsContainer.querySelectorAll('.section-card:not([style*="display: none"])'));
        const currentIndex = sections.indexOf(sectionElement);
        
        if (direction === 1 && currentIndex < sections.length - 1) {
            // Move down - insert after the next section
            const nextSection = sections[currentIndex + 1];
            sectionsContainer.insertBefore(sectionElement, nextSection.nextElementSibling);
        } else if (direction === -1 && currentIndex > 0) {
            // Move up - insert before the previous section
            const prevSection = sections[currentIndex - 1];
            sectionsContainer.insertBefore(sectionElement, prevSection);
        }
        
        // Update section numbers and sort orders
        updateSectionNumbers();
    }

    /* ================= REMOVE SECTION ================= */
    function removeSection(button) {
        if (confirm('Are you sure you want to remove this section? All fields in this section will be deleted.')) {
            const section = button.closest('.section-card');
            
            // Get the section ID
            const sectionIdInput = section.querySelector('input[name="section_id[]"]');
            if (sectionIdInput && sectionIdInput.value !== 'new') {
                // Create hidden input for section deletion
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'section_delete[]';
                deleteInput.value = sectionIdInput.value;
                section.appendChild(deleteInput);
            }
            
            // Also mark all fields in this section for deletion
            const fieldCheckboxes = section.querySelectorAll('.field-delete-checkbox');
            fieldCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            
            // Hide the section
            section.style.display = 'none';
            
            updateSectionNumbers();
        }
    }

    /* ================= REMOVE FIELD BY BUTTON ================= */
    function removeFieldByButton(button) {
        if (confirm('Are you sure you want to remove this field?')) {
            const fieldElement = button.closest('.field-card');
            
            // Find the delete checkbox and check it
            const deleteCheckbox = fieldElement.querySelector('.field-delete-checkbox');
            if (deleteCheckbox) {
                deleteCheckbox.checked = true;
            }
            
            // Hide the field
            fieldElement.style.display = 'none';

            // Update field orders and numbers after removal
            const fieldsContainer = fieldElement.parentElement;
            if (fieldsContainer) {
                updateFieldOrders(fieldsContainer);
                updateFieldNumbers(fieldsContainer);
            }
        }
    }

    /* ================= MOVE FIELD ================= */
    function moveField(button, direction) {
        const fieldElement = button.closest('.field-card');
        const fieldsContainer = fieldElement.parentElement;

        if (!fieldElement || !fieldsContainer) return;

        // Get all visible field cards in this container
        const fieldCards = Array.from(fieldsContainer.querySelectorAll('.field-card:not([style*="display: none"])'));
        const currentIndex = fieldCards.indexOf(fieldElement);

        if (direction === 1 && currentIndex < fieldCards.length - 1) {
            // Move down
            const nextField = fieldCards[currentIndex + 1];
            fieldsContainer.insertBefore(fieldElement, nextField.nextElementSibling);
        } else if (direction === -1 && currentIndex > 0) {
            // Move up
            const prevField = fieldCards[currentIndex - 1];
            fieldsContainer.insertBefore(fieldElement, prevField);
        }

        // Update all field order inputs and numbers after moving
        updateFieldOrders(fieldsContainer);
        updateFieldNumbers(fieldsContainer);
    }

    /* ================= UPDATE FIELD ORDERS ================= */
    function updateFieldOrders(fieldsContainer) {
        const fieldCards = Array.from(fieldsContainer.querySelectorAll('.field-card:not([style*="display: none"])'));

        fieldCards.forEach((field, index) => {
            const orderInput = field.querySelector('.field-order-input');
            if (orderInput) {
                orderInput.value = index;
            }
        });
    }

    /* ================= UPDATE SECTION NUMBERS ================= */
    function updateSectionNumbers() {
        const sections = document.querySelectorAll('.section-card:not([style*="display: none"])');
        sections.forEach((section, index) => {
            const numberSpan = section.querySelector('.section-number');
            const sortOrderInput = section.querySelector('input[name="sort_order[]"]');
            if (numberSpan) {
                numberSpan.textContent = `Section ${index + 1}`;
            }
            if (sortOrderInput) {
                sortOrderInput.value = index;
            }
        });
    }

    /* ================= UPDATE FIELD NUMBERS ================= */
    function updateFieldNumbers(fieldsContainer) {
        const fieldCards = fieldsContainer.querySelectorAll('.field-card:not([style*="display: none"])');
        fieldCards.forEach((field, index) => {
            const numberSpan = field.querySelector('.field-number');
            if (numberSpan) {
                numberSpan.textContent = `Field ${index + 1}`;
            }
        });
    }
    
</script>

<style>
/* ========== MAIN CONTAINER ========== */
.form-builder-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

/* ========== HEADER ========== */
.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 12px 12px 0 0;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.form-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 15px;
}

.form-header .subtitle {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 16px;
}

/* ========== SECTION CARD ========== */
.section-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.section-card:hover {
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.section-header {
    background: linear-gradient(to right, #f8f9fa, #e9ecef);
    padding: 20px 25px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.section-number {
    background: #667eea;
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    min-width: 80px;
    text-align: center;
}

.section-title-input {
    flex: 1;
    padding: 10px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.3s;
}

.section-title-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.section-controls {
    display: flex;
    gap: 10px;
}

/* ========== FIELDS CONTAINER ========== */
.fields-container {
    padding: 25px;
    background: #f8f9fa;
    min-height: 50px;
}

/* ========== FIELD CARD ========== */
.field-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    margin-bottom: 20px;
    transition: all 0.3s;
}

.field-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 15px rgba(102, 126, 234, 0.1);
}

.field-header {
    background: #f8f9fa;
    padding: 12px 20px;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    align-items: center;
    gap: 15px;
}

.field-icon {
    color: #6c757d;
    cursor: move;
}

.field-number {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.field-content {
    padding: 25px;
}

/* ========== FORM ROWS ========== */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* ========== FIELD ACTIONS ========== */
.field-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    flex-wrap: wrap;
    gap: 15px;
}

.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #495057;
    user-select: none;
}

.checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    width: 20px;
    height: 20px;
    background: white;
    border: 2px solid #6c757d;
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: all 0.3s;
}

.checkbox-container:hover input ~ .checkmark {
    background-color: #f8f9fa;
}

.checkbox-container input:checked ~ .checkmark {
    background: #667eea;
    border-color: #667eea;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 6px;
    top: 2px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-container input:checked ~ .checkmark:after {
    display: block;
}

.delete-checkbox .checkmark {
    border-color: #dc3545;
}

.delete-checkbox input:checked ~ .checkmark {
    background: #dc3545;
    border-color: #dc3545;
}

.move-buttons {
    display: flex;
    gap: 10px;
}

/* ========== BUTTONS ========== */
.btn-control, .btn-move, .btn-add-field, .btn-add-section, .btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-control {
    width: 40px;
    height: 40px;
    padding: 0;
    justify-content: center;
    background: white;
    border: 1px solid #dee2e6;
    color: #6c757d;
}

.btn-control:hover {
    background: #f8f9fa;
    color: #495057;
    transform: translateY(-2px);
}

.btn-move {
    width: 36px;
    height: 36px;
    padding: 0;
    justify-content: center;
    background: white;
    border: 1px solid #dee2e6;
    color: #6c757d;
}

.btn-move:hover {
    background: #f8f9fa;
    color: #495057;
}

.btn-add-field {
    background: #e7f5ff;
    color: #0d6efd;
    border: 1px solid #a5d8ff;
}

.btn-add-field:hover {
    background: #0d6efd;
    color: white;
    transform: translateY(-2px);
}

.btn-add-section {
    background: #e7f5ff;
    color: #0d6efd;
    border: 2px dashed #a5d8ff;
    font-weight: 600;
    padding: 12px 25px;
}

.btn-add-section:hover {
    background: #0d6efd;
    color: white;
    border-style: solid;
    transform: translateY(-2px);
}

.btn-save {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
    padding: 12px 35px;
    font-weight: 600;
    font-size: 16px;
    margin-left: auto;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(25, 135, 84, 0.3);
}

/* ========== SECTION FOOTER ========== */
.section-footer {
    padding: 20px 25px;
    background: #f8f9fa;
    border-top: 1px solid #e1e5e9;
    text-align: center;
}

.form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px 0;
    gap: 20px;
    flex-wrap: wrap;
}

/* ========== RESPONSIVE ========== */
@media (max-width: 768px) {
    .form-header {
        padding: 20px;
    }
    
    .form-header h2 {
        font-size: 22px;
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .section-controls {
        justify-content: flex-end;
    }
    
    .form-footer {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-save {
        margin-left: 0;
        width: 100%;
    }
    
    .btn-add-section {
        width: 100%;
        justify-content: center;
    }
}

/* ========== ANIMATIONS ========== */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-card, .field-card {
    animation: fadeIn 0.3s ease-out;
}
</style>

<?php
echo $OUTPUT->footer();
?>
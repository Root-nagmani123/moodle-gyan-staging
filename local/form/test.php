<?php
require_once('../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;
$formid = optional_param('formid', '', PARAM_INT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/form/addform.php', array('formid' => $formid));
$PAGE->set_title('Display Form');
$renderer = $PAGE->get_renderer('local_form');
$PAGE->requires->js_call_amd('local_form/main', 'init');

// Fetch the formid from URL parameter
$formid = optional_param('formid', 0, PARAM_INT);

if ($formid <= 0) {
    print_error('Invalid form ID');
}

// Fetch button data from the local_form table
$sql = "SELECT id, name FROM {local_form} WHERE visible = 1 AND id = $formid ORDER BY sortorder ASC";
$button = $DB->get_record_sql($sql);
// print_object($button);die;


// Fetch description from the local_form table based on formid
$descriptionSql = 'SELECT description,name FROM {local_form} WHERE id = ? AND visible = 1';
$descriptionRecord = $DB->get_record_sql($descriptionSql, [$formid]);
$description = $descriptionRecord ? $descriptionRecord->description : '';


// Fetch sections for the given form
$sectionsSql = 'SELECT * FROM {form_sections} WHERE formid = ? ORDER BY sort_order';
$sections = $DB->get_records_sql($sectionsSql, [$formid]);

$countries = $DB->get_records('country', null, 'id');
$states = $DB->get_records('state', null, 'id');
$districts = $DB->get_records('district', null, 'id');
$languages = $DB->get_records('languages', null, 'id');


// Fetch fields for all sections, ordered by section_id, row_index, col_index
$fieldsSql = 'SELECT * FROM {form_data} WHERE formid = ? ORDER BY section_id';
$fields = $DB->get_records_sql($fieldsSql, [$formid]);

$userId = $USER->id;
$submissionSql = 'SELECT *  FROM {form_submissions} WHERE formid = ? AND uid = ?';
$submissionRecord = $DB->get_records_sql($submissionSql, [$formid, $userId]);
$submittedValues = $submissionRecord ? $submissionRecord : [];

// Organize fields by section, row_index, and col_index
$fieldsBySection = [];
$onlyfields = [];
$headersBySection = []; // New array to store headers

foreach ($fields as $field) {
    // Organize fields
    if ($field->format === 'table') {
        $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
    } else {
        $onlyfields[] = $field;
    }

    // Collect headers
    if (!isset($headersBySection[$field->section_id][$field->col_index]) || empty($headersBySection[$field->section_id][$field->col_index])) {
        $headersBySection[$field->section_id][$field->col_index] = $field->header;
    }
}

echo $OUTPUT->header();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
        }

        .header-container .formpagetitle {
            text-align: center;
            flex: 1;
        }

        .header-container .home_leftimage,
        .header-container .home_rightimage {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
        }

        .formpagetitle h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .container {
            display: flex;
            height: calc(100vh - 60px);
            /* Adjust based on header height */
        }

        .description {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 0 auto 20px auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f4f4f4;
            text-align: center;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            background-color: #294b6a;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            text-align: center;
            /* Center the text */

        }


        .section-container {
            margin-bottom: 40px;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            position: fixed;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #ecf0f1;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 15px;
            text-decoration: none;
            color: #ecf0f1;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #34495e;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #1abc9c;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            background-color: #ffffff;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            box-sizing: border-box;
        }

        .section-container {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        td {
            vertical-align: top;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .horizontal-radio-group label {
            display: inline-flex;
            align-items: center;
            margin-right: 5px;
        }

        /* Grid container */
        .form-grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            /* 3 columns of equal width */
            gap: 16px;
            /* Space between grid items */
            padding: 16px;
            /* Padding around the grid */
            box-sizing: border-box;
            /* Include padding in the width calculation */
        }

        /* Grid items */
        .form-grid-item input,
        .grid-item select,
        .grid-item textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        /* Ensure form elements fill the width of the container */
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            /* Full width */
            box-sizing: border-box;
            padding: 12px;
            /* Include padding and border in width calculation */
        }


        /* Responsive design */
        @media (max-width: 1200px) {
            .form-grid-container {
                grid-template-columns: repeat(2, 1fr);
                /* 2 columns for medium screens */
            }
        }

        @media (max-width: 768px) {
            .form-grid-container {
                grid-template-columns: 1fr;
                /* 1 column for small screens */
            }
        }

        .submit-button-container {
            text-align: center;
            margin-top: 20px;
        }

        .submit-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .submit-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <form method="post" action="formsubmit.php?formid=<?php echo htmlspecialchars($formid); ?>" enctype="multipart/form-data">
        <div class="header-container" style="position:sticky; z-index:1020;">
            <div class="home_leftimage">
                <?php echo $renderer->left_form_logo(); ?>
            </div>

            <div class="formpagetitle">
                <h2><?php echo $descriptionRecord ? $descriptionRecord->name : ''; ?></h2>
            </div>

            <div class="home_rightimage">
                <?php echo $renderer->right_form_logo(); ?>
            </div>
        </div>

        <div class="container">
            <div class="sidebar">
                <h2>FORM LIST</h2>
                <?php
                if ($button): ?>
                    <a href="addform.php?formid=<?php echo htmlspecialchars($button->id); ?>">
                        <?php echo htmlspecialchars($button->name); ?>
                    </a>
                <?php else: ?>
                    <p>No form found with the specified ID.</p>
                <?php endif; ?>
            </div>

            <div class="content">
                <?php if (!empty($description)): ?>
                    <div class="description">
                        <?php echo htmlspecialchars($description); ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($sections as $section): ?>
                    <div class="section-container">
                        <div class="section-title"><?php echo htmlspecialchars($section->section_title); ?></div>

                        <?php if (isset($fieldsBySection[$section->id])): ?>

                            <?php
                            // Determine number of rows and columns
                            $rows = array_keys($fieldsBySection[$section->id]);
                            $maxRow = max($rows);
                            $cols = [];
                            $headers = []; // Initialize headers array
                            $currentHeader = '';

                            foreach ($fieldsBySection[$section->id] as $rowFields) {
                                $cols = array_merge($cols, array_keys($rowFields));
                            }
                            $maxCol = max($cols);

                            // Collect headers for the table
                            for ($i = 0; $i <= $maxCol; $i++) {
                                if (isset($headersBySection[$section->id][$i]) && !empty($headersBySection[$section->id][$i])) {
                                    $currentHeader = $headersBySection[$section->id][$i];
                                }
                                $headers[$i] = $currentHeader;
                            }
                            ?>

                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach ($headers as $index => $header): ?>
                                            <th><?php echo htmlspecialchars($header); ?></th>
                                            <input type="hidden" name="header_<?php echo $section->id . '_' . $index; ?>" value="<?php echo htmlspecialchars($header); ?>">
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i <= $maxRow; $i++): ?>
                                        <tr>
                                            <?php for ($j = 0; $j <= $maxCol; $j++): ?>
                                                <td>
                                                    <?php
                                                    if (isset($fieldsBySection[$section->id][$i][$j])) {
                                                        $field = $fieldsBySection[$section->id][$i][$j];
                                                        $fieldName = "table_{$j}_{$i}"; // Naming format: table_colindex_rowindex
                                                        switch ($field->field_type) {
                                                            case 'Text':
                                                                echo '<label>' . htmlspecialchars($field->field_title) . '</label>';
                                                                echo '<input type="text" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                break;

                                                            case 'Label':
                                                                echo '<label>' . htmlspecialchars($field->field_title) . '</label>';
                                                                break;

                                                            case 'Date':
                                                                echo '<label for="' . htmlspecialchars($field->field_title) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                                                echo '<input type="date" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($field->field_title) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                break;

                                                            case 'Email':
                                                                echo '<label for="' . htmlspecialchars($field->field_title) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                                                echo '<input type="email" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($field->field_title) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                break;

                                                            case 'Textarea':
                                                                echo '<textarea readonly>' . htmlspecialchars($field->field_title) . '</textarea>';
                                                                break;

                                                            case 'Select Box':
                                                                $options = explode(',', $field->field_options);
                                                                echo '<select name="' . htmlspecialchars($fieldName) . '">';
                                                                foreach ($options as $option) {
                                                                    echo '<option value="' . htmlspecialchars(trim($option)) . '">' . htmlspecialchars(trim($option)) . '</option>';
                                                                }
                                                                echo '</select>';
                                                                break;

                                                            case 'Radio Button':
                                                                $options = explode(',', $field->field_options);
                                                                echo '<div class="horizontal-radio-group">';
                                                                foreach ($options as $option) {
                                                                    echo '<label>';
                                                                    echo '<input type="radio" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars(trim($option)) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                    echo htmlspecialchars(trim($option));
                                                                    echo '</label>';
                                                                }
                                                                echo '</div>';
                                                                break;

                                                            case 'Checkbox':
                                                                $options = explode(',', $field->field_checkbox_options);
                                                                foreach ($options as $option) {
                                                                    echo '<label><input type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars(trim($option)) . '"> ' . htmlspecialchars(trim($option)) . '</label><br>';
                                                                }
                                                                break;

                                                            case 'File Upload':
                                                                echo '<div class="form-group">';
                                                                echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                                                echo '<input type="file" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' onchange="previewImage(event, this)"/>';
                                                                echo '<div class="image-preview" id="image-preview-' . htmlspecialchars($fieldName) . '"></div>';
                                                                echo '</div>';
                                                                break;

                                                            case 'View/Download':
                                                                $label = htmlspecialchars($field->field_title);
                                                                $url = htmlspecialchars($field->field_url);
                                                                echo '<label><a href="' . $url . '" target="_blank">' . $label . '</a></label>';
                                                                break;

                                                            default:
                                                                echo '<p>Unknown field type</p>';
                                                                break;
                                                        }
                                                    } else {
                                                        echo '&nbsp;'; // Empty cell
                                                    }
                                                    ?>
                                                </td>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>

                            <!-- Capture field at (0,0) index -->
                            <?php
                            if (isset($fieldsBySection[$section->id][0][0])) {
                                $firstField = $fieldsBySection[$section->id][0][0];
                                $firstFieldName = "table_0_0"; // For (0,0) field
                            ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($firstFieldName); ?>" value="<?php echo htmlspecialchars($firstField->field_value ?? ''); ?>">
                            <?php } ?>

                        <?php else: ?>

                            <?php
                            $submittedMap = [];
                            foreach ($submittedValues as $submitted) {
                                $submittedMap[$submitted->fieldname] = $submitted->fieldvalue;
                            }
                            // Handle non-table format fields
                            echo '<div class="form-grid-container">';
                            foreach ($onlyfields as $field) {
                                $value = isset($submittedMap[$field->formname]) ? htmlspecialchars($submittedMap[$field->formname]) : '';
                                // print_object($field);
                                if ($field->section_id == $section->id) {
                                    echo '<div class="form-grid-item">';
                                    $fieldName = "field_{$field->formname}"; // Keep it simple
                                    switch ($field->formtype) {
                                        case 'text':
                                            echo '<div class="form-group">';
                                            echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="text" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'textarea':
                                            echo '<div class="form-group">';
                                            echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="textarea" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'email':
                                            echo '<div class="form-group">';
                                            echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="email" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'dropdown':
                                            echo '<div class="form-group">';
                                            echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<select name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . '>';
                                            $options = explode(',', $field->fieldoption);
                                            echo '<option value="">Please select an option</option>';
                                            foreach ($options as $option) {
                                                echo '<option value="' . htmlspecialchars(trim($option)) . '">' . htmlspecialchars(trim($option)) . '</option>';
                                            }
                                            echo '</select>';
                                            echo '</div>';
                                            break;

                                            case 'radio':
                                                $options = explode(',', $field->fieldoption); // Split options by comma
                                                echo '<div class="form-group horizontal-radio-group">';
                                                echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                                foreach ($options as $option) {
                                                    $option = trim($option); // Ensure no extra spaces
                                                    $isChecked = ($value === $option) ? 'checked' : ''; // Check if this option matches the submitted value
                                                    echo '<label><input type="radio" name="' . htmlspecialchars($field->formname) . '" value="' . htmlspecialchars($option) . '" ' . $isChecked . ' ' 
                                                    . ($field->required ? 'required' : '') . ' /> ' . htmlspecialchars($option) . '</label> ';
                                                }
                                                echo '</div>';
                                                break;
                                            
                                                case 'checkbox':
                                                    $options = explode(',', $field->fieldoption); // Split available options
                                                    $selectedValues = is_array($value) ? $value : explode(',', $value); // Ensure submitted values are an array
                                                    echo '<div class="form-group">';
                                                    echo '<fieldset>';
                                                    echo '<legend>' . htmlspecialchars($field->formlabel) . '</legend>';
                                                    foreach ($options as $option) {
                                                        $option = trim($option); // Clean up the option value
                                                        $isChecked = in_array($option, $selectedValues) ? 'checked' : ''; // Check if the option is selected
                                                        echo '<label><input type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $isChecked . ' ' 
                                                        . ($field->required ? 'required' : '') . ' /> ' . htmlspecialchars($option) . '</label><br>';
                                                    }
                                                    echo '</fieldset>';
                                                    echo '</div>';
                                                    break;
                                                

                                        case 'date':
                                            echo '<div class="form-group">';
                                            echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="date" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($field->formname) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                            case 'file':
                                                echo '<div class="form-group">';
                                                echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                                echo '<input type="file" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                
                                                // Check if a file was submitted and display it
                                                if (!empty($value)) {
                                                    $logo_path = new moodle_url('/local/form/pix/' . $value);

                                                    echo '<div class="image-preview" id="image-preview-' . htmlspecialchars($fieldName) . '">';
                                                    echo '<img src="' . htmlspecialchars($logo_path) . '" alt="Uploaded Image" style="max-width: 100%; max-height: 100px; margin-top: 10px;" />';
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="image-preview" id="image-preview-' . htmlspecialchars($fieldName) . '"></div>';
                                                }
                                                echo '</div>';
                                                break;
                                            

                                        default:
                                            echo '<p>Unknown field type</p>';
                                            break;
                                    }
                                    echo '</div>'; // Close grid item wrapper
                                }
                            }
                            echo '</div>'; // Close grid container
                            ?>

                        <?php endif; ?>
                    </div> <!-- End of section-container -->
                <?php endforeach; ?>

                <!-- Submit Button -->
                <div class="submit-button-container">
                    <button type="submit" class="submit-button">Submit</button>
                </div>
            </div> <!-- End of content -->
        </div> <!-- End of container -->
    </form>
</body>

<script src="<?php echo $CFG->wwwroot; ?>/local/form/amd/src/main.js"></script>

<script>
    function previewImage(event, input) {
        const fileList = input.files;
        const previewContainer = document.getElementById(`image-preview-${input.id}`);

        // Clear previous previews
        previewContainer.innerHTML = '';

        if (fileList.length > 0) {
            Array.from(fileList).forEach(file => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '100px'; // Set the desired width
                img.style.margin = '5px';
                img.style.display = 'inline-block';
                previewContainer.appendChild(img);
            });
        }
    }
</script>

</html>

<?php
echo $OUTPUT->footer();
?>
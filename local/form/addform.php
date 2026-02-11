<?php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

if (!isloggedin()) {
    redirect(new moodle_url('/login/index.php'));
}

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Get token from URL (primary method)
$token = optional_param('token', '', PARAM_RAW);
$formid = 0;

if (!empty($token)) {
    // Use the updated token validation that returns data array
    $data = local_form_validate_token($token, 'addform');
    if (!$data) {
        throw new moodle_exception('Invalid or expired form link. Please request a new link.');
    }
    $formid = (int)$data['formid'];
} else {
    // Legacy support for direct formid parameter
    $formid = optional_param('formid', 0, PARAM_INT);
    if ($formid > 0) {
        // If using old URL, redirect to signed URL for security
        $signed_url = local_form_generate_signed_url($formid, 'addform');
        redirect($signed_url);
    }
}

if ($formid <= 0) {
    throw new moodle_exception('Invalid form ID');
}

// Generate signed URL for this page (for page URL)
$current_url = local_form_generate_signed_url($formid, 'addform');
$PAGE->set_url($current_url);
$PAGE->set_title('Display Form');
$renderer = $PAGE->get_renderer('local_form');
$PAGE->requires->js_call_amd('local_form/main', 'init');

// Fetch form data from database
$sql = "SELECT id, name, description FROM {local_form} WHERE visible = 1 AND id = ?";
$form_data = $DB->get_record_sql($sql, [$formid]);

if (!$form_data) {
    throw new moodle_exception('Form not found or not visible');
}

// Fetch sections for the given form
$sectionsSql = 'SELECT * FROM {form_sections} WHERE formid = ? ORDER BY sort_order';
$sections = $DB->get_records_sql($sectionsSql, [$formid]);

$countries = $DB->get_records('country', null, 'id');
$states = $DB->get_records('state', null, 'id');
$districts = $DB->get_records('district', null, 'id');
$languages = $DB->get_records('languages', null, 'id');

// Fetch fields for all sections, ordered by section_id, row_index, col_index
// $fieldsSql = 'SELECT * FROM {form_data} WHERE formid = ? ORDER BY section_id ASC , id ASC';
$fieldsSql = 'SELECT * FROM {form_data} WHERE formid = ? ORDER BY section_id ASC, sort_order ASC, id ASC';
$fields = $DB->get_records_sql($fieldsSql, [$formid]);

$userId = $USER->id;
$submissionSql = 'SELECT * FROM {form_submissions} WHERE formid = ? AND uid = ?';
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
    <title>Display Form - <?php echo htmlspecialchars($form_data->name); ?></title>
    <style>
        /* Custom CSS for form display */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        /* Container for the entire form page */
        .form-page-container {
            max-width: 95%;
            margin: 15px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Custom header styling */
        .custom-header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 25px;
            background-color: #ffffff;
            border-bottom: 2px solid #e0e0e0;
            position: relative;
            z-index: 100;
        }

        .custom-header-container .formpagetitle {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }

        .custom-header-container .home_leftimage,
        .custom-header-container .home_rightimage {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
        }

        .formpagetitle h2 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .home_leftimage img,
        .home_rightimage img {
            max-height: 80px;
            width: auto;
            border-radius: 4px;
        }

        /* Main container with sidebar and content */
        .form-main-container {
            display: flex;
            min-height: calc(100vh - 140px);
        }

        /* Sidebar styling */
        .form-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2c3e50 0%, #1a2530 100%);
            padding: 0;
            position: relative;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .form-sidebar h2 {
            color: #ecf0f1;
            margin: 0;
            padding: 15px 18px 12px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: #34495e;
        }

        .form-sidebar a {
            display: block;
            padding: 12px 18px;
            text-decoration: none;
            color: #ecf0f1;
            margin: 0;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }

        .form-sidebar a:hover {
            background-color: #3a506b;
            color: #ffffff;
            border-left-color: #1abc9c;
        }

        .form-sidebar a.active {
            background-color: #1abc9c;
            color: white;
            border-left-color: #16a085;
            font-weight: 600;
        }

        /* Content area styling */
        .form-content-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #ffffff;
        }

        /* Form description */
        .form-description {
            font-size: 14px;
            color: #555;
            margin: 0 0 20px 0;
            padding: 15px;
            border: 1px solid #e3e6f0;
            border-radius: 6px;
            background-color: #f8f9fc;
            line-height: 1.5;
            border-left: 4px solid #1abc9c;
        }

        /* Section container */
        .form-section-container {
            margin-bottom: 25px;
            padding: 18px;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
            border-radius: 6px;
            padding: 10px 18px;
            margin: -18px -18px 18px -18px;
            text-align: center;
        }

        /* Table styling */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.06);
        }

        .form-table th {
            background-color: #f8f9fa;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border: 1px solid #dee2e6;
            font-size: 13px;
        }

        .form-table td {
            border: 1px solid #dee2e6;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
            background-color: #ffffff;
            font-size: 13px;
        }

        /* CHANGED: Fixed 3-column grid for non-table fields */
        .form-grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Always 3 columns */
            gap: 16px;
            margin: 12px 0;
        }

        /* Individual field item */
        .form-grid-item {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e3e6f0;
            transition: all 0.3s ease;
        }

        .form-grid-item:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        /* Form elements styling */
        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
            background-color: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1abc9c;
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 188, 156, 0.1);
        }

        .form-group input:required,
        .form-group select:required,
        .form-group textarea:required {
            border-left: 3px solid #e74c3c;
        }

        /* Radio button and checkbox improvements */
        .radio-group-container,
        .checkbox-group-container {
            margin-top: 6px;
        }

        .radio-group-container .radio-option,
        .checkbox-group-container .checkbox-option {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            padding: 6px 8px;
            background: #f8f9fa;
            border-radius: 4px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .radio-group-container .radio-option:hover,
        .checkbox-group-container .checkbox-option:hover {
            background: #edf2f7;
            border-color: #d1d3e2;
        }

        .radio-group-container input[type="radio"],
        .checkbox-group-container input[type="checkbox"] {
            margin-right: 8px;
            width: 14px;
            height: 14px;
            cursor: pointer;
        }

        .radio-group-container label,
        .checkbox-group-container label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: #2c3e50;
            flex: 1;
            font-size: 13px;
        }

        /* Horizontal radio group */
        .horizontal-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 6px;
        }

        .horizontal-radio-group .radio-option {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .horizontal-radio-group input[type="radio"] {
            margin-right: 6px;
        }

        /* Submit button */
        .submit-button-container {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
        }

        .submit-button {
            background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(41, 75, 106, 0.15);
            letter-spacing: 0.3px;
        }

        .submit-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(41, 75, 106, 0.2);
            background: linear-gradient(135deg, #2c3e50 0%, #294b6a 100%);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        /* File upload preview */
        .image-preview,
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
            border: 1px solid #d1d3e2;
            padding: 3px;
            background: white;
        }

        /* View/Download link */
        .view-download-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 15px;
            background-color: #e8f4f8;
            color: #294b6a;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            border: 1px solid #c5e1e6;
            transition: all 0.3s;
            font-size: 13px;
        }

        .view-download-link:hover {
            background-color: #d4ecf1;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            .form-page-container {
                max-width: 100%;
                margin: 10px;
            }
            
            /* CHANGED: 2 columns on medium screens */
            .form-grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .form-main-container {
                flex-direction: column;
            }
            
            .form-sidebar {
                width: 100%;
                height: auto;
                max-height: 250px;
            }
            
            .form-content-area {
                margin-left: 0;
                padding: 15px;
            }
            
            .custom-header-container {
                padding: 10px 15px;
            }
            
            .formpagetitle h2 {
                font-size: 20px;
            }
        }

        @media (max-width: 768px) {
            /* CHANGED: 1 column on mobile */
            .form-grid-container {
                grid-template-columns: 1fr;
            }
            
            .horizontal-radio-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .form-table {
                display: block;
                overflow-x: auto;
            }
            
            .section-title {
                font-size: 16px;
                padding: 8px 15px;
                margin: -15px -15px 15px -15px;
            }
            
            .form-section-container {
                padding: 15px;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 576px) {
            .form-content-area {
                padding: 12px;
            }
            
            .form-description {
                padding: 12px;
                font-size: 13px;
                margin-bottom: 15px;
            }
            
            .form-section-container {
                padding: 12px;
            }
            
            .form-grid-item {
                padding: 12px;
            }
            
            .submit-button {
                width: 100%;
                max-width: 250px;
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .custom-header-container {
                padding: 8px 12px;
            }
            
            .formpagetitle h2 {
                font-size: 18px;
            }
            
            .home_leftimage img,
            .home_rightimage img {
                max-height: 35px;
            }
        }

        /* Scrollbar styling */
        .form-sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .form-sidebar::-webkit-scrollbar-track {
            background: #2c3e50;
        }

        .form-sidebar::-webkit-scrollbar-thumb {
            background: #1abc9c;
            border-radius: 2px;
        }

        .form-content-area::-webkit-scrollbar {
            width: 6px;
        }

        .form-content-area::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .form-content-area::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="form-page-container">
        <!-- Custom Header -->
        <div class="custom-header-container">
            <div class="home_leftimage">
                <?php echo $renderer->left_form_logo(); ?>
            </div>

            <div class="formpagetitle">
                <h2><?php echo htmlspecialchars($form_data->name); ?></h2>
            </div>

            <div class="home_rightimage">
                <?php echo $renderer->right_form_logo(); ?>
            </div>
        </div>

        <!-- Main Container -->
        <div class="form-main-container">
            <!-- Sidebar -->
            <!-- Content Area -->
            <div class="form-content-area">
                <?php if (!empty($form_data->description)): ?>
                    <div class="form-description">
                        <?php echo htmlspecialchars($form_data->description); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="formsubmit.php?token=<?php echo urlencode($token); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="formid" value="<?php echo $formid; ?>">

                    <?php foreach ($sections as $section): ?>
                        <div class="form-section-container">
                            <div class="section-title"><?php echo htmlspecialchars($section->section_title); ?></div>

                            <?php if (isset($fieldsBySection[$section->id])): ?>
                                <?php
                                // Determine number of rows and columns
                                $rows = array_keys($fieldsBySection[$section->id]);
                                $maxRow = max($rows);
                                $cols = [];
                                $headers = [];
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

                                <table class="form-table">
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
                                                            $fieldName = "table_{$j}_{$i}";
                                                            
                                                            echo '<div class="form-group">';
                                                            echo '<label>' . htmlspecialchars($field->field_title) . '</label>';
                                                            
                                                            switch ($field->field_type) {
                                                                case 'Text':
                                                                    echo '<input type="text" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                    break;

                                                                case 'Label':
                                                                    echo '<div style="padding: 10px 0; font-weight: 500;">' . htmlspecialchars($field->field_title) . '</div>';
                                                                    break;

                                                                case 'Date':
                                                                    echo '<input type="date" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                    break;

                                                                case 'Email':
                                                                    echo '<input type="email" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                    break;

                                                                case 'Textarea':
                                                                    echo '<textarea readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">' . htmlspecialchars($field->field_title) . '</textarea>';
                                                                    break;

                                                                case 'Select Box':
                                                                    $options = explode(',', $field->field_options);
                                                                    echo '<select name="' . htmlspecialchars($fieldName) . '">';
                                                                    echo '<option value="">Select...</option>';
                                                                    foreach ($options as $option) {
                                                                        echo '<option value="' . htmlspecialchars(trim($option)) . '">' . htmlspecialchars(trim($option)) . '</option>';
                                                                    }
                                                                    echo '</select>';
                                                                    break;

                                                                case 'Radio Button':
                                                                    $options = explode(',', $field->field_options);
                                                                    echo '<div class="radio-group-container">';
                                                                    foreach ($options as $option) {
                                                                        echo '<div class="radio-option">';
                                                                        echo '<input type="radio" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars(trim($option)) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                                        echo '<label>' . htmlspecialchars(trim($option)) . '</label>';
                                                                        echo '</div>';
                                                                    }
                                                                    echo '</div>';
                                                                    break;

                                                                case 'Checkbox':
                                                                    $options = explode(',', $field->field_checkbox_options);
                                                                    echo '<div class="checkbox-group-container">';
                                                                    foreach ($options as $option) {
                                                                        echo '<div class="checkbox-option">';
                                                                        echo '<input type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars(trim($option)) . '">';
                                                                        echo '<label>' . htmlspecialchars(trim($option)) . '</label>';
                                                                        echo '</div>';
                                                                    }
                                                                    echo '</div>';
                                                                    break;

                                                                case 'File Upload':
                                                                    echo '<input type="file" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' onchange="previewImage(event, this)"/>';
                                                                    echo '<div class="image-preview" id="image-preview-' . htmlspecialchars($fieldName) . '"></div>';
                                                                    break;

                                                                case 'View/Download':
                                                                    $label = htmlspecialchars($field->field_title);
                                                                    $url = htmlspecialchars($field->field_url);
                                                                    echo '<a href="' . $url . '" target="_blank" class="view-download-link">';
                                                                    echo '<i class="fas fa-download" style="margin-right: 5px;"></i>';
                                                                    echo $label;
                                                                    echo '</a>';
                                                                    break;

                                                                default:
                                                                    echo '<p style="color: #666; font-style: italic;">Unknown field type</p>';
                                                                    break;
                                                            }
                                                            echo '</div>';
                                                        } else {
                                                            echo '&nbsp;';
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
                                    $firstFieldName = "table_0_0";
                                ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($firstFieldName); ?>" value="<?php echo htmlspecialchars($firstField->field_value ?? ''); ?>">
                                <?php } ?>

                            <?php else: ?>
                                <?php
                                $submittedMap = [];
                                foreach ($submittedValues as $submitted) {
                                    $submittedMap[$submitted->fieldname] = $submitted->fieldvalue;
                                }
                                
                                // Handle non-table format fields with fixed 3-column grid
                                echo '<div class="form-grid-container">';
                                foreach ($onlyfields as $field) {
                                    $value = isset($submittedMap[$field->formname]) ? htmlspecialchars($submittedMap[$field->formname]) : '';
                                    if ($field->section_id == $section->id) {
                                        echo '<div class="form-grid-item">';
                                        $fieldName = "field_{$field->formname}";
                                        
                                        echo '<div class="form-group">';
                                        echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                        
                                        switch ($field->formtype) {
                                            case 'text':
                                                echo '<input type="text" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                                break;

                                            case 'textarea':
                                                echo '<textarea name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' rows="4" maxlength="1000">' . $value . '</textarea>';
                                                break;

                                            case 'email':
                                                echo '<input type="email" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                                break;

                                            case 'dropdown':
                                                $options = explode(',', $field->fieldoption);
                                                echo '<select name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . '>';
                                                echo '<option value="" disabled ' . ($value === '' ? 'selected' : '') . '>Select...</option>';
                                                foreach ($options as $option) {
                                                    $selected = ($value === trim($option)) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars(trim($option)) . '" ' . $selected . '>' . htmlspecialchars(trim($option)) . '</option>';
                                                }
                                                echo '</select>';
                                                break;

                                            case 'radio':
                                                $options = explode(',', $field->fieldoption);
                                                echo '<div class="radio-group-container">';
                                                foreach ($options as $option) {
                                                    $option = trim($option);
                                                    $isChecked = ($value === $option) ? 'checked' : '';
                                                    echo '<div class="radio-option">';
                                                    echo '<input type="radio" name="' . htmlspecialchars($field->formname) . '" value="' . htmlspecialchars($option) . '" ' . $isChecked . ' ' . ($field->required ? 'required' : '') . ' />';
                                                    echo '<label>' . htmlspecialchars($option) . '</label>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                                break;

                                            case 'checkbox':
                                                $options = explode(',', $field->fieldoption);
                                                $selectedValues = is_array($value) ? $value : explode(',', $value);
                                                echo '<div class="checkbox-group-container">';
                                                foreach ($options as $option) {
                                                    $option = trim($option);
                                                    $isChecked = in_array($option, $selectedValues) ? 'checked' : '';
                                                    echo '<div class="checkbox-option">';
                                                    echo '<input type="checkbox" name="' . htmlspecialchars($fieldName) . '[]" value="' . htmlspecialchars($option) . '" ' . $isChecked . ' ' . ($field->required ? 'required' : '') . ' />';
                                                    echo '<label>' . htmlspecialchars($option) . '</label>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                                break;

                                            case 'date':
                                                echo '<input type="date" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                                break;
                                                
                                            case 'time':
                                                echo '<input type="time" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                                break;
                                                
                                            case 'number':
                                                echo '<input type="number" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                                break;

                                            case 'file':
                                                echo '<input type="file" name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';
                                                
                                                if (!empty($value)) {
                                                    $file_url = new moodle_url('/local/form/pix/' . $value);
                                                    $file_extension = pathinfo($value, PATHINFO_EXTENSION);
                                                    
                                                    echo '<div class="file-preview" id="file-preview-' . htmlspecialchars($fieldName) . '">';
                                                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                                                        echo '<img src="' . htmlspecialchars($file_url) . '" alt="Uploaded Image" style="max-width: 120px; max-height: 120px; margin-top: 10px; border-radius: 6px; border: 1px solid #ddd;" />';
                                                    } elseif (in_array(strtolower($file_extension), ['pdf'])) {
                                                        echo '<a href="' . htmlspecialchars($file_url) . '" target="_blank" class="view-download-link" style="margin-top: 10px;">';
                                                        echo '<i class="fas fa-file-pdf"></i> View PDF';
                                                        echo '</a>';
                                                    } else {
                                                        echo '<span style="display: block; margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px;">' . htmlspecialchars($value) . '</span>';
                                                    }
                                                    echo '</div>';
                                                }
                                                break;

                                            default:
                                                echo '<p style="color: #666; font-style: italic;">Unknown field type</p>';
                                                break;
                                        }
                                        
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                echo '</div>';
                                ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Submit Button -->
                    <div class="submit-button-container">
                        <button type="submit" class="submit-button">Submit Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo $CFG->wwwroot; ?>/local/form/amd/src/main.js"></script>
    
    <script>
        function previewImage(event, input) {
            const fileList = input.files;
            const previewContainer = document.getElementById(`image-preview-${input.id}`);

            // Clear previous previews
            previewContainer.innerHTML = '';

            if (fileList.length > 0) {
                Array.from(fileList).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.style.maxWidth = '120px';
                        img.style.maxHeight = '120px';
                        img.style.margin = '5px';
                        img.style.borderRadius = '6px';
                        img.style.border = '1px solid #ddd';
                        previewContainer.appendChild(img);
                    }
                });
            }
        }
        
        // Add Font Awesome icons if needed
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            document.head.appendChild(link);
        }
    </script>
</body>
</html>

<?php
echo $OUTPUT->footer();
?>
<?php
require_once('../../config.php');
if (!isloggedin()) {
    redirect(new moodle_url('/login/index.php'));
}

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

// Fetch description from the local_form table based on formid
$descriptionSql = 'SELECT description,name FROM {local_form} WHERE id = ? AND visible = 1';
$descriptionRecord = $DB->get_record_sql($descriptionSql, [$formid]);
$description = $descriptionRecord ? $descriptionRecord->description : '';


// Fetch sections for the given form
$sectionsSql = 'SELECT * FROM {form_sections} WHERE formid = ? ORDER BY id';
$sections = $DB->get_records_sql($sectionsSql, [$formid]);

$countrysql = 'SELECT pk,country_name FROM `country_master`';
$countries = $DB->get_records_sql($countrysql);

$statesql = 'SELECT Pk,state_name FROM `state_master`';
$states = $DB->get_records_sql($statesql);

$districtsql = 'SELECT pk,district_name FROM `state_district_mapping`';
$districts = $DB->get_records_sql($districtsql);

$languagesql = 'SELECT pk,language_name FROM `language_master` WHERE active_status = 1';
$languages = $DB->get_records_sql($languagesql);

$admissioncategorysql = 'SELECT pk,seat_name FROM `admission_category`';
$categories = $DB->get_records_sql($admissioncategorysql);

$highest_streamsql = 'SELECT pk,highest_stream FROM `highest_stream`';
$highest_streams = $DB->get_records_sql($highest_streamsql);

$institution_typesql = 'SELECT pk,institution_type FROM `institution_type`';
$institutions = $DB->get_records_sql($institution_typesql);

$job_typesql = 'SELECT pk,job_name FROM `job_type`';
$jobstypes = $DB->get_records_sql($job_typesql);

$board_namesql = 'SELECT pk,board_name FROM `online_board_name`';
$board_names = $DB->get_records_sql($board_namesql);

$qualificationsql = 'SELECT pk,qualification FROM `online_qualification`';
$qualifications = $DB->get_records_sql($qualificationsql);

$religionsql = 'SELECT pk,religion_name FROM `religion_master`';
$religions = $DB->get_records_sql($religionsql);

$servicesql = 'SELECT pk,service_name FROM `service_master`';
$services = $DB->get_records_sql($servicesql);

$sportssql = 'SELECT pk,sports_name FROM `sports_master`';
$sports = $DB->get_records_sql($sportssql);

$cloth_sizessql = 'SELECT pk,cloth_size FROM `student_clothsize`';
$clothes = $DB->get_records_sql($cloth_sizessql);

$scale_detailsql = 'SELECT pk,scale_detail FROM `student_fc_scale`';
$fcscales = $DB->get_records_sql($scale_detailsql);

$distinctionsql = 'SELECT pk,academic_distinction FROM `student_master_academic_distinction`';
$distinctions = $DB->get_records_sql($distinctionsql);

$fatherprofessionsql = 'SELECT pk,father_profession FROM `student_master_father_profession`';
$fatherprofession = $DB->get_records_sql($fatherprofessionsql);

$pant_sizesql = 'SELECT pk,pant_size FROM `student_pantsize`';
$pantsizes = $DB->get_records_sql($pant_sizesql);

$student_shoessizesql = 'SELECT pk,ssize FROM `student_shoessize`';
$shoessizes = $DB->get_records_sql($student_shoessizesql);

$skill_namesql = 'SELECT pk,skill_name FROM `student_skill_details`';
$studentskills = $DB->get_records_sql($skill_namesql);




// Fetch fields for all sections, ordered by section_id, row_index, col_index
$fieldsSql = 'SELECT * FROM {form_data} WHERE formid = ? ORDER BY section_id ASC , id ASC';
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

        .header-container ,
        .header-container {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
        }

        .home_leftimage img {
            width: 350px;
            /* Makes image scale to the container's width */
            height: 100px;
            /* Makes image scale to the container's height */
        }


        .home_rightimage img {
            width: 270px;
            /* Makes image scale to the container's width */
            height: 170px;
            /* Makes image scale to the container's height */
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

        @media (min-width: 1200px) {

            .container,
            .container-sm,
            .container-md,
            .container-lg,
            .container-xl {
                max-width: none;
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
                if ($button):
                    // Static button URL
                    $static_url = new moodle_url('/local/form/downloadpdf.php', array('formid' => $button->id, 'uid' => $USER->id));
                ?>
                    <!-- Dynamic Button -->
                    <a href="addform.php?formid=<?php echo htmlspecialchars($button->id); ?>">
                        <?php echo htmlspecialchars($button->name); ?>
                    </a>

                    <!-- Static Button -->
                    <a href="<?php echo $static_url; ?>" class="btn bt" style="text-align: left;">
                        Download PDF
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
                            <?php $validFieldHeadings = [
                                // Define valid substrings for matching headers
                                'country',
                                'state',
                                'district',
                                'language',
                                'admissioncategory',
                                'stream',
                                'institution',
                                'jobtype',
                                'boardname',
                                'qualification',
                                'religion',
                                'service',
                                'sports',
                                'size',
                                'fcscale',
                                'distinction',
                                'fatherprofession',
                                'trouser',
                                'shoessize',
                                'studentskill'
                            ];

                            // Inside your table rendering loop
                            ?>

                            <table class="dynamic-table">
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
                                        <tr id="row-<?php echo $i; ?>">
                                            <?php for ($j = 0; $j <= $maxCol; $j++): ?>
                                                <td>
                                                    <?php
                                                    if (isset($fieldsBySection[$section->id][$i][$j])) {
                                                        $headerValue = $headers[$j] ?? ''; // Get the corresponding header value
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

                                                                // Check if the header value contains any valid substring
                                                                foreach ($validFieldHeadings as $validHeading) {
                                                                    if (stripos($headerValue, $validHeading) !== false) {
                                                                        $isMappedField = true;
                                                                        $mappedHeading = $validHeading; // Save the matched heading
                                                                        break;
                                                                    }
                                                                }

                                                                echo '<div class="form-group">';
                                                                // echo '<label>' . htmlspecialchars($headerValue) . '</label>';
                                                                echo '<select name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($headerValue) . '_select" ' . ($field->required ? 'required' : '') . '>';
                                                                echo '<option value="">Select ' . htmlspecialchars($headerValue) . '</option>';

                                                                if ($isMappedField) {
                                                                    // Populate options based on the matched header
                                                                    switch ($mappedHeading) {
                                                                        case 'country':
                                                                            foreach ($countries as $country) {
                                                                                echo '<option value="' . htmlspecialchars($country->pk) . '">' . htmlspecialchars($country->country_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'state':
                                                                            foreach ($states as $state) {
                                                                                echo '<option value="' . htmlspecialchars($state->pk) . '">' . htmlspecialchars($state->state_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'district':
                                                                            foreach ($districts as $district) {
                                                                                echo '<option value="' . htmlspecialchars($district->pk) . '">' . htmlspecialchars($district->district_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'language':
                                                                            foreach ($languages as $language) {
                                                                                echo '<option value="' . htmlspecialchars($language->pk) . '">' . htmlspecialchars($language->language_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'admissioncategory':
                                                                            foreach ($categories as $category) {
                                                                                echo '<option value="' . htmlspecialchars($category->pk) . '">' . htmlspecialchars($category->seat_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'stream':
                                                                            foreach ($highest_streams as $stream) {
                                                                                echo '<option value="' . htmlspecialchars($stream->pk) . '">' . htmlspecialchars($stream->highest_stream) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'jobtype':
                                                                            foreach ($jobstypes as $job) {
                                                                                echo '<option value="' . htmlspecialchars($job->pk) . '">' . htmlspecialchars($job->job_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'boardname':
                                                                            foreach ($board_names as $board) {
                                                                                echo '<option value="' . htmlspecialchars($board->pk) . '">' . htmlspecialchars($board->board_name) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'qualification':
                                                                            foreach ($qualifications as $qualification) {
                                                                                echo '<option value="' . htmlspecialchars($qualification->pk) . '">' . htmlspecialchars($qualification->qualification) . '</option>';
                                                                            }
                                                                            break;

                                                                        case 'religion':
                                                                            foreach ($religions as $religion) {
                                                                                echo '<option value="' . htmlspecialchars($religion->pk) . '">' . htmlspecialchars($religion->religion_name) . '</option>';
                                                                            }
                                                                            break;


                                                                            // Add similar cases for other mapped headings
                                                                    }
                                                                } else {
                                                                    // Handle dropdowns with options not mapped to a database
                                                                    $options = explode(',', $field->fieldoption);
                                                                    foreach ($options as $option) {
                                                                        $selected = ($value === trim($option)) ? 'selected' : '';
                                                                        echo '<option value="' . htmlspecialchars(trim($option)) . '" ' . $selected . '>' . htmlspecialchars(trim($option)) . '</option>';
                                                                    }
                                                                }

                                                                echo '</select>';
                                                                echo '</div>';

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
                                <tfoot>
                                    <tr>
                                        <td colspan="<?php echo $maxCol + 1; ?>" style="text-align: left;">
                                            <!-- Define the image paths using Moodle URL -->
                                            <?php
                                            $increase_logo_path = new moodle_url('/local/form/pix/increase.png'); // Path for Increase image
                                            $decrease_logo_path = new moodle_url('/local/form/pix/decrease.png'); // Path for Decrease image
                                            ?>

                                            <!-- Button with Increase image -->
                                            <button class="replicate-row btn btn-success" onclick="replicateRow(event)" style="cursor: pointer; border: none; background: none; padding: 0;">
                                                <img src="<?php echo $increase_logo_path; ?>" alt="Increase" style="width: 15px; height: 15px;">
                                            </button>

                                            <!-- Button with Decrease image -->
                                            <button class="remove-row btn btn-danger" onclick="removeRow(event)" style="cursor: pointer; border: none; background: none; padding: 0;">
                                                <img src="<?php echo $decrease_logo_path; ?>" alt="Decrease" style="width: 15px; height: 15px;">
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
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
                                            echo '<textarea name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' maxlength="1000">' . htmlspecialchars($value) . '</textarea>';
                                            echo '</div>';
                                            break;


                                        case 'email':
                                            echo '<div class="form-group">';
                                            echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="email" name="' . htmlspecialchars($fieldName) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'dropdown':
                                            // Handle country, state, district dropdowns and other general dropdowns
                                            // if (
                                            //     $field->formname === 'country' || $field->formname === 'state' || $field->formname === 'district'
                                            //     || $field->formname === 'language' || $field->formname === 'admissioncategory' || $field->formname === 'stream'
                                            //     || $field->formname === 'institution' || $field->formname === 'jobtype' || $field->formname === 'boardname' || $field->formname === 'qualification'
                                            //     || $field->formname === 'religion' || $field->formname === 'service' || $field->formname === 'sports' || $field->formname === 'clothsize' || $field->formname === 'fcscale'
                                            //     || $field->formname === 'distinction' || $field->formname === 'fatherprofession' || $field->formname === 'pantsize'
                                            //     || $field->formname === 'shoessize' || $field->formname === 'studentskill'
                                            //     ) {

                                            // $isValid = true;
                                            $validFieldNames = [
                                                'country',
                                                'state',
                                                'district',
                                                'language',
                                                'admissioncategory',
                                                'stream',
                                                'institution',
                                                'jobtype',
                                                'boardname',
                                                'qualification',
                                                'religion',
                                                'service',
                                                'sports',
                                                'size',
                                                'fcscale',
                                                'distinction',
                                                'fatherprofession',
                                                'trouser',
                                                'shoessize',
                                                'studentskill'
                                            ];

                                            // Use strpos to check if $fieldname contains any valid substring
                                            $isValidField = false;
                                            foreach ($validFieldNames as $validName) {
                                                if (stripos($field->formname, $validName) !== false) {
                                                    $isValidField = true;
                                                    break;
                                                }
                                            }

                                            if ($isValidField) {
                                                // if ($isValid) {
                                                echo '<div class="form-group">';
                                                echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                                echo '<select name="' . htmlspecialchars($field->formname) . '" id="' . htmlspecialchars($field->formname) . '_select" ' . ($field->required ? 'required' : '') . '>';
                                                echo '<option value="">Select ' . htmlspecialchars($field->formlabel) . '</option>';

                                                // Populate options based on the field type (country, state, district)
                                                // if ($field->formname === 'country') {
                                                if (strpos($field->formname, 'country') !== false) {
                                                    foreach ($countries as $country) {
                                                        echo '<option value="' . htmlspecialchars($country->pk) . '" ' . (($value === $country->pk) ? 'selected' : '') . '>'
                                                            . htmlspecialchars($country->country_name) . '</option>';

                                                        // echo '<option value="' . htmlspecialchars($country->id) . '" ' . ($value === $country->id ? 'selected' : '') . '>' . htmlspecialchars($country->name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'state') {
                                                } elseif (strpos($field->formname, 'state') !== false) {
                                                    foreach ($states as $state) {
                                                        echo '<option value="' . htmlspecialchars($state->pk) . '" ' . ($value === $state->pk ? 'selected' : '') . '>' . htmlspecialchars($state->state_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'district') {
                                                } elseif (stripos($field->formname, 'district') !== false) {

                                                    foreach ($districts as $district) {
                                                        echo '<option value="' . htmlspecialchars($district->pk) . '" ' . ($value === $district->pk ? 'selected' : '') . '>' . htmlspecialchars($district->district_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'language') {
                                                } elseif (strpos($field->formname, 'language') !== false) {

                                                    foreach ($languages as $language) {
                                                        echo '<option value="' . htmlspecialchars($language->pk) . '" ' . ($value === $language->pk ? 'selected' : '') . '>' . htmlspecialchars($language->language_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'admissioncategory') {
                                                } elseif (strpos($field->formname, 'admissioncategory') !== false) {
                                                    foreach ($categories as $category) {
                                                        echo '<option value="' . htmlspecialchars($category->pk) . '" ' . ($value === $category->pk ? 'selected' : '') . '>' . htmlspecialchars($category->seat_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'stream') {
                                                } elseif (strpos($field->formname, 'stream') !== false) {
                                                    foreach ($highest_streams as $stream) {
                                                        echo '<option value="' . htmlspecialchars($stream->pk) . '" ' . ($value === $stream->pk ? 'selected' : '') . '>' . htmlspecialchars($stream->highest_stream) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'institution') {
                                                } elseif (strpos($field->formname, 'institution') !== false) {
                                                    foreach ($institutions as $institution) {
                                                        echo '<option value="' . htmlspecialchars($institution->pk) . '" ' . ($value === $institution->pk ? 'selected' : '') . '>' . htmlspecialchars($institution->institution_type) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'jobtype') {
                                                } elseif (strpos($field->formname, 'jobtype') !== false) {
                                                    foreach ($jobstypes as $job) {
                                                        echo '<option value="' . htmlspecialchars($job->pk) . '" ' . ($value === $job->pk ? 'selected' : '') . '>' . htmlspecialchars($job->job_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'boardname') {
                                                } elseif (strpos($field->formname, 'boardname') !== false) {
                                                    foreach ($board_names as $board) {
                                                        echo '<option value="' . htmlspecialchars($board->pk) . '" ' . ($value === $board->pk ? 'selected' : '') . '>' . htmlspecialchars($board->board_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'qualification') {
                                                } elseif (strpos($field->formname, 'qualification') !== false) {
                                                    foreach ($qualifications as $qualification) {
                                                        echo '<option value="' . htmlspecialchars($qualification->pk) . '" ' . ($value === $qualification->pk ? 'selected' : '') . '>' . htmlspecialchars($qualification->qualification) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'religion') {
                                                } elseif (strpos($field->formname, 'religion') !== false) {
                                                    foreach ($religions as $religion) {
                                                        echo '<option value="' . htmlspecialchars($religion->pk) . '" ' . ($value === $religion->pk ? 'selected' : '') . '>' . htmlspecialchars($religion->religion_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'service') {
                                                } elseif (strpos($field->formname, 'service') !== false) {
                                                    foreach ($services as $service) {
                                                        echo '<option value="' . htmlspecialchars($service->pk) . '" ' . ($value === $service->pk ? 'selected' : '') . '>' . htmlspecialchars($service->service_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'sports') {
                                                } elseif (strpos($field->formname, 'sports') !== false) {
                                                    foreach ($sports as $sport) {
                                                        echo '<option value="' . htmlspecialchars($sport->pk) . '" ' . ($value === $sport->pk ? 'selected' : '') . '>' . htmlspecialchars($sport->sports_name) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'clothsize') {
                                                } elseif (stripos(trim($field->formname), 'size') !== false) {
                                                    foreach ($clothes as $cloth) {
                                                        echo '<option value="' . htmlspecialchars($cloth->pk) . '" ' . ($value === $cloth->pk ? 'selected' : '') . '>' . htmlspecialchars($cloth->cloth_size) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'fcscale') {
                                                } elseif (strpos($field->formname, 'fcscale') !== false) {
                                                    foreach ($fcscales as $fcscale) {
                                                        echo '<option value="' . htmlspecialchars($fcscale->pk) . '" ' . ($value === $fcscale->pk ? 'selected' : '') . '>' . htmlspecialchars($fcscale->scale_detail) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'distinction') {
                                                } elseif (strpos($field->formname, 'distinction') !== false) {
                                                    foreach ($distinctions as $distinction) {
                                                        echo '<option value="' . htmlspecialchars($distinction->pk) . '" ' . ($value === $distinction->pk ? 'selected' : '') . '>' . htmlspecialchars($distinction->academic_distinction) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'fatherprofession') {
                                                } elseif (strpos($field->formname, 'fatherprofession') !== false) {
                                                    foreach ($fatherprofession as $profession) {
                                                        echo '<option value="' . htmlspecialchars($profession->pk) . '" ' . ($value === $profession->pk ? 'selected' : '') . '>' . htmlspecialchars($profession->father_profession) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'pantsize') {
                                                } elseif (strpos($field->formname, 'trouser') !== false) {
                                                    foreach ($pantsizes as $pantsize) {
                                                        echo '<option value="' . htmlspecialchars($pantsize->pk) . '" ' . ($value === $pantsize->pk ? 'selected' : '') . '>' . htmlspecialchars($pantsize->pant_size) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'shoessize') {
                                                } elseif (strpos($field->formname, 'shoessize') !== false) {
                                                    foreach ($shoessizes as $shoessize) {
                                                        echo '<option value="' . htmlspecialchars($shoessize->pk) . '" ' . ($value === $shoessize->pk ? 'selected' : '') . '>' . htmlspecialchars($shoessize->ssize) . '</option>';
                                                    }
                                                    // } elseif ($field->formname === 'studentskill') {
                                                } elseif (strpos($field->formname, 'studentskill') !== false) {
                                                    foreach ($studentskills as $studentskill) {
                                                        echo '<option value="' . htmlspecialchars($studentskill->pk) . '" ' . ($value === $studentskill->pk ? 'selected' : '') . '>' . htmlspecialchars($studentskill->skill_name) . '</option>';
                                                    }
                                                }
                                                echo '</select>';
                                                echo '</div>';
                                            } else {
                                                // Handle other dropdowns
                                                echo '<div class="form-group">';
                                                echo '<label>' . htmlspecialchars($field->formlabel) . '</label>';
                                                echo '<select name="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . '>';
                                                $options = explode(',', $field->fieldoption);
                                                echo '<option value="" disabled ' . ($value === '' ? 'selected' : '') . '>Please select an option</option>';
                                                foreach ($options as $option) {
                                                    $selected = ($value === trim($option)) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars(trim($option)) . '" ' . $selected . '>' . htmlspecialchars(trim($option)) . '</option>';
                                                }
                                                echo '</select>';
                                                echo '</div>';
                                            }
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

                                        case 'number': // New case for number field
                                            echo '<div class="form-group">';
                                            echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="number" class="form-control" name="' . htmlspecialchars($field->formname) . '" id="' . htmlspecialchars($field->formname) . '" '
                                                . 'value="' . htmlspecialchars($value) . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'time': // New case for time field
                                            echo '<div class="form-group">';
                                            echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="time" class="form-control" name="' . htmlspecialchars($field->formname) . '" id="' . htmlspecialchars($field->formname) . '" '
                                                . 'value="' . htmlspecialchars($value) . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;


                                        case 'date':
                                            echo '<div class="form-group">';
                                            echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="date" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($field->formname) . '" value="' . $value . '" ' . ($field->required ? 'required' : '') . ' />';
                                            echo '</div>';
                                            break;

                                        case 'file':
                                            // if ($field->required && empty($_FILES[$fieldName]['name']) && empty($value)) {
                                            if ($field->required && empty($_FILES[$fieldName]['name']) && empty($value)) {

                                                $errors[] = 'File upload is required for ' . htmlspecialchars($field->formlabel);
                                            }
                                            echo '<div class="form-group">';
                                            echo '<label for="' . htmlspecialchars($field->formname) . '">' . htmlspecialchars($field->formlabel) . '</label>';
                                            echo '<input type="file" name="' . htmlspecialchars($fieldName) . '" id="' . htmlspecialchars($fieldName) . '" ' . ($field->required ? 'required' : '') . ' />';

                                            // Check if a file was submitted and display it
                                            if (!empty($value)) {
                                                $file_url = new moodle_url('/local/form/pix/' . $value);
                                                $file_extension = pathinfo($value, PATHINFO_EXTENSION); // Get the file extension

                                                echo '<div class="file-preview" id="file-preview-' . htmlspecialchars($fieldName) . '">';

                                                if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                                                    // Display the image if the file is an image
                                                    echo '<img src="' . htmlspecialchars($file_url) . '" alt="Uploaded Image" style="max-width: 100%; max-height: 100px; margin-top: 10px;" />';
                                                } elseif (in_array(strtolower($file_extension), ['pdf'])) {
                                                    // Display a button if the file is a PDF
                                                    echo '<a href="' . htmlspecialchars($file_url) . '" target="_blank" class="btn btn-primary">View PDF</a>';
                                                } else {
                                                    // For other file types, display the filename as a fallback
                                                    echo '<span>' . htmlspecialchars($value) . '</span>';
                                                }

                                                echo '</div>';
                                            } else {
                                                echo '<div class="file-preview" id="file-preview-' . htmlspecialchars($fieldName) . '"></div>';
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

<script>
    // Function to replicate a row
    // function replicateRow(event) {
    //     event.preventDefault();
    //     var table = event.target.closest('table').getElementsByTagName('tbody')[0]; // Get the closest table

    //     // If no rows exist, create a new row instead of cloning a non-existent one
    //     if (table.rows.length === 0) {
    //         addNewRow(table, 0); // If no rows exist, add the first row
    //     } else {
    //         var lastRow = table.rows[table.rows.length - 1];
    //         var newRow = lastRow.cloneNode(true);

    //         // Update the ID and name attributes to avoid duplicates
    //         var newRowIndex = table.rows.length;
    //         newRow.id = 'row-' + newRowIndex;

    //         var inputs = newRow.querySelectorAll('input, select, textarea');
    //         inputs.forEach(function(input) {
    //             input.name = input.name.replace(/\d+$/, newRowIndex); // Replace the row index
    //             input.id = input.id.replace(/\d+$/, newRowIndex); // Replace the row index
    //         });

    //         table.appendChild(newRow);
    //     }
    // }

    function replicateRow(event) {
        event.preventDefault(); // Prevent the default button behavior
        var table = event.target.closest('table').getElementsByTagName('tbody')[0]; // Get the closest table body

        // If no rows exist, create a new row instead of cloning a non-existent one
        if (table.rows.length === 0) {
            addNewRow(table, 0); // If no rows exist, add the first row
        } else {
            var lastRow = table.rows[table.rows.length - 1]; // Get the last row
            var newRow = lastRow.cloneNode(true); // Clone the last row

            // Check if any duplicate values exist in the dropdowns of the last row
            const isDuplicate = checkDropdownDuplicates(newRow);

            if (isDuplicate) {
                // If a duplicate is found, reset the current row instead of cloning it
                resetRowInputs(lastRow); // Reset the inputs in the last row
            } else {
                // If no duplicates, clone the row and update IDs and names
                var newRowIndex = table.rows.length;
                newRow.id = 'row-' + newRowIndex;

                // Update the name and id attributes to avoid duplicates
                var inputs = newRow.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    input.name = input.name.replace(/\d+$/, newRowIndex); // Replace the row index in name
                    input.id = input.id.replace(/\d+$/, newRowIndex); // Replace the row index in id
                });

                // Reset inputs in the cloned row
                resetRowInputs(newRow);

                // Append the new row to the table
                table.appendChild(newRow);
            }
        }
    }

    // Function to reset all inputs in the row
    function resetRowInputs(row) {
        const inputs = row.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = ''; // Clear the value for text inputs, selects, and textareas
            }
        });
    }

    // Function to check for duplicate dropdown values across all rows
    function checkDropdownDuplicates(row) {
        const dropdowns = document.querySelectorAll('.dynamic-table tbody tr td:nth-child(1) select'); // Select only the dropdowns in the first column
        const selectedValues = []; // Array to store selected values
        let isDuplicate = false; // Flag to track if a duplicate is found

        // Loop through all dropdowns in the first column to check for duplicates
        dropdowns.forEach(dropdown => {
            const selectedValue = dropdown.value;
            const selectedText = dropdown.options[dropdown.selectedIndex].text;

            // Check if the selected value already exists in the array
            if (selectedValue && selectedValues.includes(selectedValue)) {
                // If a duplicate is found, alert the user and set the flag
                alert(selectedValue + ' [' + selectedText + '] is already entered');
                isDuplicate = true;
            } else {
                selectedValues.push(selectedValue); // Add the selected value to the array
            }
        });

        return isDuplicate; // Return whether a duplicate was found
    }

    // Function to remove the last row
    function removeRow(event) {
        event.preventDefault();

        var table = event.target.closest('table').getElementsByTagName('tbody')[0]; // Get the closest table

        // Check if there is only 1 row left
        if (table.rows.length === 1) {
            // alert('You cannot remove the last row!');
        } else if (table.rows.length > 0) {
            table.deleteRow(table.rows.length - 1); // Remove the last row
        }
    }

    // Helper function to add a new row from scratch
    function addNewRow(table, rowIndex) {
        var newRow = table.insertRow(rowIndex);

        // Example cell structure (copy from the existing logic)
        for (var i = 0; i <= <?php echo $maxCol; ?>; i++) {
            var newCell = newRow.insertCell(i);
            newCell.innerHTML = '<input type="text" name="table_' + i + '_' + rowIndex + '" />'; // Example input
        }
    }
</script>

</html>

<?php
echo $OUTPUT->footer();
?>
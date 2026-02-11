<?php
require_once('../../config.php');
require_once('lib.php');
require_once('../../vendor/autoload.php');


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

use \Mpdf\Mpdf; // Import the mPDF class

// Set your custom font directory
$fontDir = __DIR__ . 'vendor/mpdf/mpdf/ttfonts';
// $fontDir = __DIR__ . '/vendor/mpdf/mpdf/ttfontdata'; 



// // Initialize mPDF with custom font settings
// $mpdf = new Mpdf([
//     'fontDir' => [$fontDir], // Add the custom fonts directory
//     'default_font' => 'dejavusans', // Default font (for fallback)
// ]);


// // Register the Devanagari font with mPDF
// $mpdf->fontdata['devanagari'] = [
//     'R' => $fontDir . '/NotoSansDevanagari-Regular.ttf', // Regular font file
//     'B' => $fontDir . '/NotoSansDevanagari-Bold.ttf',    // Bold font file
// ];

// // Set the font to Devanagari
// $mpdf->SetFont('devanagari');  // Use the Devanagari font


// use \Mpdf\Mpdf;

// require_once('../../vendor/autoload.php');
global $DB, $PAGE, $OUTPUT;

$id = optional_param('formid', 0, PARAM_INT); // Get ID from URL
$uid = optional_param('uid', 0, PARAM_INT); // Get ID from URL


if ($id <= 0) {
    print_error('invalidid', 'error');
}

$sql = "
    SELECT 
        s.section_title, 
        f.formname, 
        f.formlabel AS fieldname, 
        fs.fieldvalue
    FROM 
        mdl_form_sections s
    JOIN 
        mdl_form_data f ON s.id = f.section_id
    LEFT JOIN 
        {form_submissions} fs 
        ON f.formid = fs.formid 
        AND (f.formname = fs.fieldname OR f.format = fs.fieldname)
        AND fs.id = (
            SELECT MAX(fs2.id)
            FROM {form_submissions} fs2
            WHERE fs2.formid = f.formid 
            AND fs2.uid = :uid 
            AND (fs2.fieldname = f.formname OR fs2.fieldname = f.format)
        )
    WHERE f.formid = :formid ORDER BY s.id, fs.id ASC";

$params = [
    'formid' => $id,
    'uid' => $uid,
];

$recordset = $DB->get_recordset_sql($sql, $params);
function is_json($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

$query = "SELECT description FROM mdl_local_form WHERE id = :id";
$formdesc = $DB->get_field_sql($query, ['id' => $id]);

if (!$recordset) {
    print_error('recordnotfound', 'error');
}

// Utility functions (get_name_by_id and is_json remain unchanged)

// Prepare sections data
$sections = [];
$logo_path = '';

foreach ($recordset as $record) {
    $fieldname = trim($record->formname);
    $fieldvalue = trim($record->fieldvalue);
    // print_object($recordset);die;


    if ($record->formname === 'profile') {
        $logo_path = new moodle_url('/local/form/pix/' . $fieldvalue);
    } else {
        // List of valid substrings for matching
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
            if (strpos($fieldname, $validName) !== false) {
                $isValidField = true;
                break;
            }
        }

        if ($isValidField) {
            // If $fieldvalue is not empty, get the name by ID, otherwise assign 'Not provided'
            $fieldvalue = !empty($fieldvalue) ? get_name_by_id($fieldvalue, $fieldname) : '';
        }


        if (is_json($fieldvalue)) {
            $tableData = json_decode($fieldvalue, true);
            if (isset($tableData['header']) && isset($tableData['table_value'])) {
                $sections[$record->section_title] = [
                    'headers' => array_values($tableData['header']),
                    'table_value' => $tableData['table_value'],
                ];
            }
        }

        if (!isset($sections[$record->section_title])) {
            $sections[$record->section_title] = [];
        }

        $sections[$record->section_title][] = [
            'label_en' => $fieldname,
            'fieldvalue' => $fieldvalue,
        ];
    }
}

$recordset->close();

// Start capturing output
ob_start();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 80%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header img {
            max-width: 150px;
            margin: 10px auto;
        }

        .header h1 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #555;
            margin: 2px;
        }

        .form-description {
            text-align: center;
            font-style: italic;
            margin-bottom: 30px;
            color: #555;
        }

        .section-card {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fafafa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-card h2 {
            font-size: 1.6em;
            font-size: 12px;
            /* Set font size to 12px */
            color: #333;
            margin-bottom: 15px;
            text-transform: uppercase;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .field-container {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .field-container:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .label {
            width: 30%;
            font-weight: bold;
            color: #333;
        }

        .value {
            width: 70%;
            color: #555;
            padding-left: 10px;
        }

        .table-container {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th,
        .table-container td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table-container th {
            background-color: #f0f0f0;
            color: #333;
        }

        .table-container td {
            background-color: #fff;
            color: #555;
        }

        .table-container tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-container tr:hover {
            background-color: #f1f1f1;
        }

        .image-container {
            text-align: center;
            margin: 20px 0;
        }

        .image-container img {
            max-width: 50px;
            /* Reduced size */
            height: 50px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <!-- <h1>लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</h1>
            <p>Lal Bahadur Shastri National Academy of Administration</p>
            <p>मसूरी-248179 (उत्तराखंड)</p> -->
            <p>Mussoorie-248 179 (Uttarakhand)</p>
        </div>

        <div class="form-description">
            <p><?php echo htmlspecialchars($formdesc); ?></p>
        </div>

        <div class="image-container">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" style="width: 150px; height: auto; border-radius: 8px;">
        </div>

        <?php foreach ($sections as $section_title => $fields): ?>


            <div class="section-card">
                <h2><?php echo htmlspecialchars($section_title); ?></h2>
                <?php if (isset($fields['headers'])): ?>
                    <table class="table-container">
                        <thead>
                            <tr>
                                <?php foreach ($fields['headers'] as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Step 1: Get unique row indices (based on table_value keys)
                            $rowKeys = [];
                            foreach ($fields['table_value'] as $key => $value) {
                                // Split the key into row and column indices (e.g., 0_0, 1_0)
                                list($row, $column) = explode('_', $key);
                                $rowKeys[$column] = true; // Store unique row indices
                            }

                            // Step 2: Get actual list of row indices (from 0, 1, 2, etc.)
                            $rowKeys = array_keys($rowKeys); // This will give us an array of row indices (e.g., [0, 1, 2])
                            // print_object($rowKeys);die;

                            // Step 3: Iterate over each row dynamically based on row keys
                            foreach ($rowKeys as $rowKey) {
                                echo '<tr>';

                                // Step 4: Loop over the headers to generate each column for this row
                                foreach ($fields['headers'] as $index => $header) {
                                    // Build the key dynamically based on the current row and column index
                                    $cellKey = "{$index}_{$rowKey}";

                                    // Fetch the value from table_value using the dynamically built key
                                    $cellValue = $fields['table_value'][$cellKey] ?? ''; // Default to 'Not provided' if not found
                                    if (is_numeric($cellValue)) {
                                        $fieldname = strtolower(str_replace(' ', '_', $header)); // Convert header to a fieldname format
                                        $cellValue = get_name_by_id($cellValue, $fieldname) ?? ''; // Resolve name or fallback
                                    }
                                    // Output the cell value
                                    echo "<td>" . htmlspecialchars($cellValue) . "</td>";
                                }

                                echo '</tr>';
                            }
                            ?>
                        </tbody>


                    </table>
                <?php else: ?>
                    <?php foreach ($fields as $field): ?>
                        <div class="field-container">
                            <div class="label"><?php echo htmlspecialchars($field['label_en'], ENT_QUOTES, 'UTF-8'); ?>:</div>
                            <div class="value"><?php echo htmlspecialchars($field['fieldvalue'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>

<?php
$html = ob_get_clean();
$tempDir = $CFG->tempdir . '/mpdf_temp';
// Ensure the temp directory exists
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}
$fontDir = '/var/www/hardeep/public_html/moodlegyan4.0.1/vendor/mpdf/mpdf/ttfonts';  // Absolute path

var_dump(file_exists($fontDir . '/NotoSansDevanagari-Regular.ttf'));  // Should return true if file exists
// die("sssss");
// Initialize mPDF with custom font directory
$mpdf = new Mpdf([
    'tempDir' => $tempDir,
    'fontDir' => [$fontDir], // Add the custom fonts directory
    'default_font' => 'dejavusans', // Default font (for fallback)
]);

// Register the Devanagari font
$mpdf->fontdata['devanagari'] = [
    'R' => $fontDir . '/NotoSansDevanagari-Regular.ttf', // Regular font
    'B' => $fontDir . '/NotoSansDevanagari-Bold.ttf',    // Bold font (if exists)
];

// Set the font to Devanagari
$mpdf->SetFont('devanagari');
// $html = ob_get_clean();

// Write HTML to PDF
$mpdf->WriteHTML($html);
// Output PDF for download
$mpdf->Output('form_details.pdf', 'I');
?>
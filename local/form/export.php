<?php
require_once('../../config.php');
require_once('lib.php');

require_login();

if (!local_form_is_teacher_or_admin()) {
    $redirecturl = new moodle_url('/my');
    redirect(
        $redirecturl,
        get_string('access_denied_teachers_only', 'local_form'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$dataformat = optional_param('dataformat', '', PARAM_TEXT);
$dataformat = strtolower(trim($dataformat));

$page    = optional_param('page', 0, PARAM_INT);
$formid  = optional_param('formid', 0, PARAM_INT);
$visible = optional_param('visible', 1, PARAM_INT);

$perpage = COURSEERPAGE;

$columns = get_dynamic_columns('form_submissions', $formid);
$rs      = printcourseData($formid, $columns, $page, $perpage, $visible, $dataformat);

$filename = 'courseregisteration';

if ($dataformat == 'excel') {

    if (ob_get_length()) {
        ob_clean();
    }

    if (headers_sent($file, $line)) {
        throw new \moodle_exception(
            'Headers already sent in ' . $file . ' on line ' . $line
        );
    }

    require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    $col = 1;
    foreach ($columns as $column) {
        $sheet->setCellValueByColumnAndRow($col, 1, $column);
        $col++;
    }
    $rowindex = 2;
    foreach ($rs as $row) {
        $col = 1;
        foreach ($columns as $column) {
            $value = $row[$column] ?? '';
            $cell  = $sheet->getCellByColumnAndRow($col, $rowindex);
            if (!empty($value) &&
                preg_match('/\.(pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|ppt|pptx|zip)$/i', $value)
            ) {

                $filenameonly = basename($value);
                $decoded      = rawurldecode($filenameonly);
                $url          = $CFG->wwwroot . '/local/form/pix/' . rawurlencode($decoded);

                $cell->setValueExplicit(
                    $filenameonly,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );

                $cell->getHyperlink()->setUrl($url);

            } else {

                $cell->setValueExplicit(
                    (string)$value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }

            $col++;
        }

        $rowindex++;
    }

    foreach (range('A', $sheet->getHighestColumn()) as $colLetter) {
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');

    exit;
}
\core\dataformat::download_data(
    $filename,
    $dataformat,
    $columns,
    $rs,
    null
);
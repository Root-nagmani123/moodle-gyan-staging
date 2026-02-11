<?php

defined('MOODLE_INTERNAL') || die();
require_once('lib.php');
class local_form_renderer extends plugin_renderer_base
{

    public function local_manageforms($records, $recordcount, $page, $perpage)
    {
        global $OUTPUT;
        $o = '';

        if ($records) {
            // Create table
            $table = new html_table();
            $table->attributes['class'] = 'table table-striped table-hover';
            $table->id = 'darkheader';
            $table->head = array(
                get_string('sno', 'local_form'),
                get_string('formid', 'local_form'),
                get_string('formname', 'local_form'),
                get_string('description', 'local_form'),
                get_string('timecreated', 'local_form'),
                // get_string('manageformfields', 'local_form'),
                // get_string('displayform', 'local_form'),
                get_string('list', 'local_form'),
                get_string('notsubmit', 'local_form'),
                get_string('action', 'local_form'),
            );

            $i = ($page) ? $page * $perpage : 0;
            $nooftiles = 0;
            foreach ($records as $record) {
                $i++;
                // Toggle visibility icon
                $showeye = ($record->visible) ? 'fa-eye' : 'fa-eye-slash';
                $visible = html_writer::tag('i', '', array('class' => "fa $showeye fa-fw visible_form", 'id' => $record->id, 'aria-hidden' => 'true'));
                $moveup = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/up'), 'alt' => get_string('up'), 'title' => get_string('up'), 'class' => 'icon moveup', 'moveup' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                $down = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/down'), 'alt' => get_string('down'), 'title' => get_string('down'), 'class' => 'icon movedown', 'movedown' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                if ($nooftiles == 0) {
                    $moveup = '';
                }
                if ($nooftiles == count($records) - 1) {
                    $down = '';
                }
                // Action icons
                $actiontag = html_writer::tag('i', '', array('class' => 'fa fa-cog fa-fw', 'aria-hidden' => 'true'));
                $managefields = html_writer::link(new moodle_url('/local/form/edit.php', array('formid' => $record->id)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $editaction = html_writer::link(new moodle_url('/local/form/addnewform.php', array('formid' => $record->id, 'page' => $page)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $action = html_writer::tag('div', $editaction . $visible . $moveup . $down, array('class' => 'action-icons'));
                $viewform = html_writer::link(new moodle_url('/local/form/downloadpdf.php', array('formid' => $record->id, 'page' => $page)), get_string('view', 'local_form'));
                $formlist = html_writer::link(new moodle_url('/local/form/courselist.php', array('formid' => $record->id)), get_string('view', 'local_form'));
                $notsubmitlist = html_writer::link(new moodle_url('/local/form/pending_submission.php', array('formid' => $record->id)), get_string('tosubmit', 'local_form'));
                $table->data[] = array(
                    $i,
                    $record->id,
                    $record->name,
                    $record->description,
                    userdate($record->timecreated), // Format date
                    // $managefields,
                    // $viewform,
                    $formlist,
                    $notsubmitlist,
                    $action,
                );
                $nooftiles++;
            }

            $o .= html_writer::table($table);

            // Pagination
            $baseurl = new moodle_url('/local/form/manageform.php', array('page' => $page));
            $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
            $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
            $o .= $pagination;
        } else {
            $o .= html_writer::tag('div', get_string('norecord', 'local_form'), array('class' => 'alert alert-warning no-course-enrol mt-2 container text-center'));
        }

        return $o;
    }

    // List of inactive forms

    public function local_inactive_formlist($records, $recordcount, $page, $perpage)
    {
        global $OUTPUT;
        $o = '';

        if ($records) {
            // Create table
            $table = new html_table();
            $table->attributes['class'] = 'table table-striped table-hover';
            $table->id = 'darkheader';
            $table->head = array(
                get_string('sno', 'local_form'),
                get_string('formid', 'local_form'),
                get_string('formname', 'local_form'),
                get_string('description', 'local_form'),
                get_string('timecreated', 'local_form'),
                // get_string('manageformfields', 'local_form'),
                get_string('action', 'local_form'),
                // get_string('displayform', 'local_form'),
                get_string('list', 'local_form'),
            );

            $i = ($page) ? $page * $perpage : 0;
            $nooftiles = 0;
            foreach ($records as $record) {
                $i++;
                // Toggle visibility icon
                $showeye = ($record->visible) ? 'fa-eye' : 'fa-eye-slash';
                $visible = html_writer::tag('i', '', array('class' => "fa $showeye fa-fw visible_form", 'id' => $record->id, 'aria-hidden' => 'true'));
                $moveup = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/up'), 'alt' => get_string('up'), 'title' => get_string('up'), 'class' => 'icon moveup', 'moveup' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                $down = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/down'), 'alt' => get_string('down'), 'title' => get_string('down'), 'class' => 'icon movedown', 'movedown' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                if ($nooftiles == 0) {
                    $moveup = '';
                }
                if ($nooftiles == count($records) - 1) {
                    $down = '';
                }
                // Action icons
                $actiontag = html_writer::tag('i', '', array('class' => 'fa fa-cog fa-fw', 'aria-hidden' => 'true'));
                $managefields = html_writer::link(new moodle_url('/local/form/edit.php', array('formid' => $record->id)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $editaction = html_writer::link(new moodle_url('/local/form/addnewform.php', array('formid' => $record->id, 'page' => $page)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $action = html_writer::tag('div', $editaction . $visible . $moveup . $down, array('class' => 'action-icons'));
                $viewform = html_writer::link(new moodle_url('/local/form/downloadpdf.php', array('formid' => $record->id, 'page' => $page)), get_string('view', 'local_form'));
                $formlist = html_writer::link(new moodle_url('/local/form/courselist.php', array('formid' => $record->id)), get_string('list', 'local_form'));
                $table->data[] = array(
                    $i,
                    $record->id,
                    $record->name,
                    $record->description,
                    userdate($record->timecreated), // Format date
                    // $managefields,
                    $action,
                    // $viewform,
                    $formlist,
                );
                $nooftiles++;
            }

            $o .= html_writer::table($table);

            // Pagination
            $baseurl = new moodle_url('/local/form/inactive_forms.php', array('page' => $page));
            $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
            $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
            $o .= $pagination;
        } else {
            $o .= html_writer::tag('div', get_string('norecord', 'local_form'), array('class' => 'alert alert-warning no-course-enrol mt-2 container text-center'));
        }

        return $o;
    }

    public function local_userdashboard($records, $recordcount, $page, $perpage)
    {
        global $DB, $OUTPUT;
        $o = '';
        if ($records) {
            // Create table
            $table = new html_table();
            $table->attributes['class'] = 'table table-striped table-hover';
            $table->id = 'darkheader';
            $table->head = array(
                get_string('sno', 'local_form'),
                // get_string('formid', 'local_form'),
                get_string('formname', 'local_form'),
                get_string('description', 'local_form'),
                get_string('timecreated', 'local_form'),
                // get_string('manageformfields', 'local_form'),
                // get_string('action', 'local_form'),
                // get_string('displayform', 'local_form'),
                get_string('register', 'local_form'),
            );

            $i = ($page) ? $page * $perpage : 0;
            $nooftiles = 0;
            foreach ($records as $record) {
                $i++;
                // Toggle visibility icon
                $showeye = ($record->visible) ? 'fa-eye' : 'fa-eye-slash';
                $visible = html_writer::tag('i', '', array('class' => "fa $showeye fa-fw visible_form", 'id' => $record->id, 'aria-hidden' => 'true'));
                $moveup = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/up'), 'alt' => get_string('up'), 'title' => get_string('up'), 'class' => 'icon moveup', 'moveup' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                $down = html_writer::link('', html_writer::empty_tag('img', array('src' => $OUTPUT->image_url('t/down'), 'alt' => get_string('down'), 'title' => get_string('down'), 'class' => 'icon movedown', 'movedown' => $record->sortorder, 'id' => $record->id, 'tablename' => 'local_form')));
                if ($nooftiles == 0) {
                    $moveup = '';
                }
                if ($nooftiles == count($records) - 1) {
                    $down = '';
                }
                // Action icons
                $actiontag = html_writer::tag('i', '', array('class' => 'fa fa-cog fa-fw', 'aria-hidden' => 'true'));
                $managefields = html_writer::link(new moodle_url('/local/form/edit.php', array('formid' => $record->id)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $editaction = html_writer::link(new moodle_url('/local/form/addnewform.php', array('formid' => $record->id, 'page' => $page)), $actiontag, array('class' => 'btn btn-outline-primary btn-sm'));
                $action = html_writer::tag('div', $editaction . $visible . $moveup . $down, array('class' => 'action-icons'));
                $viewform = html_writer::link(new moodle_url('/local/form/downloadpdf.php', array('formid' => $record->id, 'page' => $page)), get_string('view', 'local_form'));
                $formlist = html_writer::link(new moodle_url('/local/form/addform.php', array('formid' => $record->id, 'page' => $page)), get_string('click', 'local_form'));
                $table->data[] = array(
                    $i,
                    // $record->id,
                    $record->name,
                    $record->description,
                    userdate($record->timecreated), // Format date
                    // $managefields,
                    // $action,
                    // $viewform,
                    $formlist,
                );
                $nooftiles++;
            }

            $o .= html_writer::table($table);

            // Pagination
            $baseurl = new moodle_url('/local/form/userdashboard.php', array('page' => $page));
            $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
            $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
            $o .= $pagination;
        } else {
            $o .= html_writer::tag('div', get_string('norecord', 'local_form'), array('class' => 'alert alert-warning no-course-enrol mt-2 container text-center'));
        }

        return $o;
    }


    public function local_formdata($records, $recordcount, $page, $perpage, $formid, $uid)
    {
        $o = '';
        // Assuming you're in a Moodle context with access to $DB and $OUTPUT
        global $DB, $OUTPUT, $USER;

        // Fetch and process data
        $sql = "SELECT * FROM  {form_submissions} where uid = $uid and formid = $formid"; // Use Moodle's table name conventions
        $records = $DB->get_records_sql($sql);

        $fields = [];
        $users = [];

        foreach ($records as $record) {
            $uid = $record->uid;
            $fieldname = $record->fieldname;
            $fieldvalue = $record->fieldvalue;

            if (!in_array($fieldname, $fields)) {
                $fields[] = $fieldname;
            }

            if (!isset($users[$uid])) {
                $users[$uid] = [];
            }
            $users[$uid][$fieldname] = $fieldvalue;
        }

        // Generate table headers
        $headers = array_merge(
            ['UID', 'Username'], // Static header
            $fields // Dynamic headers based on fields

        );

        // Initialize the HTML table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->id = 'dynamic_table';
        $table->head = $headers;


        // Add rows to the table
        // foreach ($users as $uid => $user_data) {
        //     $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
        //     $row = [$uid, $username];

        //     foreach ($fields as $field) {
        //         print_object($field);
        //         if (!empty($user_data[$field])) {
        //             // Get the field value
        //             $fieldvalue = $user_data[$field];
            
        //             // Check if the field requires `get_name_by_id`
        //             if (in_array($field, ['country', 'state', 'district'])) {
        //                 // Use `get_name_by_id` to fetch the name based on ID
        //                 $fieldvalue = get_name_by_id($fieldvalue, $field) ?: 'Not provided';
        //             }
        //         }
        //         if ($field === 'file' && !empty($user_data[$field])) {
        //             // Display the image for the 'file' field
        //             $image_url = htmlspecialchars($user_data[$field]); // Sanitize the URL
        //             $logo_path = new moodle_url('/local/form/pix/' . $image_url);
        //             $row[] = html_writer::empty_tag('img', ['src' => $logo_path, 'alt' => 'Uploaded Image', 'width' => '100']);
        //         } else {
        //             $row[] = isset($user_data[$field]) ? $user_data[$field] : '';
        //         }
        //     }

        //     $table->data[] = $row;
        // }
        foreach ($users as $uid => $user_data) {
            // Fetch the username
            $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
            $row = [$uid, $username];
        
            // Loop through each field
            foreach ($fields as $field) {
                if (!empty($user_data[$field])) {
                    // Get the field value
                    $fieldvalue = $user_data[$field];
        
                    // Check if the field requires `get_name_by_id`
                    // if (in_array($field, ['country', 'state', 'district', 'language', 'admissioncategory','stream','institution','jobtype',
                    //                       'boardname','qualification','religion','service','sports','clothsize','fcscale','distinction','fatherprofession',
                    //                       'pantsize','shoessize','studentskill'])) {
                    //     $fieldvalue = get_name_by_id($fieldvalue, $field) ?: 'Not provided';
                    // }

                    $validFields = [
                        'country', 'state', 'district', 'language', 'admissioncategory', 'stream', 
                        'institution', 'jobtype', 'boardname', 'qualification', 'religion', 
                        'service', 'sports', 'size', 'fcscale', 'distinction', 
                        'fatherprofession', 'trouser', 'shoessize', 'studentskill'
                    ];
                    
                    $isFieldValid = false;
                    
                    foreach ($validFields as $validField) {
                        if (strpos($field, $validField) !== false) {
                            $isFieldValid = true;
                            break; // Exit the loop once a match is found
                        }
                    }

                    // if (stripos(trim($field), 'size') !== false) {
                    if ($isFieldValid) {

                        $fieldvalue = get_name_by_id($fieldvalue, $field) ?: 'Not provided';
                    }
                // }
        
                    // Handle file fields separately
                    if ($field === 'file') {
                        // Sanitize the file URL
                        $file_url = htmlspecialchars($fieldvalue);
        
                        // Check the file extension to determine if it's an image
                        $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
                        if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                            // Display the image
                            $logo_path = new moodle_url('/local/form/pix/' . $file_url);
                            $row[] = html_writer::empty_tag('img', [
                                'src' => $logo_path,
                                'alt' => 'Uploaded Image',
                                'width' => '100',
                            ]);
                        } elseif (strtolower($file_extension) === 'pdf') {
                            // Provide a link for PDFs
                            $pdf_path = new moodle_url('/local/form/pix/' . $file_url);
                            $row[] = html_writer::link($pdf_path, 'Download/View PDF', ['target' => '_blank']);
                        } else {
                            // For other file types, just display the sanitized URL
                            $row[] = $file_url;
                        }
                        continue; // Skip adding this field value as it's already handled
                    }
        
                    // Add the processed field value to the row
                    $row[] = $fieldvalue;
                } else {
                    // Default value for empty fields
                    $row[] = isset($user_data[$field]) ? $user_data[$field] : 'Not provided';
                }
            }
        
            // Add the row to the table data
            $table->data[] = $row;
        }
        

        // Output the table
        $o .=  html_writer::table($table);
        // Pagination
        $baseurl = new moodle_url('/local/form/displayform.php', array('formid' => $formid, 'page' => $page));
        // $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
        // $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
        // $o .= $pagination;
        $o .= $OUTPUT->download_dataformat_selector(get_string('download', 'local_form'), 'download.php', 'dataformat', array('formid' => $formid, 'page' => $page, 'uid' => $uid));

        return $o;
    }


    public function local_allcourselist($records, $recordcount, $page, $perpage, $formid)
    {
        $o = '';
        global $DB, $OUTPUT, $USER;

        // Fetch distinct UID count for the given formid
        $sql_count = "SELECT COUNT(DISTINCT uid) AS total_students FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
        $total_students = $DB->get_field_sql($sql_count, ['formid' => $formid, 'visible' => 1]);

        // Fetch and process data for the table
        $sql = "SELECT * FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
        $records = $DB->get_records_sql($sql, ['formid' => $formid, 'visible' => 1]);

        $fields = [];
        $users = [];

        foreach ($records as $record) {
            $uid = $record->uid;
            $fieldname = $record->fieldname;
            $fieldvalue = $record->fieldvalue;
            if ($fieldname == 'table') {
                continue;
            }

            if (!in_array($fieldname, $fields)) {
                $fields[] = $fieldname;
            }

            if (!isset($users[$uid])) {
                $users[$uid] = [];
            }
            $users[$uid][$fieldname] = $fieldvalue;
        }

        // Generate table headers
        $headers = array_merge(['Select', 'UID', 'Username', 'Password'], $fields); // Add 'Select' header for the checkboxl
        $headers[] = 'View';
        $headers[] = 'Download PDF';

        // Initialize the HTML table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->id = 'dynamic_table';
        $table->head = $headers;

        // Add rows to the table
        foreach ($users as $uid => $user_data) {
            $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
            // Fetch and decrypt the password
            $encrypted_password = $DB->get_field('local_user', 'password', ['userid' => $uid]);
            $row = [];
            // Add a checkbox with a link to the UID
            $checkbox = html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'uid_checkbox[]', 'class' => 'uidcheckbox', 'value' => $uid]);
            $row[] = $checkbox;

            $row[] = $uid;
            $row[] = $username;
            $row[] = $encrypted_password;



            foreach ($fields as $field) {
                // print_object($field);
                if (!empty($user_data[$field])) {
                    // Get the field value
                    $fieldvalue = $user_data[$field];
                // print_object($fieldvalue);

                    // Check if the field requires `get_name_by_id`
                    // if (in_array($field, ['country', 'state', 'district', 'language', 'admissioncategory','stream','institution','jobtype',
                    //                       'boardname','qualification','religion','service','sports','clothsize','fcscale','distinction','fatherprofession',
                    //                        'pantsize','shoessize','studentskill'])) {
                    //     // Use `get_name_by_id` to fetch the name based on ID
                    //     $fieldvalue = get_name_by_id($fieldvalue, $field) ?: 'Not provided';
                    // }
                    $validFields = [
                        'country', 'state', 'district', 'language', 'admissioncategory', 'stream', 
                        'institution', 'jobtype', 'boardname', 'qualification', 'religion', 
                        'service', 'sports', 'size', 'fcscale', 'distinction', 
                        'fatherprofession', 'trouser', 'shoessize', 'studentskill'
                    ];
                    
                    $isFieldValid = false;
                    
                    foreach ($validFields as $validField) {
                        if (strpos($field, $validField) !== false) {
                            $isFieldValid = true;
                            break; // Exit the loop once a match is found
                        }
                    }

                    if ($isFieldValid) {
                        $fieldvalue = get_name_by_id($fieldvalue, $field) ?: 'Not provided';
                    }else{
                        $fieldvalue = $fieldvalue;

                    }
                    // Get the file URL and sanitize
                    $file_url = htmlspecialchars($user_data[$field]);

                    // Check if the file is an image
                    $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                        // Display the image
                        $image_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::empty_tag('img', ['src' => $image_path, 'alt' => 'Uploaded Image', 'width' => '100']);
                    }
                    // Check if the file is a PDF
                    elseif (strtolower($file_extension) === 'pdf') {
                        // Provide a link to view or download the PDF
                        $pdf_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::link($pdf_path, 'Download/View PDF', ['target' => '_blank']);
                    } else {
                        // Handle other file types (if necessary)
                        $row[] = $fieldvalue;
                    }
                } else {
                    $row[] = isset($user_data[$field]) ? $user_data[$field] : '';
                }
            }


            // View link
            $view_url = new moodle_url('/local/form/displayform.php', ['formid' => $formid, 'uid' => $uid]);
            $view_link = html_writer::link($view_url, 'View');
            $row[] = $view_link;

            // Download PDF link
            $pdf_url = new moodle_url('/local/form/downloadpdf.php', ['formid' => $formid, 'uid' => $uid]);
            $pdf_link = html_writer::link($pdf_url, 'Download');
            $row[] = $pdf_link;

            // Add the row to the table
            $table->data[] = $row;
        }

        // Add the "Total Registered Students" text
        $o .= html_writer::tag('h3', 'Total Register Students: ' . $total_students);

        // Output the table
        $o .= html_writer::table($table);

        // Pagination
        $baseurl = new moodle_url('/local/form/allcourselist.php', ['formid' => $formid, 'page' => $page]);
        $o .= $OUTPUT->download_dataformat_selector(
            get_string('download', 'local_form'),
            'export.php',
            'dataformat',
            ['formid' => $formid, 'page' => $page, 'visible' => 1]
        );

        return $o;
    }




    public function left_form_logo()
    {
        global $DB;
        $o = '';
        $sliderimages = get_homepage_leftimage();
        if ($sliderimages) {
            foreach ($sliderimages as $image) {
                $o .= html_writer::img($image->url, '', array('width' => '130px', 'height' => '100px'));
            }
        }

        return $o;
    }

    public function right_form_logo()
    {
        global $DB;
        $o = '';
        $sliderimages = get_homepage_rightimage();
        if ($sliderimages) {
            foreach ($sliderimages as $image) {
                $o .= html_writer::img($image->url, '', array('width' => '130px', 'height' => '100px'));
            }
        }

        return $o;
    }

    public function local_cohort()
    {
        global $DB, $OUTPUT;

        // Initialize options array
        $options[] = "Select Cohort";

        // Fetch cohort records from the database
        $sql = "SELECT id, name FROM {cohort}"; // Use Moodle's table name conventions
        $records = $DB->get_records_sql($sql);

        // Populate options array
        foreach ($records as $record) {
            $options[$record->id] = $record->name; // Correct way to assign name to option
        }

        // Create label for the dropdown
        $label = html_writer::label(get_string('selectcohort', 'local_form'), 'quizviewfilter', false);

        // Create the dropdown (select) element
        $dropdown = html_writer::select($options, "quizview", '', array(), array('id' => 'quizviewfilter'));

        // Add some space between the label and dropdown (you can use HTML <br> or CSS style for spacing)
        $space = html_writer::empty_tag('br');  // Add a line break for spacing

        // Create the button with label "Add Selected Student to Cohort"
        $button = html_writer::empty_tag('input', array(
            'type' => 'submit',
            'value' => get_string('addselectedstudent', 'local_form'),
            'id' => 'add_to_cohort_button',
            'class' => 'btn btn-primary'
        ));

        // Combine label, dropdown, space, and button
        $output = $label . $dropdown . $space . $space . $button;

        return $output;
    }

    public function local_inactive_users($formid)
    {
        // Create the button with href pointing to inactiveusers.php
        $button = html_writer::tag('a', get_string('inactiveuser', 'local_form'), array(
            'href' => new moodle_url('/local/form/inactiveusers.php', array('formid' => $formid, 'visible' => 0)), // Adjust the path as needed
            'id' => 'inactiveuserbutton',
            'class' => 'btn btn-primary'
        ));

        // Combine label, space, and button

        return $button;
    }


    //Inactive users records
    public function local_inactive_user_report($records, $recordcount, $page, $perpage, $formid)
    {
        $o = '';
        global $DB, $OUTPUT, $USER;

        // Fetch distinct UID count for the given formid
        $sql_count = "SELECT COUNT(DISTINCT uid) AS total_students FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
        $total_students = $DB->get_field_sql($sql_count, ['formid' => $formid, 'visible' => 0]);

        // Fetch and process data for the table
        $sql = "SELECT * FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
        $records = $DB->get_records_sql($sql, ['formid' => $formid, 'visible' => 0]);

        $fields = [];
        $users = [];

        foreach ($records as $record) {
            $uid = $record->uid;
            $fieldname = $record->fieldname;
            $fieldvalue = $record->fieldvalue;

            if (!in_array($fieldname, $fields)) {
                $fields[] = $fieldname;
            }

            if (!isset($users[$uid])) {
                $users[$uid] = [];
            }
            $users[$uid][$fieldname] = $fieldvalue;
        }

        // Generate table headers
        $headers = array_merge(['Select', 'UID', 'Username', 'Password'], $fields); // Add 'Select' header for the checkboxl
        $headers[] = 'View';
        $headers[] = 'Download PDF';

        // Initialize the HTML table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->id = 'dynamic_table';
        $table->head = $headers;

        // Add rows to the table
        foreach ($users as $uid => $user_data) {
            $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
            // Fetch and decrypt the password
            $encrypted_password = $DB->get_field('local_user', 'password', ['userid' => $uid]);
            $row = [];
            // Add a checkbox with a link to the UID
            $checkbox = html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'uid_checkbox[]', 'class' => 'uidcheckbox', 'value' => $uid]);
            $row[] = $checkbox;

            $row[] = $uid;
            $row[] = $username;
            $row[] = $encrypted_password;



            foreach ($fields as $field) {
                if (!empty($user_data[$field])) {
                    // Get the file URL and sanitize
                    $file_url = htmlspecialchars($user_data[$field]);

                    // Check if the file is an image
                    $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                        // Display the image
                        $image_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::empty_tag('img', ['src' => $image_path, 'alt' => 'Uploaded Image', 'width' => '100']);
                    }
                    // Check if the file is a PDF
                    elseif (strtolower($file_extension) === 'pdf') {
                        // Provide a link to view or download the PDF
                        $pdf_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::link($pdf_path, 'Download/View PDF', ['target' => '_blank']);
                    } else {
                        // Handle other file types (if necessary)
                        $row[] = $user_data[$field];
                    }
                } else {
                    $row[] = isset($user_data[$field]) ? $user_data[$field] : '';
                }
            }


            // View link
            $view_url = new moodle_url('/local/form/displayform.php', ['formid' => $formid, 'uid' => $uid]);
            $view_link = html_writer::link($view_url, 'View');
            $row[] = $view_link;

            // Download PDF link
            $pdf_url = new moodle_url('/local/form/downloadpdf.php', ['formid' => $formid, 'uid' => $uid]);
            $pdf_link = html_writer::link($pdf_url, 'Download');
            $row[] = $pdf_link;

            // Add the row to the table
            $table->data[] = $row;
        }

        // Add the "Total Registered Students" text
        $o .= html_writer::tag('h3', 'Total Register Inactive Students: ' . $total_students);

        // Output the table
        $o .= html_writer::table($table);

        // Pagination
        $baseurl = new moodle_url('/local/form/allcourselist.php', ['formid' => $formid, 'page' => $page]);
        $o .= $OUTPUT->download_dataformat_selector(
            get_string('download', 'local_form'),
            'export.php',
            'dataformat',
            ['formid' => $formid, 'page' => $page, 'visible' => 0]
        );

        return $o;
    }

    public function local_pending_submission($records, $recordcount, $page, $perpage,$formid)
    {
        global $OUTPUT;
        $o = '';
        if ($records) {
            // Create table
            $table = new html_table();
            $table->attributes['class'] = 'table table-striped table-hover';
            $table->id = 'darkheader';
            $table->head = array(
                get_string('sno', 'local_form'),
                get_string('userid', 'local_form'),
                get_string('username_name', 'local_form'),
                get_string('fullname', 'local_form'),
                get_string('user_email', 'local_form'),
                get_string('timecreated', 'local_form'),
            );

            $i = ($page) ? $page * $perpage : 0;
            $nooftiles = 0;
            foreach ($records as $record) {
                $i++;
                $table->data[] = array(
                    $i,
                    $record->id,
                    $record->username,
                    $record->firstname . '  ' . $record->lastname,
                    $record->email,
                    userdate($record->timecreated), // Format date
                );
                $nooftiles++;
            }

            $o .= html_writer::table($table);

            // Pagination
            $baseurl = new moodle_url('/local/form/pending_submission.php', array('formid'=> $formid,'page' => $page));
            $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
            $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
            $o .= $pagination;
        } else {
            $o .= html_writer::tag('div', get_string('norecord', 'local_form'), array('class' => 'alert alert-warning no-course-enrol mt-2 container text-center'));
        }

        return $o;
    }
}

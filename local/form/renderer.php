<?php

defined('MOODLE_INTERNAL') || die();
require_once('lib.php');
class local_form_renderer extends plugin_renderer_base
{

    public function local_manageforms($records, $recordcount, $page, $perpage)
    {
        global $OUTPUT, $DB;
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
                get_string('linked_cohort', 'local_form'), // New column for cohort info
                get_string('list', 'local_form'),
                get_string('previewform', 'local_form'),
                get_string('editform', 'local_form'),
                get_string('action', 'local_form'),
                get_string('timecreated', 'local_form'),
            );

            $i = ($page) ? $page * $perpage : 0;
            $nooftiles = 0;
            foreach ($records as $record) {
                $i++;

                // Toggle visibility icon
                $showeye = ($record->visible) ? 'fa-eye' : 'fa-eye-slash';
                $visible = html_writer::tag('i', '', array(
                    'class' => "fa $showeye fa-fw visible_form",
                    'id' => $record->id,
                    'aria-hidden' => 'true',
                    'title' => ($record->visible) ? 'Hide form' : 'Show form'
                ));

                // Move up/down icons
                $moveup = html_writer::link('', html_writer::empty_tag('img', array(
                    'src' => $OUTPUT->image_url('t/up'),
                    'alt' => get_string('up'),
                    'title' => get_string('up'),
                    'class' => 'icon moveup',
                    'moveup' => $record->sortorder,
                    'id' => $record->id,
                    'tablename' => 'local_form'
                )));

                $down = html_writer::link('', html_writer::empty_tag('img', array(
                    'src' => $OUTPUT->image_url('t/down'),
                    'alt' => get_string('down'),
                    'title' => get_string('down'),
                    'class' => 'icon movedown',
                    'movedown' => $record->sortorder,
                    'id' => $record->id,
                    'tablename' => 'local_form'
                )));

                if ($nooftiles == 0) {
                    $moveup = '';
                }
                if ($nooftiles == count($records) - 1) {
                    $down = '';
                }

                // Check if form has a linked cohort (matching shortname)
                $linked_cohort = null;
                $cohort_info = '';
                if (!empty($record->shortname)) {
                    $linked_cohort = $DB->get_record('cohort', ['name' => $record->shortname]);
                    if ($linked_cohort) {
                        $cohort_info = html_writer::tag('span', $linked_cohort->name, array(
                            'class' => 'badge badge-success',
                            'title' => 'Linked to cohort: ' . $linked_cohort->name,
                            'style' => 'cursor: help;'
                        ));

                        // Add link to manage cohort members
                        global $DB;

                        $membercount = $DB->count_records('cohort_members', [
                            'cohortid' => $linked_cohort->id
                        ]);

                        $cohort_info .= ' ' . html_writer::span(
                            'Members: ' . $membercount,
                            'text-muted ml-1'
                        );
                    } else {
                        // No matching cohort found
                        $cohort_info = html_writer::tag('span', 'No linked cohort', array(
                            'class' => 'badge badge-secondary',
                            'title' => 'No cohort found with name: ' . $record->shortname,
                            'style' => 'cursor: help;'
                        ));
                    }
                } else {
                    // No shortname set
                    $cohort_info = html_writer::tag('span', 'No shortname', array(
                        'class' => 'badge badge-warning',
                        'title' => 'Form shortname is not set',
                        'style' => 'cursor: help;'
                    ));
                }

                // Action icons
                $actiontag = html_writer::tag('i', '', array(
                    'class' => 'fa fa-cog fa-fw',
                    'aria-hidden' => 'true',
                    'title' => 'Settings'
                ));

                $managefields = html_writer::link(
                    new moodle_url('/local/form/edit.php', array('formid' => $record->id)),
                    $actiontag,
                    array(
                        'class' => 'btn btn-outline-primary btn-sm',
                        'title' => 'Manage form fields'
                    )
                );

                $editaction = html_writer::link(
                    new moodle_url('/local/form/addnewform.php', array('formid' => $record->id, 'page' => $page)),
                    $actiontag,
                    array(
                        'class' => 'btn btn-outline-primary btn-sm',
                        'title' => 'Edit form settings'
                    )
                );

                $action = html_writer::tag('div', $editaction . $visible . $moveup . $down, array('class' => 'action-icons'));

                // View submissions link - with cohort auto-filter if available
                $courselist_params = ['formid' => $record->id];
                if ($linked_cohort) {
                    $courselist_params['cohortid'] = $linked_cohort->id;
                    $courselist_params['autofilter'] = 1;
                }

                $formlist = html_writer::link(
                    new moodle_url('/local/form/courselist.php', $courselist_params),
                    html_writer::tag('i', '', array('class' => 'fas fa-list mr-1')) . get_string('list', 'local_form'),
                    array(
                        'class' => 'btn btn-outline-info btn-sm',
                        'title' => $linked_cohort ?
                            'View submissions (filtered by cohort: ' . $linked_cohort->name . ')' :
                            'View all submissions'
                    )
                );

                $previewform = html_writer::link(
                    new moodle_url('/local/form/addform.php', array('formid' => $record->id)),
                    html_writer::tag('i', '', array('class' => 'fas fa-eye mr-1')) . get_string('preview', 'local_form'),
                    array(
                        'class' => 'btn btn-outline-success btn-sm',
                        'title' => 'Preview form'
                    )
                );

                $editform = html_writer::link(
                    new moodle_url('/local/form/edit.php', array('formid' => $record->id)),
                    html_writer::tag('i', '', array('class' => 'fas fa-edit mr-1')) . get_string('edit', 'local_form'),
                    array(
                        'class' => 'btn btn-outline-warning btn-sm',
                        'title' => 'Edit form structure'
                    )
                );

                $table->data[] = array(
                    $i,
                    $record->id,
                    html_writer::tag('strong', $record->name),
                    html_writer::tag('div', $record->description, array('class' => 'text-muted small')),
                    $cohort_info,
                    $formlist,
                    $previewform,
                    $editform,
                    $action,
                    html_writer::tag('small', userdate($record->timecreated), array('class' => 'text-muted')),
                );
                $nooftiles++;
            }

            $o .= html_writer::table($table);

            // Add some CSS for better styling
            $o .= html_writer::tag('style', '
            .badge {
                font-size: 0.85em;
                padding: 0.3em 0.6em;
            }
            .action-icons {
                display: flex;
                gap: 5px;
                align-items: center;
            }
            .action-icons .icon {
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.2s;
            }
            .action-icons .icon:hover {
                opacity: 1;
            }
            .table .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        ');

            // Pagination
            $baseurl = new moodle_url('/local/form/manageform.php', array('page' => $page));
            $pagination = $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl);
            $pagination = html_writer::tag('div', $pagination, array('class' => 'pagination-container text-center mt-4'));
            $o .= $pagination;

            // Add help text about cohort linking
            $o .= html_writer::start_tag('div', array('class' => 'alert alert-info mt-3 small'));
            $o .= html_writer::tag('strong', html_writer::tag('i', '', array('class' => 'fas fa-info-circle mr-1')) . 'About Cohort Linking:');
            $o .= html_writer::tag('p', 'Forms are automatically linked to cohorts when the form shortname matches a cohort name. Clicking "View Submissions" will automatically filter by the linked cohort.');
            $o .= html_writer::tag('ul', '
            <li><span class="badge badge-success">Green badge</span>: Form is linked to a cohort</li>
            <li><span class="badge badge-secondary">Gray badge</span>: No matching cohort found</li>
            <li><span class="badge badge-warning">Yellow badge</span>: Form shortname not set</li>
        ');
            $o .= html_writer::end_tag('div');
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


    public function local_formdata($records, $recordcount, $page, $perpage, $formid, $uid, $token = '')
    {
        $o = '';
        global $DB, $OUTPUT, $USER;

        // If no token provided, generate one
        if (empty($token)) {
            $token_data = local_form_generate_signed_url($formid, 'displayform', ['uid' => $uid]);
            // Extract token from URL
            if (strpos($token_data, 'token=') !== false) {
                $parts = parse_url($token_data);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        } else {
            // Ensure we have just the token value
            if (strpos($token, 'token=') !== false) {
                $parts = parse_url($token);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        }

        // Fetch and process data
        $sql = "SELECT * FROM {form_submissions} WHERE uid = :uid AND formid = :formid";
        $records = $DB->get_records_sql($sql, ['uid' => $uid, 'formid' => $formid]);

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
            ['UID', 'Username'],
            $fields
        );

        // Initialize the HTML table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->id = 'dynamic_table';
        $table->head = $headers;

        // Add rows to the table
        foreach ($users as $uid => $user_data) {
            $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
            $row = [$uid, $username];

            foreach ($fields as $field) {
                if (!empty($user_data[$field])) {
                    $file_url = htmlspecialchars($user_data[$field]);

                    // Check file type
                    $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));

                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                        // Display image
                        $image_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::empty_tag('img', [
                            'src' => $image_path,
                            'alt' => 'Uploaded Image',
                            'width' => '100',
                            'class' => 'img-thumbnail'
                        ]);
                    } elseif ($file_extension === 'pdf') {
                        // Display PDF link
                        $pdf_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::link($pdf_path, 'View PDF', [
                            'target' => '_blank',
                            'class' => 'btn btn-sm btn-primary'
                        ]);
                    } elseif (in_array($file_extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                        // Display document download link
                        $doc_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::link($doc_path, 'Download ' . strtoupper($file_extension), [
                            'target' => '_blank',
                            'class' => 'btn btn-sm btn-secondary'
                        ]);
                    } else {
                        // Display text or other content
                        $row[] = htmlspecialchars($user_data[$field]);
                    }
                } else {
                    $row[] = '';
                }
            }

            $table->data[] = $row;
        }

        // Add back button to course list
        $back_url = local_form_generate_signed_url($formid, 'courselist');
        $o .= html_writer::link($back_url, '← Back to Course List', [
            'class' => 'btn btn-secondary mb-3'
        ]);

        // Output the table
        $o .= html_writer::table($table);

        // Download dataformat selector with token
        $download_params = [
            'formid' => $formid,
            'uid' => $uid,
            'page' => $page,
            'token' => $token
        ];

        $o .= $OUTPUT->download_dataformat_selector(
            get_string('download', 'local_form'),
            'download.php',
            'dataformat',
            $download_params
        );

        return $o;
    }


    // public function local_allcourselist($records, $recordcount, $page, $perpage, $formid, $token = '')
    // {
    //     $o = '';
    //     global $DB, $OUTPUT, $USER;

    //     // If no token provided, generate one
    //     if (empty($token)) {
    //         $token = local_form_generate_signed_url($formid, 'courselist');
    //         // Extract token from URL if it's a full URL
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     } else {
    //         // Ensure we have just the token value, not the full URL
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     }

    //     // Fetch distinct UID count for the given formid
    //     $sql_count = "SELECT COUNT(DISTINCT uid) AS total_students FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
    //     $total_students = $DB->get_field_sql($sql_count, ['formid' => $formid, 'visible' => 1]);

    //     // Fetch and process data for the table
    //     $sql = "SELECT * FROM {form_submissions} WHERE formid = :formid AND visible = :visible";
    //     $records = $DB->get_records_sql($sql, ['formid' => $formid, 'visible' => 1]);

    //     $fields = [];
    //     $users = [];

    //     foreach ($records as $record) {
    //         $uid = $record->uid;
    //         $fieldname = $record->fieldname;
    //         $fieldvalue = $record->fieldvalue;

    //         if (!in_array($fieldname, $fields)) {
    //             $fields[] = $fieldname;
    //         }

    //         if (!isset($users[$uid])) {
    //             $users[$uid] = [];
    //         }
    //         $users[$uid][$fieldname] = $fieldvalue;
    //     }

    //     // Generate table headers
    //     $headers = array_merge(['Select', 'UID', 'Username', 'Password'], $fields);
    //     $headers[] = 'View';
    //     $headers[] = 'Download PDF';

    //     // Initialize the HTML table
    //     $table = new html_table();
    //     $table->attributes['class'] = 'table table-striped table-hover';
    //     $table->id = 'dynamic_table';
    //     $table->head = $headers;

    //     // Add rows to the table
    //     foreach ($users as $uid => $user_data) {
    //         $username = $DB->get_field('user', 'username', ['id' => $uid]) ?? 'N/A';
    //         // Fetch and decrypt the password
    //         $encrypted_password = $DB->get_field('local_user', 'password', ['userid' => $uid]);
    //         $row = [];

    //         // Add a checkbox with a link to the UID
    //         $checkbox = html_writer::empty_tag('input', [
    //             'type' => 'checkbox',
    //             'name' => 'uid_checkbox[]',
    //             'class' => 'uidcheckbox',
    //             'value' => $uid
    //         ]);
    //         $row[] = $checkbox;

    //         $row[] = $uid;
    //         $row[] = $username;
    //         $row[] = $encrypted_password;

    //         foreach ($fields as $field) {
    //             if (!empty($user_data[$field])) {
    //                 // Get the file URL and sanitize
    //                 $file_url = htmlspecialchars($user_data[$field]);

    //                 // Check if the file is an image
    //                 $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
    //                 if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
    //                     // Display the image
    //                     $image_path = new moodle_url('/local/form/pix/' . $file_url);
    //                     $row[] = html_writer::empty_tag('img', [
    //                         'src' => $image_path,
    //                         'alt' => 'Uploaded Image',
    //                         'width' => '100'
    //                     ]);
    //                 }
    //                 // Check if the file is a PDF
    //                 elseif (strtolower($file_extension) === 'pdf') {
    //                     // Provide a link to view or download the PDF
    //                     $pdf_path = new moodle_url('/local/form/pix/' . $file_url);
    //                     $row[] = html_writer::link($pdf_path, 'Download/View PDF', ['target' => '_blank']);
    //                 } else {
    //                     // Handle other file types (if necessary)
    //                     $row[] = $user_data[$field];
    //                 }
    //             } else {
    //                 $row[] = isset($user_data[$field]) ? $user_data[$field] : '';
    //             }
    //         }

    //         // View link - Generate signed URL for displayform.php
    //         $view_url = local_form_generate_signed_url($formid, 'displayform', ['uid' => $uid]);
    //         $view_link = html_writer::link($view_url, 'View');
    //         $row[] = $view_link;

    //         // Download PDF link - Generate signed URL for downloadpdf.php
    //         $pdf_url = local_form_generate_signed_url($formid, 'downloadpdf', ['uid' => $uid]);
    //         $pdf_link = html_writer::link($pdf_url, 'Download');
    //         $row[] = $pdf_link;

    //         // Add the row to the table
    //         $table->data[] = $row;
    //     }

    //     // Add the "Total Registered Students" text
    //     $o .= html_writer::tag('h3', 'Total Register Students: ' . $total_students);

    //     // Output the table
    //     $o .= html_writer::table($table);

    //     // Pagination with signed URLs
    //     $paging_bar = '';
    //     if ($recordcount > $perpage) {
    //         $paging_bar = $OUTPUT->paging_bar(
    //             $recordcount,
    //             $page,
    //             $perpage,
    //             new moodle_url('/local/form/courselist.php', ['token' => $token])
    //         );
    //     }
    //     $o .= $paging_bar;

    //     // Download dataformat selector - Update with token
    //     $download_params = [
    //         'formid' => $formid,
    //         'page' => $page,
    //         'visible' => 1,
    //         'token' => $token  // Add token for security
    //     ];

    //     $o .= $OUTPUT->download_dataformat_selector(
    //         get_string('download', 'local_form'),
    //         'export.php',
    //         'dataformat',
    //         $download_params
    //     );

    //     return $o;
    // }

    public function local_allcourselist($records = null, $recordcount = null, $page = 0, $perpage = 10, $formid = 0, $token = '', $cohortid = 0)
    {
        $o = '';
        global $DB, $OUTPUT, $USER;

        // If no token provided, generate one
        if (empty($token)) {
            $token = local_form_generate_signed_url($formid, 'courselist');
            // Extract token from URL if it's a full URL
            if (strpos($token, 'token=') !== false) {
                $parts = parse_url($token);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        } else {
            // Ensure we have just the token value, not the full URL
            if (strpos($token, 'token=') !== false) {
                $parts = parse_url($token);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        }

        // Build SQL queries based on cohort filter
        $sql_params = ['formid' => $formid, 'visible' => 1];
        $sql_joins = '';
        $sql_where = 'fs.formid = :formid AND fs.visible = :visible';

        if ($cohortid > 0) {
            $sql_joins = 'INNER JOIN {cohort_members} cm ON fs.uid = cm.userid';
            $sql_where .= ' AND cm.cohortid = :cohortid';
            $sql_params['cohortid'] = $cohortid;
        }

        // Count total DISTINCT users with cohort filter
        $count_sql = "SELECT COUNT(DISTINCT fs.uid)
                  FROM {form_submissions} fs 
                  $sql_joins 
                  WHERE $sql_where";

        $total_users = $DB->count_records_sql($count_sql, $sql_params);

        // Fetch DISTINCT user IDs with pagination using get_records_sql's built-in pagination
        $user_ids_sql = "SELECT DISTINCT fs.uid
                     FROM {form_submissions} fs 
                     $sql_joins 
                     WHERE $sql_where
                     ORDER BY fs.uid";

        // Use get_records_sql with limitfrom and limitnum parameters
        $paged_user_records = $DB->get_records_sql(
            $user_ids_sql,
            $sql_params,
            $page * $perpage,  // limitfrom
            $perpage           // limitnum
        );

        $paged_user_ids = array_keys($paged_user_records);

        if (empty($paged_user_ids)) {
            // Return empty table message
            $o .= html_writer::tag('div', 'No records found', ['class' => 'alert alert-info']);

            // Add the "Total Registered Students" text with cohort info
            $total_text = 'Total Registered Students: ' . $total_users;
            if ($cohortid > 0) {
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
                if ($cohort) {
                    $total_text .= ' (Filtered by cohort: ' . $cohort->name . ')';
                }
            }
            $o .= html_writer::tag('h3', $total_text);

            return $o;
        }

        // IMPORTANT: Convert array to comma-separated string for IN clause
        // But first check if we have any IDs
        if (empty($paged_user_ids)) {
            $o .= html_writer::tag('div', 'No records found', ['class' => 'alert alert-info']);
            return $o;
        }

        // Create placeholders for the IN clause
        $placeholders = [];
        $data_params = ['formid' => $formid, 'visible' => 1];
        $i = 0;

        foreach ($paged_user_ids as $uid) {
            $param_name = 'uid' . $i;
            $placeholders[] = ':' . $param_name;
            $data_params[$param_name] = $uid;
            $i++;
        }

        $in_clause = implode(', ', $placeholders);

        // Fetch all data for the paginated users
        $data_sql = "SELECT fs.* 
                 FROM {form_submissions} fs 
                 WHERE fs.formid = :formid 
                   AND fs.visible = :visible 
                   AND fs.uid IN ($in_clause)
                 ORDER BY fs.uid";

        $all_records = $DB->get_records_sql($data_sql, $data_params);

        // Group records by user ID and field names
        $fields = [];
        $users = [];

        foreach ($all_records as $record) {
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
        $headers = array_merge(['Select', 'UID', 'Username', 'Full Name', 'Email'], $fields);

        // Add cohort column if not filtering by specific cohort
        if ($cohortid == 0) {
            array_splice($headers, 5, 0, ['Cohort(s)']); // Insert after Email
        }

        $headers[] = 'View';
        $headers[] = 'Download PDF';

        // Initialize the HTML table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->id = 'dynamic_table';
        $table->head = $headers;

        // Add rows to the table for paginated users
        foreach ($paged_user_ids as $uid) {
            $user_data = $users[$uid];

            // Get user details
            $user = $DB->get_record('user', ['id' => $uid], 'username, firstname, lastname, email');
            $username = $user ? $user->username : 'N/A';
            $fullname = $user ? fullname($user) : 'N/A';
            $email = $user ? $user->email : 'N/A';

            $row = [];

            // Add a checkbox with a link to the UID
            $checkbox = html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'name' => 'uid_checkbox[]',
                'class' => 'uidcheckbox',
                'value' => $uid
            ]);
            $row[] = $checkbox;

            $row[] = $uid;
            $row[] = $username;
            $row[] = $fullname;
            $row[] = $email;

            // Add cohort column if not filtering by specific cohort
            if ($cohortid == 0) {
                // Get user's cohorts
                $user_cohorts = $DB->get_records_sql(
                    "SELECT c.id, c.name 
                 FROM {cohort} c
                 JOIN {cohort_members} cm ON c.id = cm.cohortid
                 WHERE cm.userid = :userid",
                    ['userid' => $uid]
                );

                $cohort_names = [];
                foreach ($user_cohorts as $cohort) {
                    $cohort_names[] = $cohort->name;
                }
                $row[] = !empty($cohort_names) ? implode(', ', $cohort_names) : 'None';
            }

            foreach ($fields as $field) {
                if (!empty($user_data[$field])) {
                    // Get the file URL and sanitize
                    $file_url = htmlspecialchars($user_data[$field]);

                    // Check if the file is an image
                    $file_extension = pathinfo($file_url, PATHINFO_EXTENSION);
                    if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                        // Display the image
                        $image_path = new moodle_url('/local/form/pix/' . $file_url);
                        $row[] = html_writer::empty_tag('img', [
                            'src' => $image_path,
                            'alt' => 'Uploaded Image',
                            'width' => '100'
                        ]);
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

            // View link - Generate signed URL for displayform.php
            $view_url = local_form_generate_signed_url($formid, 'displayform', ['uid' => $uid]);
            $view_link = html_writer::link($view_url, 'View');
            $row[] = $view_link;

            // Download PDF link - Generate signed URL for downloadpdf.php
            $pdf_url = local_form_generate_signed_url($formid, 'downloadpdf', ['uid' => $uid]);
            $pdf_link = html_writer::link($pdf_url, 'Download');
            $row[] = $pdf_link;

            // Add the row to the table
            $table->data[] = $row;
        }

        // Add the "Total Registered Students" text with cohort info
        $total_text = 'Total Registered Students: ' . $total_users;
        if ($cohortid > 0) {
            $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
            if ($cohort) {
                $total_text .= ' (Filtered by cohort: ' . $cohort->name . ')';
            }
        }
        $o .= html_writer::tag('h3', $total_text);

        // Output the table
        $o .= html_writer::table($table);

        // Pagination - include cohortid if filtering
        if ($total_users > $perpage) {
            // Build pagination parameters
            $paging_params = [];
            if (!empty($token)) {
                $paging_params['token'] = $token;
            } else {
                $paging_params['formid'] = $formid;
            }

            if ($cohortid > 0) {
                $paging_params['cohortid'] = $cohortid;
            }

            // Create pagination URL
            $paging_url = new moodle_url('/local/form/courselist.php', $paging_params);

            // Generate pagination bar
            $paging_bar = $OUTPUT->paging_bar($total_users, $page, $perpage, $paging_url);
            $o .= $paging_bar;
        }

        // Download dataformat selector
        $download_params = [
            'formid' => $formid,
            'page' => $page,
            'visible' => 1,
            'token' => $token
        ];

        if ($cohortid > 0) {
            $download_params['cohortid'] = $cohortid;
        }

        $o .= $OUTPUT->download_dataformat_selector(
            get_string('download', 'local_form'),
            'export.php',
            'dataformat',
            $download_params
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

    //working on non registered students report

    // public function local_nonregistered_students($formid, $token = '', $page = 0, $perpage = 30, $cohortid = 0)
    // {
    //     global $DB, $OUTPUT, $CFG, $USER;

    //     $o = '';

    //     // Process token
    //     if (empty($token)) {
    //         $token = local_form_generate_signed_url($formid, 'nonregistered');
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     } else {
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     }

    //     // Build the SQL query for non-registered students
    //     $params = ['formid' => $formid];
    //     $cohort_condition = '';

    //     if ($cohortid > 0) {
    //         $cohort_condition = "AND c.id = :cohortid";
    //         $params['cohortid'] = $cohortid;
    //     }

    //     $sql = "
    // SELECT 
    //     u.id AS user_id,
    //     u.username,
    //     u.firstname,
    //     u.lastname,
    //     u.email,
    //     u.idnumber,
    //     u.phone1,
    //     u.lastaccess,
    //     c.name AS cohort_name,
    //     c.id AS cohort_id
    // FROM 
    //     {user} u
    // INNER JOIN 
    //     {cohort_members} cm ON u.id = cm.userid
    // INNER JOIN 
    //     {cohort} c ON cm.cohortid = c.id
    // LEFT JOIN 
    //     {form_submissions} fs ON u.id = fs.uid 
    //     AND fs.formid = :formid
    //     AND fs.visible = 1
    // WHERE 
    //     fs.id IS NULL
    //     $cohort_condition
    //     AND u.deleted = 0
    //     AND u.suspended = 0
    // ";

    //     // Count query
    //     $count_sql = "
    // SELECT COUNT(*)
    // FROM (
    //     SELECT DISTINCT u.id
    //     FROM 
    //         {user} u
    //     INNER JOIN 
    //         {cohort_members} cm ON u.id = cm.userid
    //     INNER JOIN 
    //         {cohort} c ON cm.cohortid = c.id
    //     LEFT JOIN 
    //         {form_submissions} fs ON u.id = fs.uid 
    //         AND fs.formid = :formid
    //         AND fs.visible = 1
    //     WHERE 
    //         fs.id IS NULL
    //         $cohort_condition
    //         AND u.deleted = 0
    //         AND u.suspended = 0
    // ) AS temp
    // ";

    //     $total_count = $DB->count_records_sql($count_sql, $params);

    //     // Add ordering and pagination to main query
    //     $sql .= " ORDER BY u.lastname, u.firstname";

    //     // Get data with pagination
    //     $nonregistered_students = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    //     // Display total count with professional badge
    //     $o .= html_writer::start_tag('div', array('class' => 'd-flex justify-content-between align-items-center mb-4'));
    //     $o .= html_writer::tag('h4', get_string('nonregistered_students', 'local_form'));
    //     $o .= html_writer::tag(
    //         'span',
    //         html_writer::tag('i', '', array('class' => 'fas fa-users mr-2')) .
    //             get_string('total_students', 'local_form') . ': ' . $total_count,
    //         array('class' => 'count-badge')
    //     );
    //     $o .= html_writer::end_tag('div');

    //     // Add notification/reminder button at the top
    //     if ($nonregistered_students) {
    //         $notify_params = array(
    //             'formid' => $formid,
    //             'cohortid' => $cohortid,
    //             'token' => $token,
    //             'action' => 'notify'
    //         );

    //         $notify_url = new moodle_url('/local/form/notify_nonregistered.php', $notify_params);

    //         $o .= html_writer::start_tag('div', array('class' => 'reminder-alert-professional'));
    //         $o .= html_writer::tag(
    //             'div',
    //             html_writer::tag('i', '', array('class' => 'fas fa-bell mr-2')) .
    //                 get_string('send_reminders', 'local_form'),
    //             array('class' => 'reminder-title')
    //         );
    //         $o .= html_writer::tag('p', get_string('reminder_instructions', 'local_form'), array('class' => 'reminder-text mb-3'));

    //         $o .= html_writer::link(
    //             $notify_url,
    //             html_writer::tag('i', '', array('class' => 'fas fa-paper-plane mr-2')) .
    //                 get_string('send_reminders_button', 'local_form'),
    //             array('class' => 'action-btn reminder-btn')
    //         );
    //         $o .= html_writer::end_tag('div');
    //     }

    //     // Cohort filter form
    //     $cohorts = $DB->get_records_sql("
    //                 SELECT DISTINCT c.id, c.name, c.idnumber
    //                 FROM {cohort} c
    //                 INNER JOIN {cohort_members} cm ON c.id = cm.cohortid
    //                 WHERE c.visible = 1
    //                 ORDER BY c.name
    //                   ");

    //     if ($cohorts) {
    //         // $o .= html_writer::start_tag('div', array('class' => 'filter-section-professional'));
    //         // $o .= html_writer::tag(
    //         //     'div',
    //         //     html_writer::tag('i', '', array('class' => 'fas fa-filter mr-2')) .
    //         //         get_string('filter_options', 'local_form'),
    //         //     array('class' => 'filter-title')
    //         // );

    //         // $o .= html_writer::start_tag('form', array(
    //         //     'method' => 'get',
    //         //     'action' => new moodle_url('/local/form/nonregistered.php'),
    //         //     'class' => 'd-flex align-items-end gap-3'
    //         // ));
    //         // $o .= html_writer::empty_tag('input', array(
    //         //     'type' => 'hidden',
    //         //     'name' => 'token',
    //         //     'value' => $token
    //         // ));
    //         // $o .= html_writer::empty_tag('input', array(
    //         //     'type' => 'hidden',
    //         //     'name' => 'formid',
    //         //     'value' => $formid
    //         // ));

    //         $cohort_options = array(0 => get_string('all_cohorts', 'local_form'));
    //         foreach ($cohorts as $cohort) {
    //             $cohort_options[$cohort->id] = $cohort->name . " (ID: {$cohort->id})";
    //         }

    //         // $o .= html_writer::start_tag('div', array('class' => 'flex-grow-1'));
    //         // $o .= html_writer::tag(
    //         //     'label',
    //         //     get_string('filter_by_cohort', 'local_form'),
    //         //     array('for' => 'cohort', 'class' => 'form-label font-weight-bold')
    //         // );
    //         // $o .= html_writer::select(
    //         //     $cohort_options,
    //         //     'cohort',
    //         //     $cohortid,
    //         //     false,
    //         //     array('class' => 'form-control', 'id' => 'cohort')
    //         // );
    //         // $o .= html_writer::end_tag('div');

    //         // $o .= html_writer::tag(
    //         //     'button',
    //         //     html_writer::tag('i', '', array('class' => 'fas fa-check mr-2')) .
    //         //         get_string('filter', 'local_form'),
    //         //     array('type' => 'submit', 'class' => 'action-btn', 'style' => 'background: #294b6a; color: white;')
    //         // );
    //         $o .= html_writer::end_tag('form');
    //         // $o .= html_writer::end_tag('div');
    //     }

    //     // Create table
    //     if ($nonregistered_students) {
    //         $table = new html_table();
    //         $table->attributes['class'] = 'table data-table-professional';
    //         $table->id = 'nonregistered_table';

    //         $table->head = array(
    //             get_string('user_id', 'local_form'),
    //             get_string('username', 'local_form'),
    //             get_string('firstname', 'local_form'),
    //             get_string('lastname', 'local_form'),
    //             get_string('email', 'local_form'),
    //             get_string('contact_no', 'local_form'),
    //             get_string('cohort', 'local_form'),
    //             get_string('last_access', 'local_form'),
    //             get_string('actions', 'local_form')
    //         );

    //         foreach ($nonregistered_students as $student) {
    //             $lastaccess = $student->lastaccess ?
    //                 userdate($student->lastaccess, get_string('strftimedatetimeshort')) :
    //                 get_string('never', 'local_form');

    //             // Actions column
    //             $profile_url = new moodle_url('/user/profile.php', array('id' => $student->user_id));
    //             $actions = html_writer::link(
    //                 $profile_url,
    //                 html_writer::tag('i', '', array('class' => 'fas fa-eye')),
    //                 array(
    //                     'title' => get_string('view_profile', 'local_form'),
    //                     'target' => '_blank',
    //                     'class' => 'btn btn-sm btn-outline-primary',
    //                     'style' => 'border-radius: 4px;'
    //                 )
    //             );

    //             $row = array(
    //                 $student->user_id,
    //                 $student->username,
    //                 html_writer::tag('span', $student->firstname, array('class' => 'student-name-cell')),
    //                 html_writer::tag('span', $student->lastname, array('class' => 'student-name-cell')),
    //                 html_writer::link("mailto:{$student->email}", $student->email, array('class' => 'text-primary')),
    //                 html_writer::tag('span', $student->phone1, array('class' => 'contact-no-cell')),
    //                 html_writer::tag('span', $student->cohort_name, array('class' => 'cohort-badge')),
    //                 html_writer::tag('small', $lastaccess, array('class' => 'text-muted')),
    //                 $actions
    //             );

    //             $table->data[] = $row;
    //         }

    //         $o .= html_writer::table($table);

    //         // Pagination
    //         if ($total_count > $perpage) {
    //             $paging_url = new moodle_url('/local/form/nonregistered.php', array(
    //                 'token' => $token,
    //                 'formid' => $formid,
    //                 'cohortid' => $cohortid,
    //                 'perpage' => $perpage
    //             ));

    //             $o .= html_writer::start_tag('div', array('class' => 'pagination-section'));
    //             $o .= $OUTPUT->paging_bar($total_count, $page, $perpage, $paging_url);
    //             $o .= html_writer::end_tag('div');
    //         }

    //         // SINGLE DOWNLOAD DATAFORMAT SELECTOR - KEPT AS IS, JUST BETTER STYLING
    //         $download_params = array(
    //             'formid' => $formid,
    //             'cohort' => $cohortid,
    //             'token' => $token,
    //             // 'page' => $page,
    //             // 'perpage' => $perpage
    //         );

    //         $o .= html_writer::start_tag('div', array('class' => 'download-section'));
    //         $o .= html_writer::tag(
    //             'div',
    //             html_writer::tag('i', '', array('class' => 'fas fa-download mr-2')) .
    //                 get_string('download_nonregistered', 'local_form'),
    //             array('class' => 'download-title')
    //         );
    //         $o .= html_writer::tag('p', get_string('download_instructions', 'local_form'), array('class' => 'text-muted mb-3'));

    //         $o .= $OUTPUT->download_dataformat_selector(
    //             get_string('download_nonregistered', 'local_form'),
    //             'export_nonregistered.php',
    //             'dataformat',
    //             $download_params
    //         );
    //         $o .= html_writer::end_tag('div');
    //     } else {
    //         $o .= html_writer::start_tag('div', array('class' => 'alert alert-success text-center py-5'));
    //         $o .= html_writer::tag('i', '', array('class' => 'fas fa-check-circle fa-3x mb-3', 'style' => 'color: #28a745;'));
    //         $o .= html_writer::tag('h4', get_string('no_nonregistered_students', 'local_form'), array('class' => 'mb-3'));
    //         $o .= html_writer::tag('p', get_string('all_students_registered', 'local_form'), array('class' => 'text-muted'));
    //         $o .= html_writer::end_tag('div');
    //     }

    //     return $o;
    // }     

    //     public function local_nonregistered_students($formid, $token = '', $page = 0, $perpage = 30, $cohortid = 0)
    // {
    //     global $DB, $OUTPUT, $CFG, $USER;

    //     $o = '';

    //     // Process token
    //     if (empty($token)) {
    //         $token = local_form_generate_signed_url($formid, 'nonregistered');
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     } else {
    //         if (strpos($token, 'token=') !== false) {
    //             $parts = parse_url($token);
    //             parse_str($parts['query'] ?? '', $query);
    //             $token = $query['token'] ?? '';
    //         }
    //     }

    //     // Build the SQL query for non-registered students
    //     $params = ['formid' => $formid];
    //     $cohort_condition = '';

    //     if ($cohortid > 0) {
    //         $cohort_condition = "AND c.id = :cohortid";
    //         $params['cohortid'] = $cohortid;
    //     }

    //     $sql = "
    //     SELECT 
    //         u.id AS user_id,
    //         u.username,
    //         u.firstname,
    //         u.lastname,
    //         u.email,
    //         u.idnumber,
    //         u.phone1,
    //         u.lastaccess,
    //         c.name AS cohort_name,
    //         c.id AS cohort_id
    //     FROM 
    //         {user} u
    //     INNER JOIN 
    //         {cohort_members} cm ON u.id = cm.userid
    //     INNER JOIN 
    //         {cohort} c ON cm.cohortid = c.id
    //     LEFT JOIN 
    //         {form_submissions} fs ON u.id = fs.uid 
    //         AND fs.formid = :formid
    //         AND fs.visible = 1
    //     WHERE 
    //         fs.id IS NULL
    //         $cohort_condition
    //         AND u.deleted = 0
    //         AND u.suspended = 0
    //     ";

    //     // Count query
    //     $count_sql = "
    //     SELECT COUNT(*)
    //     FROM (
    //         SELECT DISTINCT u.id
    //         FROM 
    //             {user} u
    //         INNER JOIN 
    //             {cohort_members} cm ON u.id = cm.userid
    //         INNER JOIN 
    //             {cohort} c ON cm.cohortid = c.id
    //         LEFT JOIN 
    //             {form_submissions} fs ON u.id = fs.uid 
    //             AND fs.formid = :formid
    //             AND fs.visible = 1
    //         WHERE 
    //             fs.id IS NULL
    //             $cohort_condition
    //             AND u.deleted = 0
    //             AND u.suspended = 0
    //     ) AS temp
    //     ";

    //     $total_count = $DB->count_records_sql($count_sql, $params);

    //     // Add ordering and pagination to main query
    //     $sql .= " ORDER BY u.lastname, u.firstname";

    //     // Get data with pagination
    //     $nonregistered_students = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    //     // Display total count with professional badge
    //     $o .= html_writer::start_tag('div', array('class' => 'd-flex justify-content-between align-items-center mb-4'));
    //     $o .= html_writer::tag('h4', get_string('nonregistered_students', 'local_form'));
    //     $o .= html_writer::tag(
    //         'span',
    //         html_writer::tag('i', '', array('class' => 'fas fa-users mr-2')) .
    //             get_string('total_students', 'local_form') . ': ' . $total_count,
    //         array('class' => 'count-badge')
    //     );
    //     $o .= html_writer::end_tag('div');

    //     // Add notification/reminder button at the top
    //     if ($nonregistered_students) {
    //         $notify_params = array(
    //             'formid' => $formid,
    //             'cohortid' => $cohortid,
    //             'token' => $token,
    //             'action' => 'notify'
    //         );

    //         $notify_url = new moodle_url('/local/form/notify_nonregistered.php', $notify_params);

    //         $o .= html_writer::start_tag('div', array('class' => 'reminder-alert-professional'));
    //         $o .= html_writer::tag(
    //             'div',
    //             html_writer::tag('i', '', array('class' => 'fas fa-bell mr-2')) .
    //                 get_string('send_reminders', 'local_form'),
    //             array('class' => 'reminder-title')
    //         );
    //         $o .= html_writer::tag('p', get_string('reminder_instructions', 'local_form'), array('class' => 'reminder-text mb-3'));

    //         $o .= html_writer::link(
    //             $notify_url,
    //             html_writer::tag('i', '', array('class' => 'fas fa-paper-plane mr-2')) .
    //                 get_string('send_reminders_button', 'local_form'),
    //             array('class' => 'action-btn reminder-btn')
    //         );
    //         $o .= html_writer::end_tag('div');
    //     }

    //     // Cohort filter form
    //     $cohorts = $DB->get_records_sql("
    //                 SELECT DISTINCT c.id, c.name, c.idnumber
    //                 FROM {cohort} c
    //                 INNER JOIN {cohort_members} cm ON c.id = cm.cohortid
    //                 WHERE c.visible = 1
    //                 ORDER BY c.name
    //                   ");

    //     if ($cohorts) {
    //         // $o .= html_writer::start_tag('div', array('class' => 'filter-section-professional'));
    //         // $o .= html_writer::tag(
    //         //     'div',
    //         //     html_writer::tag('i', '', array('class' => 'fas fa-filter mr-2')) .
    //         //         get_string('filter_options', 'local_form'),
    //         //     array('class' => 'filter-title')
    //         // );

    //         // $o .= html_writer::start_tag('form', array(
    //         //     'method' => 'get',
    //         //     'action' => new moodle_url('/local/form/nonregistered.php'),
    //         //     'class' => 'd-flex align-items-end gap-3'
    //         // ));
    //         // $o .= html_writer::empty_tag('input', array(
    //         //     'type' => 'hidden',
    //         //     'name' => 'token',
    //         //     'value' => $token
    //         // ));
    //         // $o .= html_writer::empty_tag('input', array(
    //         //     'type' => 'hidden',
    //         //     'name' => 'formid',
    //         //     'value' => $formid
    //         // ));

    //         $cohort_options = array(0 => get_string('all_cohorts', 'local_form'));
    //         foreach ($cohorts as $cohort) {
    //             $cohort_options[$cohort->id] = $cohort->name . " (ID: {$cohort->id})";
    //         }

    //         // $o .= html_writer::start_tag('div', array('class' => 'flex-grow-1'));
    //         // $o .= html_writer::tag(
    //         //     'label',
    //         //     get_string('filter_by_cohort', 'local_form'),
    //         //     array('for' => 'cohort', 'class' => 'form-label font-weight-bold')
    //         // );
    //         // $o .= html_writer::select(
    //         //     $cohort_options,
    //         //     'cohort',
    //         //     $cohortid,
    //         //     false,
    //         //     array('class' => 'form-control', 'id' => 'cohort')
    //         // );
    //         // $o .= html_writer::end_tag('div');

    //         // $o .= html_writer::tag(
    //         //     'button',
    //         //     html_writer::tag('i', '', array('class' => 'fas fa-check mr-2')) .
    //         //         get_string('filter', 'local_form'),
    //         //     array('type' => 'submit', 'class' => 'action-btn', 'style' => 'background: #294b6a; color: white;')
    //         // );
    //         $o .= html_writer::end_tag('form');
    //         // $o .= html_writer::end_tag('div');
    //     }

    //     // Create table
    //     if ($nonregistered_students) {
    //         // Add JavaScript for checkbox functionality
    //         $o .= '
    //         <script>
    //         function toggleAll(source) {
    //             const checkboxes = document.querySelectorAll(\'.row-checkbox\');
    //             checkboxes.forEach(cb => {
    //                 cb.checked = source.checked;
    //                 updateRowStyle(cb);
    //             });
    //         }

    //         function updateParentCheckbox() {
    //             const all = document.querySelectorAll(\'.row-checkbox\');
    //             const checked = document.querySelectorAll(\'.row-checkbox:checked\');
    //             document.getElementById(\'selectAll\').checked = (all.length === checked.length);
    //         }

    //         function updateRowStyle(checkbox) {
    //             const row = checkbox.closest(\'tr\');
    //             if (checkbox.checked) {
    //                 row.classList.add(\'row-selected\');
    //             } else {
    //                 row.classList.remove(\'row-selected\');
    //             }
    //         }

    //         // Initialize row styles on page load
    //         document.addEventListener(\'DOMContentLoaded\', function() {
    //             const checkboxes = document.querySelectorAll(\'.row-checkbox\');
    //             checkboxes.forEach(cb => {
    //                 updateRowStyle(cb);
    //                 cb.addEventListener(\'change\', function() {
    //                     updateRowStyle(this);
    //                     updateParentCheckbox();
    //                 });
    //             });
    //         });
    //         </script>';

    //         $table = new html_table();
    //         $table->attributes['class'] = 'table data-table-professional';
    //         $table->id = 'nonregistered_table';

    //         // Add checkbox column header
    //         $table->head = array(
    //             html_writer::tag('input', '', array(
    //                 'type' => 'checkbox',
    //                 'id' => 'selectAll',
    //                 'onclick' => 'toggleAll(this)',
    //                 'class' => 'master-checkbox'
    //             )),
    //             get_string('user_id', 'local_form'),
    //             get_string('username', 'local_form'),
    //             get_string('firstname', 'local_form'),
    //             get_string('lastname', 'local_form'),
    //             get_string('email', 'local_form'),
    //             get_string('contact_no', 'local_form'),
    //             get_string('cohort', 'local_form'),
    //             get_string('last_access', 'local_form'),
    //             get_string('actions', 'local_form')
    //         );

    //         foreach ($nonregistered_students as $student) {
    //             $lastaccess = $student->lastaccess ?
    //                 userdate($student->lastaccess, get_string('strftimedatetimeshort')) :
    //                 get_string('never', 'local_form');

    //             // Actions column
    //             $profile_url = new moodle_url('/user/profile.php', array('id' => $student->user_id));
    //             $actions = html_writer::link(
    //                 $profile_url,
    //                 html_writer::tag('i', '', array('class' => 'fas fa-eye')),
    //                 array(
    //                     'title' => get_string('view_profile', 'local_form'),
    //                     'target' => '_blank',
    //                     'class' => 'btn btn-sm btn-outline-primary',
    //                     'style' => 'border-radius: 4px;'
    //                 )
    //             );

    //             $row = array(
    //                 html_writer::tag('input', '', array(
    //                     'type' => 'checkbox',
    //                     'class' => 'row-checkbox individual-checkbox',
    //                     'value' => $student->user_id,
    //                     'onclick' => 'updateParentCheckbox()'
    //                 )),
    //                 $student->user_id,
    //                 $student->username,
    //                 html_writer::tag('span', $student->firstname, array('class' => 'student-name-cell')),
    //                 html_writer::tag('span', $student->lastname, array('class' => 'student-name-cell')),
    //                 html_writer::link("mailto:{$student->email}", $student->email, array('class' => 'text-primary')),
    //                 html_writer::tag('span', $student->phone1, array('class' => 'contact-no-cell')),
    //                 html_writer::tag('span', $student->cohort_name, array('class' => 'cohort-badge')),
    //                 html_writer::tag('small', $lastaccess, array('class' => 'text-muted')),
    //                 $actions
    //             );

    //             $table->data[] = $row;
    //         }

    //         $o .= html_writer::table($table);

    //         // Pagination
    //         if ($total_count > $perpage) {
    //             $paging_url = new moodle_url('/local/form/nonregistered.php', array(
    //                 'token' => $token,
    //                 'formid' => $formid,
    //                 'cohortid' => $cohortid,
    //                 'perpage' => $perpage
    //             ));

    //             $o .= html_writer::start_tag('div', array('class' => 'pagination-section'));
    //             $o .= $OUTPUT->paging_bar($total_count, $page, $perpage, $paging_url);
    //             $o .= html_writer::end_tag('div');
    //         }

    //         // SINGLE DOWNLOAD DATAFORMAT SELECTOR - KEPT AS IS, JUST BETTER STYLING
    //         $download_params = array(
    //             'formid' => $formid,
    //             'cohort' => $cohortid,
    //             'token' => $token,
    //             // 'page' => $page,
    //             // 'perpage' => $perpage
    //         );

    //         $o .= html_writer::start_tag('div', array('class' => 'download-section'));
    //         $o .= html_writer::tag(
    //             'div',
    //             html_writer::tag('i', '', array('class' => 'fas fa-download mr-2')) .
    //                 get_string('download_nonregistered', 'local_form'),
    //             array('class' => 'download-title')
    //         );
    //         $o .= html_writer::tag('p', get_string('download_instructions', 'local_form'), array('class' => 'text-muted mb-3'));

    //         $o .= $OUTPUT->download_dataformat_selector(
    //             get_string('download_nonregistered', 'local_form'),
    //             'export_nonregistered.php',
    //             'dataformat',
    //             $download_params
    //         );
    //         $o .= html_writer::end_tag('div');
    //     } else {
    //         $o .= html_writer::start_tag('div', array('class' => 'alert alert-success text-center py-5'));
    //         $o .= html_writer::tag('i', '', array('class' => 'fas fa-check-circle fa-3x mb-3', 'style' => 'color: #28a745;'));
    //         $o .= html_writer::tag('h4', get_string('no_nonregistered_students', 'local_form'), array('class' => 'mb-3'));
    //         $o .= html_writer::tag('p', get_string('all_students_registered', 'local_form'), array('class' => 'text-muted'));
    //         $o .= html_writer::end_tag('div');
    //     }

    //     return $o;
    // }         

    public function local_nonregistered_students($formid, $token = '', $page = 0, $perpage = 30, $cohortid = 0)
    {
        global $DB, $OUTPUT, $CFG, $USER;

        $o = '';

        // Process token
        if (empty($token)) {
            $token = local_form_generate_signed_url($formid, 'nonregistered');
            if (strpos($token, 'token=') !== false) {
                $parts = parse_url($token);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        } else {
            if (strpos($token, 'token=') !== false) {
                $parts = parse_url($token);
                parse_str($parts['query'] ?? '', $query);
                $token = $query['token'] ?? '';
            }
        }

        // Build the SQL query for non-registered students
        $params = ['formid' => $formid];
        $cohort_condition = '';

        if ($cohortid > 0) {
            $cohort_condition = "AND c.id = :cohortid";
            $params['cohortid'] = $cohortid;
        }

        $sql = "
    SELECT 
        u.id AS user_id,
        u.username,
        u.firstname,
        u.lastname,
        u.email,
        u.idnumber,
        u.phone1,
        u.lastaccess,
        c.name AS cohort_name,
        c.id AS cohort_id
    FROM 
        {user} u
    INNER JOIN 
        {cohort_members} cm ON u.id = cm.userid
    INNER JOIN 
        {cohort} c ON cm.cohortid = c.id
    LEFT JOIN 
        {form_submissions} fs ON u.id = fs.uid 
        AND fs.formid = :formid
        AND fs.visible = 1
    WHERE 
        fs.id IS NULL
        $cohort_condition
        AND u.deleted = 0
        AND u.suspended = 0
    ";

        // Count query
        $count_sql = "
    SELECT COUNT(*)
    FROM (
        SELECT DISTINCT u.id
        FROM 
            {user} u
        INNER JOIN 
            {cohort_members} cm ON u.id = cm.userid
        INNER JOIN 
            {cohort} c ON cm.cohortid = c.id
        LEFT JOIN 
            {form_submissions} fs ON u.id = fs.uid 
            AND fs.formid = :formid
            AND fs.visible = 1
        WHERE 
            fs.id IS NULL
            $cohort_condition
            AND u.deleted = 0
            AND u.suspended = 0
    ) AS temp
    ";

        $total_count = $DB->count_records_sql($count_sql, $params);

        // Add ordering and pagination to main query
        $sql .= " ORDER BY u.lastname, u.firstname";

        // Get data with pagination
        $nonregistered_students = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        // Display total count with professional badge
        $o .= html_writer::start_tag('div', array('class' => 'd-flex justify-content-between align-items-center mb-4'));
        $o .= html_writer::tag('h4', get_string('nonregistered_students', 'local_form'));
        $o .= html_writer::tag(
            'span',
            html_writer::tag('i', '', array('class' => 'fas fa-users mr-2')) .
                get_string('total_students', 'local_form') . ': ' . $total_count,
            array('class' => 'count-badge')
        );
        $o .= html_writer::end_tag('div');

        // Add notification/reminder button at the top
        if ($nonregistered_students) {
            $notify_params = array(
                'formid' => $formid,
                'cohortid' => $cohortid,
                'token' => $token,
                'action' => 'notify'
            );

            $notify_url = new moodle_url('/local/form/notify_nonregistered.php', $notify_params);

            $o .= html_writer::start_tag('div', array('class' => 'reminder-alert-professional'));
            $o .= html_writer::tag(
                'div',
                html_writer::tag('i', '', array('class' => 'fas fa-bell mr-2')) .
                    get_string('send_reminders', 'local_form'),
                array('class' => 'reminder-title')
            );
            $o .= html_writer::tag('p', get_string('reminder_instructions', 'local_form'), array('class' => 'reminder-text mb-3'));

            $o .= html_writer::link(
                $notify_url,
                html_writer::tag('i', '', array('class' => 'fas fa-paper-plane mr-2')) .
                    get_string('send_reminders_button', 'local_form'),
                array('class' => 'action-btn reminder-btn')
            );
            $o .= html_writer::end_tag('div');
        }

        // Cohort filter form
        $cohorts = $DB->get_records_sql("
                SELECT DISTINCT c.id, c.name, c.idnumber
                FROM {cohort} c
                INNER JOIN {cohort_members} cm ON c.id = cm.cohortid
                WHERE c.visible = 1
                ORDER BY c.name
                  ");

        if ($cohorts) {
            // $o .= html_writer::start_tag('div', array('class' => 'filter-section-professional'));
            // $o .= html_writer::tag(
            //     'div',
            //     html_writer::tag('i', '', array('class' => 'fas fa-filter mr-2')) .
            //         get_string('filter_options', 'local_form'),
            //     array('class' => 'filter-title')
            // );

            // $o .= html_writer::start_tag('form', array(
            //     'method' => 'get',
            //     'action' => new moodle_url('/local/form/nonregistered.php'),
            //     'class' => 'd-flex align-items-end gap-3'
            // ));
            // $o .= html_writer::empty_tag('input', array(
            //     'type' => 'hidden',
            //     'name' => 'token',
            //     'value' => $token
            // ));
            // $o .= html_writer::empty_tag('input', array(
            //     'type' => 'hidden',
            //     'name' => 'formid',
            //     'value' => $formid
            // ));

            $cohort_options = array(0 => get_string('all_cohorts', 'local_form'));
            foreach ($cohorts as $cohort) {
                $cohort_options[$cohort->id] = $cohort->name . " (ID: {$cohort->id})";
            }

            // $o .= html_writer::start_tag('div', array('class' => 'flex-grow-1'));
            // $o .= html_writer::tag(
            //     'label',
            //     get_string('filter_by_cohort', 'local_form'),
            //     array('for' => 'cohort', 'class' => 'form-label font-weight-bold')
            // );
            // $o .= html_writer::select(
            //     $cohort_options,
            //     'cohort',
            //     $cohortid,
            //     false,
            //     array('class' => 'form-control', 'id' => 'cohort')
            // );
            // $o .= html_writer::end_tag('div');

            // $o .= html_writer::tag(
            //     'button',
            //     html_writer::tag('i', '', array('class' => 'fas fa-check mr-2')) .
            //         get_string('filter', 'local_form'),
            //     array('type' => 'submit', 'class' => 'action-btn', 'style' => 'background: #294b6a; color: white;')
            // );
            $o .= html_writer::end_tag('form');
            // $o .= html_writer::end_tag('div');
        }

        // Create table
        if ($nonregistered_students) {
            // Add Bulk Action Section BEFORE the table
            $o .= html_writer::start_tag('div', array('class' => 'bulk-action-section'));
            $o .= html_writer::tag(
                'div',
                html_writer::tag('i', '', array('class' => 'fas fa-users-cog mr-2')) .
                    get_string('bulk_actions', 'local_form'),
                array('class' => 'bulk-action-title')
            );

            // Bulk action form
            $o .= html_writer::start_tag('form', array(
                'method' => 'post',
                'action' => new moodle_url('/local/form/bulk_remove_cohort.php'),
                'class' => 'bulk-action-form',
                'id' => 'bulkActionForm'
            ));

            // Add hidden inputs
            $o .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey()
            ));

            if (!empty($token)) {
                $o .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'token',
                    'value' => $token
                ));
            }

            if ($formid > 0) {
                $o .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'formid',
                    'value' => $formid
                ));
            }

            if ($cohortid > 0) {
                $o .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'cohortid',
                    'value' => $cohortid
                ));
            }

            // Hidden field for selected user IDs (will be populated by JavaScript)
            $o .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => 'userids',
                'id' => 'selectedUserIds',
                'value' => ''
            ));

            $o .= html_writer::start_tag('div', array('class' => 'd-flex align-items-center gap-3'));

            // Bulk action button
            $o .= html_writer::tag(
                'button',
                html_writer::tag('i', '', array('class' => 'fas fa-user-minus mr-2')) .
                    get_string('remove_selected_from_cohort', 'local_form'),
                array(
                    'type' => 'submit',
                    'class' => 'action-btn remove-btn',
                    'id' => 'bulkRemoveBtn',
                    'onclick' => 'return prepareBulkRemove();'
                )
            );

            // Selection info
            $o .= html_writer::tag(
                'span',
                '<span id="selectedCount">0</span> ' . get_string('users_selected', 'local_form'),
                array('class' => 'text-muted', 'id' => 'selectionInfo')
            );

            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('form');
            $o .= html_writer::end_tag('div');

            // Add JavaScript for checkbox functionality and bulk actions
            $o .= '
        <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll(\'.row-checkbox\');
            checkboxes.forEach(cb => {
                cb.checked = source.checked;
                updateRowStyle(cb);
            });
            updateSelectionInfo();
        }

        function updateParentCheckbox() {
            const all = document.querySelectorAll(\'.row-checkbox\');
            const checked = document.querySelectorAll(\'.row-checkbox:checked\');
            document.getElementById(\'selectAll\').checked = (all.length === checked.length);
            updateSelectionInfo();
        }

        function updateRowStyle(checkbox) {
            const row = checkbox.closest(\'tr\');
            if (checkbox.checked) {
                row.classList.add(\'row-selected\');
            } else {
                row.classList.remove(\'row-selected\');
            }
        }

        function updateSelectionInfo() {
            const checked = document.querySelectorAll(\'.row-checkbox:checked\');
            const selectedCount = document.getElementById(\'selectedCount\');
            const selectionInfo = document.getElementById(\'selectionInfo\');
            const bulkRemoveBtn = document.getElementById(\'bulkRemoveBtn\');
            
            if (selectedCount) {
                selectedCount.textContent = checked.length;
            }
            
            // Enable/disable bulk remove button based on selection
            if (bulkRemoveBtn) {
                bulkRemoveBtn.disabled = checked.length === 0;
                bulkRemoveBtn.style.opacity = checked.length === 0 ? \'0.6\' : \'1\';
            }
            
            // Update selection info styling
            if (selectionInfo) {
                if (checked.length > 0) {
                    selectionInfo.classList.remove(\'text-muted\');
                    selectionInfo.classList.add(\'text-primary\', \'font-weight-bold\');
                } else {
                    selectionInfo.classList.remove(\'text-primary\', \'font-weight-bold\');
                    selectionInfo.classList.add(\'text-muted\');
                }
            }
        }

        function prepareBulkRemove() {
            const checked = document.querySelectorAll(\'.row-checkbox:checked\');
            if (checked.length === 0) {
                alert(\'' . get_string('no_users_selected', 'local_form') . '\');
                return false;
            }
            
            // Confirm action
            const confirmation = confirm(\'' . get_string('confirm_remove_from_cohort', 'local_form') . '\');
            if (!confirmation) {
                return false;
            }
            
            // Collect selected user IDs
            const userIds = [];
            checked.forEach(cb => {
                userIds.push(cb.value);
            });
            
            // Set the hidden field value
            document.getElementById(\'selectedUserIds\').value = userIds.join(\',\');
            
            return true;
        }

        // Initialize on page load
        document.addEventListener(\'DOMContentLoaded\', function() {
            const checkboxes = document.querySelectorAll(\'.row-checkbox\');
            checkboxes.forEach(cb => {
                updateRowStyle(cb);
                cb.addEventListener(\'change\', function() {
                    updateRowStyle(this);
                    updateParentCheckbox();
                });
            });
            
            updateSelectionInfo();
            
            // Add row click to toggle checkbox
            const tableRows = document.querySelectorAll(\'#nonregistered_table tbody tr\');
            tableRows.forEach(row => {
                row.addEventListener(\'click\', function(e) {
                    // Don\'t toggle if clicking on checkbox or action button
                    if (e.target.type === \'checkbox\' || 
                        e.target.closest(\'a\') || 
                        e.target.closest(\'button\')) {
                        return;
                    }
                    
                    const checkbox = this.querySelector(\'.row-checkbox\');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateRowStyle(checkbox);
                        updateParentCheckbox();
                    }
                });
            });
        });
        </script>';

            $table = new html_table();
            $table->attributes['class'] = 'table data-table-professional';
            $table->id = 'nonregistered_table';

            // Add checkbox column header
            $table->head = array(
                html_writer::tag('input', '', array(
                    'type' => 'checkbox',
                    'id' => 'selectAll',
                    'onclick' => 'toggleAll(this)',
                    'class' => 'master-checkbox'
                )),
                get_string('user_id', 'local_form'),
                get_string('username', 'local_form'),
                get_string('firstname', 'local_form'),
                get_string('lastname', 'local_form'),
                get_string('email', 'local_form'),
                get_string('contact_no', 'local_form'),
                get_string('cohort', 'local_form'),
                get_string('last_access', 'local_form'),
                get_string('actions', 'local_form')
            );

            foreach ($nonregistered_students as $student) {
                $lastaccess = $student->lastaccess ?
                    userdate($student->lastaccess, get_string('strftimedatetimeshort')) :
                    get_string('never', 'local_form');

                // Actions column
                $profile_url = new moodle_url('/user/profile.php', array('id' => $student->user_id));
                $actions = html_writer::link(
                    $profile_url,
                    html_writer::tag('i', '', array('class' => 'fas fa-eye')),
                    array(
                        'title' => get_string('view_profile', 'local_form'),
                        'target' => '_blank',
                        'class' => 'btn btn-sm btn-outline-primary',
                        'style' => 'border-radius: 4px;'
                    )
                );

                $row = array(
                    html_writer::tag('input', '', array(
                        'type' => 'checkbox',
                        'class' => 'row-checkbox individual-checkbox',
                        'value' => $student->user_id,
                        'onclick' => 'updateParentCheckbox()'
                    )),
                    $student->user_id,
                    $student->username,
                    html_writer::tag('span', $student->firstname, array('class' => 'student-name-cell')),
                    html_writer::tag('span', $student->lastname, array('class' => 'student-name-cell')),
                    html_writer::link("mailto:{$student->email}", $student->email, array('class' => 'text-primary')),
                    html_writer::tag('span', $student->phone1, array('class' => 'contact-no-cell')),
                    html_writer::tag('span', $student->cohort_name, array('class' => 'cohort-badge')),
                    html_writer::tag('small', $lastaccess, array('class' => 'text-muted')),
                    $actions
                );

                $table->data[] = $row;
            }

            $o .= html_writer::table($table);

            // Pagination
            if ($total_count > $perpage) {
                $paging_url = new moodle_url('/local/form/nonregistered.php', array(
                    'token' => $token,
                    'formid' => $formid,
                    'cohortid' => $cohortid,
                    'perpage' => $perpage
                ));

                $o .= html_writer::start_tag('div', array('class' => 'pagination-section'));
                $o .= $OUTPUT->paging_bar($total_count, $page, $perpage, $paging_url);
                $o .= html_writer::end_tag('div');
            }

            // SINGLE DOWNLOAD DATAFORMAT SELECTOR - KEPT AS IS, JUST BETTER STYLING
            $download_params = array(
                'formid' => $formid,
                'cohort' => $cohortid,
                'token' => $token,
                // 'page' => $page,
                // 'perpage' => $perpage
            );

            $o .= html_writer::start_tag('div', array('class' => 'download-section'));
            $o .= html_writer::tag(
                'div',
                html_writer::tag('i', '', array('class' => 'fas fa-download mr-2')) .
                    get_string('download_nonregistered', 'local_form'),
                array('class' => 'download-title')
            );
            $o .= html_writer::tag('p', get_string('download_instructions', 'local_form'), array('class' => 'text-muted mb-3'));

            $o .= $OUTPUT->download_dataformat_selector(
                get_string('download_nonregistered', 'local_form'),
                'export_nonregistered.php',
                'dataformat',
                $download_params
            );
            $o .= html_writer::end_tag('div');
        } else {
            $o .= html_writer::start_tag('div', array('class' => 'alert alert-success text-center py-5'));
            $o .= html_writer::tag('i', '', array('class' => 'fas fa-check-circle fa-3x mb-3', 'style' => 'color: #28a745;'));
            $o .= html_writer::tag('h4', get_string('no_nonregistered_students', 'local_form'), array('class' => 'mb-3'));
            $o .= html_writer::tag('p', get_string('all_students_registered', 'local_form'), array('class' => 'text-muted'));
            $o .= html_writer::end_tag('div');
        }

        return $o;
    }


    // Report renderer for Manage Forms page
    public function local_manageforms_report($records, $recordcount, $page, $perpage, $status)
{
    global $DB, $OUTPUT;

    $o = '';

    if (!$records) {
        return html_writer::tag('div', 'No records found', [
            'class' => 'alert alert-warning text-center'
        ]);
    }

    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->head = [
        '#',
        'Form Name',
        'Description',
        'Submissions',
        'Created On'
    ];

    $i = ($page * $perpage) + 1;

    foreach ($records as $record) {

        // Reset cohort per row (IMPORTANT)
        $cohort = null;
        $cohort_info = '-';

        if (!empty($record->shortname)) {
            $cohort = $DB->get_record('cohort', ['name' => $record->shortname]);

            if ($cohort) {
                $members = $DB->count_records('cohort_members', [
                    'cohortid' => $cohort->id
                ]);

                $cohort_info =
                    html_writer::tag('span', $cohort->name, [
                        'class' => 'badge badge-success'
                    ]) .
                    html_writer::tag(
                        'small',
                        " Members: {$members}",
                        ['class' => 'text-muted ml-1']
                    );
            } else {
                $cohort_info = html_writer::tag(
                    'span',
                    'No linked cohort',
                    ['class' => 'badge badge-secondary']
                );
            }
        } else {
            $cohort_info = html_writer::tag(
                'span',
                'No shortname',
                ['class' => 'badge badge-warning']
            );
        }

        // Submission link
        $params = ['formid' => $record->id];
        if ($cohort) {
            $params['cohortid'] = $cohort->id;
            $params['autofilter'] = 1;
        }

        $submissions = html_writer::link(
            new moodle_url('/local/form/courselist.php', $params),
            'View Submissions',
            ['class' => 'btn btn-outline-info btn-sm']
        );

        $table->data[] = [
            $i++,
            html_writer::tag('strong', $record->name),
            html_writer::tag(
                'div',
                $record->description,
                ['class' => 'text-muted small']
            ),
            $submissions,
            userdate($record->timecreated)
        ];
    }

    $o .= html_writer::table($table);

    // Pagination (keep active/inactive state)
    $baseurl = new moodle_url('/local/form/form_report.php', [
        'status' => $status
    ]);

    $o .= html_writer::div(
        $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl),
        'text-center mt-4'
    );

    return $o;
}


}

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


   

    // public function local_allcourselist($records = null, $recordcount = null, $page = 0, $perpage = 10, $formid = 0, $token = '', $cohortid = 0)
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

    //     // Build SQL queries based on cohort filter
    //     $sql_params = ['formid' => $formid, 'visible' => 1];
    //     $sql_joins = '';
    //     $sql_where = 'fs.formid = :formid AND fs.visible = :visible';

    //     if ($cohortid > 0) {
    //         $sql_joins = 'INNER JOIN {cohort_members} cm ON fs.uid = cm.userid';
    //         $sql_where .= ' AND cm.cohortid = :cohortid';
    //         $sql_params['cohortid'] = $cohortid;
    //     }

    //     // Count total DISTINCT users with cohort filter
    //     $count_sql = "SELECT COUNT(DISTINCT fs.uid)
    //               FROM {form_submissions} fs 
    //               $sql_joins 
    //               WHERE $sql_where";

    //     $total_users = $DB->count_records_sql($count_sql, $sql_params);

    //     // Fetch DISTINCT user IDs with pagination using get_records_sql's built-in pagination
    //     $user_ids_sql = "SELECT DISTINCT fs.uid
    //                  FROM {form_submissions} fs 
    //                  $sql_joins 
    //                  WHERE $sql_where
    //                  ORDER BY fs.uid";

    //     // Use get_records_sql with limitfrom and limitnum parameters
    //     $paged_user_records = $DB->get_records_sql(
    //         $user_ids_sql,
    //         $sql_params,
    //         $page * $perpage,  // limitfrom
    //         $perpage           // limitnum
    //     );

    //     $paged_user_ids = array_keys($paged_user_records);

    //     if (empty($paged_user_ids)) {
    //         // Return empty table message
    //         $o .= html_writer::tag('div', 'No records found', ['class' => 'alert alert-info']);

    //         // Add the "Total Registered Students" text with cohort info
    //         $total_text = 'Total Registered Students: ' . $total_users;
    //         if ($cohortid > 0) {
    //             $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
    //             if ($cohort) {
    //                 $total_text .= ' (Filtered by cohort: ' . $cohort->name . ')';
    //             }
    //         }
    //         $o .= html_writer::tag('h3', $total_text);

    //         return $o;
    //     }

    //     // IMPORTANT: Convert array to comma-separated string for IN clause
    //     // But first check if we have any IDs
    //     if (empty($paged_user_ids)) {
    //         $o .= html_writer::tag('div', 'No records found', ['class' => 'alert alert-info']);
    //         return $o;
    //     }

    //     // Create placeholders for the IN clause
    //     $placeholders = [];
    //     $data_params = ['formid' => $formid, 'visible' => 1];
    //     $i = 0;

    //     foreach ($paged_user_ids as $uid) {
    //         $param_name = 'uid' . $i;
    //         $placeholders[] = ':' . $param_name;
    //         $data_params[$param_name] = $uid;
    //         $i++;
    //     }

    //     $in_clause = implode(', ', $placeholders);

    //     // Fetch all data for the paginated users
    //     $data_sql = "SELECT fs.* 
    //              FROM {form_submissions} fs 
    //              WHERE fs.formid = :formid 
    //                AND fs.visible = :visible 
    //                AND fs.uid IN ($in_clause)
    //              ORDER BY fs.uid";

    //     $all_records = $DB->get_records_sql($data_sql, $data_params);

    //     // Group records by user ID and field names
    //     $fields = [];
    //     $users = [];

    //     foreach ($all_records as $record) {
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
    //     $headers = array_merge(['Select', 'UID', 'Username', 'Password' ,'Full Name', 'Email'], $fields);

    //     // Add cohort column if not filtering by specific cohort
    //     if ($cohortid == 0) {
    //         array_splice($headers, 5, 0, ['Cohort(s)']); // Insert after Email
    //     }

    //     // $headers[] = 'View';
    //     // $headers[] = 'Download PDF';

    //     // Initialize the HTML table
    //     $table = new html_table();
    //     $table->attributes['class'] = 'table table-striped table-hover';
    //     $table->id = 'dynamic_table';
    //     $table->head = $headers;

    //     // Add rows to the table for paginated users
    //     foreach ($paged_user_ids as $uid) {
    //         $user_data = $users[$uid];
    //         // Fetch and decrypt the password
    //         $encrypted_password = $DB->get_field('local_user', 'password', ['userid' => $uid]);

    //         // Get user details
    //         $user = $DB->get_record('user', ['id' => $uid], 'username, firstname, lastname, email');
    //         $username = $user ? $user->username : 'N/A';
    //         $fullname = $user ? fullname($user) : 'N/A';
    //         $email = $user ? $user->email : 'N/A';

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
    //         $row[] = $fullname;
    //         $row[] = $email;

    //         // Add cohort column if not filtering by specific cohort
    //         if ($cohortid == 0) {
    //             // Get user's cohorts
    //             $user_cohorts = $DB->get_records_sql(
    //                 "SELECT c.id, c.name 
    //              FROM {cohort} c
    //              JOIN {cohort_members} cm ON c.id = cm.cohortid
    //              WHERE cm.userid = :userid",
    //                 ['userid' => $uid]
    //             );

    //             $cohort_names = [];
    //             foreach ($user_cohorts as $cohort) {
    //                 $cohort_names[] = $cohort->name;
    //             }
    //             $row[] = !empty($cohort_names) ? implode(', ', $cohort_names) : 'None';
    //         }

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
    //         // $row[] = $view_link;

    //         // Download PDF link - Generate signed URL for downloadpdf.php
    //         $pdf_url = local_form_generate_signed_url($formid, 'downloadpdf', ['uid' => $uid]);
    //         $pdf_link = html_writer::link($pdf_url, 'Download');
    //         // $row[] = $pdf_link;

    //         // Add the row to the table
    //         $table->data[] = $row;
    //     }

    //     // Add the "Total Registered Students" text with cohort info
    //     $total_text = 'Total Registered Students: ' . $total_users;
    //     if ($cohortid > 0) {
    //         $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
    //         if ($cohort) {
    //             $total_text .= ' (Filtered by cohort: ' . $cohort->name . ')';
    //         }
    //     }
    //     $o .= html_writer::tag('h3', $total_text);

    //     // Output the table
    //     $o .= html_writer::table($table);

    //     // Pagination - include cohortid if filtering
    //     if ($total_users > $perpage) {
    //         // Build pagination parameters
    //         $paging_params = [];
    //         if (!empty($token)) {
    //             $paging_params['token'] = $token;
    //         } else {
    //             $paging_params['formid'] = $formid;
    //         }

    //         if ($cohortid > 0) {
    //             $paging_params['cohortid'] = $cohortid;
    //         }

    //         // Create pagination URL
    //         $paging_url = new moodle_url('/local/form/courselist.php', $paging_params);

    //         // Generate pagination bar
    //         $paging_bar = $OUTPUT->paging_bar($total_users, $page, $perpage, $paging_url);
    //         $o .= $paging_bar;
    //     }

    //     // Download dataformat selector
    //     $download_params = [
    //         'formid' => $formid,
    //         'page' => $page,
    //         'visible' => 1,
    //         'token' => $token
    //     ];

    //     if ($cohortid > 0) {
    //         $download_params['cohortid'] = $cohortid;
    //     }

    //     $o .= $OUTPUT->download_dataformat_selector(
    //         get_string('download', 'local_form'),
    //         'export.php',
    //         'dataformat',
    //         $download_params
    //     );

    //     return $o;
    // }

   public function local_allcourselist($records = null, $recordcount = null, $page = 0, $perpage = 10, $formid = 0, $token = '', $cohortid = 0, $searchkeyword = '', $confirmfilter = '' )
{
    $o = '';
    global $DB, $OUTPUT, $USER;

    // Token handling
    if (empty($token)) {
        $token = local_form_generate_signed_url($formid, 'courselist');
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

    // ================= SQL Building =================
    $sql_params = [
        'formid' => $formid,
        'visible' => 1
    ];

    $sql_joins = '';
    $sql_where = 'fs.formid = :formid AND fs.visible = :visible';
echo $confirmfilter;
       // ================= CONFIRM FILTER LOGIC =================
    if (!empty($confirmfilter)) {

        if ($confirmfilter === 'Not Confirmed') {

            $sql_where .= " AND (fs.confirmflag = :confirmflag OR fs.confirmflag IS NULL)";
            $sql_params['confirmflag'] = 'Not Confirmed';

        } else if ($confirmfilter === 'Confirmed') {

            $sql_where .= " AND fs.confirmflag = :confirmflag";
            $sql_params['confirmflag'] = 'Confirmed';
        }
    }

    // ================= SEARCH LOGIC =================
// if (!empty($searchkeyword)) {

//     $sql_where .= " AND (
//         fs.uid IN (
//             SELECT u.id FROM {user} u
//             WHERE u.username LIKE :search1
//                OR u.firstname LIKE :search2
//                OR u.lastname LIKE :search3
//                OR u.email LIKE :search4
//         )
//         OR EXISTS (
//             SELECT 1
//             FROM {form_submissions} fs2
//             WHERE fs2.uid = fs.uid
//               AND fs2.formid = fs.formid
//               AND fs2.visible = 1
//               AND fs2.fieldvalue LIKE :search5
//         )
//     )";

//     $like = '%' . $searchkeyword . '%';

//     $sql_params['search1'] = $like;
//     $sql_params['search2'] = $like;
//     $sql_params['search3'] = $like;
//     $sql_params['search4'] = $like;
//     $sql_params['search5'] = $like;
// }
if (!empty($searchkeyword)) {

    $keywordtrim = trim($searchkeyword);

    // ============================================
    // EXACT STATUS SEARCH
    // ============================================
    if (strcasecmp($keywordtrim, 'Confirmed') === 0 ||
        strcasecmp($keywordtrim, 'Not Confirmed') === 0) {

        $sql_where .= " AND fs.confirmflag = :statussearch";
        $sql_params['statussearch'] =
            (strcasecmp($keywordtrim, 'Confirmed') === 0)
            ? 'Confirmed'
            : 'Not Confirmed';

        // Save status for later use
        $statusfilter = $sql_params['statussearch'];

    } else {

        // ============================================
        // NORMAL LIKE SEARCH
        // ============================================
        $sql_where .= " AND (
            fs.uid IN (
                SELECT u.id FROM {user} u
                WHERE u.username LIKE :search1
                   OR u.firstname LIKE :search2
                   OR u.lastname LIKE :search3
                   OR u.email LIKE :search4
            )
            OR fs.fieldvalue LIKE :search5
        )";

        $like = '%' . $searchkeyword . '%';

        $sql_params['search1'] = $like;
        $sql_params['search2'] = $like;
        $sql_params['search3'] = $like;
        $sql_params['search4'] = $like;
        $sql_params['search5'] = $like;
    }
}

    if ($cohortid > 0) {
        $sql_joins = 'INNER JOIN {cohort_members} cm ON fs.uid = cm.userid';
        $sql_where .= ' AND cm.cohortid = :cohortid';
        $sql_params['cohortid'] = $cohortid;
    }

    $count_sql = "SELECT COUNT(DISTINCT fs.uid)
                  FROM {form_submissions} fs 
                  $sql_joins 
                  WHERE $sql_where";

    $total_users = $DB->count_records_sql($count_sql, $sql_params);

    // $user_ids_sql = "SELECT DISTINCT fs.uid
    //                  FROM {form_submissions} fs 
    //                  $sql_joins 
    //                  WHERE $sql_where
    //                  ORDER BY fs.timecreated";

    $user_ids_sql = "SELECT fs.uid, MAX(fs.timecreated) as lastsubmitted
                     FROM {form_submissions} fs 
                     $sql_joins 
                     WHERE $sql_where
                     GROUP BY fs.uid
                     ORDER BY lastsubmitted DESC";

    $paged_user_records = $DB->get_records_sql(
        $user_ids_sql,
        $sql_params,
        $page * $perpage,
        $perpage
    );

    $paged_user_ids = array_keys($paged_user_records);

    if (empty($paged_user_ids)) {
        $o .= html_writer::tag('div', 'No records found', ['class' => 'alert alert-info']);
        return $o;
    }

    // ================= Fetch User Data =================
    $placeholders = [];
    $data_params = ['formid' => $formid, 'visible' => 1];
    $i = 0;

    foreach ($paged_user_ids as $uid) {
        $param = 'uid' . $i;
        $placeholders[] = ':' . $param;
        $data_params[$param] = $uid;
        $i++;
    }

    $in_clause = implode(',', $placeholders);

    // $data_sql = "SELECT fs.* 
    //              FROM {form_submissions} fs 
    //              WHERE fs.formid = :formid 
    //                AND fs.visible = :visible 
    //                AND fs.uid IN ($in_clause)
    //              ORDER BY fs.timecreated";

    $data_sql = "SELECT fs.* 
             FROM {form_submissions} fs 
             WHERE fs.formid = :formid 
               AND fs.visible = :visible 
               AND fs.uid IN ($in_clause)";

               if (!empty($statusfilter)) {
    $data_sql .= " AND fs.confirmflag = :statusfilter";
    $data_params['statusfilter'] = $statusfilter;
}

$data_sql .= " ORDER BY fs.timecreated";
    $all_records = $DB->get_records_sql($data_sql, $data_params);

    $fields = [];
    $users = [];
    $confirm_map = [];
    $time_map = [];

    foreach ($all_records as $record) {

        $uid = $record->uid;

        if (!in_array($record->fieldname, $fields)) {
            $fields[] = $record->fieldname;
        }

        if (!isset($users[$uid])) {
            $users[$uid] = [];
        }

        $users[$uid][$record->fieldname] = $record->fieldvalue;

        if (!isset($time_map[$uid]) || $record->timecreated > $time_map[$uid]) {
            $time_map[$uid] = $record->timecreated;
            $confirm_map[$uid] = $record->confirmflag ?? 'Confirm';
        }
    }

   // ================= SEARCH BOX =================
$o .= '<div class="mb-3 d-flex align-items-end">';

$o .= '<div class="mr-2">';
$o .= '<label for="table_search" class="font-weight-bold mb-1">Search</label>';
$o .= '<input type="text" 
        id="table_search" 
        value="'.htmlspecialchars($searchkeyword).'"
        class="form-control form-control-sm"
        placeholder="Search anything...">';
$o .= '</div>';

$o .= '<div class="mr-2">';
$o .= '<button type="button" 
                id="apply_search" 
                class="btn btn-sm btn-primary">
            Apply
        </button>';
$o .= '</div>';

$o .= '<div>';
$o .= '<button type="button" 
                id="reset_search" 
                class="btn btn-sm btn-secondary">
            Reset
        </button>';
$o .= '</div>';

$o .= '</div>';

    // ================= TABLE =================
    $headers = array_merge([
        html_writer::tag('input', '', [
            'type' => 'checkbox',
            'id' => 'selectAll',
            'onclick' => 'toggleAll(this)',
            'class' => 'master-checkbox'
        ]),
        'Confirm Status',
        'Form Submitted On',
        'UID',
        'Username',
        'Password',
        'Full Name',
        'Email'
    ], $fields);

    if ($cohortid == 0) {
        array_splice($headers, 5, 0, ['Cohort(s)']);
    }

    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->id = 'dynamic_table';
    $table->head = $headers;

    foreach ($paged_user_ids as $uid) {

        $user_data = $users[$uid];
        $encrypted_password = $DB->get_field('local_user', 'password', ['userid' => $uid]);
        $user = $DB->get_record('user', ['id' => $uid], 'username, firstname, lastname, email');

        $username = $user ? $user->username : 'N/A';
        $fullname = $user ? fullname($user) : 'N/A';
        $email = $user ? $user->email : 'N/A';

        $row = [];

        $row[] = html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'row-checkbox individual-checkbox uidcheckbox',
            'value' => $uid,
            'onclick' => 'updateParentCheckbox()'
        ]);

        $timecreated = $time_map[$uid] ?? null;
      $confirmstatus = $confirm_map[$uid] ?? 'Not Confirmed';

if ($confirmstatus === 'Confirmed') {
    $buttontext = 'Confirmed';
    $btnclass = 'btn-success';
} else {
    $buttontext = 'Not Confirmed';
    $btnclass = 'btn-danger';
}

        $row[] = '<button class="confirm_toggle '.$btnclass.'" 
                    data-uid="'.$uid.'" 
                    data-time="'.$timecreated.'" 
                    data-status="'.$confirmstatus.'">
                    '.$buttontext.'
                  </button>';

        $row[] = $timecreated ? userdate($timecreated) : 'N/A';
        $row[] = $uid;
        $row[] = $username;
        $row[] = $encrypted_password;
        $row[] = $fullname;
        $row[] = $email;

        if ($cohortid == 0) {
            $user_cohorts = $DB->get_records_sql(
                "SELECT c.name 
                 FROM {cohort} c
                 JOIN {cohort_members} cm ON c.id = cm.cohortid
                 WHERE cm.userid = :userid",
                ['userid' => $uid]
            );

            $names = [];
            foreach ($user_cohorts as $c) {
                $names[] = $c->name;
            }

            $row[] = !empty($names) ? implode(', ', $names) : 'None';
        }

         foreach ($fields as $field) {

            if (!empty($user_data[$field])) {

                $value = trim($user_data[$field]);
                $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                $allowedfiles = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];

                if (in_array($extension, $allowedfiles)) {

                    $fileurl = new moodle_url('/local/form/pix/' . $value);

                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {

                        $row[] = html_writer::empty_tag('img', [
                            'src' => $fileurl,
                            'alt' => 'Uploaded Image',
                            'style' => 'max-width:100px; height:auto;'
                        ]);

                    } else {

                        $row[] = html_writer::link(
                            $fileurl,
                            'View File',
                            ['target' => '_blank']
                        );
                    }

                } else {
                    $row[] = format_string($value);
                }

            } else {
                $row[] = '';
            }
        }

        $table->data[] = $row;
    }

    // ============ ORIGINAL BULK ACTION SECTION - KEPT EXACTLY AS IS ============
    $o .= html_writer::start_tag('div', ['class' => 'bulk-action-section']);
    $o .= html_writer::start_tag('form', ['method' => 'post', 'id' => 'bulkActionForm']);

    $o .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'id' => 'selectedUserIds',
        'name' => 'userids'
    ]);

    // $o .= html_writer::tag('button', 'Remove Selected', [
    //     'type' => 'submit',
    //     'class' => 'btn btn-danger',
    //     'id' => 'bulkRemoveBtn',
    //     'onclick' => 'return prepareBulkRemove();'
    // ]);

    $o .= html_writer::tag(
        'span',
        '<span id="selectedCount">0</span> Users Selected',
        ['class' => 'ml-3 text-muted']
    );

    $o .= html_writer::end_tag('form');
    $o .= html_writer::end_tag('div');
    // ============ END ORIGINAL BULK ACTION SECTION ============

    // ============ NEW MIGRATION BULK ACTION SECTION - ADDED WITHOUT MODIFYING ORIGINAL ============
    $o .= html_writer::start_tag('div', ['class' => 'bulk-action-section mt-2']);
    $o .= html_writer::start_tag('form', ['method' => 'post', 'id' => 'migrationForm', 'action' => new moodle_url('/local/form/migration.php')]);

    $o .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'id' => 'migration_selected_userids',
        'name' => 'userids'
    ]);

    $o .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'formid',
        'value' => $formid
    ]);

    if (!empty($token)) {
        $o .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'token',
            'value' => $token
        ]);
    }

    if ($cohortid > 0) {
        $o .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'cohortid',
            'value' => $cohortid
        ]);
    }

    $o .= html_writer::tag('button', 
        html_writer::tag('i', '', ['class' => 'fas fa-cloud-upload-alt mr-2']) . 'Migrate Selected to Sargam',
        [
            'type' => 'submit',
            'class' => 'btn btn-success',
            'id' => 'bulkMigrateBtn',
            'onclick' => 'return prepareBulkMigration();'
        ]
    );

    $o .= html_writer::end_tag('form');
    $o .= html_writer::end_tag('div');
    // ============ END NEW MIGRATION BULK ACTION SECTION ============

    $o .= html_writer::table($table);

    // ================= PAGINATION =================
    $totalpages = ($perpage > 0) ? ceil($total_users / $perpage) : 0;

    if ($totalpages > 1) {
        $paging_url = new moodle_url('/local/form/courselist.php', [
            'token' => $token,
            'cohortid' => $cohortid
        ]);

        $o .= $OUTPUT->paging_bar(
            $total_users,
            $page,
            $perpage,
            $paging_url
        );
    }

    // Download selector
    $o .= $OUTPUT->download_dataformat_selector(
        get_string('download', 'local_form'),
        'export.php',
        'dataformat',
        [
            'formid' => $formid,
            'token' => $token,
            'cohortid' => $cohortid
        ]
    );

    // JavaScript - UPDATED with migration function added, original JS kept intact
    $o .= '
<script>
// ============ ORIGINAL JAVASCRIPT FUNCTIONS - KEPT EXACTLY AS IS ============
function toggleAll(source){
    document.querySelectorAll(".row-checkbox").forEach(cb=>{
        cb.checked = source.checked;
        updateRowStyle(cb);
    });
    updateSelectionInfo();
}

function updateParentCheckbox(){
    const all = document.querySelectorAll(".row-checkbox");
    const checked = document.querySelectorAll(".row-checkbox:checked");
    document.getElementById("selectAll").checked = (all.length === checked.length);
    updateSelectionInfo();
}

function updateRowStyle(cb){
    const row = cb.closest("tr");
    if(cb.checked){
        row.classList.add("row-selected");
    }else{
        row.classList.remove("row-selected");
    }
}

function updateSelectionInfo(){
    const checked = document.querySelectorAll(".row-checkbox:checked");
    document.getElementById("selectedCount").textContent = checked.length;

    const btn = document.getElementById("bulkRemoveBtn");
    btn.disabled = checked.length === 0;
    btn.style.opacity = checked.length === 0 ? "0.6" : "1";
}

function prepareBulkRemove(){
    const checked = document.querySelectorAll(".row-checkbox:checked");
    if(checked.length === 0){
        alert("No users selected");
        return false;
    }
    const ids = [];
    checked.forEach(cb=>ids.push(cb.value));
    document.getElementById("selectedUserIds").value = ids.join(",");
    return true;
}
// ============ END ORIGINAL JAVASCRIPT FUNCTIONS ============

// ============ NEW MIGRATION JAVASCRIPT FUNCTIONS - ADDED WITHOUT MODIFYING ORIGINAL ============
function prepareBulkMigration(){
    const checked = document.querySelectorAll(".row-checkbox:checked");
    if(checked.length === 0){
        alert("No users selected for migration");
        return false;
    }
    const ids = [];
    checked.forEach(cb=>ids.push(cb.value));
    document.getElementById("migration_selected_userids").value = ids.join(",");
    return true;
}

// Add migration button state update to existing updateSelectionInfo function
// This wraps the original function to add migration button state
const originalUpdateSelectionInfo = updateSelectionInfo;
updateSelectionInfo = function() {
    // Call original function
    originalUpdateSelectionInfo();
    
    // Add migration button state update
    const checked = document.querySelectorAll(".row-checkbox:checked");
    const migrateBtn = document.getElementById("bulkMigrateBtn");
    if (migrateBtn) {
        migrateBtn.disabled = checked.length === 0;
        migrateBtn.style.opacity = checked.length === 0 ? "0.6" : "1";
    }
};
// ============ END NEW MIGRATION JAVASCRIPT FUNCTIONS ============

document.addEventListener("DOMContentLoaded",function(){
    document.querySelectorAll(".row-checkbox").forEach(cb=>{
        cb.addEventListener("change",function(){
            updateRowStyle(this);
            updateParentCheckbox();
        });
    });
    updateSelectionInfo();
});
</script>';

    // Add Font Awesome for icons if not already loaded
    $o .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

    // Add custom CSS for migration button
    $o .= '
<style>
/* Migration button styles - added without affecting original styles */
#bulkMigrateBtn {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    transition: all 0.3s ease;
}
#bulkMigrateBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}
#bulkMigrateBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.bulk-action-section.mt-2 {
    margin-top: 10px;
}
</style>';

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

    // public function local_cohort()
    // {
    //     global $DB, $OUTPUT;

    //     // Initialize options array
    //     $options[] = "Select Cohort";

    //     // Fetch cohort records from the database
    //     $sql = "SELECT id, name FROM {cohort}"; // Use Moodle's table name conventions
    //     $records = $DB->get_records_sql($sql);

    //     // Populate options array
    //     foreach ($records as $record) {
    //         $options[$record->id] = $record->name; // Correct way to assign name to option
    //     }

    //     // Create label for the dropdown
    //     $label = html_writer::label(get_string('selectcohort', 'local_form'), 'quizviewfilter', false);

    //     // Create the dropdown (select) element
    //     $dropdown = html_writer::select($options, "quizview", '', array(), array('id' => 'quizviewfilter'));

    //     // Add some space between the label and dropdown (you can use HTML <br> or CSS style for spacing)
    //     $space = html_writer::empty_tag('br');  // Add a line break for spacing

    //     // Create the button with label "Add Selected Student to Cohort"
    //     $button = html_writer::empty_tag('input', array(
    //         'type' => 'submit',
    //         'value' => get_string('addselectedstudent', 'local_form'),
    //         'id' => 'add_to_cohort_button',
    //         'class' => 'btn btn-primary'
    //     ));

    //     // Combine label, dropdown, space, and button
    //     $output = $label . $dropdown . $space . $space . $button;

    //     return $output;
    // }

    public function local_cohort($selectedcohortid = 0)
{
    global $DB;

    // Initialize options array properly
    $options = [];
    $options[0] = "Select Cohort";

    // Fetch cohort records
    $records = $DB->get_records('cohort', null, 'name ASC', 'id, name');

    foreach ($records as $record) {
        $options[$record->id] = $record->name;
    }

    // Label
    $label = html_writer::label(
        get_string('selectcohort', 'local_form'),
        'quizviewfilter',
        false
    );

    $dropdown = html_writer::select(
        $options,
        "quizview",
        $selectedcohortid,   // this makes it auto-selected
        null,
        array('id' => 'quizviewfilter')
    );

    $space = html_writer::empty_tag('br');

    $button = html_writer::empty_tag('input', array(
        'type' => 'submit',
        'value' => get_string('addselectedstudent', 'local_form'),
        'id' => 'add_to_cohort_button',
        'class' => 'btn btn-primary'
    ));

    return $label . $dropdown . $space . $space . $button;
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
    public function local_manageforms_report($records, $recordcount, $page, $perpage, $status, $search)
    {
        global $DB, $OUTPUT;

        if (!$records) {
            return html_writer::div(
                'No records found',
                'alert alert-warning text-center'
            );
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

            $params = ['formid' => $record->id];

            $submissions = html_writer::link(
                new moodle_url('/local/form/report_courselist.php', $params),
                'View Submissions',
                ['class' => 'btn btn-outline-info btn-sm']
            );

            $table->data[] = [
                $i++,
                html_writer::tag('strong', format_string($record->name)),
                html_writer::div(
                    format_text($record->description, FORMAT_HTML),
                    'text-muted small'
                ),
                $submissions,
                userdate($record->timecreated)
            ];
        }

        $output = html_writer::table($table);

        /* Pagination (keep filters) */
        $baseurl = new moodle_url('/local/form/form_report.php', [
            'status' => $status,
            'search' => $search
        ]);

        $output .= html_writer::div(
            $OUTPUT->paging_bar($recordcount, $page, $perpage, $baseurl),
            'text-center mt-4'
        );

        return $output;
    }
    
     /**
     * ==================== MOODLE TO SARGAM MIGRATION METHODS ====================
     */

    /**
     * Render migration interface for Moodle to Sargam
     *
     * @param array $selectedusers Selected user IDs
     * @param int $formid Form ID
     * @param string $token Token for authentication
     * @param int $cohortid Cohort ID
     * @return string HTML output
     */
    public function render_migration_interface($selectedusers, $formid, $token = '', $cohortid = 0) {
        global $DB;
        
        $o = '';
        
        // Get user data for selected users
        $userdata = [];
        if (!empty($selectedusers)) {
            list($insql, $inparams) = $DB->get_in_or_equal($selectedusers);
            $users = $DB->get_records_select('user', "id $insql", $inparams, '', 
                'id, username, firstname, lastname, email, phone1, phone2, institution, department, 
                 address, city, country, timecreated, timemodified, lastaccess, picture, 
                 confirmed, suspended, idnumber, middlename, alternatename');
            
            foreach ($users as $user) {
                $password = $DB->get_field('local_user', 'password', ['userid' => $user->id]);
                $user->password_hash = $password ? $password : '';
                
                $submissions = $DB->get_records('form_submissions', [
                    'formid' => $formid,
                    'uid' => $user->id,
                    'visible' => 1
                ], '', 'fieldname, fieldvalue');
                
                $user->submissions = [];
                foreach ($submissions as $submission) {
                    $user->submissions[$submission->fieldname] = $submission->fieldvalue;
                }
                
                $userdata[$user->id] = $user;
            }
        }
        
        $moodlecolumns = $this->get_moodle_user_columns();
        
        $sargamtables = [
            'user_credentials' => $this->get_sargam_user_credentials_columns(),
            'student_master' => $this->get_sargam_student_master_columns(),
            'student_master_course__map' => $this->get_sargam_course_map_columns()
        ];
        
        $o .= $this->output->heading('Moodle to Sargam Migration', 2, 'mb-4');
        $o .= $this->render_selected_users_summary($userdata);
        $o .= $this->render_migration_tabs();
        
        $o .= \html_writer::start_div('migration-main-container row mt-4');
        $o .= $this->render_moodle_columns_section($moodlecolumns, $userdata);
        $o .= $this->render_sargam_tables_section($sargamtables);
        $o .= \html_writer::end_div();
        
        $o .= $this->render_mapping_area();
        $o .= $this->render_migration_actions($formid, $token, $cohortid, $selectedusers);
        $o .= $this->render_progress_area($selectedusers);
        $o .= $this->render_migration_javascript($userdata, $formid, $token, $cohortid);
        $o .= $this->render_migration_css();
        
        return $o;
    }

    /**
     * Get Moodle user table columns
     */
    private function get_moodle_user_columns() {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'middlename' => 'Middle Name',
            'alternatename' => 'Alternate Name',
            'email' => 'Email',
            'password_hash' => 'Password',
            'phone1' => 'Mobile/Phone 1',
            'phone2' => 'Phone 2',
            'institution' => 'Institution',
            'department' => 'Department',
            'address' => 'Address',
            'city' => 'City',
            'country' => 'Country',
            'timecreated' => 'Time Created',
            'timemodified' => 'Time Modified',
            'lastaccess' => 'Last Access',
            'picture' => 'Picture',
            'confirmed' => 'Confirmed',
            'suspended' => 'Suspended',
            'idnumber' => 'ID Number',
            // 'dob' => 'Date of Birth'
        ];
    }

    /**
     * Get Sargam user_credentials table columns
     */
    private function get_sargam_user_credentials_columns() {
        return [
            'user_name' => 'Username',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'jbp_password' => 'Password',
            'email_id' => 'Email',
            'mobile_no' => 'Mobile Number',
            'alternate_mailid' => 'Alternate Email',
            'reg_date' => 'Registration Date',
            'jbp_enabled' => 'Enabled',
            'login_status' => 'Login Status',
            'user_id' => 'User ID',
            'last_login' => 'Last Login',
            'entity_id' => 'Entity ID',
            'image_path' => 'Image Path',
            'user_category' => 'User Category',
            'Active_inactive' => 'Active Status',
            'updated_date' => 'Updated Date'
        ];
    }

    /**
     * Get Sargam student_master table columns
     */
    private function get_sargam_student_master_columns() {
        return [
            'email' => 'Email',
            'contact_no' => 'Contact Number',
            'user_id' => 'User ID',
            'display_name' => 'Display Name',
            'password' => 'Password',
            'created_date' => 'Created Date',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'admission_status' => 'Admission Status',
            'rank' => 'Rank',
            'exam_year' => 'Exam Year',
            'web_auth' => 'Web Auth',
            'dob' => 'Date of Birth',
            'status' => 'Status',
            'enrollment' => 'Enrollment Number'
        ];
    }

    /**
     * Get Sargam student_master_course__map table columns
     */
    private function get_sargam_course_map_columns() {
        return [
            'student_master_pk' => 'Student ID',
            'course_master_pk' => 'Course ID',
            'active_inactive' => 'Active Status',
            'created_date' => 'Created Date',
            'modified_date' => 'Modified Date'
        ];
    }

    /**
     * Render selected users summary
     */
    private function render_selected_users_summary($userdata) {
        $o = '';
        $o .= \html_writer::start_div('selected-users-summary alert alert-info d-flex align-items-center');
        $o .= \html_writer::tag('i', '', ['class' => 'fas fa-users mr-3', 'style' => 'font-size: 24px;']);
        $o .= \html_writer::start_div('flex-grow-1');
        $o .= \html_writer::tag('strong', count($userdata) . ' Users Selected for Migration');
        $o .= \html_writer::start_tag('ul', ['class' => 'mb-0 mt-1']);
        
        $count = 0;
        foreach ($userdata as $user) {
            if ($count++ < 5) {
                $o .= \html_writer::tag('li', fullname($user) . ' (' . $user->username . ')');
            }
        }
        
        if (count($userdata) > 5) {
            $o .= \html_writer::tag('li', '... and ' . (count($userdata) - 5) . ' more');
        }
        
        $o .= \html_writer::end_tag('ul');
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        return $o;
    }

    /**
     * Render migration tabs
     */
    private function render_migration_tabs() {
        $o = '';
        $o .= \html_writer::start_div('migration-tabs mb-4');
        $o .= \html_writer::start_tag('ul', ['class' => 'nav nav-tabs', 'role' => 'tablist']);
        
        $tabs = [
            'user_credentials' => ['icon' => 'fa-user', 'label' => 'User Credentials'],
            'student_master' => ['icon' => 'fa-graduation-cap', 'label' => 'Student Master'],
            'student_master_course__map' => ['icon' => 'fa-book', 'label' => 'Course Enrollments']
        ];
        
        $active = true;
        foreach ($tabs as $tabid => $tab) {
            $linkclass = 'nav-link' . ($active ? ' active' : '');
            $active = false;
            
            $o .= \html_writer::start_tag('li', ['class' => 'nav-item']);
            $o .= \html_writer::start_tag('a', [
                'class' => $linkclass,
                'id' => $tabid . '-tab',
                'data-toggle' => 'tab',
                'href' => '#' . $tabid,
                'role' => 'tab'
            ]);
            $o .= \html_writer::tag('i', '', ['class' => 'fas ' . $tab['icon'] . ' mr-2']);
            $o .= $tab['label'];
            $o .= \html_writer::end_tag('a');
            $o .= \html_writer::end_tag('li');
        }
        
        $o .= \html_writer::end_tag('ul');
        $o .= \html_writer::end_div();
        return $o;
    }

    /**
     * Render Moodle columns section
     */
    private function render_moodle_columns_section($moodlecolumns, $userdata) {
        $o = '';
        $firstuser = reset($userdata);
        
        $o .= \html_writer::start_div('col-md-6');
        $o .= \html_writer::start_div('moodle-columns-card card h-100');
        $o .= \html_writer::start_div('card-header bg-primary text-white');
        $o .= \html_writer::tag('i', '', ['class' => 'fas fa-database mr-2']);
        $o .= 'Moodle User Table (mdl_user)';
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('card-body p-0');
        $o .= \html_writer::start_div('table-responsive', ['style' => 'max-height: 500px; overflow-y: auto;']);
        $o .= \html_writer::start_tag('table', ['class' => 'table table-hover mb-0']);
        $o .= \html_writer::start_tag('thead', ['class' => 'thead-light']);
        $o .= \html_writer::start_tag('tr');
        $o .= \html_writer::tag('th', 'Column Name');
        $o .= \html_writer::tag('th', 'Sample Data');
        $o .= \html_writer::tag('th', 'Action');
        $o .= \html_writer::end_tag('tr');
        $o .= \html_writer::end_tag('thead');
        $o .= \html_writer::start_tag('tbody');
        
        foreach ($moodlecolumns as $colname => $collabel) {
            $sample = $this->get_sample_data($firstuser, $colname);
            
            $o .= \html_writer::start_tag('tr', ['class' => 'moodle-column-row', 'data-column' => $colname]);
            $o .= \html_writer::tag('td', 
                \html_writer::tag('strong', $collabel) . 
                \html_writer::empty_tag('br') . 
                \html_writer::tag('small', $colname, ['class' => 'text-muted'])
            );
            $o .= \html_writer::tag('td', \html_writer::tag('span', $sample, ['class' => 'sample-data']));
            $o .= \html_writer::tag('td', 
                \html_writer::tag('button', 
                    \html_writer::tag('i', '', ['class' => 'fas fa-arrow-right']) . ' Map',
                    [
                        'class' => 'btn btn-sm btn-outline-primary map-moodle-btn',
                        'data-column' => $colname,
                        'data-label' => $collabel
                    ]
                )
            );
            $o .= \html_writer::end_tag('tr');
        }
        
        $o .= \html_writer::end_tag('tbody');
        $o .= \html_writer::end_tag('table');
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Get sample data for column
     */
    private function get_sample_data($user, $colname) {
        if (!$user) return '—';
        
        if (isset($user->$colname)) {
            $value = $user->$colname;
            if (in_array($colname, ['timecreated', 'timemodified', 'lastaccess'])) {
                return $value ? userdate($value) : 'N/A';
            } else if (in_array($colname, ['confirmed', 'suspended'])) {
                return $value ? 'Yes' : 'No';
            } else if ($colname == 'dob' && $value) {
                return userdate($value, get_string('dateformat', 'local_form'));
            }
            return strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
        } else if ($colname == 'password_hash' && $user) {
            return $user->password_hash ? '********' : 'Not set';
        }
        
        return '—';
    }

    /**
     * Render Sargam tables section
     */
    private function render_sargam_tables_section($sargamtables) {
        $o = '';
        $o .= \html_writer::start_div('col-md-6');
        $o .= \html_writer::start_div('sargam-columns-card card h-100');
        $o .= \html_writer::start_div('card-header bg-success text-white');
        $o .= \html_writer::tag('i', '', ['class' => 'fas fa-cloud mr-2']);
        $o .= 'Sargam Database Tables';
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('card-body p-0');
        $o .= \html_writer::start_div('tab-content');
        
        $o .= \html_writer::start_div('tab-pane fade show active', ['id' => 'user_credentials', 'role' => 'tabpanel']);
        $o .= $this->render_sargam_table_columns('user_credentials', $sargamtables['user_credentials']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('tab-pane fade', ['id' => 'student_master', 'role' => 'tabpanel']);
        $o .= $this->render_sargam_table_columns('student_master', $sargamtables['student_master']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('tab-pane fade', ['id' => 'student_master_course__map', 'role' => 'tabpanel']);
        $o .= $this->render_sargam_table_columns('student_master_course__map', $sargamtables['student_master_course__map']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Render Sargam table columns
     */
    private function render_sargam_table_columns($tableid, $columns) {
        $o = '';
        $o .= \html_writer::start_div('table-responsive', ['style' => 'max-height: 500px; overflow-y: auto;']);
        $o .= \html_writer::start_tag('table', ['class' => 'table table-hover mb-0']);
        $o .= \html_writer::start_tag('thead', ['class' => 'thead-light']);
        $o .= \html_writer::start_tag('tr');
        $o .= \html_writer::tag('th', 'Column Name');
        $o .= \html_writer::tag('th', 'Description');
        $o .= \html_writer::tag('th', 'Action');
        $o .= \html_writer::end_tag('tr');
        $o .= \html_writer::end_tag('thead');
        $o .= \html_writer::start_tag('tbody');
        
        foreach ($columns as $colname => $collabel) {
            $o .= \html_writer::start_tag('tr', [
                'class' => 'sargam-column-row',
                'data-table' => $tableid,
                'data-column' => $colname
            ]);
            $o .= \html_writer::tag('td', 
                \html_writer::tag('strong', $collabel) . 
                \html_writer::empty_tag('br') . 
                \html_writer::tag('small', $colname, ['class' => 'text-muted'])
            );
            $o .= \html_writer::tag('td', $this->get_column_description($colname));
            $o .= \html_writer::tag('td', 
                \html_writer::tag('button', 
                    \html_writer::tag('i', '', ['class' => 'fas fa-arrow-left']) . ' Map',
                    [
                        'class' => 'btn btn-sm btn-outline-success map-sargam-btn',
                        'data-table' => $tableid,
                        'data-column' => $colname,
                        'data-label' => $collabel
                    ]
                )
            );
            $o .= \html_writer::end_tag('tr');
        }
        
        $o .= \html_writer::end_tag('tbody');
        $o .= \html_writer::end_tag('table');
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Get column description
     */
    private function get_column_description($colname) {
        $descriptions = [
            'user_name' => 'Login username',
            'first_name' => 'User first name',
            'last_name' => 'User last name',
            'jbp_password' => 'Encrypted password',
            'email_id' => 'Primary email',
            'mobile_no' => 'Contact number',
            'alternate_mailid' => 'Secondary email',
            'reg_date' => 'Registration timestamp',
            'Active_inactive' => 'Account status',
            'display_name' => 'Full display name',
            'enrollment' => 'Student enrollment ID',
            'student_master_pk' => 'Student ID reference',
            'course_master_pk' => 'Course ID reference'
        ];
        
        return $descriptions[$colname] ?? 'Standard column';
    }

    /**
     * Render mapping area
     */
    private function render_mapping_area() {
        $o = '';
        $o .= \html_writer::start_div('mapping-area mt-4');
        $o .= \html_writer::start_div('card');
        $o .= \html_writer::start_div('card-header bg-info text-white d-flex justify-content-between align-items-center');
        $o .= \html_writer::tag('h5', 
            \html_writer::tag('i', '', ['class' => 'fas fa-link mr-2']) . 'Column Mappings', 
            ['class' => 'mb-0']
        );
        $o .= \html_writer::tag('span', '0 mappings', ['id' => 'mapping-count-badge', 'class' => 'badge badge-light']);
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('card-body');
        
        $o .= \html_writer::start_div('row mb-3');
        $o .= \html_writer::start_div('col-md-5 text-right font-weight-bold');
        $o .= 'Moodle Column';
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('col-md-2 text-center');
        $o .= \html_writer::tag('i', '', ['class' => 'fas fa-long-arrow-alt-right fa-2x']);
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('col-md-5 font-weight-bold');
        $o .= 'Sargam Column';
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('mapping-list', ['id' => 'mapping-list']);
        $o .= \html_writer::tag('p', 
            'No mappings defined yet. Click "Map" buttons to create mappings.',
            ['class' => 'text-muted text-center py-4']
        );
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('mt-3 text-right');
        $o .= \html_writer::tag('button', 
            \html_writer::tag('i', '', ['class' => 'fas fa-magic mr-2']) . 'Suggest Mappings',
            ['id' => 'suggest-mappings-btn', 'class' => 'btn btn-warning mr-2']
        );
        $o .= \html_writer::tag('button', 
            \html_writer::tag('i', '', ['class' => 'fas fa-trash mr-2']) . 'Clear All',
            ['id' => 'clear-mappings-btn', 'class' => 'btn btn-outline-danger']
        );
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Render migration actions
     */
    private function render_migration_actions($formid, $token, $cohortid, $selectedusers) {
        $o = '';
        $o .= \html_writer::start_div('migration-actions mt-4');
        $o .= \html_writer::start_div('card');
        $o .= \html_writer::start_div('card-header bg-warning');
        $o .= \html_writer::tag('h5', 
            \html_writer::tag('i', '', ['class' => 'fas fa-cogs mr-2']) . 'Migration Actions',
            ['class' => 'mb-0']
        );
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('card-body');
        $o .= \html_writer::start_div('row align-items-center');
        $o .= \html_writer::start_div('col-md-8');
        $o .= \html_writer::tag('p', 
            'Selected users will be migrated to Sargam based on your column mappings.',
            ['class' => 'mb-2']
        );
        $o .= \html_writer::tag('small', 
            'Make sure all required fields are mapped before migration.',
            ['class' => 'text-muted']
        );
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('col-md-4 text-right');
        
        $o .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => 'migration-formid',
            'value' => $formid
        ]);
        $o .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => 'migration-token',
            'value' => $token
        ]);
        $o .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => 'migration-cohortid',
            'value' => $cohortid
        ]);
        $o .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'id' => 'migration-userids',
            'value' => implode(',', $selectedusers)
        ]);
        
        $o .= \html_writer::tag('button', 
            \html_writer::tag('i', '', ['class' => 'fas fa-play mr-2']) . 'Test Connection',
            ['id' => 'test-connection-btn', 'class' => 'btn btn-info mr-2']
        );
        $o .= \html_writer::tag('button', 
            \html_writer::tag('i', '', ['class' => 'fas fa-check-circle mr-2']) . 'Validate Mappings',
            ['id' => 'validate-mappings-btn', 'class' => 'btn btn-secondary mr-2']
        );
        $o .= \html_writer::tag('button', 
            \html_writer::tag('i', '', ['class' => 'fas fa-rocket mr-2']) . 'Execute Migration',
            ['id' => 'execute-migration-btn', 'class' => 'btn btn-success']
        );
        
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Render progress area
     */
    private function render_progress_area($selectedusers = []) {
        $usercount = is_array($selectedusers) ? count($selectedusers) : 0;
        
        $o = '';
        $o .= \html_writer::start_div('migration-progress mt-4', ['id' => 'migration-progress', 'style' => 'display: none;']);
        $o .= \html_writer::start_div('card');
        $o .= \html_writer::start_div('card-header bg-success text-white');
        $o .= \html_writer::tag('h5', 
            \html_writer::tag('i', '', ['class' => 'fas fa-chart-line mr-2']) . 'Migration Progress',
            ['class' => 'mb-0']
        );
        $o .= \html_writer::end_div();
        $o .= \html_writer::start_div('card-body');
        
        $o .= \html_writer::start_div('progress mb-3', ['style' => 'height: 25px;']);
        $o .= \html_writer::start_div('progress-bar progress-bar-striped progress-bar-animated bg-success', [
            'id' => 'migration-progress-bar',
            'role' => 'progressbar',
            'style' => 'width: 0%;',
            'aria-valuenow' => '0',
            'aria-valuemin' => '0',
            'aria-valuemax' => '100'
        ]);
        $o .= '0%';
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::tag('p', 'Ready to migrate...', ['id' => 'migration-status', 'class' => 'font-weight-bold']);
        
        $o .= \html_writer::start_div('migration-log mt-3', [
            'id' => 'migration-log',
            'style' => 'background: #1a2634; border-radius: 5px; padding: 15px; max-height: 200px; overflow-y: auto; font-family: monospace;'
        ]);
        $o .= \html_writer::tag('div', '✓ Migration interface initialized', ['class' => 'text-success']);
        $o .= \html_writer::tag('div', '✓ Ready to migrate ' . $usercount . ' users', ['class' => 'text-info']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        $o .= \html_writer::end_div();
        
        return $o;
    }

    /**
     * Render migration JavaScript - IMPROVED VERSION with normal functions
     */
   /**
 * Render migration JavaScript - IMPROVED VERSION with normal functions
 */
/**
 * Render migration JavaScript - COMPLETE WORKING VERSION
 */
/**
 * Render migration JavaScript - CLEAN VERSION with only requested mappings
 */
private function render_migration_javascript($userdata, $formid, $token, $cohortid) {
    // All requested mappings including course map
    $suggestions = [
        'user_credentials' => [
            ['moodle' => 'username', 'sargam' => 'user_name'],
            ['moodle' => 'firstname', 'sargam' => 'first_name'],
            ['moodle' => 'lastname', 'sargam' => 'last_name'],
            ['moodle' => 'email', 'sargam' => 'email_id'],
            ['moodle' => 'phone1', 'sargam' => 'mobile_no']
        ],
        'student_master' => [
            ['moodle' => 'username', 'sargam' => 'user_id'],
            ['moodle' => 'firstname', 'sargam' => 'first_name'],
            ['moodle' => 'lastname', 'sargam' => 'last_name'],
            ['moodle' => 'email', 'sargam' => 'email'],
            ['moodle' => 'phone1', 'sargam' => 'contact_no'],
            ['moodle' => 'password_hash', 'sargam' => 'password']
        ],
        'student_master_course__map' => [
            ['moodle' => 'id', 'sargam' => 'student_master_pk'],
            ['moodle' => 'id', 'sargam' => 'course_master_pk'], // Will be mapped via shortname logic in PHP
            ['moodle' => 'timecreated', 'sargam' => 'created_date'],
            ['moodle' => 'timemodified', 'sargam' => 'modified_date'],
            ['moodle' => 'confirmed', 'sargam' => 'active_inactive']
        ]
    ];
    
    $suggestions_json = json_encode($suggestions, JSON_PRETTY_PRINT);
    
    $o = \html_writer::start_tag('script', ['type' => 'text/javascript']);
    $o .= "\n\n// ============================================\n";
    $o .= "// MOODLE TO SARGAM MIGRATION JAVASCRIPT - COMPLETE VERSION WITH COURSE MAP\n";
    $o .= "// ============================================\n\n";
    
    $o .= "// Global state\n";
    $o .= "let currentMappings = {\n";
    $o .= "    user_credentials: {},\n";
    $o .= "    student_master: {},\n";
    $o .= "    student_master_course__map: {}\n";
    $o .= "};\n\n";
    
    $o .= "let selectedMoodleColumn = null;\n";
    $o .= "let selectedSargamColumn = null;\n";
    $o .= "let selectedSargamTable = null;\n";
    $o .= "let selectedMoodleLabel = null;\n";
    $o .= "let selectedSargamLabel = null;\n\n";
    
    $o .= "// Complete mapping suggestions including course map\n";
    $o .= "const MAPPING_SUGGESTIONS = {$suggestions_json};\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// INITIALIZATION FUNCTION\n";
    $o .= "// ============================================\n\n";
    
    $o .= "document.addEventListener('DOMContentLoaded', function() {\n";
    $o .= "    initMigrationInterface();\n";
    $o .= "});\n\n";
    
    $o .= "function initMigrationInterface() {\n";
    $o .= "    console.log('Initializing migration interface...');\n";
    $o .= "    \n";
    $o .= "    // Moodle column buttons\n";
    $o .= "    document.querySelectorAll('.map-moodle-btn').forEach(btn => {\n";
    $o .= "        btn.addEventListener('click', onMoodleColumnClick);\n";
    $o .= "    });\n\n";
    
    $o .= "    // Sargam column buttons\n";
    $o .= "    document.querySelectorAll('.map-sargam-btn').forEach(btn => {\n";
    $o .= "        btn.addEventListener('click', onSargamColumnClick);\n";
    $o .= "    });\n\n";
    
    $o .= "    // Action buttons\n";
    $o .= "    const suggestBtn = document.getElementById('suggest-mappings-btn');\n";
    $o .= "    if (suggestBtn) {\n";
    $o .= "        suggestBtn.addEventListener('click', function(e) {\n";
    $o .= "            e.preventDefault();\n";
    $o .= "            suggestMappings();\n";
    $o .= "        });\n";
    $o .= "        console.log('Suggest button listener attached');\n";
    $o .= "    } else {\n";
    $o .= "        console.error('Suggest button not found!');\n";
    $o .= "    }\n\n";
    
    $o .= "    const clearBtn = document.getElementById('clear-mappings-btn');\n";
    $o .= "    if (clearBtn) {\n";
    $o .= "        clearBtn.addEventListener('click', function(e) {\n";
    $o .= "            e.preventDefault();\n";
    $o .= "            clearAllMappings();\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    const testBtn = document.getElementById('test-connection-btn');\n";
    $o .= "    if (testBtn) {\n";
    $o .= "        testBtn.addEventListener('click', function(e) {\n";
    $o .= "            e.preventDefault();\n";
    $o .= "            testConnection();\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    const validateBtn = document.getElementById('validate-mappings-btn');\n";
    $o .= "    if (validateBtn) {\n";
    $o .= "        validateBtn.addEventListener('click', function(e) {\n";
    $o .= "            e.preventDefault();\n";
    $o .= "            validateMappings();\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    const executeBtn = document.getElementById('execute-migration-btn');\n";
    $o .= "    if (executeBtn) {\n";
    $o .= "        executeBtn.addEventListener('click', function(e) {\n";
    $o .= "            e.preventDefault();\n";
    $o .= "            executeMigration();\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    // Load saved mappings\n";
    $o .= "    loadMappings();\n";
    $o .= "    addLogEntry('✓ Migration interface initialized', 'success');\n";
    $o .= "    const userCount = document.getElementById('migration-userids')?.value?.split(',').length || 0;\n";
    $o .= "    addLogEntry('✓ Ready to migrate ' + userCount + ' users', 'info');\n";
    $o .= "}\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// COLUMN SELECTION HANDLERS\n";
    $o .= "// ============================================\n\n";
    
    $o .= "function onMoodleColumnClick(e) {\n";
    $o .= "    e.preventDefault();\n";
    $o .= "    const btn = e.currentTarget;\n";
    $o .= "    const column = btn.dataset.column;\n";
    $o .= "    const label = btn.dataset.label;\n\n";
    
    $o .= "    // Update UI\n";
    $o .= "    document.querySelectorAll('.map-moodle-btn').forEach(b => {\n";
    $o .= "        b.classList.remove('active', 'btn-primary');\n";
    $o .= "        b.classList.add('btn-outline-primary');\n";
    $o .= "    });\n\n";
    
    $o .= "    btn.classList.add('active', 'btn-primary');\n";
    $o .= "    btn.classList.remove('btn-outline-primary');\n\n";
    
    $o .= "    selectedMoodleColumn = column;\n";
    $o .= "    selectedMoodleLabel = label;\n";
    $o .= "    highlightMoodleRow(column);\n\n";
    
    $o .= "    if (selectedSargamColumn && selectedSargamTable) {\n";
    $o .= "        createMapping();\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "function onSargamColumnClick(e) {\n";
    $o .= "    e.preventDefault();\n";
    $o .= "    const btn = e.currentTarget;\n";
    $o .= "    const table = btn.dataset.table;\n";
    $o .= "    const column = btn.dataset.column;\n";
    $o .= "    const label = btn.dataset.label;\n\n";
    
    $o .= "    // Update UI\n";
    $o .= "    document.querySelectorAll('.map-sargam-btn').forEach(b => {\n";
    $o .= "        b.classList.remove('active', 'btn-success');\n";
    $o .= "        b.classList.add('btn-outline-success');\n";
    $o .= "    });\n\n";
    
    $o .= "    btn.classList.add('active', 'btn-success');\n";
    $o .= "    btn.classList.remove('btn-outline-success');\n\n";
    
    $o .= "    selectedSargamColumn = column;\n";
    $o .= "    selectedSargamLabel = label;\n";
    $o .= "    selectedSargamTable = table;\n";
    $o .= "    highlightSargamRow(table, column);\n\n";
    
    $o .= "    if (selectedMoodleColumn) {\n";
    $o .= "        createMapping();\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "function highlightMoodleRow(column) {\n";
    $o .= "    document.querySelectorAll('.moodle-column-row').forEach(row => {\n";
    $o .= "        row.classList.remove('table-primary');\n";
    $o .= "        if (row.dataset.column === column) {\n";
    $o .= "            row.classList.add('table-primary');\n";
    $o .= "        }\n";
    $o .= "    });\n";
    $o .= "}\n\n";
    
    $o .= "function highlightSargamRow(table, column) {\n";
    $o .= "    document.querySelectorAll('.sargam-column-row').forEach(row => {\n";
    $o .= "        row.classList.remove('table-success');\n";
    $o .= "        if (row.dataset.table === table && row.dataset.column === column) {\n";
    $o .= "            row.classList.add('table-success');\n";
    $o .= "        }\n";
    $o .= "    });\n";
    $o .= "}\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// MAPPING MANAGEMENT\n";
    $o .= "// ============================================\n\n";
    
    $o .= "function createMapping() {\n";
    $o .= "    if (!selectedMoodleColumn || !selectedSargamColumn || !selectedSargamTable) {\n";
    $o .= "        addLogEntry('Please select both Moodle and Sargam columns', 'warning');\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    const moodleCol = selectedMoodleColumn;\n";
    $o .= "    const sargamCol = selectedSargamColumn;\n";
    $o .= "    const table = selectedSargamTable;\n\n";
    
    $o .= "    // Check if already mapped\n";
    $o .= "    if (currentMappings[table][moodleCol]) {\n";
    $o .= "        alert(`Column \${moodleCol} is already mapped to \${currentMappings[table][moodleCol]}`);\n";
    $o .= "        resetSelections();\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    // Check if target column already used in this table\n";
    $o .= "    let isUsed = false;\n";
    $o .= "    let usedBy = '';\n\n";
    
    $o .= "    Object.keys(currentMappings[table]).forEach(key => {\n";
    $o .= "        if (currentMappings[table][key] === sargamCol) {\n";
    $o .= "            isUsed = true;\n";
    $o .= "            usedBy = key;\n";
    $o .= "        }\n";
    $o .= "    });\n\n";
    
    $o .= "    if (isUsed) {\n";
    $o .= "        alert(`Sargam column \${sargamCol} is already mapped to \${usedBy} in \${getTableLabel(table)}`);\n";
    $o .= "        resetSelections();\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    // Save mapping\n";
    $o .= "    currentMappings[table][moodleCol] = sargamCol;\n";
    $o .= "    addMappingToList(moodleCol, sargamCol, table);\n";
    $o .= "    addLogEntry(`✓ Mapped: \${moodleCol} → \${sargamCol} (\${getTableLabel(table)})`, 'success');\n";
    $o .= "    saveMappings();\n";
    $o .= "    resetSelections();\n";
    $o .= "    updateMappingCount();\n";
    $o .= "}\n\n";
    
    $o .= "function addMappingToList(moodleCol, sargamCol, table) {\n";
    $o .= "    const mappingList = document.getElementById('mapping-list');\n";
    $o .= "    if (!mappingList) return;\n\n";
    
    $o .= "    const tableLabel = getTableLabel(table);\n\n";
    
    $o .= "    // Remove 'no mappings' message if it exists\n";
    $o .= "    if (mappingList.children.length === 1 && mappingList.children[0].tagName === 'P') {\n";
    $o .= "        mappingList.innerHTML = '';\n";
    $o .= "    }\n\n";
    
    $o .= "    // Check if this mapping already exists in the DOM\n";
    $o .= "    const existingItems = mappingList.querySelectorAll(`.mapping-item[data-moodle=\"\${moodleCol}\"][data-table=\"\${table}\"]`);\n";
    $o .= "    if (existingItems.length > 0) return;\n\n";
    
    $o .= "    const mappingItem = document.createElement('div');\n";
    $o .= "    mappingItem.className = 'mapping-item row mb-2 pb-2 border-bottom';\n";
    $o .= "    mappingItem.dataset.moodle = moodleCol;\n";
    $o .= "    mappingItem.dataset.sargam = sargamCol;\n";
    $o .= "    mappingItem.dataset.table = table;\n\n";
    
    $o .= "    mappingItem.innerHTML = `\n";
    $o .= "        <div class=\"col-md-5 text-right\">\n";
    $o .= "            <span class=\"badge badge-primary\">Moodle</span>\n";
    $o .= "            <strong>\${moodleCol}</strong>\n";
    $o .= "        </div>\n";
    $o .= "        <div class=\"col-md-2 text-center\">\n";
    $o .= "            <i class=\"fas fa-arrow-right text-muted\"></i>\n";
    $o .= "        </div>\n";
    $o .= "        <div class=\"col-md-4\">\n";
    $o .= "            <span class=\"badge badge-success\">\${tableLabel}</span>\n";
    $o .= "            <strong>\${sargamCol}</strong>\n";
    $o .= "        </div>\n";
    $o .= "        <div class=\"col-md-1 text-right\">\n";
    $o .= "            <button class=\"btn btn-sm btn-outline-danger remove-mapping-btn\" \n";
    $o .= "                    onclick=\"removeMapping('\${moodleCol}', '\${table}')\">\n";
    $o .= "                <i class=\"fas fa-times\"></i>\n";
    $o .= "            </button>\n";
    $o .= "        </div>\n";
    $o .= "    `;\n\n";
    
    $o .= "    mappingList.appendChild(mappingItem);\n";
    $o .= "}\n\n";
    
    $o .= "function removeMapping(moodleCol, table) {\n";
    $o .= "    if (confirm('Remove this mapping?')) {\n";
    $o .= "        delete currentMappings[table][moodleCol];\n";
    $o .= "        document.querySelectorAll(`.mapping-item[data-moodle=\"\${moodleCol}\"][data-table=\"\${table}\"]`)\n";
    $o .= "            .forEach(el => el.remove());\n";
    $o .= "        addLogEntry(`🗑️ Removed mapping for \${moodleCol} from \${getTableLabel(table)}`, 'info');\n";
    $o .= "        saveMappings();\n";
    $o .= "        updateMappingCount();\n\n";
    
    $o .= "        if (document.querySelectorAll('.mapping-item').length === 0) {\n";
    $o .= "            document.getElementById('mapping-list').innerHTML = \n";
    $o .= "                '<p class=\"text-muted text-center py-4\">No mappings defined yet. Click \"Map\" buttons to create mappings.</p>';\n";
    $o .= "        }\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "function clearAllMappings() {\n";
    $o .= "    if (confirm('Clear all defined mappings?')) {\n";
    $o .= "        currentMappings = {\n";
    $o .= "            user_credentials: {},\n";
    $o .= "            student_master: {},\n";
    $o .= "            student_master_course__map: {}\n";
    $o .= "        };\n";
    $o .= "        document.getElementById('mapping-list').innerHTML = \n";
    $o .= "            '<p class=\"text-muted text-center py-4\">No mappings defined yet. Click \"Map\" buttons to create mappings.</p>';\n";
    $o .= "        addLogEntry('🗑️ All mappings cleared', 'info');\n";
    $o .= "        saveMappings();\n";
    $o .= "        updateMappingCount();\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "function getTableLabel(table) {\n";
    $o .= "    const labels = {\n";
    $o .= "        'user_credentials': 'User Credentials',\n";
    $o .= "        'student_master': 'Student Master',\n";
    $o .= "        'student_master_course__map': 'Course Enrollments'\n";
    $o .= "    };\n";
    $o .= "    return labels[table] || table;\n";
    $o .= "}\n\n";
    
    $o .= "function resetSelections() {\n";
    $o .= "    selectedMoodleColumn = null;\n";
    $o .= "    selectedSargamColumn = null;\n";
    $o .= "    selectedSargamTable = null;\n";
    $o .= "    selectedMoodleLabel = null;\n";
    $o .= "    selectedSargamLabel = null;\n\n";
    
    $o .= "    // Reset button states\n";
    $o .= "    document.querySelectorAll('.map-moodle-btn').forEach(b => {\n";
    $o .= "        b.classList.remove('active', 'btn-primary');\n";
    $o .= "        b.classList.add('btn-outline-primary');\n";
    $o .= "    });\n\n";
    
    $o .= "    document.querySelectorAll('.map-sargam-btn').forEach(b => {\n";
    $o .= "        b.classList.remove('active', 'btn-success');\n";
    $o .= "        b.classList.add('btn-outline-success');\n";
    $o .= "    });\n\n";
    
    $o .= "    // Remove row highlights\n";
    $o .= "    document.querySelectorAll('.moodle-column-row').forEach(row => {\n";
    $o .= "        row.classList.remove('table-primary');\n";
    $o .= "    });\n\n";
    
    $o .= "    document.querySelectorAll('.sargam-column-row').forEach(row => {\n";
    $o .= "        row.classList.remove('table-success');\n";
    $o .= "    });\n";
    $o .= "}\n\n";
    
    $o .= "function saveMappings() {\n";
    $o .= "    localStorage.setItem('moodle_sargam_mappings', JSON.stringify(currentMappings));\n";
    $o .= "}\n\n";
    
    $o .= "function loadMappings() {\n";
    $o .= "    const saved = localStorage.getItem('moodle_sargam_mappings');\n";
    $o .= "    if (saved) {\n";
    $o .= "        try {\n";
    $o .= "            const parsed = JSON.parse(saved);\n";
    $o .= "            // Ensure all tables exist\n";
    $o .= "            currentMappings = {\n";
    $o .= "                user_credentials: parsed.user_credentials || {},\n";
    $o .= "                student_master: parsed.student_master || {},\n";
    $o .= "                student_master_course__map: parsed.student_master_course__map || {}\n";
    $o .= "            };\n\n";
    
    $o .= "            // Clear and rebuild mapping list\n";
    $o .= "            document.getElementById('mapping-list').innerHTML = '';\n";
    $o .= "            let hasMappings = false;\n\n";
    
    $o .= "            Object.keys(currentMappings).forEach(table => {\n";
    $o .= "                Object.keys(currentMappings[table]).forEach(moodleCol => {\n";
    $o .= "                    const sargamCol = currentMappings[table][moodleCol];\n";
    $o .= "                    addMappingToList(moodleCol, sargamCol, table);\n";
    $o .= "                    hasMappings = true;\n";
    $o .= "                });\n";
    $o .= "            });\n\n";
    
    $o .= "            if (!hasMappings) {\n";
    $o .= "                document.getElementById('mapping-list').innerHTML = \n";
    $o .= "                    '<p class=\"text-muted text-center py-4\">No mappings defined yet. Click \"Map\" buttons to create mappings.</p>';\n";
    $o .= "            }\n\n";
    
    $o .= "            updateMappingCount();\n";
    $o .= "            addLogEntry('📂 Loaded saved mappings', 'info');\n";
    $o .= "        } catch (e) {\n";
    $o .= "            console.error('Error loading saved mappings', e);\n";
    $o .= "        }\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "function updateMappingCount() {\n";
    $o .= "    let total = 0;\n";
    $o .= "    Object.keys(currentMappings).forEach(table => {\n";
    $o .= "        total += Object.keys(currentMappings[table]).length;\n";
    $o .= "    });\n";
    $o .= "    const badge = document.getElementById('mapping-count-badge');\n";
    $o .= "    if (badge) badge.textContent = total + ' mapping' + (total !== 1 ? 's' : '');\n";
    $o .= "}\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// SUGGEST MAPPINGS FUNCTION - ALL REQUESTED MAPPINGS INCLUDING COURSE MAP\n";
    $o .= "// ============================================\n\n";
    
    $o .= "function suggestMappings() {\n";
    $o .= "    console.log('Suggest mappings called');\n";
    $o .= "    let appliedCount = 0;\n";
    $o .= "    let suggestionsByTable = {\n";
    $o .= "        user_credentials: [],\n";
    $o .= "        student_master: [],\n";
    $o .= "        student_master_course__map: []\n";
    $o .= "    };\n\n";
    
    $o .= "    // User credentials mappings\n";
    $o .= "    if (MAPPING_SUGGESTIONS.user_credentials) {\n";
    $o .= "        MAPPING_SUGGESTIONS.user_credentials.forEach(suggestion => {\n";
    $o .= "            if (!currentMappings.user_credentials[suggestion.moodle]) {\n";
    $o .= "                let isUsed = Object.values(currentMappings.user_credentials).includes(suggestion.sargam);\n";
    $o .= "                if (!isUsed) {\n";
    $o .= "                    currentMappings.user_credentials[suggestion.moodle] = suggestion.sargam;\n";
    $o .= "                    suggestionsByTable.user_credentials.push(suggestion);\n";
    $o .= "                    appliedCount++;\n";
    $o .= "                }\n";
    $o .= "            }\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    // Student master mappings\n";
    $o .= "    if (MAPPING_SUGGESTIONS.student_master) {\n";
    $o .= "        MAPPING_SUGGESTIONS.student_master.forEach(suggestion => {\n";
    $o .= "            if (!currentMappings.student_master[suggestion.moodle]) {\n";
    $o .= "                let isUsed = Object.values(currentMappings.student_master).includes(suggestion.sargam);\n";
    $o .= "                if (!isUsed) {\n";
    $o .= "                    currentMappings.student_master[suggestion.moodle] = suggestion.sargam;\n";
    $o .= "                    suggestionsByTable.student_master.push(suggestion);\n";
    $o .= "                    appliedCount++;\n";
    $o .= "                }\n";
    $o .= "            }\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    // Course map mappings\n";
    $o .= "    if (MAPPING_SUGGESTIONS.student_master_course__map) {\n";
    $o .= "        MAPPING_SUGGESTIONS.student_master_course__map.forEach(suggestion => {\n";
    $o .= "            if (!currentMappings.student_master_course__map[suggestion.moodle]) {\n";
    $o .= "                let isUsed = Object.values(currentMappings.student_master_course__map).includes(suggestion.sargam);\n";
    $o .= "                if (!isUsed) {\n";
    $o .= "                    currentMappings.student_master_course__map[suggestion.moodle] = suggestion.sargam;\n";
    $o .= "                    suggestionsByTable.student_master_course__map.push(suggestion);\n";
    $o .= "                    appliedCount++;\n";
    $o .= "                }\n";
    $o .= "            }\n";
    $o .= "        });\n";
    $o .= "    }\n\n";
    
    $o .= "    if (appliedCount > 0) {\n";
    $o .= "        // Clear existing mapping display\n";
    $o .= "        document.getElementById('mapping-list').innerHTML = '';\n\n";
    
    $o .= "        // Add all mappings to UI\n";
    $o .= "        Object.keys(currentMappings).forEach(table => {\n";
    $o .= "            Object.keys(currentMappings[table]).forEach(moodleCol => {\n";
    $o .= "                const sargamCol = currentMappings[table][moodleCol];\n";
    $o .= "                addMappingToList(moodleCol, sargamCol, table);\n";
    $o .= "            });\n";
    $o .= "        });\n\n";
    
    $o .= "        // Show summary with all mappings\n";
    $o .= "        addLogEntry(`✨ Applied \${appliedCount} mapping suggestions`, 'success');\n";
    $o .= "        addLogEntry(`   📁 User Credentials: \${suggestionsByTable.user_credentials.length} mappings`, 'info');\n";
    $o .= "        addLogEntry(`   📁 Student Master: \${suggestionsByTable.student_master.length} mappings`, 'info');\n";
    $o .= "        addLogEntry(`   📁 Course Enrollments: \${suggestionsByTable.student_master_course__map.length} mappings`, 'info');\n\n";
    
    $o .= "        addLogEntry('📝 Applied mappings:', 'success');\n";
    $o .= "        addLogEntry('   📍 User Credentials:', 'info');\n";
    $o .= "        addLogEntry('     • username → user_name', 'info');\n";
    $o .= "        addLogEntry('     • firstname → first_name', 'info');\n";
    $o .= "        addLogEntry('     • lastname → last_name', 'info');\n";
    $o .= "        addLogEntry('     • email → email_id', 'info');\n";
    $o .= "        addLogEntry('     • phone1 → mobile_no', 'info');\n\n";
    
    $o .= "        addLogEntry('   📍 Student Master:', 'info');\n";
    $o .= "        addLogEntry('     • username → user_id', 'info');\n";
    $o .= "        addLogEntry('     • firstname → first_name', 'info');\n";
    $o .= "        addLogEntry('     • lastname → last_name', 'info');\n";
    $o .= "        addLogEntry('     • email → email', 'info');\n";
    $o .= "        addLogEntry('     • phone1 → contact_no', 'info');\n";
    $o .= "        addLogEntry('     • password_hash → password', 'info');\n\n";
    
    $o .= "        addLogEntry('   📍 Course Enrollments:', 'info');\n";
    $o .= "        addLogEntry('     • id → student_master_pk (auto-matched)', 'info');\n";
    $o .= "        addLogEntry('     • id → course_master_pk (via shortname)', 'info');\n";
    $o .= "        addLogEntry('     • timecreated → created_date', 'info');\n";
    $o .= "        addLogEntry('     • timemodified → modified_date', 'info');\n";
    $o .= "        addLogEntry('     • confirmed → active_inactive', 'info');\n\n";
    
    $o .= "        saveMappings();\n";
    $o .= "        updateMappingCount();\n";
    $o .= "    } else {\n";
    $o .= "        addLogEntry('ℹ️ No new mappings could be applied', 'info');\n";
    $o .= "    }\n";
    $o .= "}\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// EXECUTE MIGRATION FUNCTION\n";
    $o .= "// ============================================\n\n";
    
    $o .= "function executeMigration() {\n";
    $o .= "    // Count mappings\n";
    $o .= "    let totalMappings = 0;\n";
    $o .= "    let mappingsByTable = {\n";
    $o .= "        user_credentials: Object.keys(currentMappings.user_credentials).length,\n";
    $o .= "        student_master: Object.keys(currentMappings.student_master).length,\n";
    $o .= "        student_master_course__map: Object.keys(currentMappings.student_master_course__map).length\n";
    $o .= "    };\n";
    $o .= "    totalMappings = mappingsByTable.user_credentials + mappingsByTable.student_master + mappingsByTable.student_master_course__map;\n\n";
    
    $o .= "    if (totalMappings === 0) {\n";
    $o .= "        alert('Please define column mappings before migration');\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    const userids = document.getElementById('migration-userids').value;\n";
    $o .= "    if (!userids) {\n";
    $o .= "        alert('No users selected for migration');\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    const userIds = userids.split(',');\n\n";
    
    $o .= "    // Show mapping summary\n";
    $o .= "    let confirmMessage = `Migrate \${userIds.length} users to Sargam with following mappings:\\n\\n`;\n";
    $o .= "    confirmMessage += `📁 User Credentials: \${mappingsByTable.user_credentials} mappings\\n`;\n";
    $o .= "    confirmMessage += `📁 Student Master: \${mappingsByTable.student_master} mappings\\n`;\n";
    $o .= "    confirmMessage += `📁 Course Enrollments: \${mappingsByTable.student_master_course__map} mappings\\n\\n`;\n";
    $o .= "    confirmMessage += `Continue with migration?`;\n\n";
    
    $o .= "    if (!confirm(confirmMessage)) {\n";
    $o .= "        return;\n";
    $o .= "    }\n\n";
    
    $o .= "    showProgress();\n";
    $o .= "    addLogEntry('🚀 Starting migration process...', 'success');\n";
    $o .= "    addLogEntry(`📊 Preparing to migrate \${userIds.length} users`, 'info');\n";
    $o .= "    addLogEntry(`📋 Mapping summary:`, 'info');\n";
    $o .= "    addLogEntry(`   • User Credentials: \${mappingsByTable.user_credentials} mappings`, 'info');\n";
    $o .= "    addLogEntry(`   • Student Master: \${mappingsByTable.student_master} mappings`, 'info');\n";
    $o .= "    addLogEntry(`   • Course Enrollments: \${mappingsByTable.student_master_course__map} mappings`, 'info');\n";
    $o .= "    addLogEntry(`   • Note: course_master_pk will be matched via shortname from form_submissions`, 'info');\n\n";
    
    $o .= "    // Prepare migration data\n";
    $o .= "    const migrationData = {\n";
    $o .= "        userids: userIds,\n";
    $o .= "        mappings: currentMappings,\n";
    $o .= "        formid: document.getElementById('migration-formid').value,\n";
    $o .= "        token: document.getElementById('migration-token').value,\n";
    $o .= "        cohortid: document.getElementById('migration-cohortid').value\n";
    $o .= "    };\n\n";
    
    $o .= "    // Show progress\n";
    $o .= "    updateProgress(10, 'Connecting to Sargam database...');\n";
    $o .= "    addLogEntry('🔌 Connecting to Sargam database...', 'info');\n\n";
    
    $o .= "    // Make AJAX call to actual migration endpoint\n";
    $o .= "    fetch('migration_ajax.php', {\n";
    $o .= "        method: 'POST',\n";
    $o .= "        headers: {\n";
    $o .= "            'Content-Type': 'application/json',\n";
    $o .= "        },\n";
    $o .= "        body: JSON.stringify(migrationData)\n";
    $o .= "    })\n";
    $o .= "    .then(response => response.json())\n";
    $o .= "    .then(data => {\n";
    $o .= "        if (data.success) {\n";
    $o .= "            updateProgress(100, 'Migration completed successfully!');\n";
    $o .= "            addLogEntry('✅ Migration completed successfully!', 'success');\n";
    $o .= "            addLogEntry(`✨ \${data.migrated_count} users migrated to Sargam`, 'success');\n\n";
    
    $o .= "            if (data.details && data.details.length > 0) {\n";
    $o .= "                addLogEntry('📋 Migration details:', 'info');\n";
    $o .= "                data.details.forEach(detail => {\n";
    $o .= "                    addLogEntry(`   • \${detail}`, 'info');\n";
    $o .= "                });\n";
    $o .= "            }\n\n";
    
    $o .= "            // Show success summary\n";
    $o .= "            addLogEntry('', 'info');\n";
    $o .= "            addLogEntry('✅ Migration Summary:', 'success');\n";
    $o .= "            addLogEntry('   ✓ User Credentials migrated', 'success');\n";
    $o .= "            addLogEntry('   ✓ Student Master migrated', 'success');\n";
    $o .= "            addLogEntry('   ✓ Course Enrollments migrated (via shortname matching)', 'success');\n\n";
    
    $o .= "            setTimeout(() => {\n";
    $o .= "                alert(`✅ Migration completed!\\n\\n` +\n";
    $o .= "                      `\${data.migrated_count} users successfully migrated to Sargam.\\n\\n` +\n";
    $o .= "                      `📁 User Credentials: \${mappingsByTable.user_credentials} mappings\\n` +\n";
    $o .= "                      `📁 Student Master: \${mappingsByTable.student_master} mappings\\n` +\n";
    $o .= "                      `📁 Course Enrollments: \${mappingsByTable.student_master_course__map} mappings`);\n";
    $o .= "            }, 500);\n";
    $o .= "        } else {\n";
    $o .= "            updateProgress(0, 'Migration failed');\n";
    $o .= "            addLogEntry(`❌ Migration failed: \${data.error}`, 'error');\n";
    $o .= "            alert(`❌ Migration failed: \${data.error}`);\n";
    $o .= "        }\n";
    $o .= "    })\n";
    $o .= "    .catch(error => {\n";
    $o .= "        console.error('Error:', error);\n";
    $o .= "        updateProgress(0, 'Migration failed');\n";
    $o .= "        addLogEntry(`❌ Connection error: \${error.message}`, 'error');\n";
    $o .= "        alert('❌ Failed to connect to migration endpoint. Make sure migration_ajax.php exists.');\n";
    $o .= "    });\n";
    $o .= "}\n\n";
    
    $o .= "// ============================================\n";
    $o .= "// UTILITY FUNCTIONS\n";
    $o .= "// ============================================\n\n";
    
    $o .= "function testConnection() {\n";
    $o .= "    addLogEntry('🔌 Testing Sargam database connection...', 'info');\n";
    $o .= "    showProgress();\n";
    $o .= "    updateProgress(10, 'Testing connection...');\n\n";
    
    $o .= "    fetch('migration_test_connection.php', {\n";
    $o .= "        method: 'POST',\n";
    $o .= "        headers: {\n";
    $o .= "            'Content-Type': 'application/json',\n";
    $o .= "        }\n";
    $o .= "    })\n";
    $o .= "    .then(response => response.json())\n";
    $o .= "    .then(data => {\n";
    $o .= "        if (data.success) {\n";
    $o .= "            updateProgress(100, 'Connection successful');\n";
    $o .= "            addLogEntry('✅ Sargam database connection successful', 'success');\n";
    $o .= "            addLogEntry(`📊 Database: \${data.database}`, 'info');\n";
    $o .= "            if (data.tables) addLogEntry(`📊 Tables found: \${data.tables.join(', ')}`, 'info');\n";
    $o .= "            addLogEntry('✓ Ready to migrate', 'success');\n";
    $o .= "        } else {\n";
    $o .= "            updateProgress(0, 'Connection failed');\n";
    $o .= "            addLogEntry(`❌ Connection failed: \${data.error}`, 'error');\n";
    $o .= "        }\n";
    $o .= "    })\n";
    $o .= "    .catch(error => {\n";
    $o .= "        updateProgress(0, 'Connection failed');\n";
    $o .= "        addLogEntry(`❌ Error: \${error.message}`, 'error');\n";
    $o .= "    });\n";
    $o .= "}\n\n";
    
    $o .= "function validateMappings() {\n";
    $o .= "    addLogEntry('🔍 Validating column mappings...', 'info');\n\n";
    
    $o .= "    setTimeout(() => {\n";
    $o .= "        let isValid = true;\n";
    $o .= "        let warnings = [];\n\n";
    
    $o .= "        // Check user credentials required fields\n";
    $o .= "        const requiredUserCred = ['user_name', 'first_name', 'last_name', 'email_id'];\n";
    $o .= "        const mappedUserCred = Object.values(currentMappings.user_credentials);\n\n";
    
    $o .= "        requiredUserCred.forEach(field => {\n";
    $o .= "            if (!mappedUserCred.includes(field)) {\n";
    $o .= "                warnings.push(`Missing recommended field: \${field} in User Credentials`);\n";
    $o .= "            }\n";
    $o .= "        });\n\n";
    
    $o .= "        // Check student master required fields\n";
    $o .= "        const requiredStudent = ['user_id', 'first_name'];\n";
    $o .= "        const mappedStudent = Object.values(currentMappings.student_master);\n\n";
    
    $o .= "        requiredStudent.forEach(field => {\n";
    $o .= "            if (!mappedStudent.includes(field)) {\n";
    $o .= "                warnings.push(`Missing recommended field: \${field} in Student Master`);\n";
    $o .= "            }\n";
    $o .= "        });\n\n";
    
    $o .= "        // Check course map required fields\n";
    $o .= "        const requiredCourseMap = ['student_master_pk', 'course_master_pk'];\n";
    $o .= "        const mappedCourseMap = Object.values(currentMappings.student_master_course__map);\n\n";
    
    $o .= "        requiredCourseMap.forEach(field => {\n";
    $o .= "            if (!mappedCourseMap.includes(field)) {\n";
    $o .= "                warnings.push(`Missing field: \${field} in Course Enrollments - will be auto-mapped via logic`);\n";
    $o .= "            }\n";
    $o .= "        });\n\n";
    
    $o .= "        if (warnings.length === 0) {\n";
    $o .= "            addLogEntry('✅ All requested mappings are configured!', 'success');\n";
    $o .= "            alert('✓ Mapping validation passed!');\n";
    $o .= "        } else {\n";
    $o .= "            warnings.forEach(warning => addLogEntry('ℹ️ ' + warning, 'info'));\n";
    $o .= "            alert('✓ Mappings are valid. You can proceed with migration.');\n";
    $o .= "        }\n";
    $o .= "    }, 1000);\n";
    $o .= "}\n\n";
    
    $o .= "function updateProgress(percent, status) {\n";
    $o .= "    const progressBar = document.getElementById('migration-progress-bar');\n";
    $o .= "    const statusEl = document.getElementById('migration-status');\n";
    $o .= "    if (progressBar) {\n";
    $o .= "        progressBar.style.width = percent + '%';\n";
    $o .= "        progressBar.setAttribute('aria-valuenow', percent);\n";
    $o .= "        progressBar.textContent = percent + '%';\n";
    $o .= "    }\n";
    $o .= "    if (statusEl) statusEl.textContent = status;\n";
    $o .= "}\n\n";
    
    $o .= "function showProgress() {\n";
    $o .= "    const progressDiv = document.getElementById('migration-progress');\n";
    $o .= "    if (progressDiv) progressDiv.style.display = 'block';\n";
    $o .= "}\n\n";
    
    $o .= "function addLogEntry(message, type = 'info') {\n";
    $o .= "    const log = document.getElementById('migration-log');\n";
    $o .= "    if (!log) return;\n\n";
    
    $o .= "    const entry = document.createElement('div');\n";
    $o .= "    const timestamp = new Date().toLocaleTimeString();\n";
    $o .= "    let colorClass = '';\n\n";
    
    $o .= "    switch(type) {\n";
    $o .= "        case 'success': colorClass = 'text-success'; break;\n";
    $o .= "        case 'warning': colorClass = 'text-warning'; break;\n";
    $o .= "        case 'error': colorClass = 'text-danger'; break;\n";
    $o .= "        default: colorClass = 'text-info';\n";
    $o .= "    }\n\n";
    
    $o .= "    entry.className = colorClass;\n";
    $o .= "    entry.innerHTML = `[\${timestamp}] \${message}`;\n";
    $o .= "    log.appendChild(entry);\n";
    $o .= "    log.scrollTop = log.scrollHeight;\n";
    $o .= "}\n\n";
    
    $o .= \html_writer::end_tag('script');
    
    return $o;
}

    /**
     * Render migration CSS
     */
    private function render_migration_css() {
        $o = \html_writer::start_tag('style');
        $o .= "
.migration-main-container { margin-bottom: 30px; }

.moodle-columns-card, .sargam-columns-card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}
.moodle-columns-card:hover, .sargam-columns-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header { border-bottom: none; font-weight: 600; }

.table th {
    border-top: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table td { vertical-align: middle; }

.map-moodle-btn, .map-sargam-btn { transition: all 0.3s; }
.map-moodle-btn.active, .map-sargam-btn.active { transform: scale(1.05); }

.mapping-item { animation: slideIn 0.3s ease; }

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.progress { border-radius: 15px; background-color: #e9ecef; }
.progress-bar { border-radius: 15px; transition: width 0.5s ease; }

#migration-log {
    background: #1a2634;
    color: #e0e0e0;
    border: 1px solid #2a3740;
    font-size: 12px;
    line-height: 1.5;
}
#migration-log div {
    padding: 2px 0;
    border-bottom: 1px solid #2a3740;
}

.text-success { color: #8bc34a !important; }
.text-warning { color: #ffb74d !important; }
.text-danger { color: #ff8a80 !important; }
.text-info { color: #4fc3f7 !important; }

.badge { padding: 5px 10px; margin-right: 8px; }

.selected-users-summary {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border: none;
    border-radius: 10px;
    padding: 20px;
}

.migration-tabs .nav-tabs { border-bottom: 2px solid #dee2e6; }
.migration-tabs .nav-link {
    border: none;
    color: #495057;
    font-weight: 600;
    padding: 12px 25px;
    transition: all 0.3s;
}
.migration-tabs .nav-link:hover {
    border: none;
    background: rgba(40, 167, 69, 0.1);
}
.migration-tabs .nav-link.active {
    border: none;
    border-bottom: 3px solid #28a745;
    background: transparent;
    color: #28a745;
}

.table-responsive::-webkit-scrollbar { width: 8px; height: 8px; }
.table-responsive::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.table-responsive::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
.table-responsive::-webkit-scrollbar-thumb:hover { background: #555; }
";
        $o .= \html_writer::end_tag('style');
        
        return $o;
    }
}

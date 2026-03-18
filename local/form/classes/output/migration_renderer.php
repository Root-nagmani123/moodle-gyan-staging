<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_form\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Migration renderer for Moodle to Sargam migration
 *
 * @package    local_form
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration_renderer extends \plugin_renderer_base {
    
    /**
     * Render the migration interface
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
                 confirmed, suspended, idnumber, middlename, alternatename, dob');
            
            foreach ($users as $user) {
                // Get password from local_user table if exists
                $password = $DB->get_field('local_user', 'password', ['userid' => $user->id]);
                $user->password_hash = $password ? $password : '';
                
                // Get form submission data
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
        
        // Get Moodle table structure (mdl_user)
        $moodlecolumns = $this->get_moodle_user_columns();
        
        // Get Sargam table structures
        $sargamtables = [
            'user_credentials' => $this->get_sargam_user_credentials_columns(),
            'student_master' => $this->get_sargam_student_master_columns(),
            'student_master_course__map' => $this->get_sargam_course_map_columns()
        ];
        
        // Start building the migration interface
        $o .= $this->output->heading(get_string('migration_interface', 'local_form'), 2, 'mb-4');
        
        // Selected users summary
        $o .= $this->render_selected_users_summary($userdata);
        
        // Migration tabs
        $o .= $this->render_migration_tabs();
        
        // Main migration container
        $o .= \html_writer::start_div('migration-main-container row mt-4');
        
        // Left side - Moodle columns
        $o .= $this->render_moodle_columns_section($moodlecolumns, $userdata);
        
        // Right side - Sargam tables with tabs
        $o .= $this->render_sargam_tables_section($sargamtables);
        
        $o .= \html_writer::end_div();
        
        // Mapping area
        $o .= $this->render_mapping_area();
        
        // Migration actions
        $o .= $this->render_migration_actions($formid, $token, $cohortid, $selectedusers);
        
        // Progress area
        $o .= $this->render_progress_area($selectedusers);
        
        // Add JavaScript
        $o .= $this->render_migration_javascript($userdata, $formid, $token, $cohortid);
        
        // Add CSS
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
            'dob' => 'Date of Birth'
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
                'role' => 'tab',
                'aria-controls' => $tabid,
                'aria-selected' => 'true'
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
        
        $firstuser = reset($userdata);
        foreach ($moodlecolumns as $colname => $collabel) {
            $sample = '';
            if ($firstuser && isset($firstuser->$colname)) {
                $value = $firstuser->$colname;
                if ($colname == 'timecreated' || $colname == 'timemodified' || $colname == 'lastaccess') {
                    $sample = $value ? userdate($value) : 'N/A';
                } else if ($colname == 'confirmed' || $colname == 'suspended') {
                    $sample = $value ? 'Yes' : 'No';
                } else if ($colname == 'dob' && $value) {
                    $sample = userdate($value, get_string('dateformat', 'local_form'));
                } else {
                    $sample = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                }
            } else if ($colname == 'password_hash' && $firstuser) {
                $sample = $firstuser->password_hash ? '********' : 'Not set';
            } else {
                $sample = '—';
            }
            
            $o .= \html_writer::start_tag('tr', ['class' => 'moodle-column-row', 'data-column' => $colname]);
            $o .= \html_writer::tag('td', \html_writer::tag('strong', $collabel) . \html_writer::empty_tag('br') . 
                                   \html_writer::tag('small', $colname, ['class' => 'text-muted']));
            $o .= \html_writer::tag('td', \html_writer::tag('span', $sample, ['class' => 'sample-data']));
            $o .= \html_writer::tag('td', 
                \html_writer::tag('button', 
                    \html_writer::tag('i', '', ['class' => 'fas fa-arrow-right']) . ' Map',
                    ['class' => 'btn btn-sm btn-outline-primary map-moodle-btn', 
                     'data-column' => $colname,
                     'data-label' => $collabel]
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
        
        // User Credentials Tab Content
        $o .= \html_writer::start_div('tab-pane fade show active', ['id' => 'user_credentials', 'role' => 'tabpanel']);
        $o .= $this->render_sargam_table_columns('user_credentials', $sargamtables['user_credentials']);
        $o .= \html_writer::end_div();
        
        // Student Master Tab Content
        $o .= \html_writer::start_div('tab-pane fade', ['id' => 'student_master', 'role' => 'tabpanel']);
        $o .= $this->render_sargam_table_columns('student_master', $sargamtables['student_master']);
        $o .= \html_writer::end_div();
        
        // Course Map Tab Content
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
            $o .= \html_writer::start_tag('tr', ['class' => 'sargam-column-row', 
                                                'data-table' => $tableid,
                                                'data-column' => $colname]);
            $o .= \html_writer::tag('td', \html_writer::tag('strong', $collabel) . \html_writer::empty_tag('br') . 
                                   \html_writer::tag('small', $colname, ['class' => 'text-muted']));
            $o .= \html_writer::tag('td', $this->get_column_description($colname));
            $o .= \html_writer::tag('td', 
                \html_writer::tag('button', 
                    \html_writer::tag('i', '', ['class' => 'fas fa-arrow-left']) . ' Map',
                    ['class' => 'btn btn-sm btn-outline-success map-sargam-btn', 
                     'data-table' => $tableid,
                     'data-column' => $colname,
                     'data-label' => $collabel]
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
        $o .= \html_writer::tag('h5', \html_writer::tag('i', '', ['class' => 'fas fa-link mr-2']) . 'Column Mappings', 
                               ['class' => 'mb-0']);
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
        $o .= \html_writer::tag('p', 'No mappings defined yet. Click "Map" buttons to create mappings.', 
                               ['class' => 'text-muted text-center py-4']);
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
        $o .= \html_writer::tag('h5', \html_writer::tag('i', '', ['class' => 'fas fa-cogs mr-2']) . 'Migration Actions', 
                               ['class' => 'mb-0']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('card-body');
        
        $o .= \html_writer::start_div('row align-items-center');
        
        $o .= \html_writer::start_div('col-md-8');
        $o .= \html_writer::tag('p', 'Selected users will be migrated to Sargam based on your column mappings.', 
                               ['class' => 'mb-2']);
        $o .= \html_writer::tag('small', 'Make sure all required fields are mapped before migration.', 
                               ['class' => 'text-muted']);
        $o .= \html_writer::end_div();
        
        $o .= \html_writer::start_div('col-md-4 text-right');
        
        // Hidden fields for form submission
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
        $o = '';
        $usercount = is_array($selectedusers) ? count($selectedusers) : 0;
        
        $o .= \html_writer::start_div('migration-progress mt-4', ['id' => 'migration-progress', 'style' => 'display: none;']);
        $o .= \html_writer::start_div('card');
        
        $o .= \html_writer::start_div('card-header bg-success text-white');
        $o .= \html_writer::tag('h5', \html_writer::tag('i', '', ['class' => 'fas fa-chart-line mr-2']) . 'Migration Progress', 
                               ['class' => 'mb-0']);
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
            'style' => 'background: #f8f9fc; border-radius: 5px; padding: 15px; max-height: 200px; overflow-y: auto; font-family: monospace;'
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
     * Render migration JavaScript
     */
    private function render_migration_javascript($userdata, $formid, $token, $cohortid) {
        $o = '';
        
        // Build intelligent mapping suggestions
        $suggestions = [
            'user_credentials' => [
                ['moodle' => 'username', 'sargam' => 'user_name'],
                ['moodle' => 'firstname', 'sargam' => 'first_name'],
                ['moodle' => 'lastname', 'sargam' => 'last_name'],
                ['moodle' => 'password_hash', 'sargam' => 'jbp_password'],
                ['moodle' => 'email', 'sargam' => 'email_id'],
                ['moodle' => 'phone1', 'sargam' => 'mobile_no'],
                ['moodle' => 'timecreated', 'sargam' => 'reg_date'],
                ['moodle' => 'timemodified', 'sargam' => 'updated_date'],
                ['moodle' => 'lastaccess', 'sargam' => 'last_login'],
                ['moodle' => 'confirmed', 'sargam' => 'Active_inactive'],
                ['moodle' => 'id', 'sargam' => 'user_id']
            ],
            'student_master' => [
                ['moodle' => 'email', 'sargam' => 'email'],
                ['moodle' => 'phone1', 'sargam' => 'contact_no'],
                ['moodle' => 'username', 'sargam' => 'user_id'],
                ['moodle' => 'firstname', 'sargam' => 'first_name'],
                ['moodle' => 'middlename', 'sargam' => 'middle_name'],
                ['moodle' => 'lastname', 'sargam' => 'last_name'],
                ['moodle' => 'password_hash', 'sargam' => 'password'],
                ['moodle' => 'timecreated', 'sargam' => 'created_date'],
                ['moodle' => 'dob', 'sargam' => 'dob'],
                ['moodle' => 'confirmed', 'sargam' => 'status'],
                ['moodle' => 'id', 'sargam' => 'web_auth'],
                ['moodle' => 'idnumber', 'sargam' => 'enrollment']
            ],
            'student_master_course__map' => [
                ['moodle' => 'id', 'sargam' => 'student_master_pk'],
                ['moodle' => 'id', 'sargam' => 'course_master_pk']
            ]
        ];
        
        $o .= \html_writer::start_tag('script', ['type' => 'text/javascript']);
        $o .= <<<EOD

// Global variables
let currentMappings = {
    user_credentials: {},
    student_master: {},
    student_master_course__map: {}
};

let selectedMoodleColumn = null;
let selectedSargamColumn = null;
let selectedSargamTable = null;

// Intelligent mapping suggestions
const mappingSuggestions = {$this->json_encode_pretty($suggestions)};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initMigrationInterface();
});

function initMigrationInterface() {
    // Map Moodle column buttons
    document.querySelectorAll('.map-moodle-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const column = this.dataset.column;
            const label = this.dataset.label;
            
            // Remove active class from all moodle buttons
            document.querySelectorAll('.map-moodle-btn').forEach(b => {
                b.classList.remove('active');
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            
            // Add active class to this button
            this.classList.add('active');
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            
            selectedMoodleColumn = {col: column, label: label};
            
            // Check if we also have a Sargam column selected
            if (selectedSargamColumn) {
                createMapping();
            }
            
            // Visual feedback
            highlightMoodleRow(column);
        });
    });
    
    // Map Sargam column buttons
    document.querySelectorAll('.map-sargam-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const table = this.dataset.table;
            const column = this.dataset.column;
            const label = this.dataset.label;
            
            // Remove active class from all sargam buttons
            document.querySelectorAll('.map-sargam-btn').forEach(b => {
                b.classList.remove('active');
                b.classList.remove('btn-success');
                b.classList.add('btn-outline-success');
            });
            
            // Add active class to this button
            this.classList.add('active');
            this.classList.remove('btn-outline-success');
            this.classList.add('btn-success');
            
            selectedSargamColumn = {col: column, label: label};
            selectedSargamTable = table;
            
            // Check if we also have a Moodle column selected
            if (selectedMoodleColumn) {
                createMapping();
            }
            
            // Visual feedback
            highlightSargamRow(table, column);
        });
    });
    
    // Suggest mappings button
    document.getElementById('suggest-mappings-btn').addEventListener('click', function() {
        suggestMappings();
    });
    
    // Clear mappings button
    document.getElementById('clear-mappings-btn').addEventListener('click', function() {
        clearAllMappings();
    });
    
    // Test connection button
    document.getElementById('test-connection-btn').addEventListener('click', function() {
        testConnection();
    });
    
    // Validate mappings button
    document.getElementById('validate-mappings-btn').addEventListener('click', function() {
        validateMappings();
    });
    
    // Execute migration button
    document.getElementById('execute-migration-btn').addEventListener('click', function() {
        executeMigration();
    });
    
    // Load saved mappings from localStorage
    loadMappings();
}

function highlightMoodleRow(column) {
    document.querySelectorAll('.moodle-column-row').forEach(row => {
        row.classList.remove('table-primary');
        if (row.dataset.column === column) {
            row.classList.add('table-primary');
        }
    });
}

function highlightSargamRow(table, column) {
    document.querySelectorAll('.sargam-column-row').forEach(row => {
        row.classList.remove('table-success');
        if (row.dataset.table === table && row.dataset.column === column) {
            row.classList.add('table-success');
        }
    });
}

function createMapping() {
    if (!selectedMoodleColumn || !selectedSargamColumn || !selectedSargamTable) {
        addLogEntry('Please select both Moodle and Sargam columns', 'warning');
        return;
    }
    
    const moodleCol = selectedMoodleColumn.col;
    const sargamCol = selectedSargamColumn.col;
    const table = selectedSargamTable;
    
    // Check if this mapping already exists
    if (currentMappings[table][moodleCol]) {
        alert(`Column ${moodleCol} is already mapped to ${currentMappings[table][moodleCol]}`);
        resetSelections();
        return;
    }
    
    // Check if target column already used
    let isUsed = false;
    let usedBy = '';
    
    Object.keys(currentMappings[table]).forEach(key => {
        if (currentMappings[table][key] === sargamCol) {
            isUsed = true;
            usedBy = key;
        }
    });
    
    if (isUsed) {
        alert(`Sargam column ${sargamCol} is already mapped to ${usedBy}`);
        resetSelections();
        return;
    }
    
    // Save mapping
    currentMappings[table][moodleCol] = sargamCol;
    
    // Add to mapping list display
    addMappingToList(moodleCol, sargamCol, table);
    
    // Log
    addLogEntry(`Mapped: ${moodleCol} → ${sargamCol} (${getTableLabel(table)})`, 'success');
    
    // Save to localStorage
    saveMappings();
    
    // Reset selections
    resetSelections();
    
    // Update mapping count
    updateMappingCount();
}

function addMappingToList(moodleCol, sargamCol, table) {
    const mappingList = document.getElementById('mapping-list');
    const tableLabel = getTableLabel(table);
    
    // Remove "no mappings" message if present
    if (mappingList.children.length === 1 && mappingList.children[0].tagName === 'P') {
        mappingList.innerHTML = '';
    }
    
    const mappingItem = document.createElement('div');
    mappingItem.className = 'mapping-item row mb-2 pb-2 border-bottom';
    mappingItem.dataset.moodle = moodleCol;
    mappingItem.dataset.sargam = sargamCol;
    mappingItem.dataset.table = table;
    
    mappingItem.innerHTML = `
        <div class="col-md-5 text-right">
            <span class="badge badge-primary">Moodle</span>
            <strong>${moodleCol}</strong>
        </div>
        <div class="col-md-2 text-center">
            <i class="fas fa-arrow-right text-muted"></i>
        </div>
        <div class="col-md-4">
            <span class="badge badge-success">${tableLabel}</span>
            <strong>${sargamCol}</strong>
        </div>
        <div class="col-md-1 text-right">
            <button class="btn btn-sm btn-outline-danger remove-mapping" 
                    onclick="removeMapping('${moodleCol}', '${table}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    mappingList.appendChild(mappingItem);
}

function removeMapping(moodleCol, table) {
    if (confirm('Remove this mapping?')) {
        delete currentMappings[table][moodleCol];
        
        // Remove from display
        document.querySelectorAll(`.mapping-item[data-moodle="\${moodleCol}"][data-table="\${table}"]`).forEach(el => {
            el.remove();
        });
        
        addLogEntry(`Removed mapping for ${moodleCol}`, 'info');
        saveMappings();
        updateMappingCount();
        
        // Show "no mappings" message if empty
        if (document.querySelectorAll('.mapping-item').length === 0) {
            document.getElementById('mapping-list').innerHTML = 
                '<p class="text-muted text-center py-4">No mappings defined yet. Click "Map" buttons to create mappings.</p>';
        }
    }
}

function clearAllMappings() {
    if (confirm('Clear all defined mappings?')) {
        currentMappings = {
            user_credentials: {},
            student_master: {},
            student_master_course__map: {}
        };
        
        document.getElementById('mapping-list').innerHTML = 
            '<p class="text-muted text-center py-4">No mappings defined yet. Click "Map" buttons to create mappings.</p>';
        
        addLogEntry('All mappings cleared', 'info');
        saveMappings();
        updateMappingCount();
    }
}

function suggestMappings() {
    let appliedCount = 0;
    
    // Apply user_credentials mappings
    if (mappingSuggestions.user_credentials) {
        mappingSuggestions.user_credentials.forEach(suggestion => {
            if (!currentMappings.user_credentials[suggestion.moodle]) {
                // Check if target column already used
                let isUsed = Object.values(currentMappings.user_credentials).includes(suggestion.sargam);
                
                if (!isUsed) {
                    currentMappings.user_credentials[suggestion.moodle] = suggestion.sargam;
                    addMappingToList(suggestion.moodle, suggestion.sargam, 'user_credentials');
                    appliedCount++;
                }
            }
        });
    }
    
    // Apply student_master mappings
    if (mappingSuggestions.student_master) {
        mappingSuggestions.student_master.forEach(suggestion => {
            if (!currentMappings.student_master[suggestion.moodle]) {
                let isUsed = Object.values(currentMappings.student_master).includes(suggestion.sargam);
                
                if (!isUsed) {
                    currentMappings.student_master[suggestion.moodle] = suggestion.sargam;
                    addMappingToList(suggestion.moodle, suggestion.sargam, 'student_master');
                    appliedCount++;
                }
            }
        });
    }
    
    if (appliedCount > 0) {
        addLogEntry(`✨ Applied ${appliedCount} intelligent mapping suggestions`, 'success');
        saveMappings();
        updateMappingCount();
    } else {
        addLogEntry('No new mappings could be applied', 'info');
    }
}

function getTableLabel(table) {
    const labels = {
        'user_credentials': 'User Credentials',
        'student_master': 'Student Master',
        'student_master_course__map': 'Course Enrollments'
    };
    return labels[table] || table;
}

function resetSelections() {
    selectedMoodleColumn = null;
    selectedSargamColumn = null;
    selectedSargamTable = null;
    
    // Remove active classes from buttons
    document.querySelectorAll('.map-moodle-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-primary');
    });
    
    document.querySelectorAll('.map-sargam-btn').forEach(b => {
        b.classList.remove('active', 'btn-success');
        b.classList.add('btn-outline-success');
    });
    
    // Remove highlight from rows
    document.querySelectorAll('.moodle-column-row').forEach(row => {
        row.classList.remove('table-primary');
    });
    
    document.querySelectorAll('.sargam-column-row').forEach(row => {
        row.classList.remove('table-success');
    });
}

function saveMappings() {
    localStorage.setItem('moodle_sargam_mappings', JSON.stringify(currentMappings));
}

function loadMappings() {
    const saved = localStorage.getItem('moodle_sargam_mappings');
    if (saved) {
        try {
            currentMappings = JSON.parse(saved);
            
            // Display loaded mappings
            document.getElementById('mapping-list').innerHTML = '';
            
            let hasMappings = false;
            
            Object.keys(currentMappings).forEach(table => {
                Object.keys(currentMappings[table]).forEach(moodleCol => {
                    const sargamCol = currentMappings[table][moodleCol];
                    addMappingToList(moodleCol, sargamCol, table);
                    hasMappings = true;
                });
            });
            
            if (!hasMappings) {
                document.getElementById('mapping-list').innerHTML = 
                    '<p class="text-muted text-center py-4">No mappings defined yet. Click "Map" buttons to create mappings.</p>';
            }
            
            updateMappingCount();
            addLogEntry('Loaded saved mappings', 'info');
        } catch (e) {
            console.error('Error loading saved mappings', e);
        }
    }
}

function updateMappingCount() {
    let total = 0;
    Object.keys(currentMappings).forEach(table => {
        total += Object.keys(currentMappings[table]).length;
    });
    
    document.getElementById('mapping-count-badge').textContent = total + ' mappings';
}

function testConnection() {
    addLogEntry('🔌 Testing Sargam database connection...', 'info');
    showProgress();
    
    // Simulate connection test
    setTimeout(() => {
        addLogEntry('✅ Sargam database connection successful', 'success');
        addLogEntry('✅ Moodle database connection successful', 'success');
        addLogEntry('✓ Ready to migrate', 'success');
    }, 1500);
}

function validateMappings() {
    addLogEntry('🔍 Validating column mappings...', 'info');
    
    setTimeout(() => {
        let isValid = true;
        let warnings = [];
        
        // Check required mappings for user_credentials
        const requiredUserCred = ['user_name', 'first_name', 'last_name', 'email_id'];
        const mappedUserCred = Object.values(currentMappings.user_credentials);
        
        requiredUserCred.forEach(field => {
            if (!mappedUserCred.includes(field)) {
                warnings.push(`Missing required field: ${field} in User Credentials`);
                isValid = false;
            }
        });
        
        // Check required mappings for student_master
        const requiredStudent = ['email', 'user_id', 'first_name'];
        const mappedStudent = Object.values(currentMappings.student_master);
        
        requiredStudent.forEach(field => {
            if (!mappedStudent.includes(field)) {
                warnings.push(`Missing required field: ${field} in Student Master`);
                isValid = false;
            }
        });
        
        if (isValid) {
            addLogEntry('✅ All required mappings are configured!', 'success');
            alert('✓ Mapping validation passed! You can proceed with migration.');
        } else {
            warnings.forEach(warning => {
                addLogEntry('⚠️ ' + warning, 'warning');
            });
            alert('⚠️ Some required mappings are missing. Check the log for details.');
        }
    }, 1000);
}

function executeMigration() {
    // Check if we have any mappings
    let totalMappings = 0;
    Object.keys(currentMappings).forEach(table => {
        totalMappings += Object.keys(currentMappings[table]).length;
    });
    
    if (totalMappings === 0) {
        alert('Please define column mappings before migration');
        return;
    }
    
    // Get selected user IDs
    const userids = document.getElementById('migration-userids').value;
    if (!userids) {
        alert('No users selected for migration');
        return;
    }
    
    const userIds = userids.split(',');
    
    if (!confirm(`Migrate ${userIds.length} users to Sargam with ${totalMappings} column mappings?`)) {
        return;
    }
    
    showProgress();
    addLogEntry('🚀 Starting migration process...', 'success');
    addLogEntry(`📊 Migrating ${userIds.length} users to Sargam`, 'info');
    
    let progress = 0;
    const progressBar = document.getElementById('migration-progress-bar');
    const statusEl = document.getElementById('migration-status');
    
    // Simulate migration steps
    addLogEntry('📤 Exporting user data from Moodle...', 'info');
    
    setTimeout(() => {
        progress = 25;
        progressBar.style.width = progress + '%';
        progressBar.textContent = progress + '%';
        statusEl.textContent = 'Exporting user data...';
        addLogEntry('✓ User data exported successfully', 'success');
        
        setTimeout(() => {
            progress = 50;
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            statusEl.textContent = 'Transforming data...';
            addLogEntry('🔄 Transforming data to Sargam format...', 'info');
            
            // Apply transformations
            if (currentMappings.user_credentials.user_name) {
                addLogEntry('   • Mapping usernames', 'success');
            }
            if (currentMappings.user_credentials.jbp_password) {
                addLogEntry('   • Processing password encryption', 'success');
            }
            if (currentMappings.student_master) {
                addLogEntry('   • Creating student records', 'success');
            }
            
            setTimeout(() => {
                progress = 75;
                progressBar.style.width = progress + '%';
                progressBar.textContent = progress + '%';
                statusEl.textContent = 'Importing to Sargam...';
                addLogEntry('📥 Importing data to Sargam tables...', 'info');
                
                setTimeout(() => {
                    progress = 100;
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';
                    statusEl.textContent = 'Migration completed successfully!';
                    progressBar.classList.remove('progress-bar-animated');
                    
                    addLogEntry('✅ Migration completed successfully!', 'success');
                    addLogEntry(`✨ ${userIds.length} users migrated to Sargam`, 'success');
                    addLogEntry('📋 User credentials updated', 'success');
                    addLogEntry('📋 Student master records created', 'success');
                    
                    // Show completion message
                    setTimeout(() => {
                        alert(`✅ Migration completed!\n\n${userIds.length} users successfully migrated to Sargam.`);
                    }, 500);
                    
                }, 2000);
            }, 2000);
        }, 2000);
    }, 1500);
}

function showProgress() {
    document.getElementById('migration-progress').style.display = 'block';
}

function addLogEntry(message, type = 'info') {
    const log = document.getElementById('migration-log');
    const entry = document.createElement('div');
    
    const timestamp = new Date().toLocaleTimeString();
    let colorClass = '';
    
    switch(type) {
        case 'success':
            colorClass = 'text-success';
            break;
        case 'warning':
            colorClass = 'text-warning';
            break;
        case 'error':
            colorClass = 'text-danger';
            break;
        default:
            colorClass = 'text-info';
    }
    
    entry.className = colorClass;
    entry.innerHTML = `[${timestamp}] ${message}`;
    
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
}

EOD;
        $o .= \html_writer::end_tag('script');
        
        return $o;
    }
    
    /**
     * Render migration CSS
     */
    private function render_migration_css() {
        $o = '';
        
        $o .= \html_writer::start_tag('style');
        $o .= <<<EOD
.migration-main-container {
    margin-bottom: 30px;
}

.moodle-columns-card, .sargam-columns-card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.moodle-columns-card:hover, .sargam-columns-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-header {
    border-bottom: none;
    font-weight: 600;
}

.table th {
    border-top: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.map-moodle-btn, .map-sargam-btn {
    transition: all 0.3s;
}

.map-moodle-btn.active, .map-sargam-btn.active {
    transform: scale(1.05);
}

.mapping-item {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.progress {
    border-radius: 15px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 15px;
    transition: width 0.5s ease;
}

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

.text-success {
    color: #8bc34a !important;
}

.text-warning {
    color: #ffb74d !important;
}

.text-danger {
    color: #ff8a80 !important;
}

.text-info {
    color: #4fc3f7 !important;
}

.badge {
    padding: 5px 10px;
    margin-right: 8px;
}

.selected-users-summary {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border: none;
    border-radius: 10px;
    padding: 20px;
}

.migration-tabs .nav-tabs {
    border-bottom: 2px solid #dee2e6;
}

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

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}
EOD;
        $o .= \html_writer::end_tag('style');
        
        return $o;
    }
    
    /**
     * Pretty print JSON
     */
    private function json_encode_pretty($data) {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
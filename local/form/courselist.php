<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
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

/**
 * Course list page for form submissions
 *
 * @package   local_form
 */
require_once('../../config.php');
require_once('lib.php');

global $CFG, $PAGE, $DB, $OUTPUT;

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

$token = optional_param('token', '', PARAM_RAW);
$formid = 0;
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$autofilter = optional_param('autofilter', 0, PARAM_INT);
$show_nonregistered = optional_param('nonregistered', 0, PARAM_INT);
$searchkeyword = optional_param('searchkeyword', '', PARAM_RAW);


if (!empty($token)) {
    // Use the same token validation function from lib.php
    $data = local_form_validate_token($token, 'courselist');
    if (!$data) {
        print_error('Invalid or expired course list link. Please request a new link.');
    }
    $formid = (int)$data['formid'];
    // Token URLs don't include cohortid, so we don't set it here
} else {
    // Get formid from direct parameter
    $formid = optional_param('formid', 0, PARAM_INT);
    
    // This preserves the cohortid when coming from manage forms page
    if ($formid > 0 && $cohortid == 0) {
        // (external/shared links without cohort filter)
        $signed_url = local_form_generate_signed_url($formid, 'courselist');
        redirect($signed_url);
    }
    // If cohortid > 0, we're coming from admin page, keep direct parameters
}

if ($formid <= 0) {
    print_error('Invalid form ID');
}

// Get form information for display
$form = $DB->get_record('local_form', ['id' => $formid], 'name, shortname, description');

// If autofilter is set and cohortid is not provided, find matching cohort
if ($autofilter && $cohortid == 0 && !empty($form->shortname)) {
    $matching_cohort = $DB->get_record('cohort', ['name' => $form->shortname]);
    if ($matching_cohort) {
        $cohortid = $matching_cohort->id;
        // DEBUG:
        // print_object("Found matching cohort: " . $matching_cohort->name . " (ID: " . $matching_cohort->id . ")");
    }
}

// DEBUG: Final cohortid value
// print_object("Final cohortid: " . $cohortid);
// die();

// Set page URL based on whether we're using token or direct parameters
if (!empty($token)) {
    $PAGE->set_url(local_form_generate_signed_url($formid, 'courselist'));
} else {
    // For admin pages with cohort filter, use direct parameters
    $url_params = ['formid' => $formid];
    if ($cohortid > 0) {
        $url_params['cohortid'] = $cohortid;
    }
    if ($autofilter > 0) {
        $url_params['autofilter'] = $autofilter;
    }
    $PAGE->set_url(new moodle_url('/local/form/courselist.php', $url_params));
}

$PAGE->requires->js_call_amd('local_form/main', 'form');
$PAGE->requires->js_call_amd('local_form/main', 'confirmtoggle');
$PAGE->requires->js_call_amd('local_form/main', 'filterrecords');


$PAGE->set_title(get_string('courselist', 'local_form') . ($form ? ' - ' . $form->name : ''));

$page = optional_param('page', 0, PARAM_INT);

echo $OUTPUT->header();

// Add custom CSS
echo '<style>
/* Professional course list page styling */
.courselist-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    padding: 30px;
    margin-bottom: 30px;
}

.page-header-section {
    background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    border-left: 5px solid #294b6a;
}

.page-title {
    color: #2c3e50;
    font-weight: 700;
    margin: 0 0 10px 0;
    font-size: 28px;
}

.form-info {
    color: #5a6c7d;
    font-size: 16px;
    line-height: 1.5;
}

.form-name-highlight {
    font-weight: 600;
    color: #294b6a;
}

.form-description-text {
    color: #6c757d;
    font-size: 14px;
    margin-top: 8px;
    padding-left: 15px;
    border-left: 3px solid #1abc9c;
}

.action-buttons-section {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.btn-primary-action {
    background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
    color: white !important;
}

.btn-primary-action:hover {
    background: linear-gradient(135deg, #2c3e50 0%, #294b6a 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(41, 75, 106, 0.2);
}

.btn-warning-action {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    color: white !important;
}

.btn-warning-action:hover {
    background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 152, 0, 0.2);
}

.btn-secondary-action {
    background: #6c757d;
    color: white !important;
}

.btn-secondary-action:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
}

.stats-cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
    border: 1px solid #e3e6f0;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-card.clickable {
    cursor: pointer;
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 15px;
}

.stat-card.registered {
    border-top: 4px solid #28a745;
}

.stat-card.registered .stat-number {
    color: #28a745;
}

.stat-card.registered .stat-icon {
    color: #28a745;
}

.stat-card.non-registered {
    border-top: 4px solid #dc3545;
}

.stat-card.non-registered .stat-number {
    color: #dc3545;
}

.stat-card.non-registered .stat-icon {
    color: #dc3545;
}

.stat-card.total {
    border-top: 4px solid #007bff;
}

.stat-card.total .stat-number {
    color: #007bff;
}

.stat-card.total .stat-icon {
    color: #007bff;
}

.cohort-selector-section {
    background: #eef7ff;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    border: 1px solid #cfe2ff;
}

.cohort-select-label {
    font-weight: 600;
    color: #084298;
    margin-bottom: 10px;
    display: block;
}

.cohort-select {
    width: 100%;
    max-width: 400px;
    padding: 10px 15px;
    border: 2px solid #86b7fe;
    border-radius: 8px;
    font-size: 16px;
    background: white;
    color: #495057;
    transition: all 0.3s ease;
}

.cohort-select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.cohort-info-box {
    background: #d1ecf1;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
    border-left: 4px solid #0dcaf0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cohort-info-box i {
    color: #0dcaf0;
    font-size: 18px;
}

.cohort-info-text {
    color: #055160;
    font-size: 14px;
}

.content-section {
    background: #f8f9fc;
    border-radius: 10px;
    padding: 25px;
    margin-top: 30px;
    border: 1px solid #e3e6f0;
}

.section-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 25px;
    font-size: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e3e6f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cohort-section {
    margin-top: 30px;
    background: #e8f4f8;
    border-radius: 10px;
    padding: 25px;
    border-left: 5px solid #1abc9c;
}

.inactive-users-section {
    margin-top: 30px;
    background: #fff3cd;
    border-radius: 10px;
    padding: 25px;
    border-left: 5px solid #ffc107;
}

.info-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    border-left: 4px solid #17a2b8;
}

.info-box-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-box-content {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

.pagination-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eaeaea;
    text-align: center;
}

@media (max-width: 768px) {
    .courselist-container {
        padding: 20px;
    }
    
    .page-header-section {
        padding: 20px;
    }
    
    .action-buttons-section {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-cards-container {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .cohort-select {
        max-width: 100%;
    }
}

/* Font Awesome icons */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");
</style>';

// Add JavaScript for clickable cards and cohort selection - UPDATED
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Make non-registered card clickable - FIXED to pass cohortid correctly
    const nonRegisteredCard = document.getElementById("non-registered-card");
    if (nonRegisteredCard) {
        nonRegisteredCard.style.cursor = "pointer";
        nonRegisteredCard.addEventListener("click", function() {
            const cohortId = document.getElementById("cohort-select").value;
            const formId = "' . $formid . '";
            
            // Build URL correctly
            let url = "nonregistered.php?";
            if ("' . (!empty($token) ? 'true' : 'false') . '" === "true") {
                url += "token=' . $token . '";
            } else {
                url += "formid=" + formId;
            }
            if (cohortId && cohortId > 0) {
                url += "&cohortid=" + cohortId;
            }
            window.location.href = url;
        });
        
        // Add hover effect
        nonRegisteredCard.addEventListener("mouseover", function() {
            this.style.transform = "translateY(-5px)";
            this.style.boxShadow = "0 8px 25px rgba(0, 0, 0, 0.1)";
        });
        
        nonRegisteredCard.addEventListener("mouseout", function() {
            this.style.transform = "translateY(0)";
            this.style.boxShadow = "0 3px 15px rgba(0, 0, 0, 0.05)";
        });
    }
    
    // Cohort selector change handler - FIXED to work with both token and formid
    const cohortSelect = document.getElementById("cohort-select");
    if (cohortSelect) {
        cohortSelect.addEventListener("change", function() {
            const cohortId = this.value;
            const formId = "' . $formid . '";
            
            // Build URL correctly
            let url = "courselist.php?";
            if ("' . (!empty($token) ? 'true' : 'false') . '" === "true") {
                url += "token=' . $token . '";
            } else {
                url += "formid=" + formId;
            }
            if (cohortId && cohortId > 0) {
                url += "&cohortid=" + cohortId;
            }
            window.location.href = url;
        });
    }
});
</script>';

$renderer = $PAGE->get_renderer('local_form');

// Main container
echo html_writer::start_tag('div', array('class' => 'courselist-container'));

// Page Header
echo html_writer::start_tag('div', array('class' => 'page-header-section'));
echo html_writer::tag('h1', get_string('student_submissions', 'local_form'), array('class' => 'page-title'));

if ($form) {
    echo html_writer::start_tag('div', array('class' => 'form-info'));
    echo html_writer::tag('span', get_string('for_form', 'local_form') . ': ');
    echo html_writer::tag('span', $form->name, array('class' => 'form-name-highlight'));

    // Show auto-filter message if applicable
    if ($autofilter && $cohortid > 0) {
        $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
        if ($cohort) {
            echo html_writer::tag('div', 
                html_writer::tag('i', '', array('class' => 'fas fa-magic mr-1')) .
                'Auto-filtered by linked cohort: ' . $cohort->name,
                array('class' => 'text-info small mt-1')
            );
        }
    }

    if (!empty($form->description)) {
        echo html_writer::tag('div', $form->description, array('class' => 'form-description-text'));
    }
    echo html_writer::end_tag('div');
}
echo html_writer::end_tag('div');

// Cohort Selector Section
echo html_writer::start_tag('div', array('class' => 'cohort-selector-section'));
echo html_writer::tag('label', html_writer::tag('i', '', array('class' => 'fas fa-filter mr-2')) . 'Filter by Cohort:', array('class' => 'cohort-select-label'));

// Get all cohorts
$cohorts = $DB->get_records('cohort', [], 'name ASC');

echo html_writer::start_tag('select', array('id' => 'cohort-select', 'class' => 'cohort-select'));
echo html_writer::tag('option', 'All Cohorts', array('value' => '0', 'selected' => ($cohortid == 0) ? 'selected' : ''));

if ($cohorts) {
    foreach ($cohorts as $cohort) {
        $selected = ($cohortid == $cohort->id) ? 'selected' : '';
        echo html_writer::tag('option', $cohort->name, array('value' => $cohort->id, $selected => $selected));
    }
}
echo html_writer::end_tag('select');

// Show cohort info if selected
if ($cohortid > 0) {
    $selected_cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name, description');
    if ($selected_cohort) {
        echo html_writer::start_tag('div', array('class' => 'cohort-info-box'));
        echo html_writer::tag('i', '', array('class' => 'fas fa-users'));
        $info_text = 'Showing data for cohort: ' . $selected_cohort->name;
        if ($autofilter) {
            $info_text .= ' (auto-selected from form shortname)';
        }
        echo html_writer::tag('span', $info_text, array('class' => 'cohort-info-text'));
        echo html_writer::end_tag('div');
    }
}
echo html_writer::end_tag('div');

// Action Buttons Section - UPDATED to ensure cohortid is always passed
echo html_writer::start_tag('div', array('class' => 'action-buttons-section'));

// Base URL parameters
$base_url_params = [];
if (!empty($token)) {
    $base_url_params['token'] = $token;
} else {
    $base_url_params['formid'] = $formid;
}
// Always include cohortid if set
if ($cohortid > 0) {
    $base_url_params['cohortid'] = $cohortid;
}

// Button to show non-registered students - PASSES cohortid
$nonregistered_url = new moodle_url('/local/form/nonregistered.php', $base_url_params);

// Inactive users button - PASSES cohortid
$inactive_url = new moodle_url('/local/form/inactiveusers.php', $base_url_params);

// Refresh button - current page with cohort filter
$refresh_url = new moodle_url('/local/form/courselist.php', $base_url_params);

// Non-registered button
echo html_writer::link(
    $nonregistered_url,
    html_writer::tag('i', '', array('class' => 'fas fa-users mr-2')) .
        get_string('view_nonregistered', 'local_form'),
    array('class' => 'action-btn btn-warning-action')
);

// Refresh button
echo html_writer::link(
    $refresh_url,
    html_writer::tag('i', '', array('class' => 'fas fa-sync-alt mr-2')) .
        get_string('refresh', 'local_form'),
    array('class' => 'action-btn btn-secondary-action')
);

// Inactive users button
echo html_writer::link(
    $inactive_url,
    html_writer::tag('i', '', array('class' => 'fas fa-user-slash mr-2')) .
        get_string('view_inactive_users', 'local_form'),
    array('class' => 'action-btn btn-primary-action')
);

// Show all cohorts button (if currently filtered)
if ($cohortid > 0) {
    $all_cohorts_params = [];
    if (!empty($token)) {
        $all_cohorts_params['token'] = $token;
    } else {
        $all_cohorts_params['formid'] = $formid;
    }
    $all_cohorts_url = new moodle_url('/local/form/courselist.php', $all_cohorts_params);
    echo html_writer::link(
        $all_cohorts_url,
        html_writer::tag('i', '', array('class' => 'fas fa-globe mr-2')) .
            'Show All Cohorts',
        array('class' => 'action-btn btn-info')
    );
}
echo html_writer::end_tag('div');

// Get statistics based on cohort filter
if ($cohortid > 0) {
    // Get registered count for specific cohort
    $registered_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT fs.uid) 
         FROM {form_submissions} fs 
         INNER JOIN {cohort_members} cm ON fs.uid = cm.userid
         WHERE fs.formid = :formid AND cm.cohortid = :cohortid",
        ['formid' => $formid, 'cohortid' => $cohortid]
    );
    
    // Get total students in specific cohort
    $total_students = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id)
         FROM {user} u
         INNER JOIN {cohort_members} cm ON u.id = cm.userid
         WHERE u.deleted = 0 AND u.suspended = 0 AND cm.cohortid = :cohortid",
        ['cohortid' => $cohortid]
    );
} else {
    // Get counts for all cohorts (default)
    $registered_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT uid) FROM {form_submissions} WHERE formid = :formid",
        ['formid' => $formid]
    );
    
    // Get total students in all cohorts
    $total_students = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT u.id)
         FROM {user} u
         INNER JOIN {cohort_members} cm ON u.id = cm.userid
         WHERE u.deleted = 0 AND u.suspended = 0",
        []
    );
}

$non_registered_count = max(0, $total_students - $registered_count);

// Statistics Cards
echo html_writer::start_tag('div', array('class' => 'stats-cards-container'));

// Registered Students Card
echo html_writer::start_tag('div', array('class' => 'stat-card registered'));
echo html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fas fa-user-check stat-icon')), array('class' => 'mb-2'));
echo html_writer::tag('div', $registered_count, array('class' => 'stat-number'));
echo html_writer::tag('div', get_string('registered_students', 'local_form'), array('class' => 'stat-label'));
echo html_writer::end_tag('div');

// Non-Registered Students Card (Clickable)
echo html_writer::start_tag('div', array(
    'class' => 'stat-card non-registered clickable',
    'id' => 'non-registered-card',
    'title' => 'Click to view non-registered students'
));
echo html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fas fa-user-times stat-icon')), array('class' => 'mb-2'));
echo html_writer::tag('div', $non_registered_count, array('class' => 'stat-number'));
echo html_writer::tag('div', get_string('non_registered_students', 'local_form'), array('class' => 'stat-label'));
echo html_writer::end_tag('div');

// Total Students Card
echo html_writer::start_tag('div', array('class' => 'stat-card total'));
echo html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fas fa-users stat-icon')), array('class' => 'mb-2'));
echo html_writer::tag('div', $total_students, array('class' => 'stat-number'));
echo html_writer::tag('div', get_string('total_students', 'local_form'), array('class' => 'stat-label'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('div'); // End stats cards container

// Content Section
echo html_writer::start_tag('div', array('class' => 'content-section'));

echo html_writer::tag(
    'h3',
    html_writer::tag('i', '', array('class' => 'fas fa-list mr-2')) .
        get_string('registered_students_list', 'local_form'),
    array('class' => 'section-title')
);

// Information Box
echo html_writer::start_tag('div', array('class' => 'info-box'));
echo html_writer::tag(
    'div',
    html_writer::tag('i', '', array('class' => 'fas fa-info-circle mr-2')) .
        'Student Submissions Overview',
    array('class' => 'info-box-title')
);

$info_text = 'This section shows all students who have submitted the form. ';
if ($cohortid > 0) {
    $selected_cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
    $info_text .= 'Currently filtered for cohort: <strong>' . $selected_cohort->name . '</strong>.';
} else {
    $info_text .= 'Showing data for all cohorts.';
}

echo html_writer::tag(
    'p',
    $info_text,
    array('class' => 'info-box-content')
);
echo html_writer::end_tag('div');

// Hidden fields for AJAX filter
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'id' => 'ajax_formid',
    'value' => $formid
]);

//hidden fields for AJAX filter - ensure cohortid is always included
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'id' => 'ajax_page',
    'value' => $page
]);
$perpage = 50; // or any default value you want

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'id' => 'ajax_perpage',
    'value' => $perpage
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'id' => 'ajax_cohortid',
    'value' => $cohortid
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'id' => 'ajax_token',
    'value' => $token
]);

echo html_writer::start_tag('div', array('id' => 'id_index', 'class' => 'catalog-content'));

if ($show_nonregistered) {
    // Show non-registered students on the same page if needed
    echo $renderer->local_nonregistered_students($formid, $token, $page, $cohortid);
} else {
    // Define page size
    $perpage = 50; // You can adjust this number or make it configurable

    // Pass the parameters to the renderer - let it handle all the database queries
    echo html_writer::start_div('', ['id' => 'records_container']);
    echo $renderer->local_allcourselist(null, null, $page, $perpage, $formid, $token, $cohortid, $searchkeyword = '');
    echo html_writer::end_div();
}

echo html_writer::end_tag('div'); // End catalog-content
echo html_writer::end_tag('div'); // End content-section

// Cohort Management Section
echo html_writer::start_tag('div', array('class' => 'cohort-section'));
echo html_writer::tag(
    'h3',
    html_writer::tag('i', '', array('class' => 'fas fa-users-cog mr-2')) .
        'Cohort Management',
    array('class' => 'section-title')
);
// echo $renderer->local_cohort();
echo $renderer->local_cohort($cohortid);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div'); // End courselist-container

echo $OUTPUT->footer();
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display non-registered students for a form
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

// Get parameters - FIXED: use 'cohortid' instead of 'cohort' for consistency
$token = optional_param('token', '', PARAM_RAW);
$formid = optional_param('formid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT); // Changed from 'cohort' to 'cohortid'
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 3, PARAM_INT); // Items per page

if (!empty($token)) {
    $data = local_form_validate_token($token, 'nonregistered');
    if (!$data) {
        print_error('Invalid or expired link. Please request a new link.');
    }
    $formid = (int)$data['formid'];
}

if ($formid <= 0) {
    print_error('Invalid form ID');
}

// Fetch form data
$form = $DB->get_record('local_form', ['id' => $formid], 'name, description');

// Set page properties - UPDATED: include cohortid in URL
$url_params = [];
if (!empty($token)) {
    $url_params['token'] = $token;
} else {
    $url_params['formid'] = $formid;
}
if ($cohortid > 0) {
    $url_params['cohortid'] = $cohortid;
}

$PAGE->set_title(get_string('nonregistered_students', 'local_form'));
$PAGE->set_url(new moodle_url('/local/form/nonregistered.php', $url_params));

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_form');
?>

<style>
/* Professional styling improvements */
.professional-page-container {
    background: #ffffff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}

.page-header-professional {
    background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border-left: 4px solid #294b6a;
}

.page-title-professional {
    color: #2c3e50;
    font-weight: 700;
    margin: 0 0 8px 0;
    font-size: 24px;
}

.form-header-info {
    color: #5a6c7d;
    font-size: 15px;
    line-height: 1.5;
}

.form-name-highlight {
    font-weight: 600;
    color: #294b6a;
}

.form-description-text {
    color: #6c757d;
    font-size: 14px;
    margin-top: 5px;
    padding: 10px;
    background: rgba(255,255,255,0.7);
    border-radius: 4px;
    border-left: 3px solid #1abc9c;
}

.action-bar-professional {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    align-items: center;
    flex-wrap: wrap;
}

.action-btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: none;
}

.btn-back-action {
    background: #6c757d;
    color: white !important;
}

.btn-back-action:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.filter-section-professional {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #eaeaea;
}

.filter-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
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

.reminder-alert-professional {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border-left: 4px solid #ffc107;
    border: 1px solid #ffeaa7;
}

.reminder-title {
    color: #856404;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.reminder-text {
    color: #856404;
    font-size: 14px;
    line-height: 1.5;
}

.reminder-btn {
    background: #ffc107;
    color: #212529 !important;
    border: 1px solid #e0a800;
    font-weight: 600;
}

.reminder-btn:hover {
    background: #e0a800;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.2);
}

.data-table-professional {
    margin-bottom: 25px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.data-table-professional thead {
    background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
}

.data-table-professional th {
    color: white;
    font-weight: 600;
    padding: 14px 12px;
    border: none;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table-professional tbody tr {
    transition: background-color 0.2s;
    border-bottom: 1px solid #f0f0f0;
}

.data-table-professional tbody tr:hover {
    background-color: #f8f9fc;
}

.data-table-professional td {
    padding: 12px;
    vertical-align: middle;
    border-color: #f0f0f0;
}

.student-name-cell {
    font-weight: 600;
    color: #2c3e50;
}

.cohort-badge {
    background: #e8f4f8;
    color: #294b6a;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.download-section {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e3e6f0;
    margin-top: 25px;
}

.download-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.download-selector {
    max-width: 300px;
}

.pagination-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eaeaea;
}

.count-badge {
    background: #294b6a;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
/* Add these to your existing CSS */

.bulk-action-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e3e6f0;
}

.bulk-action-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 15px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bulk-action-form {
    margin-bottom: 0;
}

.form-check-input {
    cursor: pointer;
    width: 20px !important;
    height: 20px !important;
}

.form-check-label {
    cursor: pointer;
    user-select: none;
}

/* Master and individual checkbox styling */
.master-checkbox, .individual-checkbox {
    width: 20px !important;
    height: 20px !important;
    cursor: pointer !important;
    margin: 0 auto;
    display: block;
}

/* Checkbox column styling */
.data-table-professional th:first-child,
.data-table-professional td:first-child {
    width: 60px !important;
    min-width: 60px !important;
    max-width: 60px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 8px !important;
}

/* Make checkbox containers properly aligned */
.data-table-professional th:first-child div,
.data-table-professional td:first-child div {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
    height: 100% !important;
}

/* Make table rows more interactive */
#nonregistered_table tbody tr {
    transition: background-color 0.2s;
    cursor: pointer;
}

#nonregistered_table tbody tr:hover {
    background-color: #f5f8ff !important;
}

/* Fix header checkbox alignment */
.data-table-professional th:first-child .master-checkbox {
    margin: 0;
}

/* Ensure checkbox visibility in table */
.data-table-professional td:first-child .individual-checkbox {
    margin: 0;
    position: relative;
    top: 0;
    left: 0;
}

/* Style for indeterminate state */
.master-checkbox:indeterminate {
    background-color: #007bff;
    border-color: #007bff;
}

/* Selected row styling */
tr.row-selected {
    background-color: #e8f4fd !important;
}

tr.row-selected:hover {
    background-color: #d9ecfc !important;
}

/* Bulk action button styling */
.action-btn.remove-btn {
    background: #dc3545 !important;
    color: white !important;
    border: 1px solid #c82333;
    padding: 8px 16px;
    font-weight: 600;
    transition: all 0.3s;
}

.action-btn.remove-btn:hover {
    background: #c82333 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
}

/* Form check alignment */
.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.form-check-input {
    margin-right: 8px;
    margin-top: 0;
}

.form-check-label {
    margin-bottom: 0;
    font-size: 14px;
    color: #495057;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bulk-action-section .d-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .data-table-professional th:first-child,
    .data-table-professional td:first-child {
        width: 50px !important;
        min-width: 50px !important;
    }
    
    .form-check {
        margin-bottom: 10px;
    }
}

/* Ensure table cells have proper spacing */
.data-table-professional td {
    padding: 12px 8px !important;
    vertical-align: middle !important;
}

.data-table-professional th {
    padding: 14px 8px !important;
    vertical-align: middle !important;
}

/* Fix for checkbox column header */
.data-table-professional th:first-child {
    background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

/* Make sure checkboxes are visible on all backgrounds */
.data-table-professional td:first-child {
    background-color: #fff;
    border-right: 1px solid #dee2e6;
}

.data-table-professional tr:hover td:first-child {
    background-color: #f5f8ff;
}

/* Checkbox focus states */
.form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}

.master-checkbox:focus,
.individual-checkbox:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

/* Font Awesome icons */
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
</style>

<div class="professional-page-container">
    <!-- Page Header -->
    <div class="page-header-professional">
        <h1 class="page-title-professional"><?php echo get_string('nonregistered_students', 'local_form'); ?></h1>
        <div class="form-header-info">
            <?php if ($form): ?>
                <span><?php echo get_string('for_form', 'local_form'); ?>: </span>
                <span class="form-name-highlight"><?php echo s($form->name); ?></span>
                <?php if (!empty($form->description)): ?>
                    <div class="form-description-text">
                        <i class="fas fa-info-circle"></i> <?php echo s($form->description); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Action Bar -->
    <div class="action-bar-professional">
        <?php
        // Build back URL with correct parameters
        $back_params = [];
        if (!empty($token)) {
            $back_params['token'] = $token;
        } else {
            $back_params['formid'] = $formid;
        }
        if ($cohortid > 0) {
            $back_params['cohortid'] = $cohortid;
        }
        $back_url = new moodle_url('/local/form/courselist.php', $back_params);
        ?>
        <a href="<?php echo $back_url; ?>" class="action-btn btn-back-action">
            <i class="fas fa-arrow-left"></i> <?php echo get_string('back_to_courselist', 'local_form'); ?>
        </a>
    </div>
    
    <!-- Cohort Filter Section -->
    <div class="filter-section-professional">
        <div class="filter-title">
            <i class="fas fa-filter"></i> Cohort Filter
        </div>
        <form method="get" action="" class="form-inline">
            <?php if (!empty($token)): ?>
                <input type="hidden" name="token" value="<?php echo s($token); ?>">
            <?php else: ?>
                <input type="hidden" name="formid" value="<?php echo $formid; ?>">
            <?php endif; ?>
            
            <div class="form-group mr-2">
                <select name="cohortid" id="cohort-select" class="cohort-select" onchange="this.form.submit()">
                    <option value="0">All Cohorts</option>
                    <?php
                    // Get all cohorts
                    $cohorts = $DB->get_records('cohort', [], 'name ASC');
                    foreach ($cohorts as $cohort) {
                        $selected = ($cohortid == $cohort->id) ? 'selected' : '';
                        echo '<option value="' . $cohort->id . '" ' . $selected . '>' . s($cohort->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </form>
        
        <?php if ($cohortid > 0): ?>
            <?php
            $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name');
            if ($cohort):
            ?>
            <div class="cohort-info-box">
                <i class="fas fa-info-circle"></i>
                <span class="cohort-info-text">
                    Filtering for cohort: <strong><?php echo s($cohort->name); ?></strong>
                </span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Content Area -->
    <div class="content-area">
        <?php
        // Display non-registered students using the renderer method
        echo $renderer->local_nonregistered_students($formid, $token, $page, $perpage, $cohortid);
        ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
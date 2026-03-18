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

/**
 * Course list page for form submissions
 *
 * @package   local_form
 */
require_once('../../config.php');
require_once('lib.php');

global $CFG, $PAGE, $DB, $OUTPUT;

require_login();
// if (!local_form_is_teacher_or_admin()) {
//     // Redirect to My page with error message
//     $redirecturl = new moodle_url('/my');
//     redirect(
//         $redirecturl,
//         get_string('access_denied_teachers_only', 'local_form'),
//         null,
//         \core\output\notification::NOTIFY_ERROR
//     );
// }

$token = optional_param('token', '', PARAM_RAW);
$formid = optional_param('formid', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$autofilter = optional_param('autofilter', 0, PARAM_INT);
$show_nonregistered = optional_param('nonregistered', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// ORIGINAL CODE - PRESERVED FOR FUTURE REFERENCE
// Token validation logic
if (!empty($token)) {
    $data = local_form_validate_token($token, 'courselist');
    if (!$data) {
        print_error('Invalid or expired course list link.');
    }
    $formid = (int)$data['formid'];
}

if ($formid <= 0) {
    print_error('Invalid form ID');
}

// Get form information for display
$form = $DB->get_record('local_form', ['id' => $formid], 'name, shortname, description');

// ORIGINAL CODE - PRESERVED FOR FUTURE REFERENCE
// Auto-filter logic based on form shortname
if ($autofilter && !$cohortid && !empty($form->shortname)) {
    if ($c = $DB->get_record('cohort', ['name' => $form->shortname])) {
        $cohortid = $c->id;
    }
}

// Set page URL
$PAGE->set_url(new moodle_url('/local/form/report_courselist.php', [
    'formid' => $formid,
    'cohortid' => $cohortid
]));

$PAGE->set_title(get_string('courselist', 'local_form') . ($form ? ' - ' . $form->name : ''));
$PAGE->requires->js_call_amd('local_form/main', 'form');

echo $OUTPUT->header();

/* =========================================================
   ORIGINAL CSS - PRESERVED FOR FUTURE REFERENCE
   ========================================================= */
// echo '<style>
// ' . file_get_contents(__DIR__ . '/css/report.css') . '
// </style>';

/* =========================================================
   CURRENT DESIGN CSS - ENHANCED PROFESSIONAL STYLING
   ========================================================= */
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

.cohort-info-box {
    background: #d1ecf1;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
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

@media (max-width: 768px) {
    .courselist-container {
        padding: 20px;
    }
    
    .page-header-section {
        padding: 20px;
    }
    
    .stats-cards-container {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
}

/* Font Awesome icons */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");
</style>';

/* =========================================================
   ORIGINAL JS - PRESERVED FOR FUTURE REFERENCE
   ========================================================= */
// echo '<script>
// document.addEventListener("DOMContentLoaded", function () {
//     const card = document.getElementById("non-registered-card");
//     if (card) {
//         card.addEventListener("click", function () {
//             let url = "nonregistered.php?formid=' . $formid . '";
//             if (' . $cohortid . ') {
//                 url += "&cohortid=' . $cohortid . '";
//             }
//             window.location.href = url;
//         });
//     }
// });
// </script>';

/* =========================================================
   ENHANCED JS FOR CLICKABLE CARDS
   ========================================================= */
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Make non-registered card clickable
    const nonRegisteredCard = document.getElementById("non-registered-card");
    if (nonRegisteredCard) {
        nonRegisteredCard.style.cursor = "pointer";
        nonRegisteredCard.addEventListener("click", function() {
            let url = "nonregistered.php?formid=' . $formid . '";
            if (' . $cohortid . ' > 0) {
                url += "&cohortid=' . $cohortid . '";
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
});
</script>';

$renderer = $PAGE->get_renderer('local_form');

// Main container
echo html_writer::start_tag('div', array('class' => 'courselist-container'));

/* =========================================================
   PAGE HEADER SECTION
   ========================================================= */
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
                ' Auto-filtered by linked cohort: ' . $cohort->name,
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

/* =========================================================
   COHORT INFO (if selected)
   ========================================================= */
if ($cohortid > 0) {
    $selected_cohort = $DB->get_record('cohort', ['id' => $cohortid], 'name, description');
    if ($selected_cohort) {
        echo html_writer::start_tag('div', array('class' => 'cohort-info-box'));
        echo html_writer::tag('i', '', array('class' => 'fas fa-users'));
        $info_text = 'Showing data for cohort: <strong>' . $selected_cohort->name . '</strong>';
        if ($autofilter) {
            $info_text .= ' (auto-selected from form shortname)';
        }
        echo html_writer::tag('span', $info_text, array('class' => 'cohort-info-text'));
        echo html_writer::end_tag('div');
    }
}

/* =========================================================
   STATISTICS CALCULATION - FORMID BASED COUNTS (ORIGINAL LOGIC)
   ========================================================= */
if ($cohortid > 0) {
    // Count distinct users who submitted the form and are in the selected cohort
    $registered_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT fs.uid)
           FROM {form_submissions} fs
           JOIN {cohort_members} cm ON cm.userid = fs.uid
          WHERE fs.formid = :formid
            AND cm.cohortid = :cohortid",
        ['formid' => $formid, 'cohortid' => $cohortid]
    );
    
    // For cohort filter, total students = registered count (original logic)
    $total_students = $registered_count;
} else {
    // Count distinct users who submitted the form (all submissions)
    $registered_count = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT uid)
           FROM {form_submissions}
          WHERE formid = :formid",
        ['formid' => $formid]
    );
    
    // For no cohort filter, total students = registered count (original logic)
    $total_students = $registered_count;
}

// ORIGINAL LOGIC: non_registered_count is set to 0
$non_registered_count = 0;

/* =========================================================
   STAT CARDS
   ========================================================= */
echo html_writer::start_tag('div', array('class' => 'stats-cards-container'));

// Registered Students Card
echo html_writer::start_tag('div', array('class' => 'stat-card registered'));
echo html_writer::tag('div', html_writer::tag('i', '', array('class' => 'fas fa-user-check stat-icon')), array('class' => 'mb-2'));
echo html_writer::tag('div', $registered_count, array('class' => 'stat-number'));
echo html_writer::tag('div', get_string('registered_students', 'local_form'), array('class' => 'stat-label'));
echo html_writer::end_tag('div');

// Non-Registered Students Card (Clickable) - ORIGINAL LOGIC: shows 0
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

/* =========================================================
   INFORMATION BOX
   ========================================================= */
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
    $info_text .= 'Currently filtered for cohort: <strong>' . $selected_cohort->name . '</strong>. ';
}
$info_text .= 'Total registered students: <strong>' . $registered_count . '</strong>.';

echo html_writer::tag(
    'p',
    $info_text,
    array('class' => 'info-box-content')
);
echo html_writer::end_tag('div');

/* =========================================================
   CONTENT SECTION
   ========================================================= */
echo html_writer::start_tag('div', array('class' => 'content-section'));

echo html_writer::tag(
    'h3',
    html_writer::tag('i', '', array('class' => 'fas fa-list mr-2')) .
        get_string('registered_students_list', 'local_form'),
    array('class' => 'section-title')
);

echo html_writer::start_tag('div', array('id' => 'id_index', 'class' => 'catalog-content'));

/* =========================================================
   ORIGINAL CONTENT DISPLAY LOGIC - PRESERVED
   ========================================================= */
if ($show_nonregistered) {
    // Show non-registered students page
    echo $renderer->local_nonregistered_students($formid, $token, $page, $cohortid);
} else {
    // Show registered students list with pagination (30 per page)
    echo $renderer->local_allcourselist(null, null, $page, 30, $formid, $token, $cohortid);
}

echo html_writer::end_tag('div'); // End id_index
echo html_writer::end_tag('div'); // End content-section
echo html_writer::end_tag('div'); // End courselist-container

echo $OUTPUT->footer();
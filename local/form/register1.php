<?php

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

class adduserform extends moodleform
{

    function definition()
    {
        global $USER;
        $mform = $this->_form;

        $mform->addElement('html', '<div class="form-instructions">
        <h2 style="font-size: 18px; color: #333; margin-bottom: 20px;">
        Please ensure that you provide accurate details. Remember your username and password as they are essential for future logins.
	</h2>
<h2 style="font-size: 18px; color: #333; margin-bottom: 20px;">
        After successful creation of Username & Password, Participants are requested to mandatorily fill the registration form by login GYAN Portal (https://gyan.lbsnaa.gov.in).
        </h2>

        </div>');

        $formid =  $this->_customdata['formid'];
        $mform->addElement('hidden', 'formid', $formid);
        $mform->setType('formid', PARAM_INT);

        // First name field
        $mform->addElement('text', 'firstname', get_string('firstname', 'local_form'), 'maxlength="50" size="30"');
        $mform->setType('firstname', PARAM_RAW);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        // Last name field
        $mform->addElement('text', 'lastname', get_string('lastname', 'local_form'), 'maxlength="50" size="30"');
        $mform->setType('lastname', PARAM_RAW);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        // Phone number field
        $mform->addElement('text', 'phone1', get_string('phone1', 'local_form'), 'maxlength="10" size="30"');
        $mform->setType('phone1', PARAM_RAW);
        $mform->addRule('phone1', get_string('required'), 'required', null, 'client');


        // Email field
        $mform->addElement('text', 'email', get_string('email', 'local_form'), 'maxlength="30" size="30"');
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');

        // Username field
        $mform->addElement('text', 'username', get_string('username', 'local_form'), 'maxlength="30" size="30"');
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        // Password field
        $mform->addElement('password', 'password', get_string('password', 'local_form'), 'maxlength="30" size="30"');
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('required'), 'required', null, 'client');


        // Confirm Password field
        $mform->addElement('password', 'confirmpassword', get_string('confirmpassword', 'local_form'), 'maxlength="30" size="30"');
        $mform->setType('confirmpassword', PARAM_RAW);
        $mform->addRule('confirmpassword', get_string('required'), 'required', null, 'client');


        $this->add_action_buttons(true, get_string('submit', 'local_form'));
    }

    function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate username length
        if (strlen($data['username']) > 50) {
            $errors['username'] = get_string('username_toolong', 'local_form');
        }

        // Validate firstname
        if (empty($data['firstname'])) {
            $errors['firstname'] = get_string('required', 'local_form');
        }

        // Validate last name
        if (empty($data['lastname'])) {
            $errors['lastname'] = get_string('required', 'local_form');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = get_string('invalidemail', 'local_form');
        }

        // Validate password (at least 8 characters, one uppercase, one number, one special character)
        if (
            !empty($data['password']) &&
            !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $data['password'])
        ) {
            $errors['password'] = get_string('invalidpassword', 'local_form');
        }


        // Validate confirm password matches password
        if (!empty($data['password']) && $data['password'] !== $data['confirmpassword']) {
            $errors['confirmpassword'] = get_string('passwordmismatch', 'local_form');
        }

        // Validate phone number: integer, maximum length of 10
        if (!preg_match('/^\d{10}$/', $data['phone1'])) {
            $errors['phone1'] = get_string('invalidphoneno', 'local_form');
        }
        return $errors;
    }
}

global $CFG;
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$formid = required_param('formid', PARAM_INT);
$PAGE->set_url('/local/form/register.php', array('formid' => $formid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('registration', 'local_form'));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('registration', 'local_form'));
$PAGE->navbar->add(get_string('registration', 'local_form'), new moodle_url('/local/form/register.php'));

$redirect_url = new moodle_url('/login/index.php');
$edit_data = array();
if ($id) {
    $edit_data = $DB->get_record('user', array('id' => $id));
}
$mform = new adduserform('', array('formid' => $formid));
$mform->set_data($edit_data);

if ($mform->is_cancelled()) {
    $redirect_url = new moodle_url('/login/index.php');
    redirect($redirect_url);
} else if ($data = $mform->get_data()) {
    $plaintext_password = $data->password; // Keep the plaintext password
    $data->timemodified = time();

    // Check if a user exists by email or username in `user` table
    $existingUser = $DB->get_record('user', ['username' => $data->username]);
    $existingEmail = $DB->get_record('user', ['email' => $data->email]);

    if ($existingUser || $existingEmail) {
        // Update existing user
        $data->id = $existingUser ? $existingUser->id : $existingEmail->id;

        // Fetch the authentication method for the user
        $auth_method = $DB->get_field('user', 'auth', ['id' => $data->id]);

        if ($auth_method === 'ldap') {
            // Switch to manual authentication
            $data->auth = 'manual';

            // Hash the new password and set it
            $data->password = hash_internal_user_password($plaintext_password);

            // echo "The user's authentication method has been switched to manual.";
        } else {
            // For non-LDAP users, update the password if provided
            if (!empty($data->password)) {
                $data->password = hash_internal_user_password($data->password);
            }
        }

        $DB->update_record('user', $data);
        $formuser = new stdClass();
        $formuser->userid = $data->id;
        $formuser->username = $data->username;
        $formuser->email = $data->email;
        $formuser->password = $plaintext_password; // Store plaintext password
        $formuser->timemodified = time();

        // Check if the user exists in the `local_user` table
        $existingFormUser = $DB->get_record('local_user', ['userid' => $data->id]);

        if ($existingFormUser) {
            $formuser->id = $existingFormUser->id;
            $DB->update_record('local_user', $formuser);
        } else {
            $formuser->timecreated = time();
            $DB->insert_record('local_user', $formuser);
        }

        $formshortname = $DB->get_field('local_form', 'shortname', ['id' => $formid]);
        $cohort_id = $DB->get_field('cohort', 'id', ['name' => $formshortname]);
        $cohortMemberExists = $DB->record_exists('cohort_members', [
            'cohortid' => $cohort_id, // Cohort ID
            'userid' => $data->id,
        ]);

        if (!$cohortMemberExists) {
            $cohortMember = new stdClass();
            $cohortMember->cohortid = $cohort_id; // Cohort ID
            $cohortMember->userid = $data->id;
            $cohortMember->timeadded = time();
            $DB->insert_record('cohort_members', $cohortMember);
            $event = \core\event\cohort_member_added::create([
                'context' => context_system::instance(),
                'objectid' => $cohort_id, // Cohort ID
                'relateduserid' => $data->id,
            ]);
            $event->trigger();
        }
    } else {
        // Insert new user
        $plaintext_password = $data->password;
        $data->password = hash_internal_user_password($data->password);
        $data->timecreated = time();
        $data->confirmed = 1; // Automatically confirm the user
        $data->mnethostid = 1;
        $last_inserted_id = $DB->insert_record('user', $data);

        $formshortname = $DB->get_field('local_form', 'shortname', ['id' => $data->formid]);
        $cohort_id = $DB->get_field('cohort', 'id', ['name' => $formshortname]);
        $cohortMemberExists = $DB->record_exists('cohort_members', [
            'cohortid' => $cohort_id, // Cohort ID
            'userid' => $last_inserted_id,
        ]);

        if (!$cohortMemberExists) {
            // echo  $last_inserted_id;die;('kkkk');
            $cohortMember = new stdClass();
            $cohortMember->cohortid = $cohort_id; // Cohort ID
            $cohortMember->userid = $last_inserted_id;
            $cohortMember->timeadded = time();
            $inserted_id = $DB->insert_record('cohort_members', $cohortMember);
            $event = \core\event\cohort_member_added::create([
                'context' => context_system::instance(),
                'objectid' => $cohort_id, // Cohort ID
                'relateduserid' => $last_inserted_id,
            ]);
            $event->trigger();
        }

        if (!empty($last_inserted_id)) {
            $formuser = new stdClass();
            $formuser->userid = $last_inserted_id;
            $formuser->username = $data->username;
            $formuser->email = $data->email;
            $formuser->password = $plaintext_password;
            $formuser->timecreated = time();
            $formuser->timemodified = time();
            $DB->insert_record('local_user', $formuser);
        }
    }

    // Send email to user
    $subject = "Welcome to Lbsnaa";
    $fullname = "{$data->firstname} {$data->lastname}";
    $message = "Dear {$fullname},\n\n"
        . "Your account has been successfully created/updated. Here are your login details:\n"
        . "Username: {$data->username}\n"
        . "Password: {$plaintext_password}\n\n"
        . "Please change your password after your first login.\n\n"
        . "Best regards,\nTeam Lbsnaa";

    // Use Moodle's email functionality
    $userobject = (object) [
        'id' => -99,
        'email' => $data->email,
        'firstname' => $data->firstname,
        'lastname' => $data->lastname,
    ];

    $adminuser = get_admin(); // Sender (admin user)
    if (email_to_user($userobject, $adminuser, $subject, $message)) {
        $success_message = "The user has been created/updated successfully! 
                           A confirmation email with the login details has been sent to the registered email address ({$data->email}).
                           Please ask the user to check their inbox or spam folder for further instructions.";
    } else {
        // $success_message = "The user has been created/updated successfully, but there was an error sending the email.";
        $success_message = "The user has been created/updated successfully! 
        A confirmation email with the login details has been sent to the registered email address ({$data->email}).
        Please ask the user to check their inbox or spam folder for further instructions.";
    }

    redirect($redirect_url, $success_message);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

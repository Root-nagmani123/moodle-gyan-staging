<?php
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php'); // Include lib.php for token functions

class adduserform extends moodleform
{
    function definition()
    {
        global $USER;
        $mform = $this->_form;

        $mform->addElement('html', '
        <div class="form-instructions" style="background-color: #e8f5e8; border: 2px solid #4CAF50; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <h2 style="font-size: 20px; color: #2c3e50; margin-bottom: 15px; text-align: center;">
                <i class="fa fa-user-plus" style="color: #4CAF50; margin-right: 10px;"></i>
                CREDENTIAL CREATION FORM
            </h2>
            <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin: 15px 0;">
                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 16px;">
                    <i class="fa fa-exclamation-triangle" style="margin-right: 8px;"></i>IMPORTANT NOTE:
                </h3>
                <p style="color: #856404; margin: 0; line-height: 1.5;">
                    This form is <strong>ONLY for creating your login credentials</strong>. After successful submission, 
                    you will be redirected to the main registration form where you need to fill in all required details 
                    for complete registration. <strong>Without completing the main form, your registration will not be considered.</strong>
                </p>
            </div>
            <p style="color: #333; margin: 15px 0 10px 0; line-height: 1.6; text-align: center;">
                Please ensure that you provide accurate details. Remember your username and password as they are essential for future logins.
            </p>
            <p style="color: #2c3e50; margin: 10px 0 0 0; line-height: 1.6; text-align: center; font-weight: 600;">
                <i class="fa fa-globe" style="color: #4CAF50; margin-right: 8px;"></i>
                For all future logins, use: <a href="https://gyan.lbsnaa.gov.in/" target="_blank" style="color: #2196F3; text-decoration: none;">https://gyan.lbsnaa.gov.in/</a>
            </p>
        </div>');

        $formid = $this->_customdata['formid'];
        $mform->addElement('hidden', 'formid', $formid);
        $mform->setType('formid', PARAM_INT);
        // First name field
        $mform->addElement(
            'text',
            'firstname',
            get_string('firstname', 'local_form'),
            'maxlength="50" size="30" placeholder="Enter your first name"'
        );
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        // Last name field
        $mform->addElement(
            'text',
            'lastname',
            get_string('lastname', 'local_form'),
            'maxlength="50" size="30" placeholder="Enter your last name"'
        );
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        // Phone number field
        $mform->addElement(
            'text',
            'phone1',
            get_string('phone1', 'local_form'),
            'maxlength="10" size="30" placeholder="10-digit mobile number"'
        );
        $mform->setType('phone1', PARAM_RAW);
        $mform->addRule('phone1', get_string('required'), 'required', null, 'client');

        // Add email warning before email field
        $mform->addElement('html', '
            <div style="background-color: #fff8e1; border-left: 4px solid #ff9800; padding: 10px; margin: 10px 0 15px 0; border-radius: 4px;">
                <p style="margin: 0; color: #e65100; font-weight: 600; font-size: 14px;">
                    <i class="fa fa-exclamation-circle" style="margin-right: 8px;"></i>
                    Please use only personal email ID. Don\'t use post/designation based email ID.
                </p>
            </div>');
        
        // Email field
        $mform->addElement(
            'text',
            'email',
            get_string('email', 'local_form'),
            'maxlength="100" size="30" placeholder="example@domain.com"'
        );
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');

        // Username field
        $mform->addElement(
            'text',
            'username',
            get_string('username', 'local_form'),
            'maxlength="30" size="30" placeholder="lowercase, no spaces"'
        );
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');
        $mform->addElement(
            'static',
            'usernamehelp',
            '',
            '<small class="form-text text-muted">Use lowercase letters only, no spaces or @ symbol</small>'
        );

        // Password field with show/hide toggle
        $mform->addElement(
            'password',
            'password',
            get_string('password', 'local_form'),
            ['maxlength' => '30', 'size' => '30', 'id' => 'id_password', 'placeholder' => 'Create a strong password']
        );
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('required'), 'required', null, 'client');

        // Add show/hide password toggle
        $mform->addElement('html', '
            <div class="form-group row" style="margin-bottom: 20px;">
                <div class="col-sm-9 offset-sm-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="show-password">
                        <label class="form-check-label" for="show-password" style="font-size: 14px;">
                            <i class="fa fa-eye" style="margin-right: 5px;"></i>Show Password
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Password must contain at least 8 characters, one uppercase letter, one number, and one special character (@$!%*?&#)
                    </small>
                </div>
            </div>');

        // Confirm Password field
        $mform->addElement(
            'password',
            'confirmpassword',
            get_string('confirmpassword', 'local_form'),
            ['maxlength' => '30', 'size' => '30', 'id' => 'id_confirmpassword', 'placeholder' => 'Re-enter your password']
        );
        $mform->setType('confirmpassword', PARAM_RAW);
        $mform->addRule('confirmpassword', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('submit', 'local_form'));
    }

    function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate username length
        if (strlen($data['username']) > 20) {
            $errors['username'] = get_string('username_toolong', 'local_form');
        }

        // Validate username lowercase only
        if ($data['username'] !== strtolower($data['username'])) {
            $errors['username'] = "Username must be in lowercase only.";
        }

        // Validate username does not contain '@'
        if (strpos($data['username'], '@') !== false) {
            $errors['username'] = "Username must not contain '@' symbol.";
        }

        // Validate username does not contain spaces
        if (preg_match('/\s/', $data['username'])) {
            $errors['username'] = "Username must not contain spaces.";
        }

        // Check if username already exists with different email
        if ($DB->record_exists('user', ['username' => $data['username']])) {
            $existing_by_username = $DB->get_record('user', ['username' => $data['username']]);
            if ($existing_by_username->email !== $data['email']) {
                $errors['username'] = 'Username already exists with a different email.';
            }
        }

        // Check if email already exists with different username
        if ($DB->record_exists('user', ['email' => $data['email']])) {
            $existing_by_email = $DB->get_record('user', ['email' => $data['email']]);
            if ($existing_by_email->username !== $data['username']) {
                $errors['email'] = 'Email already registered with a different username.';
            }
        }

        // Validate password strength
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long.';
            } elseif (!preg_match('/[A-Z]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one lowercase letter.';
            } elseif (!preg_match('/\d/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one number.';
            } elseif (!preg_match('/[@$!%*?&#]/', $data['password'])) {
                $errors['password'] = 'Password must contain at least one special character (@$!%*?&#).';
            }
        }

        // Validate confirm password matches
        if (!empty($data['password']) && $data['password'] !== $data['confirmpassword']) {
            $errors['confirmpassword'] = get_string('passwordmismatch', 'local_form');
        }

        // Validate phone number
        if (!preg_match('/^\d{10}$/', $data['phone1'])) {
            $errors['phone1'] = get_string('invalidphoneno', 'local_form');
        }

        return $errors;
    }
}

// Main script
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

// Get token from URL
$token = optional_param('token', '', PARAM_RAW);
$formid = 0;

if (!empty($token)) {
    // Validate the token
    $data = local_form_validate_token($token, 'register');
    if (!$data) {
        throw new moodle_exception('invalidlink', 'local_form', '', null, 'Invalid or expired registration link.');
    }
    $formid = (int)$data['formid'];
} else {
    // Legacy support for encrypted formid
    $encrypted_formid = optional_param('eformid', '', PARAM_ALPHANUMEXT);
    if (!empty($encrypted_formid)) {
        // Decrypt the old encrypted formid
        function decrypt_formid($encrypted_formid)
        {
            global $CFG;
            $key = $CFG->siteidentifier;
            $iv = substr(hash('sha256', $key), 0, 16);
            return openssl_decrypt(base64_decode($encrypted_formid), 'AES-256-CBC', $key, 0, $iv);
        }

        $formid = decrypt_formid($encrypted_formid);
        if (!$formid || !is_numeric($formid)) {
            if (is_numeric($encrypted_formid)) {
                $formid = (int)$encrypted_formid;
            } else {
                throw new moodle_exception('invalidformid', 'local_form');
            }
        }
        $formid = (int)$formid;

        // Convert to token URL and redirect
        $new_url = local_form_generate_signed_url($formid, 'add', [], 0);
        redirect($new_url);
    } else {
        // Fallback to plain formid for backward compatibility
        $formid = optional_param('formid', 0, PARAM_INT);
    }

    // if ($formid > 0) {
    //     // Generate token URL and redirect
    //     $signed_url = local_form_generate_signed_url($formid, 'addform', [], 0);
    //     redirect($signed_url);
    // }
}

// If no formid, show error instead of form selection
if (empty($formid)) {
    throw new moodle_exception('invalidformid', 'local_form', '', null, 'Invalid registration link. Please use a valid registration URL.');
}

// Verify the form exists and is visible
if (!$DB->record_exists('local_form', ['id' => $formid, 'visible' => 1])) {
    throw new moodle_exception('invalidformid', 'local_form');
}

// Use token in URL for better security
if (!empty($token)) {
    $PAGE->set_url('/local/form/register.php', array('token' => $token));
} else {
    // Generate token URL
    $register_url = local_form_generate_signed_url($formid, 'register', [], 0);
    $PAGE->set_url('/local/form/register.php');
}

$PAGE->set_context($context);
$PAGE->set_title('Create Login Credentials');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading('Create Your Login Credentials');

// Generate breadcrumb URL with token
if (!empty($token)) {
    $breadcrumb_url = new moodle_url('/local/form/register.php', ['token' => $token]);
} else {
    $breadcrumb_url = new moodle_url('/local/form/register.php');
}
$PAGE->navbar->add('Create Credentials', $breadcrumb_url);

// Load form
$mform = new adduserform('', array('formid' => $formid));

// Handle form submission
if ($mform->is_cancelled()) {
    $redirect_url = new moodle_url('/login/index.php');
    redirect($redirect_url);
} else if ($data = $mform->get_data()) {
    // Always convert username to lowercase
    // $data->username = strtolower($data->username);
    $data->username = strtolower(trim($data->username));
    $data->username = preg_replace('/\s+/', '', $data->username);
    $plaintext_password = $data->password; // Keep the plaintext password
    $data->timemodified = time();

    // Check if user exists by email OR username (BOTH must be same user)
    $existing_by_username = $DB->get_record('user', ['username' => $data->username]);
    $existing_by_email = $DB->get_record('user', ['email' => $data->email]);

    // Determine which existing user to update
    $existingUser = null;
    if ($existing_by_username && $existing_by_email) {
        // Both username and email exist
        if ($existing_by_username->id == $existing_by_email->id) {
            // Same user has both this username and email - UPDATE ALLOWED
            $existingUser = $existing_by_username;
        } else {
            // Different users have the username and email - ERROR (should be caught in validation)
            // Fallback: update the one with matching email
            $existingUser = $existing_by_email;
        }
    } elseif ($existing_by_username) {
        // Only username exists
        $existingUser = $existing_by_username;
    } elseif ($existing_by_email) {
        // Only email exists
        $existingUser = $existing_by_email;
    }

    if ($existingUser) {
        // Update existing user
        $data->id = $existingUser->id;

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

        // Preserve existing values for fields not in the form
        $data->confirmed = $existingUser->confirmed;
        $data->mnethostid = $existingUser->mnethostid;
        $data->timecreated = $existingUser->timecreated;
        
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
    $subject = "Welcome to LBSNAA - Login Credentials Created";
    $fullname = "{$data->firstname} {$data->lastname}";
    $login_url = "https://gyan.lbsnaa.gov.in/";
    $message = "Dear {$fullname},\n\n"
        . "Your account has been successfully created/updated. Here are your login details:\n"
        . "Username: {$data->username}\n"
        . "Password: {$plaintext_password}\n"
        . "Login URL: {$login_url}\n\n"
        . "**FOR ALL FUTURE LOGINS, PLEASE USE THE ABOVE CREDENTIALS AT:**\n"
        . "{$login_url}\n\n"
        . "Please change your password after your first login.\n\n"
        . "Best regards,\nTeam LBSNAA";

    // Use Moodle's email functionality
    $userobject = (object) [
        'id' => -99,
        'email' => $data->email,
        'firstname' => $data->firstname,
        'lastname' => $data->lastname,
    ];

    $adminuser = get_admin(); // Sender (admin user)
    email_to_user($userobject, $adminuser, $subject, $message);
    $userid = isset($last_inserted_id) && !empty($last_inserted_id)
        ? $last_inserted_id
        : $data->id;

    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    complete_user_login($user);

    $redirect_url = local_form_generate_signed_url($formid, 'addform', []);

    // Direct redirect without showing message
    redirect($redirect_url);
    exit;
}

// Display the form
echo $OUTPUT->header();

// Add custom CSS for better design
echo '
<style>
    /* Improved form styling */
    .mform fieldset {
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background-color: #f8f9fc;
    }
    
    .mform .fitem {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #edf2f7;
    }
    
    .mform .fitem:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .mform .felement {
        margin-top: 5px;
    }
    
    .mform .fitemtitle {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    input[type="text"], input[type="email"], input[type="password"] {
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        padding: 10px 12px;
        width: 100%;
        max-width: 400px;
        transition: all 0.3s;
    }
    
    input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
        border-color: #1abc9c;
        box-shadow: 0 0 0 2px rgba(26, 188, 156, 0.1);
        outline: none;
    }
    
    input[type="text"]:required, input[type="email"]:required, input[type="password"]:required {
        border-left: 3px solid #e74c3c;
    }
    
    /* Email warning styling */
    .email-warning {
        background-color: #fff8e1;
        border-left: 4px solid #ff9800;
        padding: 12px 15px;
        margin: 15px 0;
        border-radius: 4px;
    }
    
    .email-warning p {
        margin: 0;
        color: #e65100;
        font-weight: 600;
    }
    
    .email-warning i {
        margin-right: 10px;
        color: #ff9800;
    }
    
    /* Submit button styling */
    #id_submitbutton {
        background: linear-gradient(135deg, #294b6a 0%, #2c3e50 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 20px;
        display: block;
        width: 200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    #id_submitbutton:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(41, 75, 106, 0.2);
    }
    
    /* Password toggle styling */
    #show-password {
        margin-right: 8px;
    }
    
    .fa-eye, .fa-eye-slash {
        color: #6c757d;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .mform fieldset {
            padding: 15px;
        }
        
        input[type="text"], input[type="email"], input[type="password"] {
            max-width: 100%;
        }
        
        #id_submitbutton {
            width: 100%;
            max-width: 200px;
        }
    }
</style>';

// Add JavaScript for real-time validation
echo '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Password show/hide toggle
        const passwordField = document.getElementById("id_password");
        const confirmPasswordField = document.getElementById("id_confirmpassword");
        const showPasswordCheckbox = document.getElementById("show-password");
        const phoneField = document.getElementById("id_phone1");
        const usernameField = document.getElementById("id_username");
        
        if (showPasswordCheckbox && passwordField) {
            // Get the label element
            const showPasswordLabel = showPasswordCheckbox.nextElementSibling;
            
            showPasswordCheckbox.addEventListener("change", function() {
                if (this.checked) {
                    passwordField.type = "text";
                    confirmPasswordField.type = "text";
                    if (showPasswordLabel) {
                        // Change icon and text
                        const icon = showPasswordLabel.querySelector("i");
                        if (icon) {
                            icon.className = "fa fa-eye-slash";
                        }
                        showPasswordLabel.innerHTML = \'<i class="fa fa-eye-slash" style="margin-right: 5px;"></i>Hide Password\';
                    }
                } else {
                    passwordField.type = "password";
                    confirmPasswordField.type = "password";
                    if (showPasswordLabel) {
                        // Change icon and text
                        const icon = showPasswordLabel.querySelector("i");
                        if (icon) {
                            icon.className = "fa fa-eye";
                        }
                        showPasswordLabel.innerHTML = \'<i class="fa fa-eye" style="margin-right: 5px;"></i>Show Password\';
                    }
                }
            });
        }
        
        // Real-time phone number validation (only numbers, 10 digits)
        if (phoneField) {
            phoneField.addEventListener("input", function() {
                let phoneIndicator = document.getElementById("phone-validation");
                
                if (!phoneIndicator) {
                    phoneIndicator = document.createElement("div");
                    phoneIndicator.id = "phone-validation";
                    phoneIndicator.style.marginTop = "5px";
                    phoneIndicator.style.fontSize = "12px";
                    this.parentNode.appendChild(phoneIndicator);
                }
                
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, \'\');
                
                // Limit to 10 digits
                if (this.value.length > 10) {
                    this.value = this.value.substring(0, 10);
                }
                
                if (this.value.length === 10) {
                    phoneIndicator.innerHTML = \'<span style="color: #28a745; font-weight: bold;"><i class="fa fa-check" style="margin-right: 5px;"></i>Valid phone number</span>\';
                } else if (this.value.length > 0) {
                    phoneIndicator.innerHTML = \'<span style="color: #fd7e14; font-weight: bold;">Enter 10 digits: \' + this.value.length + \'/10</span>\';
                } else {
                    phoneIndicator.innerHTML = "";
                }
            });
            
            // Also validate on paste
            phoneField.addEventListener("paste", function(e) {
                setTimeout(function() {
                    phoneField.value = phoneField.value.replace(/[^0-9]/g, \'\');
                    if (phoneField.value.length > 10) {
                        phoneField.value = phoneField.value.substring(0, 10);
                    }
                    phoneField.dispatchEvent(new Event("input"));
                }, 0);
            });
        }
        
        // Real-time username validation (lowercase, no spaces, no @)
        if (usernameField) {
            usernameField.addEventListener("input", function() {
                let usernameIndicator = document.getElementById("username-validation");
                
                if (!usernameIndicator) {
                    usernameIndicator = document.createElement("div");
                    usernameIndicator.id = "username-validation";
                    usernameIndicator.style.marginTop = "5px";
                    usernameIndicator.style.fontSize = "12px";
                    this.parentNode.appendChild(usernameIndicator);
                }
                
                // Convert to lowercase
                this.value = this.value.toLowerCase();
                
                // Remove spaces and @ symbol
                this.value = this.value.replace(/[\s@]/g, \'\');
                
                // Limit to 20 characters
                if (this.value.length > 20) {
                    this.value = this.value.substring(0, 20);
                }
                
                if (this.value.length === 0) {
                    usernameIndicator.innerHTML = "";
                } else if (this.value !== this.value.toLowerCase()) {
                    usernameIndicator.innerHTML = \'<span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times" style="margin-right: 5px;"></i>Must be lowercase</span>\';
                } else if (this.value.includes(\'@\')) {
                    usernameIndicator.innerHTML = \'<span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times" style="margin-right: 5px;"></i>Cannot contain @ symbol</span>\';
                } else if (/\s/.test(this.value)) {
                    usernameIndicator.innerHTML = \'<span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times" style="margin-right: 5px;"></i>Cannot contain spaces</span>\';
                } else if (this.value.length > 20) {
                    usernameIndicator.innerHTML = \'<span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times" style="margin-right: 5px;"></i>Maximum 20 characters</span>\';
                } else {
                    usernameIndicator.innerHTML = \'<span style="color: #28a745; font-weight: bold;"><i class="fa fa-check" style="margin-right: 5px;"></i>Valid username format</span>\';
                }
            });
            
            // Also validate on paste
            usernameField.addEventListener("paste", function(e) {
                setTimeout(function() {
                    usernameField.value = usernameField.value.toLowerCase().replace(/[\s@]/g, \'\');
                    if (usernameField.value.length > 20) {
                        usernameField.value = usernameField.value.substring(0, 20);
                    }
                    usernameField.dispatchEvent(new Event("input"));
                }, 0);
            });
        }
        
        // Real-time password strength indicator
        if (passwordField) {
            passwordField.addEventListener("input", function() {
                const password = this.value;
                let strengthIndicator = document.getElementById("password-strength");
                
                if (!strengthIndicator) {
                    strengthIndicator = document.createElement("div");
                    strengthIndicator.id = "password-strength";
                    strengthIndicator.style.marginTop = "5px";
                    strengthIndicator.style.fontSize = "12px";
                    this.parentNode.appendChild(strengthIndicator);
                }
                
                let strength = 0;
                let message = "";
                let color = "#dc3545";
                
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[@$!%*?&#]/.test(password)) strength++;
                
                switch(strength) {
                    case 0:
                    case 1:
                        message = "Very Weak";
                        color = "#dc3545";
                        break;
                    case 2:
                        message = "Weak";
                        color = "#fd7e14";
                        break;
                    case 3:
                        message = "Fair";
                        color = "#ffc107";
                        break;
                    case 4:
                        message = "Good";
                        color = "#28a745";
                        break;
                    case 5:
                        message = "Strong";
                        color = "#20c997";
                        break;
                }
                
                strengthIndicator.innerHTML = `<span style="color: ${color}; font-weight: bold;">${message}</span>`;
            });
        }
        
        // Real-time password match indicator
        if (confirmPasswordField) {
            confirmPasswordField.addEventListener("input", function() {
                let matchIndicator = document.getElementById("password-match");
                
                if (!matchIndicator) {
                    matchIndicator = document.createElement("div");
                    matchIndicator.id = "password-match";
                    matchIndicator.style.marginTop = "5px";
                    matchIndicator.style.fontSize = "12px";
                    this.parentNode.appendChild(matchIndicator);
                }
                
                if (passwordField && passwordField.value === this.value && this.value !== "") {
                    matchIndicator.innerHTML = \'<span style="color: #28a745; font-weight: bold;"><i class="fa fa-check" style="margin-right: 5px;"></i>Passwords match</span>\';
                } else if (this.value !== "") {
                    matchIndicator.innerHTML = \'<span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times" style="margin-right: 5px;"></i>Passwords do not match</span>\';
                } else {
                    matchIndicator.innerHTML = "";
                }
            });
        }
    });
    
    // Add Font Awesome if not already loaded
    if (!document.querySelector("link[href*=\'font-awesome\']")) {
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";
        document.head.appendChild(link);
    }
</script>';

$mform->display();
echo $OUTPUT->footer();
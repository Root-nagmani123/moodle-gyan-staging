<?php

// global $CFG;
// require_once("$CFG->libdir/formslib.php");

// class simplehtml_form extends moodleform
// {
//     //Add elements to form
//     public function definition()
//     {

//         $mform = $this->_form; // Don't forget the underscore! 
//         $id =  $this->_customdata['formid'];
//         $mform->addElement('hidden', 'formid', $id);
//         $mform->setType('formid', PARAM_INT);

//         $page =  $this->_customdata['page'];
//         $mform->addElement('hidden', 'page', $page);
//         $mform->setType('page', PARAM_INT);

//         $mform->addElement('text', 'name', get_string('formname', 'local_form'));
//         $mform->addRule('name', null, 'required', null, 'client');
//         $mform->setType('name', PARAM_TEXT);

//         $mform->addElement('textarea', 'description', get_string('description', 'local_form'),'wrap="virtual" rows="10" cols="2"');
//         $mform->addRule('description', 'you must fill this value', 'required', null, 'client');
//         $mform->setType('description',PARAM_TEXT);

//         $mform->addElement('advcheckbox', 'visible', get_string('showonmainpage', 'local_form'), 0);


//         $this->add_action_buttons();
//     }
// }


global $CFG;
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform
{
    // Add elements to the form
    public function definition()
    {
        $mform = $this->_form; // Don't forget the underscore! 

        // Hidden fields
        $id =  $this->_customdata['formid'];
        $mform->addElement('hidden', 'formid', $id);
        $mform->setType('formid', PARAM_INT);

        $page =  $this->_customdata['page'];
        $mform->addElement('hidden', 'page', $page);
        $mform->setType('page', PARAM_INT);

        // Name field
        $mform->addElement('text', 'name', get_string('formname', 'local_form'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        // Short Name field
        $mform->addElement('text', 'shortname', get_string('formshortname', 'local_form'));
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addRule('shortname', get_string('shortnamemaxlength', 'local_form'), 'maxlength', 100, 'client');
        $mform->setType('shortname', PARAM_TEXT);

        // Description field
        $mform->addElement('textarea', 'description', get_string('description', 'local_form'), 'wrap="virtual" rows="10" cols="50"');
        $mform->addRule('description', get_string('descriptionrequired', 'local_form'), 'required', null, 'client');
        $mform->setType('description', PARAM_TEXT);

        // Visibility checkbox
        $mform->addElement('advcheckbox', 'visible', get_string('showonmainpage', 'local_form'));

        // Create fc_registration checkbox
        $mform->addElement('advcheckbox', 'fc_registration', get_string('fcregister', 'local_form'));

        // Create Cohort checkbox
        $mform->addElement('advcheckbox', 'createcohort', get_string('createcohort', 'local_form'));

        // Add action buttons (Save and Cancel)
        $this->add_action_buttons();
    }

    // Add custom validation
    public function validation($data, $files)
    {
        $errors = [];

        // Validate name
        if (empty(trim($data['name']))) {
            $errors['name'] = get_string('namerequired', 'local_form');
        }

        // Validate short name
        if (empty(trim($data['shortname']))) {
            $errors['shortname'] = get_string('shortnamerequired', 'local_form');
        } elseif (strlen($data['shortname']) > 100) {
            $errors['shortname'] = get_string('shortnametoolong', 'local_form');
        }

        // Validate description
        if (empty(trim($data['description']))) {
            $errors['description'] = get_string('descriptionrequired', 'local_form');
        }

        return $errors;
    }
}

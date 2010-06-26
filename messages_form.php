<?php
require_once($CFG->libdir.'/formslib.php');

class messages_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('date_selector', 'date_from', get_string('since'), array('optional'=>true));
        $mform->addElement('date_selector', 'date_to', get_string('until', 'report_messages'), array('optional'=>true));

        $actionbuttons = array();
        $actionbuttons[] = &$mform->createElement('submit', 'update', get_string('update', 'report_messages'));
        $actionbuttons[] = &$mform->createElement('submit', 'deleteall', get_string('deleteall', 'report_messages'));
        $mform->addGroup($actionbuttons, 'actionbuttons', '', '&nbsp;', false);
    }
}

?>

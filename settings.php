<?php
$ADMIN->add('reports', new admin_externalpage('reportmessages', get_string('title', 'report_messages'), "$CFG->wwwroot/$CFG->admin/report/messages/index.php", 'report/messages:view'));
?>

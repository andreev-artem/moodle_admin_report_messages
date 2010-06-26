<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once('messages_form.php');
    
    $delselected = optional_param('deleteselected', false, PARAM_BOOL);
    $timestart = optional_param('timestart', 0, PARAM_INT);
    $timeend = optional_param('timeend', time(), PARAM_INT);
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    $strselusers = optional_param('selusers', "", PARAM_RAW);
    
    admin_externalpage_setup('reportmessages');
    admin_externalpage_print_header();

    $tablecolumns = array('userpic', 'fullname', 'email', 'lastmessage', 'totalmessages', 'select');
    $tableheaders = array(get_string('userpic'), get_string('fullname'), get_string('email'),
                          get_string('lastmessage', 'report_messages'), get_string('totalmessages', 'report_messages'),
                          get_string('select'));
    
    $table = new flexible_table('user-messages');

    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->sortable(true, 'totalmessages', SORT_DESC);
    $table->no_sorting('select');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'personal-messages');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('width', '95%');

    $table->setup();

    $mform = new messages_form();
    $mform->set_data(array( 'date_from' => $timestart, 'date_to' => $timeend ));
    if ($formdata = $mform->get_data(false)) {
        if ($formdata->date_from) {
            $timestart = $formdata->date_from;
        }
        if ($formdata->date_to) {
            $timeend = $formdata->date_to;    
        }
    }
    $timeendreal = $timeend + 86399;    // end is the beginning of the next day
    $dateselect = "timecreated >= $timestart AND timecreated <= $timeendreal";
    $deleted = false;
    
    $baseurl = $CFG->wwwroot.'/admin/report/messages/index.php?timestart='. $timestart. '&timeend='. $timeend;
    $table->define_baseurl($baseurl);
        
    if ($confirm) {
        $delquery = $dateselect;
        if ($strselusers) {
             $delquery .= " AND useridto IN (". $strselusers. ")";
        }
        delete_records_select('message', $delquery);
        delete_records_select('message_read', $delquery);
        $deleted = true;
    } else {
        if ($delselected) {
            $selusers = array();
            foreach ($_POST as $k => $v) {
                if (preg_match('#^user_(\d+)$#',$k,$m)) {
                    $selusers[] = $m[1];
                }
            }
            $strselusers = implode(',', $selusers);
            if ($strselusers) {
                $SQL = "SELECT u.id, u.firstname, u.lastname
                		FROM {$CFG->prefix}user as u
                		WHERE u.id IN (". $strselusers. ")";
                $users = get_records_sql($SQL);
                $usernames = array();
                foreach ($users as $user) {
                    $usernames[] = fullname($user);
                }
                $usernames = implode('<br />', $usernames);
            }
        }
    
        if ($strselusers || ($formdata && array_key_exists('deleteall', $formdata))) {
            $optionsyes = array();
            $optionsyes['confirm'] = true;
            $optionsyes['timestart'] = $timestart;
            $optionsyes['timeend'] = $timeend;
            $optionsyes['selusers'] = $strselusers;
            $optionsno = array();
            $optionsno['timestart'] = $timestart;
            $optionsno['timeend'] = $timeend;
            
            print_heading(get_string('confirmation', 'admin'));
            $confmsg = get_string('confpart1', 'report_messages');
            if ($delselected) {
                $confmsg .= get_string('confpart2', 'report_messages');
                $confmsg .= '<br />';
                $confmsg .= $usernames;
                $confmsg .= '<br />';
            }
            if ($timestart) {
                $confmsg .= get_string('confsince', 'report_messages') . userdate($timestart);
            }
            $confmsg .= get_string('confuntil', 'report_messages') . userdate($timeendreal) . '?';
                        
            notice_yesno($confmsg, 'index.php', 'index.php', $optionsyes, $optionsno, 'post', 'post');
            admin_externalpage_print_footer();
            die;
        }
    }
    
    $SQL = "SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt, u.email, stats.lastmessage, stats.totalmessages FROM (
            SELECT useridto, MAX( timecreated ) AS lastmessage, COUNT( timecreated ) AS totalmessages FROM (
            SELECT useridto, timecreated FROM {$CFG->prefix}message
            UNION SELECT useridto, timecreated FROM {$CFG->prefix}message_read )
            AS unimess WHERE $dateselect GROUP BY useridto )
            AS stats INNER JOIN {$CFG->prefix}user AS u ON stats.useridto = u.id ORDER BY ". $table->get_sql_sort();
    
    $users = get_records_sql($SQL);
    $table->pagesize(20, count($users));
    
    $total = 0;
    $counter = 0;
    $pagestart = $table->get_page_start();
    if (!empty($users)) {
        foreach ($users as $user) {
            $total += $user->totalmessages;
            $counter++;
            if($counter - 1 < $pagestart || $counter > $pagestart + 20) {
                continue;
            }
            $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'">'.fullname($user).'</a></strong>';
            $data = array(print_user_picture($user, 1, NULL, false, true, false),
                          $profilelink, $user->email, userdate($user->lastmessage), $user->totalmessages, 
                          '<input type="checkbox" name="user_'.$user->id.'" />' );
            $table->add_data($data);
        }
    }

    print_heading(get_string('title', 'report_messages'));
    if ($deleted) {
        echo '<div align="center">' . get_string('deleted', 'report_messages') . '</div><br />';       
    }
    echo '<form action="'. $baseurl. '" method="POST">';
    $table->print_html();
    echo '<div align="center">'. get_string('total', 'report_messages', $total). '<br />';
    echo '<input type="hidden" name="timestart" value="'. $timestart. '" />';
    echo '<input type="hidden" name="timeend" value="'. $timeend. '" />';
    echo '<input type="submit" name="deleteselected" value="'. get_string('deleteselected'). '" /></div></form>';
    $mform->display();        

    admin_externalpage_print_footer();
?>

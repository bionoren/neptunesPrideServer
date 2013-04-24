<?php
    /**
     curl "localhost/~bion/neptunesPride/index.php" --data 'data={"game":"1429278", "ACSID":"AJKiYcErvMYfKZuQRkLbhFJm5ih-UZ68MtmdZYnfNiePAodZwKXX_bf_TGjwLpmnA2lxz2Qkjw4TReXpK8q0JIBzcw_jd1H4d3oN0Clj_4kob983TM2s2rc0N7emw_OPtw1JH_DWB9N2iix2CWcwS9IPuw_GCsBXW3kQ722CkLDBIr9akiKGtY4z1TJLWiAMcIgJKmaXTapMgg8mXoB3Fr5reOpMh9raJ-Z_WzNlH1bjWc6cmJaznxbKDQB5gifiXtgqgXEHi_T9Fd8ykXJDI0q_8RDyQW4_TR0G0KJoFwk24X1Mtva2htZCy9cWhUCt4IxP96SqCg0p5RroxAATu4QxdCtNAv75ZEyNMTyxU0XsPns6gtIbjHIK7CQkDGvU3GjpPcpz7pmHGlLwFh57MrtmAmjcByRV3rdW9erK1SHYECdPM8iePZbVovcXuzdIUPfolvnxGDo2_3pZznGz4GP1JvCv9r2BZrt6L7pjMnDLlkIWEh9jTChHpf9kXzV9jFklrt3W-omWkRw9IbYdEglx9jMw7-JrmCGzp5qzG_p2g6yk37onJWo"}'
    */

    $path = "./";
    require_once($path.'db/SQLiteManager.php');
    require_once($path.'functions.php');

    $db = SQLiteManager\SQLiteManager::getInstance();

    if($_POST['data']) {
        $data = json_decode($_POST['data']);
        switch(json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                print ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                print ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                print ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                print ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                print ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                print ' - Unknown error';
                break;
        }
        $cookie = $data->{'ACSID'};
        $game = $data->{'game'};

        $authData = getGameData($game, $cookie);
        unset($cookie);

        $report = $authData->{'report'};
        $uid = $report->{'player_uid'};
        if(!is_null($uid) && $uid != -1) {
            $starFields = array('uid', 'ships', 'naturalResources', 'economy', 'garrison', 'industry', 'science');
            $fleetFields = array('uid', 'x', 'y', 'ships', 'puid', 'destuid', 'orbitinguid');

            $result = $db->select('users', array('ID'), array('uid'=>$uid, 'gameID'=>$game));
        	$user = $db->fetchArray($result);
            $user = $user[0];
            if(!$user) {
                $db->insert('users', array('uid'=>$uid, 'gameID'=>$game));
                $result = $db->select('users', array('ID'), array('uid'=>$uid));
            	$user = $db->fetchArray($result);
                $user = $user[0] or die('Insert fail');
            }

            $gameTime = $data->{'gameTime'};
            $tick = $data->{'tick'};
            $tickFragment = $data->{'tickFragment'};

            $action = $data->{'action'};
            if($action == 'push') {
                $gameTime = $data->{'gameTime'};
                $starData = $data->{'starData'};
                $fleetData = $data->{'fleetData'};

                $fields = array();
                $fields['userID'] = $user['ID'];
                $fields['gameTime'] = $gameTime;
                $fields['tick'] = $tick;
                $fields['tickFragment'] = $tickFragment;
                foreach($starData as $star) {
                    foreach($starFields as $field) {
                        $fields[$field] = $star->{$field};
                    }

                    $db->insert("stars", $fields);
                }

                $fields = array();
                $fields['userID'] = $user['ID'];
                $fields['gameTime'] = $gameTime;
                $fields['tick'] = $tick;
                $fields['tickFragment'] = $tickFragment;
                foreach($fleetData as $fleet) {
                    foreach($fleetFields as $field) {
                        $fields[$field] = $fleet->{$field};
                    }

                    $db->insert("fleets", $fields);
                }

                $db->update('users', array('lastUpdated'=>$gameTime), array('ID'=>$user['ID']));
            } elseif($action == 'pull') {
                $result = $db->query('SELECT * FROM users WHERE userID='.$user['ID'].' OR userID2='.$user['ID']);
                $shareRows = $db->fetchArray($result);
                print '{';
                $i = 0;
                foreach($shareRows as $shareRow) {
                    $otherUser = ($shareRow['userID'] == $user['ID'])?$shareRow['userID2']:$shareRow['userID'];
                    $result = $db->query('SELECT * FROM star WHERE userID='.$user['ID']);
                    $stars = $db->fetchArray($result);
                    print '"'.$i++.'":{';
                    $j = 0;
                    foreach($stars as $star) {
                        print '"'.$j++.'":{';
                        //TODO: print star data
                        print '},';
                    }
                    print '},';
                    /*
	$fields[] = new DBField("uid", DBField::NUM);
	$fields[] = new DBField("economy", DBField::NUM);
    $fields[] = new DBField("buildRate", DBField::NUM);
	$fields[] = new DBField("garrison", DBField::NUM);
    $fields[] = new DBField("industry", DBField::NUM);
    $fields[] = new DBField("naturalResources", DBField::NUM);
    $fields[] = new DBField("resources", DBField::NUM);
    $fields[] = new DBField("science", DBField::NUM);
    $fields[] = new DBField("ships", DBField::NUM);
    $fields[] = new DBField("gameTime", DBField::NUM);
    $fields[] = new DBField("tick", DBField::NUM);
    $fields[] = new DBField("tick_fragment", DBField::NUM);*/

                }
                print '}';
            } elseif($action == 'share') {
                $shareID = $data->{'shareID'};
                //TODO: look for a request with me and pendingID = shareID
                //if you don't find it, create it
            } elseif($action == 'unshare') {
                $shareID = $data->{'shareID'};
                //TODO: look for any pending requests or request with me as user or user2 with the other being shareID, and delete them
            } elseif($action == 'shares') {
                //TODO: return a list of my active and pending shares (them pending and me pending) along with lastUpdated timestamps
            }
        }
    }
?>
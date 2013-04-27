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
            $fleetFields = array('uid', 'name', 'x', 'y', 'ships', 'puid', 'destuid', 'orbitinguid');

            $result = $db->select('users', array('ID'), array('uid'=>$uid, 'gameID'=>$game));
        	$user = $db->fetchArray($result);
            $user = $user[0];
            if(!$user) {
                $db->insert('users', array('uid'=>$uid, 'gameID'=>$game));
                $result = $db->select('users', array('ID'), array('uid'=>$uid));
            	$user = $db->fetchArray($result);
                $user = $user[0] or die('Insert fail');
            }

            $action = $data->{'action'};
            if($action == 'push') {
                $gameTime = $data->{'gameTime'};
                $tick = $data->{'tick'};
                $tickFragment = $data->{'tickFragment'};

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
                foreach($shareRows as $shareRow) {
                    $otherUser = ($shareRow['userID'] == $user['ID'])?$shareRow['userID2']:$shareRow['userID'];
                    $result = $db->query('SELECT * FROM star WHERE userID='.$otherUser);
                    $stars = $db->fetchArray($result);

                    $result = $db->query('SELECT fleets.*, users.uid as puid FROM fleets JOIN users on fleets.userID=users.ID WHERE fleets.userID='.$otherUser);
                    $fleets = $db->fetchArray($result);
                    $ret = array('stars'=>$stars, 'fleets'=>$fleets);
                    print json_encode($ret);
                }
            } elseif($action == 'share') {
                $shareUserID = $data->{'shareUserID'};
                //look for a request with them and me
                $result = $db->select('shares', array('ID'), array('userID'=>$shareUserID, 'userID2'=>$user['ID']));
                $shares = $db->fetchArray($result);
                if(count($shares) > 0) {
                    //we found it, so confirm it
                    $db->update('shares', array('pending'=>0), array('ID'=>$shares[0]['ID']));
                    print json_encode(array("reload"=>true));
                } else {
                    //if you don't find it, create it
                    $db->insert('shares', array('userID'=>$user['ID'], 'userID2'=>$shareUserID));
                    print json_encode(array("reload"=>false));
                }
            } elseif($action == 'unshare') {
                $shareUserID = $data->{'shareUserID'};
                $db->query('DELETE FROM shares WHERE (userID='.$user['ID'].' and userID2='.$shareUserID.') or (userID='.$shareUserID.' and userID2='.$user['ID'].')');
            } elseif($action == 'shares') {
                //return a list of my active and pending shares (them pending and me pending) along with lastUpdated timestamps
                $result = $db->query('SELECT shares.*, users1.lastUpdated, users2.lastUpdated FROM shares JOIN users as users1 on shares.userID=users1.ID JOIN users as users2 on shares.userID2=users2.ID WHERE userID='.$user['ID'].' || userID2='.$user['ID']);
                $shares = $db->fetchArray($result);
                $result = $db->query('SELECT * FROM shares WHERE pending=1 and (userID='.$user['ID'].' or userID2='.$user['ID'].')');
                $pending = $db->fetchArray($result);
                print json_encode(array_merge($shares, $pending), JSON_NUMERIC_CHECK);
            }
        }
    }
?>
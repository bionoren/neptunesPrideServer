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
        printJsonError();

        $cookie = $data->{'cookie'};
        $game = $data->{'game'};

        $authData = getGameData($game, $cookie);
        unset($cookie);

        $report = $authData->{'report'};
        $uid = $report->{'player_uid'};
        if(!is_null($uid) && $uid != -1) {
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

                $reportData = $data->{'data'};
                $db->insert('reports', array('userID'=>$user['ID'], 'gameTime'=>$gameTime, 'tick'=>$tick, 'tickFragment'=>$tickFragment, 'data'=>serialize($reportData)));

                $db->update('users', array('lastUpdated'=>$gameTime), array('ID'=>$user['ID']));
            } elseif($action == 'pull') {
                $tick = $data->{'tick'};

                $result = $db->query('SELECT * FROM shares WHERE (userID='.$user['ID'].' OR userID2='.$user['ID'].') AND tick='.$tick);
                $shareRows = $db->fetchArray($result);
                $otherUser = ($shareRow['userID'] == $user['ID'])?$shareRow['userID2']:$shareRow['userID'];
                $result = $db->query('SELECT * FROM reports WHERE userID='.$otherUser);
                $reports = $db->fetchArray($result);
                foreach($reports as &$report) {
                    $report['data'] = unserialize($report['data']);
                }

                print json_encode($reports, JSON_NUMERIC_CHECK);
            } elseif($action == 'share') {
                $shareUserID = $data->{'shareUserID'};
                //look for a request with them and me
                $result = $db->select('shares', array('ID'), array('userID'=>$shareUserID, 'userID2'=>$user['ID']));
                $shares = $db->fetchArray($result);
                if(count($shares) > 0) {
                    //we found it, so confirm it
                    $db->update('shares', array('pending'=>0), array('ID'=>$shares[0]['ID']));
                    print json_encode(array("reload"=>true), JSON_NUMERIC_CHECK);
                } else {
                    //if you don't find it, create it
                    $db->insert('shares', array('userID'=>$user['ID'], 'userID2'=>$shareUserID));
                    print json_encode(array("reload"=>false), JSON_NUMERIC_CHECK);
                }
            } elseif($action == 'unshare') {
                $shareUserID = $data->{'shareUserID'};
                $db->query('DELETE FROM shares WHERE (userID='.$user['ID'].' AND userID2='.$shareUserID.') OR (userID='.$shareUserID.' AND userID2='.$user['ID'].')');
            } elseif($action == 'shares') {
                //return a list of my active and pending shares (them pending and me pending) along with lastUpdated timestamps
                $result = $db->query('SELECT shares.*, users1.lastUpdated, users2.lastUpdated FROM shares JOIN users as users1 on shares.userID=users1.ID JOIN users as users2 on shares.userID2=users2.ID WHERE (userID='.$user['ID'].' OR userID2='.$user['ID'].') AND pending=0');
                $shares = $db->fetchArray($result);
                $result = $db->query('SELECT * FROM shares WHERE pending=1 AND (userID='.$user['ID'].' OR userID2='.$user['ID'].')');
                $pending = $db->fetchArray($result);
                print json_encode(array_merge($shares, $pending), JSON_NUMERIC_CHECK);
            }
        }
    }
?>
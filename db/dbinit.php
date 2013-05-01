<?php
	namespace SQLiteManager;

    /*
	 *	Copyright 2010 Bion Oren
	 *
	 *	Licensed under the Apache License, Version 2.0 (the "License");
	 *	you may not use this file except in compliance with the License.
	 *	You may obtain a copy of the License at
	 *		http://www.apache.org/licenses/LICENSE-2.0
	 *	Unless required by applicable law or agreed to in writing, software
	 *	distributed under the License is distributed on an "AS IS" BASIS,
	 *	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 *	See the License for the specific language governing permissions and
	 *	limitations under the License.
	 */

    require_once("SQLiteManager.php");

    $db = SQLiteManager::getInstance();

	//errorLog
	$fields = array();
	$fields[] = new DBField("query", DBField::STRING);
	$fields[] = new DBField("error", DBField::STRING);
	$fields[] = new DBField("date", DBField::NUM);
	$db->createTable("errorLog", $fields);

    //users
	$fields = array();
	$fields[] = new DBField("uid", DBField::NUM);
    $fields[] = new DBField("gameID", DBField::STRING);
    $fields[] = new DBField("lastUpdated", DBField::NUM);
	$db->createTable("users", $fields);

    //shares
    $fields = array();
	$fields[] = new DBField("userID", DBField::NUM, -1 , "users", "ID");
    $fields[] = new DBField("userID2", DBField::NUM, -1);
    $fields[] = new DBField("pending", DBField::NUM, 1); //NOTE: you're always waiting on userID2
	$db->createTable("shares", $fields);

    //reports
    $fields = array();
	$fields[] = new DBField("userID", DBField::NUM, -1 , "users", "ID");
    $fields[] = new DBField("gameTime", DBField::NUM, 0);
    $fields[] = new DBField("tick", DBField::NUM, -1);
    $fields[] = new DBField("tickFragment", DBField::NUM, 0);
    $fields[] = new DBField("data", DBField::STRING);
	$db->createTable("reports", $fields);
?>
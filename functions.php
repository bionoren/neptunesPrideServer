<?php
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

	require_once($path."db/SQLiteManager.php");

	//-----------------------------
	//	   DEBUGGING FUNCTIONS
	//-----------------------------

	/**
     * Useful debug function that displays variables or arrays in a pretty format.
     *
     * @param STRING $name Name of the array (for pretty display purposes).
     * @param MIXED $array Array of data, but if it isn't an array we try to print it by itself.
     * @param STRING $member Calls a function on $array when outputing $array (assumes $array is an object or array of objects).
     * @return VOID
     */
	function dump($name, $array, $member=null) {
		if(is_array($array) || (is_object($array) && $array instanceof Iterator)) {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".htmlentities($val)."<br/>\n";
                    } else {
                        print $name."[".$key."] = ".htmlentities($val->{$member}())."<br/>\n";
                    }
                }
			}
		} else {
            if($member == null) {
    			print "$name = ".htmlentities($array)."<br/>\n";
            } else {
                print "$name = ".htmlentities($array->{$member}())."<br/>\n";
            }
        }
	}

    //-----------------------------
	//			FUNCTIONS
	//-----------------------------
	/**
	 * Generates a random string.
	 */
	function generateHash() {
		return $hash = md5(time()."This is a randomstring");
	}

	/**
	 * Ye generic encryption function.
	 * @param STRING $password Password to encrypt.
	 * @param STRING $hash Salt to use for the encryption algorithm.
	 * @return STRING Encrypted, salted password.
	 */
	function encrypt($password, $hash) {
		return hash("sha512", $hash.$password.$hash);
	}

    /**
     * Loads game data from ironhelmet.com into JSON.
     * @param STRING $game Game number
     * @param STRING $cookie Cookie information to use in authenticating the request.
     * @return STRING decoded JSON game data.
     */
    function getGameData($game, $cookie) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, 'http://triton.ironhelmet.com/grequest/order');
        curl_setopt($curl, CURLOPT_COOKIE, trim($cookie));
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'type=order&order=full_universe_report&game_number='.$game);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($curl);
        curl_close($curl);
        $ret = json_decode($json);
        printJsonError();
        return $ret;
    }

    /**
     * Prints an error message for the last json decode.
     * @return INT result of json_last_error()
     */
    function printJsonError() {
        $ret = json_last_error();
        switch($ret) {
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
        return $ret;
    }
?>
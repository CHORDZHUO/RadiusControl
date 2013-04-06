<?php
/*
  Copyright (C) 2013  Arthur Tumanyan <arthurtumanyan@yahoo.com>

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once 'config.inc';

class RadiusControl {

    var $_host;
    var $_port;
    var $_username;
    var $_passwd;
    var $_dbname;
    var $dblink = NULL;
    var $debug = FALSE;
    var $passwdType; // MD5 or plain text
    var $IOFormat;   // JSON or plain text

    public function __construct() {

        if ('json' == $this->getIOFormat()) {
            if (!extension_loaded('json')) {
                if (!dl('json.so')) {
                    return "JSON extension not loaded";
                }
            }
        }

        $this->setPasswdType();
        $this->setIOFormat();
        $this->setHost();
        $this->setPort();
        $this->setUsername();
        $this->setPasswd();
        $this->setDBName();
        if (true === $this->db_connect()) {
            if ($this->debug) {
                printf("Successfully connected to database: %s<br />\n", $this->getDBName());
            }
        }
    }

    private function db_connect() {

        $this->dblink = mysql_connect($this->_host, $this->_username, $this->_passwd, true);

        if (!$this->dblink) {
            return false;
        } else {
            if (empty($this->_dbname)) {
                
            } else {
                $db = mysql_select_db($this->_dbname, $this->dblink); // select db
                if (!$db) {
                    return false;
                }
            }
        }
    }

    public function __destruct() {
        if (NULL != $this->dblink) {
            mysql_close($this->dblink);
        }
    }

    public function setPasswdType($type = '') {
        $this->passwdType = (empty($type)) ? strtolower($GLOBALS['passwd_type']) : strtolower($type);
        if ($this->debug) {
            printf("Password type: %s<br />\n", $this->getPasswdType());
        }
    }

    public function getPasswdType() {
        return strtolower($this->passwdType);
    }

    public function setIOFormat($format = '') {
        $this->IOFormat = (empty($format)) ? strtolower($GLOBALS['io_format']) : strtolower($format);
        if ($this->debug) {
            printf("IO format: %s<br />\n", $this->getIOFormat());
        }
    }

    public function getIOFormat() {
        return strtolower($this->IOFormat);
    }

    public function setHost($host = '') {
        $this->_host = (empty($host)) ? $GLOBALS['radius_host'] : $host;
        if ($this->debug) {
            printf("Radius host: %s<br />\n", $this->getHost());
        }
    }

    public function setPort($port = 0) {
        $this->_port = ($port == 0) ? $GLOBALS['radius_port'] : $port;
        if ($this->debug) {
            printf("Radius port: %d<br />\n", $this->getPort());
        }
    }

    public function setUsername($username = '') {
        $this->_username = (empty($username)) ? $GLOBALS['radius_username'] : $username;
        if ($this->debug) {
            printf("Radius username: %s<br />\n", $this->getUsername());
        }
    }

    public function setPasswd($password = '') {
        $this->_passwd = (empty($password)) ? $GLOBALS['radius_passwd'] : $password;
        if ($this->debug) {
            printf("Radius password: %s<br />\n", $this->getPasswd());
        }
    }

    public function setDBName($dbname = '') {
        $this->_dbname = (empty($dbname)) ? $GLOBALS['radius_dbname'] : $dbname;
        if ($this->debug) {
            printf("Radius database name: %s<br />\n", $this->getDBName());
        }
    }

    public function getUsername() {
        return $this->_username;
    }

    public function getPasswd() {
        return $this->_passwd;
    }

    public function getHost() {
        return $this->_host;
    }

    public function getPort() {
        return $this->_port;
    }

    public function getDBName() {
        return $this->_dbname;
    }

    private function JSONize($param) {
        if ('json' == $this->IOFormat) {
            return json_encode($param);
        } else {
            return $param;
        }
    }

    public function isValidAccountPasswordPair($account, $password) {

        $account = mysql_real_escape_string($account);
        $password = mysql_real_escape_string($password);

        if ('md5' == $this->passwdType) {
            $passwd_attribute = 'MD5-Password';
            $password = md5($password);
        } else {
            $passwd_attribute = 'Cleartext-Password';
        }

        $query = "SELECT COUNT(*) FROM `radcheck` WHERE `username`='$account' AND `value`='$password' AND `attribute`='$passwd_attribute' AND `op`=':='";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return sprintf("Error: %s", mysql_error($this->dblink));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];

        if (0 == $count) {
            return false;
        } else {
            return true;
        }
    }

    private function AccountExists($account) {
        $account = mysql_real_escape_string($account);
        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$account'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return sprintf("Error: %s", mysql_error($this->dblink));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];

        if (0 == $count) {
            return false;
        } else {
            return true;
        }
    }

    /* Account exists */

    private function genAccountUserPassPair($prefix = '', $suffix = '') {

        $pair = array();
        $uname = array();
        $pwd = array();
//
        if (!isset($prefix))
            $prefix = "";
        if (!isset($suffix))
            $suffix = "";

        $hashAlphaLower = range('a', 'z');
        $hashAlphaUpper = range('A', 'z');

        for ($i = 1; $i <= 4; $i++) {
            //
            mt_srand(time() + (double) microtime() * 1000000);
            $r1 = rand(0, 25);
            $r2 = rand(0, 25);
            $d1 = rand(0, 9);
            $d2 = rand(0, 9);
//
            if (($i % 2) == 0) {
                $pwd[$i] = sprintf("%d%s", $d1, $hashAlphaLower[$r1]);
                $uname[$i] = sprintf("%d%s", $d2, $hashAlphaUpper[$r2]);
            } else {
                $pwd[$i] = sprintf("%d%s", $d2, $hashAlphaUpper[$r2]);
                $uname[$i] = sprintf("%d%s", $d1, $hashAlphaLower[$r1]);
            }
        }

        $pair[0] = sprintf("%s%s%s%s%s%s", $prefix, $uname[1], $uname[2], $uname[3], $uname[4], $suffix);
        $pair[1] = sprintf("%s%s%s%s", $pwd[1], $pwd[2], $pwd[3], $pwd[4]);
//
        return $pair;
    }

    public function CreateAccount($params) {

        $username = mysql_real_escape_string($params["username"]);
        $password = mysql_real_escape_string($params["password"]);
        $groupname = mysql_real_escape_string($params["groupname"]);

        $expiration = $this->date2str($params["expire"]);
        $simultaneous = $params["simultaneous"];

        if ('md5' == $this->passwdType) {
            $passwd_attribute = 'MD5-Password';
            $password = md5($password);
        } else {
            $passwd_attribute = 'Cleartext-Password';
        }

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 < $count) {
            return $this->JSONize("Username already exists");
        }
        $query = "INSERT INTO radcheck (username,attribute,value,op) VALUES ('$username','$passwd_attribute','$password',':=')";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }

        $query = "INSERT INTO radcheck (username,attribute,value,op) VALUES ('$username','Expiration','$expiration',':=')";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }

        $query = "INSERT INTO radcheck (username,attribute,value,op) VALUES ('$username','Simultaneous-Use','$simultaneous',':=')";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }

        $query = "SELECT COUNT(*) FROM radusergroup WHERE username='$username' and groupname='$groupname'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 < $count) {
            return $this->JSONize("User is already in this group");
        }

        $query = "INSERT INTO radusergroup (username,groupname) VALUES ('$username','$groupname')";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Create account */

    public function SuspendAccount($params) {

        $username = mysql_real_escape_string($params["username"]);

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];

        if (0 == $count) {
            return $this->JSONize("User Not Found");
        }
        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username' AND attribute='Auth-Type' AND value='Reject' AND op=':='";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            $query = "INSERT INTO radcheck (username,attribute,value,op) VALUES ('$username','Auth-Type','Reject',':=')";
        } else {
            return $this->JSONize("User Already Suspended");
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Suspend Account */

    public function UnsuspendAccount($params) {

        $username = mysql_real_escape_string($params["username"]);

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username' AND attribute='Auth-Type' AND value='Reject' AND op=':='";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            return $this->JSONize("User Not Currently Suspended");
        }
        $query = "DELETE FROM radcheck WHERE username='$username' AND attribute='Auth-Type' AND value='Reject' AND op=':='";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Unsuspend Account */

    public function DeleteAccount($params) {

        $username = mysql_real_escape_string($params["username"]);

        $query = "DELETE FROM radreply WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $query = "DELETE FROM radusergroup WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $query = "DELETE FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }

        return true;
    }

    /* Delete account */

    public function FlushAccounts() {

        $queries[0] = "DELETE FROM radreply";
        $queries[1] = "DELETE FROM radusergroup";
        $queries[2] = "DELETE FROM radcheck";
        $queries[3] = "DELETE FROM radacct";
        $queries[4] = "DELETE FROM radgroupcheck";
        $queries[5] = "DELETE FROM radgroupreply";
        $queries[6] = "DELETE FROM radpostauth";

        for ($i = 0; $i < count($queries); $i++) {
            $result = mysql_query($queries[$i], $this->dblink);
            if (!$result) {
                return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
            }
        }
        return true;
    }

    /* Flush accounts and any data related to them */

    public function ChangeExpireDate($params) {

        $username = mysql_real_escape_string($params["username"]);
        $expire = mysql_real_escape_string($params["expire"]);

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            return $this->JSONize("User Not Found");
        }
        $query = "UPDATE radcheck SET value='$expire' WHERE username='$username' AND attribute='Expiration'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Change expiration time */

    public function ChangeSimultaneousUse($params) {

        $username = mysql_real_escape_string($params["username"]);
        $simultaneous = mysql_real_escape_string($params["simultaneous"]);

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            return $this->JSONize("User Not Found");
        }
        $query = "UPDATE radcheck SET value='$simultaneous' WHERE username='$username' AND attribute='Simultaneous-Use'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Change simultaneous use count */

    public function ChangeAccountPasswd($params) {

        $username = mysql_real_escape_string($params["username"]);
        $password = mysql_real_escape_string($params["password"]);

        if ('md5' == $this->passwdType) {
            $passwd_attribute = 'MD5-Password';
            $password = md5($password);
        } else {
            $passwd_attribute = 'Cleartext-Password';
        }

        $query = "SELECT COUNT(*) FROM radcheck WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            return $this->JSONize("User Not Found");
        }
        $query = "UPDATE radcheck SET value='$password' WHERE username='$username' AND attribute='$passwd_attribute'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Change Account Password */

    public function ChangeAccoutGroup($params) {

        $username = mysql_real_escape_string($params["username"]);
        $groupname = mysql_real_escape_string($params["groupname"]);

        $query = "SELECT COUNT(*) FROM radusergroup WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        $data = mysql_fetch_array($result);
        $count = $data[0];
        if (0 == $count) {
            return $this->JSONize("User Not Found");
        }
        $query = "UPDATE radusergroup SET groupname='$groupname' WHERE username='$username'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    private function date2str($date) {

        $search = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        $replace = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

        $exploded_date = explode('-', $date);
        if(empty($exploded_date)){
            return $date;
        }
        return sprintf("%s %s %s", $exploded_date[2], str_replace($search, $replace, $exploded_date[1]), $exploded_date[0]);
    }

    /* Change account Group */

    public function generateAccounts($params) {

        $success = 0;
        $groupname = mysql_real_escape_string($params["groupname"]);
        $expiration = mysql_real_escape_string($this->date2str($params["expire"]));
        $simultaneous = mysql_real_escape_string($params["simultaneous"]);

        $prefix = (isset($params["prefix"]) ? $params["prefix"] : "");
        $suffix = (isset($params["suffix"]) ? $params["suffix"] : "");

        $count = $params["count"];

        for ($i = 1; $i <= $count; $i++) {
            do {
                list($account, $password) = $this->genAccountUserPassPair($prefix, $suffix);
            } while (false !== $this->AccountExists($account));

            $args["username"] = $account;
            $args["password"] = $password;
            $args["groupname"] = $groupname;
            $args["expire"] = $expiration;
            $args["simultaneous"] = $simultaneous;

            if (true == $this->CreateAccount($args)) {
                $success++;
            }
        }
        return $this->JSONize($success);
    }

    /* Generate accounts */

    public function AddAccountGroup($params) {

        $groupname = mysql_real_escape_string($params["groupname"]);

        $query = "INSERT INTO radusergroup (`groupname`) VALUES ('$groupname')";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Add account group */

    public function DeleteAccountGroup($params) {

        $groupname = mysql_real_escape_string($params["groupname"]);
        $query = "DELETE FROM radusergroup WHERE `groupname`='$groupname'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Delete account group */

    public function ChangeAccountGroupName($params) {

        $group_from = mysql_real_escape_string($params["group_from"]);
        $group_to = mysql_real_escape_string($params["group_to"]);
        $query = "UPDATE radusergroup SET `groupname`='$group_to' WHERE `groupname`='$group_from'";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        return true;
    }

    /* Change account group name */

    private function isSuspended($account) {

        $query = "SELECT * FROM radcheck WHERE username='$account' AND attribute='Auth-Type' AND value='Reject' AND op=':='";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        if (0 < mysql_num_rows($result)) {
            return true;
        }
        return false;
    }

    /* Is account suspended */

    public function GetAccounts($account = '') {
        $new_array = array();
        $rowc = 0;
        if (empty($account)) {
            $query[0] = "SELECT `radcheck`.`username` ,`radusergroup`.`groupname` as `groupname`, `value` as `simultaneous`  FROM `$this->_dbname`.`radcheck`,`$this->_dbname`.`radusergroup` WHERE `attribute`='Simultaneous-Use' and `radusergroup`.`username`= `radcheck`.`username`";
            $query[1] = "SELECT `value` as `expiration`  FROM `$this->_dbname`.`radcheck`,`$this->_dbname`.`radusergroup` where `attribute`='Expiration' and `radusergroup`.`username`= `radcheck`.`username`";
        } else {
            $account = mysql_real_escape_string($account);
            $query[0] = "SELECT `radcheck`.`username` ,`radusergroup`.`groupname` as `groupname`, `value` as `simultaneous`  FROM `$this->_dbname`.`radcheck`,`$this->_dbname`.`radusergroup` WHERE `radcheck`.`username`='$account' AND `attribute`='Simultaneous-Use' and `radusergroup`.`username`= `radcheck`.`username`";
            $query[1] = "SELECT `value` as `expiration`  FROM `$this->_dbname`.`radcheck`,`$this->_dbname`.`radusergroup` WHERE `radcheck`.`username`='$account' AND `attribute`='Expiration' and `radusergroup`.`username`= `radcheck`.`username`";
        }
        if ($this->debug) {
            echo "<p>$query[0]</p><br />";
        }
        if ($this->debug) {
            echo "<p>$query[1]</p><br />";
        }
        $result[0] = mysql_query($query[0], $this->dblink);
        $result[1] = mysql_query($query[1], $this->dblink);
        if (!$result[0]) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        if (!$result[1]) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        while ($row[0] = mysql_fetch_array($result[0], MYSQL_ASSOC)) {
            $row[1] = mysql_fetch_array($result[1], MYSQL_ASSOC);
            $new_array[$rowc] = $row[0];
            $new_array[$rowc]["expiration"] = $row[1]["expiration"];
            $new_array[$rowc]["status"] = ($this->isSuspended($row[0]['username'])) ? "Suspended" : "Active";
            ++$rowc;
        }
        return $this->JSONize($new_array);
    }

    public function GetAccountGroups() {
        $new_array = array();
        $rowc = 0;
        $query = "SELECT DISTINCT `groupname` FROM radusergroup";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $new_array[$rowc] = $row;
            ++$rowc;
        }
        return $this->JSONize($new_array);
    }

    /* Get account groups */

    public function GetOnlineAccounts() {
        $new_array = array();
        $rowc = 0;
        $query = "SELECT `username`,`groupname`,`nasipaddress`,`acctstarttime`,`callingstationid` FROM radacct WHERE `acctstoptime` IS NULL";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }

        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            if (is_array($row)) {
                $new_array[$rowc] = $row;
                ++$rowc;
            }
        }
        return $this->JSONize($new_array);
    }

    /* Get online accounts */

    public function GetLoginHistory($username) {
        $new_array = array();
        $rowc = 0;

        if (!empty($username)) {
            $username = mysql_real_escape_string($username);
            $s = "AND `username` = '$username'";
        } else {
            $s = "";
        }
        $query = "SELECT `username`,`groupname`,`nasipaddress`,`acctstarttime`,`acctstoptime`,`callingstationid` FROM radacct WHERE `acctstoptime` IS NOT NULL $s";
        if ($this->debug) {
            echo "<p>$query</p><br />";
        }
        $result = mysql_query($query, $this->dblink);
        if (!$result) {
            return $this->JSONize(sprintf("Error: %s", mysql_error($this->dblink)));
        }
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            if (is_array($row)) {
                $new_array[$rowc] = $row;
                ++$rowc;
            }
        }
        return $this->JSONize($new_array);
    }

    /* Get login history */
}

/* class RadiusControl */
?>

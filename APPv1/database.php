<?php

    class DB
    {
        private $mysqli; //Database variable
        private $select_result; //result
        private const READ_ALL          = 'read all';
        private const READ              = 'read';
        private const CREATE            = 'create';
        private const DELETE            = 'delete';
        private const PRIVATE           = 'private';
        private const PUBLIC            = 'public';
        private const MAX_BAD_LOGIN_NUM = 3;
        private const TEMP_LOCK_SECONDS = 60;

        public function __construct($serwer, $user, $pass, $baza)
        {
            $this -> mysqli = new mysqli($serwer, $user, $pass, $baza);
            if ($this -> mysqli -> connect_errno)
            {
                printf("Connection to server failed: %s \n", $mysqli -> connect_error);
                exit();
            }
            if ($this -> mysqli -> set_charset("utf8"))
            {
                //charset changed
            }
        }

        function __destruct()
        {
            $this -> mysqli -> close();
        }

        public function getUserByLogin($login)
        {
            $stmt = $this -> mysqli -> prepare("select id, salt, hash from user WHERE login=?");

            $stmt -> bind_param("s", $login);
            $stmt -> execute();

            $res = $stmt -> get_result();

            return $res->fetch_assoc();
        }

        function isBannedWord($word)
        {
            $bannedWords = array(
                'select', 'union', 'database', 'update', 'drop', 'insert', '=', ' or ', ' and ', 'join', '#'
            );

            foreach ($bannedWords as $bannedWord)
            {
                $pattern = '/(' . strtolower($bannedWord) . ')/';

                $match = preg_match($pattern, strtolower($word));

                if ($match)
                {
                    return true;
                }
            }

            return false;
        }

        public function select($sql)
        {
            //parameter $sql – select string
            //parameter $pola - array containing table fields
            //Wynik funkcji – association table with querry results
            $results = array();
            $indeks  = 0;
            if ($result = $this -> mysqli -> query($sql))
            {
                while ($row = $result -> fetch_object())
                {
                    $results[] = $row;
                }
                $result -> close();
            }
            $this -> select_result = $results;
            return $results;
        }

        public function isIPAddressBlocked()
        {
            $lockTime = $this -> getIPAddressLockTime();
            $blocked  = false;

            if ($lockTime > 0)
            {
                $blocked  = $lockTime < $this::TEMP_LOCK_SECONDS;
                $leftTime = $this::TEMP_LOCK_SECONDS - $lockTime;

                if ($leftTime > 0)
                {
                    echo "The user is blocked. Try again after $leftTime seconds.";
                }
            }

            return $blocked;
        }

        public function getIPAddressLockTime()
        {
            $addressIP       = $this -> getIPAddress();
            $addressIPRecord = $this -> getIPAddressRecord($addressIP);
            $interval        = 0;

            if (count($addressIPRecord) != 0)
            {
                $tempLock = $addressIPRecord[0] -> temp_lock;

                if (strtotime($tempLock))
                {
                    $date = new DateTime();

                    $interval = $date -> getTimestamp() - strtotime($tempLock);
                }
            }

            return $interval;
        }

        public function registerLogin($id_user, $successful)
        {
            $addressIP       = $this -> getIPAddress();

            $this -> registerLoginIPAddress($addressIP, $successful);

            $addressIPRecord = $this -> getIPAddressRecord($addressIP);

            if (count($addressIPRecord) != 0)
            {
                $this -> registerUserLogin($addressIPRecord[0] -> id, $id_user, $successful);
            }
        }

        public function registerLoginIPAddress($addressIP, $successful)
        {
            $ok       = 0;
            $bad      = 0;
            $tempLock = null;

            if ($successful)
            {
                $ok       = 1;
                $badCount = 0;
            }
            else
            {
                $bad      = 1;
                $badCount = $this -> getIPAddressBadCount($addressIP);
                $lockTime = $this -> getIPAddressLockTime();

                if ($lockTime == 0 || $lockTime > $this::TEMP_LOCK_SECONDS)
                {
                    $badCount++;

                    if ($badCount >= $this::MAX_BAD_LOGIN_NUM)
                    {
                        $tempLock = date('Y-m-d H:i:s');
                    }
                }
                else
                {
                    $addressIPRecord = $this -> getIPAddressRecord($addressIP);

                    if (count($addressIPRecord) != 0)
                    {
                        $tempLock = $addressIPRecord[0] -> temp_lock;
                    }
                }
            }

            $this -> insertOrUpdateIPAddress(
                $addressIP, $ok, $bad, $badCount, 0, $tempLock);
        }

        public function getIPAddressBadCount($addressIP)
        {
            $sql = "SELECT last_bad_login_num FROM ip_address WHERE adres_IP = '$addressIP'";

            $badCount = 0;

            $lastBadCountData = $this -> select($sql);

            if (count($lastBadCountData) == 1)
            {
                $badCount = $lastBadCountData[0] -> last_bad_login_num;
            }

            return $badCount;
        }

        public function getIPAddress()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP']))
            {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else
            {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $ip;
        }

        public function getIPAddressRecord($addressIP)
        {
            $sql = "SELECT * FROM ip_address WHERE adres_IP = '$addressIP'";

            return $this -> select($sql);
        }

        public function insertOrUpdateIPAddress($addressIP, $ok, $bad, $lastBadCount, $permLock, $tempLock)
        {
            $tempLock = $tempLock ? "'$tempLock'" : 'NULL';

            $sql = "INSERT INTO ip_address (ok_login_num, bad_login_num, last_bad_login_num, permanent_lock, temp_lock, adres_IP) 
                    VALUES($ok, $bad, $lastBadCount, $permLock, $tempLock, '$addressIP') 
                    ON DUPLICATE KEY UPDATE 
                    ok_login_num       = ok_login_num  + $ok, 
                    bad_login_num      = bad_login_num + $bad, 
                    last_bad_login_num = $lastBadCount,
                    permanent_lock     = $permLock, 
                    temp_lock          = $tempLock,
                    adres_IP           = '$addressIP'";

            $registered = false;

            if ($result = $this -> mysqli -> query($sql))
            {
                if ($result)
                {
                    $registered = true;
                }
            }

            return $registered;
        }

        public function registerUserLogin($addressIP_ID, $id_user, $successful)
        {
            $correct   = $successful ? 1 : 0;
            $timestamp = date('Y-m-d H:i:s');
            $computer  = gethostname();
            $session   = session_id();

            $sql = "
                INSERT INTO user_login (time, correct, id_user, computer, session, id_address)
                VALUES ('$timestamp', $correct, $id_user, '$computer', '$session', $addressIP_ID)";

            $this -> mysqli -> query($sql);

            if (!$successful)
            {
                $sql = "
                INSERT INTO incorrect_logins (time, session_id, id_address, computer)
                VALUES ('$timestamp', '$session', $addressIP_ID, '$computer')";

                $this -> mysqli -> query($sql);
            }
        }

        public function getMessage($message_id)
        {
            foreach ($this -> select_result as $message):
                if ($message -> id == $message_id)
                {
                    if ($message -> type != self::PRIVATE
                        || $this -> canPerformAction(self::READ_ALL))
                    {
                        $this -> log(
                            $_SESSION['id_user'],
                            self::READ,
                            'message',
                            1,
                            "$message->name | $message->type | $message->message",
                            '');

                        return $message -> message;
                    }
                    else
                    {
                        return "Cannot show the message. This message is private.";
                    }
                }
            endforeach;
        }

        public function getMessageById($message_id)
        {
            $sql = "SELECT * FROM message WHERE id = $message_id";

            return $this -> select($sql);
        }

        public function insertPrivateMessage($name, $message)
        {
            if (!$this -> canPerformAction(self::READ_ALL, true))
            {
                return false;
            }

            return $this -> insertMessage($name, self::PRIVATE, $message);
        }

        public function insertPublicMessage($name, $message)
        {
            return $this -> insertMessage($name, self::PUBLIC, $message);
        }

        public function insertMessage($name, $type, $message)
        {
            if (!$this -> canPerformAction(self::CREATE, true))
            {
                return false;
            }

            $nameFiltered    = filter_var($name, FILTER_SANITIZE_STRING);
            $messageFiltered = filter_var($message, FILTER_SANITIZE_STRING);

            if ($this->isBannedWord($name))
            {
                $this -> logError("Insert attack: ".$nameFiltered."", "Invalid data");

                echo "Invalid data";

                return false;
            }

            if ($this->isBannedWord($message))
            {
                $this -> logError("Insert attack: ".$messageFiltered."", "Invalid data");

                echo "Invalid data";

                return false;
            }

            $db = new PDO("mysql:host=" . 'localhost' . ";dbname=" . 'public', 'root', '');

            $stmt = $db -> prepare("
                INSERT INTO message (name, type, message)
                    VALUES (?, ?, ?)");

            $stmt -> bindValue(1, $nameFiltered,  PDO::PARAM_STR);
            $stmt -> bindValue(2, $type,  PDO::PARAM_STR);
            $stmt -> bindValue(3, $messageFiltered,  PDO::PARAM_STR);

            $result = $stmt -> execute();

            if ($result)
            {
                $inserted = true;

                $this -> log(
                    $_SESSION['id_user'],
                    self::CREATE,
                    'message',
                    1,
                    '',
                    "$nameFiltered | $type | $messageFiltered");
            }
            else
            {
                $inserted = false;
            }

            return $inserted;
        }

        public function deleteMessage($id)
        {
            if (!$this -> canPerformAction(self::DELETE, true))
            {
                return false;
            }

            $prevData = $this -> getMessageById($id);

            if (count($prevData) == 0)
            {
                return false;
            }

            $prevMessage = $prevData[0];

            $sql = "DELETE FROM message WHERE id = $id";

            $deleted = false;

            if ($result = $this -> mysqli -> query($sql))
            {
                if ($result)
                {
                    $deleted = true;

                    $this -> log(
                        $_SESSION['id_user'],
                        self::DELETE,
                        'message',
                        1,
                        "$prevMessage->name | $prevMessage->type | $prevMessage->message",
                        '');
                }
            }

            return $deleted;
        }

        public function canPerformAction($action, $showErrorMsg = false)
        {
            $ret = false;

            if (isset($_SESSION['login']))
            {
                $ret = $this -> checkUserHasPrivilege($_SESSION['login'], $action);
            }

            if (!$ret && $showErrorMsg)
            {
                echo "You need permission to perform this action";
            }

            return $ret;
        }

        public function checkUserHasPrivilege($login, $privilege)
        {
            $sql = "
                SELECT T1.name, T3.login FROM privilege T1
                    LEFT JOIN user_privilege T2 
                        ON T2.id_privilege = T1.id 
                    INNER JOIN user T3 
                        ON T3.id = T2.id_user
                    WHERE T1.name = '$privilege' AND T3.login = '$login'
                UNION        
                SELECT T1.name, T5.login FROM privilege T1 
                    INNER JOIN role_privilege T2 
                        ON T2.id_privilege = T1.id 
                    INNER JOIN role T3 
                        ON T3.id = T2.id_role
                    INNER JOIN user_role T4 
                        ON T4.id_role = T3.id
                    INNER JOIN user T5 
                        ON T5.id = T4.id_user     
                    WHERE T1.name = '$privilege' AND T5.login = '$login'";

            $result = $this -> select($sql);

            if (count($result) == 1)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public function log($id_user, $action, $table, $rows, $prev_data, $new_data)
        {
            $sql = "
                INSERT INTO user_activity (id_user, action_taken, table_affected, row_number, previous_data, new_data)
                    VALUES ($id_user, '$action', '$table', $rows, '$prev_data', '$new_data')";

            $logged = false;
            $result = $this -> mysqli -> query($sql);

            if ($result)
            {
                $logged = true;
            }

            return $logged;
        }

        public function logError($reason, $message)
        {
            $timestamp = date('Y-m-d H:i:s');

            $db = new PDO("mysql:host=" . 'localhost' . ";dbname=" . 'public', 'root', '');

            $stmt = $db -> prepare("
                INSERT INTO error (`reason`, `message`, `time`)
                    VALUES (?, ?, '$timestamp')");

            $stmt -> bindValue(1, $reason,  PDO::PARAM_STR);
            $stmt -> bindValue(2, $message,  PDO::PARAM_STR);

            $result = $stmt -> execute();

            $logged = false;

            if ($result)
            {
                $logged = true;
            }

            return $logged;
        }
    }

//koniec klasy Baza
?>
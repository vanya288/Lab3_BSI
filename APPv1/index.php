<html>
<head>
    <title>Access control:</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>
<?php
    session_start();
    include_once "database.php";

    //log in
    if (isset($_REQUEST['sign_in']))
    {
        $pepper = "d$#dr";
        $login  = $_REQUEST['login'];
        $pass   = $_REQUEST['password'];
        $hash   = md5($pass);
        $db     = new DB("localhost", "root", "", "public");
        $sql    = "select id, salt, hash from user WHERE login='" . $login . "'";
        $result = $db -> select($sql);

        if (count($result) == 1)
        {
            if (!$db -> isIPAddressBlocked() &&
                $result[0] -> hash == md5($pepper . $result[0] -> salt . $pass))
            {
                echo "Signed successfully as: " . $login . "<br>";
                $_SESSION['login']   = $login;
                $_SESSION['id_user'] = $result[0] -> id;

                $db -> registerLogin($result[0] -> id, true);
            }
            else
            {
                $db -> registerLogin($result[0] -> id, false);

                echo "<BR> User verification failed";
            }
        }
        else
        {
            echo "<BR> User verification failed";
        }
    }
    //log out
    if (isset($_REQUEST['sign_out']))
    {
        session_destroy();
        echo 'Signed out successfully <BR/>';
    }
    else

//Display if a user is signed in
        if (isset($_SESSION) && isset($_SESSION['login']))
        {
            if ($_SESSION['login'] != null && $_SESSION['login'] != "")
            {
                echo '<BR/> signed as: ' . $_SESSION['login'] . '<BR/>';
            }
            else
            {
                echo '<BR/> unsigned <BR/>';
            }
        }
        else
        {
            echo '<BR/> unsigned <BR/>';
        }
?>


<form method="post" action="index.php">
    <h4>Sign in: (example login: "john", password: "password1")</h4>
    <table>
        <tr>
            <td><input required type="text" name="login" id="login" size="30" value="john"/></td>
        </tr>
        <tr>
            <td><input required type="text" name="password" id="password" size="30" value="password1"/></td>
        </tr>
    </table>
    <input type="submit" id="submit" value="Sign in" name="sign_in">
</form>

<form method="post" action="log_out.php">

    <input type="submit" id="submit" value="Log out" name="log_out">
</form>


<form action="index.php" method="post">
    <select size='6' name="id">
        <?php
            //messages display
            $db       = new DB("localhost", "root", "", "public");
            $sql      = "SELECT * from message";
            $messages = $db -> select($sql);

            foreach ($messages as $msg)://returned as an object so calls to the fields must be done the same as to object fields
                if (!isset($_SESSION['login']) && $msg -> type == "private")
                {
                    $disabledstr = 'disabled="disabled"';
                }
                else
                {
                    $disabledstr = '';
                }
                ?>
                <option value="<?php echo $msg -> id ?>"<?php echo $disabledstr; ?>>
                    <?php echo $msg -> name ?></option>
            <?php
            endforeach;
        ?>
    </select>
    <BR/>
    <input type="hidden" name="action" value="showmsg">
    <input type="submit" name="delete" value="Delete">
    <input type="submit" name="send" value="Check the message">
</form>

<h4>New message</h4>
<form action="index.php" method="post">
    <label style="display: inline-block; width: 60px">Name:</label>
    <input type="text" name="msgName" required><br>
    <label style="display: inline-block; width: 60px">Message:</label>
    <input type="text" name="msgTxt" required><br>
    <label style="display: inline-block; width: 60px">Type:</label>
    <select name="msgType" id="msgTypes">
        <option value="public" selected>Public message</option>
        <option value="private" <?php echo isset($_SESSION['login']) ? '' : 'disabled' ?>>Private message</option>
    </select>
    <br/>
    <br/>
    <input type="submit" name="create" value="Add message">
</form>

<br/>

<?php
    //download and display message content
    if (isset($_REQUEST['send']))
    {
        if (isset($_REQUEST['id']))
        {
            echo '<div>';
            echo $db -> getMessage($_REQUEST['id']);
            echo '</div>';
        }
        else
        {
            echo "The message has not been selected";
        }
    }
    else if (isset($_REQUEST['create']))
    {
        $msgType = isset($_POST["msgType"]) ? $_POST["msgType"] : '';

        switch ($msgType)
        {
            case 'public':
                if ($db -> insertPublicMessage($_POST["msgName"], $_POST["msgTxt"]))
                {
                    header("Refresh: 0");
                }
                break;

            case 'private':
                if ($db -> insertPrivateMessage($_POST["msgName"], $_POST["msgTxt"]))
                {
                    header("Refresh: 0");
                }
                break;

            default:
                echo "Wrong type of the message";
                break;
        }
    }
    else if (isset($_REQUEST['delete']))
    {
        if (isset($_REQUEST['id']))
        {
            if ($db -> deleteMessage($_REQUEST['id']))
            {
                header("Refresh:0");
            }
        }
        else
        {
            echo "The message has not been selected";
        }
    }
?>
</body>
</html>
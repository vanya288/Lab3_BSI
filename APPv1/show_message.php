<?php
    include_once "Baza.php";
?>

<html>
<head>
    <title>Przykładowy formularz HTML:</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" type="text/css"/>
</head>
<body>
<form action="KontrolaDostepu.php" method="get">
    <select size='6' name="id">
        <?php
            $baza       = new Baza("localhost", "root", "", "bezpieczenstwo");
            $sql        = "SELECT * from messages";
            $wiadomosci = $baza -> select($sql);
            /*
            $wiad=$wiadomosci[0]; //zwracane jako obiekt, więc do pól odwołujemy się jak do zmiennych obiektu
            var_dump($wiad);
            echo $wiad->content;

            echo 'yy<BR>';
            */
            var_dump($wiadomosci);
            foreach ($wiadomosci as $msg):
                echo 'yy<BR>';
                if (!isset($_SESSION['zalogowany']) && $msg -> type == "private")
                {
                    $disabledstr = 'disabled="disabled"';
                }
                else
                {
                    $disabledstr = '';
                }
                ?>
                <option value="<?php echo $msg -> id_message ?>"
                    <?php echo $disabledstr; ?>><?php echo $msg -> content ?>
                </option>
            <?php
            endforeach;
        ?>
    </select>
    <input type="hidden" name="action" value="showmsg">
    <input type="submit" value="Sprawdź wiadomość">
</form>
<?php
?>
</body>
</html>
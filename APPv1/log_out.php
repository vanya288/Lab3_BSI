<html>
<head>
    <title>HTML form:</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" type="text/css"/>
</head>
<body>
<?php
    session_start();
    if (isset($_REQUEST['log_out']))
    {
        session_destroy();
        echo 'Signed out successfully <BR/>';
    }
?>
<a href="index.php">Return</a>

</body>
</html>

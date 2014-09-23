<?php
set_time_limit(0);
require("QueryServer.php");

$fake = new QueryServer(array(
        'hostname' => 'A Minecraft Server',
        'map' => 'world',
        'numplayers' => '3',
        'maxplayers' => '20000000',
        'hostport' => '25565',
        'hostip' => '127.0.0.1',
    ), array(
        "Notch",
        "Jeb",
        "Dinnerbone"
    ));

$fake->start();
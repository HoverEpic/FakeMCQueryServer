Fake Minecraft Query Server (in PHP)
------------------------------------
By tschrock

You will need php (cli)
Then run ./start.sh

Use 'Ctrl-C' or 'pkill -9 php' to stop it.

Right now everything is static, if you want to change anything you will have to edit start.php yourself.

Other problems:
 - Sometimes generates a random token that it can't convert to the right format.
 - Will (eventualy, but not for a couple of years) run out of memory since the tokens don't get erased.
 

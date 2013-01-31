getplexusers
============

Reads a plex media server log file and tells you the active users and connections

by Kireol

A script I wrote to get the users from the plex media server log

Like what I did?  Buy me a beer!  
https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2WSJ87ZVXQUPL

Installation:
Run at your own risk.  Not my fault if your computer dies/burns/becomes possessed by the devil/etc.
Place PHP file on the server plex is on (or figure out a way to get access to the log file) and make sure the file has correct permissions.
Edit the script in your favorite editor. Make sure the lines in between the edit comments are correct
Edit the users.ini file if you want a more human readable name for your ips.  If you know the IPs, enter them here and they will be displayed in the User field
Run the script
command line: php getplexusers.php
browser: http://192.168.0.70/getplexusers.php?alt=array      replace 192.168.0.70 with your plex servers ip or name

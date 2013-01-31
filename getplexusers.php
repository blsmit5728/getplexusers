<?php
        //========EDIT BELOW============//
	$logfile = "/var/lib/plexmediaserver/Library/Application Support/Plex Media Server/Logs/Plex Media Server.log";
	$lookuphostname = true;
	//========STOP EDDITING=========//

	$users = parse_ini_file("users.ini");
	$lines = explode("\n",rtrim(shell_exec("grep -nr \"GET /video/:/transcode/segmented/start.m3u8\" \"".$logfile."\" | tail -10000 | sed -n 's/^\\([0-9]*\)[:].*/\\1/p'")));
	$iplines = array();
	//get ips from log file
	foreach($lines as $thisline)
	{
		$linetext = shell_exec("sed -n ".$thisline."p \"".$logfile . "\"");
        	preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}):\d{1,5}/m', $linetext, $matches);
		$iplines[end($matches)] = $thisline;
	}

	//We have the ips.  Get the last movie and date from each ip
	$out = array();
	foreach($iplines as $ip => $thisline)
	{
		$linetext = shell_exec("sed -n '".$thisline.",".($thisline + 20)."p;".($thisline + 20)."q' \"".$logfile."\" | grep -m 1 \"key => http://\"");
		$laststarttime = timestampFromDate(getDateFromString($linetext));
		$startdatetime = date('l jS \of F Y h:i:s A',$laststarttime);
		$showname = getNameFromText(file_get_contents(getShowURL($linetext)));
		$ipname = lookupName($ip,$users);
		if($lookuphostname)
		{
			$hostname = gethostbyaddr($ip); 
		}
		$lasttime = getLastPacketTime($ip,$logfile);
		$elapsedseconds = time() - $lasttime;
		$elapsedtime = time_since($elapsedseconds);

		$out[] = array("User"=>$ipname,"Show"=>$showname, "LastActivity"=>$elapsedtime, "StartTime"=>$startdatetime,"IP"=>$ip,"HostName"=>$hostname,"ElapsedSeconds"=>$elapsedseconds);
	}
	//sort and echo out the results
	usort($out, "sortByTimeStamp");
	echo formatOutput($out);
	exit;
	function getLastPacketTime($ip,$logfile)
	{
		return timestampFromDate(getDateFromString(shell_exec("grep -r 'Request: GET /video/:/transcode.*".$ip."' \"".$logfile."\"| tail -1")));
	}
	function timestampFromDate($indate)
	{
		return strtotime($indate);

	}
	function lookupName($ip,$users)
	{
		return (array_key_exists($ip,$users) ? $users[$ip] : $ip);
	}
	function getDateFromString($string)
	{
        	preg_match("/^[a-zA-Z]{3} \d{1,2}, \d{4} \d{1,2}:\d{1,2}:\d{1,2}/m", $string, $datematches);
		return(end($datematches));
	}
	function getShowURL($string)
	{
        	preg_match("/http(.*?)$/", $string, $urlmatches);
		return($urlmatches[0]);
	}
	function getNameFromText($string)
	{
        	preg_match("/\" title=\"(.*?)\"/", $string, $urlmatches);
		$endtitle = end($urlmatches);
        	preg_match("/\" grandparentTitle=\"(.*?)\"/", $string, $urlmatches);
		$gptitle = end($urlmatches);
		return (strlen($gptitle) > 0) ?  $gptitle . " - " . $endtitle : $endtitle;
	}
	function sortByTimeStamp($a, $b)
	{
		$sortcolumn = (isset($_REQUEST['sorttype'])&&strlen($_REQUEST['sorttype'])>0)?$_REQUEST['sorttype']:"ElapsedSeconds";
    		if ($a[$sortcolumn] == $b[$sortcolumn]) 
		{
        		return 0;
    		}
    		return ($a[$sortcolumn] < $b[$sortcolumn]) ? -1 : 1;
	}
	function time_since($since) 
	{
    		$chunks = array( array(60 * 60 * 24 * 365 , 'year'), array(60 * 60 * 24 * 30 , 'month'), array(60 * 60 * 24 * 7, 'week'), array(60 * 60 * 24 , 'day'), array(60 * 60 , 'hour'), array(60 , 'minute'), array(1 , 'second'));
    		for ($i = 0, $j = count($chunks); $i < $j; $i++) 
		{
        		$seconds = $chunks[$i][0];
		        $name = $chunks[$i][1];
		        if (($count = floor($since / $seconds)) != 0) 
			{
            			break;
        		}
    		}
    		$print = ($count == 1) ? '1 '.$name : "$count {$name}s";
    		return $print;
	}

	function formatOutput($inarray)
	{
		if(strlen($_REQUEST['alt']) > 0)
        	{
                	if($_REQUEST['alt'] == "array")
                	{
                        	$outstring =  "<pre>" . print_r($inarray, TRUE) . "</pre>";
                	}
                	else if($_REQUEST['alt'] == 'xml')
                	{
                        	$xmlinfo = new SimpleXMLElement('<?xml version="1.0"?><server_info></server_info>');
                        	array_to_xml($inarray,$xmlinfo);
                        	$outstring = $xmlinfo->asXML();
                	}
			else if($_REQUEST['alt'] == 'phpserial')
			{
				$outstring = serialize($inarray);
			}
			else
			{
                		$outstring = json_encode($inarray);
			}
        	}
        	else
        	{
                	$outstring = json_encode($inarray);
        	}
		return $outstring;
	}
	function array_to_xml($student_info, &$xml_student_info) 
	{
    		foreach($student_info as $key => $value) 
		{
        		if(is_array($value)) 
			{
            			if(!is_numeric($key))
				{
                			$subnode = $xml_student_info->addChild("$key");
                			array_to_xml($value, $subnode);
            			}
            			else
				{
                			array_to_xml($value, $xml_student_info);
            			}
        		}
        		else 
			{
            			$xml_student_info->addChild("$key","$value");
        		}
    		}
	}

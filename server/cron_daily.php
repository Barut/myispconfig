<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

define('SCRIPT_PATH', dirname($_SERVER["SCRIPT_FILENAME"]));
require(SCRIPT_PATH."/lib/config.inc.php");
require(SCRIPT_PATH."/lib/app.inc.php");

set_time_limit(0);

// make sure server_id is always an int
$conf['server_id'] = intval($conf['server_id']);


// Load required base-classes
$app->uses('ini_parser,file,services,getconf');


#######################################################################################################
// store the mailbox statistics in the database
#######################################################################################################

$sql = "SELECT mailuser_id,maildir FROM mail_user WHERE server_id = ".$conf['server_id'];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	if(@is_file($rec['maildir'].'/ispconfig_mailsize')) {

		// rename file
		rename($rec['maildir'].'/ispconfig_mailsize',$rec['maildir'].'/ispconfig_mailsize_save');

		// Read the file
		$lines = file($rec['maildir'].'/ispconfig_mailsize_save');
		$mail_traffic = 0;
		foreach($lines as $line) {
			$mail_traffic += intval($line);
		}
		unset($lines);

		// Delete backup file
		if(@is_file($rec['maildir'].'/ispconfig_mailsize_save')) unlink($rec['maildir'].'/ispconfig_mailsize_save');

		// Save the traffic stats in the sql database
		$tstamp = date('Y-m');

		$sql = "SELECT * FROM mail_traffic WHERE month = '$tstamp' AND mailuser_id = ".$rec['mailuser_id'];
		$tr = $app->dbmaster->queryOneRecord($sql);

		$mail_traffic += $tr['traffic'];
		if($tr['traffic_id'] > 0) {
			$sql = "UPDATE mail_traffic SET traffic = $mail_traffic WHERE traffic_id = ".$tr['traffic_id'];
		} else {
			$sql = "INSERT INTO mail_traffic (month,mailuser_id,traffic) VALUES ('$tstamp',".$rec['mailuser_id'].",$mail_traffic)";
		}
		$app->dbmaster->query($sql);
		echo $sql;

	}

}

#######################################################################################################
// Create webalizer statistics
#######################################################################################################

function setConfigVar( $filename, $varName, $varValue ) {
	if($lines = @file($filename)) {
		$out = '';
		$found = 0;
		foreach($lines as $line) {
			@list($key, $value) = preg_split('/[\t= ]+/', $line, 2);
			if($key == $varName) {
				$out .= $varName.' '.$varValue."\n";
				$found = 1;
			} else {
				$out .= $line;
			}
		}
		if($found == 0) {
			//* add \n if the last line does not end with \n or \r
			if(substr($out,-1) != "\n" && substr($out,-1) != "\r") $out .= "\n";
			//* add the new line at the end of the file
			if($append == 1) $out .= $varName.' '.$varValue."\n";
		}

		file_put_contents($filename,$out);
	}
}


$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE stats_type = 'webalizer' AND server_id = ".$conf['server_id'];
$records = $app->db->queryAllRecords($sql);

foreach($records as $rec) {
	//$yesterday = date('Ymd',time() - 86400);
	$yesterday = date('Ymd',strtotime("-1 day", time()));
	$logfile = escapeshellcmd($rec['document_root'].'/log/'.$yesterday.'-access.log');
	if(!@is_file($logfile)) {
		$logfile = escapeshellcmd($rec['document_root'].'/log/'.$yesterday.'-access.log.gz');
		if(!@is_file($logfile)) {
			continue;
		}
	}

	$domain = escapeshellcmd($rec['domain']);
	$statsdir = escapeshellcmd($rec['document_root'].'/web/stats');
	$webalizer = '/usr/bin/webalizer';
	$webalizer_conf_main = '/etc/webalizer/webalizer.conf';
	$webalizer_conf = escapeshellcmd($rec['document_root'].'/log/webalizer.conf');
	
	if(is_file($statsdir.'/index.php')) unlink($statsdir.'/index.php');

	if(!@is_file($webalizer_conf)) {
		copy($webalizer_conf_main,$webalizer_conf);
	}

	if(@is_file($webalizer_conf)) {
		setConfigVar($webalizer_conf, 'Incremental', 'yes');
		setConfigVar($webalizer_conf, 'IncrementalName', $statsdir.'/webalizer.current');
		setConfigVar($webalizer_conf, 'HistoryName', $statsdir.'/webalizer.hist');
	}


	if(!@is_dir($statsdir)) mkdir($statsdir);
	exec("$webalizer -c $webalizer_conf -n $domain -s $domain -r $domain -q -T -p -o $statsdir $logfile");
}

#######################################################################################################
// Create awstats statistics
#######################################################################################################

$sql = "SELECT domain_id, domain, document_root, system_user, system_group FROM web_domain WHERE stats_type = 'awstats' AND server_id = ".$conf['server_id'];
$records = $app->db->queryAllRecords($sql);

$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

foreach($records as $rec) {
	//$yesterday = date('Ymd',time() - 86400);
	$yesterday = date('Ymd',strtotime("-1 day", time()));
	$logfile = escapeshellcmd($rec['document_root'].'/log/'.$yesterday.'-access.log');
	if(!@is_file($logfile)) {
		$logfile = escapeshellcmd($rec['document_root'].'/log/'.$yesterday.'-access.log.gz');
		if(!@is_file($logfile)) {
			continue;
		}
	}
	
	$domain = escapeshellcmd($rec['domain']);
	$statsdir = escapeshellcmd($rec['document_root'].'/web/stats');
	$awstats_pl = $web_config['awstats_pl'];
	$awstats_buildstaticpages_pl = $web_config['awstats_buildstaticpages_pl'];
	
	$awstats_conf_dir = $web_config['awstats_conf_dir'];
	$awstats_website_conf_file = $web_config['awstats_conf_dir'].'/awstats.'.$domain.'.conf';
	
	if(is_file($awstats_website_conf_file)) unlink($awstats_website_conf_file);
	
	$sql = "SELECT domain FROM web_domain WHERE (type = 'alias' OR type = 'subdomain') AND parent_domain_id = ".$rec['domain_id'];
	$aliases = $app->db->queryAllRecords($sql);
	$aliasdomain = '';
	
	if(is_array($aliases)) {
		foreach ($aliases as $alias) {
			$aliasdomain.= ' '.$alias['domain']. ' www.'.$alias['domain'];
		}
	}
	
	if(!is_file($awstats_website_conf_file)) {
		$awstats_conf_file_content = 'Include "'.$awstats_conf_dir.'/awstats.conf"
LogFile="/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log"
SiteDomain="'.$domain.'"
HostAliases="www.'.$domain.' localhost 127.0.0.1'.$aliasdomain.'"';
		file_put_contents($awstats_website_conf_file,$awstats_conf_file_content);
	}
	
	if(!@is_dir($statsdir)) mkdir($statsdir);
	if(is_link('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log')) unlink('/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');
	symlink($logfile,'/var/log/ispconfig/httpd/'.$domain.'/yesterday-access.log');
	
	$awmonth = date("n");
	$awyear = date("Y");

	if (date("d") == 1) {
		$awmonth = date("m")-1;
		if (date("m") == 1) {
			$awyear = date("Y")-1;
			$awmonth = "12";
		}
	}
	
	// awstats_buildstaticpages.pl -update -config=mydomain.com -lang=en -dir=/var/www/domain.com/web/stats -awstatsprog=/path/to/awstats.pl
	// $command = "$awstats_buildstaticpages_pl -update -config='$domain' -lang=".$conf['language']." -dir='$statsdir' -awstatsprog='$awstats_pl'";
	
	$command = "$awstats_buildstaticpages_pl -month='$awmonth' -year='$awyear' -update -config='$domain' -lang=".$conf['language']." -dir='$statsdir' -awstatsprog='$awstats_pl'";

	if (date("d") == 2) {
		$awmonth = date("m")-1;
		if (date("m") == 1) {
			$awyear = date("Y")-1;
			$awmonth = "12";
		}

		$statsdirold = $statsdir."/".$awyear."-".$awmonth."/";
		mkdir($statsdirold);
		$files = scandir($statsdir);
		foreach ($files as $file) {
			if (substr($file,0,1) != "." && !is_dir("$statsdir"."/"."$file") && substr($file,0,1) != "w" && substr($file,0,1) != "i") copy("$statsdir"."/"."$file","$statsdirold"."$file");
		}
	}
	
	
	if($awstats_pl != '' && $awstats_buildstaticpages_pl != '' && fileowner($awstats_pl) == 0 && fileowner($awstats_buildstaticpages_pl) == 0) {
		exec($command);
		if(is_file($rec['document_root'].'/web/stats/index.html')) unlink($rec['document_root'].'/web/stats/index.html');
		rename($rec['document_root'].'/web/stats/awstats.'.$domain.'.html',$rec['document_root'].'/web/stats/awsindex.html');
		if(!is_file($rec['document_root']."/web/stats/index.php")) copy("/usr/local/ispconfig/server/conf/awstats_index.php.master",$rec['document_root']."/web/stats/index.php");
		
		$app->log('Created awstats statistics with command: '.$command,LOGLEVEL_DEBUG);
	} else {
		$app->log("No awstats statistics created. Either $awstats_pl or $awstats_buildstaticpages_pl is not owned by root user.",LOGLEVEL_WARN);
	}
	
	if(is_file($rec['document_root']."/web/stats/index.php")) {
		chown($rec['document_root']."/web/stats/index.php",$rec['system_user']);
		chgrp($rec['document_root']."/web/stats/index.php",$rec['system_group']);
	}
	
}


#######################################################################################################
// Make the web logfiles directories world readable to enable ftp access
#######################################################################################################

exec('chmod +r /var/log/ispconfig/httpd/*');

#######################################################################################################
// Manage and compress web logfiles and create traffic statistics
#######################################################################################################

$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ".$conf['server_id'];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	
	//* create traffic statistics based on yesterdays access log file
	$yesterday = date('Ymd',time() - 86400);
	$logfile = $rec['document_root'].'/log/'.$yesterday.'-access.log';
	$total_bytes = 0;
	
	$handle = @fopen($logfile, "r");
	if ($handle) {
		while (($line = fgets($handle, 4096)) !== false) {
			if (preg_match('/^\S+ \S+ \S+ \[.*?\] "\S+.*?" \d+ (\d+) ".*?" ".*?"/', $line, $m)) {
				$total_bytes += intval($m[1]);
			}
		}
		
		//* Insert / update traffic in master database
		$traffic_date = date('Y-m-d',time() - 86400);
		$tmp = $app->dbmaster->queryOneRecord("select hostname from web_traffic where hostname='".$rec['domain']."' and traffic_date='".$traffic_date."'");
		if(is_array($tmp) && count($tmp) > 0) {
			$sql = "update web_traffic set traffic_bytes=traffic_bytes+"
                  . $total_bytes
                  . " where hostname='" . $rec['domain']
                  . "' and traffic_date='" . $traffic_date . "'";
		} else {
			$sql = "insert into web_traffic (hostname, traffic_date, traffic_bytes) values ('".$rec['domain']."', '".$traffic_date."', '".$total_bytes."')";
		}
		$app->dbmaster->query($sql);
		
		fclose($handle);
	}
	
	$yesterday2 = date('Ymd',time() - 86400*2);
	$logfile = escapeshellcmd($rec['document_root'].'/log/'.$yesterday2.'-access.log');
	
	//* Compress logfile
	if(@is_file($logfile)) {
		// Compress yesterdays logfile
		exec("gzip -c $logfile > $logfile.gz");
		unlink($logfile);
	}
	
	// rotate and compress the error.log when it exceeds a size of 10 MB
	$logfile = escapeshellcmd($rec['document_root'].'/log/error.log');
	if(is_file($logfile) && filesize($logfile) > 10000000) {
		exec("gzip -c $logfile > $logfile.1.gz");
		exec("cat /dev/null > $logfile");
	}

	// delete logfiles after 30 days
	$month_ago = date('Ymd',time() - 86400 * 30);
	$logfile = escapeshellcmd($rec['document_root'].'/log/'.$month_ago.'-access.log.gz');
	if(@is_file($logfile)) {
		unlink($logfile);
	}
	
	//* Delete older Log files, in case that we missed them before due to serverdowntimes.
	$datepart = date('Ym',time() - 86400 * 31 * 2);
	
	$logfile = escapeshellcmd($rec['document_root']).'/log/'.$datepart.'*-access.log.gz';
	exec('rm -f '.$logfile);
	
	$logfile = escapeshellcmd($rec['document_root']).'/log/'.$datepart.'*-access.log';
	exec('rm -f '.$logfile);
}

#######################################################################################################
// Rotate the ispconfig.log file
#######################################################################################################

// rotate the ispconfig.log when it exceeds a size of 10 MB
$logfile = $conf['ispconfig_log_dir'].'/ispconfig.log';
if(is_file($logfile) && filesize($logfile) > 10000000) {
	exec("gzip -c $logfile > $logfile.1.gz");
	exec("cat /dev/null > $logfile");
}

// rotate the cron.log when it exceeds a size of 10 MB
$logfile = $conf['ispconfig_log_dir'].'/cron.log';
if(is_file($logfile) && filesize($logfile) > 10000000) {
	exec("gzip -c $logfile > $logfile.1.gz");
	exec("cat /dev/null > $logfile");
}

#######################################################################################################
// Cleanup website tmp directories
#######################################################################################################

$sql = "SELECT domain_id, domain, document_root, system_user FROM web_domain WHERE server_id = ".$conf['server_id'];
$records = $app->db->queryAllRecords($sql);
$app->uses('system');
if(is_array($records)) {
	foreach($records as $rec){
		$tmp_path = realpath(escapeshellcmd($rec['document_root'].'/tmp'));
		if($tmp_path != '' && strlen($tmp_path) > 10 && is_dir($tmp_path) && $app->system->is_user($rec['system_user'])){
			exec('cd '.$tmp_path."; find . -mtime +1 -name 'sess_*' | grep -v -w .no_delete | xargs rm > /dev/null 2> /dev/null");
		}
	}
}

#######################################################################################################
// Cleanup logs in master database (only the "master-server")
#######################################################################################################

if ($app->dbmaster == $app->db) {
	/** 7 days */
	$tstamp = time() - (60*60*24*7);

	/*
	 *  Keep 7 days in sys_log
	 * (we can delete the old items, because if they are OK, they don't interrest anymore
	 * if they are NOT ok, the server will try to process them in 1 minute and so the
	 * error appears again after 1 minute. So it is no problem to delete the old one!
	 */
	$sql = "DELETE FROM sys_log WHERE tstamp < " . $tstamp . " AND server_id != 0";
	$app->dbmaster->query($sql);

	/*
	 * Delete all remote-actions "done" and older than 7 days
	 * ATTENTION: We have the same problem as described in cleaning the datalog. We must not
	 * delete the last entry
	 */
	$sql = "SELECT max(action_id) FROM sys_remoteaction";
	$res = $app->dbmaster->queryOneRecord($sql);
	$maxId = $res['max(action_id)'];
	$sql =  "DELETE FROM sys_remoteaction " .
			"WHERE tstamp < " . $tstamp . " " .
			" AND action_state = 'ok' " .
			" AND action_id <" . intval($maxId);
	$app->dbmaster->query($sql);

	/*
	 * The sys_datalog is more difficult.
	 * 1) We have to keet ALL entries with
	 *    server_id=0, because they depend on ALL servers (even if they are not
	 *    actually in the system (and will be insered in 3 days or so).
	 * 2) We have to keey ALL entries which are not actually precessed by the
	 *    server never mind how old they are!
	 * 3) We have to keep the entry with the highest autoinc-id, because mysql calculates the
	 *    autoinc-id as "new value = max(row) +1" and does not store this in a separate table.
	 *    This means, if we delete to entry with the highest autoinc-value then this value is
	 *    reused as autoinc and so there are more than one entries with the same value (over
	 *    for example 4 Weeks). This is confusing for our system.
	 *    ATTENTION 2) and 3) is in some case NOT the same! so we have to check both!
	 */

	/* First we need all servers and the last sys_datalog-id they processed */
	$sql = "SELECT server_id, updated FROM server ORDER BY server_id";
	$records = $app->dbmaster->queryAllRecords($sql);

	/* Then we need the highest value ever */
	$sql = "SELECT max(datalog_id) FROM sys_datalog";
	$res = $app->dbmaster->queryOneRecord($sql);
	$maxId = $res['max(datalog_id)'];

	/* Then delete server by server */
	foreach($records as $server) {
		$tmp_server_id = intval($server['server_id']);
		if($tmp_server_id > 0) {
			$sql = 	"DELETE FROM sys_datalog " .
					"WHERE tstamp < " . $tstamp .
					" AND server_id = " . intval($server['server_id']) .
					" AND datalog_id < " . intval($server['updated']) .
					" AND datalog_id < " . intval($maxId);
		}
//		echo $sql . "\n";
		$app->dbmaster->query($sql);
	}
}

#######################################################################################################
// enforce traffic quota (run only on the "master-server")
#######################################################################################################

if ($app->dbmaster == $app->db) {

	$current_month = date('Y-m');

	//* Check website traffic quota
	$sql = "SELECT sys_groupid,domain_id,domain,traffic_quota,traffic_quota_lock FROM web_domain WHERE (traffic_quota > 0 or traffic_quota_lock = 'y') and type = 'vhost'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {

			$web_traffic_quota = $rec['traffic_quota'];
			$domain = $rec['domain'];

			// get the client
			/*
			$client_group_id = $rec["sys_groupid"];
			$client = $app->db->queryOneRecord("SELECT limit_traffic_quota,parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			$reseller = $app->db->queryOneRecord("SELECT limit_traffic_quota FROM client WHERE client_id = ".intval($client['parent_client_id']));

			$client_traffic_quota = intval($client['limit_traffic_quota']);
			$reseller_traffic_quota = intval($reseller['limit_traffic_quota']);
			*/

			//* get the traffic
			$tmp = $app->db->queryOneRecord("SELECT SUM(traffic_bytes) As total_traffic_bytes FROM web_traffic WHERE traffic_date like '$current_month%' AND hostname = '$domain'");
			$web_traffic = (int)$tmp['total_traffic_bytes']/1024/1024;

			//* Website is over quota, we will disable it
			/*if( ($web_traffic_quota > 0 && $web_traffic > $web_traffic_quota) ||
				($client_traffic_quota > 0 && $web_traffic > $client_traffic_quota) ||
				($reseller_traffic_quota > 0 && $web_traffic > $reseller_traffic_quota)) {*/
			if($web_traffic_quota > 0 && $web_traffic > $web_traffic_quota) {
				$app->dbmaster->datalogUpdate('web_domain', "traffic_quota_lock = 'y',active = 'n'", 'domain_id', $rec['domain_id']);
				$app->log('Traffic quota for '.$rec['domain_id'].' exceeded. Disabling website.',LOGLEVEL_DEBUG);
			} else {
				//* unlock the website, if traffic is lower then quota
				if($rec['traffic_quota_lock'] == 'y') {
					$app->dbmaster->datalogUpdate('web_domain', "traffic_quota_lock = 'n',active = 'y'", 'domain_id', $rec['domain_id']);
					$app->log('Traffic quota for '.$rec['domain_id'].' ok again. Re-enabling website.',LOGLEVEL_DEBUG);
				}
			}
		}
	}


}


#######################################################################################################
// deactivate virtual servers (run only on the "master-server")
#######################################################################################################

if ($app->dbmaster == $app->db) {
	$current_date = date('Y-m-d');

	//* Check which virtual machines have to be deactivated
	$sql = "SELECT * FROM openvz_vm WHERE active = 'y' AND active_until_date != '0000-00-00' AND active_until_date < '$current_date'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->dbmaster->datalogUpdate('openvz_vm', "active = 'n'", 'vm_id', $rec['vm_id']);
			$app->log('Virtual machine active date expired. Disabling VM '.$rec['veid'],LOGLEVEL_DEBUG);
		}
	}


}

#######################################################################################################
// Create website backups
#######################################################################################################

$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
$backup_dir = $server_config['backup_dir'];

if($backup_dir != '') {
	
	if(isset($server_config['backup_dir_ftpread']) && $server_config['backup_dir_ftpread'] == 'y') {
		$backup_dir_permissions = 0755;
	} else {
		$backup_dir_permissions = 0750;
	}
	
	if(!is_dir($backup_dir)) {
		mkdir(escapeshellcmd($backup_dir), $backup_dir_permissions, true);
	} else {
		chmod(escapeshellcmd($backup_dir), $backup_dir_permissions);
	}
	
	$sql = "SELECT * FROM web_domain WHERE type = 'vhost'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			
			// Create a backup
			if($rec['backup_interval'] == 'daily' or ($rec['backup_interval'] == 'weekly' && date('w') == 0) or ($rec['backup_interval'] == 'monthly' && date('d') == '01')) {
				
				$web_path = $rec['document_root'];
				$web_user = $rec['system_user'];
				$web_group = $rec['system_group'];
				$web_id = $rec['domain_id'];
				$web_backup_dir = $backup_dir.'/web'.$web_id;
				if(!is_dir($web_backup_dir)) mkdir($web_backup_dir, 0750);
				chmod($web_backup_dir, 0750); 
				if(isset($server_config['backup_dir_ftpread']) && $server_config['backup_dir_ftpread'] == 'y') {
					chown($web_backup_dir, $rec['system_user']); 
					chgrp($web_backup_dir, $rec['system_group']);
				} else {
					chown($web_backup_dir, 'root');
					chgrp($web_backup_dir, 'root');
				}
				exec('cd '.escapeshellarg($web_path).' && sudo -u '.escapeshellarg($web_user).' find . -group '.escapeshellarg($web_group).' -print | zip -y '.escapeshellarg($web_backup_dir.'/web.zip').' -@');
				chown($web_backup_dir.'/web.zip', $rec['system_user']); 
				chgrp($web_backup_dir.'/web.zip', $rec['system_group']);
				chmod($web_backup_dir.'/web.zip', 0750);
				
				// Rename or remove old backups
				$backup_copies = intval($rec['backup_copies']);
				
				//* delete any older backup copies that previously existed
				for ($n = $backup_copies; $n <= 10; $n++) {
					if(is_file($web_backup_dir.'/web.'.$n.'.zip')) unlink($web_backup_dir.'/web.'.$n.'.zip');
				}
				
				for($n = $backup_copies - 1; $n >= 1; $n--) {
					if(is_file($web_backup_dir.'/web.'.$n.'.zip')) {
						rename($web_backup_dir.'/web.'.$n.'.zip',$web_backup_dir.'/web.'.($n+1).'.zip');
					}
				}
			
				if(is_file($web_backup_dir.'/web.zip')) rename($web_backup_dir.'/web.zip',$web_backup_dir.'/web.1.zip');
			
				// Create backupdir symlink
				if(is_link($web_path.'/backup')) unlink($web_path.'/backup');
				symlink($web_backup_dir,$web_path.'/backup');
				// chmod($web_path.'/backup', 0755);
				chown($web_path.'/backup', $rec['system_user']); 
				chgrp($web_path.'/backup', $rec['system_group']);
				
			}
			
			/* If backup_interval is set to none and we have a 
			backup directory for the website, then remove the backups */
			
			if($rec['backup_interval'] == 'none') {
				$web_id = $rec['domain_id'];
				$web_user = $rec['system_user'];
				$web_backup_dir = realpath($backup_dir.'/web'.$web_id);
				if(is_dir($web_backup_dir)) {
					exec('sudo -u '.escapeshellarg($web_user).' rm -f '.escapeshellarg($web_backup_dir.'/*'));
				}
			}
		}
	}
}

include_once dirname(__FILE__).'/backup.php';

die("finished.\n");
?>

<?php

/*
  Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

class monitor_tools {

	//** Get distribution identifier
	//** IMPORTANT!
	//   This is the same code as in install/lib/install.lib.php
	//   So if you change it here, you also have to change it in there!
	//   Please do not forget to remove the swriteln(); - lines here at this file
	public function get_distname() {

		$distname = '';
		$distver = '';
		$distid = '';
		$distbaseid = '';

		//** Debian or Ubuntu
		if (file_exists('/etc/debian_version')) {

			if (trim(file_get_contents('/etc/debian_version')) == '4.0') {
				$distname = 'Debian';
				$distver = '4.0';
				$distid = 'debian40';
				$distbaseid = 'debian';
			} elseif (strstr(trim(file_get_contents('/etc/debian_version')), '5.0')) {
				$distname = 'Debian';
				$distver = 'Lenny';
				$distid = 'debian40';
				$distbaseid = 'debian';
			} elseif (strstr(trim(file_get_contents('/etc/debian_version')), '6.0') || trim(file_get_contents('/etc/debian_version')) == 'squeeze/sid') {
				$distname = 'Debian';
				$distver = 'Squeeze/Sid';
				$distid = 'debian60';
				$distbaseid = 'debian';
			} else {
				$distname = 'Debian';
				$distver = 'Unknown';
				$distid = 'debian40';
				$distbaseid = 'debian';
			}
		}

		//** OpenSuSE
		elseif (file_exists('/etc/SuSE-release')) {
			if (stristr(file_get_contents('/etc/SuSE-release'), '11.0')) {
				$distname = 'openSUSE';
				$distver = '11.0';
				$distid = 'opensuse110';
				$distbaseid = 'opensuse';
			} elseif (stristr(file_get_contents('/etc/SuSE-release'), '11.1')) {
				$distname = 'openSUSE';
				$distver = '11.1';
				$distid = 'opensuse110';
				$distbaseid = 'opensuse';
			} elseif (stristr(file_get_contents('/etc/SuSE-release'), '11.2')) {
				$distname = 'openSUSE';
				$distver = '11.1';
				$distid = 'opensuse110';
				$distbaseid = 'opensuse';
			} else {
				$distname = 'openSUSE';
				$distver = 'Unknown';
				$distid = 'opensuse110';
				$distbaseid = 'opensuse';
			}
		}


		//** Redhat
		elseif (file_exists('/etc/redhat-release')) {

			$content = file_get_contents('/etc/redhat-release');

			if (stristr($content, 'Fedora release 9 (Sulphur)')) {
				$distname = 'Fedora';
				$distver = '9';
				$distid = 'fedora9';
				$distbaseid = 'fedora';
			} elseif (stristr($content, 'Fedora release 10 (Cambridge)')) {
				$distname = 'Fedora';
				$distver = '10';
				$distid = 'fedora9';
				$distbaseid = 'fedora';
			} elseif (stristr($content, 'Fedora release 10')) {
				$distname = 'Fedora';
				$distver = '11';
				$distid = 'fedora9';
				$distbaseid = 'fedora';
			} elseif (stristr($content, 'CentOS release 5.2 (Final)')) {
				$distname = 'CentOS';
				$distver = '5.2';
				$distid = 'centos52';
				$distbaseid = 'fedora';
			} elseif (stristr($content, 'CentOS release 5.3 (Final)')) {
				$distname = 'CentOS';
				$distver = '5.3';
				$distid = 'centos53';
				$distbaseid = 'fedora';
			} else {
				$distname = 'Redhat';
				$distver = 'Unknown';
				$distid = 'fedora9';
				$distbaseid = 'fedora';
			}
		}

		//** Gentoo
		elseif (file_exists('/etc/gentoo-release')) {

			$content = file_get_contents('/etc/gentoo-release');

			preg_match_all('/([0-9]{1,2})/', $content, $version);
			$distname = 'Gentoo';
			$distver = $version[0][0] . $version[0][1];
			$distid = 'gentoo';
			$distbaseid = 'gentoo';
		} else {
			die('Unrecognized GNU/Linux distribution');
		}

		return array('name' => $distname, 'version' => $distver, 'id' => $distid, 'baseid' => $distbaseid);
	}

    //** Email Quota
    public function monitorEmailQuota() {
        global $conf, $app;

		//* Initialize data array
		$data = array();

		//* the id of the server as int
		$server_id = intval($conf['server_id']);

		//* The type of the data
		$type = 'email_quota';

		//* The state of the email_quota.
		$state = 'ok';
		
		$mailboxes = $app->db->queryAllRecords("SELECT email FROM mail_user WHERE server_id = $server_id");
		if(is_array($mailboxes)) {
			foreach($mailboxes as $mb) {
				$email = $mb['email'];
				$email_parts = explode('@',$mb['email']);
				$filename = '/var/vmail/'.$email_parts[1].'/'.$email_parts[0].'/.quotausage';
				if(file_exists($filename)) {
					$quotafile = file($filename);
					$data[$email]['used'] = trim($quotafile['1']);
					unset($quotafile);
				} else {
					exec('du -s '.escapeshellcmd('/var/vmail/'.$email_parts[1].'/'.$email_parts[0]),$out);
					$parts = explode(' ',$out[0]);
					$data[$email]['used'] = intval($parts[0])*1024;
					unset($out);
					unset($parts);
				}
			}
		}
		
		unset($mailboxes);

        //* Dovecot quota check Courier in progress lathama@gmail.com
		/*
        if($dir = opendir("/var/vmail")){
            while (($quotafiles = readdir($dir)) !== false){
                if(preg_match('/.\_quota$/', $quotafiles)){
                    $quotafile = (file("/var/vmail/" . $quotafiles));
                    $emailaddress = preg_replace('/_quota/',"", $quotafiles);
                    $emailaddress = preg_replace('/_/',"@", $emailaddress);
                    $data[$emailaddress]['used'] = trim($quotafile['1']);
                }
            }
            closedir($dir);
        }
		*/
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
        return $res;
    }

    //** Filesystem Quota
	public function monitorHDQuota() {
		global $conf;

		//* Initialize data array
		$data = array();

		//* the id of the server as int
		$server_id = intval($conf['server_id']);

		//* The type of the data
		$type = 'harddisk_quota';

		//* The state of the harddisk_quota.
		$state = 'ok';

		//* Fetch the data for all users
		$dfData = shell_exec('repquota -au');

		//* Split into array
		$df = explode("\n", $dfData);

		//* ignore the first 5 lines, process the rest
		for ($i = 5; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				//* Make a array of the data
				$s = preg_split('/[\s]+/', $df[$i]);
				$username = $s[0];
				if (substr($username, 0, 3) == 'web') {
					if (isset($data['user'][$username])) {
						$data['user'][$username]['used'] += $s[2];
						$data['user'][$username]['soft'] += $s[3];
						$data['user'][$username]['hard'] += $s[4];
					} else {
						$data['user'][$username]['used'] = $s[2];
						$data['user'][$username]['soft'] = $s[3];
						$data['user'][$username]['hard'] = $s[4];
					}
				}
			}
		}

		//** Fetch the data for all users
		$dfData = shell_exec('repquota -ag');

		//* split into array
		$df = explode("\n", $dfData);

		//* ignore the first 5 lines, process the rest
		for ($i = 5; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				//* Make a array of the data
				$s = preg_split('/[\s]+/', $df[$i]);
				$groupname = $s[0];
				if (substr($groupname, 0, 6) == 'client') {
					if (isset($data['group'][$groupname])) {
						$data['group'][$groupname]['used'] += $s[1];
						$data['group'][$groupname]['soft'] += $s[2];
						$data['group'][$groupname]['hard'] += $s[3];
					} else {
						$data['group'][$groupname]['used'] = $s[1];
						$data['group'][$groupname]['soft'] = $s[2];
						$data['group'][$groupname]['hard'] = $s[3];
					}
				}
			}
		}

		//* Return the Result
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorServer() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'server_load';

		/*
		  Fetch the data into a array
		 */
		$procUptime = shell_exec("cat /proc/uptime | cut -f1 -d' '");
		$data['up_days'] = floor($procUptime / 86400);
		$data['up_hours'] = floor(($procUptime - $data['up_days'] * 86400) / 3600);
		$data['up_minutes'] = floor(($procUptime - $data['up_days'] * 86400 - $data['up_hours'] * 3600) / 60);

		$data['uptime'] = shell_exec('uptime');

		$tmp = explode(',', $data['uptime'], 4);
		$tmpUser = explode(' ', trim($tmp[2]));
		$data['user_online'] = intval($tmpUser[0]);

		//* New Load Average code to fix "always zero" bug in non-english distros. NEEDS TESTING
		$loadTmp = shell_exec("cat /proc/loadavg | cut -f1-3 -d' '");
		$load = explode(' ', $loadTmp);
		$data['load_1'] = floatval(str_replace(',', '.', $load[0]));
		$data['load_5'] = floatval(str_replace(',', '.', $load[1]));
		$data['load_15'] = floatval(str_replace(',', '.', $load[2]));

		/** The state of the server-load. */
		$state = 'ok';
		if ($data['load_1'] > 20)
			$state = 'info';
		if ($data['load_1'] > 50)
			$state = 'warning';
		if ($data['load_1'] > 100)
			$state = 'critical';
		if ($data['load_1'] > 150)
			$state = 'error';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorOsVer() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'os_info';

		/*
		  Fetch the data into a array
		 */
		$dist = $this->get_distname();

		$data['name'] = $dist['name'];
		$data['version'] = $dist['version'];

		/* the OS has no state. It is, what it is */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorIspcVer() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'ispc_info';

		/*
		  Fetch the data into a array
		 */
		$data['name'] = ISPC_APP_TITLE;
		$data['version'] = ISPC_APP_VERSION;

		/* the ISPC-Version has no state. It is, what it is */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorDiskUsage() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'disk_usage';

		/** The state of the disk-usage */
		$state = 'ok';

		/** Fetch the data of ALL devices into a array (needed for monitoring!) */
		$dfData = shell_exec('df -hT');

		// split into array
		$df = explode("\n", $dfData);

		/*
		 * ignore the first line, process the rest
		 */
		for ($i = 1; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				/*
				 * Make an array of the data
				 */
				$s = preg_split('/[\s]+/', $df[$i]);
				$data[$i]['fs'] = $s[0];
				$data[$i]['type'] = $s[1];
				$data[$i]['size'] = $s[2];
				$data[$i]['used'] = $s[3];
				$data[$i]['available'] = $s[4];
				$data[$i]['percent'] = $s[5];
				$data[$i]['mounted'] = $s[6];
				/*
				 * calculate the state
				 */
				$usePercent = floatval($data[$i]['percent']);
				
				//* get the free memsize
				if(substr($data[$i]['available'],-1) == 'G') {
					$freesize = floatval($data[$i]['available'])*1024;
				} elseif(substr($data[$i]['available'],-1) == 'T') {
					$freesize = floatval($data[$i]['available'])*1024*1024;
				} else {
					$freesize = floatval($data[$i]['available']);
				}

				//* We don't want to check some filesystem which have no sensible filling levels
				switch ($data[$i]['type']) {
					case 'iso9660':
					case 'cramfs':
					case 'udf':
					case 'tmpfs':
					case 'devtmpfs':
					case 'udev':
						break;
					default:
						if ($usePercent > 75 && $freesize < 2000)
							$state = $this->_setState($state, 'info');
						if ($usePercent > 80 && $freesize < 1000)
							$state = $this->_setState($state, 'warning');
						if ($usePercent > 90 && $freesize < 500)
							$state = $this->_setState($state, 'critical');
						if ($usePercent > 95 && $freesize < 100)
							$state = $this->_setState($state, 'error');
						break;
				}
			}
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMemUsage() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'mem_usage';

		/*
		  Fetch the data into a array
		 */
		$miData = shell_exec('cat /proc/meminfo');

		$memInfo = explode("\n", $miData);

		foreach ($memInfo as $line) {
			$part = preg_split('/:/', $line);
			$key = trim($part[0]);
			$tmp = explode(' ', trim($part[1]));
			$value = 0;
			if ($tmp[1] == 'kB')
				$value = $tmp[0] * 1024;
			$data[$key] = $value;
		}

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorCpu() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'cpu_info';

		/*
		  Fetch the data into a array
		 */
		if (file_exists('/proc/cpuinfo')) {
			$cpuData = shell_exec('cat /proc/cpuinfo');
			$cpuInfo = explode("\n", $cpuData);
			$processor = 0;

			foreach ($cpuInfo as $line) {

				$part = preg_split('/:/', $line);
				$key = trim($part[0]);
				$value = trim($part[1]);
				if ($key == 'processor')
					$processor = intval($value);
				if ($key != '')
					$data[$key . ' ' . $processor] = $value;
			}

			/* the cpu has no state. It is, what it is */
			$state = 'no_state';
		} else {
			/*
			 * It is not Linux, so there is no data and no state
			 *
			 * no_state, NOT unknown, because "unknown" is shown as state
			 * inside the GUI. no_state is hidden.
			 *
			 * We have to write NO DATA inside the DB, because the GUI
			 * could not know, if there is any dat, or not...
			 */
			$state = 'no_state';
			$data['output'] = '';
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorServices() {
		global $app;
		global $conf;

		/** the id of the server as int */
		$server_id = intval($conf['server_id']);

		/**  get the "active" Services of the server from the DB */
		$services = $app->db->queryOneRecord('SELECT * FROM server WHERE server_id = ' . $server_id);
		/*
		 * If the DB is down, we have to set the db to "yes".
		 * If we don't do this, then the monitor will NOT monitor, that the db is down and so the
		 * rescue-module can not try to rescue the db
		 */
		if ($services == null) {
			$services['db_server'] = 1;
		}

		/* The type of the Monitor-data */
		$type = 'services';

		/** the State of the monitoring */
		/* ok, if ALL active services are running,
		 * error, if not
		 * There is no other state!
		 */
		$state = 'ok';

		/* Monitor Webserver */
		$data['webserver'] = -1; // unknown - not needed
		if ($services['web_server'] == 1) {
			if ($this->_checkTcp('localhost', 80)) {
				$data['webserver'] = 1;
			} else {
				$data['webserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor FTP-Server */
		$data['ftpserver'] = -1; // unknown - not needed
		if ($services['file_server'] == 1) {
			if ($this->_checkFtp('localhost', 21)) {
				$data['ftpserver'] = 1;
			} else {
				$data['ftpserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor SMTP-Server */
		$data['smtpserver'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 25)) {
				$data['smtpserver'] = 1;
			} else {
				$data['smtpserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor POP3-Server */
		$data['pop3server'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 110)) {
				$data['pop3server'] = 1;
			} else {
				$data['pop3server'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor IMAP-Server */
		$data['imapserver'] = -1; // unknown - not needed
		if ($services['mail_server'] == 1) {
			if ($this->_checkTcp('localhost', 143)) {
				$data['imapserver'] = 1;
			} else {
				$data['imapserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor BIND-Server */
		$data['bindserver'] = -1; // unknown - not needed
		if ($services['dns_server'] == 1) {
			if ($this->_checkUdp('localhost', 53)) {
				$data['bindserver'] = 1;
			} else {
				$data['bindserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/* Monitor MySQL Server */
		$data['mysqlserver'] = -1; // unknown - not needed
		if ($services['db_server'] == 1) {
			if ($this->_checkTcp('localhost', 3306)) {
				$data['mysqlserver'] = 1;
			} else {
				$data['mysqlserver'] = 0;
				$state = 'error'; // because service is down
			}
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorOpenVzHost() {
		global $app;
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'openvz_veinfo';

		/*
		  Fetch the data into a array
		 */
		$app->load(openvz_tools);
		$openVzTools = new openvz_tools();
		$data = $openVzTools->getOpenVzVeInfo();

		/* the VE-Info has no state. It is, what it is */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorOpenVzUserBeancounter() {
		global $app;
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'openvz_beancounter';

		/*
		  Fetch the data into a array
		 */
		$app->load(openvz_tools);
		$openVzTools = new openvz_tools();
		$data = $openVzTools->getOpenVzVeBeanCounter();

		/* calculate the state of the beancounter */
		if ($data == '') {
			$state = 'no_state';
		} else {
			$state = 'ok';

			/* transfer this output-string into a array */
			$test = explode("\n", $data);

			/* the first list of the output is not needed */
			array_shift($test);

			/* now process all items of the rest */
			foreach ($test as $item) {
				/*
				 * eliminate all doubled spaces and spaces at the beginning and end
				 */
				while (strpos($item, '  ') !== false) {
					$item = str_replace('  ', ' ', $item);
				}
				$item = trim($item);

				/*
				 * The failcounter is the LAST
				 */
				if ($item != '') {
					$tmp = explode(' ', $item);
					$failCounter = $tmp[sizeof($tmp) - 1];
					if ($failCounter > 0)
						$state = 'info';
					if ($failCounter > 50)
						$state = 'warning';
					if ($failCounter > 200)
						$state = 'critical';
					if ($failCounter > 10000)
						$state = 'error';
				}
			}
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorSystemUpdate() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'system_update';

		/* This monitoring is only available on Debian or Ubuntu */
		if (file_exists('/etc/debian_version')) {

			/*
			 * first update the "apt database"
			 */
			shell_exec('apt-get update');

			/*
			 * Then test the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$aptData = shell_exec('apt-get -s -qq dist-upgrade');
			if ($aptData == '') {
				/* There is nothing to update! */
				$state = 'ok';
			} else {
				/*
				 * There is something to update! this is in most cases not critical, so we can
				 * do a system-update once a month or so...
				 */
				$state = 'info';
			}

			/*
			 * Fetch the output
			 */
			$data['output'] = shell_exec('apt-get -s -q dist-upgrade');
		} elseif (file_exists('/etc/gentoo-release')) {

			/*
			 * first update the portage tree
			 */

			// In keeping with gentoo's rsync policy, don't update to frequently (every four hours - taken from http://www.gentoo.org/doc/en/source_mirrors.xml)
			$do_update = true;
			if (file_exists('/usr/portage/metadata/timestamp.chk')) {
				$datetime = file_get_contents('/usr/portage/metadata/timestamp.chk');
				$datetime = trim($datetime);

				$dstamp = strtotime($datetime);
				if ($dstamp) {
					$checkat = $dstamp + 14400; // + 4hours
					if (mktime() < $checkat) {
						$do_update = false;
					}
				}
			}

			if ($do_update) {
				shell_exec('emerge --sync --quiet');
			}

			/*
			 * Then test the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$emergeData = shell_exec('glsa-check -t affected');
			if ($emergeData == '') {
				/* There is nothing to update! */
				$state = 'ok';
				$data['output'] = 'No unapplied GLSA\'s found on the system.';
			} else {
				/* There is something to update! */
				$state = 'info';
				$data['output'] = shell_exec('glsa-check -pv --nocolor affected 2>/dev/null');
			}
		} elseif (file_exists('/etc/SuSE-release')) {

			/*
			 * update and find the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$aptData = shell_exec('zypper -q lu');
			if ($aptData == '') {
				/* There is nothing to update! */
				$state = 'ok';
			} else {
				/*
				 * There is something to update! this is in most cases not critical, so we can
				 * do a system-update once a month or so...
				 */
				$state = 'info';
			}

			/*
			 * Fetch the output
			 */
			$data['output'] = shell_exec('zypper lu');
		} else {
			/*
			 * It is not Debian/Ubuntu, so there is no data and no state
			 *
			 * no_state, NOT unknown, because "unknown" is shown as state
			 * inside the GUI. no_state is hidden.
			 *
			 * We have to write NO DATA inside the DB, because the GUI
			 * could not know, if there is any dat, or not...
			 */
			$state = 'no_state';
			$data['output'] = '';
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMailQueue() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'mailq';

		/* Get the data from the mailq */
		$data['output'] = shell_exec('mailq');

		/*
		 *  The last line has more informations
		 */
		$tmp = explode("\n", $data['output']);
		$more = $tmp[sizeof($tmp) - 1];
		$this->_getIntArray($more);
		$data['bytes'] = $res[0];
		$data['requests'] = $res[1];

		/** The state of the mailq. */
		$state = 'ok';
		if ($data['requests'] > 2000)
			$state = 'info';
		if ($data['requests'] > 5000)
			$state = 'warning';
		if ($data['requests'] > 8000)
			$state = 'critical';
		if ($data['requests'] > 10000)
			$state = 'error';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorRaid() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'raid_state';

		/*
		 * We support several RAID types, but if we can't find any of them, we have no data
		 */
		$state = 'no_state';
		$data['output'] = '';

		/*
		 * Check, if Software-RAID is enabled
		 */
		if (file_exists('/proc/mdstat')) {
			/*
			 * Fetch the output
			 */
			$data['output'] = shell_exec('cat /proc/mdstat');

			/*
			 * Then calc the state.
			 */
			$tmp = explode("\n", $data['output']);
			$state = 'ok';
			for ($i = 0; $i < sizeof($tmp); $i++) {
				/* fetch the next line */
				$line = $tmp[$i];

				if ((strpos($line, '[U_]') !== false) || (strpos($line, '[_U]') !== false)) {
					/* One Disk is not working.
					 * if the next line starts with "[>" or "[=" then
					 * recovery (resync) is in state and the state is
					 * information instead of critical
					 */
					$nextLine = $tmp[$i + 1];
					if ((strpos($nextLine, '[>') === false) && (strpos($nextLine, '[=') === false)) {
						$state = $this->_setState($state, 'critical');
					} else {
						$state = $this->_setState($state, 'info');
					}
				}
				if (strpos($line, '[__]') !== false) {
					/* both Disk are not working */
					$state = $this->_setState($state, 'error');
				}
				if (strpos($line, '[UU]') !== false) {
					/* The disks are OK.
					 * if the next line starts with "[>" or "[=" then
					 * recovery (resync) is in state and the state is
					 * information instead of ok
					 */
					$nextLine = $tmp[$i + 1];
					if ((strpos($nextLine, '[>') === false) && (strpos($nextLine, '[=') === false)) {
						$state = $this->_setState($state, 'ok');
					} else {
						$state = $this->_setState($state, 'info');
					}
				}
			}
		}
		/*
		 * Check, if we have mpt-status installed (LSIsoftware-raid)
		 */
		if (file_exists('/proc/mpt/summary')) {
			system('which mpt-status', $retval);
			if ($retval === 0) {
				/*
				 * Fetch the output
				 */
				$data['output'] = shell_exec('mpt-status --autoload');

				/*
				 * Then calc the state.
				 */
				$state = 'ok';
				if(is_array($data['output'])) {
					foreach ($data['output'] as $item) {
						/*
						* The output contains information for every RAID and every HDD.
						* We only need the state of the RAID
						*/
						if (strpos($item, 'state ') !== false) {
							/*
							* We found a raid, process the state of it
							*/
							if (strpos($item, ' ONLINE ') !== false) {
								$this->_setState($state, 'ok');
							} elseif (strpos($item, ' OPTIMAL ') !== false) {
								$this->_setState($state, 'ok');
							} elseif (strpos($item, ' INITIAL ') !== false) {
								$this->_setState($state, 'info');
							} elseif (strpos($item, ' INACTIVE ') !== false) {
								$this->_setState($state, 'critical');
							} elseif (strpos($item, ' RESYNC ') !== false) {
								$this->_setState($state, 'info');
							} elseif (strpos($item, ' DEGRADED ') !== false) {
								$this->_setState($state, 'critical');
							} else {
								/* we don't know the state. so we set the state to critical, that the
								* admin is warned, that something is wrong
								*/
								$this->_setState($state, 'critical');
							}
						}
					}
				}
			}
		}
		
		/*
		* 3ware Controller
		*/
		system('which tw_cli', $retval);
		if($retval === 0) {

			$data['output'] = shell_exec('tw_cli info c0');

			$state = 'ok';
			foreach ($data['output'] as $item) {
				if (strpos($item, 'RAID') !== false) {
					if (strpos($item, ' VERIFYING ') !== false) {
						$this->_setState($state, 'info');
					}
					else if (strpos($item, ' MIGRATE-PAUSED ') !== false) {
						$this->_setState($state, 'info');
					}
					else if (strpos($item, ' MIGRATING ') !== false) {
						$this->_setState($state, 'ok');
					}
					else if (strpos($item, ' INITIALIZING ') !== false) {
						$this->_setState($state, 'info');
					}
					else if (strpos($item, ' INIT-PAUSED ') !== false) {
						$this->_setState($state, 'info');
					}
					else if (strpos($item, ' REBUILDING ') !== false) {
						$this->_setState($state, 'info');
					}
					else if (strpos($item, ' REBUILD-PAUSED ') !== false) {
						$this->_setState($state, 'warning');
					}
					else if (strpos($item, ' RECOVERY ') !== false) {
						$this->_setState($state, 'warning');
					}
					else if (strpos($item, ' DEGRADED ') !== false) {
						$this->_setState($state, 'critical');
					}
					else if (strpos($item, ' UNKNOWN ') !== false) {
						$this->_setState($state, 'critical');
					}
					else if (strpos($item, ' OK ') !== false) {
						$this->_setState($state, 'ok');
					}
					else if (strpos($item, ' OPTIMAL ') !== false) {
						$this->_setState($state, 'ok');
					}
					else {
						$this->_setState($state, 'critical');
					}
				}
			}
		}
		

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorRkHunter() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'rkhunter';

		/* This monitoring is only available if rkhunter is installed */
		system('which rkhunter', $retval);
		if ($retval === 0) {
			/*
			 * Fetch the output
			 */
			$data['output'] = shell_exec('rkhunter --update --checkall --nocolors --skip-keypress');

			/*
			 * At this moment, there is no state (maybe later)
			 */
			$state = 'no_state';
		} else {
			/*
			 * rkhunter is not installed, so there is no data and no state
			 *
			 * no_state, NOT unknown, because "unknown" is shown as state
			 * inside the GUI. no_state is hidden.
			 *
			 * We have to write NO DATA inside the DB, because the GUI
			 * could not know, if there is any dat, or not...
			 */
			$state = 'no_state';
			$data['output'] = '';
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorFail2ban() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_fail2ban';

		/* This monitoring is only available if fail2ban is installed */
		system('which fail2ban-client', $retval); // Debian, Ubuntu, Fedora
		if ($retval !== 0)
			system('which fail2ban', $retval); // CentOS
		if ($retval === 0) {
			/*  Get the data of the log */
			$data = $this->_getLogData($type);

			/*
			 * At this moment, there is no state (maybe later)
			 */
			$state = 'no_state';
		} else {
			/*
			 * fail2ban is not installed, so there is no data and no state
			 *
			 * no_state, NOT unknown, because "unknown" is shown as state
			 * inside the GUI. no_state is hidden.
			 *
			 * We have to write NO DATA inside the DB, because the GUI
			 * could not know, if there is any dat, or not...
			 */
			$state = 'no_state';
			$data = '';
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorIPTables() {
        global $conf;

        /* the id of the server as int */
        $server_id = intval($conf['server_id']);

        /** The type of the data */
        $type = 'iptables_rules';

        /* This monitoring is only available if fail2ban is installed */
        system('which iptables', $retval); // Debian, Ubuntu, Fedora
        if ($retval === 0) {
            /*  Get the data of the log */
            $data['output'] = '<h2>iptables -S (ipv4)</h2>'.shell_exec('iptables -S');

            /*
             * At this moment, there is no state (maybe later)
             */
            $state = 'no_state';
        } else {
            $state = 'no_state';
            $data = '';
        }
        
        
        /* This monitoring is only available if fail2ban is installed */
        system('which ip6tables', $retval); // Debian, Ubuntu, Fedora
        if ($retval === 0) {
            /*  Get the data of the log */
            $data['output'] .= '<br><h2>ip6tables -S (ipv6)</h2>'.shell_exec('ip6tables -S');

            /*
             * At this moment, there is no state (maybe later)
             */
            $state = 'no_state';
        } else {
            $state = 'no_state';
            $data = '';
        }

        /*
         * Return the Result
         */
        $res['server_id'] = $server_id;
        $res['type'] = $type;
        $res['data'] = $data;
        $res['state'] = $state;
        return $res;
    }

	public function monitorSysLog() {
		global $app;
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'sys_log';

		/*
		 * is there any warning or error for this server?
		 */
		$state = 'ok';
		$dbData = $app->dbmaster->queryAllRecords('SELECT loglevel FROM sys_log WHERE server_id = ' . $server_id . ' AND loglevel > 0');
		if (is_array($dbData)) {
			foreach ($dbData as $item) {
				if ($item['loglevel'] == 1)
					$state = $this->_setState($state, 'warning');
				if ($item['loglevel'] == 2)
					$state = $this->_setState($state, 'error');
			}
		}

		/** There is no monitor-data because the data is in the sys_log table */
		$data['output'] = '';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMailLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_mail';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMailWarnLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_mail_warn';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMailErrLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_mail_err';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorMessagesLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_messages';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorISPCCronLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_ispc_cron';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/*
		 * actually this info has no state.
		 * maybe someone knows better...???...
		 */
		$state = 'no_state';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorFreshClamLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_freshclam';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		/* Get the data from the LAST log-Entry.
		 * if there can be found:
		 * WARNING: Your ClamAV installation is OUTDATED!
		 * then the clamav is outdated. This is a warning!
		 */
		$state = 'ok';

		$tmp = explode("\n", $data);
		$lastLog = array();
		if ($tmp[sizeof($tmp) - 1] == '') {
			/* the log ends with an empty line remove this */
			array_pop($tmp);
		}
		if (strpos($tmp[sizeof($tmp) - 1], '-------------') !== false) {
			/* the log ends with "-----..." remove this */
			array_pop($tmp);
		}
		for ($i = sizeof($tmp) - 1; $i > 0; $i--) {
			if (strpos($tmp[$i], '---------') === false) {
				/* no delimiter found, so add this to the last-log */
				$lastLog[] = $tmp[$i];
			} else {
				/* delimiter found, so there is no more line left! */
				break;
			}
		}

		/*
		 * Now we have the last log in the array.
		 * Check if the outdated-string is found...
		 */
		foreach ($lastLog as $line) {
			if (strpos(strtolower($line), 'outdated') !== false) {
				/*
				 * Outdatet is only info, because if we set this to warning, the server is
				 * as long in state warning, as there is a new version of ClamAv which takes
				 * sometimes weeks!
				 */
				$state = $this->_setState($state, 'info');
			}
		}

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorClamAvLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_clamav';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		// Todo: the state should be calculated.
		$state = 'ok';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function monitorIspConfigLog() {
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */
		$type = 'log_ispconfig';

		/* Get the data of the log */
		$data = $this->_getLogData($type);

		// Todo: the state should be calculated.
		$state = 'ok';

		/*
		 * Return the Result
		 */
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;
		return $res;
	}

	public function _getLogData($log) {
		global $conf;

		$dist = '';
		$logfile = '';

		if (@is_file('/etc/debian_version')) {
			$dist = 'debian';
		} elseif (@is_file('/etc/redhat-release')) {
			$dist = 'redhat';
		} elseif (@is_file('/etc/SuSE-release')) {
			$dist = 'suse';
		} elseif (@is_file('/etc/gentoo-release')) {
			$dist = 'gentoo';
		}

		switch ($log) {
			case 'log_mail':
				if ($dist == 'debian') {
					$logfile = '/var/log/mail.log';
				} elseif ($dist == 'redhat') {
					$logfile = '/var/log/maillog';
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/mail.info';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/maillog';
				}
				break;
			case 'log_mail_warn':
				if ($dist == 'debian') {
					$logfile = '/var/log/mail.warn';
				} elseif ($dist == 'redhat') {
					$logfile = '/var/log/maillog';
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/mail.warn';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/maillog';
				}
				break;
			case 'log_mail_err':
				if ($dist == 'debian') {
					$logfile = '/var/log/mail.err';
				} elseif ($dist == 'redhat') {
					$logfile = '/var/log/maillog';
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/mail.err';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/maillog';
				}
				break;
			case 'log_messages':
				if ($dist == 'debian') {
					$logfile = '/var/log/messages';
				} elseif ($dist == 'redhat') {
					$logfile = '/var/log/messages';
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/messages';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/messages';
				}
				break;
			case 'log_ispc_cron':
				if ($dist == 'debian') {
					$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
				} elseif ($dist == 'redhat') {
					$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
				} elseif ($dist == 'suse') {
					$logfile = $conf['ispconfig_log_dir'] . '/cron.log';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/cron';
				}
				break;
			case 'log_freshclam':
				if ($dist == 'debian') {
					$logfile = '/var/log/clamav/freshclam.log';
				} elseif ($dist == 'redhat') {
					$logfile = (is_file('/var/log/clamav/freshclam.log') ? '/var/log/clamav/freshclam.log' : '/var/log/freshclam.log');
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/freshclam.log';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/clamav/freshclam.log';
				}
				break;
			case 'log_clamav':
				if ($dist == 'debian') {
					$logfile = '/var/log/clamav/clamav.log';
				} elseif ($dist == 'redhat') {
					$logfile = (is_file('/var/log/clamav/clamd.log') ? '/var/log/clamav/clamd.log' : '/var/log/maillog');
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/clamd.log';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/clamav/clamd.log';
				}
				break;
			case 'log_fail2ban':
				if ($dist == 'debian') {
					$logfile = '/var/log/fail2ban.log';
				} elseif ($dist == 'redhat') {
					$logfile = '/var/log/fail2ban.log';
				} elseif ($dist == 'suse') {
					$logfile = '/var/log/fail2ban.log';
				} elseif ($dist == 'gentoo') {
					$logfile = '/var/log/fail2ban.log';
				}
				break;
			case 'log_ispconfig':
				if ($dist == 'debian') {
					$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
				} elseif ($dist == 'redhat') {
					$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
				} elseif ($dist == 'suse') {
					$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
				} elseif ($dist == 'gentoo') {
					$logfile = $conf['ispconfig_log_dir'] . '/ispconfig.log';
				}
				break;
			default:
				$logfile = '';
				break;
		}

		// Getting the logfile content
		if ($logfile != '') {
			$logfile = escapeshellcmd($logfile);
			if (stristr($logfile, ';') or substr($logfile, 0, 9) != '/var/log/' or stristr($logfile, '..')) {
				$log = 'Logfile path error.';
			} else {
				$log = '';
				if (is_readable($logfile)) {
					$fd = popen('tail -n 100 ' . $logfile, 'r');
					if ($fd) {
						while (!feof($fd)) {
							$log .= fgets($fd, 4096);
							$n++;
							if ($n > 1000)
								break;
						}
						fclose($fd);
					}
				} else {
					$log = 'Unable to read ' . $logfile;
				}
			}
		}

		return $log;
	}

	private function _checkTcp($host, $port) {
		/* Try to open a connection */
		$fp = @fsockopen($host, $port, $errno, $errstr, 2);

		if ($fp) {
			/*
			 * We got a connection, this means, everything is O.K.
			 * But maybe we are able to do more deep testing?
			 */
			if ($port == 80) {
				/*
				 * Port 80 means, testing APACHE
				 * So we can do a deepter test and try to get data over this connection.
				 * (if apache hangs, we get a connection but a timeout by trying to GET the data!)
				 */
				// fwrite($fp, "GET / HTTP/1.0\r\n\r\n");
				$out = "GET / HTTP/1.1\r\n";
				$out .= "Host: localhost\r\n";
				$out .= "User-Agent: Mozilla/5.0 (ISPConfig monitor)\r\n";
				$out .= "Accept: application/xml,application/xhtml+xml,text/html\r\n";
				$out .= "Connection: Close\r\n\r\n";
				fwrite($fp, $out);
				stream_set_timeout($fp, 5); // Timeout after 5 seconds
				$res = fread($fp, 10);  // try to get 10 bytes (enough to test!)
				$info = stream_get_meta_data($fp);
				if ($info['timed_out']) {
					return false; // Apache was not able to send data over this connection
				}
			}

			/* The connection is no longer needed */
			fclose($fp);
			/* We are able to establish a connection */
			return true;
		} else {
			/* We are NOT able to establish a connection */
			return false;
		}
	}

	private function _checkUdp($host, $port) {

		$fp = @fsockopen('udp://' . $host, $port, $errno, $errstr, 2);

		if ($fp) {
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}

	private function _checkFtp($host, $port) {

		$conn_id = @ftp_connect($host, $port);

		if ($conn_id) {
			@ftp_close($conn_id);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set the state to the given level (or higher, but not lesser).
	 * * If the actual state is critical and you call the method with ok,
	 *   then the state is critical.
	 *
	 * * If the actual state is critical and you call the method with error,
	 *   then the state is error.
	 */
	private function _setState($oldState, $newState) {
		/*
		 * Calculate the weight of the old state
		 */
		switch ($oldState) {
			case 'no_state': $oldInt = 0;
				break;
			case 'ok': $oldInt = 1;
				break;
			case 'unknown': $oldInt = 2;
				break;
			case 'info': $oldInt = 3;
				break;
			case 'warning': $oldInt = 4;
				break;
			case 'critical': $oldInt = 5;
				break;
			case 'error': $oldInt = 6;
				break;
		}
		/*
		 * Calculate the weight of the new state
		 */
		switch ($newState) {
			case 'no_state': $newInt = 0;
				break;
			case 'ok': $newInt = 1;
				break;
			case 'unknown': $newInt = 2;
				break;
			case 'info': $newInt = 3;
				break;
			case 'warning': $newInt = 4;
				break;
			case 'critical': $newInt = 5;
				break;
			case 'error': $newInt = 6;
				break;
		}

		/*
		 * Set to the higher level
		 */
		if ($newInt > $oldInt) {
			return $newState;
		} else {
			return $oldState;
		}
	}

	private function _getIntArray($line) {
		/** The array of float found */
		$res = array();
		/* First build a array from the line */
		$data = explode(' ', $line);
		/* then check if any item is a float */
		foreach ($data as $item) {
			if ($item . '' == (int) $item . '') {
				$res[] = $item;
			}
		}
		return $res;
	}

}

?>
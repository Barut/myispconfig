<?php
/*
 Copyright (c) 2012, Alex Beljansky, 
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

//Nginx 
$conf['nginx_conf_dir']='/usr/local/etc/nginx';
$conf['nginx_vhost_available']=$conf['nginx_conf_dir'].'/sites-available';
$conf['nginx_vhost_enable']=$conf['nginx_conf_dir'].'/sites-enable';
$conf['nginx_upstream_available']=$conf['nginx_conf_dir'].'/upstream-available';
$conf['nginx_upstream_enable']=$conf['nginx_conf_dir'].'/upstream-enable';

//Backup
$conf['backup']=array();
$conf['backup']['tmp']='/home/backups';
$conf['backup']['remote_ftp']=array('host'=>'192.168.1.1',
					'user' =>'test',
					'password'=>'passwort');
$conf['backup']['web_exclude_dir']=array('log','tmp');
$conf['backup']['web_backup_dir']=array('public_html','ssl','cgi-bin');



?>
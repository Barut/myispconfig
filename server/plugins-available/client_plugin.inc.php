<?php
/*
 * 
 *		KHEXT Plugin For Client Module 
 * 
 * 
 */

class client_plugin {

    var $plugin_name='client_plugin';
    var $class_name='client_plugin';
    
    function onInstall(){
	return true;

    }

	/*
             This function is called when the plugin is loaded
	*/

    function onLoad(){
		global $app;

		$app->plugins->registerEvent('client_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('client_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('client_delete',$this->plugin_name,'delete');
    }

    function insert($event_name,$data){
		global $app;
		$app->log('--== Insert User Plugin EVENT ==--');
		$rec=$app->dbmaster->queryOneRecord('SELECT name FROM sys_group WHERE `client_id`=\''.$data['new']['client_id'].'\'');
		$group_name=$rec['name'];
		exec('groupadd '.$group_name['name']);
		$u_home='/home/'.$data['new']['username'];
		exec('useradd -d '.$u_home.' -g '.$group_name.' -s /bin/false '.$data['new']['username'], $out);
		exec('mkdir '.$u_home.' '.$u_home.'/domains '.$u_home.'/imap');
		exec('chown -R '.$data['new']['username'].':'.$group_name.' '.$u_home);
    }

    function update($event_name,$data){
		global $app;
		
		$app->log('Update User EVENT');
		$rec=$app->dbmaster->queryOneRecord('SELECT name FROM sys_group WHERE `client_id`=\''.$data['new']['client_id'].'\'');
		$group_name=$rec['name'];
		exec('groupadd '.$group_name);
		$u_home='/home/'.$data['new']['username'];
		exec('useradd -d '.$u_home.' -g '.$group_name.' -s /bin/false '.$data['new']['username'], $out);
		if (!is_dir($u_home)){
	    	exec('mkdir '.$u_home.' '.$u_home.'/domains '.$u_home.'/imap');
	    	//exec('chown -R '.$data['new']['username'].':'.$group_name.' '.$u_home);
		} 
	
		exec('chown '.$data['new']['username'].':'.$group_name.' '.$u_home);
		exec('chown '.$data['new']['username'].':'.$group_name.' '.$u_home.'/domains');
		exec('chown '.$data['new']['username'].':mail '.$u_home.'/imap');
	
    }

    function delete($event_name,$data){
		global $app;
		$app->log('DELETE  User Plugin EVENT');
		// foreach ($data['old'] as $key=>$value) $app->log('New Data ---  '.$key.'='.$value); 
		// foreach ($data['new'] as $key=>$value) $app->log('New Data ---  '.$key.'='.$value);
    }


}

?>
<?php
	use WHMCS\Database\Capsule;
	use WHMCS\Session;
	if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
		require_once("../../init.php");
		$pdo = Capsule::connection()->getPdo();
			$row = $pdo->query("SELECT id FROM tblkilllogins WHERE id_login = '".$_GET["id"]."';")->fetch();
		$row2 = $pdo->query("SELECT name FROM tblemailtemplates WHERE message LIKE '%avisa_cliente_%';")->fetch();
		!isset($row['id'])&&(sha1($_GET['id'].$row2['name'])==$_GET['tk'])?$row = $pdo->query("INSERT INTO tblkilllogins(id_login) VALUES('".(int)$_GET['id']."')"):null;
		header("LOCATION: http://".$_SERVER['SERVER_NAME']);
	}
	else {
		function avisar_login($vars,$tp){
			$pdo = Capsule::connection()->getPdo();
			$pdo->beginTransaction();
				$pdo->query("CREATE TABLE IF NOT EXISTS `tblkilllogins` (`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,`id_login` int(11) UNIQUE NOT NULL,UNIQUE KEY `id` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
			$row = $pdo->query("SELECT username FROM tbladmins")->fetch();
			$row2 = $pdo->query("SELECT name FROM tblemailtemplates WHERE message LIKE '%avisa_cliente_%';")->fetch();
			(!isset($_SESSION['adminid'])||$tp=="a")&&isset($row2['name'])?localAPI($tp=="c"?"sendemail":"Send_Admin_Email", array("id"=>$vars['userid'],"messagename"=>$row2['name']), $row['username']):null;
		}
		add_hook("ClientLogin",1,function ($vars){avisar_login($vars,"c");});
		add_hook("AdminLogin",1,function($vars){avisar_login($vars,"a");});
		add_hook("EmailPreSend",1,function($vars){
			$pdo = Capsule::connection()->getPdo();
			$ip = !empty($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:!empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
			$rowcfg = $pdo->query("SELECT * FROM tblconfiguration WHERE setting = 'SystemURL';")->fetch();
			$ipapi = json_decode(file_get_contents("http://ip-api.com/json/".$ip));
			return array("avisa_cliente_ip"=>$ip,"avisa_cliente_provedor"=>isset($ipapi->as)?$ipapi->as:null,"avisa_cliente_local"=>(isset($ipapi->city)?$ipapi->city:null)." ".(isset($ipapi->country)?$ipapi->country:null),"avisa_cliente_data"=>date("d/m/Y H:i:s"),"avisa_cliente_linksair"=>$rowcfg['value']."includes/hooks/".basename(__FILE__)."?tk=".sha1($vars['relid'].$vars['messagename'])."&id=".$vars['relid']);
		});
		add_hook('ClientAreaFooterOutput', 1, function($vars) {
				$pdo = Capsule::connection()->getPdo();
				$pdo->query("CREATE TABLE IF NOT EXISTS `tblkilllogins` (`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,`id_login` int(11) UNIQUE NOT NULL,UNIQUE KEY `id` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
				$row = $pdo->query("SELECT id FROM tblkilllogins WHERE id_login = '".$_SESSION["uid"]."';")->fetch();
				if(isset($row['id'])){
					$pdo->query("DELETE FROM tblkilllogins WHERE id_login = '".$_SESSION["uid"]."';");
					header("LOCATION: logout.php");
				}
		});
	}

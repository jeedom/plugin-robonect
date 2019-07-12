<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes**********************************/
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class robonect extends eqLogic {
	/***************************Attributs*******************************/
	public static $_widgetPossibility = array('custom' => true);

	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('robonect', 'pull');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('robonect', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		$cron = cron::byClassAndFunction('robonect', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->halt();
	}

	public static function pull() {
		$eqLogics = eqLogic::byType('robonect', true);
		foreach ($eqLogics as $robonect) {
			$id= $robonect->getId();
			$currentstate = $robonect->getCmd(null, 'statusnum')->execCmd();
			if (!in_array($currentstate,array(16,17))){
				try {
					$state = $robonect->getrobonectInfo();
				} catch (Exception $e) {
					$state = 99;
				}
			} else {
				log::add('robonect','debug','Sleeping or Off ignoring refresh until change');
				$state = $currentstate;
			}
			if (in_array($state, array(2,3,5,99))){
				$counter = $robonect->getConfiguration('timemowing',15);
			} else if (in_array($state,array(4,7,8))){
				$counter = $robonect->getConfiguration('timecharging',120);
			} else {
				$counter = $robonect->getConfiguration('timedefault',600);
			}
			$i = 0;
			while ($i < $counter) {
				if ($robonect->getCache('daemonTimerReset',0) == 1) {
					log::add('robonect','debug','Sortie de while car push de robonect');
					$robonect->setCache('daemonTimerReset',0);
					break;
				}
				sleep(1);
				$i++;
			}
		}
	}

	public static function cronModeHandler($_params) {
		$id = $_params['robonect_id'];
		$eqLogic = eqLogic::byId($id);
		$cmd = $eqLogic->getCmd(null, $_params['mode']);
		$cmd->execCmd();
	}

	public static function convertDec($var) {
		$var = preg_replace('#([^.a-z0-9]+)#i', '-', $var);
		$tab = explode('-', $var);
		$varD = $tab[0] + ($tab[1] / 60) + ($tab[2] / 3600);
		$pattern = array('n', 's', 'e', 'o', 'N', 'S', 'E', 'O');
		$replace = array('', '-', '', '-', '', '-', '', '-');
		return str_replace($pattern, $replace, $tab[3]) . $varD;
	}

	public function getModeHuman()
    {
        return array(  	0 => 'Auto',
						1 => 'Manuel',
						2 => 'Maison',
						3 => 'Démo',
        );
    }

	public function getStatusHuman()
    {
        return array(  	0 => 'Parking',
						1 => 'Sur sa base',
						2 => 'En tonte',
						3 => 'Recherche sa base',
						4 => 'En charge',
						5 => 'En recherche',
						7 => 'En erreur',
						8 => 'Signal de boucle perdu',
						16 => 'Eteint',
						17 => 'Fait dodo',
        );
    }

	public function getMonthHuman()
    {
        return array(  	'01' => 'Janvier',
						'02' => 'Février',
						'03' => 'Mars',
						'04' => 'Avril',
						'05' => 'Mai',
						'06' => 'Juin',
						'07' => 'Juillet',
						'08' => 'Aôut',
						'09' => 'Septembre',
						'10' => 'Octobre',
						'11' => 'Novembre',
						'12' => 'Décembre',
        );
    }

	public function convertMonthHuman($_date)
    {
        $montharray = array(  	'January'	=>  'Janvier',
								'February'	=>  'Février',
								'March'   	=>  'Mars',
								'April'   	=>  'Avril',
								'May'     	=>  'Mai',
								'June'    	=>  'Juin',
								'July'    	=>  'Juillet',
								'August'  	=>  'Aôut',
								'September'	=>  'Septembre',
								'October' 	=>  'Octobre',
								'November'	=>  'Novembre',
								'December'	=>  'Décembre',
        );
		foreach ($montharray as $key=>$value) {
			$_date=str_replace($key,$value,$_date);
		}
		return $_date;
    }

	public function getTimerHuman()
    {
        return array(  	0 => 'Désactivé',
						1 => 'Actif',
						2 => 'Veille',
        );
    }

	public function getrobonectInfo() {
		try {
			$servip = $this->getConfiguration('servip','');
			$user = $this->getConfiguration('username','');
			$password = $this->getConfiguration('password','');
			$url = 'http://' . $servip . '/json?cmd=';
			if ($user !='' && $password !='') {
				$url = 'http://' . $servip . '/json?user='.$user.'&pass='.$password.'&cmd=';
			}
			$request_http = new com_http($url.'version');
			$robonectInfo=$request_http->exec(5,2);
			log::add('robonect','debug',$robonectInfo);
			$jsonrobonectInfo = json_decode($robonectInfo,true);
			sleep(1);
			$request_http_hour = new com_http($url.'hour');
			$robonectHour=$request_http_hour->exec(5,2);
			log::add('robonect','debug',$robonectHour);
			$jsonrobonectHour = json_decode($robonectHour,true);
			sleep(1);
			$request_http_status = new com_http($url.'status');
			$robonectStatus=$request_http_status->exec(5,2);
			$robonectStatus = mb_convert_encoding($robonectStatus,'UTF-8','ISO-8859-1');
			log::add('robonect','debug',$robonectStatus);
			$jsonrobonectStatus = json_decode($robonectStatus,true);
			sleep(1);
			$request_http_error = new com_http($url.'error');
			$robonectError=$request_http_error->exec(5,2);
			$robonectError = mb_convert_encoding($robonectError,'UTF-8','ISO-8859-1');
			log::add('robonect','debug',$robonectError);
			$jsonrobonectError = json_decode($robonectError,true);
			sleep(1);
			$request_http_motor = new com_http($url.'motor');
			$robonectMotor=$request_http_motor->exec(5,2);
			log::add('robonect','debug',$robonectMotor);
			$jsonrobonectMotor = json_decode($robonectMotor,true);
			sleep(1);
			$request_http_battery = new com_http($url.'battery');
			$robonectBattery=$request_http_battery->exec(5,2);
			log::add('robonect','debug',$robonectBattery);
			$jsonrobonectBattery = json_decode($robonectBattery,true);
			sleep(1);
			$request_http_gps = new com_http($url.'gps');
			$robonectGps=$request_http_gps->exec(5,2);
			$robonectGps = mb_convert_encoding($robonectGps,'UTF-8','ISO-8859-1');
			log::add('robonect','debug',$robonectGps);
			$jsonrobonectGps = json_decode($robonectGps,true);
			$change=0;
			if ($this->getConfiguration('model','') != $jsonrobonectInfo['mower']['msw']['title']) {
				$this->setConfiguration('model',$jsonrobonectInfo['mower']['msw']['title']);
				$change=1;
			}
			if ($this->getConfiguration('serial','') != $jsonrobonectInfo['serial']) {
				$this->setConfiguration('serial',$jsonrobonectInfo['serial']);
				$change=1;
			}
			if ($this->getConfiguration('firmware','') != $jsonrobonectInfo['application']['version'].'-'.$jsonrobonectInfo['application']['comment']) {
				$this->setConfiguration('firmware',$jsonrobonectInfo['application']['version'].'-'.$jsonrobonectInfo['application']['comment']);
				$change=1;
			}
			if ($this->getConfiguration('mswfirm') != $jsonrobonectInfo['mower']['msw']['version']. '-' . $jsonrobonectInfo['mower']['sub']['version']) {
				$this->setConfiguration('mswfirm',$jsonrobonectInfo['mower']['msw']['version']. '-' . $jsonrobonectInfo['mower']['sub']['version']);
				$change=1;
			}
			if ($this->getConfiguration('idrobonect') != $jsonrobonectStatus['id']) {
				$this->setConfiguration('idrobonect',$jsonrobonectStatus['id']);
				$change=1;
			}
			$return =99;
			foreach ($this->getCmd('info') as $robonectCmd) {
				$checkedCmd = 1;
				switch ($robonectCmd->getLogicalId()) {
					case 'batterie':
						$value = $jsonrobonectStatus['status']['battery'];
						if ($value != $this->getStatus('battery')) {
							$this->batteryStatus($value);
							$change = 1;
						}
						break;
					case 'mode':
						$value = $this->getModeHuman()[$jsonrobonectStatus['status']['mode']];
						break;
					case 'status':
						$value = $this->getStatusHuman()[$jsonrobonectStatus['status']['status']];
						break;
					case 'modenum':
						$value = $jsonrobonectStatus['status']['mode'];
						break;
					case 'statusnum':
						$value = $jsonrobonectStatus['status']['status'];
						$return = $value;
						break;
					case 'statesince':
						$value = $jsonrobonectStatus['status']['duration'];
						break;
					case 'distance':
						$value = $jsonrobonectStatus['status']['distance'];
						break;
					case 'stopped':
						$value = $jsonrobonectStatus['status']['stopped'];
						break;
					case 'wlansignal':
						$value = $jsonrobonectStatus['wlan']['signal'];
						break;
					case 'temperature':
						$value = $jsonrobonectStatus['health']['temperature'];
						break;
					case 'humidity':
						$value = $jsonrobonectStatus['health']['humidity'];
						break;
					case 'error':
						if (isset($jsonrobonectStatus['error'])) {
						$value = $jsonrobonectStatus['error']['error_code'];
						} else {
							$value = 0;
						}
						break;
					case 'errorhuman':
						if (isset($jsonrobonectStatus['error'])) {
						$value = $jsonrobonectStatus['error']['error_message'];
						} else {
							$value = 'Aucune';
						}
						break;
					case 'robottime':
						$value = date('H:i:s',strtotime($jsonrobonectStatus['clock']['time']));
						break;
					case 'robotdate':
						$value =  $this->convertMonthHuman(date('d M Y',strtotime($jsonrobonectStatus['clock']['date'])));
						break;
					case 'lame':
						$value = $jsonrobonectStatus['blades']['quality'];
						break;
					case 'lametime':
						$value = $jsonrobonectStatus['blades']['hours'];
						break;
					case 'lameage':
						$value = $jsonrobonectStatus['blades']['days'];
						break;
					case 'timerstatus':
						$value = $jsonrobonectStatus['timer']['status'];
						break;
					case 'timerstatushuman':
						$value =  $this->getTimerHuman()[$jsonrobonectStatus['timer']['status']];
						break;
					case 'timernextday':
						if (isset($jsonrobonectStatus['timer']['next'])) {
							$value =   $this->convertMonthHuman(date('d M',strtotime($jsonrobonectStatus['timer']['next']['date'])));
						} else {
							$value = 'Inconnue';
						}
						break;
					case 'timernexttime':
						if (isset($jsonrobonectStatus['timer']['next'])) {
							$value =  date('H:i',strtotime($jsonrobonectStatus['timer']['next']['time']));
						} else {
							$value = 'Inconnue';
						}
						break;
					case 'leftwheelspeed':
						$value = $jsonrobonectMotor['drive']['left']['speed']/10;
						break;
					case 'rightwheelspeed':
						$value = $jsonrobonectMotor['drive']['right']['speed']/10;
						break;
					case 'bladespeed':
						$value = $jsonrobonectMotor['blade']['speed'];
						break;
					case 'batteryvoltage':
						$value = $jsonrobonectBattery['batteries'][0]['voltage']/1000;
						break;
					case 'batterytemperature':
						$value = $jsonrobonectBattery['batteries'][0]['temperature']/10;
						break;
					case 'batterycapacity':
						$value = $jsonrobonectBattery['batteries'][0]['capacity']['remaining'];
						break;
					case 'totalrun':
						$value = $jsonrobonectHour['general']['run'];
						break;
					case 'totalmow':
						$value = $jsonrobonectHour['general']['mow'];
						break;
					case 'totalsearch':
						$value = $jsonrobonectHour['general']['search'];
						break;
					case 'totalcharge':
						$value = $jsonrobonectHour['general']['charge'];
						break;
					case 'numbercharges':
						$value = $jsonrobonectHour['general']['charges'];
						break;
					case 'totalerrors':
						$value = $jsonrobonectHour['general']['errors'];
						break;
					case 'lastsearch':
						$value = $jsonrobonectHour['seek'][0]['duration'];
						break;
					case 'lastmowing':
						$value = $jsonrobonectHour['mowing'][0]['duration'];
						break;
					case 'moymowing':
						$value = 0;
						$i = 0;
						foreach ($jsonrobonectHour['mowing'] as $mowing){
							$value += $mowing['duration'];
							$i++;
						}
						$value = $value/$i;
						break;
					case 'moysearch':
						$value = 0;
						$i = 0;
						foreach ($jsonrobonectHour['seek'] as $seek){
							$value += $seek['duration'];
							$i++;
						}
						$value = $value/$i;
						break;
					case 'gpssat':
						$value = $jsonrobonectGps['gps']['satellites'];
						break;
					case 'gpspos':
						if ($this->getConfiguration('ignorebaseposition') && $this->getConfiguration('baseposition','') != ''){
							if (in_array($jsonrobonectStatus['status']['status'],array(4,7,8))){
								log::add('robonect','debug','Sauvegarde de la position de la base');
								$value=$this->getConfiguration('baseposition','');
							} else {
								$value = self::convertDec($jsonrobonectGps['gps']['latitude']).','.self::convertDec($jsonrobonectGps['gps']['longitude']);
							}
						} else {
							$value = self::convertDec($jsonrobonectGps['gps']['latitude']).','.self::convertDec($jsonrobonectGps['gps']['longitude']);
						}
						break;
					default:
						$checkedCmd = 0;
				}
				if ($checkedCmd == 1) {
					$this->checkAndUpdateCmd($robonectCmd, $value);
				}
			}
			if ($change == 1) {
				$this->save();
			}
			$this->refreshWidget();
		} catch (Exception $e) {
			log::add('robonect','debug','Erreur lors de la récupération des infos ' . $e);
			$return = 99;
		}
		return $return;
	}

	public function getImage(){
		return 'plugins/robonect/plugin_info/robonect_icon.png';
	}

	public function preSave() {
		$this->setConfiguration('url',network::getNetworkAccess('internal') . '/plugins/robonect/core/php/Api.php?jid=' . $this->getId());
		$this->setConfiguration('battery_type', 'Batterie');
	}

	public function postSave() {
		$batterie = $this->getCmd(null, 'batterie');
		if (!is_object($batterie)) {
			$batterie = new robonectcmd();
			$batterie->setLogicalId('batterie');
			$batterie->setIsVisible(1);
			$batterie->setUnite('%');
			$batterie->setName(__('Batterie', __FILE__));
		}
		$batterie->setType('info');
		$batterie->setSubType('numeric');
		$batterie->setEqLogic_id($this->getId());
		$batterie->save();

		$mode = $this->getCmd(null, 'mode');
		if (!is_object($mode)) {
			$mode = new robonectcmd();
			$mode->setLogicalId('mode');
			$mode->setIsVisible(1);
			$mode->setName(__('Mode', __FILE__));
		}
		$mode->setType('info');
		$mode->setSubType('string');
		$mode->setEqLogic_id($this->getId());
		$mode->save();

		$status = $this->getCmd(null, 'status');
		if (!is_object($status)) {
			$status = new robonectcmd();
			$status->setLogicalId('status');
			$status->setIsVisible(1);
			$status->setName(__('Statut', __FILE__));
		}
		$status->setType('info');
		$status->setSubType('string');
		$status->setEqLogic_id($this->getId());
		$status->save();

		$modenum = $this->getCmd(null, 'modenum');
		if (!is_object($modenum)) {
			$modenum = new robonectcmd();
			$modenum->setLogicalId('modenum');
			$modenum->setIsVisible(1);
			$modenum->setName(__('Mode numérique', __FILE__));
		}
		$modenum->setType('info');
		$modenum->setSubType('numeric');
		$modenum->setEqLogic_id($this->getId());
		$modenum->save();

		$statusnum = $this->getCmd(null, 'statusnum');
		if (!is_object($statusnum)) {
			$statusnum = new robonectcmd();
			$statusnum->setLogicalId('statusnum');
			$statusnum->setIsVisible(1);
			$statusnum->setName(__('Statut numérique', __FILE__));
		}
		$statusnum->setType('info');
		$statusnum->setSubType('numeric');
		$statusnum->setEqLogic_id($this->getId());
		$statusnum->save();

		$statesince = $this->getCmd(null, 'statesince');
		if (!is_object($statesince)) {
			$statesince = new robonectcmd();
			$statesince->setLogicalId('statesince');
			$statesince->setIsVisible(1);
			$statesince->setName(__('Statut depuis', __FILE__));
		}
		$statesince->setType('info');
		$statesince->setSubType('numeric');
		$statesince->setEqLogic_id($this->getId());
		$statesince->save();

		$stopped = $this->getCmd(null, 'stopped');
		if (!is_object($stopped)) {
			$stopped = new robonectcmd();
			$stopped->setLogicalId('stopped');
			$stopped->setIsVisible(1);
			$stopped->setName(__('En marche', __FILE__));
		}
		$stopped->setType('info');
		$stopped->setSubType('binary');
		$stopped->setEqLogic_id($this->getId());
		$stopped->save();

		$wlansignal = $this->getCmd(null, 'wlansignal');
		if (!is_object($wlansignal)) {
			$wlansignal = new robonectcmd();
			$wlansignal->setLogicalId('wlansignal');
			$wlansignal->setIsVisible(1);
			$wlansignal->setUnite('dBm');
			$wlansignal->setName(__('Signal Wifi', __FILE__));
		}
		$wlansignal->setType('info');
		$wlansignal->setSubType('numeric');
		$wlansignal->setEqLogic_id($this->getId());
		$wlansignal->save();

		$temperature = $this->getCmd(null, 'temperature');
		if (!is_object($temperature)) {
			$temperature = new robonectcmd();
			$temperature->setLogicalId('temperature');
			$temperature->setIsVisible(1);
			$temperature->setUnite('°C');
			$temperature->setName(__('Température', __FILE__));
		}
		$temperature->setType('info');
		$temperature->setSubType('numeric');
		$temperature->setEqLogic_id($this->getId());
		$temperature->save();

		$humidity = $this->getCmd(null, 'humidity');
		if (!is_object($humidity)) {
			$humidity = new robonectcmd();
			$humidity->setLogicalId('humidity');
			$humidity->setIsVisible(1);
			$humidity->setUnite('%');
			$humidity->setName(__('Humidité', __FILE__));
		}
		$humidity->setType('info');
		$humidity->setSubType('numeric');
		$humidity->setEqLogic_id($this->getId());
		$humidity->save();

		$error = $this->getCmd(null, 'error');
		if (!is_object($error)) {
			$error = new robonectcmd();
			$error->setLogicalId('error');
			$error->setIsVisible(1);
			$error->setName(__('Code erreur', __FILE__));
		}
		$error->setType('info');
		$error->setSubType('numeric');
		$error->setEqLogic_id($this->getId());
		$error->save();

		$errorhuman = $this->getCmd(null, 'errorhuman');
		if (!is_object($errorhuman)) {
			$errorhuman = new robonectcmd();
			$errorhuman->setLogicalId('errorhuman');
			$errorhuman->setIsVisible(1);
			$errorhuman->setName(__('Message derreur', __FILE__));
		}
		$errorhuman->setType('info');
		$errorhuman->setSubType('string');
		$errorhuman->setEqLogic_id($this->getId());
		$errorhuman->save();

		$robottime = $this->getCmd(null, 'robottime');
		if (!is_object($robottime)) {
			$robottime = new robonectcmd();
			$robottime->setLogicalId('robottime');
			$robottime->setIsVisible(1);
			$robottime->setName(__('Heure du robot', __FILE__));
		}
		$robottime->setType('info');
		$robottime->setSubType('string');
		$robottime->setEqLogic_id($this->getId());
		$robottime->save();

		$robotdate = $this->getCmd(null, 'robotdate');
		if (!is_object($robotdate)) {
			$robotdate = new robonectcmd();
			$robotdate->setLogicalId('robotdate');
			$robotdate->setIsVisible(1);
			$robotdate->setName(__('Date du robot', __FILE__));
		}
		$robotdate->setType('info');
		$robotdate->setSubType('string');
		$robotdate->setEqLogic_id($this->getId());
		$robotdate->save();

		$lame = $this->getCmd(null, 'lame');
		if (!is_object($lame)) {
			$lame = new robonectcmd();
			$lame->setLogicalId('lame');
			$lame->setIsVisible(1);
			$lame->setUnite('%');
			$lame->setName(__('Pourcentage des lames', __FILE__));
		}
		$lame->setType('info');
		$lame->setSubType('numeric');
		$lame->setEqLogic_id($this->getId());
		$lame->save();

		$lametime = $this->getCmd(null, 'lametime');
		if (!is_object($lametime)) {
			$lametime = new robonectcmd();
			$lametime->setLogicalId('lametime');
			$lametime->setIsVisible(1);
			$lametime->setUnite('h');
			$lametime->setName(__('Heures de fonctionnement des lames', __FILE__));
		}
		$lametime->setType('info');
		$lametime->setSubType('numeric');
		$lametime->setEqLogic_id($this->getId());
		$lametime->save();

		$lameage = $this->getCmd(null, 'lameage');
		if (!is_object($lameage)) {
			$lameage = new robonectcmd();
			$lameage->setLogicalId('lameage');
			$lameage->setIsVisible(1);
			$lameage->setUnite('j');
			$lameage->setName(__('Age en jour des lames', __FILE__));
		}
		$lameage->setType('info');
		$lameage->setSubType('numeric');
		$lameage->setEqLogic_id($this->getId());
		$lameage->save();

		$timerstatus = $this->getCmd(null, 'timerstatus');
		if (!is_object($timerstatus)) {
			$timerstatus = new robonectcmd();
			$timerstatus->setLogicalId('timerstatus');
			$timerstatus->setIsVisible(1);
			$timerstatus->setName(__('Statut Timer', __FILE__));
		}
		$timerstatus->setType('info');
		$timerstatus->setSubType('numeric');
		$timerstatus->setEqLogic_id($this->getId());
		$timerstatus->save();

		$timerstatushuman = $this->getCmd(null, 'timerstatushuman');
		if (!is_object($timerstatushuman)) {
			$timerstatushuman = new robonectcmd();
			$timerstatushuman->setLogicalId('timerstatushuman');
			$timerstatushuman->setIsVisible(1);
			$timerstatushuman->setName(__('Statut Timer texte', __FILE__));
		}
		$timerstatushuman->setType('info');
		$timerstatushuman->setSubType('string');
		$timerstatushuman->setEqLogic_id($this->getId());
		$timerstatushuman->save();

		$timernextday = $this->getCmd(null, 'timernextday');
		if (!is_object($timernextday)) {
			$timernextday = new robonectcmd();
			$timernextday->setLogicalId('timernextday');
			$timernextday->setIsVisible(1);
			$timernextday->setName(__('Jour prochaine Tonte', __FILE__));
		}
		$timernextday->setType('info');
		$timernextday->setSubType('string');
		$timernextday->setEqLogic_id($this->getId());
		$timernextday->save();

		$timernexttime = $this->getCmd(null, 'timernexttime');
		if (!is_object($timernexttime)) {
			$timernexttime = new robonectcmd();
			$timernexttime->setLogicalId('timernexttime');
			$timernexttime->setIsVisible(1);
			$timernexttime->setName(__('Heure prochaine Tonte', __FILE__));
		}
		$timernexttime->setType('info');
		$timernexttime->setSubType('string');
		$timernexttime->setEqLogic_id($this->getId());
		$timernexttime->save();

		$leftwheelspeed = $this->getCmd(null, 'leftwheelspeed');
		if (!is_object($leftwheelspeed)) {
			$leftwheelspeed = new robonectcmd();
			$leftwheelspeed->setLogicalId('leftwheelspeed');
			$leftwheelspeed->setIsVisible(1);
			$leftwheelspeed->setUnite('rpm');
			$leftwheelspeed->setName(__('Vitesse roue gauche', __FILE__));
		}
		$leftwheelspeed->setType('info');
		$leftwheelspeed->setSubType('numeric');
		$leftwheelspeed->setEqLogic_id($this->getId());
		$leftwheelspeed->save();

		$rightwheelspeed = $this->getCmd(null, 'rightwheelspeed');
		if (!is_object($rightwheelspeed)) {
			$rightwheelspeed = new robonectcmd();
			$rightwheelspeed->setLogicalId('rightwheelspeed');
			$rightwheelspeed->setIsVisible(1);
			$rightwheelspeed->setUnite('rpm');
			$rightwheelspeed->setName(__('Vitesse roue droite', __FILE__));
		}
		$rightwheelspeed->setType('info');
		$rightwheelspeed->setSubType('numeric');
		$rightwheelspeed->setEqLogic_id($this->getId());
		$rightwheelspeed->save();

		$bladespeed = $this->getCmd(null, 'bladespeed');
		if (!is_object($bladespeed)) {
			$bladespeed = new robonectcmd();
			$bladespeed->setLogicalId('bladespeed');
			$bladespeed->setIsVisible(1);
			$bladespeed->setUnite('rpm');
			$bladespeed->setName(__('Vitesse lame', __FILE__));
		}
		$bladespeed->setType('info');
		$bladespeed->setSubType('numeric');
		$bladespeed->setEqLogic_id($this->getId());
		$bladespeed->save();

		$batteryvoltage = $this->getCmd(null, 'batteryvoltage');
		if (!is_object($batteryvoltage)) {
			$batteryvoltage = new robonectcmd();
			$batteryvoltage->setLogicalId('batteryvoltage');
			$batteryvoltage->setIsVisible(1);
			$batteryvoltage->setUnite('V');
			$batteryvoltage->setName(__('Voltage Batterie', __FILE__));
		}
		$batteryvoltage->setType('info');
		$batteryvoltage->setSubType('numeric');
		$batteryvoltage->setEqLogic_id($this->getId());
		$batteryvoltage->save();

		$batterytemperature = $this->getCmd(null, 'batterytemperature');
		if (!is_object($batterytemperature)) {
			$batterytemperature = new robonectcmd();
			$batterytemperature->setLogicalId('batterytemperature');
			$batterytemperature->setIsVisible(1);
			$batterytemperature->setUnite('°C');
			$batterytemperature->setName(__('Température Batterie', __FILE__));
		}
		$batterytemperature->setType('info');
		$batterytemperature->setSubType('numeric');
		$batterytemperature->setEqLogic_id($this->getId());
		$batterytemperature->save();

		$batterycapacity = $this->getCmd(null, 'batterycapacity');
		if (!is_object($batterycapacity)) {
			$batterycapacity = new robonectcmd();
			$batterycapacity->setLogicalId('batterycapacity');
			$batterycapacity->setIsVisible(1);
			$batterycapacity->setUnite('mAh');
			$batterycapacity->setName(__('Capacité Batterie restante', __FILE__));
		}
		$batterycapacity->setType('info');
		$batterycapacity->setSubType('numeric');
		$batterycapacity->setEqLogic_id($this->getId());
		$batterycapacity->save();

		$distance = $this->getCmd(null, 'distance');
		if (!is_object($distance)) {
			$distance = new robonectcmd();
			$distance->setLogicalId('distance');
			$distance->setIsVisible(1);
			$distance->setUnite('m');
			$distance->setName(__('Départ à distance', __FILE__));
		}
		$distance->setType('info');
		$distance->setSubType('numeric');
		$distance->setEqLogic_id($this->getId());
		$distance->save();

		$totalrun = $this->getCmd(null, 'totalrun');
		if (!is_object($totalrun)) {
			$totalrun = new robonectcmd();
			$totalrun->setLogicalId('totalrun');
			$totalrun->setIsVisible(1);
			$totalrun->setUnite('h');
			$totalrun->setName(__('Durée totale de fonctionnement', __FILE__));
		}
		$totalrun->setType('info');
		$totalrun->setSubType('numeric');
		$totalrun->setEqLogic_id($this->getId());
		$totalrun->save();

		$totalmow = $this->getCmd(null, 'totalmow');
		if (!is_object($totalmow)) {
			$totalmow = new robonectcmd();
			$totalmow->setLogicalId('totalmow');
			$totalmow->setIsVisible(1);
			$totalmow->setUnite('h');
			$totalmow->setName(__('Durée totale de tonte', __FILE__));
		}
		$totalmow->setType('info');
		$totalmow->setSubType('numeric');
		$totalmow->setEqLogic_id($this->getId());
		$totalmow->save();

		$totalsearch = $this->getCmd(null, 'totalsearch');
		if (!is_object($totalsearch)) {
			$totalsearch = new robonectcmd();
			$totalsearch->setLogicalId('totalsearch');
			$totalsearch->setIsVisible(1);
			$totalsearch->setUnite('h');
			$totalsearch->setName(__('Durée totale de recherche base', __FILE__));
		}
		$totalsearch->setType('info');
		$totalsearch->setSubType('numeric');
		$totalsearch->setEqLogic_id($this->getId());
		$totalsearch->save();

		$totalcharge = $this->getCmd(null, 'totalcharge');
		if (!is_object($totalcharge)) {
			$totalcharge = new robonectcmd();
			$totalcharge->setLogicalId('totalcharge');
			$totalcharge->setIsVisible(1);
			$totalcharge->setUnite('h');
			$totalcharge->setName(__('Durée totale de recharge', __FILE__));
		}
		$totalcharge->setType('info');
		$totalcharge->setSubType('numeric');
		$totalcharge->setEqLogic_id($this->getId());
		$totalcharge->save();

		$numbercharges = $this->getCmd(null, 'numbercharges');
		if (!is_object($numbercharges)) {
			$numbercharges = new robonectcmd();
			$numbercharges->setLogicalId('numbercharges');
			$numbercharges->setIsVisible(1);
			$numbercharges->setName(__('Nombre de recharge', __FILE__));
		}
		$numbercharges->setType('info');
		$numbercharges->setSubType('numeric');
		$numbercharges->setEqLogic_id($this->getId());
		$numbercharges->save();

		$totalerrors = $this->getCmd(null, 'totalerrors');
		if (!is_object($totalerrors)) {
			$totalerrors = new robonectcmd();
			$totalerrors->setLogicalId('totalerrors');
			$totalerrors->setIsVisible(1);
			$totalerrors->setName(__('Nombre totale d\'erreur', __FILE__));
		}
		$totalerrors->setType('info');
		$totalerrors->setSubType('numeric');
		$totalerrors->setEqLogic_id($this->getId());
		$totalerrors->save();

		$lastsearch = $this->getCmd(null, 'lastsearch');
		if (!is_object($lastsearch)) {
			$lastsearch = new robonectcmd();
			$lastsearch->setLogicalId('lastsearch');
			$lastsearch->setIsVisible(1);
			$lastsearch->setUnite('min');
			$lastsearch->setName(__('Durée dernière recherche de base', __FILE__));
		}
		$lastsearch->setType('info');
		$lastsearch->setSubType('numeric');
		$lastsearch->setEqLogic_id($this->getId());
		$lastsearch->save();

		$lastmowing = $this->getCmd(null, 'lastmowing');
		if (!is_object($lastmowing)) {
			$lastmowing = new robonectcmd();
			$lastmowing->setLogicalId('lastmowing');
			$lastmowing->setIsVisible(1);
			$lastmowing->setUnite('min');
			$lastmowing->setName(__('Durée dernière tonte', __FILE__));
		}
		$lastmowing->setType('info');
		$lastmowing->setSubType('numeric');
		$lastmowing->setEqLogic_id($this->getId());
		$lastmowing->save();

		$moymowing = $this->getCmd(null, 'moymowing');
		if (!is_object($moymowing)) {
			$moymowing = new robonectcmd();
			$moymowing->setLogicalId('moymowing');
			$moymowing->setIsVisible(1);
			$moymowing->setUnite('min');
			$moymowing->setName(__('Moyenne 20 dernières tontes', __FILE__));
		}
		$moymowing->setType('info');
		$moymowing->setSubType('numeric');
		$moymowing->setEqLogic_id($this->getId());
		$moymowing->save();

		$moysearch = $this->getCmd(null, 'moysearch');
		if (!is_object($moysearch)) {
			$moysearch = new robonectcmd();
			$moysearch->setLogicalId('moysearch');
			$moysearch->setIsVisible(1);
			$moysearch->setUnite('min');
			$moysearch->setName(__('Moyenne 20 dernières recherches de base', __FILE__));
		}
		$moysearch->setType('info');
		$moysearch->setSubType('numeric');
		$moysearch->setEqLogic_id($this->getId());
		$moysearch->save();

		$gpspos = $this->getCmd(null, 'gpspos');
		if (!is_object($gpspos)) {
			$gpspos = new robonectcmd();
			$gpspos->setLogicalId('gpspos');
			$gpspos->setIsVisible(1);
			$gpspos->setIsHistorized(1);
			$gpspos->setName(__('Position GPS', __FILE__));
		}
		$gpspos->setType('info');
		$gpspos->setSubType('string');
		$gpspos->setEqLogic_id($this->getId());
		$gpspos->save();

		$gpssat = $this->getCmd(null, 'gpssat');
		if (!is_object($gpssat)) {
			$gpssat = new robonectcmd();
			$gpssat->setLogicalId('gpssat');
			$gpssat->setIsVisible(1);
			$gpssat->setName(__('Nombre Satellites', __FILE__));
		}
		$gpssat->setType('info');
		$gpssat->setSubType('numeric');
		$gpssat->setEqLogic_id($this->getId());
		$gpssat->save();

		#ACTIONS
		$start = $this->getCmd(null, 'start');
		if (!is_object($start)) {
			$start = new robonectcmd();
			$start->setLogicalId('start');
			$start->setIsVisible(1);
			$start->setName(__('Start', __FILE__));
		}
		$start->setType('action');
		$start->setSubType('other');
		$start->setEqLogic_id($this->getId());
		$start->save();

		$stop = $this->getCmd(null, 'stop');
		if (!is_object($stop)) {
			$stop = new robonectcmd();
			$stop->setLogicalId('stop');
			$stop->setIsVisible(1);
			$stop->setName(__('Stop', __FILE__));
		}
		$stop->setType('action');
		$stop->setSubType('other');
		$stop->setEqLogic_id($this->getId());
		$stop->save();

		$modeAuto = $this->getCmd(null, 'modeAuto');
		if (!is_object($modeAuto)) {
			$modeAuto = new robonectcmd();
			$modeAuto->setLogicalId('modeAuto');
			$modeAuto->setIsVisible(1);
			$modeAuto->setName(__('Mode Auto', __FILE__));
		}
		$modeAuto->setType('action');
		$modeAuto->setSubType('other');
		$modeAuto->setEqLogic_id($this->getId());
		$modeAuto->save();

		$modeMan = $this->getCmd(null, 'modeMan');
		if (!is_object($modeMan)) {
			$modeMan = new robonectcmd();
			$modeMan->setLogicalId('modeMan');
			$modeMan->setIsVisible(1);
			$modeMan->setName(__('Mode Manuel', __FILE__));
		}
		$modeMan->setType('action');
		$modeMan->setSubType('other');
		$modeMan->setEqLogic_id($this->getId());
		$modeMan->save();

		$modeEod = $this->getCmd(null, 'modeEod');
		if (!is_object($modeEod)) {
			$modeEod = new robonectcmd();
			$modeEod->setLogicalId('modeEod');
			$modeEod->setIsVisible(1);
			$modeEod->setName(__('Mode Fin de Journée', __FILE__));
		}
		$modeEod->setType('action');
		$modeEod->setSubType('other');
		$modeEod->setEqLogic_id($this->getId());
		$modeEod->save();

		$modeHome = $this->getCmd(null, 'modeHome');
		if (!is_object($modeHome)) {
			$modeHome = new robonectcmd();
			$modeHome->setLogicalId('modeHome');
			$modeHome->setIsVisible(1);
			$modeHome->setName(__('Mode Maison', __FILE__));
		}
		$modeHome->setType('action');
		$modeHome->setSubType('other');
		$modeHome->setEqLogic_id($this->getId());
		$modeHome->save();

		$modeHomeTemp = $this->getCmd(null, 'modeHomeTemp');
		if (!is_object($modeHomeTemp)) {
			$modeHomeTemp = new robonectcmd();
			$modeHomeTemp->setLogicalId('modeHomeTemp');
			$modeHomeTemp->setIsVisible(1);
			$modeHomeTemp->setName(__('Mode Maison avec retour Auto au bout de x min', __FILE__));
		}
		$modeHomeTemp->setType('action');
		$modeHomeTemp->setSubType('slider');
		$modeHomeTemp->setEqLogic_id($this->getId());
		$modeHomeTemp->save();

		$job = $this->getCmd(null, 'job');
		if (!is_object($job)) {
			$job = new robonectcmd();
			$job->setLogicalId('job');
			$job->setIsVisible(1);
			$job->setName(__('Lancer un job', __FILE__));
		}
		$job->setType('action');
		$job->setSubType('message');
		$job->setDisplay('title_disable', 1);
		$job->setDisplay('message_placeholder', __('Lister vos options', __FILE__));
		$job->setEqLogic_id($this->getId());
		$job->save();

		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new robonectcmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraîchir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
	}

	public function postAjax() {
		self::deamon_stop();
		self::deamon_start();
	}

	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			if ($cmd->getIsHistorized() == 1) {
				$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
			}
			if ($cmd->getLogicalId() == 'statesince') {
				$replace['#depuis#']= round($cmd->execCmd()/60,1);
			}
		}
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}
		$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
		$replace['#apigoogle#'] = $this->getConfiguration('apiGoogle','');
		if (str_replace('px' ,'' ,$replace['#height#']) < 205) {
			$replace['#height#'] = '205px';
		}
		if (str_replace('px' ,'' ,$replace['#width#']) < 250) {
			$replace['#width#'] = '250px';
		}
		if (is_object($cron)) {
			$next=$cron->getSchedule();
			$replace['#next#'] = substr($next,3,2).':'.substr($next,0,2).' le '. substr($next,6,2).' '. $this->getMonthHuman()[substr($next,9,2)];
		} else {
			if ($this->getCmd(null, 'timernexttime')->execCmd() == 'Inconnue') {
				$replace['#next#'] = 'Aucun';
			} else {
				$replace['#next#'] = $this->getCmd(null, 'timernexttime')->execCmd() . ' le ' . $this->getCmd(null, 'timernextday')->execCmd();
			}
		}
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic', 'robonect')));
	}
}

class robonectCmd extends cmd {
	/***************************Attributs*******************************/


	/*************************Methode static****************************/

	/***********************Methode d'instance**************************/

	public function execute($_options = null) {
		if ($this->getType() == '') {
			return '';
		}
		$eqLogic = $this->getEqlogic();
		$servip = $eqLogic->getConfiguration('servip','');
		$user = $eqLogic->getConfiguration('username','');
		$password = $eqLogic->getConfiguration('password','');
		$url = 'http://' . $servip . '/json?cmd=';
		if ($user !='' && $password !='') {
			$url = 'http://' . $servip . '/json?user='.$user.'&pass='.$password.'&cmd=';
		}
		$logical = $this->getLogicalId();
		if ($logical != 'refresh'){
			switch ($logical) {
				case 'stop':
					$url.='stop';
				break;
				case 'start':
					$url.='start';
				break;
				case 'modeAuto':
					$url.='mode&mode=auto';
					$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
					if (is_object($cron)) {
						$cron->remove(false);
					}
				break;
				case 'modeEod':
					$url.='mode&mode=eod';
					$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
					if (is_object($cron)) {
						$cron->remove(false);
					}
				break;
				case 'modeMan':
					$url.='mode&mode=man';
					$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
					if (is_object($cron)) {
						$cron->remove(false);
					}
				break;
				case 'modeHome':
					$url.='mode&mode=home';
					$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
					if (is_object($cron)) {
						$cron->remove(false);
					}
				break;
				case 'modeHomeTemp':
					$url.='mode&mode=home';
					if (isset($_options['slider']) && $_options['slider'] != 0){
						$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
						if (is_object($cron)) {
							$cron->remove(false);
						}
						$cron = new cron();
						$cron->setClass('robonect');
						$cron->setFunction('cronModeHandler');
						$cron->setOption(array('robonect_id' => intval($eqLogic->getId()), 'mode' => 'modeAuto'));
						$cron->setLastRun(c);
						$cron->setOnce(1);
						$cron->setSchedule(cron::convertDateToCron(strtotime("now") + $_options['slider']*60));
						$cron->save();
						log::add('robonect','debug','Cron created to go to modeAuto after ' . $_options['slider'] . ' minutes '. date('Y-m-d H:i:s' ,strtotime("now") + $_options['slider']*60));
					}
				break;
				case 'job':
					$url.='mode&mode=job';
					if (is_object($cron)) {
						$cron->remove(false);
					}
					if (isset($_options['message']) && $_options['message'] != ''){
						log::add('robonect','debug',$_options['message']);
						$params = explode(' ',$_options['message']);
						$calculate ='';
						$fromCalculate ='';
						$duration=0;
						if (strpos($_options['message'], 'duration') !== false){
							$calculate = 'now';
							if (strpos($_options['message'], 'start') !== false && strpos($_options['message'], 'end') !== false){
								log::add('robonect','error','Vous ne pouvez pas passer duration et start et end en même temps comme paramètre');
								return;
							} else if(strpos($_options['message'], 'start') !== false){
								$calculate ='end';
							} else if(strpos($_options['message'], 'end') !== false){
								$calculate ='start';
							}
						}
						foreach ($params as $param) {
							$paramdetail = explode('=',$param);
							if ($paramdetail[0] == 'duration') {
								$duration = $paramdetail[1];
								continue;
							}
							if (($calculate == 'end' && $paramdetail[0]=='start') || ($calculate == 'start' && $paramdetail[0]=='end')){
									$fromCalculate = $paramdetail[1];
							}
							if ($paramdetail[0] == 'remote'){
								$url.='&remotestart=' . $paramdetail[1];
							} else {
								$url.='&'.$paramdetail[0] . '=' . $paramdetail[1];
							}
						}
						if ($calculate == 'start'){
							$start = date('H:i',strtotime($fromCalculate) - $duration*60);
							$url .= '&start='.$start;
						} else if ($calculate == 'end'){
							$end = date('H:i',strtotime($fromCalculate) + $duration*60);
							$url .= '&end='.$end;
						} else if ($calculate == 'now'){
							$end = date('H:i',strtotime(date('H:i')) + $duration*60);
							$url .= '&end='.$end;
						}
					}
				break;
			}
			log::add('robonect','debug','Executing : ' . $url);
			$request_http = new com_http($url);
			$result=$request_http->exec(5,2);
			log::add('robonect','debug',$result);
		}
		log::add('robonect','debug','refresh');
		$eqLogic->setCache('daemonTimerReset',1);
	}

	/************************Getteur Setteur****************************/
}
?>

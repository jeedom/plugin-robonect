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
			try {
				$state = $robonect->getrobonectInfo();
			} catch (Exception $e) {
				$state = 99;
			}
		}
		if (in_array($state, array(2,5,3,99))){
			sleep(15);
		} else{
			sleep(120);
		}
	}
	
	 private function getModeHuman()
    {
        return array(  	0 => 'Parking',
						1 => 'Manuel',
						2 => 'Accueil',
						3 => 'Démo',
        );
    }
	
	private function getStatusHuman()
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
	
	private function getTimerHuman()
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
			$robonectInfo=$request_http->exec();
			log::add('robonect','debug',$robonectInfo);
			$change=0;
			$jsonrobonectInfo = json_decode($robonectInfo,true);
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
			if ($change == 1) {
				$this->save();
			}
			$request_http = new com_http($url.'status');
			$robonectStatus=$request_http->exec();
			log::add('robonect','debug',$robonectStatus);
			$jsonrobonectStatus = json_decode($robonectStatus,true);
			$return =99;
			foreach ($this->getCmd('info') as $robonectCmd) {
				$checkedCmd = 1;
				switch ($robonectCmd->getLogicalId()) {
					case 'batterie':
						$value = $jsonrobonectStatus['status']['battery'];
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
					case 'totalhours':
						$value = $jsonrobonectStatus['status']['hours'];
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
						$value = $jsonrobonectStatus['error']['error_code'];
						break;
					case 'errorhuman':
						$value = $jsonrobonectStatus['error']['error_message'];
						break;
					case 'robottime':
						$value = $jsonrobonectStatus['clock']['time'];
						break;
					case 'robotdate':
						$value = $jsonrobonectStatus['clock']['date'];
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
							$value =  $jsonrobonectStatus['timer']['next']['date'];
						} else {
							$value = 'Inconnue';
						}
						break;
					case 'timernexttime':
						if (isset($jsonrobonectStatus['timer']['next'])) {
							$value =  $jsonrobonectStatus['timer']['next']['time'];
						} else {
							$value = 'Inconnue';
						}
						break;
					default:
						$checkedCmd = 0;
				}
				if ($checkedCmd == 1) {
					$this->checkAndUpdateCmd($robonectCmd, $value);
				}
			}
		} catch (Exception $e) {
			log::add('robonect','debug','Erreur lors de la récupération des infos');
			$return = 99;
		}
		return $return;
	}
	
	public function getImage(){
		return 'plugins/robonect/plugin_info/robonect_icon.png';
	}
	
	public function postSave() {
		$batterie = $this->getCmd(null, 'batterie');
		if (!is_object($batterie)) {
			$batterie = new robonectcmd();
			$batterie->setLogicalId('batterie');
			$batterie->setIsVisible(1);
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
		
		$totalhours = $this->getCmd(null, 'totalhours');
		if (!is_object($totalhours)) {
			$totalhours = new robonectcmd();
			$totalhours->setLogicalId('totalhours');
			$totalhours->setIsVisible(1);
			$totalhours->setName(__('Temps déxécution', __FILE__));
		}
		$totalhours->setType('info');
		$totalhours->setSubType('numeric');
		$totalhours->setEqLogic_id($this->getId());
		$totalhours->save();
		
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
		$logical = $this->getLogicalId();
		if ($logical != 'refresh'){
			switch ($logical) {
				case 'cancel':
					$request_http->setPost('{"command":"cancel"}');
				break;
				case 'pause':
					$request_http->setPost('{"command":"pause","action":"pause"}');
				break;
				case 'resume':
					$request_http->setPost('{"command":"pause","action":"resume"}');
				break;
				case 'shutdown':
					$urlprinter = 'http://' . $servip . '/api/system/commands/core/shutdown';
					$request_http = new com_http($urlprinter);
					$request_http->setHeader(array('Content-Type: application/json','X-Api-Key: '.$apikey));
				break;
				case 'reboot':
					$urlprinter = 'http://' . $servip . '/api/system/commands/core/restart';
					$request_http = new com_http($urlprinter);
					$request_http->setHeader(array('Content-Type: application/json','X-Api-Key: '.$apikey));
				break;
				case 'restart':
					$urlprinter = 'http://' . $servip . '/api/system/commands/core/restart';
					$request_http = new com_http($urlprinter);
					$request_http->setHeader(array('Content-Type: application/json','X-Api-Key: '.$apikey));
				break;
			}
			$result=$request_http->exec();
			log::add('robonect','debug',$result);
		}
		$status = $eqLogic->getrobonectInfo();
	}

	/************************Getteur Setteur****************************/
}
?>
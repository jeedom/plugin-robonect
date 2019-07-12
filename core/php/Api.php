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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
//if (!jeedom::apiAccess(init('apikey'), 'robonect')) {
	//echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (robonect)', __FILE__);
	//die();
//}
$id = init('jid');
http_response_code(200);
header("Content-Type: application/json");
$eqLogic = robonect::byId($id);
$content = file_get_contents('php://input');
log::add('robonect','debug',$content);
$status= init('status');
$signal= init('signal');
$stopped= init('stopped');
$mode= init('mode');
$battery= init('battery');
$duration= init('duration');
$hours= init('hours');
$distance= init('distance');
$statecmd = $eqLogic->getCmd(null, 'statusnum');
$stateLabelcmd = $eqLogic->getCmd(null, 'status');
$previousStateNum = $statecmd->execCmd();
$previousStateLabel = $stateLabelcmd->execCmd();
log::add('robonect','debug','Previous State is ' . $previousStateLabel);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'statusnum'), $status);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'status'), $eqLogic->getStatusHuman()[$status]);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'wlansignal'), $signal);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'stopped'), $stopped);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'modenum'), $mode);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'mode'), $eqLogic->getModeHuman()[$mode]);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'batterie'), $battery);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'statesince'), $duration);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'totalhours'), $hours);
$eqLogic->checkAndUpdateCmd($eqLogic->getCmd(null, 'distance'), $distance);
if ($battery != $eqLogic->getStatus('battery')) {
	$eqLogic->batteryStatus($battery);
	$eqLogic->save();
}
if ($status == 1 && $previousStateNum == 17){
	die();
}
$eqLogic->setCache('daemonTimerReset',1);
$eqLogic->refreshWidget();
die();

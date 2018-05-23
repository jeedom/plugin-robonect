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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
	
	if (init('action') == 'homeTemp') {
		$eqLogic = robonect::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Robonect eqLogic non trouvé : ', __FILE__) . init('id'));
		}
		if (init('type') == 'always') {
			$modeHome = $eqLogic->getCmd(null, 'modeHome');
			$modeHome->execCmd();
		} else if (init('type') == 'selection') {
			$modeHomeTemp = $eqLogic->getCmd(null, 'modeHomeTemp');
			$_options=array();
			$_options['slider'] = init('time');
			$modeHomeTemp->execCmd($_options);
		}
		ajax::success();
	}
	
	if (init('action') == 'jobTemp') {
		$eqLogic = robonect::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Robonect eqLogic non trouvé : ', __FILE__) . init('id'));
		}
		$modeJobTemp = $eqLogic->getCmd(null, 'job');
		$message ='remote='.init('remotestart').' after=' . init('afterwards');
		if (init('until') != 0){
				$message .= ' end=' . init('until');
		} else {
				$message .= ' duration=' . init('duration');
		}
		if (init('now') != 1){
			$message .= ' start=' . init('inTime');
		}
		$_options=array();
		$_options['message'] = $message;
		$modeJobTemp->execCmd($_options);
		ajax::success();
	}

	ajax::init();

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>

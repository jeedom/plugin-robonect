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


if (init('id') == '') {
    throw new Exception('{{L\'id de l\'équipement ne peut etre vide : }}' . init('op_id'));
}
$id = init('id');
sendVarToJS('hometempid', $id);
$robonect = robonect::byId($id);
if (!is_object($robonect)) {   
	 throw new Exception(__('Aucun equipement ne  correspond : Il faut (re)-enregistrer l\'équipement ', __FILE__) . init('action'));
}
?>
<div class="form-group">
	<div class="eventDisplay"></div>
</div>
<div class="alert alert-info">
     Que voulez-vous faire ?
</div>
<div>
     <span>Envoyer <?php echo $robonect->getHumanName(true); ?> à la maison pendant :</span></br></br>
	 <div class="form-group">
	 
	<div class="row">
		<div class="col-sm-3">
		<input class="form-control number" type="number"  min="0" max="60" step="1"/>
		</div>
		<div class="col-sm-3">
		<select class="form-control multiplier">
			<option value="1">{{minute(s)}}</option>
			<option value="60">{{heure(s)}}</option>
			<option value="1440">{{jour(s)}}</option>
			<option value="10080">{{semaine(s)}}</option>
			<option value="302400">{{mois (basé sur 30 jours)}}</option>
		</select>
		</div>
	
		</div>
		</div>
	<div class="form-group">
	<div class="row pull-right">
		<a class="btn btn-success selection"><i class="fa fa-clock-o"> </i>  Selon la sélection </a>
		<a class="btn btn-warning always"><i class="fa fa-home"> </i>  Jusqu'à nouvel ordre </a>
		<a class="btn btn-danger nothing"><i class="fa fa-times-circle"> </i>  Ne rien faire </a>
	</div>
	</div>
</div>
<div class="form-group">
<div class="row">
<?php
$cron = cron::byClassAndFunction('robonect', 'cronModeHandler');
if (is_object($cron)) {
	$next=$cron->getSchedule();
	$nicedate = substr($next,3,2).':'.substr($next,0,2).' '. substr($next,6,2).'/'.substr($next,9,2);
	echo '<div class="alert alert-danger">' . $robonect->getHumanName(true) . ' est déjà programmé pour rester dans sa base jusqu\'à : ' . $nicedate . ' !</div>';
} else {
	echo '<div class="alert alert-warning">Rien n\'est programmé dans Jeedom pour ' . $robonect->getHumanName(true) . ' !</div>';
}
?>
</div>
</div>
<script>
$('.nothing').on('click', function() {
	$('#md_modal2').dialog('close');
})
$('.selection').on('click', function() {
	if ($('.number').val()==''){
		$('.eventDisplay').showAlert({message:  'Vous devez saisir une durée !',level: 'danger'});
		setTimeout(function() { deleteAlert() }, 2000);
	} else {
		bootbox.dialog({
			title: 'Etes-vous sur ?',
			message: 'Vous allez envoyer le robot à sa base pour ' + $('.number').val() + ' ' + $('.multiplier option:selected').text() + ' ! Voulez-vous continuer ?',
			buttons: {
				"{{Annuler}}": {
					className: "btn-danger",
					callback: function () {
					}
				},
				success: {
					label: "{{Continuer}}",
					className: "btn-success",
					callback: function () {
						homeTemp(hometempid,'selection',$('.number').val()*$('.multiplier option:selected').val());
					}
				},
			}
		});
	}
})
$('.always').on('click', function() {
	homeTemp(hometempid,'always',0);
})

function homeTemp(_id,_type,_time) {
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/robonect/core/ajax/robonect.ajax.php", // url du fichier php
			data: {
				action: "homeTemp",
				id: _id,
				type: _type,
				time : _time
			},
			dataType: 'json',
			error: function(request, status, error) {
				handleAjaxError(request, status, error);
			},
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
				$('.eventDisplay').showAlert({message:  data.result,level: 'danger'});
				setTimeout(function() { deleteAlert() }, 2000);
                return;
            }
            modifyWithoutSave=false;
			$('#md_modal2').dialog('close');
        }
    });
}

function deleteAlert() {
	$('.eventDisplay').hideAlert();
}
</script>
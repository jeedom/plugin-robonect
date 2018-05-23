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
sendVarToJS('jobtempid', $id);
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
     <span>Envoyer <?php echo $robonect->getHumanName(true); ?> travailler :</span></br></br>
	 <div class="row">
	 <div class="form-group">
		<label class="col-sm-3 control-label">Choisir le type</label>
        <div class="col-sm-9">
		<form>
			<input type="radio" id="duringcheck" name="type" value="during" checked/>
			<label for="duringcheck">Pendant</label>
			<input type="radio" id="untilcheck" name="type" value="until"/>
			<label for="untilcheck">Jusqu'à</label>
		</form>
		</div>
	</div>
	<div class="form-group during">		
		<label class="col-sm-3 control-label">Pendant</label>
		<div class="col-sm-3">
		<input class="form-control number" type="number"  min="0" max="60" step="1"/>
		</div>
		<div class="col-sm-3">
		<select class="form-control multiplier">
			<option value="1">{{minute(s)}}</option>
			<option value="60">{{heure(s)}}</option>
		</select>
		</div>
	</div>
	<div class="form-group until" style="display:none;">
		<label class="col-sm-3 control-label">Jusqu'à</label>
		<div class="col-sm-3">
			<input class="form-control input-sm untilValue" type="time"/>
		</div>
	</div>
	</div>
	</br>
	<div class="row">
	 <div class="form-group">
		<label class="col-sm-3 control-label">En démarrant</label>
		<div class="col-sm-3">
		<select class="form-control remotestart">
			<option value="0">{{Normal}}</option>
			<option value="1">{{Depuis la base}}</option>
			<option value="2">{{Distance 1}}</option>
			<option value="3">{{Distance 2}}</option>
			<option value="4">{{Distance 3}}</option>
		</select>
		</div>
	</div>
	</div>
	</br>
	<div class="row">
	 <div class="form-group">
		<label class="col-sm-3 control-label">Ensuite</label>
		<div class="col-sm-3">
		<select class="form-control afterwards">
			<option value="1">{{Maison}}</option>
			<option value="2">{{Fin de journée}}</option>
			<option value="3">{{Manuel}}</option>
			<option value="4" selected="selected">{{Auto}}</option>
		</select>
		</div>
	</div>
	</div>
	</br>
	<div class="row">
	 <div class="form-group">
		<label class="col-sm-3 control-label">Départ</label>
        <div class="col-sm-9">
		<form>
			<input type="radio" id="now" name="when" value="now" checked/>
			<label for="now">Immédiatement</label>
			<input type="radio" id="in" name="when" value="in"/>
			<label for="in">A</label>
		</form>
		</div>
	</div>
	<div class="form-group in" style="display:none;">		
		<label class="col-sm-3 control-label">A</label>
		<div class="col-sm-3">
			<input class="form-control input-sm inTime" type="time"/>
		</div>
	</div>	
	</div>
	<div class="row pull-right">
		<a class="btn btn-success selection"><i class="fa fa-clock-o"> </i>  Selon la sélection </a>
		<a class="btn btn-danger nothing"><i class="fa fa-times-circle"> </i>  Ne rien faire </a>
	</div>
</div>
		
		
<script>
$('input[type=radio][name=type]').change(function() {
        if (this.value == 'during') {
			$('.until').hide();
			$('.during').show();
        }
        else if (this.value == 'until') {
			$('.until').show();
			$('.during').hide();
        }
    });
$('input[type=radio][name=when]').change(function() {
        if (this.value == 'now') {
			$('.in').hide();
        }
        else if (this.value == 'in') {
			$('.in').show();
        }
    });
$('.nothing').on('click', function() {
	$('#md_modal2').dialog('close');
})
$('.selection').on('click', function() {
	now =0;
	until=0;
	inTime=0;
	duration=0;
	type = $('input[type=radio][name=type]:checked').val();
	if (type == 'during'){
		if ($('.number').val()==''){
			$('.eventDisplay').showAlert({message:  'Vous devez saisir une durée !',level: 'danger'});
			setTimeout(function() { deleteAlert() }, 2000);
			return;
		} else {
			duration = $('.number').val()*$('.multiplier option:selected').val();
		}
	} else {
		until = $('.untilValue').val();
	}
	remotestart = $('.remotestart option:selected').val();
	afterwards = $('.afterwards option:selected').val();
	if ($('input[type=radio][name=when]:checked').val() == 'in'){
		inTime = $('.inTime').val();
	} else {
		now=1;
	}
	message = 'Vous allez envoyer le robot travailler ';
	if (type == 'during') {
		message = message + 'pendant ' + $('.number').val() + ' ' + $('.multiplier option:selected').text()+ ' ';
	} else {
		message = message + 'jusqu\'à ' + $('.untilValue').val()+ ' ';
	}
	if ($('input[type=radio][name=when]:checked').val() == 'in'){
		message = message + 'avec un départ à ' + inTime+'. ';
	} else {
		message = message + 'avec un départ immédiat. ';
	}
	message = message + 'Le robot partira de la zone ' +$('.remotestart option:selected').text() + ' ensuite il passera en mode ' +$('.afterwards option:selected').text()+ ' ! ';
	message = message + 'Voulez continuez ?';
	bootbox.dialog({
		title: 'Etes-vous sur ?',
		message: message,
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
					jobTemp(jobtempid,type,until,duration,remotestart,afterwards,now,inTime);
				}
			},
		}
	});
})

function jobTemp(_id,_type,_until,_duration,_remotestart,_afterwards,_now,_inTime) {
		$.ajax({// fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "plugins/robonect/core/ajax/robonect.ajax.php", // url du fichier php
			data: {
				action: "jobTemp",
				id: _id,
				type: _type,
				until : _until,
				duration : _duration,
				remotestart : _remotestart,
				afterwards : _afterwards,
				now : _now,
				inTime :_inTime
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
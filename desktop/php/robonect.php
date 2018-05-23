<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('robonect');
sendVarToJS('eqType', 'robonect');
$eqLogics = eqLogic::byType('robonect');
?>
<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
   <div class="eqLogicThumbnailContainer">
   <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
     <center>
      <i class="fa fa-plus-circle" style="font-size : 5em;color:#94ca02;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#94ca02"><center>Ajouter</center></span>
  </div>
  <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
    <center>
      <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
  </div>
  <div class="cursor" id="bt_healthrobonect" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
    <center>
      <i class="fa fa-medkit" style="font-size : 5em;color:#767676;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Santé}}</center></span>
  </div>
</div>
<legend><i class="fa fa-table"></i>  {{Mes Robonects}}</legend>
<div class="eqLogicThumbnailContainer">
         <?php
                foreach ($eqLogics as $eqLogic) {
					$model = strtolower($eqLogic->getConfiguration('model',''));
                    $opacity = '';
                    if ($eqLogic->getIsEnable() != 1) {
                        $opacity = 'opacity:0.3;';
                    }
					echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
					echo "<center>";
					if (file_exists(dirname(__FILE__) . '/../../core/config/' . $model . '.png')) {
						echo '<img class="lazy" src="plugins/robonect/core/config/' . $model . '.png" height="105" width="105" />';
					} else {
						echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="105" />';
					}
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
                ?>
            </div>  
</div>
<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
  <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
 <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>

    <ul class="nav nav-tabs" role="tablist">
		<li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
        <li role="presentation"><a href="#commandinfotab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes Infos}}</a></li>
        <li role="presentation"><a href="#commandactiontab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes Actions}}</a></li>
        <li role="presentation"><a href="#refreshtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-refresh"></i> {{Refreshs}}</a></li>
        <li role="presentation"><a href="#gpstab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-compass"></i> {{Gps}}</a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane active" id="eqlogictab">
		<div class="row">
    <div class="col-sm-6">
       <form class="form-horizontal">
            <fieldset>
                <div class="form-group">
					</br>
                    <label class="col-lg-3 control-label">{{Nom de l'équipement}}</label>
                    <div class="col-lg-4">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                    </div>
					
                </div>
                <div class="form-group">
                <label class="col-lg-3 control-label" >{{Objet parent}}</label>
                    <div class="col-lg-4">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (object::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
				<div class="form-group">
            <label class="col-sm-3 control-label">{{Catégorie}}</label>
            <div class="col-sm-9">
              <?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
	echo '<label class="checkbox-inline">';
	echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
	echo '</label>';
}
?>

           </div>
         </div>
                       <div class="form-group">
                    <label class="col-sm-3 control-label"></label>
                    <div class="col-sm-9">
                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                    </div>
                </div>
     <div class="form-group">
      <label class="col-sm-3 control-label">{{Ip du serveur}}</label>
      <div class="col-sm-6">
        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="servip" placeholder="{{Ip du serveur robonect}}"/>
      </div>
                </div>
				  <div class="form-group">
      <label class="col-sm-3 control-label">{{Username}}</label>
      <div class="col-sm-6">
        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" placeholder="{{Username si vous en avez un}}"/>
      </div>
                </div>
				  <div class="form-group">
	   <label class="col-sm-3 control-label">{{Mot de passe}}</label>
      <div class="col-sm-6">
        <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="{{Password du username}}"/>
      </div>
                </div>
				 <div class="form-group">
    <label class="col-lg-3 control-label">{{URL de retour}}</label>
    <div class="alert alert-warning col-lg-9">
        <span class="eqLogicAttr" data-l1key="configuration" data-l2key="url"></span>
    </div>
	</div>
            </fieldset>
        </form>
		</div>
<div class="col-sm-6">
<div class="form-group">
  <center>
    <img id="icon_visu" src="plugins/robonect/plugin_info/robonect_icon.png" style="height : 300px;margin-top:5px" />
  </center>
  </div>
  
       <form class="form-horizontal">
  <fieldset>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Modèle}}</label>
          <div class="col-sm-6">
            <span class="eqLogicAttr label label-info" style="font-size:1em;cursor: default;" data-l1key="configuration" data-l2key="model" onchange="$('#icon_visu').attr('src','/plugins/robonect/core/config/'+$(this).value().toLowerCase()+'.png')"></span>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Id}}</label>
          <div class="col-sm-6">
            <span class="eqLogicAttr label label-info" style="font-size:1em;cursor: default;" data-l1key="configuration" data-l2key="idrobonect"></span>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Firmware Robot}}</label>
          <div class="col-sm-6">
            <span class="eqLogicAttr label label-info" style="font-size:1em;cursor: default;" data-l1key="configuration" data-l2key="mswfirm"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label">{{Serial}}</label>
          <div class="col-sm-6">
            <span class="eqLogicAttr label label-info" style="font-size:1em;cursor: default;" data-l1key="configuration" data-l2key="serial"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label">{{Firmware Robonect}}</label>
          <div class="col-sm-6">
            <span class="eqLogicAttr label label-info" style="font-size:1em;cursor: default;" data-l1key="configuration" data-l2key="firmware"></span>
          </div>
        </div>
	</fieldset>
        </form>
</div>
</div>
</div>
<div role="tabpanel" class="tab-pane" id="commandinfotab">
        </br>
       <table id="table_cmd_info" class="table table-bordered table-condensed">
             <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
<div role="tabpanel" class="tab-pane" id="commandactiontab">
        </br>
       <table id="table_cmd_action" class="table table-bordered table-condensed">
             <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Action}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
<div role="tabpanel" class="tab-pane" id="refreshtab">
	</br>
	<form class="form-horizontal">
  <fieldset>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Durée de refresh lorsque le robot est en tonte (en secondes)}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timemowing" placeholder="{{15 par défaut}}"></input>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Durée de refresh lorsque le robot est en charge ou en erreur (en secondes)}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timecharging" placeholder="{{120 par défaut}}"></input>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Durée de refresh le reste du temps (en secondes)}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timedefault" placeholder="{{600 par défaut}}"></input>
          </div>
        </div>
	</fieldset>
        </form>
</div>
<div role="tabpanel" class="tab-pane" id="gpstab">
	</br>
	<form class="form-horizontal">
  <fieldset>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Coordonnées pour centrer la carte (votre maison)}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="centerMap" placeholder="{{Exemple : 45.8757,3.0558}}"></input>
          </div>
        </div>
		<div class="form-group">
			<label class="col-sm-3 control-label"><a href='https://developers.google.com/maps/documentation/geocoding/start#get-a-key' target="_blank">{{Clé API Google Maps Widget}}</a></label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="apiGoogle" placeholder="{{Votre clé Api Google Maps Widget}}"></input>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Ne pas sauver toutes les positions si en base}}</label>
          <div class="col-sm-3">
		  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="ignorebaseposition"/></label>
          </div>
        </div>
		<div class="form-group">
          <label class="col-sm-3 control-label">{{Coordonnées de la base (si option ne pas sauver activé)}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="baseposition" placeholder="{{Exemple : 45.8757,3.0558}}"></input>
          </div>
        </div>
	</fieldset>
        </form>
</div>
</div>
</div>
</div>

<?php include_file('desktop', 'robonect', 'js', 'robonect'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

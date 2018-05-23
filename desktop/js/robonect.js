
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

 $('#bt_healthrobonect').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé robonect}}"});
    $('#md_modal').load('index.php?v=d&plugin=robonect&modal=health').dialog('open');
});
 
 $("#table_cmd_info").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
 $("#table_cmd_action").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
	if (_cmd.type == 'info') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<div class="row">';
	tr += '<div class="col-sm-9">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
	tr += '</div>';
	tr += '</div>';
	tr += '<td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
	tr += '</td>';
	tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
	tr += '</td>';
    tr += '</tr>';
    $('#table_cmd_info tbody').append(tr);
    $('#table_cmd_info tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd_info tbody tr:last'), init(_cmd.subType));
	}
	if (_cmd.type == 'action') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<div class="row">';
	tr += '<div class="col-sm-9">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
	tr += '</div>';
	tr += '</div>';
	tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
	tr += '</td>';
    tr += '</tr>';
    $('#table_cmd_action tbody').append(tr);
    $('#table_cmd_action tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd_action tbody tr:last'), init(_cmd.subType));
	}
}
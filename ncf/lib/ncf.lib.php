<?php
/* Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    ncf/lib/ncf.lib.php
 * \ingroup ncf
 * \brief   Library files with common functions for NCF
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
// function ncfAdminPrepareHead()
// {
// 	global $langs, $conf;
//
// 	$langs->load("ncf@ncf");
//
// 	$h = 0;
// 	$head = array();
//
// 	$head[$h][0] = dol_buildpath("/ncf/admin/setup.php", 1);
// 	$head[$h][1] = $langs->trans("Settings");
// 	$head[$h][2] = 'settings';
// 	$h++;
//
// 	/*
// 	$head[$h][0] = dol_buildpath("/ncf/admin/myobject_extrafields.php", 1);
// 	$head[$h][1] = $langs->trans("ExtraFields");
// 	$head[$h][2] = 'myobject_extrafields';
// 	$h++;
// 	*/
//
// 	$head[$h][0] = dol_buildpath("/ncf/admin/about.php", 1);
// 	$head[$h][1] = $langs->trans("About");
// 	$head[$h][2] = 'about';
// 	$h++;
//
// 	// Show more tabs from modules
// 	// Entries must be declared in modules descriptor with line
// 	//$this->tabs = array(
// 	//	'entity:+tabname:Title:@ncf:/ncf/mypage.php?id=__ID__'
// 	//); // to add new tab
// 	//$this->tabs = array(
// 	//	'entity:-tabname:Title:@ncf:/ncf/mypage.php?id=__ID__'
// 	//); // to remove a tab
// 	complete_head_from_modules($conf, $langs, null, $head, $h, 'ncf');
//
// 	return $head;
// }

//dol_include_once('/ncf/class/comprobantes.class.php');
require_once DOL_DOCUMENT_ROOT.'/ncf/class/comprobantes.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

function ComprobantesDataTxt($object){
	 global $db, $langs, $conf, $user;
	 $obj = new Comprobantes($db);
	 $dir_ = DOL_DATA_ROOT.'/ncf/comprobantes/'.$object->ref.'/'.$object->ref.'/';
	 $zip = DOL_DATA_ROOT.'/ncf/comprobantes/'.$object->ref.'/'.$object->ref.'.zip';
	 $err = false;

	 if (!dol_is_dir($dir_)) dol_mkdir($dir_);
	 if (dol_is_dir($dir_)){

			 $sql = '
					 SELECT * FROM(
					 SELECT t.c_n_comprobante AS num, t.c_sec_ntf AS sec, t.tms as tms FROM '.MAIN_DB_PREFIX.'facture_extrafields as t
					 WHERE t.c_fk_comprobante='.$object->id.'
					 UNION
					 SELECT b.c_n_comprobante AS num, b.c_sec_ntf AS sec, b.tms as tms from '.MAIN_DB_PREFIX.'facture_fourn_extrafields as b
					 WHERE b.c_fk_comprobante='.$object->id.'
					 ) AS t
					 ORDER BY num ASC
			 ';

			 $res = $object->sql_r($sql);
			 $file_ = fopen($dir_.$object->ref.'.txt', 'w+');

			 foreach ($res as $r) {
					 $txt = $r->tms.'|'.$r->sec.'|'.$r->num;
					 fwrite($file_, $txt . PHP_EOL);
			 }

			 fclose($file_);



	 } else $err = true;

	 if (!$err) {
			 if (dol_is_file($zip)) dol_delete_file($zip);
			 dol_compress_file($dir_, $zip,'zip');
	 }

	 if (!$err) {
			 if (dol_is_dir($dir_)) dol_delete_dir_recursive($dir_);
	 }

}

function ComprobantesPdfHead($id = 0)
{
	global $db, $langs, $conf, $user;
	$obj = new Comprobantes($db);
	$ex = explode('/', $_SERVER['REQUEST_URI'] ?? '');
	if ((in_array('facture', $ex) && (in_array('fourn', $ex)) || in_array('compta', $ex))) {
		$table = (in_array('compta', $ex)) ? 'facture' : 'facture_fourn';
		$rows_t = 'fe.c_sec_ntf AS sec, c.tipo_comprobante as label';
		$sql = 'SELECT '.$rows_t;
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table.'_extrafields AS fe';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.$table.' AS tt ON tt.rowid=fe.fk_object';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ncf_comprobantes AS c ON c.rowid=fe.c_fk_comprobante';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_tipos_comprobante AS b ON b.code=c.tipo_comprobante';
		$sql .= ' WHERE tt.rowid='.((int) $id);
		$rel = $obj->sql_r($sql);

		if (!empty($rel)) {
			foreach ($rel as $r) {
				if (!isset($r->label) || $r->label == '') $r->label = '---';
				if (!isset($r->sec) || $r->sec == '') $r->sec = '---';
			}
			return $rel[0];
		}
	}

	return 0;
}

function ComprobantesPdfIvaRetencion($id = 0)
{
	global $db;
	$obj = new Comprobantes($db);
	$ex = explode('/', $_SERVER['REQUEST_URI'] ?? '');

	if ((in_array('facture', $ex) && (in_array('fourn', $ex)) || in_array('compta', $ex))) {
		$table = (in_array('compta', $ex)) ? 'facture' : 'facture_fourn';
		$labels = 'IF(f.c_retencion=1, p.percent, "no") AS itebis, IF(f.c_retencion=1, b.percent, "no") AS base, p.label AS itebislabel, b.label AS baselabel';

		$sql = 'SELECT '.$labels;
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table.'_extrafields as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_tipos_retencion_itebis AS p ON p.rowid=f.c_fk_rten_itebis';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_tipos_retencion_base AS b ON b.rowid=f.c_fk_rten_base';
		$sql .= ' WHERE fk_object='.((int) $id);
		$rel = $obj->sql_r($sql);

		if (!empty($rel)) {
			foreach ($rel as $r) {
				if (!isset($r->itebis) || $r->itebis == '') $r->itebis = 'no';
				if (!isset($r->base) || $r->base == '') $r->base = 'no';
			}
			return $rel[0];
		}
	}

	return 0;
}

function ComprobantesCreateFields(){
	 global $db, $langs, $conf, $user;
	 $obj = new Comprobantes($db);
	 $tables = Array(MAIN_DB_PREFIX.'facture_extrafields', MAIN_DB_PREFIX.'facture_fourn_extrafields');
	 $rel = $obj->sql_r('SHOW COLUMNS FROM '.$tables[0].' WHERE Field = "c_fk_comprobante"');

	 if (!isset($rel[0]->Field)) {
			 return ComprobanteCreateExtraFieldsFactures();
	 }

	 return 0;
}

function ComprobantesProveedores(){

	 global $db, $langs, $conf, $user;

	 if ($conf->ncf->enabled && $user->rights->ncf->facturasprov->write){
			 $obj = new Comprobantes($db);
			 $rel = $obj->sql_result('entrepot', 'rowid AS id, ref AS label', 'statut=1');
			 $rel2 = $obj->sql_result('c_tipos_retencion_itebis', 'rowid AS id, label', 'active=1');
			 $rel4 = $obj->sql_result('c_tipos_retencion_base', 'rowid AS id, label', 'active=1');
			 $data = getComprobanteNextNumber();
			 $rel3 = [
					 (object)['id'=>'1', 'label'=>'Automático'],
					 (object)['id'=>'2', 'label'=>'Manual']
			 ];

			 getComprobanteAttrHtml('', 2, 'es_comprobante', '¿Es factura NCF?', 2);
			 getComprobanteAttrHtml(1, 2, 'c_tipo_sec', 'Modo de selección comprobante', 3, '', '', $rel3);
			 getComprobanteAttrHtml(1, 2, 'c_fk_comprobante', 'Comprobante', 3, '', '', $data);
			 getComprobanteAttrHtml(1, 2, 'c_sec_ntf', 'NCF', 1, 'minwidth200', 20);
			 getComprobanteAttrHtml(1, 2, 'c_fk_almacen', 'Almacen', 3, '', '', $rel);
			 getComprobanteAttrHtml(1, 2, 'c_retencion', '¿Aplica retención?', 2);
			 getComprobanteAttrHtml(1, 2, 'c_fk_rten_base', 'Retención sobre base imponible', 3, '', '', $rel4);
			 getComprobanteAttrHtml(1, 2, 'c_fk_rten_itebis', 'Retención sobre impuestos', 3, '', '', $rel2);
			 getComprobanteAttrHtml(1, 2, 'c_itebis', '¿ITEBIS es llevado al costo?', 2);
			 getComprobanteAttrHtml(1, 2, 'c_propor', '¿Aplica proporcionalidad?', 2);
			 getComprobanteAttrHtml(1, 2, 'c_n_comprobante', '', 1, 'maxwidth75',5);

			 print '
			 <script type="text/javascript">
					 $(document).ready(function()
					 {
							 let classattr = "";
							 $("#options_es_comprobante").click(function() {
									 if($("#options_es_comprobante").is(":checked")) {
											 $(".invoice_supplier_extras_c_retencion").show();
											 $(".invoice_supplier_extras_c_itebis").show();
											 $(".invoice_supplier_extras_c_propor").show();
											 $(".invoice_supplier_extras_c_fk_almacen").show();
											 $(".invoice_supplier_extras_c_tipo_sec").show();

											 classattr = `compt-cli-${$("#options_c_fk_comprobante").val()}`;
											 console.log(classattr);
											 $("#options_c_n_comprobante").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-num"));
											 $("#options_c_sec_ntf").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-code"));
											 $("input[name=label]").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-sublabel"));
									 } else {
											 $(".invoice_supplier_extras_c_retencion").hide();
											 $(".invoice_supplier_extras_c_itebis").hide();
											 $(".invoice_supplier_extras_c_propor").hide();
											 $(".invoice_supplier_extras_c_fk_almacen").hide();
											 $(".invoice_supplier_extras_c_fk_comprobante").hide();
											 $(".invoice_supplier_extras_c_fk_rten_itebis").hide();
											 $(".invoice_supplier_extras_c_fk_rten_base").hide();
											 $(".invoice_supplier_extras_c_tipo_sec").hide();

											 classattr = "";
											 $("#options_c_n_comprobante").val("");
											 $("#options_c_sec_ntf").val("");
											 $("input[name=label]").val("");

											 $("#options_c_retencion").prop("checked", false);
											 $("#options_c_itebis").prop("checked", false);
											 $("#options_c_propor").prop("checked", false);
									 }
							 });

							 $("#options_c_fk_comprobante").change(function() {
									 classattr = `compt-cli-${$("#options_c_fk_comprobante").val()}`;
									 console.log(classattr);
									 $("#options_c_n_comprobante").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-num"));
									 $("#options_c_sec_ntf").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-code"));
									 $("input[name=label]").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-sublabel"));
									 $(".invoice_supplier_extras_c_sec_ntf").show();
							 });

							 $("#options_c_retencion").click(function() {
									 if($("#options_c_retencion").is(":checked")) {
											 $(".invoice_supplier_extras_c_fk_rten_itebis").show();
											 $(".invoice_supplier_extras_c_fk_rten_base").show();
									 } else {
											 $(".invoice_supplier_extras_c_fk_rten_itebis").hide();
											 $(".invoice_supplier_extras_c_fk_rten_base").hide();
									 }
							 });

							 $("#options_c_tipo_sec").change(function() {
									 let optts = $("#options_c_tipo_sec").val();
									 if (optts == 2){
											 $(".invoice_supplier_extras_c_fk_comprobante").hide();
											 $(".invoice_supplier_extras_c_sec_ntf").show();
									 }else{
											 $(".invoice_supplier_extras_c_fk_comprobante").show();
											 $(".invoice_supplier_extras_c_sec_ntf").hide();
									 }
							 });

					 });
			 </script>

			 ';
	 }
}

function ComprobantesClientes(){

	global $db, $langs, $conf, $user;

	if ($conf->ncf->enabled && $user->rights->ncf->facturasprov->write){
			$obj = new Comprobantes($db);
			$rel = $obj->sql_result('entrepot', 'rowid AS id, ref AS label', 'statut=1');
			$rel2 = $obj->sql_result('c_tipos_retencion_itebis', 'rowid AS id, label', 'active=1');
			$rel4 = $obj->sql_result('c_tipos_retencion_base', 'rowid AS id, label', 'active=1');
			$data = getComprobanteNextNumber();
			$rel3 = [
					(object)['id'=>'1', 'label'=>'Automático'],
					(object)['id'=>'2', 'label'=>'Manual']
			];

			getComprobanteAttrHtml('', 1, 'es_comprobante', '¿Es factura NCF?', 2);
			getComprobanteAttrHtml(1, 1, 'c_tipo_sec', 'Modo de selección comprobante', 3, '', '', $rel3);
			getComprobanteAttrHtml(1, 1, 'c_fk_comprobante', 'Comprobante', 3, '', '', $data);
			getComprobanteAttrHtml(1, 1, 'c_sec_ntf', 'NCF', 1, 'minwidth200', 20);
			getComprobanteAttrHtml(1, 1, 'c_fk_almacen', 'Almacen', 3, '', '', $rel);
			getComprobanteAttrHtml(1, 1, 'c_retencion', '¿Aplica retención?', 2);
			getComprobanteAttrHtml(1, 1, 'c_fk_rten_base', 'Retención sobre base imponible', 3, '', '', $rel4);
			getComprobanteAttrHtml(1, 1, 'c_fk_rten_itebis', 'Retención sobre impuestos', 3, '', '', $rel2);
			getComprobanteAttrHtml(1, 1, 'c_itebis', '¿ITEBIS es llevado al costo?', 2);
			getComprobanteAttrHtml(1, 1, 'c_propor', '¿Aplica proporcionalidad?', 2);
			getComprobanteAttrHtml(1, 1, 'c_n_comprobante', '', 1, 'maxwidth75',5);

			print '
			<script type="text/javascript">
					$(document).ready(function()
					{
							let classattr = "";
							$("#options_es_comprobante").click(function() {
									if($("#options_es_comprobante").is(":checked")) {
										// console.log("clic");
											$(".facture_extras_c_retencion").show();
											$(".facture_extras_c_itebis").show();
											$(".facture_extras_c_propor").show();
											$(".facture_extras_c_fk_almacen").show();
											$(".facture_extras_c_sec_ntf").show();
											$(".facture_extras_c_fk_comprobante").show();

											classattr = `compt-cli-${$("#options_c_fk_comprobante").val()}`;
											console.log(classattr);
											$("#options_c_n_comprobante").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-num"));
											$("#options_c_sec_ntf").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-code"));
											$("input[name=label]").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-sublabel"));
									} else {
											$(".facture_extras_c_retencion").hide();
											$(".facture_extras_c_itebis").hide();
											$(".facture_extras_c_propor").hide();
											$(".facture_extras_c_fk_almacen").hide();
											$(".facture_extras_c_fk_comprobante").hide();
											$(".facture_extras_c_fk_rten_itebis").hide();
											$(".facture_extras_c_fk_rten_base").hide();
											$(".facture_extras_c_sec_ntf").hide();

											classattr = "";
											$("#options_c_n_comprobante").val("");
											$("#options_c_sec_ntf").val("");
											$("input[name=label]").val("");

											$("#options_c_retencion").prop("checked", false);
											$("#options_c_itebis").prop("checked", false);
											$("#options_c_propor").prop("checked", false);
									}
							});

							$("#options_c_fk_comprobante").change(function() {
									classattr = `compt-cli-${$("#options_c_fk_comprobante").val()}`;
									console.log(classattr);
									$("#options_c_n_comprobante").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-num"));
									$("#options_c_sec_ntf").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-code"));
									$("input[name=label]").val($("#options_c_fk_comprobante ."+classattr).attr("compt-cli-sublabel"));
									$(".facture_extras_c_sec_ntf").show();
							});

							$("#options_c_retencion").click(function() {
									if($("#options_c_retencion").is(":checked")) {
											$(".facture_extras_c_fk_rten_itebis").show();
											$(".facture_extras_c_fk_rten_base").show();
									} else {
											$(".facture_extras_c_fk_rten_itebis").hide();
											$(".facture_extras_c_fk_rten_base").hide();
									}
							});

					});
			</script>

			';
	}
}


function getComprobanteNextNumber()
{
	global $db, $langs, $conf;
	$obj = new Comprobantes($db);
	$now = date('Y-m-d', dol_now());

	$sql = 'SELECT * FROM ( SELECT id, IF(val > val2, val, val2) AS val, label, tipo_comprobante, n_inicial, n_final FROM ( SELECT c.rowid AS id, IF(MAX(t.c_n_comprobante) IS NULL, 0, ';
	$sql .= 'MAX(t.c_n_comprobante)) AS val, IF(MAX(e.c_n_comprobante) IS NULL, 0, MAX(e.c_n_comprobante)) AS val2, tc.sublabel AS label, c.tipo_comprobante, c.n_inicial, c.n_final FROM ';
	$sql .= MAIN_DB_PREFIX.'ncf_comprobantes AS c LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields AS t ON c.rowid=t.c_fk_comprobante LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_extrafields AS e ON c.rowid=e.c_fk_comprobante LEFT JOIN ';
	$sql .= MAIN_DB_PREFIX.'c_tipos_comprobante AS tc ON tc.code = c.tipo_comprobante WHERE "'.$now.'" < c.fecha_vencimiento GROUP BY c.rowid, tc.sublabel, c.tipo_comprobante, c.n_inicial, c.n_final) AS t ) AS t WHERE val < n_final';
	$rel = $obj->sql_r($sql);

	 $nprefix = 8;

	 $sel = Array();
	 foreach($rel AS $r){
			 if ($r->val < $r->n_inicial) $r->val = $r->n_inicial;
			 else $r->val += 1;

			 $sel[$r->id] = (Object) Array(
					 'id'=>$r->id,
					 'num'=>$r->val,
					 'label'=>''.$r->label.' ('.strtoupper($r->tipo_comprobante).sprintf("%08s", $r->val).')',
					 'code'=>strtoupper($r->tipo_comprobante).sprintf("%08s", $r->val),
					 'sublabel'=>$r->label
			 );
	 }

	 return $sel;
}

function getComprobanteAttrHtml($style, $tb, $id, $title, $nval, $css='', $maxlg='', $data=''){
	 $style = ($style === 1) ? 'style="display: none;"' : '';
	 $tb = ($tb === 1) ? 'facture' : 'invoice_supplier' ;
 print '<tr '.$style.' class="'.$tb.'_extras_'.$id.' trextrafields_collapse" data-element="extrafield" data-targetelement="'.$tb.'" data-targetid=""><td class="wordbreak">'.$title.'</td><td class="'.$tb.'_extras_'.$id.'" colspan="2">';
 switch($nval){
	 case 1:
		 print '<input type="text" class="flat '.$css.' maxwidthonsmartphone" name="options_'.$id.'" id="options_'.$id.'" maxlength="'.$maxlg.'" value="">';
		 break;
	 case 2:
		 print '<input type="checkbox" class="flat  maxwidthonsmartphone" name="options_'.$id.'" id="options_'.$id.'" value="1">';
		 break;
	 case 3:
		 getComprobanteNexthtmlSelection($data, $id);
		 break;
	 default:
	 print '';
 }

 print '</td></tr>';
}

function getComprobanteNexthtmlSelection($data, $htmlname='', $id=''){
	 print '<select id="options_'.$htmlname.'" class="flat minwidth100 minwidth200 minwidth300" name="options_'.$htmlname.'">';

	 //if (sizeof($data) == 0) {
			 print '<option value="">&nbsp;</option>';
	 //}

	 foreach($data as $r){
			 $value = '<option value="'.$r->id.'" ';
			 if (isset($r->num)) $value .= 'class="compt-cli-'.$r->id.'" compt-cli-num="'.$r->num.'" ';
			 if (isset($r->code)) $value .= 'compt-cli-code="'.$r->code.'" ';
			 if (isset($r->sublabel)) $value .= 'compt-cli-sublabel="'.$r->sublabel.'" ';
			 if ($r->id == $id) $value .= 'selected="selected"';
			 $value .= '>&nbsp;';
			 $value .= $r->label;
			 $value .= '</option>';
			 print $value;
	 }
	 print '</select>';
	 print ajax_combobox('options_'.$htmlname);
}

function ComprobantesPdfNcf($id = 0)
{
	global $db, $langs, $conf, $user;
	$obj = new Comprobantes($db);
	$ex = explode('/', $_SERVER['REQUEST_URI'] ?? '');

	if (in_array('facture', $ex) && (in_array('fourn', $ex) || in_array('compta', $ex))) {
		$table = (in_array('compta', $ex)) ? 'facture' : 'facture_fourn';
		$labels = 'f.c_sec_ntf AS ncf, c.sucursal AS suc, DATE_FORMAT(c.fecha_vencimiento, "%d/%m/%Y") AS fven';
		if ($table == 'facture_fourn')  $labels = 'IF(f.c_retencion=1, p.percent, "no") AS prop, f.c_sec_ntf AS ncf, c.sucursal AS suc, DATE_FORMAT(c.fecha_vencimiento, "%d/%m/%Y") AS fven';

		$sql = 'SELECT '.$labels;
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table.'_extrafields as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ncf_comprobantes as c ON c.rowid=f.c_fk_comprobante';
		if ($table == 'facture_fourn') {
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_tipos_retencion_itebis AS p ON p.rowid=f.c_fk_rten_itebis';
		}
		$sql .= ' WHERE fk_object='.((int) $id);

		$rel = $obj->sql_r($sql);

		if (!empty($rel)) {
			foreach ($rel as $r) {
				if (!isset($r->ncf)) $r->ncf = '---';
				if (!isset($r->suc)) $r->suc = '---';
				if (!isset($r->fven)) $r->fven = '---';
				if (!isset($r->prop)) $r->prop = '---';
			}
			return $rel[0];
		}
	}

	return 0;
}

function comprobantesUpdateTotalAmount($ob){
	 global $db, $langs, $conf, $user;
	 // $obj = new Comprobantes($db);
	 $ex = explode('/', $_SERVER['REQUEST_URI']);
	 if ( in_array('facture', $ex) && (in_array('fourn', $ex) || in_array('compta', $ex)) ){
			 $table = (in_array('compta', $ex)) ? MAIN_DB_PREFIX.'facture' : MAIN_DB_PREFIX.'facture_fourn';

			 $id = $ob->id;
			 $total_ht = round($ob->total_ht, 2);
			 $total_tva = round($ob->total_tva, 2);
			 $total = round($total_ht + $total_tva, 2);
			 $total_ttc = round($ob->total_ttc, 2);

			 $update = 1;
			 $ivabaseTemp = 0;
			 $ivaItebisTemp = 0;
			 // $ivaDecTemp = 0;
			 // $sumIvaTemp = 0;
			 if ($total_tva != 0){
					 $dataTemp = ComprobantesPdfIvaRetencion($id);
					 if ($dataTemp != 0){
							 $ivabaseTemp = ($dataTemp->base != 'no') ? ($total_ht / 100)*$dataTemp->base : 0;
							 $ivaItebisTemp = ($dataTemp->itebis != 'no') ? ($total_tva/ 100)*$dataTemp->itebis : 0;

							 $ivabaseTemp = round($ivabaseTemp, 2);
							 $ivaItebisTemp = round($ivaItebisTemp, 2);

							 $sumIvaTemp = $ivabaseTemp + $ivaItebisTemp;
							 $totalTtc = $total - $sumIvaTemp;
					 } else $update = 0;
			 } else $update = 0;

			 if ($update != 0){
					 $sql = '
					 UPDATE '.$table.'
					 SET total_ttc='.$totalTtc.', multicurrency_total_ttc='.$totalTtc.'
					 WHERE rowid='.$id.'
					 ';
					 $resql = $db->query($sql);
					 // $rel = $ob->sql_r($sql);
			 }
			 return Array (
					 'update' => $update,
					 'ivabaseTemp' => $ivabaseTemp,
					 'ivaItebisTemp' => $ivaItebisTemp,
					 // 'ivaDecTemp' => $ivaDecTemp,
					 // 'sumIvaTemp' => $sumIvaTemp,
					 'total_ht' => $total_ht,
					 'total_tva' => $total_tva,
					 'total' => $total,
					 'total_ttc' => $total_ttc
			 );

	 }
}

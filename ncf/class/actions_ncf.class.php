<?php
declare(strict_types=1);
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
 * \file    ncf/class/actions_ncf.class.php
 * \ingroup ncf
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsNCF
 */
class ActionsNCF
{
	/**
	 * @var DoliDB Database handler.
	 */
	public ?DoliDB $db = null;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		if (in_array($parameters['currentcontext'], array('invoicecard')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		{
			// if ($action == 'create') {
			// 	echo "<script>
			// 		jQuery( document ).ready(function() {
			// 			console.log('ready');
			// 		});
			// 	</script>";
			// }
		}
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		dol_include_once('/ncf/lib/ncf.lib.php');
		global $conf, $user, $langs;

		$error = 0; // Error counter

		//print_r($parameters); print_r($object); echo "action: " . $action;
		if (in_array($parameters['currentcontext'], array('invoicecard', 'invoicesuppliercard')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		{
			if ($action == 'create') {
				// ComprobantesClientes();
				ComprobantesProveedores();
			}
			if ($action != 'create') {
				$dataTemp = comprobantesUpdateTotalAmount($object);
			}
		}

		if (in_array($parameters['currentcontext'], array('invoicesuppliercard')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		{
			if ($action == 'create') {
				print '<script>
					jQuery( document ).ready(function() {
						var rows = $("tr");
						var ultimocampo = $(".invoice_supplier_extras_c_propor")[0];

						rows.find("td").each (function() {
							if($(this).is(\':contains("Nota (pública)")\')){
								var nota = $(this).parent()[0];
								ultimocampo.after(nota);
							}
							if($(this).is(\':contains("Nota (privada)")\')){
								var nota = $(this).parent()[0];
								ultimocampo.after(nota);
							}
						});
					});
				</script>';
			}
		}
	}

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		require_once DOL_DOCUMENT_ROOT.'/ncf/lib/ncf.lib.php';
		// dol_include_once('/ncf/lib/ncf_comprobantes.lib.php');

		$error = 0; // Error counter
		if (in_array($parameters['currentcontext'], array('invoicecard', 'invoicesuppliercard')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{
			$dataTemp = comprobantesUpdateTotalAmount($object);
			// echo "string2";
			$ivabaseTemp = ($dataTemp['ivabaseTemp'] != 0) ? price($dataTemp['ivabaseTemp'], 1, $langs, 0, -1, -1, $conf->currency): 0;
			$ivaItebisTemp = ($dataTemp['ivaItebisTemp'] != 0) ? price($dataTemp['ivaItebisTemp'], 1, $langs, 0, -1, -1, $conf->currency): 0;
			$ivabaseTemp = ($dataTemp['ivabaseTemp'] != 0) ? '<spam>'.$ivabaseTemp.'</spam><br>': '';
			$ivaItebisTemp = ($dataTemp['ivaItebisTemp'] != 0) ? '<spam>'.$ivaItebisTemp.'</spam><br>': '';
			$totalFact = price($dataTemp['total_ht'] + $dataTemp['total_tva']);
			print '
				<script>
					var rows = $("tr");
					var ivabaseTemp = "'.$ivabaseTemp.'";
					var ivaItebisTemp = "'.$ivaItebisTemp.'";
					var totalFact = "'.$totalFact.'";
					rows.find("td").each (function() {
						if($(this).is(\':contains("Importe IVA")\')){
							$(this).parent().after(\'<tr id="ivabase"><td>Retención sobre base imponible</td><td>\'+ivabaseTemp+\'</td></tr>\');
							$(this).parent().after(\'<tr id="ivaItebis"><td>Retención sobre impuestos</td><td>\'+ivaItebisTemp+\'</td></tr>\');
						}
						if($(this).is(\':contains("Facturado")\')){
							$(this).next().html(totalFact);
						}
						if($(this).is(\':contains("Facturada")\')){
							$(this).next().html(totalFact);
						}
						if($(this).is(\':contains("Importe total")\')){
							$(this).next().html(\'$\'+totalFact);
						}
					});
				</script>
			';
		}
	}

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function paymentsupplierinvoices($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		require_once DOL_DOCUMENT_ROOT.'/ncf/lib/ncf.lib.php';

		$error = 0; // Error counter
		if (in_array($parameters['currentcontext'], array('paiementcardlist', 'paymentsupplierlist')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{
			$dataTemp = comprobantesUpdateTotalAmount($object);
			$totalFact = price($dataTemp['total_ht'] + $dataTemp['total_tva']);

			echo '<script>
				$(document).ready(function(){
					var tabla = $(".tagtable.liste");
					var totalFact = "'.$totalFact.'";

					tabla.find("tr").each (function() {
						if(!$(this).is(\':contains("Importe total")\')){
							var hijos = $(this).find("td:eq(4)");
							hijos.html(totalFact);

						}
					});
				});
			</script>';
		}
	}


	/* Add here any other hooked methods... */
}

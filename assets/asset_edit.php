<?php
/*
 * Copyright (c)  2009, Tracmor, LLC
 *
 * This file is part of Tracmor.
 *
 * Tracmor is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tracmor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tracmor; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

	require_once('../includes/prepend.inc.php');
	QApplication::Authenticate(2);
	require_once(__FORMBASE_CLASSES__ . '/AssetEditFormBase.class.php');

	/**
	 * This is a quick-and-dirty draft form object to do Create, Edit, and Delete functionality
	 * of the Asset class.  It extends from the code-generated
	 * abstract AssetEditFormBase class.
	 *
	 * Any display custimizations and presentation-tier logic can be implemented
	 * here by overriding existing or implementing new methods, properties and variables.
	 *
	 * Additional qform control objects can also be defined and used here, as well.
	 *
	 * @package Application
	 * @subpackage FormDraftObjects
	 *
	 */
	class AssetEditForm extends AssetEditFormBase {

		// Header Tabs
		protected $ctlHeaderMenu;
		// Shortcut Menu
		protected $ctlShortcutMenu;

		protected $ctlAssetEdit;
		protected $ctlAssetTransact;
		protected $ctlAssetSearchTool;
		protected $intTransactionTypeId;

		// Child assets datagrid
		protected $dtgChildAssets;

        //Asset history datarepeater
        protected $dtrAssetHistory;

		// Buttons
		protected $btnChildAssetsRemove;
		protected $btnReassign;
		protected $btnLinkToParent;
		protected $btnUnlink;
		protected $btnAddChild;

		// Text box
		protected $txtAddChild;

		protected $lblAddChild;
		protected $lblChildAssets;
		protected $lblAssetCode;
        protected $lblAssetHistory;

		// Status of Asset Search Tool
		protected $intDlgStatus;

		protected $blnEditChild;

		protected $intAssetIdArray;

		protected $pnlAddChildAsset;

		// These are needed for the hovertips in the Shipping/Receiving datagrid
		public $objAssetTransactionArray;
		public $objInventoryTransactionArray;

		// Override the Form_Create method in AssetEditFormBase.inc
		protected function Form_Create() {

			/*QApplication::$Database[1]->EnableProfiling();*/

      // Assign the Transaction Type from the query string, if it exists.
			$this->intTransactionTypeId = QApplication::QueryString('intTransactionTypeId');

		  // Create the Header Menu
			$this->ctlHeaderMenu_Create();
			// Create the Shortcut Menu
			$this->ctlShortcutMenu_Create();

			$this->ctlAssetEdit_Create();
            $this->lblAssetHistory_Create();
            $this->dtrAssetHistory_Create();

			if (!$this->intTransactionTypeId) {
			  $this->lblChildAssets_Create();
  			// Create Buttons
  			$this->btnChildAssetsRemove_Create();
  			$this->btnReassign_Create();
  			$this->btnLinkToParent_Create();
  			$this->btnUnlink_Create();
  			$this->AddChild_Create();
  			$this->ctlAssetSearchTool_Create();
		  }

			// Create the two composite controls
			if ($this->ctlAssetEdit->blnEditMode || $this->intTransactionTypeId) {
				$this->ctlAssetTransact_Create();
			}

			// Display transaction screen if passed an intTransactionTypeId (from shortcut menu)
			if ($this->intTransactionTypeId) {
				$this->DisplayEdit(false);
				$this->DisplayTransaction(true, $this->intTransactionTypeId);
			}
			// Display the edit form
			else {
				$this->DisplayTransaction(false);
				$this->DisplayEdit(true);
			}
			// Create Child Assets Datagrid
			if (!$this->intTransactionTypeId) {
			  $this->dtgChildAssets_Create();
			}
		}

		// Datagrid values must be assigned here because they are not encoded like all other controls
		protected function Form_PreRender() {

			// If an existing asset is being edited, render the Transaction datagrid
			if ($this->ctlAssetEdit->blnEditMode) {

				// Specify the local databind method this datagrid will use
				$this->ctlAssetEdit->dtgAssetTransaction->SetDataBinder('dtgAssetTransaction_Bind');

				// Specify the local databind method this datagrid will use
				$this->ctlAssetEdit->dtgShipmentReceipt->SetDataBinder('dtgShipmentReceipt_Bind');

                // Specify the local databind method this datarepeater will use
                $this->dtrAssetHistory->SetDataBinder('dtrAssetHistory_Bind');

			}

			if ($this->ctlAssetEdit->blnEditMode || $this->intTransactionTypeId) {
				$this->ctlAssetTransact->dtgAssetTransact->SetDataBinder('dtgAssetTransact_Bind');
			}

			// If assets are in the array, finish setting up the datagrid of assets prepared for a transaction
/*			if ($this->ctlAssetEdit->blnEditMode || $this->intTransactionTypeId) {
				if ($this->ctlAssetTransact->objAssetArray) {
					$this->ctlAssetTransact->dtgAssetTransact->TotalItemCount = count($this->ctlAssetTransact->objAssetArray);
					$this->ctlAssetTransact->dtgAssetTransact->DataSource = $this->ctlAssetTransact->objAssetArray;
					$this->ctlAssetTransact->dtgAssetTransact->ShowHeader = true;
				}
				else {
					$this->ctlAssetTransact->dtgAssetTransact->TotalItemCount = 0;
					$this->ctlAssetTransact->dtgAssetTransact->ShowHeader = false;
				}
			}*/

			if (!$this->intTransactionTypeId && $this->ctlAssetEdit->Display) {
        $this->dtgChildAssets->SetDataBinder('dtgChildAssets_Bind');
      }

			$this->DisplayChildAssets();
		}

		/*protected function Form_Exit() {
			//Output database profiling - it shows you the queries made to create this page
			//This will not work on pages with the AJAX Pagination
			QApplication::$Database[1]->OutputProfiling();
  	}*/

		// Setup the Child Assets datagrid
		protected function dtgChildAssets_Create() {

			$this->dtgChildAssets = new QDataGrid($this);
			$this->dtgChildAssets->CellPadding = 5;
			$this->dtgChildAssets->CellSpacing = 0;
			$this->dtgChildAssets->CssClass = "datagrid";

	    // Enable AJAX - this won't work while using the DB profiler
	    $this->dtgChildAssets->UseAjax = true;

	    // Enable Pagination, and set to 20 items per page
	    $objPaginator = new QPaginator($this->dtgChildAssets);
	    $this->dtgChildAssets->Paginator = $objPaginator;
	    $this->dtgChildAssets->ItemsPerPage = 20;

	    $this->dtgChildAssets->AddColumn(new QDataGridColumnExt('<?=$_CONTROL->chkSelectAll_Render() ?>', '<?=$_CONTROL->chkSelected_Render($_ITEM->AssetId) ?>', 'CssClass="dtg_column"', 'HtmlEntities=false', 'Width=15px', 'Display=false'));
	    $this->dtgChildAssets->AddColumn(new QDataGridColumn('&nbsp;', '<?= $_FORM->DisplayLockedImage($_ITEM->LinkedFlag) ?>', array('CssClass' => "dtg_column", 'Width' => "15px", 'HtmlEntities' => false)));
	    $this->dtgChildAssets->AddColumn(new QDataGridColumn('Asset Tag', '<?= $_ITEM->__toStringWithLink("bluelink") ?>', array('CssClass' => "dtg_column", 'Width' => "30%", 'HtmlEntities' => false)));
	    $this->dtgChildAssets->AddColumn(new QDataGridColumn('Model', '<?= $_ITEM->AssetModel->__toStringWithLink("bluelink") ?>', array('CssClass' => "dtg_column", 'Width' => "30%", 'HtmlEntities' => false)));
	    $this->dtgChildAssets->AddColumn(new QDataGridColumn('Location', '<?= $_ITEM->Location->__toString() ?>', array('CssClass' => "dtg_column", 'Width' => "30%")));

	    //$this->dtgChildAssets->SortColumnIndex = 2;

	    $objStyle = $this->dtgChildAssets->RowStyle;
	    $objStyle->ForeColor = '#000000';
	    $objStyle->BackColor = '#FFFFFF';
	    $objStyle->FontSize = 12;

	    $objStyle = $this->dtgChildAssets->AlternateRowStyle;
	    $objStyle->BackColor = '#EFEFEF';

	    $objStyle = $this->dtgChildAssets->HeaderRowStyle;
	    $objStyle->ForeColor = '#000000';
	    $objStyle->BackColor = '#EFEFEF';
	    $objStyle->CssClass = 'dtg_header';

	    $this->dtgChildAssets->ShowHeader = false;
	    if (!isset($this->objAsset)) {
	      $this->SetupAsset($this);
	    }
	    if ($this->objAsset && is_int($this->objAsset->AssetId)) {
	    	$this->ctlAssetEdit->objChildAssetArray = Asset::LoadArrayByParentAssetId($this->objAsset->AssetId);
	    }
	    else {
	    	$this->ctlAssetEdit->objChildAssetArray = array();
	    }
		}

        protected function lblAssetHistory_Create() {
    		$this->lblAssetHistory = new QLabel($this);
    		$this->lblAssetHistory->Name = 'Asset History';
    		$this->lblAssetHistory->Text = 'Asset History';
    		$this->lblAssetHistory->CssClass = 'title';
    	}

        protected function dtrAssetHistory_Create(){
            $this->dtrAssetHistory = new QDataRepeater($this);
            $this->dtrAssetHistory->Paginator = new QPaginator($this);
            $this->dtrAssetHistory->ItemsPerPage = 10;
            $this->dtrAssetHistory->UseAjax = true;
            $this->dtrAssetHistory->Template = 'dtr_asset_history.tpl.php';
            $this->dtrAssetHistory->SetDataBinder('dtrAssetHistory_Bind');
        }

		protected function dtgChildAssets_Bind() {
		  $this->dtgChildAssets->TotalItemCount = count($this->ctlAssetEdit->objChildAssetArray);
	    if ($this->dtgChildAssets->TotalItemCount) {
        $this->dtgChildAssets->ShowHeader = true;
        // added to fix items per page
	       $this->dtgChildAssets->DataSource = $this->ctlAssetEdit->objChildAssetArray;
           $intItemsPerPage = $this->dtgChildAssets->ItemsPerPage;
	       $intItemOffset = ($this->dtgChildAssets->PageNumber - 1) * $intItemsPerPage;
	       $arrDataSource = array_slice($this->ctlAssetEdit->objChildAssetArray, $intItemOffset, $intItemsPerPage);
	      // end of fix
	      $this->dtgChildAssets->DataSource = $arrDataSource;
	      $this->btnChildAssetsRemove->Enabled = true;
	      $this->btnReassign->Enabled = true;
	      $this->btnLinkToParent->Enabled = true;
	      if (!$this->btnUnlink->Enabled)
			$this->btnUnlink->Enabled = true;
	    }
	    else {
	      $this->dtgChildAssets->ShowHeader = false;
	      $this->btnChildAssetsRemove->Enabled = false;
	      $this->btnReassign->Enabled = false;
	      $this->btnLinkToParent->Enabled = false;
	      $this->btnUnlink->Enabled = false;
	    }
		}

		protected function dtgAssetTransaction_Bind() {
			// Get Total Count b/c of Pagination
			$this->ctlAssetEdit->dtgAssetTransaction->TotalItemCount = AssetTransaction::CountShipmentReceiptByAssetId($this->ctlAssetEdit->objAsset->AssetId, false);
			if ($this->ctlAssetEdit->dtgAssetTransaction->TotalItemCount === 0) {
				$this->ctlAssetEdit->dtgAssetTransaction->ShowHeader = false;
			}
			else {
				$this->ctlAssetEdit->dtgAssetTransaction->ShowHeader = true;
			}

			$objClauses = array();
			if ($objClause = $this->ctlAssetEdit->dtgAssetTransaction->OrderByClause)
				array_push($objClauses, $objClause);
			if ($objClause = $this->ctlAssetEdit->dtgAssetTransaction->LimitClause)
				array_push($objClauses, $objClause);
			if ($objClause = QQ::Expand(QQN::AssetTransaction()->Transaction->TransactionType))
				array_push($objClauses, $objClause);
			if ($objClause = QQ::Expand(QQN::AssetTransaction()->AssetTransactionCheckout->AssetTransactionCheckoutId))
				array_push($objClauses, $objClause);
			if ($objClause = QQ::Expand(QQN::AssetTransaction()->SourceLocation))
				array_push($objClauses, $objClause);
			if ($objClause = QQ::Expand(QQN::AssetTransaction()->DestinationLocation))
				array_push($objClauses, $objClause);

			$objCondition = QQ::AndCondition(QQ::Equal(QQN::AssetTransaction()->AssetId, $this->ctlAssetEdit->objAsset->AssetId), QQ::NotEqual(QQN::AssetTransaction()->Transaction->TransactionTypeId, 6), QQ::NotEqual(QQN::AssetTransaction()->Transaction->TransactionTypeId, 7));

			$this->ctlAssetEdit->dtgAssetTransaction->DataSource = AssetTransaction::QueryArray($objCondition, $objClauses);
		}

		protected function dtgShipmentReceipt_Bind() {
			// Get Total Count for Pagination

			$objClauses = array();

			$this->ctlAssetEdit->dtgShipmentReceipt->TotalItemCount = AssetTransaction::CountShipmentReceiptByAssetId($this->ctlAssetEdit->objAsset->AssetId);

			if ($this->ctlAssetEdit->dtgShipmentReceipt->TotalItemCount === 0) {
				//$this->ctlAssetEdit->lblShipmentReceipt->Display = false;
				$this->ctlAssetEdit->dtgShipmentReceipt->ShowHeader = false;
			}
			else {

				$objClauses = array();
				if ($objClause = QQ::OrderBy(QQN::AssetTransaction()->Transaction->CreationDate, false)) {
					array_push($objClauses, $objClause);
				}
				if ($objClause = $this->ctlAssetEdit->dtgShipmentReceipt->LimitClause) {
					array_push($objClauses, $objClause);
				}
				if ($objClause = QQ::Expand(QQN::AssetTransaction()->Transaction->Shipment)) {
					array_push($objClauses, $objClause);
				}
				if ($objClause = QQ::Expand(QQN::AssetTransaction()->Transaction->Receipt)) {
					array_push($objClauses, $objClause);
				}
				if ($objClause = QQ::Expand(QQN::AssetTransaction()->SourceLocation)) {
					array_push($objClauses, $objClause);
				}
				if ($objClause = QQ::Expand(QQN::AssetTransaction()->DestinationLocation)) {
					array_push($objClauses, $objClause);
				}

				$objCondition = QQ::AndCondition(QQ::Equal(QQN::AssetTransaction()->AssetId, $this->ctlAssetEdit->objAsset->AssetId), QQ::OrCondition(QQ::Equal(QQN::AssetTransaction()->Transaction->TransactionTypeId, 6), QQ::Equal(QQN::AssetTransaction()->Transaction->TransactionTypeId, 7)));

				$this->ctlAssetEdit->dtgShipmentReceipt->DataSource = AssetTransaction::QueryArray($objCondition, $objClauses);
			}
		}

        protected function dtrAssetHistory_Bind() {

     		// Get Total Count for Pagination
            $this->dtrAssetHistory->TotalItemCount = AssetTransaction::CountAssetTransaction($this->ctlAssetEdit->objAsset->AssetId);
            $objClauses = array();
            $objClause = QQ::OrderBy(QQN::AssetTransaction()->Transaction->CreationDate, false);
            array_push($objClauses, $objClause);
            $objCondition = QQ::AndCondition(QQ::Equal(QQN::AssetTransaction()->AssetId, $this->ctlAssetEdit->objAsset->AssetId), QQ::OrCondition(QQ::In(QQN::AssetTransaction()->Transaction->TransactionTypeId, array(1,2,3,6,7,8,9,10,11))));
            $intItemsPerPage = $this->dtrAssetHistory->ItemsPerPage;
            $intItemOffset = ($this->dtrAssetHistory->PageNumber - 1) * $intItemsPerPage;
            $arrDataSource = array_slice(AssetTransaction::QueryArray($objCondition, $objClauses), $intItemOffset, $intItemsPerPage);
            $this->dtrAssetHistory->DataSource =$arrDataSource;
     	}

		protected function dtgAssetTransact_Bind() {

			// If assets are in the array, finish setting up the datagrid of assets prepared for a transaction
			if ($this->ctlAssetTransact->objAssetArray) {
				// ItemOffset only exists in QCodo4 and beyond. When merging with Q4 we can use this to calculate intItemOffset instead of the line two lines below it.
				//$intItemOffset = $this->ctlAssetTransact->dtgAssetTransact->ItemOffset;
				$intItemsPerPage = $this->ctlAssetTransact->dtgAssetTransact->ItemsPerPage;
				$intItemOffset = ($this->ctlAssetTransact->dtgAssetTransact->PageNumber - 1) * $intItemsPerPage;

				$arrDataSource = array_slice($this->ctlAssetTransact->objAssetArray, $intItemOffset, $intItemsPerPage);

				$this->ctlAssetTransact->dtgAssetTransact->TotalItemCount = count($this->ctlAssetTransact->objAssetArray);
				// $this->ctlAssetTransact->dtgAssetTransact->DataSource = $this->ctlAssetTransact->objAssetArray;
				$this->ctlAssetTransact->dtgAssetTransact->DataSource = $arrDataSource;
				$this->ctlAssetTransact->dtgAssetTransact->ShowHeader = true;
			}
			else {
				$this->ctlAssetTransact->dtgAssetTransact->TotalItemCount = 0;
				$this->ctlAssetTransact->dtgAssetTransact->ShowHeader = false;
			}
		}

/*		protected function Form_Exit() {
			QApplication::$Database[1]->OutputProfiling();
		}*/
/*********************
CREATE FIELD METHODS
*********************/

  	// Create and Setup the Header Composite Control
  	protected function ctlHeaderMenu_Create() {
  		$this->ctlHeaderMenu = new QHeaderMenu($this);
  	}

  	// Create and Setp the Shortcut Menu Composite Control
  	protected function ctlShortcutMenu_Create() {
  		$this->ctlShortcutMenu = new QShortcutMenu($this);
  	}

		// Create the AssetEdit composite control
		protected function ctlAssetEdit_Create() {
			$this->ctlAssetEdit = new QAssetEditComposite($this);
		}

		// Create the AssetTransact Composite control
		protected function ctlAssetTransact_Create() {
			$this->ctlAssetTransact = new QAssetTransactComposite($this);
		}

		protected function lblChildAssets_Create() {
		  $this->lblChildAssets = new QLabel($this);
		  $this->lblChildAssets->Text = "Child Assets";
		  $this->lblChildAssets->CssClass = "title";
		}


		protected function ctlAssetSearchTool_Create() {
		  $this->ctlAssetSearchTool = new QAssetSearchToolComposite($this);
		}

		protected function btnChildAssetsRemove_Create() {
		  $this->btnChildAssetsRemove = new QButton($this);
		  $this->btnChildAssetsRemove->Text = "Remove";
		  $this->btnChildAssetsRemove->Enabled = false;
		  $this->btnChildAssetsRemove->Display = false;
		  $this->btnChildAssetsRemove->AddAction(new QClickEvent(), new QConfirmAction('Are you SURE you want to REMOVE this Asset?'));
  		$this->btnChildAssetsRemove->AddAction(new QClickEvent(), new QAjaxAction('btnChildAssetsRemove_Click'));
  		$this->btnChildAssetsRemove->AddAction(new QEnterKeyEvent(), new QConfirmAction('Are you SURE you want to REMOVE this Asset?'));
  		$this->btnChildAssetsRemove->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnChildAssetsRemove_Click'));
  		$this->btnChildAssetsRemove->AddAction(new QEnterKeyEvent(), new QTerminateAction());
		}

		protected function btnReassign_Create() {
		  $this->btnReassign = new QButton($this);
		  $this->btnReassign->Text = "Reassign";
		  $this->btnReassign->Enabled = false;
		  $this->btnReassign->Display = false;
		  $this->btnReassign->AddAction(new QClickEvent(), new QAjaxAction('btnReassign_Click'));
		  $this->btnReassign->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnReassign_Click'));
		  $this->btnReassign->AddAction(new QEnterKeyEvent(), new QTerminateAction());
		}

		protected function btnLinkToParent_Create() {
		  $this->btnLinkToParent = new QButton($this);
		  $this->btnLinkToParent->Text = "Lock to Parent";
		  $this->btnLinkToParent->Enabled = false;
		  $this->btnLinkToParent->Display = false;
		  $this->btnLinkToParent->AddAction(new QClickEvent(), new QAjaxAction('btnLinkToParent_Click'));
		  $this->btnLinkToParent->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnLinkToParent_Click'));
		  $this->btnLinkToParent->AddAction(new QEnterKeyEvent(), new QTerminateAction());
		}

		protected function btnUnlink_Create() {
		  $this->btnUnlink = new QButton($this);
		  $this->btnUnlink->Text = "Unlock";
		  $this->btnUnlink->Enabled = false;
		  $this->btnUnlink->Display = false;
		  $this->btnUnlink->AddAction(new QClickEvent(), new QAjaxAction('btnUnlink_Click'));
		  $this->btnUnlink->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnUnlink_Click'));
		  $this->btnUnlink->AddAction(new QEnterKeyEvent(), new QTerminateAction());
		}

		protected function AddChild_Create() {
		  $this->pnlAddChildAsset = new QPanel($this);
		  $this->pnlAddChildAsset->Display = false;
		  $this->pnlAddChildAsset->Template = "asset_pnl_add_child_asset.tpl.php";

		  $this->lblAssetCode = new QLabel($this->pnlAddChildAsset);
		  $this->lblAssetCode->Text = "Asset Tag:";

		  $this->btnAddChild = new QButton($this->pnlAddChildAsset);
		  $this->btnAddChild->Text = "Add Child";
		  $this->btnAddChild->AddAction(new QClickEvent(), new QAjaxAction('btnAddChild_Click'));
		  $this->btnAddChild->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnAddChild_Click'));
		  $this->btnAddChild->AddAction(new QEnterKeyEvent(), new QTerminateAction());

		  $this->txtAddChild = new QTextBox($this->pnlAddChildAsset);
		  $this->txtAddChild->Width = 200;
		  $this->txtAddChild->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnAddChild_Click'));
		  $this->txtAddChild->AddAction(new QEnterKeyEvent(), new QTerminateAction());

		  $this->lblAddChild = new QLabel($this->pnlAddChildAsset);
		  $this->lblAddChild->HtmlEntities = false;
		  $this->lblAddChild->Text = '<img src="../images/icons/lookup.png" border="0" style="cursor:pointer;">';
		  $this->lblAddChild->AddAction(new QClickEvent(), new QAjaxAction('lblAddChild_Click'));
		  $this->lblAddChild->AddAction(new QEnterKeyEvent(), new QAjaxAction('lblAddChild_Click'));
		  $this->lblAddChild->AddAction(new QEnterKeyEvent(), new QTerminateAction());
		}

		// Originally taken from AssetEditFormBase.inc
		// Altered because it is being called from a composite control
		public function SetupAsset($objCaller = null) {

			// Lookup Object PK information from Query String (if applicable)
			// Set mode to Edit or New depending on what's found
			// Overridden from AssetEditFormBase to add the $objCaller parameter
			$intAssetId = QApplication::QueryString('intAssetId');

			if (($intAssetId)) {

				//$objCaller->objAsset = Asset::Load($intAssetId);
				// To minimize the count of queries
				$objClauses = array();
        array_push($objClauses, QQ::Expand(QQN::Asset()->AssetModel));
        array_push($objClauses, QQ::Expand(QQN::Asset()->AssetModel->Category));
        array_push($objClauses, QQ::Expand(QQN::Asset()->AssetModel->Manufacturer));
        array_push($objClauses, QQ::Expand(QQN::Asset()->ParentAsset));
        array_push($objClauses, QQ::Expand(QQN::Asset()->Location));
        array_push($objClauses, QQ::Expand(QQN::Asset()->CreatedByObject));
        $objCaller->objAsset = Asset::QuerySingle(QQ::Equal(QQN::Asset()->AssetId, $intAssetId), $objClauses);

				if (!$objCaller->objAsset)
					throw new Exception('Could not find a Asset object with PK arguments: ' . $intAssetId);

				$objCaller->strTitleVerb = QApplication::Translate('Edit');
				$objCaller->blnEditMode = true;
				$this->blnEditChild = true;
			} else {
				$objCaller->objAsset = new Asset();
				$objCaller->strTitleVerb = QApplication::Translate('Create');
				$objCaller->blnEditMode = false;
			}
			QApplication::AuthorizeEntity($objCaller->objAsset, $objCaller->blnEditMode);
		}

		// Render the remove button column in the datagrid
		public function RemoveColumn_Render(Asset $objAsset) {

      $strControlId = 'btnRemove' . $objAsset->AssetId;
      $btnRemove = $this->GetControl($strControlId);
      if (!$btnRemove) {
          // Create the Remove button for this row in the DataGrid
          // Use ActionParameter to specify the ID of the asset
          $btnRemove = new QButton($this->ctlAssetTransact->dtgAssetTransact, $strControlId);
          $btnRemove->Text = 'Remove';
          $btnRemove->ActionParameter = $objAsset->AssetId;
          $btnRemove->AddAction(new QClickEvent(), new QAjaxAction('btnRemove_Click'));
          $btnRemove->AddAction(new QEnterKeyEvent(), new QAjaxAction('btnRemove_Click'));
          $btnRemove->AddAction(new QEnterKeyEvent(), new QTerminateAction());
          $btnRemove->CausesValidation = false;
          if ($objAsset->LinkedFlag) {
            $btnRemove->Enabled = false;
          }
      }

      return $btnRemove->Render(false);
		}

		// Remove button click action for each asset in the datagrid
		public function btnRemove_Click($strFormId, $strControlId, $strParameter) {

			$intAssetId = $strParameter;
			$objLinkedAssetArray = Asset::LoadChildLinkedArrayByParentAssetId($intAssetId);
			if ($this->ctlAssetTransact->objAssetArray) {
				foreach ($this->ctlAssetTransact->objAssetArray as $key => $value) {
          if ($value->AssetId == $intAssetId) {
						unset ($this->ctlAssetTransact->objAssetArray[$key]);
					}
					// Removing all linked (locked) assets from the transaction
					elseif ($objLinkedAssetArray) {
					  foreach ($objLinkedAssetArray as $objLinkedAsset) {
					    if ($value->AssetId == $objLinkedAsset->AssetId) {
					      unset ($this->ctlAssetTransact->objAssetArray[$key]);
					    }
					  }
					}
			  }
			}
			$this->ctlAssetTransact->dtgAssetTransact->Refresh();
		}

		protected function btnAddChild_Click() {
		  if ($this->txtAddChild->Text) {
  		  $objChildAsset = Asset::LoadByAssetCode($this->txtAddChild->Text);
  		  if ($objChildAsset) {
            // check if asset already within removed child assets
            $blnAssetNotRemoved = true;
            if(is_array($this->ctlAssetEdit->objRemovedChildAssetArray)){
                $newRemovedChildAssetArray = array();

                foreach($this->ctlAssetEdit->objRemovedChildAssetArray as $removedAsset){
                  if($removedAsset->AssetId == $objChildAsset->AssetId){
                    $blnAssetNotRemoved = false;
                  }
                  else {
                    $newRemovedChildAssetArray[] = $removedAsset;
                  }
                }
                if($blnAssetNotRemoved == false){
                    $this->ctlAssetEdit->objRemovedChildAssetArray = $newRemovedChildAssetArray;
                }
            }
  		    if ($objChildAsset->ParentAssetId&&$blnAssetNotRemoved){
              $this->txtAddChild->Warning = "That asset tag already have the parent asset tag. Please try another.";
            }
  		    elseif ($objChildAsset->AssetCode == $this->objAsset->AssetCode) {
  		      $this->txtAddChild->Warning = "That asset tag does not exist. Please try another.";
  		    }
  		    else {
  		      $objChildAsset->LinkedFlag = false;
  		      $objChildAsset->ParentAssetId = $this->objAsset->AssetId;
  		      array_push($this->ctlAssetEdit->objChildAssetArray, $objChildAsset);

  		      $this->txtAddChild->Text = "";
  		      $this->dtgChildAssets_Bind();
  		    }
  		  }
  		  else {
  		    $this->txtAddChild->Warning = "That asset tag does not exist. Please try another.";
  		  }
		  }
		  else {
		    $this->txtAddChild->Warning = "";
		  }
		}

		protected function lblAddChild_Click() {
		  // Uncheck all items but SelectAll checkbox
      $this->UncheckAllItems();
      if ($this->intDlgStatus) {
        $this->ctlAssetSearchTool->Refresh();
      }
      $this->ctlAssetSearchTool->btnAssetSearchToolAdd->Text = "Add Selected";
      $this->ctlAssetSearchTool->dlgAssetSearchTool->ShowDialogBox();
      $this->intDlgStatus = 2; // Add Child
		}

		protected function btnReassign_Click() {
		  $this->intAssetIdArray = $this->dtgChildAssets->GetSelected("AssetId");
		  if (count($this->intAssetIdArray) > 0) {
		    // Uncheck all items but SelectAll checkbox
        $this->UncheckAllItems();
        if ($this->intDlgStatus) {
          $this->ctlAssetSearchTool->Refresh();
        }
        $this->ctlAssetSearchTool->btnAssetSearchToolAdd->Text = "Reassign";
        $this->ctlAssetSearchTool->dlgAssetSearchTool->ShowDialogBox();
		    $this->intDlgStatus = 1; // Reassign
		  }
      else {
        $this->btnUnlink->Warning = "No selected assets.";
      }
		}

		protected function btnChildAssetsRemove_Click() {
		  $arrAssetId = $this->dtgChildAssets->GetSelected("AssetId");
          if (count($arrAssetId)) {
		    if (!is_array($this->ctlAssetEdit->objRemovedChildAssetArray)) {
		      $this->ctlAssetEdit->objRemovedChildAssetArray = array();
		    }
		    $objNewChildAssetArray = array();
           // Creating the associative array with AssetId as a key
		    foreach ($this->ctlAssetEdit->objChildAssetArray as $objChildAsset) {
              $objNewChildAssetArray[$objChildAsset->AssetId] = $objChildAsset;
            }
            // Removing all checked child assets
            foreach ($arrAssetId as $intAssetId) {
              // subtract if checked already removed
              $blnNotRemoved = true;
              foreach($this->ctlAssetEdit->objRemovedChildAssetArray as $alreadyRemoved){
                if ($alreadyRemoved->AssetId==$intAssetId) {
                   $blnNotRemoved = false;
                }
              }
              if ($blnNotRemoved){
                $objRemovedChildAsset = $objNewChildAssetArray[$intAssetId];
                // Remove child asset
                 unset($objNewChildAssetArray[$intAssetId]);
                 $objRemovedChildAsset->ParentAssetId = "";
                 $objRemovedChildAsset->LinkedFlag = false;
                 // Add removing child asset to objRemovedChildAssetArray
                 array_push($this->ctlAssetEdit->objRemovedChildAssetArray, $objRemovedChildAsset);
              }
            }
        // Creating new objChildAssetArray without removing assets
        $this->ctlAssetEdit->objChildAssetArray = array();
		    foreach ($objNewChildAssetArray as $objChildAsset) {
		      array_push($this->ctlAssetEdit->objChildAssetArray, $objChildAsset);
		    }
		  }
		  else {
		    $this->btnUnlink->Warning = "No selected assets.";
		  }
		}

		protected function btnLinkToParent_Click() {
		  $this->btnUnlink->Warning = "";
		  $blnError = false;
		  $arrAssetId = $this->dtgChildAssets->GetSelected("AssetId");
		  if (count($arrAssetId)) {
		    $objNewChildAssetArray = array();
        // Creating the associative array with AssetId as a key
		    foreach ($this->ctlAssetEdit->objChildAssetArray as $objChildAsset) {
          $objNewChildAssetArray[$objChildAsset->AssetId] = $objChildAsset;
        }
        // Foreach checked child assets
		    foreach ($arrAssetId as $intAssetId) {
		      // Load the object of the child asset from array by AssetId
		      $objAsset = $objNewChildAssetArray[$intAssetId];
		      // Error checking
          if ($objAsset->LocationId != $this->objAsset->LocationId) {
            $blnError = true;
      		  $this->btnUnlink->Warning .= "The child asset (" . $objAsset->AssetCode . ") must be in the same location as the parent asset.<br />";
          }
          elseif ($objAsset->CheckedOutFlag || $objAsset->ReservedFlag || $objAsset->ArchivedFlag || $objAsset->LocationId == 2 && $objAsset->LocationId == 3 || $objAsset->LocationId == 5 || AssetTransaction::PendingTransaction($objAsset->AssetId)) {
      		  $blnError = true;
      		  $this->btnUnlink->Warning .= "Child asset tag (" . $objAsset->AssetCode . ") must not be currently Archived, Checked Out, Pending Shipment, Shipped/TBR, or Reserved.<br />";
      		}
      		elseif ($this->objAsset->CheckedOutFlag || $this->objAsset->ReservedFlag || $this->objAsset->ArchivedFlag || $this->objAsset->LocationId == 2 && $this->objAsset->LocationId == 3 || $this->objAsset->LocationId == 5 || AssetTransaction::PendingTransaction($this->objAsset->AssetId)) {
      		  $blnError = true;
      		  $this->btnUnlink->Warning .= "Parent asset tag (" . $this->objAsset->AssetCode . ") must not be currently Archived, Checked Out, Pending Shipment, Shipped/TBR, or Reserved.<br />";
      		}
      		else {
      		  $objAsset->LinkedFlag = true;
      		  $objNewChildAssetArray[$objAsset->AssetId] = $objAsset;
      		}
		    }
		    if (!$blnError) {
		      $this->ctlAssetEdit->objChildAssetArray = array();
		      foreach ($objNewChildAssetArray as $objChildAsset) {
		        array_push($this->ctlAssetEdit->objChildAssetArray, $objChildAsset);
		      }
		    }
		    $this->UncheckAllItems();
		  }
		  else {
		    $this->btnUnlink->Warning = "No selected assets.";
		  }
		}

		protected function btnUnlink_Click() {
		  $arrAssetId = $this->dtgChildAssets->GetSelected("AssetId");
		  if (count($arrAssetId)) {
		    $objNewChildAssetArray = array();
        foreach ($this->ctlAssetEdit->objChildAssetArray as $objChildAsset) {
          $objNewChildAssetArray[$objChildAsset->AssetId] = $objChildAsset;
        }

        foreach ($arrAssetId as $intAssetId) {
      		$objNewChildAssetArray[$intAssetId]->LinkedFlag = false;
		    }
		    $this->ctlAssetEdit->objChildAssetArray = array();
        foreach ($objNewChildAssetArray as $objChildAsset) {
           array_push($this->ctlAssetEdit->objChildAssetArray, $objChildAsset);
        }

		    $this->UncheckAllItems();
		  }
		}

		public function btnAssetSearchToolAdd_Click() {
		  $this->ctlAssetSearchTool->lblWarning->Text = "";
      switch ($this->intDlgStatus) {
        // Reassign
        case '1' :
          $intSelectedAssetId = $this->ctlAssetSearchTool->ctlAssetSearch->dtgAsset->GetSelected("AssetId");
          if (count($intSelectedAssetId) > 1) {
            $this->ctlAssetSearchTool->lblWarning->Text = "You must select only one parent asset.";
          }
          elseif (count($intSelectedAssetId) != 1) {
            $this->ctlAssetSearchTool->lblWarning->Text = "No selected assets.";
          }
          else {
            if ($objAsset = Asset::LoadByAssetId($intSelectedAssetId[0])) {
              $blnError = false;
              $objNewChildAssetArray = array();
              foreach ($this->ctlAssetEdit->objChildAssetArray as $objChildAsset) {
                $objNewChildAssetArray[$objChildAsset->AssetId] = $objChildAsset;
              }
              if (!is_array($this->ctlAssetEdit->objRemovedChildAssetArray)) {
      		      $this->ctlAssetEdit->objRemovedChildAssetArray = array();
      		    }

      		    $objNewRemovedAssetArray = array();
              foreach ($this->intAssetIdArray as $intAssetId) {
                $objChildAsset = $objNewChildAssetArray[$intAssetId];
                if ($objChildAsset->AssetCode != $objAsset->AssetCode) {
                  $objChildAsset->ParentAssetId = $objAsset->AssetId;
                  $objChildAsset->LinkedFlag = false;
                  $objNewRemovedAssetArray[] = $objChildAsset;
                  unset($objNewChildAssetArray[$intAssetId]);
                }
                else{
                  $this->ctlAssetSearchTool->lblWarning->Text = "Parent and child asset tags cannot be the same.";
                  $blnError = true;
                }
              }
              if (!$blnError) {
                foreach ($objNewRemovedAssetArray as $objNewRemovedAsset) {
                  array_push($this->ctlAssetEdit->objRemovedChildAssetArray, $objNewRemovedAsset);
                }

                $this->ctlAssetEdit->objChildAssetArray = array();
                foreach ($objNewChildAssetArray as $objNewChildAsset) {
                  array_push($this->ctlAssetEdit->objChildAssetArray, $objNewChildAsset);
                }

                $this->intAssetIdArray = array();
                $this->ctlAssetSearchTool->dlgAssetSearchTool->HideDialogBox();
                $this->dtgChildAssets_Bind();
              }
            }
          }
          $this->UncheckAllItems();
          break;
        // Add child assets using popup
        case '2' :
          $intSelectedAssetCount = 0;
          $blnError = false;
          $arrCheckedAssets = array();
          foreach ($this->ctlAssetSearchTool->ctlAssetSearch->dtgAsset->GetSelected("AssetId") as $intAssetId) {
            $intSelectedAssetCount++;
            $objNewChildAsset = Asset::LoadByAssetId($intAssetId);
            if ($objNewChildAsset && $objNewChildAsset->ParentAssetId) {
    		      $this->ctlAssetSearchTool->lblWarning->Text .= "Asset tag (" . $objNewChildAsset->AssetCode . ") already have the parent asset tag. Please try another.<br />";
    		      $blnError = true;
            }
            elseif ($objNewChildAsset->AssetCode != $this->objAsset->AssetCode) {
              $objNewChildAsset->LinkedFlag = false;
              $objNewChildAsset->ParentAssetId = $this->objAsset->AssetId;
              $arrCheckedAssets[] = $objNewChildAsset;
            }
            else {
              $this->ctlAssetSearchTool->lblWarning->Text .= "Asset tag (" . $objNewChildAsset->AssetCode . ") must not be the same as asset tag.<br />";
              $blnError = true;
            }
          }
          if ($intSelectedAssetCount == 0) {
            $this->ctlAssetSearchTool->lblWarning->Text .= "No selected assets.<br />";
          }
          elseif (!$blnError) {
            foreach ($arrCheckedAssets as $objAsset) {
            	//$objAsset->Save();
            	array_push($this->ctlAssetEdit->objChildAssetArray, $objAsset);
            }
            $this->ctlAssetSearchTool->dlgAssetSearchTool->HideDialogBox();
            $this->dtgChildAssets_Bind();
          }
          break;
        // Add Parent Asset Code
        case '3' :
          $intSelectedAssetId = $this->ctlAssetSearchTool->ctlAssetSearch->dtgAsset->GetSelected("AssetId");
          if (count($intSelectedAssetId) > 1) {
            $this->ctlAssetSearchTool->lblWarning->Text = "You must select only one parent asset.";
          }
          elseif (count($intSelectedAssetId) != 1) {
            $this->ctlAssetSearchTool->lblWarning->Text = "No selected assets.";
          }
          else {
            if (!($objParentAsset = Asset::LoadByAssetId($intSelectedAssetId[0]))) {
              $this->ctlAssetSearchTool->lblWarning->Text = "That asset tag does not exist. Please try another.";

            }
            elseif ($objParentAsset->AssetId == $this->objAsset->AssetId) {
        			$this->ctlAssetSearchTool->lblWarning->Text = "Parent asset tag must not be the same as asset tag. Please try another.";
            }
            else {
              $this->ctlAssetEdit->txtParentAssetCode->Text = $objParentAsset->AssetCode;
              $this->ctlAssetSearchTool->dlgAssetSearchTool->HideDialogBox();
            }
          }
          break;
        default :
          $this->ctlAssetSearchTool->lblWarning->Text = "Error: unknown action";
          break;
      }

      // Uncheck all items but SelectAll checkbox
      $this->UncheckAllItems();
		}

		// Display the edit form
		public function DisplayEdit($blnDisplay) {
			if ($blnDisplay) {
				$this->ctlAssetEdit->Display = true;
				$this->DisplayChildAssets();
			}
			else {
                $this->dtrAssetHistory->Paginator->Display = false;
                $this->lblAssetHistory->Display = false;
                $this->dtrAssetHistory->Display = false;
                $this->dtrAssetHistory->Paginator->Visible = false;
                $this->lblAssetHistory->Visible = false;
                $this->dtrAssetHistory->Visible = false;
				$this->ctlAssetEdit->Display = false;
				$this->DisplayChildAssets();
			}
		}

		// Display the Child Assets form
		public function DisplayChildAssets() {
			if ($this->ctlAssetEdit->btnSaveDisplay && $this->blnEditChild) {
  		  $this->lblChildAssets->Display = true;
			  $this->lblAssetCode->Display = true;
  		  $this->pnlAddChildAsset->Display = true;
			  $this->btnChildAssetsRemove->Display = true;
  		  $this->btnReassign->Display = true;
  		  $this->btnLinkToParent->Display = true;
  		  $this->btnUnlink->Display = true;
  		  $this->dtgChildAssets->GetColumn(0)->Display = true;
  		  $this->dtgChildAssets_Bind();
			}
			else {
			  if ($this->intTransactionTypeId) {
			    $this->lblChildAssets->Display = false;
			    $this->dtgChildAssets->Display = false;
			  }
			  elseif (!$this->ctlAssetEdit->Display) {
			    $this->lblChildAssets->Visible = false;
			    $this->dtgChildAssets->Visible = false;
			  }
			  else {
			    $this->lblChildAssets->Visible = true;
			    @$this->dtgChildAssets->Visible = true;
			  }
  		  $this->lblAssetCode->Display = false;
  		  $this->pnlAddChildAsset->Display = false;
			  $this->btnChildAssetsRemove->Display = false;
  		  $this->btnReassign->Display = false;
  		  $this->btnLinkToParent->Display = false;
  		  $this->btnUnlink->Display = false;
			}
		}

		// Display the transaction form
		public function DisplayTransaction($blnDisplay, $intTransactionTypeId = null) {
			if ($blnDisplay) {
				$this->ctlAssetTransact->SetupDisplay($intTransactionTypeId);
				$this->ctlAssetTransact->Display = true;
			}
			elseif ($this->ctlAssetTransact) {
				$this->ctlAssetTransact->Display = false;
			}
		}

		// This method is run when the asset model edit dialog box is closed
		public function CloseAssetModelEditPanel($blnUpdates) {
			$objPanel = $this->ctlAssetEdit->dlgNewAssetModel;
			$objPanel->HideDialogBox();
		}

		public function DisplayLockedImage($bitLinkedFlag) {
		  if ($bitLinkedFlag)
		    return '<img src="../images/icons/locked.png" border="0">';
		  return '&nbsp;';
		}

		// Uncheck all items but SelectAll checkbox
		public function UncheckAllItems() {
		  foreach ($this->GetAllControls() as $objControl) {
        if (substr($objControl->ControlId, 0, 11) == 'chkSelected') {
          $objControl->Checked = false;
        }
      }
		}

		// This method is called by btnSave_Click() or btnCancel_Click()
		public function RefreshChildAssets() {
		  $this->ctlAssetEdit->objChildAssetArray = Asset::LoadArrayByParentAssetId($this->objAsset->AssetId);
		  $this->ctlAssetEdit->objRemovedChildAssetArray = array();
		  // Hide the column with checkboxes
		  $this->dtgChildAssets->GetColumn(0)->Display = false;
		  $this->dtgChildAssets_Bind();
		}

		public function lblIconParentAssetCode_Click() {
		  // Uncheck all items but SelectAll checkbox
      $this->UncheckAllItems();
      if ($this->intDlgStatus) {
        $this->ctlAssetSearchTool->Refresh();
      }
      $this->ctlAssetSearchTool->btnAssetSearchToolAdd->Text = "Add Parent Asset";
      $this->ctlAssetSearchTool->dlgAssetSearchTool->ShowDialogBox();
		  $this->intDlgStatus = 3; // Adding the Parent Asset Tag
		}

		// This is run every time a 'To Company' is selected in CheckOut Transaction
  	public function lstToCompany_Select() {
  		if ($this->ctlAssetTransact) {
  		  $this->ctlAssetTransact->lstToCompany_Select();
  		}
  	}

  	// This is run every time a 'Check out to' is selected in CheckOut Transaction
  	public function lstCheckOutTo_Select() {
  	  if ($this->ctlAssetTransact) {
  		  $this->ctlAssetTransact->lstCheckOutTo_Select();
  		}
  	}

  	// This is run every time a 'Set Due Date' is selected in CheckOut Transaction
  	public function lstDueDate_Select() {
  	  if ($this->ctlAssetTransact) {
  		  $this->ctlAssetTransact->lstDueDate_Select();
  		}
  	}

  	// This method is run when company or contact edit dialog box is closed
  	public function CloseNewPanel($blnUpdates) {
  	  if ($this->ctlAssetTransact) {
  	    $this->ctlAssetTransact->CloseNewPanel($blnUpdates);
  	  }
  	}
    // This method is run when the company edit dialog box is closed
  	public function CloseNewToCompanyPanel($blnUpdates) {
  		if ($this->ctlAssetTransact) {
  	    $this->ctlAssetTransact->CloseNewToCompanyPanel($blnUpdates);
  	  }
  	}
    // This method is run when the contact edit dialog box is closed
  	public function CloseNewToContactPanel($blnUpdates) {
  		if ($this->ctlAssetTransact) {
  	    $this->ctlAssetTransact->CloseNewToContactPanel($blnUpdates);
  	  }
  	}
  	
	}

	// Run the form using the template asset_edit.php.inc to render the html
	AssetEditForm::Run('AssetEditForm', 'asset_edit.tpl.php');
?>

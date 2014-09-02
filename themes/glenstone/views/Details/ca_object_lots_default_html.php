<?php
	$va_comments = $this->getVar("comments");
	$t_item = $this->getVar("item");
	$va_access_values = $this->getVar('access_values');

?>
<div class="container">
	<div class="row">
		<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1'>
			<div class="detailNavBgLeft">
				{{{previousLink}}}{{{resultsLink}}}
			</div><!-- end detailNavBgLeft -->
		</div><!-- end col -->
		<div class='col-xs-10 col-sm-10 col-md-10 col-lg-10'>
	
		</div>
		<div class='col-xs-1 col-sm-1 col-md-1 col-lg-1'>
			<div class="detailNavBgRight">
				{{{nextLink}}}
			</div><!-- end detailNavBgLeft -->
		</div><!-- end col -->
	</div>
<?php 
	if ($this->request->user->hasUserRole("founder") || $this->request->user->hasUserRole("supercurator")){
?>	
	<div class="row">
		<div class='col-md-12 col-lg-12'>
			<H4><?php print $t_item->get('ca_object_lots.preferred_labels'); ?></H4>
			<H6></H6>
	

		</div><!-- end col 12-->
	</div><!-- end row -->
	<div class="row">
		<div class='col-xs-12 col-sm-6 col-md-6 col-lg-6'>
			<div class="row">
<?php
				$va_related_artworks = $t_item->get('ca_objects.object_id', array('returnAsArray' => true));
				foreach ($va_related_artworks as $vn_id => $va_related_artwork) {
					$t_object = new ca_objects($va_related_artwork);
					$va_reps = $t_object->getPrimaryRepresentation(array('versions' => 'medium'), null, array("checkAccess" => $va_access_values));
					print "<div class='col-xs-12 col-sm-6 col-md-6 col-lg-6 relatedLot'>";
					print caNavLink($this->request, $va_reps['tags']['medium'], '', '', 'Detail', 'objects/'.$va_related_artwork);
					print "<div class='lotCaption'>";
					print "<p>".caNavLink($this->request, $t_object->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('artist'))), '', '', 'Detail', 'objects/'.$va_related_artwork)."</p>";
					print "<p>".caNavLink($this->request, "<i>".$t_object->get('ca_objects.preferred_labels')."</i>, ".$t_object->get('ca_objects.creation_date'), '', '', 'Detail', 'objects/'.$va_related_artwork)."</p>";
					print "<p>".$t_object->get('ca_objects.medium')."</p>";
					print "<p>".$t_object->get('ca_objects.dimensions.display_dimensions')."</p>";
					print "<p>".$t_object->get('ca_objects.idno')."</p>";				
					print "</div><!-- end lotCaption -->";
					print "</div>";
				}
?>				

			</div><!-- end row -->	
		</div><!-- end col 6-->		
		<div class='col-xs-12 col-sm-6 col-md-6 col-lg-6'>				
<?php
				if ($va_lot_entities = $t_item->get('ca_entities.preferred_labels', array('restrictToRelationshipTypes' => array('source'), 'delimiter' => '<br/>', 'returnAsLink' => true))) {
					print "<div class='unit'><span class='metaTitle'>Source: </span><span class='meta'>".$va_lot_entities."</span></div>";
				}				
				if ($va_lot_status = $t_item->get('ca_object_lots.lot_status_id', array('convertCodesToDisplayText' => true))) {
					print "<div class='unit'><span class='metaTitle'>Accession Status: </span><span class='meta'>".$va_lot_status."</span></div>";
				}				
				if ($va_purchase_date = $t_item->get('ca_object_lots.purchase_date')) {
					print "<div class='unit'><span class='metaTitle'>Purchased by: </span><span class='meta'>".$va_purchase_date."</span></div>";
				}			
				if ($va_accession_date = $t_item->get('ca_object_lots.accession_date')) {
					print "<div class='unit'><span class='metaTitle'>Accession Date: </span><span class='meta'>".$va_accession_date."</span></div>";
				}				
				if ($va_invoice_date = $t_item->get('ca_object_lots.invoice_date')) {
					print "<div class='unit'><span class='metaTitle'>Invoice Date: </span><span class='meta'>".$va_invoice_date."</span></div>";
				}
				if ($va_purchase = $t_item->get('ca_object_lots.purchase_price')) {
					print "<div class='unit'><span class='metaTitle'>Purchase Price: </span><span class='meta'>".$va_purchase."</span></div>";
				}
				if ($va_terms = $t_item->get('ca_object_lots.payment_terms')) {
					print "<div class='unit'><span class='metaTitle'>Payment Terms: </span><span class='meta'>".$va_terms."</span></div>";
				}	
				if ($va_final = $t_item->get('ca_object_lots.final_payment_date')) {
					print "<div class='unit'><span class='metaTitle'>Final Payment Date: </span><span class='meta'>".$va_final."</span></div>";
				}	
				if ($va_retail = $t_item->get('ca_object_lots.retail_value')) {
					print "<div class='unit'><span class='metaTitle'>Current Market Value: </span><span class='meta'>".$va_retail."</span></div>";
				}	
				if ($va_insurance = $t_item->get('ca_object_lots.accession_insurance_value')) {
					print "<div class='unit '><span class='metaTitle'>Insurance Value: </span><span class='meta'>".$va_insurance."</span></div>";
				}
				if ($t_item->get('ca_object_lots.invoice_upload.invoice_upload_media')){
					$va_lot_images = $t_item->get('ca_object_lots.invoice_upload', array('returnAsArray' => true, 'ignoreLocale' => true, 'rawDate' => 1, 'version' => 'icon')); 
					print '<div class="unit "><span class="metaTitle">&nbsp;</span><span class="meta">';

					$o_db = new Db();
					$vn_media_element_id = $t_item->_getElementID('invoice_upload_media');
					foreach ($va_lot_images as $vn_lot_id => $va_lot_image) {
						if ($va_lot_image['invoice_upload_primary'] == 162) {
							$qr_res = $o_db->query('SELECT value_id FROM ca_attribute_values WHERE attribute_id = ? AND element_id = ?', array($vn_lot_id, $vn_media_element_id)) ;
							if ($qr_res->nextRow()) {
								print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, '', 'Detail', 'GetMediaInfo', array('object_id' => $vn_object_id, 'value_id' => $qr_res->get('value_id')))."\"); return false;'>".$va_lot_image['invoice_upload_media']."</a>";

							}
						}
					}
					print "</span><div class='clearfix'></div></div>";
				}
				if ($t_item->get('ca_object_lots.bill_upload.bill_upload_media')){
					$va_bill_images = $t_item->get('ca_object_lots.bill_upload', array('returnAsArray' => true, 'ignoreLocale' => true, 'rawDate' => 1, 'version' => 'icon')); 
					print '<div class="unit "><span class="metaTitle">&nbsp;</span><span class="meta">';

					$o_db = new Db();
					$vn_media_element_id = $t_item->_getElementID('bill_upload_media');
					foreach ($va_bill_images as $vn_bill_id => $va_bill_image) {
						if ($va_bill_image['bill_upload_primary'] == 162) {
							$qr_res = $o_db->query('SELECT value_id FROM ca_attribute_values WHERE attribute_id = ? AND element_id = ?', array($vn_bill_id, $vn_media_element_id)) ;
							if ($qr_res->nextRow()) {
								print "<a href='#' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, '', 'Detail', 'GetMediaInfo', array('object_id' => $vn_object_id, 'value_id' => $qr_res->get('value_id')))."\"); return false;'>".$va_bill_image['bill_upload_media']."</a>";

							}
						}
					}
					print "</span><div class='clearfix'></div></div>";
				}																					

?>			
			

		</div><!-- end col -->
	</div>	<!-- end row-->
</div>
<?php
	} else {
		print "You do not have access to view this page";
	}
?>
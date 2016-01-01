<?php
	$t_item = $this->getVar("item");
	$va_comments = $this->getVar("comments");
	$va_access_values = caGetUserAccessValues($this->request);
?>
<div class="row">
	<div class='col-xs-12 navTop'><!--- only shown at small screen size -->
		{{{previousLink}}}{{{resultsLink}}}{{{nextLink}}}
	</div><!-- end detailTop -->
	<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'>
		<div class="container">
			<div class="row">
				<div class='col-sm-1 col-md-1 col-lg-1'>
					<div class="detailNavBgLeft">
						{{{previousLink}}}{{{resultsLink}}}
					</div><!-- end detailNavBgLeft -->
				</div>
				<div class='col-sm-10 col-md-10 col-lg-10'>
					<div class="detailHead">
<?php
					print "<div class='leader'>".$t_item->get('ca_occurrences.type_id', array('convertCodesToDisplayText' => true))."</div>";
					print "<h2>".$t_item->get('ca_occurrences.preferred_labels')."</h2>";
					if ($va_event_date = $t_item->get('ca_occurrences.productionDate', array('delimiter' => ', '))) {
						print "<h3>".$va_event_date."</h3>";
					}					
?>			
				</div><!-- end detailHead -->
				</div><!-- end col -->
				<div class='col-sm-1 col-md-1 col-lg-1'>
					<div class="detailNavBgRight">
						{{{nextLink}}}
					</div><!-- end detailNavBgLeft -->
				</div>				
			</div><!-- end row -->
			
			<div class="row" id="dataPanel">
			<hr class="divide"/>			
				<div class='col-sm-6 col-md-6 col-lg-6'>
<?php
					if ($vs_non_preferred = $t_item->get('ca_occurrences.nonpreferred_labels', array('delimiter' => ', '))) {
						print "<div class='unit'><span class='label'>Alternate Title </span>".$vs_non_preferred."</div>";
					}
					if ($vs_premiere = $t_item->get('ca_occurrences.premiere', array('convertCodesToDisplayText' => true))) {
						if ($vs_premiere != " ") {
							print "<div class='unit'><span class='label'>Premiere </span>".$vs_premiere."</div>";
						}
					}
					if (($vs_language = $t_item->get('ca_occurrences.productionLanguage', array('convertCodesToDisplayText' => true))) != "null") {
						print "<div class='unit'><span class='label'>Production Language </span>".$vs_language."</div>";
					}	
					if ($vs_country = $t_item->get('ca_occurrences.country_origin', array('convertCodesToDisplayText' => true, 'delimiter' => ', '))) {
						print "<div class='unit'><span class='label'>Country of Origin </span>".$vs_country."</div>";
					}
					if ($va_description = $t_item->get('ca_occurrences.productionDescription.prodesc_text')) {
						print "<div class='unit'><span class='label'>Description </span>".$va_description."</div>";
					}
					#if ($va_description_source = $t_item->get('ca_occurrences.productionDescription.prodesc_source')) {
					#	print "<div class='unit'><span class='label'>Description source: </span>".$va_description_source."</div>";
					#}																				
?>
					<!--<div id="detailTools">
						<div class="detailTool"><a href='#' onclick='jQuery("#detailComments").slideToggle(); return false;'><span class="glyphicon glyphicon-comment"></span>Comments (<?php print sizeof($va_comments); ?>)</a></div>
						<div id='detailComments'>{{{itemComments}}}</div>
						<div class="detailTool"><span class="glyphicon glyphicon-share-alt"></span>{{{shareLink}}}</div>
					</div> -->
					
				</div><!-- end col -->
				<div class='col-sm-6 col-md-6 col-lg-6'>
<?php
					if ($vn_parent_id = $t_item->get('ca_occurrences.parent.occurrence_id', array('checkAccess' => $va_access_values))) {
						$t_parent = new ca_occurrences($vn_parent_id);
						if ($vs_season = $t_parent->get('ca_occurrences.parent.preferred_labels')) {
							print "<div class='unit'><span class='label'>Season </span>".caNavLink($this->request, $vs_season, '', '', '', 'Search/objects/search/"'.$vs_season.'"')."</div>";
						}
					}
					if ($vs_venue = $t_item->get('ca_occurrences.venue', array('convertCodesToDisplayText' => true))) {
						if ($vs_venue != " ") {
							print "<div class='unit'><span class='label'>Venue</span>".caNavLink($this->request, $vs_venue, '', '', '', 'Search/objects/search/"'.$vs_venue.'"')."</div>";
						}
					}
					if ($va_related_entities = $t_item->get('ca_entities', array('returnWithStructure' => true, 'checkAccess' => $va_access_values, 'excludeRelationshipTypes' => array('principal_artist')))) {
						$va_entity_list = array();
						foreach ($va_related_entities as $va_entity_id => $va_related_entity) {
							$va_entity_list[$va_related_entity['relationship_typename']][$va_related_entity['entity_id']][] = caNavLink($this->request, $va_related_entity['displayname'], '', '', 'Detail', 'entities/'.$va_related_entity['entity_id']);
						}
						
						foreach ($va_entity_list as $va_role => $va_entity) {
							print "<div class='unit'><span class='label'>".$va_role."</span>";
							$va_entity_links = array();
							foreach($va_entity as $va_entity_role => $va_entity_link) {
								foreach ($va_entity_link as $va_key => $va_entity) {
									$va_entity_links[] = $va_entity;
								}
							}
							print join(', ', $va_entity_links);
							print "</div>";
						}
					}									
?>				
				</div><!-- end col -->
			</div><!-- end row -->

{{{<ifcount code="ca_objects" min="1">
			<hr class="divide" style="margin-bottom:0px;"/>
			<div class="container"><div class="row">
				
				<div id="browseResultsContainer">
					<?php print caBusyIndicatorIcon($this->request).' '.addslashes(_t('Loading...')); ?>
				</div><!-- end browseResultsContainer -->
			</div><!-- end row --></div><!-- end container -->
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#browseResultsContainer").load("<?php print caNavUrl($this->request, '', 'Search', 'objects', array('detailNav' => '1', 'openResultsInOverlay' => 1, 'search' => 'occurrence_id:^ca_occurrences.occurrence_id'), array('dontURLEncodeParameters' => true)); ?>", function() {
						jQuery('#browseResultsContainer').jscroll({
							autoTrigger: true,
							loadingHtml: '<?php print caBusyIndicatorIcon($this->request).' '.addslashes(_t('Loading...')); ?>',
							padding: 20,
							nextSelector: 'a.jscroll-next'
						});
					});
					
					
				});
			</script>
</ifcount>}}}		</div><!-- end container -->
	</div><!-- end col -->
</div><!-- end row -->
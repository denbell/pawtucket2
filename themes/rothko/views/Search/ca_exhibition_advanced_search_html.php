<div class="container">
	<div class="row">
		<div class="col-sm-8 " >
			<h1>Advanced Exhibition Search <small>or search <?php print caNavLink($this->request, 'provenance', '', 'Search', 'advanced', 'provenance');?>, <?php print caNavLink($this->request, 'references', '', 'Search', 'advanced', 'references');?>, or <?php print caNavLink($this->request, 'works', '', 'Search', 'advanced', 'artworks');?></small></h1>

<?php			
	print "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc pharetra venenatis lorem, sit amet ornare tortor molestie quis. Ut commodo in elit sit amet lacinia. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nulla facilisi. Proin iaculis at nisl nec ultricies. Vivamus commodo commodo dui nec efficitur. </p>";
?>

{{{form}}}
	
	<div class='advancedContainer'>
		<div class='row'>
			<div class="advancedSearchField col-sm-12">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Search across all fields in the catalog.">Keyword</span>
				{{{_fulltext%width=200px&height=1}}}
			</div>			
		</div>		
		<div class='row'>
			<div class="advancedSearchField col-sm-12">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Limit your search to exhibition titles only.">Exhibition Title</span>
				{{{ca_occurrences.preferred_labels.name%width=220px&height=1}}}
			</div>
		</div>
		<div class='row'>
			<div class="advancedSearchField col-sm-12">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Limit your search to related object titles only.">Object Title</span>
				{{{ca_objects.preferred_labels.name%width=220px&height=1}}}
			</div>
		</div>		
		<div class='row'>
			<div class="advancedSearchField col-sm-6">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Search by exhibition venues.">Venue</span>
				{{{ca_entities.preferred_labels%width=210px&height=1&restrictToRelationshipTypes=venue}}}
			</div>
			<div class="advancedSearchField col-sm-6">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Search by exhibition dates.">Date</span>
				{{{ca_occurrences.occurrence_dates%height=30px&height=1}}}
			</div>
		</div>
		<div class='row'>
			<div class="advancedSearchField col-sm-12">
				<span class='formLabel' data-toggle="popover" data-trigger="hover" data-content="Search exhibitions by location.">Location</span>
				{{{ca_places.preferred_labels.name%width=220px&height=1}}}
			</div>
		</div>					
		<br style="clear: both;"/>
		<div class='advancedFormSubmit'>
			<span class='btn btn-default'>{{{reset%label=Reset}}}</span>
			<span class='btn btn-default' style="margin-left: 10px;">{{{submit%label=Search}}}</span>
		</div>
	</div>	

{{{/form}}}

		</div>
		<div class="col-sm-4" >

		</div><!-- end col -->
	</div><!-- end row -->
</div><!-- end container -->

<script>
	jQuery(document).ready(function() {
		$('.advancedSearchField .formLabel').popover(); 
	});
	
</script>
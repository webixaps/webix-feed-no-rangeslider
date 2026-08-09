(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */


	 $(function() {

		/** JS for Metabox Tab */
		$(".tablinks").click(function(){
			var tab = $(this).data("tab");
			$(".tablinks").removeClass("active");
			$(".tabcontent").removeClass("active");
			$(this).addClass("active");
			$("#"+tab).addClass("active");
		});
		/** JS for Find SubCategories */

		$(".find_subcategories").change(function(){
			$("#webix-feed-subcategory").hide();
			$("#webix-feed-subcategory").empty();
            var country_id = $("#webix-country").val();
            var feed_category_id = $("#webix-feed-category").val();
            $.ajax({
                url: "https://ai.webixaps.com/find_subcategories",
                type: "get", //send it through get method
                dataType: "json",
                data: {
                    country_id: country_id,
                    feed_category_id: feed_category_id
                },
                success: function(response) {


                    if (response.length > 0){
                    var selOpts;

                    $.each(response, function(k, v){
                        var id = v.id;
                        var val = v.feed_subcategory_name;

                        selOpts += "<option value='"+id+"'>"+val+"</option>";
                    });

                    $('#webix-feed-subcategory').append(selOpts);
                    }

                    //Do Something
                    $("#webix-feed-subcategory").show();
                },
                error: function(xhr) {
                    //Do Something to handle error
                    $("#webix-feed-subcategory").show();
                }
            });
        });


		 $(".find_campaigns").change(function(){
			 $("#webix-feed-campaign").hide();
			 $("#webix-feed-campaign").empty();
			 var country_id = $("#webix-feed-campaign-country").val();
			 var feed_category_id = $("#webix-feed-campaign-category").val();
			 $.ajax({
				 url: "https://ai.webixaps.com/find_campaigns",
				 type: "get", //send it through get method
				 dataType: "json",
				 data: {
					 country_id: country_id,
					 feed_category_id: feed_category_id
				 },
				 success: function(response) {
					 if (response.length > 0){
						 var selOpts;
						 $.each(response, function(k, v){
							 var id = v.id;
							 var val = v.name;

							 selOpts += "<option value='"+id+"'>"+val+"</option>";
						 });
						 $('#webix-feed-campaign').append(selOpts);
					 }
					 //Do Something
					 $("#webix-feed-campaign").show();
				 },
				 error: function(xhr) {
					 //Do Something to handle error
					 $("#webix-feed-campaign").show();
				 }
			 });
		 });
	 });

})( jQuery );

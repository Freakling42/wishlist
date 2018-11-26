function td_saveWishList(){
			var v_NewListName = document.getElementById("TDwishlistname").value;

			/* This does the ajax request */
			jQuery.ajax({
				url: ajax_object.ajaxurl,
				type : 'post',
				data: {
					'action':'TD_saveWishList',
					'NewListName' : v_NewListName
				},
				success:function(data) {
					/* This outputs the result of the ajax request */
					console.log(data);
//					sp_displayAllFavLists(2);
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});
}
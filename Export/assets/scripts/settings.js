// ----------------------- Search system -----------------------------------------------
$(document).ready(function(){
	base_url =getbaseurl();
	
	$(document).on("keydown", function (e) { if (e.keyCode == 70 && e.ctrlKey) { 
			event.preventDefault();
			$(".select2-drop").css("zoom","0 !important");
			$(".select2-drop-active").css("zoom","0");
			//search_criteria =  $("#search_criteria").find(':selected').data('cat');
			var stock_type = $("#search_criteria").find(":selected").val();
			search_criteria = '';
			jQuery.getScript(base_url+"assets/scripts/select2-3.4.8/select2.js")//Load select 2 scripts
				.done(function() {
					
					$("#search_option").removeAttr("disabled");
					$("#search_criteria").removeAttr("disabled");
					$("#search_option").select2(select2Options())
					.on("change", function(e) {
					  // mostly used event, fired to the original element when the value changes
			          //console.log("change val=" + e.val);
			        })
			        .on("select2-opening", function() {
			          //console.log("opening");
			        })
			        .on("select2-open", function() {
			          // fired to the original element when the dropdown opens
			          console.log("open");
			          //alert(search_criteria)
			        })
			        .on("select2-close", function() {
			          // fired to the original element when the dropdown closes
			          //console.log("close");
			        })
			        .on("select2-highlight", function(e) {
			          //console.log("highlighted val=" + e.val + " choice=" + e.choice.text);
			        })
			        .on("select2-selecting", function(e) {
			          //console.log("selecting val=" + e.val + " choice=" + e.object.text);
			        })
			        .on("select2-removed", function(e) {
			          //console.log("removed val=" + e.val + " choice=" + e.choice.text);
			        })
			        .on("select2-loaded", function(e) {
			          //console.log("loaded (data property omitted for brevitiy)");
			        })
			        .on("select2-focus", function(e) {
			          
			        });
					
				})
				.fail(function() {
					alert('Failed to load select2 scripts')
				});
				
			$("#search_criteria").select2({
				placeholder: "Select search option"
			}).on("select2-close", function() {
	          // fired to the original element when the dropdown closes
	          select2Options().ajax = {
	          	url:generateUrl(),
				data: function (term, page) {
					return {
						q : term
					};
				}, results: function (data, page) {
					return {
						results : data
					};
				}

	          }
	          $("#search_option").select2("destroy").select2(select2Options());
	        });
			$("#searchModal").modal('show');
		} 
	
	});
	function generateUrl(){
		search_criteria =  $("#search_criteria").find(':selected').data('cat');
		base_url = getbaseurl();
		var _url =  base_url+"system_management/search_system/"+search_criteria+"/"+$("#search_criteria").find(":selected").val();
		return _url;
	}
	
	function select2Options(){
		var options = {
			minimumInputLength: 2,
		    ajax: {
		      url:generateUrl(),
		      data: function (term, page) {
		        return {
		          q: term
		        };
		      },
		      results: function (data, page) {
		        return { results: data };
		      }
	        }
		}
		return options;
	}
	
	//Search form submission
	$("#fmSearchSystem").submit(function(){
		var search_criteria = $("#search_criteria").find(":selected").data("cat");//Category selected
		var search_option = $("#search_option").val();
		var destination_link ="";
		var link = $("#search_criteria").find(":selected").data("dest")//Get destination link
		
		if($.trim(search_option)==''){
			return false;
		}else{
			if(search_criteria=='patient'){
				destination_link =base_url+link+search_option;
			}else if(search_criteria=='drugcode'){
				//If inventory, get stock type
				var stock_type = $("#search_criteria").find(":selected").data("id");
				destination_link =base_url+link+search_option+'/'+stock_type;
			}
			
			$("#fmSearchSystem").attr("action",destination_link);
			return true;
		}
		
		
	})
})

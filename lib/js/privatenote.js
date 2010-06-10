jQuery(document).ready(function(){
	jQuery("#addprivatenote, #modifprivatenote").click(function(){
		jQuery(".note").html('<form class="cmxform" action="" method="post"><textarea name="forumsParam[privatenote]" class="jTagEditor-editor" cols="60" rows="5" id="privatenotetxt">'+(typeof(privateNote) == 'undefined' ? '' : privateNote)+'</textarea><input id="privatenotebt" class="button" type="submit" value="OK"/></form>');
		jQuery("#privatenotetxt").jTagEditor();
	});
	jQuery("#showprivatenote").click(function(){
		jQuery(this).addClass('hidden');
		jQuery("#hideprivatenote").removeClass('hidden');
		jQuery("#privatenotevalue").removeClass('hidden');
		jQuery("#modifprivatenote").removeClass('hidden');
	});
	jQuery("#hideprivatenote").click(function(){
		jQuery(this).addClass('hidden');
		jQuery("#showprivatenote").removeClass('hidden');;
		jQuery("#privatenotevalue").addClass('hidden');
		jQuery("#modifprivatenote").addClass('hidden');
	});
});
$(document).ready(function(){
	$("#addprivatenote, #modifprivatenote").click(function(){
		$(".note").html('<form class="cmxform" action="" method="post"><textarea name="forumsParam[privatenote]" class="jTagEditor-editor" cols="60" rows="5" id="privatenotetxt">'+(typeof(privateNote) == 'undefined' ? '' : privateNote)+'</textarea><input id="privatenotebt" class="button" type="submit" value="OK"/></form>');
		$("#privatenotetxt").jTagEditor();
	});
	$("#showprivatenote").click(function(){
		$(this).addClass('hidden');
		$("#hideprivatenote").removeClass('hidden');
		$("#privatenotevalue").removeClass('hidden');
		$("#modifprivatenote").removeClass('hidden');
	});
	$("#hideprivatenote").click(function(){
		$(this).addClass('hidden');
		$("#showprivatenote").removeClass('hidden');;
		$("#privatenotevalue").addClass('hidden');
		$("#modifprivatenote").addClass('hidden');
	});
});
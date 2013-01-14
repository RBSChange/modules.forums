jQuery(document).ready(function(){
	jQuery("a.postDelete").click(function(){
		if (confirm('${trans:m.forums.frontoffice.confirmdeletepost,ucf}'))
		{
			return true;
		}
		return false;
	});
	jQuery("a.closeThread").click(function(){
		if (confirm('${trans:m.forums.frontoffice.confirmclosethread,ucf}'))
		{
			return true;
		}
		return false;
	});
	jQuery("a.openThread").click(function(){
		if (confirm('vforums.frontoffice.confirmopenthread,ucf}'))
		{
			return true;
		}
		return false;
	});
});
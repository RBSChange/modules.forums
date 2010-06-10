jQuery(document).ready(function(){
	jQuery("a.postDelete").click(function(){
		if(confirm('&modules.forums.frontoffice.Confirmdeletepost;'))
		{
			return true;
		}
		return false;
	});
	jQuery("a.closeThread").click(function(){
		if(confirm('&modules.forums.frontoffice.Confirmclosethread;'))
		{
			return true;
		}
		return false;
	});
	jQuery("a.openThread").click(function(){
		if(confirm('&modules.forums.frontoffice.Confirmopenthread;'))
		{
			return true;
		}
		return false;
	});
});
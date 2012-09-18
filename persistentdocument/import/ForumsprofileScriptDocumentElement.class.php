<?php
/**
 * forums_ForumsprofileScriptDocumentElement
 * @package modules.forums.persistentdocument.import
 */
class forums_ForumsprofileScriptDocumentElement extends users_ProfileScriptDocumentElement
{
	/**
	 * @return forums_persistentdocument_forumsprofilemodel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_forums/forumsprofile');
	}
}
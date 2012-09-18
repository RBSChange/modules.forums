<?php
/**
 * forums_patch_0401
 * @package modules.forums
 */
class forums_patch_0401 extends change_Patch
{
	/**
	 * @return array
	 */
	public function getPreCommandList()
	{
		return array(
			array('disable-site'),
			array('compile-documents'),
			array('generate-database'),
		);
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->fixPages();
				
		// At this time, users should not have any forumsprofile.
		forums_ForumsprofileService::getInstance()->createQuery()->delete();
				
		// Convert existing members to forumsprofiles, preserving ids.
		$this->executeSQLQuery("INSERT INTO m_users_doc_profile (document_id, document_model, document_label, document_author, document_authorid, document_creationdate, document_modificationdate, document_publicationstatus, document_lang, document_modelversion, document_startpublicationdate, document_endpublicationdate, document_metas, document_version, forummembertitle, accessorId) SELECT document_id, 'modules_forums/forumsprofile' AS document_model, document_label, document_author, document_authorid, document_creationdate, document_modificationdate, document_publicationstatus, document_lang, document_modelversion, document_startpublicationdate, document_endpublicationdate, document_metas, 0, title, user FROM m_forums_doc_member;");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_forums/forumsprofile' WHERE document_model = 'modules_forums/member';");
		$delRelId = $this->getPersistentProvider()->getRelationId('user');
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/member' AND relation_id = $delRelId;");
		$delRelId = $this->getPersistentProvider()->getRelationId('country');
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/member' AND relation_id = $delRelId;");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_forums/forumsprofile' WHERE document_model_id1 = 'modules_forums/member';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_forums/forumsprofile' WHERE document_model_id2 = 'modules_forums/member';");
		$oldRelId = $this->getPersistentProvider()->getRelationId('title');
		$newRelId = $this->getPersistentProvider()->getRelationId('forumMemberTitle');
		$this->executeSQLQuery("UPDATE f_relation SET relation_id = $newRelId, relation_name = 'forumMemberTitle' WHERE document_model_id1 = 'modules_forums/forumsprofile' AND relation_id = $oldRelId;");
		
		// Update bans stucture.
		$delRelId = $this->getPersistentProvider()->getRelationId('by'); // Deleted
		$this->executeSQLQuery("ALTER TABLE m_forums_doc_ban DROP COLUMN `by`;");
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/ban' AND relation_id = $delRelId;");
		
		// Update posts stucture.
		$delRelId = $this->getPersistentProvider()->getRelationId('editedby'); // Converted to DocumentId
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/post' AND relation_id = $delRelId;");
		$delRelId = $this->getPersistentProvider()->getRelationId('deletedby'); // Converted to DocumentId
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/post' AND relation_id = $delRelId;");
		$delRelId = $this->getPersistentProvider()->getRelationId('postauthor'); // Deleted
		$this->executeSQLQuery("UPDATE m_forums_doc_post SET document_authorid = postauthor;");
		$this->executeSQLQuery("ALTER TABLE m_forums_doc_post DROP COLUMN postauthor;");
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/post' AND relation_id = $delRelId;");
		
		// Update thread stucture.
		$delRelId = $this->getPersistentProvider()->getRelationId('privatenoteby'); // Converted to DocumentId
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/thread' AND relation_id = $delRelId;");
		$delRelId = $this->getPersistentProvider()->getRelationId('threadauthor'); // Deleted
		$this->executeSQLQuery("UPDATE m_forums_doc_thread SET document_authorid = threadauthor;");
		$this->executeSQLQuery("ALTER TABLE m_forums_doc_thread DROP COLUMN threadauthor;");
		$this->executeSQLQuery("DELETE FROM f_relation WHERE document_model_id1 = 'modules_forums/thread' AND relation_id = $delRelId;");
		
		$this->executeSQLQuery("TRUNCATE f_cache;");
		
		// Dispatch data from members.
		$statement = $this->executeSQLSelect("SELECT * FROM m_forums_doc_member;");
		$statement->execute();
		$memberInfos = $statement->fetchAll();
		foreach ($memberInfos as $memberInfo)
		{
			// Update the user label.
			$user = users_persistentdocument_user::getInstanceById($memberInfo['user']);
			$user->setLabel($memberInfo['document_label']);
			$user->save();
			
			// Update the user profile.
			$ups = users_UsersprofileService::getInstance();
			$usersprofile = $ups->getByAccessorId($memberInfo['user']);
			if ($usersprofile == null)
			{
				$usersprofile = $ups->getNewDocumentInstance();
				$usersprofile->setAccessor($user);
			}
			
			$usersprofile->setDisplayname($memberInfo['displayname'] === '1');
			if ($memberInfo['websiteurl'])
			{
				$usersprofile->setPersonnalwebsiteurl($memberInfo['websiteurl']);
			}
			if ($memberInfo['country'])
			{
				$country = zone_persistentdocument_country::getInstanceById($memberInfo['country']);
				$usersprofile->setLocation($country->getLabel());
			}
			if ($memberInfo['shortdateformat'])
			{
				$usersprofile->setDateformat($memberInfo['shortdateformat']);
			}
			if ($memberInfo['longdateformat'])
			{
				$usersprofile->setDatetimeformat($memberInfo['longdateformat']);
			}
			$usersprofile->save();
			
			// Create the forum profile.
			$fps = forums_ForumsprofileService::getInstance();
			$forumsprofile = $fps->getByAccessorId($memberInfo['user']);
			if ($forumsprofile == null)
			{
				$forumsprofile = $fps->getNewDocumentInstance();
			}
			$forumsprofile->setAccessor($user);
			
			$forumsprofile->setBan($memberInfo['ban']);
			$forumsprofile->setLastAllRead($memberInfo['lastallread']);
			$forumsprofile->setTrackingByForum($memberInfo['trackingbyforum']);
			$forumsprofile->setTrackingByThread($memberInfo['trackingbythread']);
			$forumsprofile->setSignature($memberInfo['signature']);
			$forumsprofile->save();
		}
		
		// Remplace members by users in bans.
		foreach (forums_BanService::getInstance()->createQuery()->find() as $ban)
		{
			/* @var $ban forums_persistentdocument_ban */
			$member = $ban->getMember();
			if ($member instanceof forums_persistentdocument_forumsprofile)
			{
				$ban->setMember($member->getAccessorIdInstance());
			}
			
			$ban->save();
		}
		
		// Remplace members by users in posts.
		foreach (forums_PostService::getInstance()->createQuery()->find() as $post)
		{
			/* @var $ban forums_persistentdocument_post */
			$author = $post->getAuthoridInstance();
			if ($author instanceof forums_persistentdocument_forumsprofile)
			{
				$post->setAuthorid($author->getAccessorId());
				$post->setAuthor($author->getAccessorIdInstance()->getLabel());
			}
			
			$editedby = $post->getEditedbyInstance();
			if ($editedby instanceof forums_persistentdocument_forumsprofile)
			{
				$post->setEditedby($editedby->getAccessorId());
			}
			
			$deletedby = $post->getDeletedbyInstance();
			if ($deletedby instanceof forums_persistentdocument_forumsprofile)
			{
				$post->setDeletedby($deletedby->getAccessorId());
			}
			
			$post->save();
		}
		
		// Remplace members by users in threads.
		foreach (forums_ThreadService::getInstance()->createQuery()->find() as $thread)
		{
			/* @var $ban forums_persistentdocument_post */
			$author = $thread->getAuthoridInstance();
			if ($author instanceof forums_persistentdocument_forumsprofile)
			{
				$thread->setAuthorid($author->getAccessorId());
				$thread->setAuthor($author->getAccessorIdInstance()->getLabel());
			}
			
			$privatenoteby = $thread->getPrivatenotebyInstance();
			if ($privatenoteby instanceof forums_persistentdocument_forumsprofile)
			{
				$thread->setPrivatenoteby($privatenoteby->getAccessorId());
			}
			
			$followers = $thread->getFollowersArray();
			foreach ($followers as $follower)
			{
				if ($follower instanceof forums_persistentdocument_forumsprofile)
				{
					$thread->removeFollowers($follower);
					$thread->addFollowers($follower->getAccessorIdInstance());
				}
			}
			
			$thread->save();
		}
		
		// Delete rewriting rule definitions for posts.
		$rule = website_RewriteruleService::getInstance()->getByModelName('modules_forums/member');
		$rule->getDocumentService()->purgeDocument($rule);
	}
	
	private function fixPages()
	{
		$ts = TagService::getInstance();
		$rc = RequestContext::getInstance();
		
		$tm = f_persistentdocument_TransactionManager::getInstance();
		try
		{
			$tm->beginTransaction();
					
			// Delete old editprofile page.
			foreach ($ts->getDocumentsByTag('contextual_website_website_modules_forums_editprofile') as $doc)
			{
				if (!($doc instanceof website_persistentdocument_pagereference))
				{
					$doc->getDocumentService()->purgeDocument($doc);
				}
			}
			
			// Replace member member list tag.
			foreach ($ts->getDocumentsByTag('contextual_website_website_modules_forums_memberlist') as $doc)
			{
				if (!($doc instanceof website_persistentdocument_pagereference))
				{
					$ts->removeTag($doc, 'contextual_website_website_modules_forums_memberlist');
					$ts->addTag($doc, 'contextual_website_website_modules_users_userslist');
				}
			}
			
			// Replace member detail block and tag.
			foreach ($ts->getDocumentsByTag('contextual_website_website_modules_forums_member') as $doc)
			{
				if (!($doc instanceof website_persistentdocument_pagereference))
				{
					$ts->removeTag($doc, 'contextual_website_website_modules_forums_member');
					$ts->addTag($doc, 'contextual_website_website_modules_users_user');
					
					// Get languages for page
					foreach ($doc->getI18nInfo()->getLangs() as $lang)
					{
						try
						{
							$rc->beginI18nWork($lang);
							
							if ($doc->getPublicationstatus() == 'DEPRECATED')
							{
								$rc->endI18nWork();
								continue;
							}
							
							$dom = f_util_DOMUtils::fromString($doc->getContent());
							$blocks = $dom->getElementsByTagNameNS(website_PageService::CHANGE_PAGE_EDITOR_NS, 'block');
							$modified = false;
							foreach ($blocks as $block)
							{
								if (strtolower($block->getAttribute('type')) == 'modules_forums_member')
								{
									$block->setAttribute('type', 'modules_users_User');
									$modified = true;
								}					
							}
							
							if ($modified)
							{
								$doc->setContent($dom->saveXML());
								$from = array('{forums_member.login}', '{forums_member.registrationdate}');
								$to = array('{users_user.label}', '{users_user.registrationdate}');
								if ($doc->getMetatitle()) { $doc->setMetatitle(str_replace($from, $to, $doc->getMetatitle())); }
								if ($doc->getDescription()) { $doc->setDescription(str_replace($from, $to, $doc->getDescription())); }
								if ($doc->getKeywords()) { $doc->setKeywords(str_replace($from, $to, $doc->getKeywords())); }
								f_persistentdocument_PersistentProvider::getInstance()->updateDocument($doc);
							}
							
							$rc->endI18nWork();
						}
						catch (Exception $e)
						{
							$rc->endI18nWork($e);
						}
					}
				}
			}
			$tm->commit();
		}
		catch (Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
	
	/**
	 * @return array
	 */
	public function getPostCommandList()
	{
		return array(
			array('clear-documentscache'),
			array('enable-site'),
		);
	}
	
	/**
	 * @return string
	 */
	public function getExecutionOrderKey()
	{
		return '2011-10-26 15:22:56';
	}
		
	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return dirname(__FILE__);
	}
	
	/**
	 * @return false
	 */
	public function isCodePatch()
	{
		return false;
	}
}
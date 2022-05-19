<?php

use srag\DIC\Tst2Lrs\DICTrait;
use spyfly\Plugins\Tst2Lrs\Utils\Tst2LrsTrait;

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class iltst2lrsPlugin
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Sebastian Heiden <tst2lrs@spyfly.xyz>
 */
class ilTst2LrsXapiStatement implements JsonSerializable
{
	use Tst2LrsTrait;
    use DICTrait;

	protected static $XAPI_VERBS = [
		'http://adlnet.gov/expapi/verbs/failed' => 'failed',
		'http://adlnet.gov/expapi/verbs/completed' => 'completed',
		'http://adlnet.gov/expapi/verbs/attempted' => 'attempted'
	];
	
	protected static $VERBS_BY_EVENT_TYPE = [
		'finishTestPass' => 'http://adlnet.gov/expapi/verbs/completed',
	];
	
	
	protected static $RELEVANT_PARENTS = ['cat', 'crs', 'grp', 'root'];
	
	const CATEGORY_DEFINITION_TYPE_TAG = 'http://id.tincanapi.com/activitytype/tag';
	
	const DEFAULT_LOCALE = 'en-US';
	
	/**
	 * @var ilCmiXapiLrsType
	 */
	protected $lrsType;
	
	/**
	 * @var ilObject
	 */
	protected $object;
	
	/**
	 * @var ilObjUser
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $event_type;
	
	/**
	 * ilTst2LrsXapiStatement constructor.
	 * @param ilCmiXapiLrsType|ilXapiCmi5Type  $lrsType
	 * @param ilObject $object
	 * @param ilObjUser $user
	 */
	public function __construct(
		$lrsType,
		ilObject $object,
		ilObjUser $user,
		string $event_type,
		$pass_details,
		$test_details
	)
	{
		$this->lrsType = $lrsType;
		$this->object = $object;
		$this->user = $user;
		$this->event_type = $event_type;
		$this->pass_details = $pass_details;
		$this->test_details = $test_details;
	}
	
	/**
	 * @return string
	 */
	protected function buildTimestamp()
	{
		$timestamp = new ilCmiXapiDateTime($this->test_details['result_tstamp'], IL_CAL_UNIX);
		return $timestamp->toXapiTimestamp();
	}
	
	/**
	 * @return array
	 */
	protected function buildActor()
	{
		if(isset(array_flip(get_class_methods($this->lrsType))['getPrivacyName'])) //ILIAS 7
		{
			$identMode = $this->lrsType->getPrivacyIdent();
			$nameMode = $this->lrsType->getPrivacyName();
		} else {
			$identMode = $this->lrsType->getUserIdent();
			$nameMode = $this->lrsType->getUserName();
		}
		return [
			'objectType' => 'Agent',
        	'mbox' => 'mailto:'.ilCmiXapiUser::getIdent($identMode ,$this->user),
        	'name' => ilCmiXapiUser::getName($nameMode ,$this->user)
		];
	}
	
	/**
	 * @return array
	 */
	protected function buildVerb()
	{
		return [
			'id' => $this->getVerbId(),
			'display' => [ $this->getLocale() => $this->getVerbName() ]
		];
	}
	
	protected function hasResult()
	{
		return $this->pass_details !== null;
	}
	
	/**
	 * @return array
	 */
	protected function buildResult()
	{
		return [
			'score' => [
				'scaled' => $this->pass_details['percent'],
				'raw' => $this->pass_details['total_reached_points'],
				'min' => 0,
				'max' => $this->pass_details['total_max_points'],
			],
			'completion' => $this->event_type === 'finishTestPass',
			'success' => $this->test_details['passed'] == 1
		];
	}
	
	/**
	 * @return array
	 */
	protected function buildObject()
	{
		return $this->getObjectProperties($this->object);
	}
	
	/**
	 * @return array
	 */
	protected function buildContext()
	{
		$context = [
			'contextActivities' => []
		];
		
		$parent = $this->getContextParent($this->object);
		
		if( $parent )
		{
			self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | xApiContextParent '.json_encode($parent));
			$context['contextActivities']['parent'] = $this->getObjectProperties($parent);
		}

		$categories = $this->getObjectCategories($this->object);
		if( $categories )
		{
			self::dic()->logger()->root()->info('DEBUG-Tst2Lrs | xApiContextCategories '.json_encode($parent));
            $context['contextActivities']['category'] = $categories;
        }
		
		return $context;
	}
	
	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		$statement = [];
		
		$statement['timestamp'] = $this->buildTimestamp();
		
		$statement['actor'] = $this->buildActor();
		
		$statement['verb'] = $this->buildVerb();
		
		if( $this->hasResult() )
		{
			$statement['result'] = $this->buildResult();
		}
		
		$statement['object'] = $this->buildObject();
		
		$statement['context'] = $this->buildContext();
		
		return $statement;
	}
	
	/**
	 * @return string
	 */
	protected function getVerbId()
	{
		return self::$VERBS_BY_EVENT_TYPE[$this->event_type];
	}
	
	/**
	 * @return string
	 */
	protected function getVerbName()
	{
		return self::$XAPI_VERBS[$this->getVerbId()];
	}
	
	/**
	 * @return string
	 */
	protected function getObjectType(ilObject $object)
	{
		switch( $object->getType() )
		{
			case 'cat':
			case 'crs':
			case 'grp':
			case 'fold':
            case 'root':
				return 'Group';
			
			default:
				return 'Activity';
		}
	}
	
	/**
	 * @return string
	 */
	protected function getObjectId(ilObject $object)
	{
		switch( $object->getType() )
		{
			case 'cmix':
				
				/* @var ilObjCmiXapi $object */
				
				if( strlen($object->getActivityId()) )
				{
					return $object->getActivityId();
				}
				else
				{
					return $this->getFallbackObjectId($object);
				}
				
			case 'lti':
				
				/* @var ilObjLTIConsumer $object */

				if( $object->getUseXapi() && strlen($object->getActivityId()) )
				{
					return $object->getActivityId();
				}
				else
				{
					return $this->getFallbackObjectId($object);
				}
				
			default:
				
				return $this->getFallbackObjectId($object);
		}
	}
	
	/**
	 * @return string
	 */
	protected function getFallbackObjectId(ilObject $object)
	{
		$settings = new ilSetting('cmix');
		$iliasUid = $settings->get('ilias_uuid');
		
		return 'http://ilias.local/'.$iliasUid.'/'.$object->getId();
	}
	
	/**
	 * @return string
	 */
	protected function getObjectDefinitionType(ilObject $object)
	{
		switch($object->getType())
		{
			case 'cat':
				
				return 'http://id.tincanapi.com/activitytype/category';
				
			case 'crs':
			case 'grp':
				
				return 'http://adlnet.gov/expapi/activities/course';
		}
		
		return 'http://adlnet.gov/expapi/activities/module';
	}
	
	/**
	 * @return string
	 */
	protected function getObjectMoreInfo(ilObject $object)
	{
		return ilLink::_getLink($object->getRefId(), $object->getType());
	}
	
	protected function getObjectProperties(ilObject $object)
	{
		$objectProperties = [
			'id' => $this->getObjectId($object),
                'definition' => [
                    'name' => [$this->getLocale() => $object->getTitle()],
                    'type' => $this->getObjectDefinitionType($object)
                ]
		];
        if( $object->getDescription() != '')
        {
            $objectProperties['definition']['description'] = [$this->getLocale() => $object->getDescription()];
        }

		if( $object->getRefId() )
		{
			$objectProperties['definition']['moreInfo'] = $this->getObjectMoreInfo($object);
		}
		
		return $objectProperties;
	}
	
	/**
	 * @param ilObject $object
	 * @return bool|object|null
	 */
	protected function getContextParent(ilObject $object)
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		if( !$object->getRefId() )
		{
			return null;
		}

		$parents = self::$RELEVANT_PARENTS;
		if( $object->getType() == 'crs' )
        {
            $parents = ['cat', 'root'];
        }

		$pathNodes = array_reverse($DIC->repositoryTree()->getNodePath($object->getRefId()));
		
		foreach($pathNodes as $nodeData)
		{
			if( !in_array($nodeData['type'], $parents) )
			{
				continue;
			}
			
			return ilObjectFactory::getInstanceByObjId($nodeData['obj_id'], false);
		}
		
		return null;
	}
	
	/**
	 * @param ilObject $object
	 * @return array
	 */
	protected function getObjectCategories(ilObject $object)
	{
		$categories = [];
		
		foreach($this->getKeywords($object) as $keyword)
		{
			$categories[] = [
				'id' => 'http://ilias.local/keyword/'.rawurlencode($keyword),
				'definition' => [
				    'name' => [$this->getLocale() => $keyword],
					'type' => self::CATEGORY_DEFINITION_TYPE_TAG
				]
			];
		}
		
		return $categories;
	}
	
	/**
	 * @param ilObject $object
	 * @return array
	 */
	protected function getKeywords(ilObject $object)
	{
		$keywords = [];
		
		$metadata = new ilMD($object->getId(), $object->getId(), $object->getType());
		
		if( !$metadata->getGeneral() )
		{
			ilLoggerFactory::getRootLogger()->debug(
				'No keywords found for object '.$object->getType().$object->getId()
			);
			
			return $keywords;
		}
		
		foreach($metadata->getGeneral()->getKeywordIds() as $keywordId)
		{
		    if ($metadata->getGeneral()->getKeyword($keywordId)->getKeyword() != "") {
                $keywords[] = $metadata->getGeneral()->getKeyword($keywordId)->getKeyword();
            }
		}
		
		ilLoggerFactory::getRootLogger()->debug(
			'Found keywords for object '.$object->getType().$object->getId()."\n".implode(',', $keywords)
		);
		
		
		return $keywords;
	}
	
	protected function getLocale()
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$ilLocale = $DIC->settings()->get('locale', '');
		
		if( strlen($ilLocale) )
		{
			return str_replace('_', '-', $ilLocale);
		}
		
		return self::DEFAULT_LOCALE;
	}
}

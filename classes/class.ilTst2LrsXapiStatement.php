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
class ilTst2LrsXapiStatement extends ilLp2LrsXapiStatement implements JsonSerializable
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
	 * @return string
	 */
	protected function getVerbId()
	{
		return self::$VERBS_BY_EVENT_TYPE[$this->event_type];
	}
}

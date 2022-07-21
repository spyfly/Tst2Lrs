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
class ilTst2LrsXapiStatement extends ilEvents2LrsXapiStatement implements JsonSerializable
{
	use Tst2LrsTrait;
	use DICTrait;

	protected static $XAPI_VERBS = [
		'http://adlnet.gov/expapi/verbs/attempted' => 'attempted',
		'http://adlnet.gov/expapi/verbs/resumed' => 'resumed',
		'http://adlnet.gov/expapi/verbs/suspended' => 'suspended',
		'http://adlnet.gov/expapi/verbs/completed' => 'completed'
	];

	protected static $VERBS_BY_EVENT_TYPE = [
		'startTestPass' => 'http://adlnet.gov/expapi/verbs/attempted',
		'resumeTestPass' => 'http://adlnet.gov/expapi/verbs/resumed',
		'suspendTestPass' => 'http://adlnet.gov/expapi/verbs/suspended',
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
	) {
		$this->lrsType = $lrsType;
		$this->object = $object;
		$this->user = $user;
		$this->event_type = $event_type;
		$this->pass_details = $pass_details;
		$this->test_details = $test_details;

		global $DIC; /** @var Container $DIC */
		$this->dic = $DIC;
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'EventHandling', 'evhk', 'Tst2Lrs');
	}

	/**
	 * @return string
	 */
	protected function buildTimestamp()
	{
		/* Generate Timestamp */
		$raw_timestamp = time();
		if ($this->event_type == 'finishTestPass') {
			/* If user submits test, use test result timestamp	 */
			$raw_timestamp = $this->test_details['result_tstamp'];
		}
		$timestamp = new ilCmiXapiDateTime($raw_timestamp, IL_CAL_UNIX);
		return $timestamp->toXapiTimestamp();
	}

	protected function hasResult()
	{
		return $this->pass_details !== null;
	}

	/**
	 * @return array
	 */
	protected function buildResult(): array
	{
		$resultObj = [
			'score' => [
				'scaled' => $this->pass_details['percent'],
				'raw' => $this->pass_details['total_reached_points'],
				'min' => 0,
				'max' => $this->pass_details['total_max_points'],
			],
			'completion' => $this->event_type === 'finishTestPass'
		];

		if ($this->event_type === 'finishTestPass') { /* Only include success statments for finished tests */
			$resultObj['success'] = $this->test_details['passed'] == 1;
		}

		return $resultObj;
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
}

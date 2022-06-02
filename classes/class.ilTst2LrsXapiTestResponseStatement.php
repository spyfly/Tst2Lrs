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
class ilTst2LrsXapiTestResponseStatement extends ilLp2LrsXapiStatement implements JsonSerializable
{
	use Tst2LrsTrait;
    use DICTrait;

	protected static $XAPI_VERBS = [
		'http://adlnet.gov/expapi/verbs/answered' => 'answered'
	];
		
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
		ilObjUser $user,
		$ass_details,
		$test_details,
		ilObjTest $testObj,
		$choices,
		$correct_choices
	)
	{
		$this->lrsType = $lrsType;
		$this->user = $user;
		$this->ass_details = $ass_details;
		$this->test_details = $test_details;
		$this->testObj = $testObj;
		$this->choices = $choices;
		$this->correct_choices = $correct_choices;
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
		return $this->ass_details !== null;
	}
	
	/**
	 * @return array
	 */
	protected function buildResult()
	{
		return [
			'score' => [
				'scaled' => $this->ass_details['reached'] / $this->ass_details['max'],
				'raw' => $this->ass_details['reached'],
				'min' => 0,
				'max' => $this->ass_details['max'],
			],
			'completion' => $this->ass_details['answered'] == 1,
			'success' => $this->test_details['passed'] == 1
		];
	}
	
	/**
	 * @return array
	 */
	protected function buildVerb()
	{
		return [
			'id' => "http://adlnet.gov/expapi/verbs/answered",
			'display' => [ $this->getLocale() => "answered" ]
		];
	}

	/* Placeholder */
	protected function buildObject()
	{
		$objectProperties = [
			'id' => $this->buildContext()['contextActivities']['parent']['id'] . '/' . $this->ass_details['qid'],
                'definition' => [
                    'name' => [$this->getLocale() => $this->ass_details['title']],
                    'type' => 'http://adlnet.gov/expapi/activities/cmi.interaction'
                ]
		];

		if (count($this->choices) > 0) {
			$objectProperties['definition']['interactionType'] = 'choice';
			$objectProperties['definition']['choices'] = $this->buildChoices();
			$objectProperties['definition']['correctResponsesPattern'] = $this->correct_choices;
		}

		return $objectProperties;
	}

	protected function buildChoices() {
		$choices = [];
		foreach ($this->choices as $id => $description) {
			$choices[$id] = [
				'id' => (string)$id,
				'description' => [$this->getLocale() => $description]
			];
		}
		return $choices;
	}

	/**
	 * @return array
	 */
	protected function buildContext()
	{
		$context = [
			'contextActivities' => []
		];
		
		$context['contextActivities']['parent'] = $this->getObjectProperties($this->testObj);

		/*
		$categories = $this->getObjectCategories($this->object);
		if( $categories )
		{
            $context['contextActivities']['category'] = $categories;
        }
		*/
		
		return $context;
	}
}

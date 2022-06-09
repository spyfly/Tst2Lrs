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

	protected static $INTERACTION_TYPES = [
		'assSingleChoice' => 'choice',
		'assMultipleChoice' => 'choice',
		'assTextQuestion' => 'long-fill-in',
		'assNumeric' => 'numeric',
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
		$questionUi,
		$user_solutions
	) {
		$this->lrsType = $lrsType;
		$this->user = $user;
		$this->ass_details = $ass_details;
		$this->test_details = $test_details;
		$this->testObj = $testObj;
		$this->questionUi = $questionUi;
		$this->user_solutions = $user_solutions;
	}

	/**
	 * @return string
	 */
	protected function buildTimestamp()
	{
		/* Fetch Test Result Timestamp as fallback for unanswered questions */
		$raw_timestamp = $this->test_details['result_tstamp'];
		if (count($this->user_solutions) > 0) {
			/* If we have a user_solution, fetch solution timestamp instead */
			$raw_timestamp = $this->user_solutions[0]['tstamp'];
		}
		$timestamp = new ilCmiXapiDateTime($raw_timestamp, IL_CAL_UNIX);
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
		$result = [
			'score' => [
				'scaled' => $this->ass_details['reached'] / $this->ass_details['max'],
				'raw' => $this->ass_details['reached'],
				'min' => 0,
				'max' => $this->ass_details['max'],
			],
			'completion' => $this->ass_details['answered'] == 1,
		];

		if (count($this->user_solutions) > 0 && $this->getInteractionType() !== null) {
			$result['response'] = $this->buildUserResponse();
		}

		return $result;
	}

	protected function buildUserResponse()
	{
		$solutions = [];
		foreach ($this->user_solutions as $key => $solution) {
			$solutions[] = $solution['value1'];
		}

		return implode('[,]', $solutions);
	}

	/**
	 * @return array
	 */
	protected function buildVerb()
	{
		return [
			'id' => "http://adlnet.gov/expapi/verbs/answered",
			'display' => [$this->getLocale() => "answered"]
		];
	}

	protected function getInteractionType()
	{
		if (array_key_exists($this->ass_details['type'], self::$INTERACTION_TYPES)) {
			return self::$INTERACTION_TYPES[$this->ass_details['type']];
		}
		return null;
	}

	/* Placeholder */
	protected function buildObject()
	{
		$objectProperties = [
			'id' => $this->buildContext()['contextActivities']['parent']['id'] . '/' . $this->ass_details['qid'],
			'definition' => [
				'name' => [$this->getLocale() => $this->ass_details['title']],
				'description' => [$this->getLocale() => $this->questionUi->object->getQuestion()],
				'type' => 'http://adlnet.gov/expapi/activities/cmi.interaction'
			]
		];

		if ($this->getInteractionType() !== null) {
			$objectProperties['definition']['interactionType'] = $this->getInteractionType();
			if ($this->getInteractionType() === 'choice') {
				list($objectProperties['definition']['choices'], $objectProperties['definition']['correctResponsesPattern']) = $this->buildChoicesList();
			} else if ($this->getInteractionType() === 'numeric') {
				$objectProperties['definition']['correctResponsesPattern'] = $this->buildNumericCorrectResponsesPattern();
			} else if ($this->getInteractionType() === 'long-fill-in') {
				$objectProperties['definition']['correctResponsesPattern'] = $this->buildFillInCorrectResponsesPattern();
			}
		}

		return $objectProperties;
	}

	protected function buildChoicesList()
	{
		$choices = [];
		$correctResponsesPattern = [];
		if (isset($this->questionUi->object->answers)) {
			foreach ($this->questionUi->object->answers as $id => $answer) {
				$choices[$id] = [
					'id' => (string)$id,
					'description' => [$this->getLocale() => $answer->getAnswertext()]
				];
				if ($this->ass_details['type'] == 'assMultipleChoice' && $answer->getPointsChecked() > 0) {
					$correctResponsesPattern[] = (string)$id;
				} else if ($this->ass_details['type'] == 'assSingleChoice' && $answer->getPoints() > 0) {
					$correctResponsesPattern[] = (string)$id;
				}
			}
		}

		return [$choices, $correctResponsesPattern];
	}

	protected function buildNumericCorrectResponsesPattern()
	{
		return [$this->questionUi->object->getLowerLimit() . '[:]' . $this->questionUi->object->getUpperLimit()];
	}

	protected function buildFillInCorrectResponsesPattern()
	{
		$correctResponsesPattern = [];
		if (isset($this->questionUi->object->answers)) {
			foreach ($this->questionUi->object->answers as $id => $answer) {
				if ($answer->getPointsChecked() > 0) {
					$correctResponsesPattern[] = $answer->getAnswertext();
				}
			}
		}

		return $correctResponsesPattern;
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

<?php

namespace spyfly\Plugins\Tst2Lrs\Config\Form;

use spyfly\Plugins\Tst2Lrs\Config\ConfigCtrl;
use spyfly\Plugins\Tst2Lrs\Utils\Tst2LrsTrait;
use ilTst2LrsPlugin;
use srag\CustomInputGUIs\Tst2Lrs\FormBuilder\AbstractFormBuilder;

/**
 * Class FormBuilder
 *
 * @package spyfly\Plugins\Tst2Lrs\Config\Form
 *
 * @author Sebastian Heiden <test2lrs@spyfly.xyz>
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class FormBuilder extends AbstractFormBuilder
{

    use Tst2LrsTrait;

    const KEY_SOME = "some";
    const PLUGIN_CLASS_NAME = ilTst2LrsPlugin::class;


    /**
     * @inheritDoc
     *
     * @param ConfigCtrl $parent
     */
    public function __construct(ConfigCtrl $parent)
    {
        parent::__construct($parent);
    }


    /**
     * @inheritDoc
     */
    protected function getButtons() : array
    {
        $buttons = [
            ConfigCtrl::CMD_UPDATE_CONFIGURE => self::plugin()->translate("save", ConfigCtrl::LANG_MODULE)
        ];

        return $buttons;
    }


    /**
     * @inheritDoc
     */
    protected function getData() : array
    {
        $data = [
            self::KEY_SOME => self::tst2Lrs()->config()->getValue(self::KEY_SOME)
        ];

        return $data;
    }


    /**
     * @inheritDoc
     */
    protected function getFields() : array
    {
        $fields = [
            self::KEY_SOME => self::dic()->ui()->factory()->input()->field()->text(self::plugin()->translate(self::KEY_SOME, ConfigCtrl::LANG_MODULE))->withRequired(true)
        ];

        return $fields;
    }


    /**
     * @inheritDoc
     */
    protected function getTitle() : string
    {
        return self::plugin()->translate("configuration", ConfigCtrl::LANG_MODULE);
    }


    /**
     * @inheritDoc
     */
    protected function storeData(array $data) : void
    {
        self::tst2Lrs()->config()->setValue(self::KEY_SOME, strval($data[self::KEY_SOME]));
    }
}

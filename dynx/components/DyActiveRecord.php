<?php

namespace dynx\components;

use yii\db\ActiveRecord;

class DyActiveRecord extends ActiveRecord
{

    /**
     * @param string $attribute
     * @return string Translated Attribute label
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute]))
            return $labels[$attribute];
        else {
            return \Yii::t("dynx/ar", $this->generateAttributeLabel($attribute));
        }
    }

    	/**
	 * I18N helper
	 *
	 * @param string      $category
	 * @param string      $message
	 * @param array       $params
	 * @param null|string $language
	 *
	 * @return string
	 */
	public static function t($category, $message, $params = [], $language = null)
	{

		return \Yii::t('dynx/ar', $message, $params, $language);
	}
}

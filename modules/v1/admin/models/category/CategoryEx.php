<?php
namespace app\modules\v1\admin\models\category;

use app\components\validators\ArrayUniqueValidator;
use app\components\validators\TranslationValidator;
use app\models\category\Category;

/**
 * Class CategoryEx
 * @package app\modules\v1\admin\models\category
 */
class CategoryEx extends Category
{
	public $translations;
	
	public function getCategoryLangs ()
	{
		return $this->hasMany(CategoryLangEx::className(), [ "category_id" => "id" ]);
	}
	
	/** @inheritdoc */
	public function fields ()
	{
		return [
			"id",
			"is_active",
			"translations" => "categoryLangs",
			"created_on",
			"updated_on",
		];
	}
	
	/** @inheritdoc */
	public function rules ()
	{
		return [
			[ "is_active", "integer" ],
			[ "is_active", "default", "value" => self::INACTIVE ],
			
			[ "translations", "required" ],
			[ "translations", TranslationValidator::className(), "validator" => CategoryLangEx::className() ],
			[ "translations", ArrayUniqueValidator::className(), "uniqueKey" => "language" ],
		];
	}
	
	/**
	 * @param $data
	 * @param $translations
	 *
	 * @return array
	 */
	public static function createWithTranslations ( $data, $translations )
	{
		//  start a transaction to rollback at any moment if there is a problem
		$transaction = self::$db->beginTransaction();
		
		//  create category entry
		$result = self::createCategory($data);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === self::ERROR) {
			$transaction->rollBack();
			
			return $result;
		}
		
		//  keep the category ID
		$categoryId = $result[ "category_id" ];
		
		//  create all translations
		$result = CategoryLangEx::manageTranslations($categoryId, $translations);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === CategoryLangEx::ERROR) {
			$transaction->rollBack();
			
			return self::buildError([ "translations" => $result[ "error" ] ]);
		}
		
		//  commit translations
		$transaction->commit();
		
		//  return category ID
		return self::buildSuccess([ "category_id" => $categoryId ]);
	}
	
	/**
	 * @param int $categoryId
	 *
	 * @return array
	 */
	public static function deleteWithTranslations ( $categoryId )
	{
		//  start a transaction to rollback at any moment if there is a problem
		$transaction = self::$db->beginTransaction();
		
		//  delete translation first
		$result = CategoryLangEx::deleteTranslations($categoryId);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === CategoryLangEx::ERROR) {
			$transaction->rollBack();
			
			return self::buildError([ "translations" => $result[ "error" ] ]);
		}
		
		//  delete category
		$result = self::deleteCategory($categoryId);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === self::ERROR) {
			$transaction->rollBack();
			
			return $result;
		}
		
		//  commit translations
		$transaction->commit();
		
		return self::buildSuccess([]);
	}
	
	/**
	 * @return \app\models\category\CategoryBase[]|array
	 */
	public static function getAllWithTranslations ()
	{
		return self::find()->withTranslations()->all();
	}
	
	/**
	 * @param $categoryId
	 *
	 * @return \app\models\category\CategoryBase[]|array
	 */
	public static function getOneWithTranslations ( $categoryId )
	{
		return self::find()->id($categoryId)->withTranslations()->all();
	}
	
	/**
	 * @param $categoryId
	 * @param $data
	 * @param $translations
	 *
	 * @return array
	 */
	public static function updateWithTranslations ( $categoryId, $data, $translations )
	{
		//  start a transaction to rollback at any moment if there is a problem
		$transaction = self::$db->beginTransaction();
		
		//  update the category
		$result = self::updateCategory($categoryId, $data);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === self::ERROR) {
			$transaction->rollBack();
			
			return $result;
		}
		
		//  update or create all translations for this category
		$result = CategoryLangEx::manageTranslations($categoryId, $translations);
		
		//  in case of error, rollback and return error
		if ($result[ "status" ] === CategoryLangEx::ERROR) {
			$transaction->rollBack();
			
			return self::buildError([ "translations" => $result[ "error" ] ]);
		}
		
		//  commit translations
		$transaction->commit();
		
		//  return the updated Category
		$category = self::find()->id($categoryId)->withTranslations()->one();
		
		return self::buildSuccess([ "category" => $category ]);
	}
}
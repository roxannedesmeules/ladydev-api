<?php

namespace app\models\post;

use Yii;

/**
 * This is the model class for table "post".
 *
 * @property int            $id
 * @property int            $category_id
 * @property int            $post_status_id
 * @property string         $created_on
 * @property string         $updated_on
 *
 * Relations :
 * @property AssoTagPost[]  $assoTagPosts
 * @property Tag[]          $tags
 * @property Category       $category
 * @property PostStatusBase $postStatus
 * @property PostLangBase[] $postLangs
 * @property Lang[]         $langs
 */
abstract class PostBase extends \yii\db\ActiveRecord
{
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	/** @inheritdoc */
	public static function tableName () { return 'post'; }
	
	/** @inheritdoc */
	public function rules ()
	{
		return [
			[ "category_id", "required" ],
			[ "category_id", "integer" ],
			[
				[ 'category_id' ],
				'exist',
				'skipOnError'     => true,
				'targetClass'     => Category::className(),
				'targetAttribute' => [ 'category_id' => 'id' ],
			],
			
			[ "post_status_id", "integer" ],
			[
				[ 'post_status_id' ],
				'exist',
				'skipOnError'     => true,
				'targetClass'     => PostStatusBase::className(),
				'targetAttribute' => [ 'post_status_id' => 'id' ],
			],
			
			[ "created_on", "safe" ],
			[ "updated_on", "safe" ],
		];
	}
	
	/** @inheritdoc */
	public function attributeLabels ()
	{
		return [
			'id'             => Yii::t('app.post', 'ID'),
			'category_id'    => Yii::t('app.post', 'Category ID'),
			'post_status_id' => Yii::t('app.post', 'Post Status ID'),
			'created_on'     => Yii::t('app.post', 'Created On'),
			'updated_on'     => Yii::t('app.post', 'Updated On'),
		];
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getAssoTagPosts ()
	{
		return $this->hasMany(AssoTagPost::className(), [ 'post_id' => 'id' ]);
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getTags ()
	{
		return $this->hasMany(Tag::className(), [ 'id' => 'tag_id' ])
		            ->viaTable('asso_tag_post', [ 'post_id' => 'id' ]);
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getCategory ()
	{
		return $this->hasOne(Category::className(), [ 'id' => 'category_id' ]);
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getPostStatus ()
	{
		return $this->hasOne(PostStatusBase::className(), [ 'id' => 'post_status_id' ]);
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getPostLangs ()
	{
		return $this->hasMany(PostLangBase::className(), [ 'post_id' => 'id' ]);
	}
	
	/** @return \yii\db\ActiveQuery */
	public function getLangs ()
	{
		return $this->hasMany(Lang::className(), [ 'id' => 'lang_id' ])
		            ->viaTable('post_lang', [ 'post_id' => 'id' ]);
	}
	
	/**
	 * @inheritdoc
	 * @return PostQuery the active query used by this AR class.
	 */
	public static function find ()
	{
		return new PostQuery(get_called_class());
	}
	
	/** @inheritdoc */
	public function beforeSave ( $insert )
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}
		
		switch ($insert) {
			case true:
				$this->created_on = date(self::DATE_FORMAT);
				break;
				
			case false:
				$this->updated_on = date(self::DATE_FORMAT);
				break;
		}
		
		return true;
	}
}
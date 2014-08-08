<?php namespace Clumsy\CMS\Models;

use Illuminate\Support\Collection;

class BaseModel extends \Eloquent {

    protected $guarded = array('id');

    protected $required_by = array();

    public static $has_slug = false;
    
    public static $rules = array();

    public static $booleans = array();

    public static $media = array();

    public static $parent_resource = null;
    
    public static $parent_id_column = null;

    public static function boot()
    {
        parent::boot();

        if (static::$has_slug)
        {
            self::saving(function($model)
            {
                $model->slug = \Str::slug($model->title);
            });
        }
    }

    public static function isNested()
    {
        return (bool)static::parentResource();
    }

    public static function parentResource()
    {
        return (string)static::$parent_resource;
    }

    public static function parentModel()
    {
        return (string)studly_case(static::$parent_resource);
    }

    public static function parentItemId($id)
    {
        $id_column = static::$parent_id_column === null ? static::$parent_resource.'_id' : static::$parent_id_column;

        return self::find($id)->$id_column;
    }

    public static function parentItem($id)
    {
        $parent_model = static::parentModel();
        return $parent_model::find(static::parentItemId());
    }

    public function requiredBy()
    {
        $required_by = new Collection;

        foreach ((array)$this->required_by as $relationship)
        {
            $required_by = $required_by->merge(
                $this->$relationship ? $this->$relationship : array()
            );
        }

        return $required_by;
    }

    public function isRequiredByOthers()
    {
        return !self::requiredBy()->isEmpty();
    }

    public function images()
    {
        return $this->morphToMany('Media', 'media_association')->select(array('media.*', 'position'));
    }

    public function hasImages()
    {
        return (bool)sizeof($this->images);
    }
    
    public function image($position = null)
    {
        if ($this->hasImages())
        {
            if ($position)
            {
                $image = $this->images->filter(function($image) use ($position)
                    {
                        return $image->position === $position;
                    })->first();
            }
            else
            {    
                $image = $this->images->first();
            }

            if ($image)
            {
                return $image->path();
            }
        }

        return $this->imagePlaceholder($position);
    }

    public static function displayName()
    {
        return false;
    }

    public static function displayNamePlural()
    {
        return false;
    }
}
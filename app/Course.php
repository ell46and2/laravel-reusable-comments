<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public function getRouteKeyName()
    {
    	return 'slug';
    }

    public function comments()
    {
    	return $this->morphMany(Comment::class, 'commentable')
    		->whereNull('parent_id') // so we only get top comments not replies
    		->orderBy('created_at', 'desc');
    }
}

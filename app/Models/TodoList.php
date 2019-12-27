<?php

namespace App\models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method Builder user_id(string $user_id)
 * @method Builder id(string $id)
 */
class TodoList extends Model
{
    protected $table = 'todo_list';
    protected $fillable = ['user_id', 'event'];

    /**
     * @param $query
     * @param string $user_id
     * @return mixed
     */
    public function scopeUser_Id($query, string $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    /**
     * @param $query
     * @param Int $id
     * @return mixed
     */
    public function scopeId($query, Int $id)
    {
        return $query->where('id', $id);
    }
}

<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class TodoList extends Model
{
    protected $table = 'todo_list';
    protected $fillable = ['user_id', 'event'];
}

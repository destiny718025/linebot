<?php


namespace App\Repositories;


use App\models\TodoList;

class TodoListRepository
{
    protected $todoList;

    public function __construct(TodoList $todoList)
    {
        $this->todoList = $todoList;
    }


}

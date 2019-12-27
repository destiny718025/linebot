<?php


namespace App\Repositories;


use App\models\TodoList;
use Illuminate\Support\Collection;

class TodoListRepository
{
    protected $todoList;

    public function __construct(TodoList $todoList)
    {
        $this->todoList = $todoList;
    }

    public function showTodoListByUser_Id(string $user_id): Collection
    {
        return $this->todoList->user_id($user_id)->get();
    }

    public function createTodoList(Array $payload)
    {
        $this->todoList->create($payload);
    }

    public function updateTodoListById(Array $payload, String $user_id, Int $id)
    {
        $this->todoList->user_id($user_id)->id($id)->update($payload);
    }

    public function deleteTodoListById(String $user_id, Int $id)
    {
        $this->todoList->user_id($user_id)->id($id)->delete();
    }
}

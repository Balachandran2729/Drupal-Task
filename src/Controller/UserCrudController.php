<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;

class UserCrudController extends ControllerBase
{
    public function list()
    {
        return [
            '#markup' => 'User CRUD List',
        ];
    }

    public function add()
    {
        return [
            '#markup' => 'Add User',
        ];
    }

    public function edit()
    {
        return [
            '#markup' => 'Edit User',
        ];
    }

    public function delete()
    {
        return [
            '#markup' => 'Delete User',
        ];
    }
}
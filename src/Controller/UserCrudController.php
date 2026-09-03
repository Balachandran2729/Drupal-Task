<?php

namespace Drupal\user_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

class UserCrudController extends ControllerBase
{
    public function list()
{
    $storage = $this->entityTypeManager()->getStorage('user');
    $users = $storage->loadMultiple();

    $rows = [];

    foreach ($users as $user) {
        if ($user->id() == 0) {
            continue;
        }

        $edit_link = Link::createFromRoute('Edit', 'user_crud.edit', ['user' => $user->id()])->toRenderable();
        $delete_link = Link::createFromRoute('Delete', 'user_crud.delete', ['user' => $user->id()])->toRenderable();

        $rows[] = [
            $user->id(),
            $user->getAccountName(),
            $user->getEmail(),
            $user->get('field_phone_number')->value,
            $user->isActive() ? 'Active' : 'Blocked',
            ['data' => $edit_link],
            ['data' => $delete_link],
        ];
    }

    $build['heading'] = [
        '#markup' => '<h2>User List</h2>',
    ];
     
    $build['add_link'] = Link::createFromRoute('Add User', 'user_crud.add')->toRenderable();

    $build['table'] = [
        '#type' => 'table',
        '#header' => ['ID', 'Username', 'Email', 'Phone Number', 'Status', 'Edit', 'Delete'],
        '#rows' => $rows,
        '#empty' => 'No users found.',
    ];

    return $build;
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

    public function hello() {
        return [
            '#markup' => 'Hello Chandru',
        ];
    }
}
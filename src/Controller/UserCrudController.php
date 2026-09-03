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

            // Skip anonymous user.
            if ($user->id() == 0) {
                continue;
            }

            $edit_link = Link::createFromRoute(
                'Edit',
                'user_crud.edit',
                [
                    'user' => $user->id(),
                ]
            )->toRenderable();

            $delete_link = Link::createFromRoute(
                'Delete',
                'user_crud.delete',
                [
                    'user' => $user->id(),
                ]
            )->toRenderable();

            $rows[] = [
                $user->id(),
                $user->getAccountName(),
                $user->getEmail(),
                $user->isActive() ? 'Active' : 'Blocked',
                [
                    'data' => $edit_link,
                ],
                [
                    'data' => $delete_link,
                ],
            ];
        }

        return [
            '#type' => 'table',
            '#header' => [
                'ID',
                'Username',
                'Email',
                'Status',
                'Edit',
                'Delete',
            ],
            '#rows' => $rows,
            '#empty' => 'No users found.',
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

    public function hello() {
        return [
            '#markup' => 'Hello Chandru',
        ];
    }
}
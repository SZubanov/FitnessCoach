<?php
declare(strict_types=1);

namespace App\Actions\Datatables;

use App\Contracts\Actions\Datatables\UserListsColumnInterface;
use App\Dto\Web\DatatableColumnDto;
use Spatie\LaravelData\DataCollection;

class UserListColumn implements UserListsColumnInterface
{
    public function __invoke(): DataCollection
    {
        $id = new DatatableColumnDto('id', __('users.datatable.id'), 'id', true, false, null);
        $name = new DatatableColumnDto('name', __('users.datatable.name'), 'name', true, true, null);
        $role = new DatatableColumnDto('role', __('users.datatable.role'), 'role', true, false, null);
        $email = new DatatableColumnDto('email', __('users.datatable.email'), 'email', false, true, null);
        $action = new DatatableColumnDto('action', __('users.datatable.action'), 'action', false, false, '5%');
        return DatatableColumnDto::collection([$id, $name, $role, $email, $action]);
    }
}

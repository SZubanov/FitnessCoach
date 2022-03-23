<?php
declare(strict_types=1);

namespace App\Actions\Datatables;

use App\Contracts\Actions\Datatables\UserListsColumns;
use App\Dto\Web\DatatableColumnDto;
use Spatie\LaravelData\DataCollection;

class UserListColumns implements UserListsColumns
{
    public function __invoke(): DataCollection
    {
        $id = [
            'data'        => 'id',
            'column_name' => 'id',
            'name'        => 'id',
            'sortable'    => false,
        ];

        $name = [
            'data'        => 'name',
            'column_name' => 'name',
            'name'        => 'name',
            'sortable'    => false,
        ];

        $email = [
            'data'        => 'email',
            'column_name' => 'email',
            'name'        => 'email',
            'sortable'    => false,
        ];

        $action = [
            'data'        => 'action',
            'column_name' => 'action',
            'name'        => 'action',
            'sortable'    => false,
            'searchable'  => false,
            'width'       => '5%',
        ];


        return DatatableColumnDto::collection([$id, $name, $email, $action]);
    }
}

<?php

namespace App\Console\Commands;

use App\Contracts\Actions\Users\StoreUserInterface;
use App\Dto\Web\UserStoreDto;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(StoreUserInterface $storeUser)
    {
        $roles = Role::get()->pluck('id', 'name');

        $email = $this->ask('Enter email');
        $password = $this->secret('Enter password');
        $name = $this->ask('Enter name');
        $role = $this->choice('Select role', $roles->keys()->toArray());

        $dto = new UserStoreDto(
            name: $name,
            email: $email,
            password: $password,
            role: $roles[$role]
        );

        $storeUser($dto);

        return 0;
    }
}

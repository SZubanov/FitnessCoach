<?php

namespace App\Console\Commands;

use App\FatSecret\Dto\DtoFactory;
use App\FatSecret\Dto\OAuthTokenDto;
use App\Services\UserReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Validator;

class GetFatSecretFoodEntryUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fatsecret:food_entry {userId} {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'FatSecret get food entry user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(UserReportService $userReportService, DtoFactory $factory)
    {
        $userId = $this->argument('userId');
        $date = $this->argument('date');
        if ($this->validate($userId, $date)) {
            return Command::FAILURE;
        }

        if(!\Auth::loginUsingId((int)$userId)) {
            $this->error('User not found');
            return Command::FAILURE;
        }

        $user = \Auth::user();

        $authTokenDTO = $factory->createOAuthTokenDto($user->oauth_token, $user->oauth_token_secret);
        $date = Carbon::make($date);

//       $userReportService->updateUserFoodEntry($user->id, $authTokenDTO, $date);
       $userReportService->updateUserWeight($user->id, $authTokenDTO, $date);

        return Command::SUCCESS;
    }

    private function validate(string $userId, string $date): int
    {
        $validator = $this->createValidator($userId, $date);
        try {
            $validator->validate();
        } catch (ValidationException $exception) {
            \Log::error($exception->getMessage());
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $userId
     * @param string $date
     * @return \Illuminate\Validation\Validator
     */
    private function createValidator(string $userId, string $date): \Illuminate\Validation\Validator
    {
        return Validator::make([
            'userId' => $userId,
            'date' => $date
        ], [
            'userId' => ['required', 'integer'],
            'date' => ['required', 'date']
        ]);
    }
}

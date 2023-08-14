<?php

namespace App\Services;

use App\Dto\UserReport\DtoFactory;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RequestErrorException;
use App\FatSecret\Exceptions\ResponseDecodeException;
use App\FatSecret\FatSecretFacade;
use App\Repositories\FoodEntryRepository;
use Carbon\Carbon;

class UserReportService
{
    public function __construct(
        protected FatSecretFacade $fatSecretFacade,
        protected DtoFactory $foodEntryDtoFactory,
        protected FoodEntryRepository $userReportRepository
    ) {

    }

    /**
     * @param int $userId
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return void
     * @throws FatSecretException
     */
    public function updateUserFoodEntry(int $userId, OAuthTokenDto $authTokenDTO, Carbon $date): void
    {
       $foodEntry = $this->fatSecretFacade->getFoodEntry($authTokenDTO, $date);
       $userFoodEntry = $this->foodEntryDtoFactory->createUserFoodEntry($userId, $date, $foodEntry);

       $this->userReportRepository->createUserFoodEntry($userFoodEntry);
    }

    /**
     * @param int $userId
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return void
     * @throws FatSecretException
     */
    public function updateUserWeight(int $userId, OAuthTokenDto $authTokenDTO, Carbon $date): void
    {
        $weight = $this->fatSecretFacade->getWeightByDate($authTokenDTO, $date);
        $userWeight = $this->foodEntryDtoFactory->createUserWeightDto($userId, $date, $weight);

        $this->userReportRepository->createUserWeight($userWeight);
    }
}

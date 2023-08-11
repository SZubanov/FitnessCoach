<?php

namespace App\Services;

use App\Dto\UserReport\DtoFactory;
use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\FatSecretFacade;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class UserReportService
{
    public function __construct(
        protected FatSecretFacade $fatSecretFacade,
        protected DtoFactory $userReportDtoFactory,
        protected UserReportRepository $userReportRepository
    ) {

    }

    /**
     * @param int $userId
     * @param OAuthTokenDto $authTokenDTO
     * @param Carbon $date
     * @return void
     * @throws FatSecretException
     */
    public function updateUserReport(int $userId, OAuthTokenDto $authTokenDTO, Carbon $date): void
    {
       $foodEntry = $this->fatSecretFacade->getFoodEntry($authTokenDTO, $date);
       $userReportDto = $this->userReportDtoFactory->createUserReportDto($userId, $date, $foodEntry);

       $this->userReportRepository->createUserReport($userReportDto);
    }
}

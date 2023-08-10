<?php

namespace App\Services;

use App\FatSecret\Dto\OAuthTokenDto;
use App\FatSecret\FatSecretFacade;

class UserReportService
{
    public function __construct(protected FatSecretFacade $fatSecretFacade)
    {

    }

    public function updateFoodEntry(OAuthTokenDto $authTokenDTO, int $date)
    {
       return $this->fatSecretFacade->getFoodEntry($authTokenDTO, $date);
    }
}

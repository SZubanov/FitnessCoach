<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\GetUserDiaryWeightWithFatSecretInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ServerException;
use App\FatSecret\Dto\DtoFactory;
use App\FatSecret\Exceptions\FatSecretException;
use App\FatSecret\Exceptions\RecordNotFoundException as FatRecordNotFoundException;
use App\Services\FatSecretUserDiaryService;
use Carbon\Carbon;

class GetUserDiaryWeightWithFatSecret implements GetUserDiaryWeightWithFatSecretInterface
{
    public function __construct(
        private readonly FatSecretUserDiaryService $userReportService,
        private readonly DtoFactory $dtoFactory
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws ServerException
     */
    public function __invoke(Carbon $date)
    {
        $authUser = \Auth::user();
        $oauthDto = $this->dtoFactory->createOAuthTokenDto($authUser->oauth_token, $authUser->oauth_token_secret);

        try {
            $this->userReportService->updateUserWeight($authUser->id, $oauthDto, $date);
        } catch (FatSecretException $exception) {
            if ($exception instanceof FatRecordNotFoundException) {
                throw new NotFoundException();
            }
            throw new ServerException();
        } catch (\Exception $exception) {
            throw new ServerException();
        }
    }
}

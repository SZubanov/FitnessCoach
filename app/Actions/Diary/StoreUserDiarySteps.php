<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryStepsInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiaryStepsStoreDto;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiarySteps implements StoreUserDiaryStepsInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory
    ) {
    }

    public function __invoke(DiaryStepsStoreDto $dto): void
    {
        $authUser = \Auth::user();
        $userSteps = $this->dtoFactory
            ->createUserStepsDto(
                $authUser->id,
                $dto->steps,
                Carbon::parse($dto->date)
            );

        $this->reportRepository->createUserSteps($userSteps);
    }
}

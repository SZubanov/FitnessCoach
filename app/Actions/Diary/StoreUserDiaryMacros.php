<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Contracts\Actions\Users\GetDefaultWeightUnitUserInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiaryMacrosStoreDto;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiaryMacros implements StoreUserDiaryMacrosInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory,
        private readonly GetDefaultWeightUnitUserInterface $defaultWeightUnitUser
    ) {
    }

    public function __invoke(DiaryMacrosStoreDto $dto): void
    {
        $authUser = \Auth::user();
        $userFoodEntry = $this->dtoFactory
            ->createUserFoodEntryDto(
                $authUser->id,
                $dto->kcal,
                $dto->protein,
                $dto->fat,
                $dto->carbs,
                ($this->defaultWeightUnitUser)(),
                Carbon::parse($dto->date)
            );

        $this->reportRepository->createUserFoodEntry($userFoodEntry);
    }
}

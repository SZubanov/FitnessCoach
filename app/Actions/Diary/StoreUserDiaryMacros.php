<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryMacrosInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiaryMacrosStoreDto;
use App\Helpers\MetricSystem;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiaryMacros implements StoreUserDiaryMacrosInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory
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
                MetricSystem::getDefaultWeightUnitByMetricSystem($authUser->default_measure_system),
                Carbon::parse($dto->date)
            );

        $this->reportRepository->createUserFoodEntry($userFoodEntry);
    }
}

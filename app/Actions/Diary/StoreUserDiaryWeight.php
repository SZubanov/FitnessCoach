<?php

namespace App\Actions\Diary;

use App\Contracts\Actions\Diary\StoreUserDiaryWeightInterface;
use App\Dto\UserReport\DtoFactory;
use App\Dto\Web\Diary\DiaryWeightStoreDto;
use App\Helpers\MetricSystem;
use App\Repositories\UserReportRepository;
use Carbon\Carbon;

class StoreUserDiaryWeight implements StoreUserDiaryWeightInterface
{
    public function __construct(
        private readonly UserReportRepository $reportRepository,
        private readonly DtoFactory $dtoFactory
    ) {
    }

    public function __invoke(DiaryWeightStoreDto $dto): void
    {
        $authUser = \Auth::user();
        $userWeight = $this->dtoFactory
            ->createUserWeightDto(
                $authUser->id,
                Carbon::parse($dto->date),
                $dto->weight,
                $dto->unit
            );

        $this->reportRepository->createUserWeight($userWeight);
    }
}

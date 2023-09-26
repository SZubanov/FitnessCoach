<?php

namespace Tests\Unit\Services;

use App\Dto\UserReport\DtoFactory;
use App\FatSecret\FatSecretFacade;
use App\Repositories\UserReportRepository;
use PHPUnit\Framework\TestCase;

class UserReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $fatSecretFacade = $this->getMockBuilder(FatSecretFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $foodEntryDtoFactory = $this->getMockBuilder(DtoFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userReportRepository = $this->getMockBuilder(UserReportRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}

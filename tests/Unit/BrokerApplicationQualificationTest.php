<?php

namespace Tests\Unit;

use App\Models\ApplicationRequirement;
use App\Models\BrokerApplication;
use PHPUnit\Framework\TestCase;

class BrokerApplicationQualificationTest extends TestCase
{
    public function test_persisted_requirements_must_all_be_verified(): void
    {
        $verifiedRequirement = $this->makeRequirement(101, 'Verified');
        $pendingRequirement = $this->makeRequirement(102, 'Pending');

        $application = new BrokerApplication();
        $application->setRelation('requirements', collect([
            $verifiedRequirement,
            $pendingRequirement,
        ]));

        $this->assertFalse($application->canBeQualified());

        $pendingRequirement->verification_status = 'Verified';

        $this->assertTrue($application->canBeQualified());
    }

    public function test_review_payload_must_cover_every_requirement_and_mark_each_verified(): void
    {
        $application = new BrokerApplication();
        $application->setRelation('requirements', collect([
            $this->makeRequirement(201, 'Pending'),
            $this->makeRequirement(202, 'Pending'),
        ]));

        $this->assertFalse($application->canBeQualified([
            ['id' => 201, 'verification_status' => 'Verified'],
        ]));

        $this->assertFalse($application->canBeQualified([
            ['id' => 201, 'verification_status' => 'Verified'],
            ['id' => 202, 'verification_status' => 'Rejected'],
        ]));

        $this->assertTrue($application->canBeQualified([
            ['id' => 201, 'verification_status' => 'Verified'],
            ['id' => 202, 'verification_status' => 'Verified'],
        ]));
    }

    private function makeRequirement(int $id, string $verificationStatus): ApplicationRequirement
    {
        $requirement = new ApplicationRequirement();
        $requirement->id = $id;
        $requirement->verification_status = $verificationStatus;

        return $requirement;
    }
}

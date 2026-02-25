<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Evaluation;
use App\Enum\StatutEvaluation;
use PHPUnit\Framework\TestCase;

class EvaluationTest extends TestCase
{
    private Evaluation $evaluation;

    protected function setUp(): void
    {
        $this->evaluation = new Evaluation();
    }

    public function testUuidGenerated(): void
    {
        $this->assertNotNull($this->evaluation->getUuid());
    }

    public function testSetIpHashesValue(): void
    {
        $this->evaluation->setIp('192.168.1.1');
        $ip = $this->evaluation->getIp();

        $this->assertNotSame('192.168.1.1', $ip);
        $this->assertSame(hash('sha256', '192.168.1.1'), $ip);
    }

    public function testSetIpNullReturnsNull(): void
    {
        $this->evaluation->setIp(null);
        $this->assertNull($this->evaluation->getIp());
    }

    public function testIncrementerUtile(): void
    {
        $this->assertSame(0, $this->evaluation->getNombreUtile());
        $this->evaluation->incrementerUtile();
        $this->assertSame(1, $this->evaluation->getNombreUtile());
    }

    public function testIncrementerSignalement(): void
    {
        $this->assertSame(0, $this->evaluation->getNombreSignalement());
        $this->evaluation->incrementerSignalement();
        $this->assertSame(1, $this->evaluation->getNombreSignalement());
    }

    public function testEstSupprimable(): void
    {
        // ACTIVE with 0 signalements (< 3) → supprimable
        $this->evaluation->setStatut(StatutEvaluation::ACTIVE);
        $this->assertTrue($this->evaluation->estSupprimable());
    }

    public function testEstSupprimableReturnsFalseWhenTooManySignalements(): void
    {
        $this->evaluation->setStatut(StatutEvaluation::ACTIVE);
        // 3 signalements → no longer supprimable
        $this->evaluation->incrementerSignalement();
        $this->evaluation->incrementerSignalement();
        $this->evaluation->incrementerSignalement();
        $this->assertFalse($this->evaluation->estSupprimable());
    }

    public function testGetPseudoAnonymousWithNoCustomPseudo(): void
    {
        $this->evaluation->setEstAnonyme(true);
        // No pseudo set → falls back to 'Utilisateur anonyme'
        $this->assertSame('Utilisateur anonyme', $this->evaluation->getPseudo());
    }

    public function testGetPseudoWithCustomPseudo(): void
    {
        $this->evaluation->setEstAnonyme(true);
        $this->evaluation->setPseudo('Jean');
        $this->assertSame('Jean', $this->evaluation->getPseudo());
    }

    public function testDefaultStatutIsActive(): void
    {
        $this->assertSame(StatutEvaluation::ACTIVE, $this->evaluation->getStatut());
    }
}

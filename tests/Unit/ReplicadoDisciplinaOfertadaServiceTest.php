<?php

namespace Tests\Unit;

use App\Services\ReplicadoDisciplinaOfertadaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplicadoDisciplinaOfertadaServiceTest extends TestCase
{
    private ReplicadoDisciplinaOfertadaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReplicadoDisciplinaOfertadaService;
    }

    #[Test]
    public function parse_sgldis_separa_departamento_e_codigo(): void
    {
        $this->assertSame([
            'sgldis' => 'MAT1111',
            'departamento' => 'MAT',
            'codigo' => '1111',
        ], $this->service->parseSgldis('mat1111'));

        $this->assertSame([
            'sgldis' => 'MAC50',
            'departamento' => 'MAC',
            'codigo' => '0050',
        ], $this->service->parseSgldis('MAC50'));
    }

    #[Test]
    public function parse_sgldis_aceita_mpm(): void
    {
        $this->assertSame([
            'sgldis' => 'MPM0501',
            'departamento' => 'MPM',
            'codigo' => '0501',
        ], $this->service->parseSgldis('MPM0501'));
    }

    #[Test]
    public function formatar_data_para_replicado_usa_literal_yyyy_mm_dd(): void
    {
        $this->assertSame('2026-01-01', $this->service->formatarDataParaReplicado('2026-01-01'));
        $this->assertSame('2026-06-15', $this->service->formatarDataParaReplicado('2026-06-15T00:00:00'));
    }

    #[Test]
    public function parse_sgldis_rejeita_formato_invalido(): void
    {
        $this->assertNull($this->service->parseSgldis('MAT'));
        $this->assertNull($this->service->parseSgldis('MATERIAL1234'));
        $this->assertNull($this->service->parseSgldis('XYZ1234'));
    }
}

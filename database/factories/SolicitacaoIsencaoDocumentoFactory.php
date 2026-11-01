<?php

namespace Database\Factories;

use App\Models\SolicitacaoIsencao;
use App\Models\SolicitacaoIsencaoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SolicitacaoIsencaoDocumento>
 */
class SolicitacaoIsencaoDocumentoFactory extends Factory
{
    protected $model = SolicitacaoIsencaoDocumento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solicitacao_isencao_id' => SolicitacaoIsencao::factory(),
            'path' => 'isencao/'.Str::uuid().'.pdf',
            'nome_original' => 'documento-'.$this->faker->slug(2).'.pdf',
            'tamanho' => $this->faker->numberBetween(10_000, 1_048_576),
            'mime' => 'application/pdf',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\DisciplinaOfertada;
use App\Models\Inscricao;
use App\Models\Periodo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InscricaoEtapa3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inscricao = $this->inscricaoDaSessao();
            if ($inscricao === null || $inscricao->etapa_concluida !== 2) {
                $validator->errors()->add('sessao', 'Sessão inválida ou etapa incorreta.');

                return;
            }

            $ativo = Periodo::ativoParaInscricoes();
            if (! $ativo) {
                $validator->errors()->add('periodo', 'Não há período de inscrições ativo.');

                return;
            }

            $obr = $this->integer('disciplina_obrigatoria_id');
            $op1 = $this->filled('disciplina_opcional_1_id') ? $this->integer('disciplina_opcional_1_id') : null;
            $op2 = $this->filled('disciplina_opcional_2_id') ? $this->integer('disciplina_opcional_2_id') : null;

            $brutos = array_filter([$obr, $op1, $op2], static fn ($v) => $v !== null && $v !== 0);
            if (count($brutos) !== count(array_unique($brutos))) {
                $validator->errors()->add('disciplinas', 'As disciplinas selecionadas devem ser diferentes entre si.');

                return;
            }

            $ids = array_values(array_unique($brutos));

            $count = DisciplinaOfertada::query()
                ->where('periodo_id', $ativo->id)
                ->whereIn('id', $ids)
                ->count();
            if ($count !== count($ids)) {
                $validator->errors()->add('disciplinas', 'Selecione apenas disciplinas ofertadas no período atual.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'disciplina_obrigatoria_id' => 'required|integer|exists:disciplinas_ofertadas,id',
            'disciplina_opcional_1_id' => 'nullable|integer|exists:disciplinas_ofertadas,id',
            'disciplina_opcional_2_id' => 'nullable|integer|exists:disciplinas_ofertadas,id',
        ];
    }

    private function inscricaoDaSessao(): ?Inscricao
    {
        $raw = $this->session()->get('inscricao_id');
        if (is_int($raw)) {
            $id = $raw;
        } elseif (is_string($raw) && ctype_digit($raw)) {
            $id = (int) $raw;
        } else {
            return null;
        }

        return Inscricao::query()->whereKey($id)->first();
    }
}

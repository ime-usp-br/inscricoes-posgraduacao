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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'justificativa_disciplina_obrigatoria' => $this->string('justificativa_disciplina_obrigatoria')->trim()->toString(),
            'justificativa_disciplina_opcional_1' => $this->filled('justificativa_disciplina_opcional_1')
                ? $this->string('justificativa_disciplina_opcional_1')->trim()->toString()
                : null,
            'justificativa_disciplina_opcional_2' => $this->filled('justificativa_disciplina_opcional_2')
                ? $this->string('justificativa_disciplina_opcional_2')->trim()->toString()
                : null,
        ]);
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

            if ($op1 === null && $this->filled('justificativa_disciplina_opcional_1')) {
                $validator->errors()->add('justificativa_disciplina_opcional_1', 'Informe a disciplina opcional 1 ou remova a justificativa.');
            }

            if ($op2 === null && $this->filled('justificativa_disciplina_opcional_2')) {
                $validator->errors()->add('justificativa_disciplina_opcional_2', 'Informe a disciplina opcional 2 ou remova a justificativa.');
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
            'justificativa_disciplina_obrigatoria' => 'required|string|max:5000',
            'disciplina_opcional_1_id' => 'nullable|integer|exists:disciplinas_ofertadas,id',
            'justificativa_disciplina_opcional_1' => 'nullable|string|max:5000|required_with:disciplina_opcional_1_id',
            'disciplina_opcional_2_id' => 'nullable|integer|exists:disciplinas_ofertadas,id',
            'justificativa_disciplina_opcional_2' => 'nullable|string|max:5000|required_with:disciplina_opcional_2_id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'disciplina_obrigatoria_id.required' => 'Selecione a disciplina obrigatória.',
            'disciplina_obrigatoria_id.exists' => 'A disciplina obrigatória selecionada é inválida.',
            'justificativa_disciplina_obrigatoria.required' => 'Informe a justificativa da disciplina obrigatória.',
            'justificativa_disciplina_obrigatoria.max' => 'A justificativa da disciplina obrigatória deve ter no máximo 5000 caracteres.',
            'disciplina_opcional_1_id.exists' => 'A disciplina opcional 1 selecionada é inválida.',
            'justificativa_disciplina_opcional_1.required_with' => 'Informe a justificativa da disciplina opcional 1.',
            'justificativa_disciplina_opcional_1.max' => 'A justificativa da disciplina opcional 1 deve ter no máximo 5000 caracteres.',
            'disciplina_opcional_2_id.exists' => 'A disciplina opcional 2 selecionada é inválida.',
            'justificativa_disciplina_opcional_2.required_with' => 'Informe a justificativa da disciplina opcional 2.',
            'justificativa_disciplina_opcional_2.max' => 'A justificativa da disciplina opcional 2 deve ter no máximo 5000 caracteres.',
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

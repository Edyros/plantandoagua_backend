<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paymentMethod' => ['required', 'string', Rule::in([
                Payment::METHOD_PIX,
                Payment::METHOD_CREDIT_CARD,
                Payment::METHOD_BANK_SLIP,
            ])],
            'description' => ['nullable', 'string', 'max:255'],
            'dueDate' => ['nullable', 'date'],
            'payerName' => ['nullable', 'string', 'max:255'],
            'payerCpf' => ['nullable', 'string', 'max:18'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor do pagamento.',
            'amount.numeric' => 'O valor do pagamento precisa ser um número.',
            'amount.min' => 'O valor deve ser pelo menos R$ 0,01.',
            'paymentMethod.required' => 'Informe o método de pagamento.',
            'paymentMethod.in' => 'Use pix, credit_card ou bank_slip.',
            'dueDate.date' => 'A data de vencimento precisa estar no formato AAAA-MM-DD.',
            'payerName.max' => 'O nome do pagador é longo demais.',
            'payerCpf.max' => 'O CPF do pagador é inválido.',
        ];
    }
}

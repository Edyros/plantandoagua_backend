<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\HebronPayWebhookSimulator;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;

class SimulateHebronPayWebhookCommand extends Command
{
    protected $signature = 'hebronpay:simulate-webhook
                            {fixture? : Nome do JSON em resources/hebronpay/webhooks}
                            {--payment= : UUID local do pagamento}
                            {--list : Lista as simulações disponíveis}
                            {--dump : Só mostra o JSON, não dispara o POST}';

    protected $description = 'Reproduz um webhook HebronPay no Laravel local, no mesmo formato { event, data }.';

    public function handle(HebronPayWebhookSimulator $simulator): int
    {
        if ($this->option('list') || $this->argument('fixture') === null) {
            $this->info('Simulações em resources/hebronpay/webhooks:');
            foreach ($simulator->list() as $name) {
                $this->line('  '.$name);
            }

            if ($this->option('list')) {
                return self::SUCCESS;
            }

            $this->newLine();
            $this->comment('Uso: php artisan hebronpay:simulate-webhook invoice.updated.paid --payment=<uuid>');

            return self::SUCCESS;
        }

        $fixture = (string) $this->argument('fixture');
        $payment = $this->resolvePayment();

        if (! $payment) {
            $this->error('Nenhum pagamento local encontrado. Crie um em /dev/pagamentos e passe --payment=<uuid>.');

            return self::FAILURE;
        }

        $payload = $simulator->bind($simulator->load($fixture), $payment);
        $body = $simulator->signedBody($payload);

        if ($this->option('dump')) {
            $this->line($body);

            return self::SUCCESS;
        }

        $status = $this->postWebhook($body, $simulator);

        $payment->refresh();

        $this->info("POST /api/webhooks/hebronpay → HTTP {$status}");
        $this->line('fixture: '.$fixture);
        $this->line('payment: '.$payment->id);
        $this->line('status:  '.$payment->status);
        $this->line('event:   '.(string) ($payload['event'] ?? ''));

        return $status === 200 ? self::SUCCESS : self::FAILURE;
    }

    private function resolvePayment(): ?Payment
    {
        $id = trim((string) $this->option('payment'));
        if ($id !== '') {
            return Payment::query()->find($id);
        }

        return Payment::query()->latest('created_at')->first();
    }

    private function postWebhook(string $body, HebronPayWebhookSimulator $simulator): int
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $secret = (string) config('hebronpay.webhook_secret');
        if ($secret !== '') {
            $headers['HTTP_X_SIGNATURE'] = $simulator->signature($body, $secret);
        }

        $request = Request::create('/api/webhooks/hebronpay', 'POST', [], [], [], $headers, $body);
        $response = app(HttpKernel::class)->handle($request);

        return $response->getStatusCode();
    }
}

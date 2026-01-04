<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Exception;
use FriendsOfBotble\SePay\SePayClient;
use Illuminate\Http\Request;

class SePayController extends BaseController
{
    public function __construct(protected SePayClient $client)
    {
    }

    public function bankSubAccounts(Request $request): BaseHttpResponse
    {
        $request->validate([
            'bank_account_id' => ['required', 'string'],
        ]);

        try {
            $bankSubAccounts = collect($this->client->bankSubAccounts($request->input('bank_account_id')))
                ->mapWithKeys(fn ($item) => [
                    $item['id'] => "{$item['account_number']}" . ($item['account_holder_name'] ? " - {$item['account_holder_name']}" : ''),
                ])->all();

            return $this
                ->httpResponse()
                ->setData($bankSubAccounts);
        } catch (Exception $e) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function paymentCodes(): BaseHttpResponse
    {
        try {
            $company = $this->client->company();

            return $this
                ->httpResponse()
                ->setData($company['configurations']['payment_code_formats']);
        } catch (Exception $e) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function checkTransaction(Request $request): BaseHttpResponse
    {
        $request->validate([
            'charge_id' => ['required', 'string'],
        ]);

        $payment = Payment::query()
            ->where('charge_id', $request->input('charge_id'))
            ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::COMPLETED])
            ->firstOrFail();

        return $this
            ->httpResponse()
            ->setData([
                'status' => $payment->status,
                'status_html' => $payment->status->toHtml(),
            ]);
    }
}

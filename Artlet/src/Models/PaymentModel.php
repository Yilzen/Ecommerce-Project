<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class PaymentModel
{
    private string $table = 'payment';

    public function create(int $userId, float $amount, string $status = 'pending', string $method = 'debit'): int 
    {
        $payment = R::dispense($this->table);

        $payment->user_id = $userId;
        $payment->amount = $amount;
        $payment->status = $status;
        $payment->method = $method;
        $payment->created_at = date('Y-m-d H:i:s');

        return R::store($payment);
    }

    public function updateStatus(int $id, string $status): void
    {
        $payment = R::load($this->table, $id);

        if ($payment->id) {
            $payment->status = $status;
            R::store($payment);
        }
    }

    public function findByUser(int $userId): array
    {
        return array_values(
            R::find($this->table, 'user_id = ? ORDER BY created_at DESC', [$userId])
        );
    }

    public function findById(int $id): mixed
    {
        $payment = R::load($this->table, $id);

        return $payment->id ? $payment : null;
    }

    public function process(Request $request, Response $response): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }
        $paymentSuccess = true;

        if ($paymentSuccess) {

            $type = $_SESSION['pending_membership'] ?? null;

            if ($type) {
                $this->membershipModel->upgrade((int)$user['id'], $type);

                $_SESSION['user']['membership_status'] = $type;

                unset($_SESSION['pending_membership']);
            }

            return $response
                ->withHeader('Location', $this->basePath . '/profile')
                ->withStatus(302);
        }

        return $response
            ->withHeader('Location', $this->basePath . '/payment?error=1')
            ->withStatus(302);
    }
}
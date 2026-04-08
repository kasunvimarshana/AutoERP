<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class JournalEntryLineResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'account_id'       => $this->account_id,
            'account'          => $this->whenLoaded('account', fn () => new AccountResource($this->account)),
            'description'      => $this->description,
            'debit'            => $this->debit,
            'credit'           => $this->credit,
            'currency_code'    => $this->currency_code,
            'exchange_rate'    => $this->exchange_rate,
            'reference'        => $this->reference,
        ];
    }
}

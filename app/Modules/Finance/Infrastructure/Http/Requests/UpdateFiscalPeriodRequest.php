<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

class UpdateFiscalPeriodRequest extends StoreFiscalPeriodRequest
{
	/** @return array<string, mixed> */
	public function rules(): array
	{
		return array_merge(parent::rules(), ['row_version' => 'required|integer|min:1']);
	}
}

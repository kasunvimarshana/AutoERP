<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

/** @method \Modules\Product\Domain\Entities\ProductAttachment execute(array $data = []) */
interface CreateProductAttachmentServiceInterface extends ServiceInterface {}

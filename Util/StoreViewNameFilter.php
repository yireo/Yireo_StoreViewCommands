<?php declare(strict_types=1);

namespace Yireo\StoreViewCommands\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;

class StoreViewNameFilter
{
    private const MAX_LENGTH = 255;
    private const TRIM_CHARACTERS = " \f\n\r\t\v\x00";

    public function __construct(
        private FilterManager $filterManager
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function filter(string $name): string
    {
        $name = $this->filterManager->removeTags(trim($name, self::TRIM_CHARACTERS));
        $name = trim($name, self::TRIM_CHARACTERS);
        if ($name === '') {
            throw new LocalizedException(__('The store view name may not be empty'));
        }

        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw new LocalizedException(
                __('The store view name may not be longer than %1 characters', self::MAX_LENGTH)
            );
        }

        return $name;
    }
}

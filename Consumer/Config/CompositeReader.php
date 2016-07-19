<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\MessageQueue\Consumer\Config;

use Magento\Framework\MessageQueue\Consumer\Config\ReaderInterface;
use Magento\Framework\Phrase;
use Magento\Framework\MessageQueue\Consumer\Config\Validator;

/**
 * Composite reader for consumer config.
 */
class CompositeReader implements ReaderInterface
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ReaderInterface[]
     */
    private $readers;

    /**
     * Initialize dependencies.
     *
     * @param Validator $validator
     * @param array $readers
     */
    public function __construct(Validator $validator, array $readers)
    {
        $this->validator = $validator;
        $this->readers = [];
        $readers = $this->sortReaders($readers);
        foreach ($readers as $name => $readerInfo) {
            if (!isset($readerInfo['reader']) || !($readerInfo['reader'] instanceof ReaderInterface)) {
                throw new \InvalidArgumentException(
                    new Phrase(
                        'Reader "%name" must implement "%readerInterface"',
                        ['name' => $name, 'readerInterface' => ReaderInterface::class]
                    )
                );
            }
            $this->readers[] = $readerInfo['reader'];
        }
    }

    /**
     * Read config.
     *
     * @param string|null $scope
     * @return array
     */
    public function read($scope = null)
    {
        $result = [];
        foreach ($this->readers as $reader) {
            $result = array_replace_recursive($result, $reader->read($scope));
        }
        $this->validator->validate($result);
        return $result;
    }

    /**
     * Sort readers according to param 'sortOrder'
     *
     * @param array $readers
     * @return array
     */
    private function sortReaders(array $readers)
    {
        usort(
            $readers,
            function ($firstItem, $secondItem) {
                $firstValue = 0;
                $secondValue = 0;
                if (isset($firstItem['sortOrder'])) {
                    $firstValue = intval($firstItem['sortOrder']);
                }

                if (isset($secondItem['sortOrder'])) {
                    $secondValue = intval($secondItem['sortOrder']);
                }

                if ($firstValue == $secondValue) {
                    return 0;
                }
                return $firstValue < $secondValue ? -1 : 1;
            }
        );
        return $readers;
    }
}

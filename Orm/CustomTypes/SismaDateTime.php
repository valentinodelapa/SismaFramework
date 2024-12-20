<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Orm\CustomTypes;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Orm\Interfaces\CustomDateTimeComparableInterface;
use SismaFramework\Orm\Interfaces\CustomDateTimeTriggerableInterface;

/**
 *
 * @author Valentino de Lapa
 */
class SismaDateTime extends \DateTime implements CustomDateTimeComparableInterface, CustomDateTimeTriggerableInterface
{

    private ?BaseEntity $parentEntity = null;

    public function __construct(?BaseEntity $parentEntity = null, string $datetime = "now", ?\DateTimeZone $timezone = null)
    {
        $this->parentEntity = $parentEntity;
        parent::__construct($datetime, $timezone);
    }

    #[\Override]
    public function equals(CustomDateTimeComparableInterface $other): bool
    {
        return $this->getTimestamp() === $other->getTimestamp();
    }

    #[\Override]
    public function injectParentEntity(BaseEntity $parentEntity): void
    {
        $this->parentEntity = $parentEntity;
    }

    #[\Override]
    public function add(\DateInterval $interval): \DateTime
    {
        if ($this->parentEntity instanceof BaseEntity) {
            $this->parentEntity->modified = true;
        }
        return parent::add($interval);
    }

    #[\Override]
    public function sub(\DateInterval $interval): \DateTime
    {
        if ($this->parentEntity instanceof BaseEntity) {
            $this->parentEntity->modified = true;
        }
        return parent::sub($interval);
    }
}

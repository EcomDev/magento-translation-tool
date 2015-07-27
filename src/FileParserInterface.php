<?php

namespace EcomDev\TranslateTool;

/**
 * File parser interface for parsing PHP, XML files
 *
 */
interface FileParserInterface
{
    /**
     * @param $filePath
     * @param $defaultScope
     * @return ExpressionInterface[]
     * @throws \InvalidArgumentException if file is not readable
     */
    public function parse($filePath, $defaultScope);
}

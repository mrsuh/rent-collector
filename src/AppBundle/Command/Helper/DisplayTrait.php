<?php

namespace AppBundle\Command\Helper;

trait DisplayTrait
{
    /**
     * @param $message
     */
    public function debug($message)
    {
        if ('dev' === $this->getContainer()->getParameter('kernel.environment')) {
            echo $message . PHP_EOL;
        }
    }
}

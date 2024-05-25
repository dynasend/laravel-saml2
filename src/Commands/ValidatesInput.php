<?php

namespace Slides\Saml2\Commands;

use Cerbero\CommandValidator\ValidatesInput as BaseValidatesInput;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ValidatesInput
{
    use BaseValidatesInput;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return (int) parent::execute($input, $output);
    }
}

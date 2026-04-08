<?php

namespace YourVendor\LaravelDDDArchitect\Contracts;

interface GeneratorContract
{
    /**
     * Execute the generator, returning a list of created file paths.
     *
     * @return array<string>
     */
    public function generate(): array;
}

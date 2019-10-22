<?php

namespace oleg0296\CurrencyConverter\Interfaces;

interface ConverterInterface
{
    public static function init();

    public function from($code);

    public function to($code);

    public function convert(float $sum): float;

    public function getRate($code);

    public function update($model);

    public function getFrom();

    public function setFrom($model);

    public function getTo();

    public function setTo($model);
}
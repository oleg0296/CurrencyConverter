<?php

namespace oleg0296\CurrencyConverter;

use App\RBCExchangeRate;
use App\Services\CurrencyConverter\Exceptions\RBCConverterException;
use App\Services\CurrencyConverter\Interfaces\ConverterInterface;
use Carbon\Carbon;

class RBCConverter implements ConverterInterface
{
    /** @var string */
    protected const URL = 'http://cbrates.rbc.ru/tsv/';

    /** @var RBCExchangeRate */
    protected $from;

    /** @var RBCExchangeRate */
    protected $to;

    /**
     * @return RBCConverter
     */
    public static function init(): RBCConverter
    {
        return new RBCConverter();
    }

    /**
     * @param $code
     * @return RBCConverter
     * @throws RBCConverterException
     */
    public function from($code): RBCConverter
    {
        $this->from = $this->getRate($code);

        return $this;
    }

    /**
     * @param $code
     * @return RBCConverter
     * @throws RBCConverterException
     */
    public function to($code): RBCConverter
    {
        $this->to = $this->getRate($code);

        return $this;
    }

    /**
     * @param float $sum
     * @return float
     */
    public function convert(float $sum): float
    {
        return $sum * $this->from->rate / $this->from->nominal * $this->to->nominal / $this->to->rate;
    }

    /**
     * @param $code
     * @return RBCExchangeRate|null
     * @throws RBCConverterException
     */
    public function getRate($code): ?RBCExchangeRate
    {
        try {
            return ($this->getRateByStrCode($code)) ? $this->getRateByStrCode($code) : $this->getRateByIntCode($code);
        } catch (RBCConverterException $exception) {
            throw new RBCConverterException('Failed to get exchange rate "' . $code . '"' , null, $exception);
        }
    }

    /**
     * @param $model
     * @return RBCExchangeRate
     * @throws RBCConverterException
     */
    public function update($model): RBCExchangeRate
    {
        if ($model->updated_at >= Carbon::today()) {

            return $model;

        };

        try {

            $rate = $this->getRateFromRBC($model->int_code);
            $model->nominal = $rate['nominal'];
            $model->rate = $rate['rate'];
            $model->save();

            return $model;

        } catch (RBCConverterException $exception) {

            throw new RBCConverterException('Failed to update exchange rate', null, $exception);

        }
    }

    /**
     * @return RBCExchangeRate
     */
    public function getFrom(): RBCExchangeRate
    {
        return $this->from;
    }

    /**
     * @param RBCExchangeRate $model
     */
    public function setFrom($model): void
    {
        $this->from = $model;
    }

    /**
     * @return RBCExchangeRate
     */
    public function getTo(): RBCExchangeRate
    {
        return $this->to;
    }

    /**
     * @param RBCExchangeRate $model
     */
    public function setTo($model): void
    {
        $this->to = $model;
    }

    /**
     * @param $code
     * @return RBCExchangeRate|null
     * @throws RBCConverterException
     */
    protected function getRateByStrCode($code): ?RBCExchangeRate
    {
        $model = RBCExchangeRate::where('str_code', $code)->first();

        if ($model === null) {
            return null;
        }

        try {

            return $this->update($model);

        } catch (RBCConverterException $exception) {

            throw new RBCConverterException('Failed to get exchange rate by "str_code=' . $code . '"', null, $exception);

        }
    }

    /**
     * @param $code
     * @return RBCExchangeRate
     * @throws RBCConverterException
     */
    protected function getRateByIntCode($code): RBCExchangeRate
    {
        $model = RBCExchangeRate::where('int_code', $code)->first();

        if ($model === null) {
            return null;
        }

        try {

            return $this->update($model);

        } catch (RBCConverterException $exception) {

            throw new RBCConverterException('Failed to get exchange rate by "int_code=' . $code . '"', null, $exception);

        }
    }

    /**
     * @param int $code
     * @return array
     * @throws RBCConverterException
     */
    protected function getRateFromRBC(int $code): array
    {
        if($code === 643 || $code === 810) return ['nominal' => 1, 'rate' => 1];

        $url = static::URL.$code.'/'.date('Y/m/d').'.tsv';
        $result = explode("\t", file_get_contents($url));

        if (!isset($result[1]) || empty($result[1])) {
            throw new RBCConverterException('Failed to get exchange rate from '.$url);
        }

        return [
            'nominal' => $result[0],
            'rate' => $result[1],
        ];
    }
}